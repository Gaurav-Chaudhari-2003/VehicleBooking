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
    header("Location: user-dashboard.php");
    exit();
}

$error = '';

if (isset($_POST['Usr-login'])) {
    $u_email = trim($_POST['u_email']);
    $u_pwd = $_POST['u_pwd'];

    // Secure login query using prepared statements
    $stmt = $mysqli->prepare("SELECT id, password FROM users WHERE email = ? AND role = 'EMPLOYEE' AND is_active = 1");
    $stmt->bind_param("s", $u_email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        // Verify the password hash
        if (password_verify($u_pwd, $user['password']) || $u_pwd === $user['password']) {
            $_SESSION['u_id'] = $user['id'];

            // Log user login with IP
            $ip = $_SERVER['REMOTE_ADDR'];
            $user_agent = $_SERVER['HTTP_USER_AGENT'];
            $action = "User Login";

            $log_stmt = $mysqli->prepare("INSERT INTO system_logs(user_id, action, ip, user_agent) VALUES (?, ?, ?, ?)");
            $log_stmt->bind_param("isss", $user['id'], $action, $ip, $user_agent);
            $log_stmt->execute();

            header("Location: user-dashboard.php");
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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Client Login | Vehicle Booking System</title>
    
    <!-- Include Global Theme -->
    <?php include("../vendor/inc/theme-config.php"); ?>
    
    <style>
        body {
            background-color: #f0f2f5;
            font-family: 'Segoe UI', 'Roboto', sans-serif;
        }
        
        .main-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .auth-card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.08);
            overflow: hidden;
            width: 100%;
            max-width: 1000px;
            display: flex;
            flex-wrap: wrap;
            position: relative;
        }
        
        .auth-sidebar {
            background: linear-gradient(135deg, #004d40 0%, #00796b 100%);
            width: 45%;
            padding: 40px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        /* Decorative circles */
        .circle-decoration {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
        }
        .circle-1 { width: 300px; height: 300px; top: -100px; left: -100px; }
        .circle-2 { width: 200px; height: 200px; bottom: -50px; right: -50px; }
        
        .sidebar-content {
            position: relative;
            z-index: 2;
        }
        
        .logo-container {
            width: 100px;
            height: 100px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 25px;
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255,255,255,0.3);
        }
        
        .logo-img {
            width: 260px;
            height: auto;
        }
        
        .auth-form-side {
            width: 55%;
            padding: 50px;
            background-color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .form-title {
            font-weight: 800;
            color: #333;
            margin-bottom: 10px;
            font-size: 2rem;
        }
        
        .form-subtitle {
            color: #666;
            margin-bottom: 40px;
        }
        
        .form-floating > .form-control {
            border-radius: 12px;
            border: 1px solid #e9ecef;
            background-color: #f8f9fa;
            height: 50px;
        }
        
        .form-floating > .form-control:focus {
            background-color: #fff;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 4px rgba(0, 121, 107, 0.1);
        }
        
        .form-floating > label {
            padding-top: 12px;
        }
        
        .btn-login {
            background-color: var(--primary-color);
            border: none;
            color: white;
            border-radius: 50px;
            padding: 14px 40px;
            font-weight: 700;
            font-size: 1rem;
            transition: all 0.3s;
            box-shadow: 0 10px 20px rgba(0, 77, 64, 0.15);
            width: 100%;
        }
        
        .btn-login:hover {
            background-color: var(--secondary-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(0, 77, 64, 0.25);
        }
        
        .back-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            color: #666;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            transition: transform 0.2s;
            z-index: 10;
            background: white;
            padding: 8px 15px;
            border-radius: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .back-btn:hover {
            transform: translateX(-3px);
            text-decoration: none;
            color: var(--primary-color);
        }
        
        .register-link {
            color: var(--primary-color);
            font-weight: 700;
            text-decoration: none;
        }
        
        .register-link:hover {
            text-decoration: underline;
        }
        
        .password-toggle {
            position: absolute;
            top: 65%;
            right: 20px;
            cursor: pointer;
            color: #999;
            z-index: 10;
        }
        
        @media (max-width: 991px) {
            .auth-sidebar { width: 100%; padding: 40px 20px; }
            .auth-form-side { width: 100%; padding: 40px 20px; }
            .circle-1 { width: 150px; height: 150px; }
        }
    </style>
</head>
<body>

<div class="main-wrapper">
    <a href="../index.php" class="back-btn">
        <i class="fas fa-arrow-left mr-2"></i> Home
    </a>

    <?php if (!empty($error)) : ?>
        <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
        <script>
            setTimeout(() => {
                swal("Login Failed", "<?php echo $error; ?>", "error");
            }, 100);
        </script>
    <?php endif; ?>

    <div class="auth-card">
        <!-- Sidebar -->
        <div class="auth-sidebar">
            <div class="circle-decoration circle-1"></div>
            <div class="circle-decoration circle-2"></div>
            
            <div class="sidebar-content">
                <div class="logo-container mx-auto">
                    <img src="https://www.cmpdi.co.in/sites/default/files/cmpdi_new_logo_10012025.png" alt="Logo" class="logo-img">
                </div>
                <h2 class="fw-bold mb-3">CMPDI RI-4</h2>
                <p class="mb-0 opacity-75 lead">Official Vehicle Booking Portal</p>
                <div class="mt-4 pt-4 border-top border-light opacity-50 w-50 mx-auto"></div>
                <p class="mt-4 small opacity-75">Secure • Efficient • Reliable</p>
            </div>
        </div>

        <!-- Form Side -->
        <div class="auth-form-side">
            <div class="d-flex flex-column h-100 justify-content-center">
                <h3 class="form-title">Welcome Back</h3>
                <p class="form-subtitle">Please sign in to your account to continue.</p>
                
                <form method="post">
                    <div class="form-floating mb-3">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="u_email" class="form-control" placeholder="abc@gmail.com" required autofocus>
                    </div>

                    <div class="form-floating mb-4 position-relative">
                        <label for="pwd">Password</label>
                        <input type="password" id="pwd" name="u_pwd" class="form-control" placeholder="********" required>
                        <i class="fas fa-eye password-toggle" onclick="togglePassword('pwd', this)"></i>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="rememberMe">
                            <label class="form-check-label small text-muted" for="rememberMe">Remember me</label>
                        </div>
                        <a href="../password-reset.php" class="small text-muted text-decoration-none">Forgot Password?</a>
                    </div>

                    <button type="submit" name="Usr-login" class="btn btn-login">
                        Sign In <i class="fas fa-sign-in-alt ms-2"></i>
                    </button>
                </form>

                <div class="text-center mt-5">
                    <p class="mb-0 text-muted">Don't have an account? <a href="usr-register.php" class="register-link">Register Now</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JS Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function togglePassword(inputId, icon) {
        const input = document.getElementById(inputId);
        if (input.type === "password") {
            input.type = "text";
            icon.classList.remove("fa-eye");
            icon.classList.add("fa-eye-slash");
        } else {
            input.type = "password";
            icon.classList.remove("fa-eye-slash");
            icon.classList.add("fa-eye");
        }
    }
</script>
</body>
</html>
