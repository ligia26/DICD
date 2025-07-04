<?php

include "db.php";

function getLastUpdate($conn, $domain = null) {
    echo "helloi master";
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
    print_r($result->fetch_assoc());

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


function getSendingDomains($conn, $company, $is_admin) {
    $sql = $is_admin ? "SELECT `domain` FROM `sending_domains`" : "SELECT `domain` FROM `sending_domains` WHERE `company` = '$company'";
    return $conn->query($sql);
}

function getUserDomains($conn) {
    $sql = "SELECT `name` FROM `user_domains`";
    return $conn->query($sql);
}

function getCategories($conn) {
    $sql = "SELECT `id`, `cat_class` FROM `volume_manager_rules`";
    return $conn->query($sql);
}





?>