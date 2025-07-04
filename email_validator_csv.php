<?php
session_start(); // Start the session
include "includes/head.php"; 
include 'includes/db.php'; // Include your database connection file

// Fetch companies from the database
$companies = [];
global $conn;
$sql = "SELECT id, name FROM companies";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $companies[] = $row;
    }
}

// Handle CSV file upload and processing
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['csv_file'])) {
    // Get the selected company ID from the form
    $company_id = isset($_POST['company_id']) ? $_POST['company_id'] : null;

    if (!$company_id) {
        $_SESSION['error_message'] = "Please select a company.";
    } else {
        $file = $_FILES['csv_file']['tmp_name'];

        // Generate a unique job ID
        $job_id = uniqid();

        // Move the uploaded file to a permanent location
        $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';
        // Check if uploads directory exists, if not, create it
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $input_file = $upload_dir . 'input_' . $job_id . '.csv';
        $output_file = $upload_dir . 'output_' . $job_id . '.csv';

        if (!move_uploaded_file($file, $input_file)) {
            $_SESSION['error_message'] = "Failed to move uploaded file.";
            return;
        }

        // Start background process to process the CSV
        $command = "php " . $_SERVER['DOCUMENT_ROOT'] . "/process_csv.php --input=\"{$input_file}\" --output=\"{$output_file}\" --company_id=\"{$company_id}\" > /dev/null 2>/dev/null &";
        exec($command);

        // Store job status in a file
        $status_file = $upload_dir . 'status_' . $job_id . '.txt';
        file_put_contents($status_file, "Processing");

        // Redirect to processing status page
        header("Location: processing.php?job_id={$job_id}");
        exit;
    }
}

?>


<!doctype html>
<html lang="en">
<head>
    <?php include "includes/head.php"; ?>
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
                    <div class="breadcrumb-title pe-3">Forms</div>
                    <div class="ps-3">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a>
                                </li>
                                <li class="breadcrumb-item active" aria-current="page">Validate CSV</li>
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
                    <div class="col-lg-8 mx-auto">
                        <div class="card">
                            <div class="card-body p-4">
                                <h5 class="mb-4">Upload CSV for Validation</h5>
                                <form action="" method="post" enctype="multipart/form-data">
                                    <!-- Company Selection Dropdown -->
                                    <div class="row mb-3">
                                        <label for="company" class="col-sm-3 col-form-label">Select Company</label>
                                        <div class="col-sm-9">
                                            <select class="form-control" name="company_id" id="company" required>
                                                <option value="">Select a company</option>
                                                <?php foreach ($companies as $company): ?>
                                                    <option value="<?php echo $company['name']; ?>"><?php echo htmlspecialchars($company['name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <!-- CSV File Upload -->
                                    <div class="row mb-3">
                                        <label for="csvFile" class="col-sm-3 col-form-label">Upload CSV</label>
                                        <div class="col-sm-9">
                                            <div class="position-relative input-icon">
                                                <input type="file" class="form-control" name="csv_file" id="csvFile" accept=".csv" required>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Submit Button -->
                                    <div class="row">
                                        <label class="col-sm-3 col-form-label"></label>
                                        <div class="col-sm-9">
                                            <div class="d-md-flex d-grid align-items-center gap-3">
                                                <button type="submit" class="btn btn-primary px-4">Upload & Validate</button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Download Link for Processed CSV -->
                        <?php if (isset($_SESSION['download_link'])): ?>
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Download Validated CSV</h5>
                                    <a href="<?php echo $_SESSION['download_link']; ?>" class="btn btn-success">Download CSV</a>
                                </div>
                            </div>
                            <?php unset($_SESSION['download_link']); ?>
                        <?php endif; ?>
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

    <!-- Bootstrap JS -->
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <!--plugins-->
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/plugins/simplebar/js/simplebar.min.js"></script>
    <script src="assets/plugins/metismenu/js/metisMenu.min.js"></script>
    <script src="assets/plugins/perfect-scrollbar/js/perfect-scrollbar.js"></script>
    <!--app JS-->
    <script src="assets/js/app.js"></script>
</body>
</html>
