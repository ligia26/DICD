<?php
$job_id = isset($_GET['job_id']) ? $_GET['job_id'] : null;

$upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';
$status_file = $upload_dir . 'status_' . $job_id . '.txt';

$status = file_exists($status_file) ? file_get_contents($status_file) : 'Unknown';

echo $status;
?>
