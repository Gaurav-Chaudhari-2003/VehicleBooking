<?php
session_start();
include('vendor/inc/config.php');
include('vendor/inc/checklogin.php');
check_login();

$u_id = $_SESSION['u_id'];
$succ = $err = "";

// Handle profile update
if (isset($_POST['update_profile'])) {
    $u_fname = $_POST['u_fname'];
    $u_lname = $_POST['u_lname'];
    $u_phone = $_POST['u_phone'];
    $u_addr  = $_POST['u_addr'];
    $u_email = $_POST['u_email'];

    $stmt = $mysqli->prepare("UPDATE tms_user SET u_fname=?, u_lname=?, u_phone=?, u_addr=?, u_email=? WHERE u_id=?");
    if ($stmt) {
        $stmt->bind_param('sssssi', $u_fname, $u_lname, $u_phone, $u_addr, $u_email, $u_id);
        if ($stmt->execute()) {
            $succ = "Profile Updated Successfully!";
        } else {
            $err = "Execution Failed. Please Try Again";
        }
    } else {
        $err = "Database Error. Please Try Again";
    }
}

// Fetch current user data
$stmt = $mysqli->prepare("SELECT * FROM tms_user WHERE u_id=?");
$stmt->bind_param('i', $u_id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_object();
?>

<?php include("vendor/inc/head.php"); ?>
<!-- Include SweetAlert if not already included -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<body>

<div class="container mt-5">

    <!-- Show SweetAlert on success -->
    <?php if (!empty($succ)): ?>
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: '<?php echo $succ; ?>',
                    timer: 2000,
                    timerProgressBar: true,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = 'user-dashboard.php';
                });
            });
        </script>
    <?php endif; ?>

    <!-- Show SweetAlert on error -->
    <?php if (!empty($err)): ?>
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: '<?php echo $err; ?>',
                    confirmButtonColor: '#d33'
                });
            });
        </script>
    <?php endif; ?>

    <!-- Profile Update Form -->
    <div class="card shadow rounded-4">
        <div class="card-header bg-primary text-white fw-bold fs-5 py-3 rounded-top-4">
            Update Profile
        </div>
        <div class="card-body p-4">
            <form method="post" class="row g-3">
                <div class="col-md-6">
                    <label for="u_fname" class="form-label">First Name</label>
                    <input type="text" name="u_fname" id="u_fname" class="form-control" value="<?php echo htmlspecialchars($row->u_fname); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="u_lname" class="form-label">Last Name</label>
                    <input type="text" name="u_lname" id="u_lname" class="form-control" value="<?php echo htmlspecialchars($row->u_lname); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="u_phone" class="form-label">Phone Number</label>
                    <input type="text" name="u_phone" id="u_phone" class="form-control" value="<?php echo htmlspecialchars($row->u_phone); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="u_email" class="form-label">Email</label>
                    <input type="email" name="u_email" id="u_email" class="form-control" value="<?php echo htmlspecialchars($row->u_email); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="u_addr" class="form-label">Address</label>
                    <input type="text" name="u_addr" id="u_addr" class="form-control" value="<?php echo htmlspecialchars($row->u_addr); ?>" required>
                </div>
                <div class="col-12 text-end">
                    <button type="submit" name="update_profile" class="btn btn-success rounded-pill px-4 py-2">Update Profile</button>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>
