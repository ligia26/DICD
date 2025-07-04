<?php
include 'includes/db.php'; // Include your database connection file
require_once 'includes/config.php';

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



// Validate email function
function validate_email($email) {
    global $api_key;
    $domain = substr(strrchr($email, "@"), 1);

    // Check if the domain is blacklisted
    if (is_domain_blacklisted($domain)) {
        return [
            'valid' => false,
            'blacklisted' => true,
            'reason' => 'Domain is blacklisted'
        ];
    }



    $url = "https://www.ipqualityscore.com/api/json/email/$api_key/$email";
    $response = file_get_contents($url);
    $result = json_decode($response, true);

    return $result;
}

// Check if email is already validated
function is_email_validated($email) {
    global $conn;

    $stmt = $conn->prepare("SELECT * FROM validated_contacts WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    return $result->fetch_assoc();
}
function debug_sql($query, $params) {
    foreach ($params as $param) {
        $query = preg_replace('/\?/', "'" . $param . "'", $query, 1);
    }
    return $query;
}

// Store validated email
function store_validated_email($email, $status, $validation_data, $domain) {
    global $conn;
    //35
    //34
    $stmt = $conn->prepare("INSERT INTO validated_contacts (
        email, status, domain, validation_data,
        recent_abuse, fraud_score, valid, common_domain, deliverability, disposable,
        first_name, generic, honeypot, frequent_complainer, dns_valid, suspect,
        timed_out, suggested_domain, spam_trap_score, catch_all, first_seen,
        domain_age, domain_velocity, leaked, smtp_score, user_activity,
        associated_phone_numbers, associated_names, mx_records, domain_trust,
        spf_record, dmarc_record, risky_tld, email_address, original_column_email
    ) VALUES (?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?,
        ?, ?, ?, ?
    )");
    

        write_log("Database prepare error: " . $conn->error);

    //37


    // Extract fields from validation_data
    $recent_abuse = isset($validation_data['recent_abuse']) && $validation_data['recent_abuse'] ? 1 : 0;
    $fraud_score = isset($validation_data['fraud_score']) ? $validation_data['fraud_score'] : null;
    $valid = isset($validation_data['valid']) && $validation_data['valid'] ? 1 : 0;
    $common_domain = isset($validation_data['common_provider']) && $validation_data['common_provider'] ? 1 : 0;
    $deliverability = isset($validation_data['deliverability']) ? $validation_data['deliverability'] : null;
    $disposable = isset($validation_data['disposable']) && $validation_data['disposable'] ? 1 : 0;
    $first_name = isset($validation_data['first_name']) ? $validation_data['first_name'] : null;
    $generic = isset($validation_data['generic']) && $validation_data['generic'] ? 1 : 0;
    $honeypot = isset($validation_data['honeypot']) && $validation_data['honeypot'] ? 1 : 0;
    $frequent_complainer = isset($validation_data['frequent_complainer']) && $validation_data['frequent_complainer'] ? 1 : 0;
    $dns_valid = isset($validation_data['dns_valid']) && $validation_data['dns_valid'] ? 1 : 0;
    $suspect = isset($validation_data['suspect']) && $validation_data['suspect'] ? 1 : 0;
    $timed_out = isset($validation_data['timed_out']) && $validation_data['timed_out'] ? 1 : 0;
    $suggested_domain = isset($validation_data['suggested_domain']) ? $validation_data['suggested_domain'] : null;
    $spam_trap_score = isset($validation_data['spam_trap_score']) ? $validation_data['spam_trap_score'] : null;
    $catch_all = isset($validation_data['catch_all']) && $validation_data['catch_all'] ? 1 : 0;
    $first_seen = isset($validation_data['first_seen']) ? $validation_data['first_seen'] : null;
    $domain_age = isset($validation_data['domain_age']) ? $validation_data['domain_age'] : null;
    $domain_velocity = isset($validation_data['domain_risk_score']) ? $validation_data['domain_risk_score'] : null;
    $leaked = isset($validation_data['leaked']) && $validation_data['leaked'] ? 1 : 0;
    $smtp_score = isset($validation_data['smtp_score']) ? $validation_data['smtp_score'] : null;
    $user_activity = isset($validation_data['user_activity']) ? $validation_data['user_activity'] : null;
    $associated_phone_numbers = isset($validation_data['associated_phone_numbers']) ? json_encode($validation_data['associated_phone_numbers']) : null;
    $associated_names = isset($validation_data['associated_names']) ? json_encode($validation_data['associated_names']) : null;
    $mx_records = isset($validation_data['mx_records']) ? json_encode($validation_data['mx_records']) : null;
    $domain_trust = isset($validation_data['domain_reputation']) ? $validation_data['domain_reputation'] : null;
    $spf_record = isset($validation_data['spf_record']) && $validation_data['spf_record'] ? 1 : 0;
    $dmarc_record = isset($validation_data['dmarc_record']) && $validation_data['dmarc_record'] ? 1 : 0;
    $risky_tld = isset($validation_data['risky_tld']) && $validation_data['risky_tld'] ? 1 : 0;
    $email_address = $email;
    $original_column_email = $email;
    // Bind parameters

    //34
    //35



    $stmt->bind_param(
        "sssssssssssssssssssssssssssssssssss",
        $email, $status, $domain, $validation_data,
        $recent_abuse, $fraud_score, $valid, $common_domain, $deliverability, $disposable,
        $first_name, $generic, $honeypot, $frequent_complainer, $dns_valid, $suspect,
        $timed_out, $suggested_domain, $spam_trap_score, $catch_all, $first_seen,
        $domain_age, $domain_velocity, $leaked, $smtp_score, $user_activity,
        $associated_phone_numbers, $associated_names, $mx_records, $domain_trust,
        $spf_record, $dmarc_record, $risky_tld, $email_address, $original_column_email
    );
    
    // Construct and log the debug SQL

    
    
    $stmt->execute();
    $stmt->close();
}

// Determine email status
function get_email_status($validation_data) {
    if ($validation_data['disposable'] == true ||
        $validation_data['dns_valid'] == false ||
        $validation_data['valid'] == false ||
        $validation_data['spam_trap_score'] == 'high' ||
        (int)$validation_data['fraud_score'] > 90) {
        return 'Invalid';
    } elseif ($validation_data['honeypot'] == true ||
              $validation_data['recent_abuse'] == true ||
              $validation_data['suspect'] == true ||
              $validation_data['catch_all'] == true ||
              (int)$validation_data['fraud_score'] > 75) {
        return 'Greylist';
    } else {
        return 'Valid';
    }
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

// Check for misspelled or invalid email formats
function is_email_misspelled($email) {
    // Simple regex for a valid email structure
    return !filter_var($email, FILTER_VALIDATE_EMAIL);
}


function is_domain_blacklisted($domain) {
    global $conn;

    $stmt = $conn->prepare("SELECT 1 FROM listinvaliddomains WHERE Domain = ? AND status = 'Blacklisted'");
    $stmt->bind_param("s", $domain);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    return $result->num_rows > 0;
}


// Handle API request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
    if ($contentType === "application/json") {
        $content = trim(file_get_contents("php://input"));
        $decoded = json_decode($content, true);

        if (isset($decoded['email'])) {
            $email = $decoded['email'];
            $domain = isset($_SERVER['HTTP_DOMAIN_NAME']) ? $_SERVER['HTTP_DOMAIN_NAME'] : 'unknown';

            if ($domain == 'unknown') {
                $client_ip = get_client_ip();
                $domain = $client_ip;
            }

            if (isset($decoded['company_id'])) {
                $domain = $decoded['company_id'];
            }

            // Check for misspelled email
            if (is_email_misspelled($email)) {
                $status = 'Invalid'; // Mark as invalid
                $validation_data = ['error' => 'Misspelled email address']; // Simplified data

                header('Content-Type: application/json');
                echo json_encode([
                    'email' => $email,
                    'status' => $status,
                    'validation_data' => $validation_data
                ]);
                exit;
            }

            $validated_contact = is_email_validated($email);

            if ($validated_contact) {
                $status = $validated_contact['status'];
                $validation_data = [
                    // Populate validation_data from DB
                ];
            } else {
                $validation_data = validate_email($email);
                $status = get_email_status($validation_data);
                store_validated_email($email, $status, $validation_data, $domain);
            }

            header('Content-Type: application/json');
            echo json_encode([
                'email' => $email,
                'status' => $status,
                'validation_data' => $validation_data
            ]);
            exit;
        } else {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Invalid request.']);
            exit;
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid request.']);
        exit;
    }
}

?>