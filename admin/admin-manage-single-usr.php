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
        if (!empty($u_pwd)) {
             $hashed_pwd = password_hash($u_pwd, PASSWORD_DEFAULT);
             $query = "UPDATE users SET first_name=?, last_name=?, phone=?, address=?, role=?, email=?, password=? WHERE id=?";
             $stmt = $mysqli->prepare($query);
             $stmt->bind_param('sssssssi', $u_fname, $u_lname, $u_phone, $u_addr, $u_role, $u_email, $hashed_pwd, $u_id);
        } else {
             $query = "UPDATE users SET first_name=?, last_name=?, phone=?, address=?, role=?, email=? WHERE id=?";
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
            
            $succ = "User Updated Successfully";
        } else {
            $err = "Update Failed. Try Again Later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<?php include('vendor/inc/head.php'); ?>

<body id="page-top">

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

            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary text-white d-flex align-items-center">
                    <a href="javascript:void(0);" onclick="window.location.replace('admin-view-user.php')" class="btn btn-light btn-sm mr-3 text-primary font-weight-bold"><i class="fas fa-arrow-left"></i> Back</a>
                    <h4 class="mb-0 flex-grow-1 text-center" style="margin-right: 60px;">Edit User Details</h4>
                </div>

                <div class="card-body">
                    <?php
                    $aid = $_GET['u_id'];
                    // Fetch user details
                    $ret = "SELECT * FROM users WHERE id=?";
                    $stmt = $mysqli->prepare($ret);
                    $stmt->bind_param('i', $aid);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    while ($row = $res->fetch_object()) {
                        // Fetch driver details if role is DRIVER
                        $driver_details = null;
                        if ($row->role == 'DRIVER') {
                            $d_stmt = $mysqli->prepare("SELECT * FROM drivers WHERE user_id = ?");
                            $d_stmt->bind_param('i', $aid);
                            $d_stmt->execute();
                            $driver_details = $d_stmt->get_result()->fetch_object();
                            $d_stmt->close();
                        }
                        ?>
                        <form method="POST">
                            <div class="form-group">
                                <label>First Name</label>
                                <input type="text" value="<?php echo $row->first_name; ?>" required class="form-control" name="u_fname">
                            </div>
                            <div class="form-group">
                                <label>Last Name</label>
                                <input type="text" value="<?php echo $row->last_name; ?>" class="form-control" name="u_lname">
                            </div>

                            <div class="form-group">
                                <label>Phone</label>
                                <input type="text" value="<?php echo $row->phone; ?>" class="form-control" name="u_phone">
                            </div>

                            <div class="form-group">
                                <label>Address</label>
                                <input type="text" value="<?php echo $row->address; ?>" class="form-control" name="u_addr">
                            </div>

                            <div class="form-group">
                                <label>Role</label>
                                <select class="form-control" name="u_role" id="u_role" onchange="toggleDriverFields()">
                                    <option value="EMPLOYEE" <?php if($row->role == 'EMPLOYEE') echo 'selected'; ?>>Employee</option>
                                    <option value="ADMIN" <?php if($row->role == 'ADMIN') echo 'selected'; ?>>Admin</option>
                                    <option value="MANAGER" <?php if($row->role == 'MANAGER') echo 'selected'; ?>>Manager</option>
                                    <option value="DRIVER" <?php if($row->role == 'DRIVER') echo 'selected'; ?>>Driver</option>
                                </select>
                            </div>

                            <!-- Driver Specific Fields -->
                            <div id="driver-fields" style="display: <?php echo ($row->role == 'DRIVER') ? 'block' : 'none'; ?>; background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin-top: 10px;">
                                <h5 class="text-secondary border-bottom pb-2">Driver Details</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="font-weight-bold">License Number</label>
                                            <input type="text" class="form-control" name="d_license_no" value="<?php echo $driver_details->license_no ?? ''; ?>" placeholder="Enter License No">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="font-weight-bold">License Expiry Date</label>
                                            <input type="date" class="form-control" name="d_license_expiry" value="<?php echo $driver_details->license_expiry ?? ''; ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="font-weight-bold">Experience (Years)</label>
                                            <input type="number" class="form-control" name="d_experience" value="<?php echo $driver_details->experience_years ?? ''; ?>" placeholder="e.g. 5">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="font-weight-bold">Status</label>
                                            <select class="form-control" name="d_status">
                                                <option value="ACTIVE" <?php if(($driver_details->status ?? '') == 'ACTIVE') echo 'selected'; ?>>Active</option>
                                                <option value="ON_LEAVE" <?php if(($driver_details->status ?? '') == 'ON_LEAVE') echo 'selected'; ?>>On Leave</option>
                                                <option value="INACTIVE" <?php if(($driver_details->status ?? '') == 'INACTIVE') echo 'selected'; ?>>Inactive</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Email</label>
                                <div class="input-group">
                                    <input type="email" value="<?php echo $row->email; ?>" class="form-control" id="u_email" name="u_email" readonly required>
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary" type="button" onclick="enableEmailEdit()">Edit</button>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Password</label>
                                <input type="password" class="form-control" name="u_pwd" placeholder="Leave blank to keep current password">
                            </div>

                            <div class="form-group text-center">
                                <button type="submit" name="update_user" class="btn btn-success px-4">
                                    Update User
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
