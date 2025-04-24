<?php
session_start();
include('vendor/inc/config.php');
include('vendor/inc/checklogin.php');
check_login();
$u_id = $_SESSION['u_id'];

$succ = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old_pwd = $_POST['old_pwd'];
    $new_pwd = $_POST['new_pwd'];
    $confirm_pwd = $_POST['confirm_pwd'];

    // Fetch plain text password
    $stmt = $mysqli->prepare("SELECT u_pwd FROM tms_user WHERE u_id=?");
    $stmt->bind_param("i", $u_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && $old_pwd === $user['u_pwd']) {
        if ($new_pwd === $confirm_pwd) {
            $update = $mysqli->prepare("UPDATE tms_user SET u_pwd=? WHERE u_id=?");
            $update->bind_param("si", $new_pwd, $u_id);
            if ($update->execute()) {
                $succ = "Password updated successfully!";
            } else {
                $err = "Database update error!";
            }
        } else {
            $err = "New and Confirm password do not match!";
        }
    } else {
        $err = "Old password is incorrect!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<?php include('vendor/inc/head.php'); ?>
<body id="page-top">
<div id="wrapper">
    <div id="content-wrapper">
        <div class="container-fluid mt-4">

            <!-- Bootstrap Alert -->
            <?php if (!empty($succ)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $succ; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <script>
                    setTimeout(() => {
                        window.location.href = "user-dashboard.php";
                    }, 2500);
                </script>
            <?php elseif (!empty($err)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $err; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">Change Your Password</div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-group">
                            <label>Old Password</label>
                            <input type="password" class="form-control" name="old_pwd" required>
                        </div>
                        <div class="form-group">
                            <label>New Password</label>
                            <input type="password" class="form-control" name="new_pwd" required>
                        </div>
                        <div class="form-group">
                            <label>Confirm Password</label>
                            <input type="password" class="form-control" name="confirm_pwd" required>
                        </div>
                        <button type="submit" class="btn btn-danger btn-block mt-3">
                            Update Password
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- JS Files -->
<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="vendor/js/sb-admin.min.js"></script>

</body>
</html>
