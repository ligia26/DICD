<?php
require 'vendor/autoload.php';
include 'includes/db.php'; 

use Aws\Athena\AthenaClient;
use Aws\Credentials\CredentialProvider; // Add this line

// MySQL Database Credentials
// (Assuming $servername, $username, $password, and $dbname are defined in includes/db.php)

// AWS Athena Credentials
$athena_client = new AthenaClient([
    'version' => 'latest',
    'region'  => 'eu-west-3', // Specify your AWS region
    'suppress_php_deprecation_warning' => true,

    'credentials' => CredentialProvider::ini('default', '/home/www-data/.aws/credentials'),
]);

// Dynamic Dates for Athena Query
$year = date('Y');
$month = date('m');
$day = date('d');

// Fetch domains and associated Athena table name
function fetchDomainsWithAthenaNames($conn)
{
    $sql = "SELECT sending_domains.domain, sending_domains.company, companies.athena_name
            FROM sending_domains
            LEFT JOIN companies ON sending_domains.company = companies.id where companies.id = 1";
    $result = $conn->query($sql);

    $domains = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $domains[] = $row;
        }
    }
    return $domains;
}

// Fetch campaign data from Mautic for a specific domain
function fetchMauticCampaigns($mautic_url, $username, $password)
{


    if (strpos($mautic_url, "https://crm.cpcseguro.com") === 0) {
        // Replace "https://crm." with "https://m."
        $mautic_url = str_replace("https://crm.cpcseguro.com", "https://m3.cpcseguro.com", $mautic_url);
    }



    // Check if $mautic_url starts with "https://crm."
    if (strpos($mautic_url, "https://crm.") === 0) {
        // Replace "https://crm." with "https://m."
        $mautic_url = str_replace("https://crm.", "https://m.", $mautic_url);
    }


    echo "<br>";

    echo "the domain I am working on is " . $mautic_url;

    echo "<br>";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $mautic_url . "/api/emails?orderBy=id&orderByDir=DESC&limit=20");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        die("cURL Error: " . curl_error($ch));
    }
    curl_close($ch);

    $data = json_decode($response, true);
    return $data['emails'] ?? [];



    
    echo "<br>";

    echo "the emails I am working on is " . $data['emails'];

    echo "<br>";
}

// Query Athena for scheduled and sent counts
function runAthenaQuery($client, $athena_name, $domain) {
    // Start the query
    $queryString = "
        SELECT SUM(scheduled) AS scheduled, 
               SUM(sent) AS sent,
               CAST(SUM(sent) AS decimal(18, 2)) / NULLIF(CAST(SUM(scheduled) AS decimal(18, 2)), 0) AS sent_ratio
        FROM ${athena_name}.events_grouped_dimension
        WHERE year = 2025
          AND month = '01'
          AND day = '10'
          AND sending_domain = '{$domain}';
    ";

    echo $queryString."\n";

    $startResult = $client->startQueryExecution([
        'QueryString' => $queryString,
        'ResultConfiguration' => [
            'OutputLocation' => 's3://datainnovation.athenaqueryresults2022b/sending_status_report/',
        ],
    ]);

    // Get the QueryExecutionId
    $queryExecutionId = $startResult->get('QueryExecutionId');

    // Poll every 1 second until the query is finished or fails
    while (true) {
        $status = $client->getQueryExecution([
            'QueryExecutionId' => $queryExecutionId
        ]);
        $state = $status['QueryExecution']['Status']['State'];

        if ($state === 'SUCCEEDED') {
            // Query finished successfully, we can retrieve results
            break;
        } elseif (in_array($state, ['FAILED', 'CANCELLED'])) {
            // Stop if query fails or is cancelled
            throw new Exception("Athena query {$queryExecutionId} ended in state: {$state}");
        }

        // If it's still QUEUED or RUNNING, wait 1 second and check again
        sleep(1);
    }

    // Now retrieve the results
    $results = $client->getQueryResults(['QueryExecutionId' => $queryExecutionId]);
    $rows = $results['ResultSet']['Rows'];

    // Skip header row
    if (count($rows) > 1) {
        $data = $rows[1]['Data'];
        return [
            'scheduled'  => $data[0]['VarCharValue'] ?? 0,
            'sent'       => $data[1]['VarCharValue'] ?? 0,
            'sent_ratio' => $data[2]['VarCharValue'] ?? 0,
        ];
    }
    return [];
}


// Insert or update data into sending_status
// (Campaign-level info, no more vm_count here)
function insertOrUpdateSendingStatus($conn, $domain, $company, $pendingMautic, $pendingDates, $setoffMautic, $setoffDates, $mauticSent, $evaluation, $lastUpdate)
{
    $sql = "INSERT INTO sending_status (domain, company, pending_mautic, pending_names_dates, setoff_mautic, setoff_names_dates, mautic_sent, evaluation, last_update)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                company              = VALUES(company),
                pending_names_dates  = VALUES(pending_names_dates),
                setoff_mautic       = VALUES(setoff_mautic),
                setoff_names_dates   = VALUES(setoff_names_dates),
                mautic_sent         = VALUES(mautic_sent),
                evaluation          = VALUES(evaluation),
                last_update         = VALUES(last_update)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "sssssssss",
        $domain,
        $company,
        $pendingMautic,
        $pendingDates,
        $setoffMautic,
        $setoffDates,
        $mauticSent,
        $evaluation,
        $lastUpdate
    );

    if ($stmt->execute()) {
        echo "Record inserted/updated successfully for domain: $domain\n";
    } else {
        echo "Error inserting/updating record for domain $domain: " . $conn->error . "\n";
    }
    $stmt->close();
}

// Insert or update domain-level data into sending_status_schedule
function insertOrUpdateSendingStatusSchedule($conn, $domain, $company, $vmCount, $lastUpdate)
{
    $sql = "INSERT INTO sending_status_schedule (domain, company, count_vm, last_update)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                company     = VALUES(company),
                count_vm    = VALUES(count_vm),
                last_update = VALUES(last_update)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $domain, $company, $vmCount, $lastUpdate);

    if ($stmt->execute()) {
        echo "Record inserted/updated successfully in sending_status_schedule for domain: $domain\n";
    } else {
        echo "Error inserting/updating record in sending_status_schedule for domain $domain: " . $conn->error . "\n";
    }
    $stmt->close();
}

// Main Execution
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch domains and their Athena table names
$domains = fetchDomainsWithAthenaNames($conn);

// Process each domain
foreach ($domains as $entry) {
    $domain      = $entry['domain'];
    $company     = $entry['company'];
    $athena_name = $entry['athena_name'];

    if (!$athena_name) {
        echo "Skipping domain $domain due to missing Athena table name.\n";
        continue;
    }

    echo "Processing domain: $domain using Athena table: $athena_name\n";

    try {
        $athenaData = runAthenaQuery($athena_client, $athena_name, $domain);
    } catch (Exception $e) {
        echo "Athena Error: " . $e->getMessage() . "\n";
        $athenaData = [];
    }
    // Insert/Update domain-level data in sending_status_schedule
    $vmCount    = $athenaData['scheduled'] ?? "0";
    $lastUpdate = date('Y-m-d H:i:s');
    insertOrUpdateSendingStatusSchedule($conn, $domain, $company, $vmCount, $lastUpdate);

    // Generate the Mautic instance URL dynamically based on the domain
    $mautic_url       = "https://" . $domain;
    $mauticCampaigns  = fetchMauticCampaigns($mautic_url, "admin", "mW4{oF0~DIrE0.hY9}");

    // Today's date in Y-m-d format
    $today = (new DateTime('today'))->format('Y-m-d');

    // Process campaigns while filtering by today's publishUp date
    foreach ($mauticCampaigns as $campaign) {
 // Convert publishUp to Spain time
 $publishUp = isset($campaign['publishUp']) 
 ? (new DateTime($campaign['publishUp'], new DateTimeZone('UTC')))
       ->setTimezone(new DateTimeZone('Europe/Madrid'))
       ->format('Y-m-d H:i:s') 
 : null;

$today = (new DateTime('now', new DateTimeZone('Europe/Madrid')))->format('Y-m-d');

// Compare only the date part of publishUp with today's date
if ((new DateTime($publishUp))->format('Y-m-d') !== $today) {
continue;
}



        // Extract campaign details
        $name      = $campaign['name'];
        $sentCount = $campaign['sentCount'];

        // Prepare data for database insertion
        $pendingMautic = $name;
        $pendingDates  = $publishUp;
        $setoffMautic  = "Setoff Data";  // Adjust as needed
        $setoffDates   = "Setoff Dates"; // Adjust as needed
        $mauticSent    = $sentCount;
        $evaluation    = $athenaData['sent_ratio'] ?? "N/A";
        $lastUpdate    = date('Y-m-d H:i:s');

        // Insert/Update campaign-level data in sending_status
        insertOrUpdateSendingStatus(
            $conn,
            $domain,
            $company,
            $pendingMautic,
            $pendingDates,
            $setoffMautic,
            $setoffDates,
            $mauticSent,
            $evaluation,
            $lastUpdate
        );
    }
}

$conn->close();
?>
