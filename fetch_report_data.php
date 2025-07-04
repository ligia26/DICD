<?php

include 'includes/db.php';
include 'includes/functions.php';
require_once 'includes/config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

$user_id = $_SESSION['user_id'];
$user_data = getUserData($conn, $user_id);

$company = isset($user_data['company']) ? $user_data['company'] : '';
$company_details = getCompanyName($conn, $company);
$company_name = isset($company_details['name']) ? $company_details['name'] : '';
$is_admin = $user_data['admin'];

$from_date = isset($_POST['from_date']) ? $_POST['from_date'] : date('Y-m-d');
$to_date = isset($_POST['to_date']) ? $_POST['to_date'] : date('Y-m-d');

if (!$from_date || !$to_date) {
    echo json_encode(['error' => 'Invalid date input']);
    exit;
}


function getSparkPostSendingDomainReport($apiKey, $from_date, $to_date, $domains = [])
{
    $params = [
        'from' => $from_date . 'T00:00',
        'to' => $to_date . 'T23:59',
        'metrics' => 'count_injected',
        'timezone' => 'Europe/Madrid',
        'limit' => 1000,
        'order_by' => 'count_injected'
    ];

    if (!empty($domains)) {
        $params['sending_domains'] = implode(',', $domains);
    }

    $url = 'https://api.eu.sparkpost.com/api/v1/metrics/deliverability/sending-domain?' . http_build_query($params);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: ' . $apiKey,
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!$response) {
        die('Error: "' . curl_error($ch) . '" - Code: ' . curl_errno($ch));
    }

    if ($http_status !== 200) {
        die('API request failed with status ' . $http_status . ': ' . $response);
    }

    return json_decode($response, true);
}

function processSendingDomainData($data, $conn)
{
    $processedData = [];

    foreach ($data['results'] as $result) {
        $domain = $result['sending_domain'];
        $count_injected = $result['count_injected'];

        $company_name = getCompanyNameByDomain($domain, $conn);

        $processedData[] = [
            'name' => $domain,
            'y' => $count_injected,
            'company' => $company_name
        ];
    }

    return $processedData;
}
$query = "SELECT domain FROM sending_domains WHERE is_sparkpost = 1";
if (!$is_admin) {
    $query .= " AND company = ?"; // Only fetch domains from this user's company
}

$stmt = $conn->prepare($query);
if (!$is_admin) {
    $stmt->bind_param("s", $company);
}
$stmt->execute();
$result = $stmt->get_result();

$user_domains = [];
while ($row = $result->fetch_assoc()) {
    $user_domains[] = $row['domain'];
}
$stmt->close();

try {
    $apiKey = Config::getRequired('SPARKPOST_API_KEY');
} catch (Exception $e) {
    die(json_encode(['error' => 'Configuration error: ' . $e->getMessage()]));
}

if (!$is_admin && empty($user_domains)) {
    echo json_encode([
        'processedData' => [],
        'domains_with_no_sends' => [],
        'trendData' => [],
        'totalSendingData' => ['name' => 'Total Emails Sent', 'data' => []],
        'dates' => [],
        'companyData' => []
    ]);
    exit;
}

// If admin, get all sending-domain data; otherwise, get only the user’s domains
$reportData = getSparkPostSendingDomainReport(
    $apiKey,
    $from_date,
    $to_date,
    $is_admin ? [] : $user_domains
);



$processedData = ($reportData && isset($reportData['results'])) ? processSendingDomainData($reportData, $conn) : [];
$domains_with_sends = array_column($processedData, 'name');
$domains_with_no_sends = array_values(array_diff($user_domains, $domains_with_sends));


$companyAgg = [];
foreach ($processedData as $entry) {
    $companyName = $entry['company'];
    $countInjected = $entry['y'];

    if (!isset($companyAgg[$companyName])) {
        $companyAgg[$companyName] = 0;
    }
    $companyAgg[$companyName] += $countInjected;
}

$companyData = [];
foreach ($companyAgg as $companyName => $totalCount) {
    $companyData[] = [
        'name' => $companyName,
        'y'    => $totalCount
    ];
}

$last_fifteen_days_data = [];
for ($i = 0; $i < 15; $i++) {
    $date = date('Y-m-d', strtotime("-" . $i . " days"));
    $reportDataForDay = getSparkPostSendingDomainReport($apiKey, $date, $date, $user_domains);

    if ($reportDataForDay && isset($reportDataForDay['results'])) {
        $last_fifteen_days_data[$date] = processSendingDomainData($reportDataForDay, $conn);
    } else {
        $last_fifteen_days_data[$date] = [];
    }
}

// Aggregate data by domain
$trend_data = [];
foreach ($last_fifteen_days_data as $date => $data) {
    foreach ($data as $entry) {
        $domain = $entry['name'];
        $count = $entry['y'];

        if (!isset($trend_data[$domain])) {
            $trend_data[$domain] = [
                'name' => $domain,
                'data' => array_fill(0, count($last_fifteen_days_data), 0)
            ];
        }

        $date_index = array_search($date, array_keys($last_fifteen_days_data));
        if ($date_index !== false) {
            $trend_data[$domain]['data'][$date_index] = $count;
        }
    }
}


// Calculate total emails sent per day for new Chart 7
$total_daily_sending = [];

foreach ($last_fifteen_days_data as $date => $data) {
    $total_count = 0;
    
    foreach ($data as $entry) {
        $total_count += $entry['y']; // Sum counts for all domains
    }

    $total_daily_sending[] = $total_count;
}

// Prepare total sending data for Chart 7
$total_sending_chart_data = [
    'name' => 'Total Emails Sent',
    'data' => array_reverse($total_daily_sending) // Reverse to match date order
];



$response = [
    'processedData' => $processedData,
    'domains_with_no_sends' => $domains_with_no_sends,

    'trendData' => array_values($trend_data),
    'totalSendingData' => $total_sending_chart_data, // New data for Chart 7
    'dates' => array_keys($last_fifteen_days_data)
];



$response['companyData'] = $companyData;

header('Content-Type: application/json');
echo json_encode($response);
?>
