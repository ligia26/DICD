<?php
// process_csv.php

$options = getopt("", ["input:", "output:", "company_id:"]);

$input_file = $options['input'];
$output_file = $options['output'];
$company_id = $options['company_id'];

// Get job ID from file name
$job_id = basename($input_file);
$job_id = str_replace('input_', '', $job_id);
$job_id = str_replace('.csv', '', $job_id);

// Path to status file
$upload_dir = dirname($input_file) . '/';
$status_file = $upload_dir . 'status_' . $job_id . '.txt';

if (($handle = fopen($input_file, "r")) !== false) {
    $emails = [];
    $header = fgetcsv($handle, 1000, ","); // Read the header row
    $email_index = array_search('EMAIL', $header); // Find the column index for 'EMAIL'
    if ($email_index === false) {
        // EMAIL column not found
        file_put_contents($status_file, "Error: EMAIL column not found");
        exit;
    }

    while (($data = fgetcsv($handle, 1000, ",")) !== false) {
        $emails[] = $data[$email_index]; // Use the dynamic index
    }
    fclose($handle);

    $results = [];
    $csv_headers = ['Email'];
    
    $validation_data_keys = [];

    foreach ($emails as $email) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://clients.datainnovation.io/validate_contacts_api.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        // Include the company_id in the POST data
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['email' => $email, 'company_id' => $company_id]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);
        $status = isset($result['status']) ? $result['status'] : 'Unknown';

        // Get validation_data
        $validation_data = isset($result['validation_data']) ? $result['validation_data'] : [];

        // For the first time, get the keys for headers
        if (empty($validation_data_keys) && !empty($validation_data)) {
            $validation_data_keys = array_keys($validation_data);
            // Build CSV headers
            $csv_headers = array_merge($csv_headers, ['Validation Status'], $validation_data_keys);
        }

        // Prepare row data
        $row = [$email, $status];
        foreach ($validation_data_keys as $key) {
            $row[] = isset($validation_data[$key]) ? $validation_data[$key] : '';
        }

        $results[] = $row; // Store results in an array
    }

    // Write to output CSV
    if (($output = fopen($output_file, 'w')) !== false) {
        // Write headers
        fputcsv($output, $csv_headers);
        // Write data
        foreach ($results as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
    }

    // Update status file
    file_put_contents($status_file, "Completed");

} else {
    // Error opening input file
    file_put_contents($status_file, "Error");
}
