<?php
global $mysqli;
session_start();
include('vendor/inc/config.php');
include('vendor/inc/checklogin.php');
check_login();

$aid = $_SESSION['u_id'];

$query = "SELECT u_fname, u_lname, u_addr, u_phone, u_email FROM tms_user WHERE u_id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $aid);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_object();
?>

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center bg-info text-white">
        <div><i class="fas fa-user-circle"></i> User Profile</div>
    </div>
        <div class="card-body">
            <!-- User Info Header -->
            <div class="text-center mb-5">
                <h3 class="text-dark font-weight-bold">
                    <?php echo htmlspecialchars($user->u_fname . ' ' . $user->u_lname); ?>
                </h3>
            </div>

            <!-- Profile Info Layout -->
            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-map-marker-alt me-2 text-muted"></i>
                        <strong>Address:</strong>
                    </div>
                    <p class="text-muted"><?php echo htmlspecialchars($user->u_addr); ?></p>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-phone me-2 text-muted"></i>
                        <strong>Contact:</strong>
                    </div>
                    <p class="text-muted"><?php echo htmlspecialchars($user->u_phone); ?></p>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-envelope me-2 text-muted"></i>
                        <strong>Email:</strong>
                    </div>
                    <p class="text-muted"><?php echo htmlspecialchars($user->u_email); ?></p>
                </div>
            </div>

            <!-- Action Buttons with Smooth Hover Effects -->
            <div class="d-flex justify-content-between mt-5 gap-3">
                <a href="user-update-profile.php" class="btn btn-outline-primary px-5 py-3 rounded-pill shadow-sm transition-all hover:bg-primary hover:text-white">
                    <i class="fa fa-user-edit me-2"></i> Update Profile
                </a>
                <a href="user-change-pwd.php" class="btn btn-outline-danger px-5 py-3 rounded-pill shadow-sm transition-all hover:bg-danger hover:text-white">
                    <i class="fa fa-key me-2"></i> Change Password
                </a>
                <button class="btn btn-danger px-5 py-3 rounded-pill shadow-sm transition-all hover:bg-dark hover:text-white" data-bs-toggle="modal" data-bs-target="#logoutModal">
                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                </button>
            </div>
        </div>
    </div>

    <!-- Logout Modal -->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="logoutModalLabel">
                        <i class="fas fa-sign-out-alt me-2"></i> Confirm Logout
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <p class="text-muted mb-3">Are you sure you want to log out from your account?</p>
                    <i class="fas fa-exclamation-triangle fa-4x text-warning mb-4"></i>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="user-logout.php" class="btn btn-danger">Logout</a>
                </div>
            </div>
        </div>
    </div>


<!-- Bootstrap Logout Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title text-dark" id="logoutModalLabel">
                    <i class="fas fa-sign-out-alt me-2"></i>Confirm Logout
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <p class="text-gray-700 mb-3">Are you sure you want to log out from your account?</p>
                <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="user-logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </div>
</div>

<!-- Ensure Bootstrap JS is loaded -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
