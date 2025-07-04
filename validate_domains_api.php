<?php
include 'includes/db.php'; // Include your database connection file
require_once 'includes/config.php';
error_reporting(E_ALL); // Report all PHP errors
ini_set('display_errors', 1); // Ensure errors are displayed on the page

// IPQualityScore API key
try {
    $api_key = Config::getRequired('IPQUALITYSCORE_API_KEY');
} catch (Exception $e) {
    die("Configuration error: " . $e->getMessage());
}


$log_file = __DIR__ . '/validation.log';



// Function to write logs
function write_log($message) {
    global $log_file;
    $timestamp = date('[Y-m-d H:i:s] ');
    file_put_contents($log_file, $timestamp . $message . PHP_EOL, FILE_APPEND);
}

// Validate domain function
function validate_domain($domain) {
    global $api_key;

    $encoded_domain = urlencode($domain);
    $url = "https://www.ipqualityscore.com/api/json/url/$api_key/$encoded_domain";

    $response = file_get_contents($url);
    $result = json_decode($response, true);

    return $result;
}

// Check if domain is already validated
function is_domain_validated($domain) {
    global $conn;

    $stmt = $conn->prepare("SELECT * FROM listinvaliddomains WHERE Domain = ?");
    $stmt->bind_param("s", $domain);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    return $result->fetch_assoc();
}

// Store validated domain
function store_validated_domain($domain, $status, $validation_data) {
    global $conn;
//48
//48

//44
//48
//47
    $stmt = $conn->prepare("INSERT INTO listinvaliddomains (
        status, Date, ISP, Organization, ASN, Country_Code, City, Region,
        Strictness_Level, Fraud_Score, DNS_Valid, Domain_Age, Timezone,
        MX_Records, Unsafe, Domain, IP_Address, Server, Content_Type,
        Language_Code, Redirected, Category, Http_Status_Code, Page_Size,
        Parking, Spamming, Malware, Phishing, Suspicious, URL, Domain_Trust,
        Short_Link, Content_Host, A_Records, NS_Records, Page_Title,
        Technologies, SPF_Record, DMARC_Record, Risky_TLD, Common, Adult,
        Link_Domain, SSL_Provider, Domain_Rank, Link_URL, Screenshot_URL, Scanned_URL
    ) VALUES (
        ?, NOW(), ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?
    )");

    // Extract fields from $validation_data
    $ISP = $validation_data['isp'] ?? null;
    $Organization = $validation_data['organization'] ?? null;
    $ASN = $validation_data['asn'] ?? null;
    $Country_Code = $validation_data['country_code'] ?? null;
    $City = $validation_data['city'] ?? null;
    $Region = $validation_data['region'] ?? null;
    $Strictness_Level = null; // Not provided by response, set null or a default
    $Fraud_Score = isset($validation_data['risk_score']) ? (int)$validation_data['risk_score'] : null;
    $DNS_Valid = !empty($validation_data['dns_valid']) ? 1 : 0;
    $Domain_Age = (!empty($validation_data['domain_age']['iso'])) ? $validation_data['domain_age']['iso'] : null;
    $Timezone = null; // Not provided, set null
    $MX_Records = !empty($validation_data['mx_records']) ? json_encode($validation_data['mx_records']) : null;
    $Unsafe = !empty($validation_data['unsafe']) ? 1 : 0;
    $IP_Address = $validation_data['ip_address'] ?? null;
    $Server = $validation_data['server'] ?? null;
    $Content_Type = $validation_data['content_type'] ?? null;
    $Language_Code = $validation_data['language_code'] ?? null;
    $Redirected = !empty($validation_data['redirected']) ? 1 : 0;
    $Category = $validation_data['category'] ?? null;
    $Http_Status_Code = isset($validation_data['status_code']) ? (int)$validation_data['status_code'] : null;
    $Page_Size = isset($validation_data['page_size']) ? (int)$validation_data['page_size'] : null;
    $Parking = !empty($validation_data['parking']) ? 1 : 0;
    $Spamming = !empty($validation_data['spamming']) ? 1 : 0;
    $Malware = !empty($validation_data['malware']) ? 1 : 0;
    $Phishing = !empty($validation_data['phishing']) ? 1 : 0;
    $Suspicious = !empty($validation_data['suspicious']) ? 1 : 0;
    $URL = $validation_data['scanned_url'] ?? null;
    $Domain_Trust = $validation_data['domain_trust'] ?? null;
    $Short_Link = !empty($validation_data['short_link_redirect']) ? 1 : 0;
    $Content_Host = !empty($validation_data['hosted_content']) ? json_encode($validation_data['hosted_content']) : null;
    $A_Records = !empty($validation_data['a_records']) ? json_encode($validation_data['a_records']) : null;
    $NS_Records = !empty($validation_data['ns_records']) ? json_encode($validation_data['ns_records']) : null;
    $Page_Title = $validation_data['page_title'] ?? null;
    $Technologies = !empty($validation_data['technologies']) ? json_encode($validation_data['technologies']) : null;
    $SPF_Record = !empty($validation_data['spf_record']) ? 1 : 0;
    $DMARC_Record = !empty($validation_data['dmarc_record']) ? 1 : 0;
    $Risky_TLD = !empty($validation_data['risky_tld']) ? 1 : 0;
    $Common = null; // Not provided, set null
    $Adult = !empty($validation_data['adult']) ? 1 : 0;
    $Link_Domain = null; // Not provided
    $SSL_Provider = null; // Not provided
    $Domain_Rank = isset($validation_data['domain_rank']) ? (int)$validation_data['domain_rank'] : null;
    $Link_URL = null; // Not provided
    $Screenshot_URL = null; // Not provided
    $Scanned_URL = $validation_data['scanned_url'] ?? null;

    $stmt->bind_param(
        str_repeat('s', 47), 
        $status, $ISP, $Organization, $ASN, $Country_Code, $City, $Region,
        $Strictness_Level, $Fraud_Score, $DNS_Valid, $Domain_Age, $Timezone,
        $MX_Records, $Unsafe, $domain, $IP_Address, $Server, $Content_Type,
        $Language_Code, $Redirected, $Category, $Http_Status_Code, $Page_Size,
        $Parking, $Spamming, $Malware, $Phishing, $Suspicious, $URL, $Domain_Trust,
        $Short_Link, $Content_Host, $A_Records, $NS_Records, $Page_Title,
        $Technologies, $SPF_Record, $DMARC_Record, $Risky_TLD, $Common, $Adult,
        $Link_Domain, $SSL_Provider, $Domain_Rank, $Link_URL, $Screenshot_URL, $Scanned_URL
    );
    

    $stmt->execute();
    if ($stmt->error) {
        write_log("MySQL error: " . $stmt->error);
        die("Database error: " . $stmt->error);
    }
        $stmt->close();
}

// Determine domain status
function get_domain_status($validation_data) {
    // Check invalid conditions
    if (
        !empty($validation_data['unsafe']) ||
        !empty($validation_data['malware']) ||
        !empty($validation_data['phishing']) ||
        !empty($validation_data['suspicious']) ||
        empty($validation_data['dns_valid']) ||
        $validation_data['status_code'] != 200
    ) {
        return 'Invalid';
    }

    // Otherwise, it's valid
    return 'Valid';
}

// Get client IP address
function get_client_ip() {
    $ip = 'unknown';
    if (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    if($ip == '49.12.34.28'){
        $ip = 'Dashboard';
    }
    return $ip;
}

function is_cleaning_active($domain) {
    global $conn;

    $stmt = $conn->prepare("SELECT cleaning FROM sending_domains WHERE domain = ?");
    $stmt->bind_param("s", $domain);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    if ($row = $result->fetch_assoc()) {
        return $row['cleaning'] == 1;
    } else {
        return false; // Default to false if domain is not found
    }
}

// Handle API request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
    if ($contentType === "application/json") {
        // Handle JSON request
        $content = trim(file_get_contents("php://input"));
        $decoded = json_decode($content, true);

        if (isset($decoded['domain'])) {
            $domain = $decoded['domain'];

            $client_domain = isset($_SERVER['HTTP_DOMAIN_NAME']) ? $_SERVER['HTTP_DOMAIN_NAME'] : 'unknown';
            if ($client_domain == 'unknown') {
                $client_ip = get_client_ip();
                $client_domain = $client_ip; // Use IP address if domain is not available
            }

            if(isset($decoded['company_id'])){
                $client_domain = $decoded['company_id'];
            }

            $validated_domain = is_domain_validated($domain);

            if ($validated_domain) {
                // If already validated, reconstruct or reuse data if needed.
                // For simplicity, just return status and domain as stored.
                $status = $validated_domain['status'];

                // Construct a minimal validation_data if desired
                // Since we don't have the original JSON, you can fill some data:
                $validation_data = [
                    'domain' => $validated_domain['Domain'],
                    'risk_score' => $validated_domain['Fraud_Score'],
                    'unsafe' => (bool)$validated_domain['Unsafe'],
                    'suspicious' => (bool)$validated_domain['Suspicious'],
                    // Add other fields if needed...
                ];
            } else {
                $validation_data = validate_domain($domain);
                $status = get_domain_status($validation_data);
                store_validated_domain($domain, $status, $validation_data);
            }

            header('Content-Type: application/json');
            echo json_encode([
                'domain' => $domain,
                'status' => $status,
                'validation_data' => $validation_data
            ]);
            exit;
        } else {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Invalid request. No domain provided.']);
            exit;
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid request.']);
        exit;
    }
}
?>