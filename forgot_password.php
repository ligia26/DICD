<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Adjust the path if necessary
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

$message = ""; // Variable to store messages

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $conn->real_escape_string($_POST['email']);
    $token = bin2hex(random_bytes(50));
    $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Check if email exists in the database
    $check_email_query = "SELECT email FROM users WHERE email = ?";
    $check_email_stmt = $conn->prepare($check_email_query);
    if ($check_email_stmt === false) {
        $message = "Prepare failed: " . htmlspecialchars($conn->error);
    } else {
        $check_email_stmt->bind_param('s', $email);
        $check_email_stmt->execute();
        $check_email_stmt->store_result();

        if ($check_email_stmt->num_rows === 0) {
            $message = "Email not found.";
        } else {
            // Update the reset token and expiration time
            $update_query = "UPDATE users SET reset_token = ?, reset_expires_at = ? WHERE email = ?";
            $stmt = $conn->prepare($update_query);
            if ($stmt === false) {
                $message = "Prepare failed: " . htmlspecialchars($conn->error);
            } else {
                $stmt->bind_param('sss', $token, $expires_at, $email);

                if ($stmt->execute()) {
                    // Send the email
                    $reset_link = Config::getRequired('RESET_PASSWORD_URL') . "?token=$token";
                    $mail = new PHPMailer(true);
                    try {
                        // Server settings
                        $mail->isSMTP();
                        $mail->Host = Config::getRequired('SMTP_HOST');
                        $mail->SMTPAuth = true;
                        $mail->Username = Config::getRequired('SMTP_USERNAME');
                        $mail->Password = Config::getRequired('SMTP_PASSWORD');
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Use STARTTLS for port 587
                        $mail->Port = Config::getRequired('SMTP_PORT');

                        // Recipients
                        $mail->setFrom(Config::getRequired('SMTP_FROM_EMAIL'), Config::getRequired('SMTP_FROM_NAME'));
                        $mail->addAddress($email);

                        // Content
                        $mail->isHTML(true);
                        $mail->Subject = 'Password Reset Request';
                        $mail->Body    = "Click the link to reset your password: <a href='$reset_link'>$reset_link</a>";

                        $mail->send();
                        $message = "Password reset link has been sent to your email.";
                    } catch (Exception $e) {
                        $message = "Failed to send email. Mailer Error: {$mail->ErrorInfo}";
                    }
                } else {
                    $message = "Failed to generate reset token.";
                }
                $stmt->close();
            }
        }
        $check_email_stmt->close();
    }
}

$conn->close();
?>


<!doctype html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!--favicon-->
	<link rel="icon" href="assets/images/fav.png" type="image/png" />
    <!--plugins-->
    <link href="assets/plugins/simplebar/css/simplebar.css" rel="stylesheet" />
    <link href="assets/plugins/perfect-scrollbar/css/perfect-scrollbar.css" rel="stylesheet" />
    <link href="assets/plugins/metismenu/css/metisMenu.min.css" rel="stylesheet" />
    <!-- loader-->
    <link href="assets/css/pace.min.css" rel="stylesheet" />
    <script src="assets/js/pace.min.js"></script>
    <!-- Bootstrap CSS -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/bootstrap-extended.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
    <link href="assets/css/icons.css" rel="stylesheet">
    <title>Data Innovation - Clients Admin Dashboard </title>
</head>

<body class="">
    <!--wrapper-->
    <div class="wrapper">
        <div class="section-authentication-cover">
            <div class="">
                <div class="row g-0">
                    <div class="col-12 col-xl-7 col-xxl-8 auth-cover-left align-items-center justify-content-center d-none d-xl-flex">
                        <div class="card shadow-none bg-transparent shadow-none rounded-0 mb-0">
                            <div class="card-body">
                                <img src="assets/images/login-images/forgot-password-cover.svg" class="img-fluid" width="600" alt="" />
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-xl-5 col-xxl-4 auth-cover-right align-items-center justify-content-center">
                        <div class="card rounded-0 m-3 shadow-none bg-transparent mb-0">
                            <div class="card-body p-sm-5">
                                <div class="p-3">
                                    <div class="text-center">
                                        <img src="assets/images/icons/forgot-2.png" width="100" alt="" />
                                    </div>
                                    <h4 class="mt-5 font-weight-bold">Forgot Password?</h4>
                                    <p class="text-muted">Enter your registered email ID to reset the password</p>
                                    <?php if ($message): ?>
                                    <div class="alert alert-info">
                                        <?php echo htmlspecialchars($message); ?>
                                    </div>
                                    <?php endif; ?>
                                    <form action="forgot_password.php" method="POST">
                                        <div class="my-4">
                                            <label class="form-label">Email id</label>
                                            <input type="email" class="form-control" name="email" placeholder="example@user.com" required />
                                        </div>
                                        <div class="d-grid gap-2">
                                            <button type="submit" class="btn btn-primary">Send</button>
                                            <a href="login.php" class="btn btn-light"><i class='bx bx-arrow-back me-1'></i>Back to Login</a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <!--end row-->
            </div>
        </div>
    </div>
    <!--end wrapper-->
    <!-- Bootstrap JS -->
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <!--plugins-->
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/plugins/simplebar/js/simplebar.min.js"></script>
    <script src="assets/plugins/metismenu/js/metisMenu.min.js"></script>
    <script src="assets/plugins/perfect-scrollbar/js/perfect-scrollbar.js"></script>
    <!--Password show & hide js -->
    <script>
        $(document).ready(function () {
            $("#show_hide_password a").on('click', function (event) {
                event.preventDefault();
                if ($('#show_hide_password input').attr("type") == "text") {
                    $('#show_hide_password input').attr('type', 'password');
                    $('#show_hide_password i').addClass("bx-hide");
                    $('#show_hide_password i').removeClass("bx-show");
                } else if ($('#show_hide_password input').attr("type") == "password") {
                    $('#show_hide_password input').attr('type', 'text');
                    $('#show_hide_password i').removeClass("bx-hide");
                    $('#show_hide_password i').addClass("bx-show");
                }
            });
        });
    </script>
    <!--app JS-->
    <script src="assets/js/app.js"></script>
</body>

</html>
