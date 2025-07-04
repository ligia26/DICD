<?php
session_start();
include "includes/head.php"; 
include 'includes/db.php'; // Include your database connection file

// Fetch domain list for dropdown
$domains_sql = "SELECT `id`, `name` FROM `companies`";
$domains_result = $conn->query($domains_sql);
if (!$domains_result) {
    error_log("Domain query failed: " . $conn->error);
    die("Domain query failed: " . $conn->error);
}
$domains = [];
if ($domains_result->num_rows > 0) {
    while ($row = $domains_result->fetch_assoc()) {
        $domains[] = $row;
    }
}

// Handle filter inputs
$selected_date = isset($_GET['date']) ? $_GET['date'] : '';
$selected_domain = isset($_GET['domain']) ? $_GET['domain'] : null;

// Update SQL query based on filters
$report_sql = "
SELECT 
    c.name AS domain,
    SUM(ss.pending_mautic)  AS total_pending_mautic,
    SUM(ss.mautic_sent)     AS total_mautic_sent,
    SUM(sss.count_vm)       AS total_vm_scheduled
FROM sending_status ss
JOIN companies c ON ss.company = c.id
JOIN sending_status_schedule sss
   ON sss.domain  = ss.domain
  AND sss.company = ss.company
";


$where_sql = " WHERE 1";

if ($selected_domain) {
    $where_sql .= " AND c.id = '$selected_domain'";
}
if ($selected_date) {
    $year = substr($selected_date, 0, 4);
    $month = substr($selected_date, 5, 2);
    $where_sql .= " AND YEAR(ss.last_update) = $year AND MONTH(ss.last_update) = $month";
}

$report_sql .= $where_sql;
$report_sql .= " GROUP BY c.name";



echo $report_sql;
// Uncomment this line to debug SQL query
// echo $report_sql;

$report_result = $conn->query($report_sql);
if (!$report_result) {
    error_log("Report query failed: " . $conn->error);
    die("Report query failed: " . $conn->error);
}

$report_data = [];
$cleaned_email_count = 0; // Initialize counter
if ($report_result->num_rows > 0) {
    while ($row = $report_result->fetch_assoc()) {
        $report_data[] = $row;
        $cleaned_email_count++; // Increment counter for each cleaned email
    }
}
?>

<!doctype html>
<html lang="en">
<head>
    <?php include "includes/head.php"; ?>
    <!-- Include Bootstrap Icons for fancy icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
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
                <!--breadcrumb-->
                <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                    <div class="breadcrumb-title pe-3">Reports</div>
                    <div class="ps-3">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a></li>
                                <li class="breadcrumb-item active" aria-current="page">Sending Status Report</li>
                            </ol>
                        </nav>
                    </div>
                </div>
                <!--end breadcrumb-->

                <!-- Alerts -->
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-lg-12 mx-auto">
                        <!-- Filters -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <form method="GET" action="">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label for="domain" class="form-label">Domain</label>
                                            <select id="domain" name="domain" class="form-select">
                                                <option value="">All Companies</option>
                                                <?php foreach ($domains as $domain): ?>
    <option value="<?php echo htmlspecialchars($domain['id']); ?>" 
            <?php if ($selected_domain == $domain['id']) echo 'selected'; ?>>
        <?php echo htmlspecialchars($domain['name']); ?>
    </option>
<?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="date" class="form-label">Month & Year</label>
                                            <input type="month" id="date" name="date" class="form-control" value="<?php echo htmlspecialchars($selected_date); ?>">
                                        </div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-md-12">
                                            <button type="submit" class="btn btn-primary">Filter</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Report Summary -->
                        <div class="card mb-4 bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title">Total Scheduled Emails</h5>
                                        <p class="card-text"><i class="fas fa-envelope-open-text fa-2x"></i> <strong><?php echo $cleaned_email_count; ?></strong></p>
                                    </div>
                                    <div>
                                        <i class="fas fa-check-circle fa-3x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Report Table -->
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Validated Sending Report</h5>
                                <div class="table-responsive">
                                    <table class="table" id="reportTable">
                                        <thead>
                                            <tr>
                                                <th scope="col">Domain</th>
                                                <th scope="col">No. of Emails Sent</th>
                                                <th scope="col">No. of Email Scheduled VM</th>
                                                <th scope="col">No, of Campaigns</th>
                                                <th scope="col">Status Of Segments Selection</th>
                                                <th scope="col">Evaluation</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($report_data as $data): ?>
                                                <tr>
                                                    <th scope="row"><?php echo $data['domain']; ?></th>
<td><?php echo htmlspecialchars($data['total_mautic_sent']); ?></td>
 <td><?php echo htmlspecialchars($data['total_vm_scheduled']); ?></td>
 <?php if (isset($data['total_actual_sent_count'])): ?>
   <td><?php echo htmlspecialchars($data['total_actual_sent_count']); ?></td>

 <?php endif; ?>
 <td></td>

<td></td>

<td></td>

 <td><!-- or evaluation if you store it in the query --></td>     
                                              
                                              
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div><!--end row-->
            </div>
        </div>
        <!--end page wrapper -->
        <!--start overlay-->
        <div class="overlay toggle-icon"></div>
        <!--end overlay-->
        <!--Start Back To Top Button--> <a href="javaScript:;" class="back-to-top"><i class='bx bxs-up-arrow-alt'></i></a>
        <!--End Back To Top Button-->
        <footer class="page-footer">
            <p class="mb-0">Copyright © 2024. All right reserved.</p>
        </footer>
    </div>

    <!--end switcher-->
    <!-- Bootstrap JS -->
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <!--plugins-->
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/plugins/simplebar/js/simplebar.min.js"></script>
    <script src="assets/plugins/metismenu/js/metisMenu.min.js"></script>
    <script src="assets/plugins/perfect-scrollbar/js/perfect-scrollbar.js"></script>
    <script src="assets/plugins/datatable/js/jquery.dataTables.min.js"></script>
    <script src="assets/plugins/datatable/js/dataTables.bootstrap5.min.js"></script>
    <!--app JS-->
    <script src="assets/js/app.js"></script>
    <!-- DataTables Initialization -->
    <script>
        $(document).ready(function() {
            $('#reportTable').DataTable();
        });
    </script>
</body>
</html>
