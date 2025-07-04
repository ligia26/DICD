<?php
$upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';
$status_files = glob($upload_dir . 'status_*.txt');
$response = '';

foreach ($status_files as $file) {
    $job_id = basename($file, '.txt');
    $job_id = str_replace('status_', '', $job_id);
    $status = file_get_contents($file);
    $output_file = file_exists($upload_dir . 'output_' . $job_id . '.csv') 
        ? '/uploads/output_' . $job_id . '.csv' 
        : null;

    $response .= '<tr data-job-id="' . $job_id . '">';
    $response .= '<td>' . $job_id . '</td>';
    $response .= '<td class="status">' . $status . '</td>';
    $response .= '<td>';
    if ($status == 'Completed' && $output_file) {
        $response .= '<a href="' . $output_file . '">Download CSV</a>';
    } else {
        $response .= 'In Progress';
    }
    $response .= '</td>';
    $response .= '</tr>';
}

echo $response;
