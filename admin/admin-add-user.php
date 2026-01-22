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

<head>
    <meta charset="UTF-8">
    <title>Manage User | Vehicle Booking System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Include Global Theme -->
    <?php include("../vendor/inc/theme-config.php"); ?>

    <style>
        body {
            background-color: #fff;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar styles are in sidebar.php */

        .main-content {
            flex: 1;
            padding: 30px;
            margin-left: 260px; /* Width of sidebar */
            background-color: #f8f9fa;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #eee;
            padding: 20px;
            border-radius: 15px 15px 0 0 !important;
        }

        .form-control, .custom-select {
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            padding: 10px 15px;
            height: auto;
        }

        .form-control:focus, .custom-select:focus {
            box-shadow: 0 0 0 3px rgba(0, 121, 107, 0.1);
            border-color: var(--secondary-color);
        }

        .btn-update {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 12px 30px;
            font-weight: 600;
            box-shadow: 0 4px 10px rgba(0, 77, 64, 0.2);
            transition: all 0.3s;
            margin: 0 10px;
        }

        .btn-update:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 77, 64, 0.3);
            color: white;
        }

        .btn-disable {
            border-radius: 50px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s;
            margin: 0 10px;
        }
    </style>
</head>

<body id="page-top" style="background-color: #f8f9fc;">

<div id="wrapper">
    <!-- Sidebar -->
    <?php include("vendor/inc/sidebar.php"); ?>

    <div id="content-wrapper" class="d-flex flex-column" style="margin-left: 260px; width: calc(100% - 260px);">

        <div class="container-fluid mt-4">
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

            <div class="card shadow border-0 rounded-lg mb-4">
                <div class="card-header bg-white py-3 d-flex align-items-center border-bottom-0">
                    <a href="javascript:void(0);" onclick="window.location.replace('admin-view-user.php')" class="btn btn-sm btn-outline-secondary font-weight-bold rounded-pill px-3 mr-3"><i class="fas fa-arrow-left mr-1"></i> Back</a>
                    <h5 class="mb-0 font-weight-bold text-primary flex-grow-1 text-center" style="margin-right: 80px;">
                        <i class="fas fa-user-plus mr-2"></i> Add New User
                    </h5>
                </div>
                <div class="card-body bg-light p-4">
                    <form method="POST">
                        <div class="row">
                            <!-- Left Column: Personal Info -->
                            <div class="col-lg-6 mb-4">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body p-4">
                                        <h6 class="text-uppercase text-muted small font-weight-bold mb-3 border-bottom pb-2">Personal Information</h6>
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label class="small font-weight-bold">First Name</label>
                                                <input type="text" required class="form-control" name="u_fname">
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label class="small font-weight-bold">Last Name</label>
                                                <input type="text" required class="form-control" name="u_lname">
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label class="small font-weight-bold">Contact Number</label>
                                            <input type="text" required class="form-control" name="u_phone">
                                        </div>

                                        <div class="form-group">
                                            <label class="small font-weight-bold">Email Address</label>
                                            <input type="email" required class="form-control" name="u_email">
                                        </div>

                                        <div class="form-group">
                                            <label class="small font-weight-bold">Address</label>
                                            <textarea required class="form-control" name="u_addr" rows="2"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column: Account & Role -->
                            <div class="col-lg-6 mb-4">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body p-4">
                                        <h6 class="text-uppercase text-muted small font-weight-bold mb-3 border-bottom pb-2">Account & Role</h6>
                                        
                                        <div class="form-group">
                                            <label class="small font-weight-bold">Role</label>
                                            <select class="form-control custom-select" name="u_role" id="u_role" required onchange="toggleDriverFields()">
                                                <option value="EMPLOYEE">Employee</option>
                                                <option value="ADMIN">Admin</option>
                                                <option value="MANAGER">Manager</option>
                                                <option value="DRIVER">Driver</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label class="small font-weight-bold">Password</label>
                                            <input type="password" required class="form-control" name="u_pwd" >
                                        </div>

                                        <!-- Driver Specific Fields (Hidden by default) -->
                                        <div id="driver-fields" style="display: none;" class="mt-3">
                                            <div class="bg-light p-3 rounded border">
                                                <h6 class="text-secondary font-weight-bold mb-3 small text-uppercase">Driver Details</h6>
                                                <div class="form-row">
                                                    <div class="form-group col-md-6">
                                                        <label class="small font-weight-bold">License Number</label>
                                                        <input type="text" class="form-control form-control-sm" name="d_license_no">
                                                    </div>
                                                    <div class="form-group col-md-6">
                                                        <label class="small font-weight-bold">License Expiry Date</label>
                                                        <input type="date" class="form-control form-control-sm" name="d_license_expiry">
                                                    </div>
                                                </div>
                                                <div class="form-row">
                                                    <div class="form-group col-md-6">
                                                        <label class="small font-weight-bold">Experience (Years)</label>
                                                        <input type="number" class="form-control form-control-sm" name="d_experience" placeholder="e.g. 5">
                                                    </div>
                                                    <div class="form-group col-md-6">
                                                        <label class="small font-weight-bold">Status</label>
                                                        <select class="form-control form-control-sm custom-select" name="d_status">
                                                            <option value="ACTIVE">Active</option>
                                                            <option value="ON_LEAVE">On Leave</option>
                                                            <option value="INACTIVE">Inactive</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Remarks Section -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body p-4">
                                        <h6 class="text-uppercase text-muted small font-weight-bold mb-3 border-bottom pb-2">Remarks</h6>
                                        <div class="form-group mb-0">
                                            <textarea class="form-control" name="u_remark" rows="2" placeholder="Enter any initial remarks..."></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-center mt-4 mb-5">
                            <button type="submit" name="add_user" class="btn btn-success btn-lg px-5 shadow-sm rounded-pill">
                                <i class="fas fa-check-circle mr-2"></i> Register User
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
