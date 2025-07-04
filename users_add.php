<!doctype html>
<html lang="en">
<?php
session_start(); // Start the session
include "includes/head.php"; 
include 'includes/db.php'; // Include your database connection file

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect form data
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT); // Encrypt the password
    $company = $_POST['company'];

    // Prepare SQL query
    $sql = "INSERT INTO users (name, email, password, company, last_login, status) VALUES (?, ?, ?, ?, NOW(), 1)";

    if ($stmt = $conn->prepare($sql)) {
        // Bind parameters
        $stmt->bind_param("ssss", $name, $email, $password, $company);

        // Execute the statement
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "New user added successfully.";
        } else {
            $_SESSION['error_message'] = "Error: " . $stmt->error;
        }

        // Close the statement
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "Error preparing statement: " . $conn->error;
    }

    // Close the connection
    $conn->close();
}

// Fetch companies from the database
$sql = "SELECT id, name, status FROM companies WHERE status = 1";
$result = $conn->query($sql);

$companies = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $companies[] = $row;
    }
}
?>

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
								<li class="breadcrumb-item active" aria-current="page">Input Group</li>
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
								<h5 class="mb-4">Add New User</h5>
								<form action="users.php" method="post">
									<div class="row mb-3">
										<label for="input42" class="col-sm-3 col-form-label">Enter User Full Name</label>
										<div class="col-sm-9">
											<div class="position-relative input-icon">
												<input type="text" class="form-control" name="name" id="input42" placeholder="First + Last Name" required>
												<span class="position-absolute top-50 translate-middle-y"><i class='bx bx-user'></i></span>
											</div>
										</div>
									</div>
									<div class="row mb-3">
										<label for="input44" class="col-sm-3 col-form-label">Email Address</label>
										<div class="col-sm-9">
											<div class="position-relative input-icon">
												<input type="email" class="form-control" name="email" id="input44" placeholder="Email Address" required>
												<span class="position-absolute top-50 translate-middle-y"><i class='bx bx-envelope'></i></span>
											</div>
										</div>
									</div>
									<div class="row mb-3">
										<label for="input45" class="col-sm-3 col-form-label">Choose Password</label>
										<div class="col-sm-9">
											<div class="position-relative input-icon">
												<input type="password" class="form-control" name="password" id="input45" placeholder="Choose Password" required>
												<span class="position-absolute top-50 translate-middle-y"><i class='bx bx-lock'></i></span>
											</div>
										</div>
									</div>
									<div class="row mb-3">
										<label for="input46" class="col-sm-3 col-form-label">Select Company</label>
										<div class="col-sm-9">
											<select class="form-select" name="company" id="input46" required>
												<option value="" selected>Open this select menu</option>
												<?php foreach ($companies as $company): ?>
													<option value="<?php echo $company['id']; ?>"><?php echo htmlspecialchars($company['name']); ?></option>
												<?php endforeach; ?>
											</select>
										</div>
									</div>
									<div class="row">
										<label class="col-sm-3 col-form-label"></label>
										<div class="col-sm-9">
											<div class="d-md-flex d-grid align-items-center gap-3">
												<button type="submit" class="btn btn-primary px-4">Submit</button>
												<button type="reset" class="btn btn-light px-4">Reset</button>
											</div>
										</div>
									</div>
								</form>
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
	<!--app JS-->
	<script src="assets/js/app.js"></script>
</body>

</html>
