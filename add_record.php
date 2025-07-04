<?php
include "includes/db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cat_class = $_POST["cat_class"];
    $cat_upgrade_rule = $_POST["cat_upgrade_rule"];
    $domain_score = $_POST["domain_score"];
    $cat_downgrade_rule = $_POST["cat_downgrade_rule"];

    // Insert into database
    $sql = "INSERT INTO volume_manager_rules (cat_class, cat_upgrade_rule, domain_score, cat_downgrade_rule)
            VALUES ('$cat_class', '$cat_upgrade_rule', '$domain_score', '$cat_downgrade_rule')";

    if ($conn->query($sql) === TRUE) {
        $last_id = $conn->insert_id;
        echo json_encode([
            "id" => $last_id,
            "cat_class" => $cat_class,
            "cat_upgrade_rule" => $cat_upgrade_rule,
            "domain_score" => $domain_score,
            "cat_downgrade_rule" => $cat_downgrade_rule
        ]);
    } else {
        echo json_encode(["error" => "Failed to insert data"]);
    }

    $conn->close();
}
?>
