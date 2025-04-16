<?php
session_start();
include('vendor/inc/config.php');
include('vendor/inc/checklogin.php');
check_login();

// Get the user ID from the session
$aid = $_SESSION['u_id'];

// Fetch user details in a more efficient manner
$query = "SELECT u_fname, u_lname, u_addr, u_phone, u_email FROM tms_user WHERE u_id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $aid);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_object();
?>
<!DOCTYPE html>
<html lang="en">

<!-- Head -->
<?php include('vendor/inc/head.php'); ?>
<!-- End Head -->

<body id="page-top">

<div class="container-fluid">
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center bg-info text-white">
            <div><i class="fas fa-user"></i> User Profile </div>
        </div>

        <!-- Profile Card -->
        <div class="card mb-4 col-md-8 mx-auto">
            <div class="card-header bg-info text-white">
                <h5 class="card-title"><?php echo htmlspecialchars($user->u_fname . ' ' . $user->u_lname); ?></h5>
            </div>
            <div class="card-body">
                <ul class="list-group">
                    <li class="list-group-item"><strong>Address:</strong> <?php echo htmlspecialchars($user->u_addr); ?></li>
                    <li class="list-group-item"><strong>Contact:</strong> <?php echo htmlspecialchars($user->u_phone); ?></li>
                    <li class="list-group-item"><strong>Email Address:</strong> <?php echo htmlspecialchars($user->u_email); ?></li>
                </ul>
            </div>
            <div class="card-footer text-right">
                <a href="user-update-profile.php" class="btn btn-outline-primary"><i class="fa fa-user-edit"></i> Update Profile</a>
                <a href="user-change-pwd.php" class="btn btn-outline-danger"><i class="fa fa-key"></i> Change Password</a>
            </div>
        </div>

    </div>
</div>

<!-- Scroll to Top Button -->
<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

<!-- Logout Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                <a class="btn btn-danger" href="user-logout.php">Logout</a>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap core JavaScript-->
<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

<!-- Core plugin JavaScript-->
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>

<!-- Custom scripts for all pages-->
<script src="vendor/js/sb-admin.min.js"></script>

</body>
</html>
