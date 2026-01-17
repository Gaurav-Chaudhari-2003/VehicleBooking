<?php
session_start();
include('vendor/inc/config.php'); // get configuration file

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

  <!-- Custom fonts for this template-->
  <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <!-- Custom styles for this template-->
  <link href="vendor/css/sb-admin.css" rel="stylesheet">
</head>

<body class="bg-dark">
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

  <div class="container">
    <div class="card card-login mx-auto mt-5">
      <div class="card-header d-flex align-items-center">
          <a href="javascript:void(0);" onclick="window.location.replace('../index.php')" class="btn btn-light btn-sm mr-3 text-primary font-weight-bold"><i class="fas fa-arrow-left"></i> Back</a>
          <div class="flex-grow-1 text-center" style="margin-right: 60px;">Admin Login</div>
      </div>
      <div class="card-body">

        <form method="POST">
          <div class="form-group">
            <div class="form-label-group">
              <input type="email" id="inputEmail" name="a_email" class="form-control" placeholder="Email address" required="required" autofocus="autofocus">
              <label for="inputEmail">Email address</label>
            </div>
          </div>
          <div class="form-group">
            <div class="form-label-group">
              <input type="password" id="inputPassword" name ="a_pwd" class="form-control" placeholder="Password" required="required">
              <label for="inputPassword">Password</label>
            </div>
          </div>
          <div class="form-group">
            <div class="checkbox">
              <label>
                <input type="checkbox" value="remember-me">
                Remember Password 
              </label>
            </div>
          </div>
          <input type="submit"  class="btn btn-success btn-block" name="admin_login" value="Login">
        </form>

        <div class="text-center">
          <a class="d-block small mt-3" href="../index.php">Home</a>
          <a class="d-block small" href="admin-reset-pwd.php">Forgot Password?</a>
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

</body>

</html>
