<?php
session_start();
include('../DATABASE FILE/config.php');
include('../DATABASE FILE/checklogin.php');
check_login();
$aid = $_SESSION['a_id'];

// Prevent caching to ensure back button doesn't show stale page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Add User
if (isset($_POST['add_user'])) {
    $u_fname = trim($_POST['u_fname']);
    $u_lname = trim($_POST['u_lname']);
    $u_phone = trim($_POST['u_phone']);
    $u_addr = trim($_POST['u_addr']);
    $u_email = trim($_POST['u_email']);
    $u_pwd = $_POST['u_pwd']; 
    $u_role = $_POST['u_role'];
    $u_remark = trim($_POST['u_remark']);
    
    // Driver specific fields
    $d_license_no = trim($_POST['d_license_no'] ?? '');
    $d_license_expiry = $_POST['d_license_expiry'] ?? null;
    $d_experience = $_POST['d_experience'] ?? 0;
    $d_status = $_POST['d_status'] ?? 'ACTIVE';

    // Hash the password
    $hashed_pwd = password_hash($u_pwd, PASSWORD_DEFAULT);

    global $mysqli;

    // Check if email already exists in 'users' table
    $check_stmt = $mysqli->prepare("SELECT email FROM users WHERE email = ?");
    $check_stmt->bind_param('s', $u_email);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        $err = "User with this email already exists!";
    } else {
        // Insert into 'users' table
        $stmt = $mysqli->prepare("INSERT INTO users (first_name, last_name, phone, address, email, password, role, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
        $stmt->bind_param('sssssss', $u_fname, $u_lname, $u_phone, $u_addr, $u_email, $hashed_pwd, $u_role);
        
        if ($stmt->execute()) {
            $new_user_id = $stmt->insert_id;
            $last_remark_id = null;

            // Insert Remark if provided
            if (!empty($u_remark)) {
                $remark_stmt = $mysqli->prepare("INSERT INTO entity_remarks (entity_type, entity_id, user_id, remark) VALUES ('USER', ?, ?, ?)");
                $remark_stmt->bind_param('iis', $new_user_id, $aid, $u_remark);
                $remark_stmt->execute();
                $last_remark_id = $remark_stmt->insert_id;
                $remark_stmt->close();
            }

            // If Role is DRIVER, insert into 'drivers' table
            if ($u_role === 'DRIVER') {
                $driver_stmt = $mysqli->prepare("INSERT INTO drivers (user_id, license_no, license_expiry, experience_years, status, last_remark_id) VALUES (?, ?, ?, ?, ?, ?)");
                $driver_stmt->bind_param('issisi', $new_user_id, $d_license_no, $d_license_expiry, $d_experience, $d_status, $last_remark_id);
                $driver_stmt->execute();
                $driver_stmt->close();
            }

            $succ = "User Added Successfully";
        } else {
            $err = "Something went wrong. Please try again.";
        }
        $stmt->close();
    }
    $check_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<?php include('vendor/inc/head.php'); ?>

<body id="page-top">

<div id="wrapper">

    <div id="content-wrapper">

        <div class="container-fluid">
            <?php if (isset($succ)) { ?>
                <script>
                    setTimeout(function () {
                        swal("Success!", "<?php echo $succ; ?>", "success");
                    }, 100);
                </script>
            <?php } ?>
            <?php if (isset($err)) { ?>
                <script>
                    setTimeout(function () {
                        swal("Failed!", "<?php echo $err; ?>", "error");
                    }, 100);
                </script>
            <?php } ?>

            <div class="card shadow-lg border-0 rounded-lg mt-4">
                <div class="card-header bg-primary text-white d-flex align-items-center">
                    <a href="javascript:void(0);" onclick="window.location.replace('admin-view-user.php')" class="btn btn-light btn-sm mr-3 text-primary font-weight-bold"><i class="fas fa-arrow-left"></i> Back</a>
                    <h4 class="font-weight-bold mb-0 flex-grow-1 text-center" style="margin-right: 60px;"><i class="fas fa-user-plus"></i> Add New User</h4>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold">First Name</label>
                                    <input type="text" required class="form-control" name="u_fname" placeholder="Enter First Name">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold">Last Name</label>
                                    <input type="text" required class="form-control" name="u_lname" placeholder="Enter Last Name">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold">Contact Number</label>
                                    <input type="text" required class="form-control" name="u_phone" placeholder="Enter Contact Number">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold">Email Address</label>
                                    <input type="email" required class="form-control" name="u_email" placeholder="Enter Email Address">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Address</label>
                            <textarea required class="form-control" name="u_addr" rows="2" placeholder="Enter Address"></textarea>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Remark (Optional)</label>
                            <textarea class="form-control" name="u_remark" rows="2" placeholder="Enter any initial remarks..."></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold">Role</label>
                                    <select class="form-control" name="u_role" id="u_role" required onchange="toggleDriverFields()">
                                        <option value="EMPLOYEE">Employee</option>
                                        <option value="ADMIN">Admin</option>
                                        <option value="MANAGER">Manager</option>
                                        <option value="DRIVER">Driver</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold">Password</label>
                                    <input type="password" required class="form-control" name="u_pwd" placeholder="Enter Password">
                                </div>
                            </div>
                        </div>

                        <!-- Driver Specific Fields (Hidden by default) -->
                        <div id="driver-fields" style="display: none; background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin-top: 10px;">
                            <h5 class="text-secondary border-bottom pb-2">Driver Details</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold">License Number</label>
                                        <input type="text" class="form-control" name="d_license_no" placeholder="Enter License No">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold">License Expiry Date</label>
                                        <input type="date" class="form-control" name="d_license_expiry">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold">Experience (Years)</label>
                                        <input type="number" class="form-control" name="d_experience" placeholder="e.g. 5">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold">Status</label>
                                        <select class="form-control" name="d_status">
                                            <option value="ACTIVE">Active</option>
                                            <option value="ON_LEAVE">On Leave</option>
                                            <option value="INACTIVE">Inactive</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-center mt-4">
                            <button type="submit" name="add_user" class="btn btn-success btn-lg px-5 shadow-sm">
                                <i class="fas fa-check-circle"></i> Register User
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Scroll to Top Button-->
<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

<!-- Bootstrap core JavaScript-->
<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

<!-- Core plugin JavaScript-->
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>

<!-- Page level plugin JavaScript-->
<script src="vendor/datatables/jquery.dataTables.js"></script>
<script src="vendor/datatables/dataTables.bootstrap4.js"></script>

<!-- Custom scripts for all pages-->
<script src="vendor/js/sb-admin.min.js"></script>

<!-- SweetAlert -->
<script src="vendor/js/swal.js"></script>

<script>
    function toggleDriverFields() {
        var role = document.getElementById("u_role").value;
        var driverFields = document.getElementById("driver-fields");
        
        if (role === "DRIVER") {
            driverFields.style.display = "block";
            // Make driver fields required if visible
            document.getElementsByName("d_license_no")[0].required = true;
            document.getElementsByName("d_license_expiry")[0].required = true;
        } else {
            driverFields.style.display = "none";
            // Remove required attribute if hidden
            document.getElementsByName("d_license_no")[0].required = false;
            document.getElementsByName("d_license_expiry")[0].required = false;
        }
    }
</script>

</body>
</html>
