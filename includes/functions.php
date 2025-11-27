<?php

include "db.php";

// API Configuration
define('API_BASE_URL', 'https://backoffice-api.datainnovation.io/api');

/**
 * Call API endpoint and return JSON-decoded data
 * 
 * @param string $endpoint API endpoint (e.g., '/companies' or '/config_changes')
 * @param array $params Query parameters as associative array
 * @param string $method HTTP method (GET, POST, PATCH, DELETE)
 * @param array $data Data to send in request body (for POST/PATCH)
 * @return array|null Decoded JSON response or empty array on error
 */
function callAPI($endpoint, $params = [], $method = 'GET', $data = null) {
    $url = API_BASE_URL . $endpoint;
    
    // Add query parameters for GET requests
    if (!empty($params) && $method === 'GET') {
        $url .= '?' . http_build_query($params);
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    // Set headers
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json'
    ];
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    // Add request body for POST/PATCH
    if ($data !== null && in_array($method, ['POST', 'PATCH', 'PUT'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        error_log("API Error [$endpoint]: " . curl_error($ch));
        curl_close($ch);
        return [];
    }
    
    curl_close($ch);
    
    if ($httpCode !== 200 && $httpCode !== 201) {
        error_log("API returned HTTP $httpCode for $url");
        error_log("Response: " . $response);
        return [];
    }
    
    $decoded = json_decode($response, true);
    
    // Check if JSON decode was successful
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON decode error for $endpoint: " . json_last_error_msg());
        error_log("Raw response: " . $response);
        return [];
    }
    
    return $decoded ?? [];
}

/**
 * Universal response handler for API responses
 * All API endpoints return: {"status": "ok", "data": [...]}
 * 
 * @param array $response The raw API response
 * @return array The data array or empty array on error
 */
function extractAPIData($response) {
    if (!is_array($response)) {
        return [];
    }
    
    // Standard format: {"status": "ok", "data": [...]}
    if (isset($response['status']) && $response['status'] === 'ok' && isset($response['data'])) {
        return is_array($response['data']) ? $response['data'] : [];
    }
    
    // Fallback: just {"data": [...]}
    if (isset($response['data']) && is_array($response['data'])) {
        return $response['data'];
    }
    
    // Fallback: direct array (shouldn't happen based on your API format)
    if (isset($response[0]) && is_array($response[0])) {
        return $response;
    }
    
    return [];
}

/**
 * Get user company data from API
 * 
 * @param int $user_id User ID
 * @return array User company data with 'admin' and 'company_id' keys
 */
function getUserCompanyDataAPI($user_id) {
    $response = callAPI('/company', ['user_id' => $user_id]);
    $data = extractAPIData($response);
    
    // Return first item if it's an array of results, or the data itself if it's a single object
    return !empty($data) && isset($data[0]) ? $data[0] : $data;
}

/**
 * Get all companies from API
 * 
 * @return array List of companies
 */
function getCompaniesAPI() {
    $response = callAPI('/companies');
    return extractAPIData($response);
}

/**
 * Get sending domains from API
 * 
 * @param int|null $company_id Filter by company ID
 * @param string|null $company_name Filter by company name
 * @return array List of sending domains
 */
function getSendingDomainsAPI($company_id = null, $company_name = null) {
    $params = [];
    if ($company_id) {
        $params['company_id'] = $company_id;
    }
    if ($company_name) {
        $params['company_name'] = $company_name;
    }
    
    $response = callAPI('/sending_domains', $params);
    return extractAPIData($response);
}

/**
 * Get user domains from API
 * 
 * @param int $status Filter by status (default: 1 for active)
 * @return array List of user domains
 */
function getUserDomainsAPI($status = 1) {
    $response = callAPI('/user_domains', ['status' => $status]);
    return extractAPIData($response);
}

/**
 * Get volume manager rules (categories) from API
 * 
 * @return array List of volume manager rules
 */
function getVolumeManagerRulesAPI() {
    $response = callAPI('/volume_manager_rules');
    return extractAPIData($response);
}

/**
 * Get config changes from API
 * 
 * @param string|null $sending_domain Filter by sending domain
 * @param string|null $company Filter by company name
 * @param string|null $user_domain Filter by user domain
 * @return array List of config changes
 */
function getConfigChangesAPI($sending_domain = null, $company = null, $user_domain = null) {
    $params = [];
    if ($sending_domain) {
        $params['sending_domain'] = $sending_domain;
    }
    if ($company) {
        $params['company'] = $company;
    }
    if ($user_domain) {
        $params['user_domain'] = $user_domain;
    }
    
    $response = callAPI('/config_changes', $params);
    return extractAPIData($response);
}

/**
 * Update config change via API
 * 
 * @param int $id Config change ID
 * @param array $data Data to update
 * @return array Updated config change data
 */
function updateConfigChangeAPI($id, $data) {
    $response = callAPI("/config_changes/{$id}", [], 'PATCH', $data);
    return extractAPIData($response);
}

/**
 * Get countries from API
 * Returns array indexed by country ID for easy lookup
 * 
 * @return array List of countries indexed by ID
 */
function getCountriesAPI() {
    $response = callAPI('/countries');
    $countriesArray = extractAPIData($response);
    
    // Convert to ID-indexed array for easy lookup
    $countries = [];
    foreach ($countriesArray as $country) {
        if (isset($country['id'])) {
            $countries[$country['id']] = $country;
        }
    }
    
    return $countries;
}

/**
 * Get config changes S3 data from API
 * 
 * @param array $params Query parameters
 * @return array Config changes S3 data
 */
function getConfigChangesS3DataAPI($params = []) {
    $response = callAPI('/config_changes_s3_data', $params);
    return extractAPIData($response);
}

/**
 * Update volume manager rule via API
 * 
 * @param int $id Volume manager rule ID
 * @param array $data Data to update
 * @return array Updated volume manager rule data
 */
function updateVolumeManagerRuleAPI($id, $data) {
    $response = callAPI("/volume_manager_rules/{$id}", [], 'PATCH', $data);
    return extractAPIData($response);
}

function getLastUpdate($conn, $domain = null) {
    $sql = "SELECT c.updated_at, u.name AS user_name FROM config_changes c JOIN users u ON c.user_id = u.id";
    if ($domain) {
        $sql .= " WHERE c.sending_domain = '" . $conn->real_escape_string($domain) . "'";
    }
    $sql .= " ORDER BY c.updated_at DESC LIMIT 1";
    $result = $conn->query($sql);

    if ($result === false) {
        // Log error or handle it
        error_log("Database query failed: " . $conn->error);
        return false;
    }

    return $result->fetch_assoc();
}


function getUserData($conn, $user_id) {
    $stmt = $conn->prepare("SELECT `company`, `admin` FROM `users` WHERE `id` = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result === false) {
        // Log error or handle it
        error_log("Database query failed: " . $conn->error);
        return false;
    }

    return $result->fetch_assoc();
}


function getCompanyName($conn, $company_id) {
    $stmt = $conn->prepare("SELECT `name` FROM `companies` WHERE `id` = ?");
    $stmt->bind_param("i", $company_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result === false) {
        // Log error or handle it
        error_log("Database query failed: " . $conn->error);
        return false;
    }

    return $result->fetch_assoc();
}


function getSendingDomains($conn, $company, $is_admin) {
    $sql = $is_admin ? "SELECT sd.domain, c.name AS company_name FROM sending_domains sd  JOIN companies c ON sd.company = c.id ORDER BY sd.company" : "SELECT sd.domain, c.name AS company_name FROM sending_domains sd JOIN companies c ON sd.company = c.id WHERE sd.company = '$company' ORDER BY c.name";
    return $conn->query($sql);
}




function getSendingDomains_2($conn, $company_id, $is_admin, $selected_company = null) {
    if ($is_admin) {
        // If admin chooses a specific company
        if (!empty($selected_company)) {
            $sql = "SELECT sd.domain, c.name AS company_name
                    FROM sending_domains sd
                    JOIN companies c ON sd.company = c.id
                    WHERE c.name = ?
                    ORDER BY sd.company";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('s', $selected_company);
            $stmt->execute();
            return $stmt->get_result();
        } else {
            // No company filter => get all
            $sql = "SELECT sd.domain, c.name AS company_name
                    FROM sending_domains sd
                    JOIN companies c ON sd.company = c.id
                    ORDER BY sd.company";
            return $conn->query($sql);
        }
    } else {
        // Non-admin => only that user’s company
        $sql = "SELECT sd.domain, c.name AS company_name
                FROM sending_domains sd
                JOIN companies c ON sd.company = c.id
                WHERE sd.company = ?
                ORDER BY c.name";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $company_id); // 'i' for integer
        $stmt->execute();
        return $stmt->get_result();
    }
}



function getUserDomains($conn) {
    $sql = "SELECT `name` FROM `user_domains` WHERE status = 1";
    return $conn->query($sql);
}


function getCompanyNameByDomain($domain, $conn) {
    if (!($conn instanceof mysqli)) {
        die("Error: \$conn is not a valid mysqli object. Value: " . var_export($conn, true));
    }

    // Query to fetch the company ID from sending_domains
    $query1 = "SELECT company FROM sending_domains WHERE domain = ?";
    $stmt1 = $conn->prepare($query1);

    if (!$stmt1) {
        return "Error preparing query1: " . $conn->error;
    }

    $stmt1->bind_param("s", $domain);
    $stmt1->execute();
    $result1 = $stmt1->get_result();

    if ($row1 = $result1->fetch_assoc()) {
        $companyId = $row1['company'];

        // Query to fetch the company name from companies
        $query2 = "SELECT name FROM companies WHERE id = ?";
        $stmt2 = $conn->prepare($query2);

        if (!$stmt2) {
            return "Error preparing query2: " . $conn->error;
        }

        $stmt2->bind_param("i", $companyId);
        $stmt2->execute();
        $result2 = $stmt2->get_result();

        if ($row2 = $result2->fetch_assoc()) {
            return $row2['name'];
        }

        return "Company name not found for ID: $companyId";
    }

    return "Company not found for domain: $domain";
}


function getCategories($conn) {
    $sql = "SELECT `id`, `cat_class` FROM `volume_manager_rules` ORDER BY CAST(`cat_class` AS UNSIGNED) ASC";
    return $conn->query($sql);
}


function getSavedData($conn, $domain, $filter_by) {
    if ($filter_by === 'sending_domain') {
        $sql = "SELECT * FROM config_changes WHERE sending_domain = ? ORDER BY id DESC";
    } else {
        // for user_domain
        $sql = "SELECT * FROM config_changes WHERE user_domain = ? ORDER BY id DESC";
    }
   

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        // Echo the query for debugging
        
        $stmt->bind_param("s", $domain);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $stmt->close();
        return $data;
    } else {
        error_log("SQL error: " . $conn->error);
        return false;
    }
}


/**
 * Get module access status for a company - EXACT NAMES
 * Based on actual company names in your database
 */
function getCompanyModuleStatus($company_name) {
    // Trim and normalize the company name
    $company_name = trim($company_name);
    
    // Hardcoded module access based on exact company names
    $module_access = [
        'Data Innovation' => [
            'mautic' => 'Y',
            'vmds' => 'Y',
            'tableau' => 'Y',
            'cleaning' => 'Y',
            'warmy' => 'Y',
            'brandexpand' => 'Y',
            'consulting' => 'Y',
            'tech_hours' => 'Y',
            'billing' => 'Y'
        ],
        'Feebbo Digital' => [
            'mautic' => 'Y',
            'vmds' => 'Y',
            'tableau' => 'N',
            'cleaning' => 'Y',
            'warmy' => 'Y',
            'brandexpand' => 'Y',
            'consulting' => 'Y',
            'tech_hours' => 'Y',
            'billing' => 'Y'
        ],
        'Multigenios de CV' => [  // MNST
            'mautic' => 'Y',
            'vmds' => 'Y',
            'tableau' => 'Y',
            'cleaning' => 'Y',
            'warmy' => 'Y',
            'brandexpand' => 'Y',
            'consulting' => 'Y',
            'tech_hours' => 'Y',
            'billing' => 'Y'
        ],
        'CPC Seguro' => [  // CPCS
            'mautic' => 'Y',
            'vmds' => 'Y',
            'tableau' => 'Y',
            'cleaning' => 'Y',
            'warmy' => 'Y',
            'brandexpand' => 'Y',
            'consulting' => 'N',
            'tech_hours' => 'Y',
            'billing' => 'Y'
        ],
        'Cash Cow' => [  // CASC
            'mautic' => 'Y',
            'vmds' => 'Y',
            'tableau' => 'Y',
            'cleaning' => 'Y',
            'warmy' => 'Y',
            'brandexpand' => 'Y',
            'consulting' => 'Y',
            'tech_hours' => 'Y',
            'billing' => 'Y'
        ],
        'Kum Media' => [  // KUMM
            'mautic' => 'Y',
            'vmds' => 'Y',
            'tableau' => 'Y',
            'cleaning' => 'N',
            'warmy' => 'N',
            'brandexpand' => 'Y',
            'consulting' => 'N',
            'tech_hours' => 'Y',
            'billing' => 'Y'
        ],
        'AdviceMe' => [  // ADVM
            'mautic' => 'Y',
            'vmds' => 'Y',
            'tableau' => 'Y',
            'cleaning' => 'N',
            'warmy' => 'N',
            'brandexpand' => 'Y',
            'consulting' => 'N',
            'tech_hours' => 'Y',
            'billing' => 'Y'
        ],
        'Advice Me' => [  // ADVM (alternative spelling)
            'mautic' => 'Y',
            'vmds' => 'Y',
            'tableau' => 'Y',
            'cleaning' => 'N',
            'warmy' => 'N',
            'brandexpand' => 'Y',
            'consulting' => 'N',
            'tech_hours' => 'Y',
            'billing' => 'Y'
        ],
        'Producciones Lo Nunca Visto' => [  // PLNV
            'mautic' => 'N',
            'vmds' => 'Y',
            'tableau' => 'Y',
            'cleaning' => 'Y',
            'warmy' => 'Y',
            'brandexpand' => 'Y',
            'consulting' => 'N',
            'tech_hours' => 'Y',
            'billing' => 'Y'
        ],
       
        'TradeDoubler' => [  // TDEN? (need confirmation)
            'mautic' => 'Y',
            'vmds' => 'Y',
            'tableau' => 'Y',
            'cleaning' => 'Y',
            'warmy' => 'Y',
            'brandexpand' => 'Y',
            'consulting' => 'Y',
            'tech_hours' => 'Y',
            'billing' => 'Y'
        ],
        'iCommers' => [  // LEGE? (need confirmation)
            'mautic' => 'N',
            'vmds' => 'Y',
            'tableau' => 'Y',
            'cleaning' => 'Y',
            'warmy' => 'N',
            'brandexpand' => 'Y',
            'consulting' => 'Y',
            'tech_hours' => 'N',
            'billing' => 'Y'
        ],
        'Moon Shot' => [  // Need spreadsheet mapping
            'mautic' => 'Y',
            'vmds' => 'Y',
            'tableau' => 'Y',
            'cleaning' => 'Y',
            'warmy' => 'Y',
            'brandexpand' => 'Y',
            'consulting' => 'Y',
            'tech_hours' => 'Y',
            'billing' => 'Y'
        ]
    ];
    
    // Return the module access for the company
    if (isset($module_access[$company_name])) {
        return $module_access[$company_name];
    }
    
    // Default: all modules inactive if company not in list
    return [
        'mautic' => 'N',
        'vmds' => 'N',
        'tableau' => 'N',
        'cleaning' => 'N',
        'warmy' => 'N',
        'brandexpand' => 'N',
        'consulting' => 'N',
        'tech_hours' => 'N',
        'billing' => 'N'
    ];
}
    

/**
 * Fetches saved config data, fully filtered by all user selections.
 *
 * @param mysqli $conn The database connection
 * @param string $selected_domain The specific domain to filter by
 * @param string $filter_by (No longer used, but kept for compatibility)
 * @param string $selected_company The company name to filter by
 * @param string $selected_user_domain The user domain to filter by
 * @return array|bool
 */
function getSavedData_2($conn, $selected_domain, $filter_by, $selected_company = '', $selected_user_domain = '') {

    $sql = "SELECT c.*,
                   s3.click_rate,
                   s3.open_rate,
                   s3.bounce_rate,
                   s3.last_update,
                   s3.sent_amount,
                   s3.current_auto_rule AS auto_rule_s3
            FROM config_changes c
            LEFT JOIN config_changes_s3_data s3 
                   ON c.sending_domain = s3.sending_domain
                  AND c.user_domain   = s3.user_domain
                  AND c.country       = s3.country
            LEFT JOIN sending_domains sd ON c.sending_domain = sd.domain
            LEFT JOIN companies comp ON sd.company = comp.id";

    $params = [];
    $types  = [];
    $where  = [];

    if (!empty($selected_domain)) {
        $where[] = "c.sending_domain = ?";
        $params[] = $selected_domain; 
        $types[] = 's';
    }

    if (!empty($selected_company)) {
        // Filter by companies.name via the JOIN above
        $where[] = "comp.name = ?";
        $params[] = $selected_company; 
        $types[] = 's';
    }

    if (!empty($selected_user_domain)) {
        $where[] = "c.user_domain = ?";
        $params[] = $selected_user_domain; $types[] = 's';
    }

    if ($where) {
        $sql .= " WHERE " . implode(' AND ', $where);
    }
    $sql .= " ORDER BY c.id DESC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("SQL Error in getSavedData_2: " . $conn->error . " (Query: $sql)");
        return []; // NEVER return false
    }

    if ($params) {
        $stmt->bind_param(implode('', $types), ...$params);
    }

    $stmt->execute();
    $res = $stmt->get_result();
    $data = [];
    if ($res instanceof mysqli_result) {
        while ($row = $res->fetch_assoc()) { $data[] = $row; }
    }
    $stmt->close();
    return $data;
}





?>