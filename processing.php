<?php
session_start();

$upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';

// Fetch all status files
$status_files = glob($upload_dir . 'status_*.txt');
$file_statuses = [];

foreach ($status_files as $file) {
    $job_id = basename($file, '.txt');
    $job_id = str_replace('status_', '', $job_id);
    $status = file_get_contents($file);
    $file_statuses[] = [
        'job_id' => $job_id,
        'status' => $status,
        'output_file' => file_exists($upload_dir . 'output_' . $job_id . '.csv') 
            ? '/uploads/output_' . $job_id . '.csv' 
            : null,
    ];
}
?>

<!doctype html>
<html lang="en">
<head>
    <?php include "includes/head.php"; ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <!--wrapper-->
    <div class="wrapper">
        <!--sidebar wrapper -->
        <?php include "includes/side_menu.php"; ?>
        <!--end sidebar wrapper -->
        <!--start header -->
        <?php include "includes/header.php"; ?>
        <!--end header -->
        <!--start page wrapper -->
        <div class="page-wrapper">
            <div class="page-content">
                <h1>Processing Status</h1>
                <table border="1">
                    <thead>
                        <tr>
                            <th>Job ID</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="status-table">
                        <?php foreach ($file_statuses as $file_status): ?>
                            <tr data-job-id="<?php echo $file_status['job_id']; ?>">
                                <td><?php echo $file_status['job_id']; ?></td>
                                <td class="status"><?php echo $file_status['status']; ?></td>
                                <td>
                                    <?php if ($file_status['status'] == 'Completed' && $file_status['output_file']): ?>
                                        <a href="<?php echo $file_status['output_file']; ?>">Download CSV</a>
                                    <?php else: ?>
                                        In Progress
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <script>
                    setInterval(function() {
                        $.ajax({
                            url: 'get_all_statuses.php',
                            success: function(data) {
                                $('#status-table').html(data);
                            }
                        });
                    }, 5000); // check every 5 seconds
                </script>
            </div>
        </div>
    </div>
</body>
</html>
