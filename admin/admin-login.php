<?php
session_start();
include('../DATABASE FILE/config.php');
include('../DATABASE FILE/checklogin.php');

// Prevent caching to ensure the back button doesn't show a stale page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Check if a user is already logged in
if (isset($_SESSION['a_id'])) {
    $aid = $_SESSION['a_id'];
    global $mysqli;
    
    // Verify if the logged-in user is an ADMIN
    $stmt = $mysqli->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->bind_param('i', $aid);
    $stmt->execute();
    global $role;
    $stmt->bind_result($role);
    $stmt->fetch();
    $stmt->close();

    if ($role === 'ADMIN') {
        echo '<script type="text/javascript">';
        echo 'window.location.replace("admin-dashboard.php");';
        echo '</script>';
        exit();
    }
}

if (isset($_POST['admin_login'])) {
    $email = $_POST['a_email'];
    $password = $_POST['a_pwd'];

    // New Schema: users table with role='ADMIN'
    // Columns: id, email, password, role
    global $mysqli;
    $stmt = $mysqli->prepare("SELECT id, email, password, role FROM users WHERE email=? AND role='ADMIN'");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    global $id, $db_email, $db_password, $role;
    $stmt->bind_result($id, $db_email, $db_password, $role);
    $stmt->fetch();
    $stmt->close();

    // Verify password (assuming hashed passwords as per new schema best practices)
    // If you are still using plain text for legacy reasons, you might need to adjust this.
    // But the new schema implies secure practices.
    // For now, I will support both hash verification and plain text fallback for transition.
    
    if ($id && (password_verify($password, $db_password) || $password === $db_password)) {
        $_SESSION['a_id'] = $id;
        // Use JavaScript to replace the current history entry and redirect
        echo '<script type="text/javascript">';
        echo 'window.location.replace("admin-dashboard.php");';
        echo '</script>';
        exit();
    } else {
        $error = "Access Denied. Please Check Your Credentials.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Vehicle Booking System - Admin Login</title>

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
  <!--Trigger Sweet Alert-->
  <?php if(isset($error)) {?>
      <script src="vendor/js/swal.js"></script>
      <script>
            setTimeout(function ()
            { 
              swal("Failed!","<?php echo $error;?>!","error");
            },
              100);
      </script>
  <?php } ?>

  <div class="main-wrapper">
      <a href="../index.php" class="back-btn">
          <i class="fas fa-arrow-left mr-2"></i> Back to Home
      </a>

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
                <h3 class="form-title">Admin Portal</h3>
                <p class="form-subtitle">Sign in to manage vehicle bookings</p>
                
                <form method="POST">
                    <div class="form-floating mb-3">
                        <label for="inputEmail">Email address</label>
                        <input type="email" id="inputEmail" name="a_email" class="form-control" required="required" autofocus="autofocus">
                    </div>
                    
                    <div class="form-floating mb-4 position-relative">
                        <label for="inputPassword">Password</label>
                        <input type="password" id="inputPassword" name ="a_pwd" class="form-control" required="required">
                        <i class="fas fa-eye password-toggle" onclick="togglePassword('inputPassword', this)"></i>
                    </div>
                    
                    <div class="form-group mb-4">
                      <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="rememberMe" value="remember-me">
                        <label class="custom-control-label small text-muted" for="rememberMe">Remember Password</label>
                      </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block btn-login" name="admin_login">Login</button>
                </form>

                <div class="text-center mt-4">
                    <a class="small text-muted text-decoration-none" href="../password-reset.php">Forgot Password?</a>
                </div> 
            </div>
        </div>
      </div>
  </div>

  <!-- Bootstrap core JavaScript-->
  <script src="vendor/jquery/jquery.min.js"></script>
  <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

  <!-- Core plugin JavaScript-->
  <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
  <!--Sweet alerts js-->
  <script src="vendor/js/swal.js"></script>
  
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
