<?php
session_start();
include('../DATABASE FILE/config.php');
include('../DATABASE FILE/checklogin.php');
check_login();
$aid = $_SESSION['a_id'];

// Update User Logic
if (isset($_POST['update_user'])) {
    $u_id = $_GET['u_id'];
    $u_fname = $_POST['u_fname'];
    $u_lname = $_POST['u_lname'];
    $u_phone = $_POST['u_phone'];
    $u_addr = $_POST['u_addr'];
    $u_email = $_POST['u_email'];
    $u_pwd = $_POST['u_pwd'];
    $u_role = $_POST['u_role'];
    $admin_remark = trim($_POST['admin_remark']);
    
    // Driver specific fields
    $d_license_no = $_POST['d_license_no'] ?? '';
    $d_license_expiry = $_POST['d_license_expiry'] ?? null;
    $d_experience = $_POST['d_experience'] ?? 0;
    $d_status = $_POST['d_status'] ?? 'ACTIVE';

    global $mysqli;

    // Check if email already exists for another user
    $check_query = "SELECT id FROM users WHERE email = ? AND id != ?";
    $check_stmt = $mysqli->prepare($check_query);
    $check_stmt->bind_param('si', $u_email, $u_id);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        $err = "This email address is already in use by another user!";
    } else {
        // Update User Table
        // If approving (action=approve in URL), set is_active = 1
        $is_approve_action = (isset($_GET['action']) && $_GET['action'] == 'approve');
        $active_status_update = $is_approve_action ? ", is_active=1" : "";

        if (!empty($u_pwd)) {
             $hashed_pwd = password_hash($u_pwd, PASSWORD_DEFAULT);
             $query = "UPDATE users SET first_name=?, last_name=?, phone=?, address=?, role=?, email=?, password=? $active_status_update WHERE id=?";
             $stmt = $mysqli->prepare($query);
             $stmt->bind_param('sssssssi', $u_fname, $u_lname, $u_phone, $u_addr, $u_role, $u_email, $hashed_pwd, $u_id);
        } else {
             $query = "UPDATE users SET first_name=?, last_name=?, phone=?, address=?, role=?, email=? $active_status_update WHERE id=?";
             $stmt = $mysqli->prepare($query);
             $stmt->bind_param('ssssssi', $u_fname, $u_lname, $u_phone, $u_addr, $u_role, $u_email, $u_id);
        }

        if ($stmt->execute()) {
            // Handle Driver Details
            if ($u_role === 'DRIVER') {
                // Check if driver record exists
                $check_driver = $mysqli->prepare("SELECT id FROM drivers WHERE user_id = ?");
                $check_driver->bind_param('i', $u_id);
                $check_driver->execute();
                $check_driver->store_result();
                
                if ($check_driver->num_rows > 0) {
                    // Update existing driver record
                    $update_driver = $mysqli->prepare("UPDATE drivers SET license_no=?, license_expiry=?, experience_years=?, status=? WHERE user_id=?");
                    $update_driver->bind_param('ssisi', $d_license_no, $d_license_expiry, $d_experience, $d_status, $u_id);
                    $update_driver->execute();
                    $update_driver->close();
                } else {
                    // Insert new driver record
                    $insert_driver = $mysqli->prepare("INSERT INTO drivers (user_id, license_no, license_expiry, experience_years, status) VALUES (?, ?, ?, ?, ?)");
                    $insert_driver->bind_param('issis', $u_id, $d_license_no, $d_license_expiry, $d_experience, $d_status);
                    $insert_driver->execute();
                    $insert_driver->close();
                }
                $check_driver->close();
            }
            
            // Insert Admin Remark if provided
            if (!empty($admin_remark)) {
                $remark_query = "INSERT INTO entity_remarks (entity_type, entity_id, user_id, remark) VALUES (?, ?, ?, ?)";
                $remark_stmt = $mysqli->prepare($remark_query);
                $entity_type = 'USER';
                // user_id here is the admin ($aid) making the remark
                $remark_stmt->bind_param('siis', $entity_type, $u_id, $aid, $admin_remark);
                $remark_stmt->execute();
            }
            
            $succ = "User Updated Successfully";
            
            // If it was an approval action, redirect back to view users after a delay
            if ($is_approve_action) {
                echo "<script>setTimeout(function(){ window.location.href = 'admin-view-user.php'; }, 1500);</script>";
            }
            
        } else {
            $err = "Update Failed. Try Again Later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<?php include('vendor/inc/head.php'); ?>

<body id="page-top" style="background-color: #f8f9fc;">

<div id="wrapper">

    <div id="content-wrapper" class="d-flex flex-column">

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

            <div class="card shadow border-0 rounded-lg">
                <div class="card-header bg-white py-3 d-flex align-items-center border-bottom-0">
                    <a href="javascript:void(0);" onclick="window.location.replace('admin-view-user.php')" class="btn btn-sm btn-outline-secondary font-weight-bold rounded-pill px-3 mr-3"><i class="fas fa-arrow-left mr-1"></i> Back</a>
                    <h5 class="mb-0 font-weight-bold text-primary flex-grow-1 text-center" style="margin-right: 80px;">
                        <?php echo (isset($_GET['action']) && $_GET['action'] == 'approve') ? '<i class="fas fa-user-check mr-2"></i> Approve User' : '<i class="fas fa-user-edit mr-2"></i> Edit User Details'; ?>
                    </h5>
                </div>

                <div class="card-body bg-light p-4">
                    <?php
                    $u_id = $_GET['u_id'];
                    // Fetch user details
                    $ret = "SELECT * FROM users WHERE id=?";
                    $stmt = $mysqli->prepare($ret);
                    $stmt->bind_param('i', $u_id);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    while ($row = $res->fetch_object()) {
                        // Fetch driver details if role is DRIVER
                        $driver_details = null;
                        if ($row->role == 'DRIVER') {
                            $d_stmt = $mysqli->prepare("SELECT * FROM drivers WHERE user_id = ?");
                            $d_stmt->bind_param('i', $u_id);
                            $d_stmt->execute();
                            $driver_details = $d_stmt->get_result()->fetch_object();
                            $d_stmt->close();
                        }
                        
                        // Fetch user's remark (if any) - specifically for approval view
                        $user_remark = "";
                        if (isset($_GET['action']) && $_GET['action'] == 'approve') {
                            // Fetch the latest remark made by this user about themselves (USER entity)
                            // Or just any remark linked to this user entity where author is the user
                            $ur_stmt = $mysqli->prepare("SELECT remark FROM entity_remarks WHERE entity_type = 'USER' AND entity_id = ? AND user_id = ? ORDER BY created_at DESC LIMIT 1");
                            $ur_stmt->bind_param('ii', $u_id, $u_id);
                            $ur_stmt->execute();
                            $ur_res = $ur_stmt->get_result();
                            if ($ur_row = $ur_res->fetch_object()) {
                                $user_remark = $ur_row->remark;
                            }
                            $ur_stmt->close();
                        }
                        ?>
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
                                                    <input type="text" value="<?php echo $row->first_name; ?>" required class="form-control" name="u_fname">
                                                </div>
                                                <div class="form-group col-md-6">
                                                    <label class="small font-weight-bold">Last Name</label>
                                                    <input type="text" value="<?php echo $row->last_name; ?>" class="form-control" name="u_lname">
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label class="small font-weight-bold">Phone Number</label>
                                                <input type="text" value="<?php echo $row->phone; ?>" class="form-control" name="u_phone">
                                            </div>

                                            <div class="form-group">
                                                <label class="small font-weight-bold">Address</label>
                                                <input type="text" value="<?php echo $row->address; ?>" class="form-control" name="u_addr">
                                            </div>
                                            
                                            <div class="form-group">
                                                <label class="small font-weight-bold">Email Address</label>
                                                <div class="input-group">
                                                    <input type="email" value="<?php echo $row->email; ?>" class="form-control" id="u_email" name="u_email" readonly required>
                                                    <div class="input-group-append">
                                                        <button class="btn btn-outline-secondary" type="button" onclick="enableEmailEdit()"><i class="fas fa-pen"></i></button>
                                                    </div>
                                                </div>
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
                                                <label class="small font-weight-bold">System Role</label>
                                                <select class="form-control custom-select" name="u_role" id="u_role" onchange="toggleDriverFields()">
                                                    <option value="EMPLOYEE" <?php if($row->role == 'EMPLOYEE') echo 'selected'; ?>>Employee</option>
                                                    <option value="ADMIN" <?php if($row->role == 'ADMIN') echo 'selected'; ?>>Admin</option>
                                                    <option value="MANAGER" <?php if($row->role == 'MANAGER') echo 'selected'; ?>>Manager</option>
                                                    <option value="DRIVER" <?php if($row->role == 'DRIVER') echo 'selected'; ?>>Driver</option>
                                                </select>
                                            </div>

                                            <div class="form-group">
                                                <label class="small font-weight-bold">Password</label>
                                                <input type="password" class="form-control" name="u_pwd" placeholder="Leave blank to keep current password">
                                                <small class="form-text text-muted">Only enter a value if you wish to change the user's password.</small>
                                            </div>

                                            <!-- Driver Specific Fields -->
                                            <div id="driver-fields" style="display: <?php echo ($row->role == 'DRIVER') ? 'block' : 'none'; ?>;" class="mt-4">
                                                <div class="bg-light p-3 rounded border">
                                                    <h6 class="text-secondary font-weight-bold mb-3 small text-uppercase">Driver Details</h6>
                                                    <div class="form-row">
                                                        <div class="form-group col-md-6">
                                                            <label class="small font-weight-bold">License Number</label>
                                                            <input type="text" class="form-control form-control-sm" name="d_license_no" value="<?php echo $driver_details->license_no ?? ''; ?>" placeholder="Enter License No">
                                                        </div>
                                                        <div class="form-group col-md-6">
                                                            <label class="small font-weight-bold">License Expiry</label>
                                                            <input type="date" class="form-control form-control-sm" name="d_license_expiry" value="<?php echo $driver_details->license_expiry ?? ''; ?>">
                                                        </div>
                                                    </div>
                                                    <div class="form-row">
                                                        <div class="form-group col-md-6">
                                                            <label class="small font-weight-bold">Experience (Years)</label>
                                                            <input type="number" class="form-control form-control-sm" name="d_experience" value="<?php echo $driver_details->experience_years ?? ''; ?>" placeholder="e.g. 5">
                                                        </div>
                                                        <div class="form-group col-md-6">
                                                            <label class="small font-weight-bold">Status</label>
                                                            <select class="form-control form-control-sm custom-select" name="d_status">
                                                                <option value="ACTIVE" <?php if(($driver_details->status ?? '') == 'ACTIVE') echo 'selected'; ?>>Active</option>
                                                                <option value="ON_LEAVE" <?php if(($driver_details->status ?? '') == 'ON_LEAVE') echo 'selected'; ?>>On Leave</option>
                                                                <option value="INACTIVE" <?php if(($driver_details->status ?? '') == 'INACTIVE') echo 'selected'; ?>>Inactive</option>
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
                                            <h6 class="text-uppercase text-muted small font-weight-bold mb-3 border-bottom pb-2">Remarks & Notes</h6>
                                            
                                            <!-- User Remark (Only visible if action=approve and remark exists) -->
                                            <?php if (!empty($user_remark)): ?>
                                                <div class="alert alert-info border-0 shadow-sm mb-4">
                                                    <div class="d-flex align-items-start">
                                                        <i class="fas fa-quote-left fa-2x mr-3 opacity-50"></i>
                                                        <div>
                                                            <h6 class="alert-heading font-weight-bold mb-1">User's Note (from Registration)</h6>
                                                            <p class="mb-0 small"><?php echo nl2br(htmlspecialchars($user_remark)); ?></p>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <!-- Admin Remark Field -->
                                            <div class="form-group mb-0">
                                                <label class="small font-weight-bold">Admin Remarks</label>
                                                <textarea name="admin_remark" class="form-control" rows="3" placeholder="Enter any internal notes or remarks about this user..."></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group text-center mt-4 mb-5">
                                <button type="submit" name="update_user" class="btn btn-success btn-lg px-5 font-weight-bold shadow-sm rounded-pill">
                                    <?php echo (isset($_GET['action']) && $_GET['action'] == 'approve') ? '<i class="fas fa-check-circle mr-2"></i> Approve & Save' : '<i class="fas fa-save mr-2"></i> Update User'; ?>
                                </button>
                            </div>
                        </form>
                    <?php } ?>
                </div>
            </div>

        </div>

    </div>

</div>

<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="vendor/js/sb-admin.min.js"></script>
<script src="vendor/js/swal.js"></script>

<script>
    function enableEmailEdit() {
        swal({
            title: "Are you sure?",
            text: "Changing the email address might affect the user's login credentials.",
            icon: "warning",
            buttons: true,
            dangerMode: true,
        })
        .then((willEdit) => {
            if (willEdit) {
                document.getElementById('u_email').removeAttribute('readonly');
                document.getElementById('u_email').focus();
            }
        });
    }

    function toggleDriverFields() {
        var role = document.getElementById("u_role").value;
        var driverFields = document.getElementById("driver-fields");
        
        if (role === "DRIVER") {
            driverFields.style.display = "block";
        } else {
            driverFields.style.display = "none";
        }
    }
</script>

</body>

</html>
