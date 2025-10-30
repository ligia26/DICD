<?php

include 'includes/db.php';
include 'includes/functions.php';

// Performance optimizations
ini_set('max_execution_time', 120);
ini_set('memory_limit', '256M');

error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

try {
    $apiKey = Config::getRequired('SPARKPOST_API_KEY');
} catch (Exception $e) {
    die(json_encode(['error' => 'Configuration error: ' . $e->getMessage()]));
}
// Add timing for performance monitoring
$start_time = microtime(true);

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
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Add timeout
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // Add connection timeout
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: ' . $apiKey,
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($response === false || !empty($curl_error)) {
        error_log("CURL Error: " . $curl_error);
        return ['error' => 'API connection failed: ' . $curl_error];
    }

    if ($http_status !== 200) {
        error_log("API Error: Status $http_status, Response: $response");
        return ['error' => "API request failed with status $http_status"];
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
$apiKey = 'd684678cb33e9cc270b73868a83201fd4d0b44b2';

// Create cache key for this request (include today's date for decrease comparison)
$today_date = date('Y-m-d');
$cache_key = "sparkpost_data_{$from_date}_{$to_date}_{$today_date}_" . ($is_admin ? 'admin' : $company) . "_" . md5(serialize($user_domains));

// Try to get cached data first
$cached_response = getCachedData($cache_key);
if ($cached_response !== null) {
    // Add cache hit indicator
    $cached_response['cached'] = true;
    $cached_response['execution_time'] = '0.01 seconds (cached)';
    header('Content-Type: application/json');
    echo json_encode($cached_response);
    exit;
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

// Simple caching mechanism
function getCachedData($cache_key) {
    $cache_file = 'cache/' . md5($cache_key) . '.json';
    $cache_duration = 180; // 3 minutes cache (reduced for more current data)
    
    if (!file_exists('cache')) {
        mkdir('cache', 0755, true);
    }
    
    if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $cache_duration) {
        return json_decode(file_get_contents($cache_file), true);
    }
    
    return null;
}

function setCachedData($cache_key, $data) {
    $cache_file = 'cache/' . md5($cache_key) . '.json';
    
    if (!file_exists('cache')) {
        mkdir('cache', 0755, true);
    }
    
    file_put_contents($cache_file, json_encode($data));
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

// Calculate domains with significant decrease (>50% from yesterday to today) - Admin only
$domains_with_decrease = [];

if ($is_admin) {
    // Get today's and yesterday's data
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));

    $today_data = getSparkPostSendingDomainReport($apiKey, $today, $today, []);
    $yesterday_data = getSparkPostSendingDomainReport($apiKey, $yesterday, $yesterday, []);

    if ($today_data && isset($today_data['results']) && $yesterday_data && isset($yesterday_data['results'])) {
        // Process today's data
        $today_counts = [];
        foreach ($today_data['results'] as $result) {
            $today_counts[$result['sending_domain']] = $result['count_injected'];
        }
        
        // Process yesterday's data
        $yesterday_counts = [];
        foreach ($yesterday_data['results'] as $result) {
            $yesterday_counts[$result['sending_domain']] = $result['count_injected'];
        }
        
        // Compare and find domains with >50% decrease
        foreach ($yesterday_counts as $domain => $yesterday_count) {
            $today_count = isset($today_counts[$domain]) ? $today_counts[$domain] : 0;
            
            // Only consider domains that had significant volume yesterday (>100 emails)
            if ($yesterday_count > 100) {
                $decrease_percentage = (($yesterday_count - $today_count) / $yesterday_count) * 100;
                
                if ($decrease_percentage > 50) {
                    $domains_with_decrease[] = [
                        'name' => $domain,
                        'y' => $decrease_percentage,
                        'yesterday' => $yesterday_count,
                        'today' => $today_count
                    ];
                }
            }
        }
        
        // Sort by decrease percentage (highest first)
        usort($domains_with_decrease, function($a, $b) {
            return $b['y'] <=> $a['y'];
        });
    }
}




$scheduled_comparison = [];
if (!empty($processedData)) {
    // Get all relevant domains from SparkPost data
    $sparkpost_domains = array_column($processedData, 'name');
    $placeholders = implode(',', array_fill(0, count($sparkpost_domains), '?'));
    $sql = "SELECT sending_domain, scheduled_count FROM scheduled_domain_counts WHERE sending_domain IN ($placeholders)";
    $stmt = $conn->prepare($sql);
    $types = str_repeat('s', count($sparkpost_domains));
    $stmt->bind_param($types, ...$sparkpost_domains);
    $stmt->execute();
    $result = $stmt->get_result();
    $scheduled_map = [];
    while ($row = $result->fetch_assoc()) {
        $scheduled_map[$row['sending_domain']] = is_numeric($row['scheduled_count']) ? (int)$row['scheduled_count'] : 0;
    }
    $stmt->close();
    // Prepare comparison data for chart
    foreach ($processedData as $entry) {
        $domain = $entry['name'];
        $sent = $entry['y'];
        $scheduled = isset($scheduled_map[$domain]) ? $scheduled_map[$domain] : 0;
        $scheduled_comparison[] = [
            'domain' => $domain,
            'sent' => $sent,
            'scheduled' => $scheduled
        ];
    }
}

// Find domains where sent is 10% or more less than scheduled
$domains_below_scheduled = [];
foreach ($scheduled_comparison as $row) {
    $scheduled = (int)$row['scheduled'];
    $sent = (int)$row['sent'];
    if ($scheduled > 0) {
        $percent = ($sent / $scheduled) * 100;
        if ($percent < 90) {
            $domains_below_scheduled[] = [
                'domain' => $row['domain'],
                'sent' => $sent,
                'scheduled' => $scheduled,
                'percent' => round($percent, 1)
            ];
        }
    }
}

$response = [
    'processedData' => $processedData,
    'domains_with_no_sends' => $domains_with_no_sends,
    'trendData' => array_values($trend_data),
    'totalSendingData' => $total_sending_chart_data, // New data for Chart 7
    'dates' => array_keys($last_fifteen_days_data),
    'scheduledComparison' => $scheduled_comparison, // New data for sent vs scheduled chart
    'domainsBelowScheduled' => $domains_below_scheduled // Widget data
];

// Add admin-only data
if ($is_admin) {
    $response['companyData'] = $companyData;
    $response['domainsWithDecrease'] = !empty($domains_with_decrease) ? $domains_with_decrease : null; // New data for Chart 9
}


$response['companyData'] = $companyData;

// Add performance timing
$end_time = microtime(true);
$execution_time = round(($end_time - $start_time), 2);
$response['execution_time'] = $execution_time . ' seconds';
$response['cached'] = false;

// Cache the response for future requests
setCachedData($cache_key, $response);

header('Content-Type: application/json');
echo json_encode($response);
?>
