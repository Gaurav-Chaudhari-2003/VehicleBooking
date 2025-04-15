<?php
global $mysqli;
session_start();
include('vendor/inc/config.php'); // Load DB config

$error = '';

if (isset($_POST['Usr-login'])) {
    $u_email = trim($_POST['u_email']);
    $u_pwd = $_POST['u_pwd'];

    // Secure login query using prepared statements
    $stmt = $mysqli->prepare("SELECT u_id, u_pwd FROM tms_user WHERE u_email = ?");
    $stmt->bind_param("s", $u_email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        // If you use hashed passwords in your DB, use password_verify instead of ===
        // Example: if (password_verify($u_pwd, $user['u_pwd']))
        if ($u_pwd === $user['u_pwd']) {
            $_SESSION['u_id'] = $user['u_id'];

            // Log user login with IP
            $ip = $_SERVER['REMOTE_ADDR'];
            $city = 'N/A';
            $country = 'N/A';

            $log_stmt = $mysqli->prepare("INSERT INTO userLog(u_id, u_email, u_ip, u_city, u_country) VALUES (?, ?, ?, ?, ?)");
            $log_stmt->bind_param("issss", $user['u_id'], $u_email, $ip, $city, $country);
            $log_stmt->execute();

            header("Location: usr-book-vehicle.php");
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
</head>

<body class="bg-dark">
<div class="container">
    <div class="card card-login mx-auto mt-5">
        <div class="card-header">Client Login Panel</div>
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
                <a class="d-block small" href="usr-forgot-password.php">Forgot Password?</a>
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
