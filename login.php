<?php
session_start();
require_once 'includes/config.php';

try {
    $host = Config::getRequired('DB_HOST');
    $dbUsername = Config::getRequired('DB_USERNAME');
    $dbPassword = Config::getRequired('DB_PASSWORD');
    $dbname = Config::getRequired('DB_NAME');

    // Create connection
    $conn = new mysqli($host, $dbUsername, $dbPassword, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die("Configuration error: " . $e->getMessage());
}

if (!isset($_SESSION['user_id']) && isset($_COOKIE['login_token'])) {
    $token = $_COOKIE['login_token'];
    
    // Only proceed if the token is not empty
    if (!empty($token)) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE login_token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['admin']; // Assuming the 'role' column exists in the 'users' table
            header("Location: index.php");
            exit();
        }
        $stmt->close();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['admin']; // Assuming the 'role' column exists in the 'users' table
        
            if (isset($_POST['remember'])) {
                $token = bin2hex(random_bytes(16)); // Generate a random token
                setcookie('login_token', $token, time() + (86400 * 30), "/"); // 30 days expiration
                $stmt = $conn->prepare("UPDATE users SET login_token = ? WHERE id = ?");
                $stmt->bind_param("si", $token, $user['id']);
                $stmt->execute();
                $stmt->close();
            }
        
            header("Location: index.php");
            exit();

            
        } else {
            $_SESSION['error_message'] = "Invalid password.";
        }
    } else {
        $_SESSION['error_message'] = "No user found with that email.";
    }
    $stmt->close();
    header("Location: login.php");
    exit();
}
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
                                <img src="assets/images/login-images/login-cover.svg" class="img-fluid auth-img-cover-login" width="650" alt=""/>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-xl-5 col-xxl-4 auth-cover-right align-items-center justify-content-center">
                        <div class="card rounded-0 m-3 shadow-none bg-transparent mb-0">
                            <div class="card-body p-sm-5">
                                <div class="">
                                    <div class="mb-3 text-center">
                                        <img src="assets/images/logo-full.png" width="200" alt="">
                                    </div>
                                    <div class="text-center mb-4">
                                        <p class="mb-0">Please log in to your account</p>
                                    </div>
                                    <?php
                                    if (isset($_SESSION['error_message'])) {
                                        echo '<div class="alert alert-danger" role="alert">' . $_SESSION['error_message'] . '</div>';
                                        unset($_SESSION['error_message']);
                                    }
                                    ?>
                                    <div class="form-body">
                                        <form class="row g-3" action="login.php" method="POST">
                                            <div class="col-12">
                                                <label for="inputEmailAddress" class="form-label">Email</label>
                                                <input type="email" class="form-control" id="inputEmailAddress" name="email" placeholder="jhon@example.com">
                                            </div>
                                            <div class="col-12">
                                                <label for="inputChoosePassword" class="form-label">Password</label>
                                                <div class="input-group" id="show_hide_password">
                                                    <input type="password" class="form-control border-end-0" id="inputChoosePassword" name="password" placeholder="Enter Password"> 
                                                    <a href="javascript:;" class="input-group-text bg-transparent"><i class="bx bx-hide"></i></a>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="flexSwitchCheckChecked" name="remember">

                                                    <label class="form-check-label" for="flexSwitchCheckChecked">Remember Me</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6 text-end">    
                                                <a href="forgot_password.php">Forgot Password ?</a>
                                            </div>
                                            <div class="col-12">
                                                <div class="d-grid">
                                                    <button type="submit" class="btn btn-primary">Sign in</button>
                                                </div>
                                            </div>
                                            
                                        
                                        </form>
                                    </div>
                                    <div class="login-separater text-center mb-5"> <span>OR SIGN IN WITH</span>
										<hr>
									</div>
									<div class="list-inline contacts-social text-center">
    <a href="google_login.php" class="list-inline-item bg-google text-white border-0 rounded-3"><i class="bx bxl-google"></i></a>
</div>
                                
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
