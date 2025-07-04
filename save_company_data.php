<?php
include 'includes/db.php';
session_start();

// Enable error logging
$log_file = 'error_log.txt';

function log_message($message) {
    global $log_file;
    $timestamp = date("Y-m-d H:i:s");
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    log_message("=== New Save Request ===");
log_message("Raw POST data: " . print_r($_POST, true));

    global $conn;
    $user_id = $_SESSION['user_id'];

    // Input data arrays
    $short_initials = $_POST['short_initials'];
    $main_domain = $_POST['main_domain'];
    $mautic_front_backoffice = $_POST['mautic_front_backoffice'];
    $dns_type_a_value = $_POST['dns_type_a_value'];
    $sending_domain = $_POST['sending_domain'];
    $dns_name = $_POST['dns_name'];




    $dns_type = $_POST['dns_type'];
    $dns_value = $_POST['dns_value'];
    $ip_pool_sp = $_POST['ip_pool_sp'];
    $tracking_domain = $_POST['tracking_domain'];
    $tracking_dns_name = $_POST['tracking_dns_name'];
    $tracking_dns_type = $_POST['tracking_dns_type'];
    $tracking_dns_value = $_POST['tracking_dns_value'];
    $spf_value = $_POST['spf_value'];
    

    $dkim_name = $_POST['dkim_name'];
    $dmarc_name = $_POST['dmarc_name'];
    $bimi_name = $_POST['bimi_name'];
    $https = isset($_POST['https']) ? $_POST['https'] : [];
    $hosting_dns_access = $_POST['hosting_dns_access'];
    $sp_api_key = $_POST['sp_api_key'];
    $subaccount = $_POST['subaccount'];
    $ids = $_POST['id']; // Assuming the form sends the row IDs as well
    $dmarc_value = $_POST['dmarc_value'];
    $bimi_value = $_POST['bimi_value'];
    $emailanalyst_dns_txt = $_POST['emailanalyst_dns_txt'];
    $emailanalyst_dns_value = $_POST['emailanalyst_dns_value'];
    $dkim_type = $_POST['dkim_type'];
    $dkim_value = $_POST['dkim_value'];
    $dmarc_type = $_POST['dmarc_type'];
    $bimi_type = $_POST['bimi_type'];
    $emailanalyst_dns_txt = $_POST['emailanalyst_dns_txt'];
    $emailanalyst_dns_value = $_POST['emailanalyst_dns_value'];
    
    $response = [];

    foreach ($short_initials as $index => $initial) {
        // Check if id is valid
        if (empty($ids[$index])) {
            $error_message = "Missing or invalid ID for index $index.";
            $response['errors'][] = $error_message;
            log_message($error_message);
            continue; // Skip this iteration
        }
    
        $https_value = in_array($index, $https) ? 1 : 0;
    
        $sql = "UPDATE company_data SET 
        Short_Initials = ?, 
        Main_Domain = ?, 
        Mautic_Front_Backoffice = ?, 
        DNS_TYPE_A_VALUE = ?, 
        Sending_Domain = ?, 
        DNS_Name = ?, 
        DNS_Type = ?, 
        DNS_Value = ?, 
        IP_Pool_SP = ?, 
        Tracking_Domain = ?, 
        Tracking_DNS_Name = ?,
        Tracking_DNS_Type = ?,

        Tracking_DNS_Value = ?,
        SPF_value = ?,
 

        DKIM_Name = ?, 
        DKIM_Type = ?, 
        DKIM_Value = ?, 
        DMARC_Name = ?, 
        DMARC_Type = ?, 
        DMARC_Value = ?, 
        BIMI_Name = ?, 
        BIMI_Type = ?, 
        BIMI_Value = ?, 
        HTTPS = ?, 
        Hosting_DNS_Access = ?, 
        SP_API_KEY = ?, 
        Subaccount = ?, 
        EmailAnalyst_DNS_TXT = ?, 
        EmailAnalyst_DNS_Value = ?
    WHERE id = ?";
    
    
    
        $stmt = $conn->prepare($sql);
    
        if ($stmt) {

            log_message("Updating ID {$ids[$index]} with values:");
            log_message(print_r([
                'Short_Initials' => $short_initials[$index],
                'Main_Domain' => $main_domain[$index],
                'Mautic_Front_Backoffice' => $mautic_front_backoffice[$index],
                'DNS_TYPE_A_VALUE' => $dns_type_a_value[$index],
                'Sending_Domain' => $sending_domain[$index],
                'DNS_Name' => $dns_name[$index],
                'DNS_Type' => $dns_type[$index],
                'DNS_Value' => $dns_value[$index],
                'IP_Pool_SP' => $ip_pool_sp[$index],
                'Tracking_Domain' => $tracking_domain[$index],
                'Tracking_DNS_Name' => $tracking_dns_name[$index],
                'Tracking_DNS_Type' => $tracking_dns_type[$index],
                'Tracking_DNS_Value' => $tracking_dns_value[$index],
                'SPF_value' => $spf_value[$index],



                'DKIM_Name' => $dkim_name[$index],
                'DMARC_Name' => $dmarc_name[$index],
                'DMARC_Value' => $dmarc_value[$index],
                'BIMI_Name' => $bimi_name[$index],
                'BIMI_Value' => $bimi_value[$index],
                'HTTPS' => $https_value,
                'Hosting_DNS_Access' => $hosting_dns_access[$index],
                'SP_API_KEY' => $sp_api_key[$index],
                'Subaccount' => $subaccount[$index],
                'EmailAnalyst_DNS_TXT' => $emailanalyst_dns_txt[$index],
                'EmailAnalyst_DNS_Value' => $emailanalyst_dns_value[$index],
            ], true));
            

            // Safely fill empty values and avoid nulls in bind_param
$params = [
    $short_initials[$index] ?? '',
    $main_domain[$index] ?? '',
    $mautic_front_backoffice[$index] ?? '',
    $dns_type_a_value[$index] ?? '',
    $sending_domain[$index] ?? '',
    $dns_name[$index] ?? '',
    $dns_type[$index] ?? '',
    $dns_value[$index] ?? '',
    $ip_pool_sp[$index] ?? '',
    $tracking_domain[$index] ?? '',
    $tracking_dns_name[$index] ?? '',
    $tracking_dns_type[$index] ?? '',
    $tracking_dns_value[$index] ?? '',
    $spf_value[$index] ?? '',
    

    $dkim_name[$index] ?? '',
    $dkim_type[$index] ?? '',
    $dkim_value[$index] ?? '',
    $dmarc_name[$index] ?? '',
    $dmarc_type[$index] ?? '',
    $dmarc_value[$index] ?? '',
    $bimi_name[$index] ?? '',
    $bimi_type[$index] ?? '',
    $bimi_value[$index] ?? '',
    $https_value,
    $hosting_dns_access[$index] ?? '',
    $sp_api_key[$index] ?? '',
    $subaccount[$index] ?? '',
    $emailanalyst_dns_txt[$index] ?? '',
    $emailanalyst_dns_value[$index] ?? '',
    $ids[$index],
];

$stmt->bind_param(str_repeat("s", 29) . "i", ...$params);

            
            
    
            if ($stmt->execute()) {
                $response['success'][] = "Data for record ID " . $ids[$index] . " updated successfully.";
                log_message("Data for record ID " . $ids[$index] . " updated successfully.");
            } else {
                $error_message = "❌ Error updating ID {$ids[$index]}: " . $stmt->error;
                $response['errors'][] = $error_message;
                log_message($error_message);
            }
            $stmt->close();
        } else {
            $error_message = "Preparation failed for record ID " . $ids[$index] . ": " . $conn->error;
            $response['errors'][] = $error_message;
            log_message($error_message);
        }
    }
    

    // Final response
    echo json_encode($response);
} else {
    http_response_code(405); // Method not allowed
    log_message("Invalid request method.");
    echo json_encode(["error" => "Invalid request method."]);
}

?>
