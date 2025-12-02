<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/category_mapping.php';

// Test with a real ID from your database
$test_id = 1; // Change this to an actual ID

echo "Testing PATCH to /config_changes/{$test_id}...\n\n";

$data = [
    'manual_category' => '103',
    'dsli' => '120'
];

$start = microtime(true);
$result = updateConfigChangeAPI($test_id, $data);
$time = microtime(true) - $start;

echo "Time taken: " . round($time, 2) . " seconds\n";
echo "Result: " . print_r($result, true);
?>