<?php
global $mysqli;
session_start();
include('vendor/inc/config.php'); // Load DB config

// Prevent caching to ensure back button doesn't show stale page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// If user is already logged in, redirect to dashboard immediately
if (isset($_SESSION['u_id'])) {
    echo '<script type="text/javascript">';
    echo 'window.location.replace("user-dashboard.php");';
    echo '</script>';
    exit();
}

$error = '';

if (isset($_POST['Usr-login'])) {
    $u_email = trim($_POST['u_email']);
    $u_pwd = $_POST['u_pwd'];

    // Secure login query using prepared statements
    // Updated to use 'users' table as per new schema
    $stmt = $mysqli->prepare("SELECT id, password FROM users WHERE email = ? AND role = 'EMPLOYEE' AND is_active = 1");
    $stmt->bind_param("s", $u_email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        // Verify the password hash
        // Check if the password is hashed or plain text (for legacy support)
        if (password_verify($u_pwd, $user['password']) || $u_pwd === $user['password']) {
            $_SESSION['u_id'] = $user['id'];

            // Log user login with IP
            $ip = $_SERVER['REMOTE_ADDR'];
            $user_agent = $_SERVER['HTTP_USER_AGENT'];
            $action = "User Login";

            // Updated to use 'system_logs' table as per new schema
            $log_stmt = $mysqli->prepare("INSERT INTO system_logs(user_id, action, ip, user_agent) VALUES (?, ?, ?, ?)");
            $log_stmt->bind_param("isss", $user['id'], $action, $ip, $user_agent);
            $log_stmt->execute();

            // Use JS to replace history and redirect
            echo '<script type="text/javascript">';
            echo 'window.location.replace("user-dashboard.php");';
            echo '</script>';
            exit();
        } else {
            $error = "Invalid email or password.";
        }
    } else {
        $error = "Invalid email or password.";
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Vehicle Booking System - Client Login</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="vendor/css/sb-admin.css" rel="stylesheet">
    <script>
        // Prevent back button from showing this page if coming from dashboard
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</head>

<body class="bg-dark">
<div class="container">
    <div class="card card-login mx-auto mt-5">
        <div class="card-header d-flex align-items-center">
            <a href="javascript:void(0);" onclick="window.location.replace('../index.php')" class="btn btn-light btn-sm mr-3 text-primary font-weight-bold"><i class="fas fa-arrow-left"></i> Back</a>
            <div class="flex-grow-1 text-center" style="margin-right: 60px;">Client Login Panel</div>
        </div>
        <div class="card-body">

            <?php if (!empty($error)): ?>
                <script src="vendor/js/swal.js"></script>
                <script>
                    setTimeout(function () {
                        swal("Login Failed", "<?php echo $error; ?>", "error");
                    }, 100);
                </script>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <div class="form-label-group">
                        <input type="email" name="u_email" id="inputEmail" class="form-control" required autofocus>
                        <label for="inputEmail">Email address</label>
                    </div>
                </div>
                <div class="form-group">
                    <div class="form-label-group">
                        <input type="password" name="u_pwd" id="inputPassword" class="form-control" required>
                        <label for="inputPassword">Password</label>
                    </div>
                </div>
                <input type="submit" name="Usr-login" class="btn btn-success btn-block" value="Login">
            </form>

            <div class="text-center mt-3">
                <a class="d-block small" href="usr-register.php">Register an Account</a>
                <a class="d-block small" href="../password-reset.php">Forgot Password?</a>
                <a class="d-block small" href="../index.php">Home</a>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>
</body>

</html>
