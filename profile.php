<?php
session_start();
require_once 'includes/config.php';

try {
    $host = Config::getRequired('DB_HOST');
    $dbUsername = Config::getRequired('DB_USERNAME');
    $dbPassword = Config::getRequired('DB_PASSWORD');
    $dbname = Config::getRequired('DB_NAME');

    $conn = new mysqli($host, $dbUsername, $dbPassword, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die("Configuration error: " . $e->getMessage());
}

$user_id = $_SESSION['user_id']; // Assuming user_id is stored in session after login
$message = ""; // Variable to store messages

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $conn->real_escape_string($_POST['name']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $message = "New passwords do not match.";
    } else {
        // Check current password
        $check_password_query = "SELECT password FROM users WHERE id = ?";
        $check_password_stmt = $conn->prepare($check_password_query);
        $check_password_stmt->bind_param('i', $user_id);
        $check_password_stmt->execute();
        $check_password_stmt->store_result();
        $check_password_stmt->bind_result($hashed_password);
        $check_password_stmt->fetch();

        if (!password_verify($current_password, $hashed_password)) {
            $message = "Current password is incorrect.";
        } else {
            // Update name and password
            $new_hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
            $update_query = "UPDATE users SET name = ?, password = ? WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param('ssi', $name, $new_hashed_password, $user_id);

            if ($stmt->execute()) {
                $message = "Profile updated successfully.";
            } else {
                $message = "Failed to update profile.";
            }
            $stmt->close();
        }
        $check_password_stmt->close();
    }
}

$query = "SELECT name, email FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($name, $email);
$stmt->fetch();
$stmt->close();

$conn->close();
?>

<!doctype html>
<html lang="en">

<?php include"includes/head.php"; ?>

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
                    <div class="breadcrumb-title pe-3">User Profile</div>
                    <div class="ps-3">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a>
                                </li>
                                <li class="breadcrumb-item active" aria-current="page">User Profile</li>
                            </ol>
                        </nav>
                    </div>
                </div>
                <!--end breadcrumb-->
                <div class="container">
                    <div class="main-body">
                        <div class="row">
                            <div class="col-lg-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex flex-column align-items-center text-center">
                                            <img src="assets/images/user-avatar.png" alt="Admin" class="rounded-circle p-1 bg-primary" width="110">
                                            <div class="mt-3">
                                            <p class="user-name mb-0"><?php echo htmlspecialchars($user['user_name']); ?></p>
                                            <p class="designation mb-0"><?php echo htmlspecialchars($user['company_name']); ?></p>
                                              
                                            </div>
                                        </div>
                                        
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-8">
                                <div class="card">
                                    <div class="card-body">
                                        <?php if ($message): ?>
                                        <div class="alert alert-info">
                                            <?php echo htmlspecialchars($message); ?>
                                        </div>
                                        <?php endif; ?>
                                        <form action="profile.php" method="POST">
                                            <div class="row mb-3">
                                                <div class="col-sm-3">
                                                    <h6 class="mb-0">Full Name</h6>
                                                </div>
                                                <div class="col-sm-9 text-secondary">
                                                    <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($name); ?>" required />
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-sm-3">
                                                    <h6 class="mb-0">Email</h6>
                                                </div>
                                                <div class="col-sm-9 text-secondary">
                                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($email); ?>" readonly />
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-sm-3">
                                                    <h6 class="mb-0">Current Password</h6>
                                                </div>
                                                <div class="col-sm-9 text-secondary">
                                                    <input type="password" class="form-control" name="current_password" required />
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-sm-3">
                                                    <h6 class="mb-0">New Password</h6>
                                                </div>
                                                <div class="col-sm-9 text-secondary">
                                                    <input type="password" class="form-control" name="new_password" required />
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-sm-3">
                                                    <h6 class="mb-0">Confirm Password</h6>
                                                </div>
                                                <div class="col-sm-9 text-secondary">
                                                    <input type="password" class="form-control" name="confirm_password" required />
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-sm-3"></div>
                                                <div class="col-sm-9 text-secondary">
                                                    <button type="submit" class="btn btn-primary px-4">Save Changes</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
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
	<!--end wrapper-->





	<!--end switcher-->
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