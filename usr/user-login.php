<?php
global $mysqli;
session_start();
include('../DATABASE FILE/config.php');
include('../DATABASE FILE/checklogin.php');

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
    <title>Client Login | Vehicle Booking System</title>
    
    <!-- Include Global Theme -->
    <?php include("../vendor/inc/theme-config.php"); ?>
    
    <style>
        body {
            background: linear-gradient(135deg, var(--accent-color) 0%, #ffffff 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        
        .login-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
            background: #fff;
        }
        
        .card-header {
            background-color: #fff;
            border-bottom: none;
            padding: 30px 30px 10px;
            text-align: center;
        }
        
        .card-body {
            padding: 30px;
        }
        
        .login-logo {
            width: 80px;
            margin-bottom: 15px;
        }
        
        .login-title {
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        
        .login-subtitle {
            color: #666;
            font-size: 0.9rem;
        }
        
        .form-control {
            border-radius: 50px;
            padding: 12px 20px;
            height: auto;
            background-color: #f8f9fa;
            border: 1px solid #eee;
        }
        
        .form-control:focus {
            box-shadow: none;
            border-color: var(--secondary-color);
            background-color: #fff;
        }
        
        .btn-login {
            border-radius: 50px;
            padding: 12px;
            font-weight: 600;
            background-color: var(--primary-color);
            border: none;
            transition: all 0.3s;
        }
        
        .btn-login:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .back-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            transition: transform 0.2s;
        }
        
        .back-btn:hover {
            transform: translateX(-3px);
            text-decoration: none;
            color: var(--secondary-color);
        }
        
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 20px 0;
            color: #ccc;
        }
        
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #eee;
        }
        
        .divider span {
            padding: 0 10px;
            font-size: 0.8rem;
        }
        
        .footer-links a {
            color: #666;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.2s;
        }
        
        .footer-links a:hover {
            color: var(--primary-color);
        }
    </style>
    
    <script>
        // Prevent back button from showing this page if coming from dashboard
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</head>

<body>

<a href="../index.php" class="back-btn">
    <i class="fas fa-arrow-left mr-2"></i> Back to Home
</a>

<div class="login-card">
    <div class="card-header">
        <img src="https://www.cmpdi.co.in/sites/default/files/cmpdi_new_logo_10012025.png" alt="Logo" class="login-logo">
        <h4 class="login-title">Welcome Back</h4>
        <p class="login-subtitle">Sign in to book your vehicle</p>
    </div>
    
    <div class="card-body">
        <?php if (!empty($error)): ?>
            <script src="vendor/js/swal.js"></script>
            <script>
                setTimeout(function () {
                    swal("Login Failed", "<?php echo $error; ?>", "error");
                }, 100);
            </script>
            <div class="alert alert-danger text-center small py-2 mb-3 rounded-pill">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group mb-3">
                <input type="email" name="u_email" class="form-control" placeholder="Email Address" required autofocus>
            </div>
            <div class="form-group mb-4">
                <input type="password" name="u_pwd" class="form-control" placeholder="Password" required>
            </div>
            <button type="submit" name="Usr-login" class="btn btn-primary btn-block btn-login">
                Sign In
            </button>
        </form>

        <div class="divider">
            <span>OR</span>
        </div>

        <div class="text-center footer-links">
            <p class="mb-2">Don't have an account? <a href="usr-register.php" class="font-weight-bold">Register Now</a></p>
            <a href="../password-reset.php" class="small text-muted">Forgot Password?</a>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
</body>

</html>
