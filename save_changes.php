<?php
// Set the default timezone to Spain
date_default_timezone_set('Europe/Madrid');

include "includes/db.php";
include "includes/functions.php";
require 'vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
$log_file = 'debug.log';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['data']) && isset($_POST['user_id'])) {

   // file_put_contents($log_file, file_get_contents('php://input'), FILE_APPEND);

    $data = $_POST['data'];
    $user_id = $_POST['user_id'];
    $currentTimestamp = date('Y-m-d H:i:s');

    // Log file path
    $log_file = 'debug.log';
    file_put_contents($log_file, "Request received\n", FILE_APPEND);

    // Function to safely escape and format SQL values
    function escapeValue($value, $conn) {
        if (is_null($value) || $value === '' || trim($value) === '') {
            return 'NULL';
        } elseif (is_numeric($value)) {
            return $value;
        } else {
            return "'" . $conn->real_escape_string($value) . "'";
        }
    }
    

    // Fetch S3 directory for the user
    // Get the sending domain from the first row
    $sending_domain = $data[0]['sending_domain'] ?? '';

    // SQL query to get s3_dir using the sending domain
    $company_query = "
        SELECT c.s3_dir 
        FROM companies c 
        JOIN sending_domains sd ON c.id = sd.company 
        WHERE sd.domain = '$sending_domain'
    ";

    // Execute the query
    $company_result = $conn->query($company_query);

    if ($company_result === false || $company_result->num_rows == 0) {
        $error = "Company query error: " . htmlspecialchars($conn->error);
        file_put_contents($log_file, $error . "\n", FILE_APPEND);
        die($error);
    }

    $company = $company_result->fetch_assoc();
    $s3_dir = $company['s3_dir'];

    // Prepare the data for CSV upload
    $csvHeader = [
        'sending_domain', 'user_domain', 'country', 'manual_category', 'expected_volume_increment',
        'date_start', 'date_end', 'quien', 'current_auto_rule', 'percent_lwor', 'percent_ldor',
        'current_sent', 'sendable_user', 'clickers', 'openers', 'reactivated', 'preactivated',
        'halfslept', 'awaken', 'whitelist', 'precached', 'zeroclicks', 'new', 'aos', 'slept',
        'keepalive', 'stranger', 'new_inactive', 'total_inactive'
    ];

    $csvData = [];
    foreach ($data as $row) {
        $csvData[] = [
            $row['sending_domain'] ?? '',
            $row['user_domain'] ?? '',
            $row['country'] ?? '',
            $row['manual_category'] ?? '',
            $row['expected_volume_increment'] ?? '',
            $row['date_start'] ?? '',
            $row['date_end'] ?? '',
            $row['quien'] ?? '',
            $row['current_auto_rule'] ?? '',
            $row['percent_lwor'] ?? '',
            $row['percent_ldor'] ?? '',
            $row['current_sent'] ?? '',
            $row['sendable_user'] ?? '',
            $row['clickers'] ?? '',
            $row['openers'] ?? '',
            $row['reactivated'] ?? '',
            $row['preactivated'] ?? '',
            $row['halfslept'] ?? '',
            $row['awaken'] ?? '',
            $row['whitelist'] ?? '',
            $row['precached'] ?? '',
            $row['zeroclicks'] ?? '',
            $row['new'] ?? '',
            $row['aos'] ?? '',
            $row['slept'] ?? '',
            $row['keepalive'] ?? '',
            $row['stranger'] ?? '',
            $row['new_inactive'] ?? '',
            $row['total_inactive'] ?? ''
        ];
    }

    
    // Convert data array to CSV format
    $csvContent = implode(',', $csvHeader) . "\n"; // Add the header row
    foreach ($csvData as $fields) {
        $csvContent .= implode(',', $fields) . "\n";
    }

    // Create a temporary CSV file
    $tempFilePath = tempnam(sys_get_temp_dir(), 'data_') . '.csv';
    file_put_contents($tempFilePath, $csvContent);

    // Function to upload file to S3
    function uploadToS3($filePath, $bucket, $key) {
        $s3 = new S3Client([
            'version' => 'latest',
            'region'  => 'eu-west-3',
           
        ]);




        try {
            $result = $s3->putObject([
                'Bucket' => $bucket,
                'Key'    => $key,
                'SourceFile' => $filePath,
                'ACL'    => 'private', // or 'private' depending on your requirements
            ]);
            return $result['ObjectURL'];
        } catch (AwsException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    // Upload the CSV file to S3
    $bucket = 'datainnovation.inbound';
    $key = $s3_dir . '/sources/dashboard/manual-rules/' . $sending_domain. '.csv';
    // $key = $s3_dir . '/' . basename($tempFilePath);
    $uploadResult = uploadToS3($tempFilePath, $bucket, $key);

    if ($uploadResult !== false) {
        file_put_contents($log_file, "Upload to S3 successful: $uploadResult\n", FILE_APPEND);
    } else {
        file_put_contents($log_file, "Upload to S3 failed\n", FILE_APPEND);
        die("Upload to S3 failed");
    }

    // Delete the temporary file
    unlink($tempFilePath);
    foreach ($data as $row) {
        $sending_domain = $row['sending_domain'] ?? '';
        $user_domain = $row['user_domain'] ?? '';
        $country = $row['country'] ?? '';
        $manual_category = ($row['manual_category'] === 'None' || $row['manual_category'] === '') ? null : $row['manual_category'];
        $expected_volume_increment = isset($row['expected_volume_increment']) && is_numeric($row['expected_volume_increment']) ? $row['expected_volume_increment'] :  '';
        $date_start = empty($row['date_start']) ? null : $row['date_start'];
        $date_end = empty($row['date_end']) ? null : $row['date_end'];
        $quien = $row['quien'] ?? '';
        $current_auto_rule = $row['current_auto_rule'] ?? '';
        $percent_lwor = isset($row['percent_lwor']) && is_numeric($row['percent_lwor']) ? $row['percent_lwor'] : '';
        $percent_ldor = isset($row['percent_ldor']) && is_numeric($row['percent_ldor']) ? $row['percent_ldor'] : '';
        $current_sent = isset($row['current_sent']) && is_numeric($row['current_sent']) ? $row['current_sent'] : '';
        $sendable_user = isset($row['sendable_user']) && is_numeric($row['sendable_user']) ? $row['sendable_user'] : '';
        $clickers = isset($row['clickers']) && is_numeric($row['clickers']) ? $row['clickers'] :'';
        $openers = isset($row['openers']) && is_numeric($row['openers']) ? $row['openers'] : '';
        $reactivated = isset($row['reactivated']) && is_numeric($row['reactivated']) ? $row['reactivated'] : '';
        $preactivated = isset($row['preactivated']) && is_numeric($row['preactivated']) ? $row['preactivated'] : '';
        $halfslept = isset($row['halfslept']) && is_numeric($row['halfslept']) ? $row['halfslept'] : '';
        $awaken = isset($row['awaken']) && is_numeric($row['awaken']) ? $row['awaken'] : '';
        $whitelist = isset($row['whitelist']) && is_numeric($row['whitelist']) ? $row['whitelist'] : '';
        $precached = isset($row['precached']) && is_numeric($row['precached']) ? $row['precached'] : '';
        $zeroclicks = isset($row['zeroclicks']) && is_numeric($row['zeroclicks']) ? $row['zeroclicks'] : '';
        $new = isset($row['new']) && is_numeric($row['new']) ? $row['new'] : '';
        $aos = isset($row['aos']) && is_numeric($row['aos']) ? $row['aos'] : '';
        $slept = isset($row['slept']) && is_numeric($row['slept']) ? $row['slept'] : '';
        $keepalive = isset($row['keepalive']) && is_numeric($row['keepalive']) ? $row['keepalive'] : '';
        $stranger = isset($row['stranger']) && is_numeric($row['stranger']) ? $row['stranger'] : '';
        $new_inactive = isset($row['new_inactive']) && is_numeric($row['new_inactive']) ? $row['new_inactive'] : '';
        $total_inactive = isset($row['total_inactive']) && is_numeric($row['total_inactive']) ? $row['total_inactive'] : '';

        // Prepare the SQL query for checking existing records
        $check_query = "SELECT COUNT(*) FROM config_changes WHERE sending_domain = '" . $conn->real_escape_string($sending_domain) . "' AND user_domain = '" . $conn->real_escape_string($user_domain) . "' AND user_id = '" . $conn->real_escape_string($user_id) . "' AND country = '" . $conn->real_escape_string($country) . "'";
        $check_result = $conn->query($check_query);

        if ($check_result === false) {
            $error = "Check error: " . htmlspecialchars($conn->error);
            file_put_contents($log_file, $error . "\n", FILE_APPEND);
            echo $error;
        } else {
            $record_count = $check_result->fetch_row()[0];
            file_put_contents($log_file, "Record count: " . $record_count . "\n", FILE_APPEND);

            if ($record_count > 0) {

                
                // Construct the update query
                $update_query = "UPDATE config_changes SET 
                manual_category = " . escapeValue($manual_category, $conn) . ", 
                updated_at = " . escapeValue($currentTimestamp, $conn) . ", 
                expected_volume_increment = " . escapeValue($expected_volume_increment, $conn) . ", 
                date_start = " . escapeValue($date_start, $conn) . ", 
                date_end = " . escapeValue($date_end, $conn) . ", 
                quien = " . escapeValue($quien, $conn) . ", 
                current_auto_rule = " . escapeValue($current_auto_rule, $conn) . ", 
                percent_lwor = " . escapeValue($percent_lwor, $conn) . ", 
                percent_ldor = " . escapeValue($percent_ldor, $conn) . ", 
                current_sent = " . escapeValue($current_sent, $conn) . ", 
                sendable_user = " . escapeValue($sendable_user, $conn) . ", 
                clickers = " . escapeValue($clickers, $conn) . ", 
                openers = " . escapeValue($openers, $conn) . ", 
                reactivated = " . escapeValue($reactivated, $conn) . ", 
                preactivated = " . escapeValue($preactivated, $conn) . ", 
                halfslept = " . escapeValue($halfslept, $conn) . ", 
                awaken = " . escapeValue($awaken, $conn) . ", 
                whitelist = " . escapeValue($whitelist, $conn) . ", 
                precached = " . escapeValue($precached, $conn) . ", 
                zeroclicks = " . escapeValue($zeroclicks, $conn) . ", 
                new = " . escapeValue($new, $conn) . ", 
                aos = " . escapeValue($aos, $conn) . ", 
                slept = " . escapeValue($slept, $conn) . ", 
                keepalive = " . escapeValue($keepalive, $conn) . ", 
                stranger = " . escapeValue($stranger, $conn) . ", 
                new_inactive = " . escapeValue($new_inactive, $conn) . ", 
                total_inactive = " . escapeValue($total_inactive, $conn) . " 
            WHERE sending_domain = " . escapeValue($sending_domain, $conn) . " 
            AND user_domain = " . escapeValue($user_domain, $conn) . " 
            AND user_id = " . escapeValue($user_id, $conn) . " 
            AND country = " . escapeValue($country, $conn);
            



                // Execute the update query
                if ($conn->query($update_query) === false) {
                    $error = "Update error: " . htmlspecialchars($conn->error);
                    file_put_contents($log_file, $error . "\n", FILE_APPEND);
                    echo $error;
                } else {
                    file_put_contents($log_file, "Update successful\n", FILE_APPEND);
                    echo "Update successful\n";
                }
            } else {
                // Construct the insert query
                $insert_query = "INSERT INTO config_changes (
                    sending_domain, user_domain, country, manual_category, user_id, updated_at, 
                    expected_volume_increment, date_start, date_end, quien, current_auto_rule, 
                    percent_lwor, percent_ldor, current_sent, sendable_user, clickers, openers, 
                    reactivated, preactivated, halfslept, awaken, whitelist, precached, 
                    zeroclicks, new, aos, slept, keepalive, stranger, new_inactive, total_inactive
                ) VALUES (
                    " . escapeValue($sending_domain, $conn) . ", 
                    " . escapeValue($user_domain, $conn) . ", 
                    " . escapeValue($country, $conn) . ", 
                    " . escapeValue($manual_category, $conn) . ", 
                    " . escapeValue($user_id, $conn) . ", 
                    " . escapeValue($currentTimestamp, $conn) . ", 
                    " . escapeValue($expected_volume_increment, $conn) . ", 
                    " . escapeValue($date_start, $conn) . ", 
                    " . escapeValue($date_end, $conn) . ", 
                    " . escapeValue($quien, $conn) . ", 
                    " . escapeValue($current_auto_rule, $conn) . ", 
                    " . escapeValue($percent_lwor, $conn) . ", 
                    " . escapeValue($percent_ldor, $conn) . ", 
                    " . escapeValue($current_sent, $conn) . ", 
                    " . escapeValue($sendable_user, $conn) . ", 
                    " . escapeValue($clickers, $conn) . ", 
                    " . escapeValue($openers, $conn) . ", 
                    " . escapeValue($reactivated, $conn) . ", 
                    " . escapeValue($preactivated, $conn) . ", 
                    " . escapeValue($halfslept, $conn) . ", 
                    " . escapeValue($awaken, $conn) . ", 
                    " . escapeValue($whitelist, $conn) . ", 
                    " . escapeValue($precached, $conn) . ", 
                    " . escapeValue($zeroclicks, $conn) . ", 
                    " . escapeValue($new, $conn) . ", 
                    " . escapeValue($aos, $conn) . ", 
                    " . escapeValue($slept, $conn) . ", 
                    " . escapeValue($keepalive, $conn) . ", 
                    " . escapeValue($stranger, $conn) . ", 
                    " . escapeValue($new_inactive, $conn) . ", 
                    " . escapeValue($total_inactive, $conn) . "
                )";

                // Execute the insert query
                if ($conn->query($insert_query) === false) {
                    $error = "Insert error: " . htmlspecialchars($conn->error);
                    file_put_contents($log_file, $error . "\n", FILE_APPEND);
                    echo $error;
                } else {
                    file_put_contents($log_file, "Insert successful\n", FILE_APPEND);
                    echo "Insert successful\n";
                }
            }
        }
    }

   /* $insert_querys = "INSERT INTO conf_changes_log (
        sending_domain, user_domain, country, manual_category, user_id, updated_at, 
        expected_volume_increment, date_start, date_end, quien, current_auto_rule, 
        percent_lwor, percent_ldor, current_sent, sendable_user, clickers, openers, 
        reactivated, preactivated, halfslept, awaken, whitelist, precached, 
        zeroclicks, new, aos, slept, keepalive, stranger, new_inactive, total_inactive
    ) VALUES (
        " . escapeValue($sending_domain, $conn) . ", 
        " . escapeValue($user_domain, $conn) . ", 
        " . escapeValue($country, $conn) . ", 
        " . escapeValue($manual_category, $conn) . ", 
        " . escapeValue($user_id, $conn) . ", 
        " . escapeValue($currentTimestamp, $conn) . ", 
        " . escapeValue($expected_volume_increment, $conn) . ", 
        " . escapeValue($date_start, $conn) . ", 
        " . escapeValue($date_end, $conn) . ", 
        " . escapeValue($quien, $conn) . ", 
        " . escapeValue($current_auto_rule, $conn) . ", 
        " . escapeValue($percent_lwor, $conn) . ", 
        " . escapeValue($percent_ldor, $conn) . ", 
        " . escapeValue($current_sent, $conn) . ", 
        " . escapeValue($sendable_user, $conn) . ", 
        " . escapeValue($clickers, $conn) . ", 
        " . escapeValue($openers, $conn) . ", 
        " . escapeValue($reactivated, $conn) . ", 
        " . escapeValue($preactivated, $conn) . ", 
        " . escapeValue($halfslept, $conn) . ", 
        " . escapeValue($awaken, $conn) . ", 
        " . escapeValue($whitelist, $conn) . ", 
        " . escapeValue($precached, $conn) . ", 
        " . escapeValue($zeroclicks, $conn) . ", 
        " . escapeValue($new, $conn) . ", 
        " . escapeValue($aos, $conn) . ", 
        " . escapeValue($slept, $conn) . ", 
        " . escapeValue($keepalive, $conn) . ", 
        " . escapeValue($stranger, $conn) . ", 
        " . escapeValue($new_inactive, $conn) . ", 
        " . escapeValue($total_inactive, $conn) . "
    )";

    // Execute the insert query
    file_put_contents($log_file, "Preparing to execute insert query for conf_changes_log: $insert_querys\n", FILE_APPEND);
*/
    // Execute the insert query
    // Additional Logging After Execution
    
    $log_stmt->close();

    // Close the connection
    $conn->close();
    echo "Changes saved successfully!";
}
?>
