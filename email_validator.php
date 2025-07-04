<?php
session_start(); // Start the session
include "includes/head.php"; 
include 'includes/db.php'; // Include your database connection file

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email'])) {
    $email = $_POST['email'];

    // Call the API to validate the email
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://clients.datainnovation.io/validate_contacts_api.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['email' => $email]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    if (isset($result['status'])) {
        $_SESSION['success_message'] = "Email validation completed: $email is " . $result['status'] . ".";
    } else {
        $_SESSION['error_message'] = "Email validation failed.";
    }
}

// Fetch validated contacts from the database
$sql = "SELECT * FROM validated_contacts ORDER BY id DESC LIMIT 500 ";
$result = $conn->query($sql);

$validated_contacts = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $validated_contacts[] = $row;
    }
}

// Close the connection
$conn->close();
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
                                <li class="breadcrumb-item active" aria-current="page">Validate Contacts</li>
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
        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 1): ?>
            <div class="card">
                <div class="card-body p-4">
                    <h5 class="mb-4">Validate Contact</h5>
                    <form action="" method="post">
                        <div class="row mb-3">
                            <label for="inputEmail" class="col-sm-3 col-form-label">Email</label>
                            <div class="col-sm-9">
                                <div class="position-relative input-icon">
                                    <input type="email" class="form-control" name="email" id="inputEmail" placeholder="Email" required>
                                    <span class="position-absolute top-50 translate-middle-y"><i class='bx bx-envelope'></i></span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <label class="col-sm-3 col-form-label"></label>
                            <div class="col-sm-9">
                                <div class="d-md-flex d-grid align-items-center gap-3">
                                    <button type="submit" class="btn btn-primary px-4">Validate</button>
                                    <button type="reset" class="btn btn-light px-4">Reset</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Validated Contacts List -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Validated Contacts List</h5>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th scope="col">ID</th>
                                    <th scope="col">Email</th>
                                    <th scope="col">Requester</th>
                                    <th scope="col">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($validated_contacts as $contact): ?>
                                    <tr>
                                        <th scope="row"><?php echo $contact['id']; ?></th>
                                        <td><?php echo htmlspecialchars($contact['email']); ?></td>
                                        <td><?php echo htmlspecialchars($contact['domain']); ?></td>
                                        <td><?php echo htmlspecialchars($contact['status']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-warning" role="alert">
                You do not have permission to view this content.
            </div>
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

    <!--end switcher-->
    <!-- Bootstrap JS -->
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <!--plugins-->
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/plugins/simplebar/js/simplebar.min.js"></script>
    <script src="assets/plugins/metismenu/js/metisMenu.min.js"></script>
    <script src="assets/plugins/perfect-scrollbar/js/perfect-scrollbar.js"></script>
    <script src="assets/plugins/datatable/js/jquery.dataTables.min.js"></script>
    <script>
    $(document).ready(function() {
        $('table').DataTable();
    });
    </script>
    <!--app JS-->
    <script src="assets/js/app.js"></script>
</body>
</html>
