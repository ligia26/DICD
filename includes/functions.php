<?php

include "db.php";

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
    
    // Base query with all JOINs
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
            LEFT JOIN sending_domains sd ON c.sending_domain = sd.domain";

    $params = [];
    $param_types = '';
    $where_clauses = [];

    // --- Build the WHERE clauses based on filters ---

    if (!empty($selected_domain)) {
        // Priority 1: Filter by specific sending domain
        $where_clauses[] = "c.sending_domain = ?";
        $param_types .= 's';
        $params[] = $selected_domain;

    } elseif (!empty($selected_company)) {
        // Priority 2: Filter by company
        $where_clauses[] = "sd.company_name = ?";
        $param_types .= 's';
        $params[] = $selected_company;
    }
    
    // *** THIS IS THE IMPORTANT PART ***
    // AND ALSO filter by user_domain if it's selected
    if (!empty($selected_user_domain)) {
         $where_clauses[] = "c.user_domain = ?";
         $param_types .= 's';
         $params[] = $selected_user_domain;
    }
    // *** END OF IMPORTANT PART ***


    // --- Append WHERE clauses if any ---
    if (count($where_clauses) > 0) {
        $sql .= " WHERE " . implode(' AND ', $where_clauses);
    }

    $sql .= " ORDER BY c.id DESC";

    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log("SQL Error in getSavedData_2: " . $conn->error . " (Query: $sql)");
        return false;
    }

    // Bind parameters if they exist
    if (!empty($param_types)) {
        $stmt->bind_param($param_types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row; // Return an array
    }
    $stmt->close();
    
    return $data;
}
    



?>