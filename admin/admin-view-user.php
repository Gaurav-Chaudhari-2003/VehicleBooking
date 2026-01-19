<?php
session_start();
include('../DATABASE FILE/config.php');
include('../DATABASE FILE/checklogin.php');
check_login();
$aid = $_SESSION['a_id'];
global $mysqli;

// Prevent caching to ensure back button doesn't show stale page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Handle AJAX Actions (Deactivate, Activate, Reject, Revert)
if (isset($_GET['ajax_action'])) {
    $action = $_GET['ajax_action'];
    $id = intval($_GET['id']);
    $response = ['status' => 'error', 'message' => 'Invalid action'];

    try {
        if ($action == 'deactivate') {
            $stmt = $mysqli->prepare("UPDATE users SET is_active = -1 WHERE id = ?");
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                $response = ['status' => 'success', 'message' => 'User Deactivated Successfully'];
            }
            $stmt->close();
        } elseif ($action == 'activate') {
            $stmt = $mysqli->prepare("UPDATE users SET is_active = 1 WHERE id = ?");
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                $response = ['status' => 'success', 'message' => 'User Reactivated Successfully'];
            }
            $stmt->close();
        } elseif ($action == 'reject') {
            // Set is_active to -2 for Rejected
            $stmt = $mysqli->prepare("UPDATE users SET is_active = -2 WHERE id = ?");
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                $response = ['status' => 'success', 'message' => 'User Rejected Successfully'];
            } else {
                $response = ['status' => 'error', 'message' => 'Database error: ' . $stmt->error];
            }
            $stmt->close();
        } elseif ($action == 'revert') {
            // Set is_active to 0 for Pending
            $stmt = $mysqli->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                $response = ['status' => 'success', 'message' => 'User Status Reverted to Pending'];
            } else {
                $response = ['status' => 'error', 'message' => 'Database error: ' . $stmt->error];
            }
            $stmt->close();
        }
    } catch (Exception $e) {
        $response = ['status' => 'error', 'message' => 'Exception: ' . $e->getMessage()];
    }

    echo json_encode($response);
    exit;
}

// Handle AJAX Filter (Table Refresh for Registered Users)
if (isset($_GET['ajax_filter'])) {
    $filter_role = $_GET['role'];
    $show_deactivated = $_GET['show_deactivated'] == 'true';
    $active_status = $show_deactivated ? -1 : 1;
    
    if ($filter_role == 'ALL') {
        $ret = "SELECT * FROM users WHERE is_active = ? ORDER BY id DESC";
        $stmt = $mysqli->prepare($ret);
        $stmt->bind_param('i', $active_status);
    } else {
        $ret = "SELECT * FROM users WHERE role = ? AND is_active = ? ORDER BY id DESC";
        $stmt = $mysqli->prepare($ret);
        $stmt->bind_param('si', $filter_role, $active_status);
    }
    
    $stmt->execute();
    $res = $stmt->get_result();
    $cnt = 1;
    
    if ($res->num_rows > 0) {
        while ($row = $res->fetch_object()) {
            ?>
            <tr id="user-row-<?php echo $row->id; ?>">
                <td><?php echo $cnt++; ?></td>
                <td><?php echo $row->first_name . " " . $row->last_name; ?></td>
                <td><?php echo $row->phone; ?></td>
                <td><?php echo $row->address; ?></td>
                <td><?php echo $row->email; ?></td>
                <td><span class="badge badge-info"><?php echo $row->role; ?></span></td>
                <td>
                    <?php if ($show_deactivated) { ?>
                        <button class="badge badge-success border-0" onclick="performAction('activate', <?php echo $row->id; ?>)"><i class="fas fa-check"></i> Reactivate</button>
                    <?php } else { ?>
                        <a href="admin-manage-single-usr.php?u_id=<?php echo $row->id; ?>" class="badge badge-success"><i class="fa fa-edit"></i> Update</a>
                        <button class="badge badge-danger border-0" onclick="performAction('deactivate', <?php echo $row->id; ?>)"><i class="fa fa-trash"></i> Deactivate</button>
                    <?php } ?>
                </td>
            </tr>
            <?php
        }
    } else {
        echo '<tr><td colspan="7" class="text-center text-danger font-weight-bold">' . ($show_deactivated ? 'No deactivated users found.' : 'No active users found for the selected role.') . '</td></tr>';
    }
    exit;
}

// Handle AJAX Filter (Table Refresh for Pending/Rejected Users)
if (isset($_GET['ajax_pending_filter'])) {
    $show_rejected = $_GET['show_rejected'] == 'true';
    $status = $show_rejected ? -2 : 0; // -2 for Rejected, 0 for Pending

    $ret = "SELECT * FROM users WHERE is_active = ? ORDER BY id DESC";
    $stmt = $mysqli->prepare($ret);
    $stmt->bind_param('i', $status);
    $stmt->execute();
    $res = $stmt->get_result();
    $cnt = 1;

    if ($res->num_rows > 0) {
        while ($row = $res->fetch_object()) {
            ?>
            <tr id="pending-row-<?php echo $row->id; ?>">
                <td><?php echo $cnt++; ?></td>
                <td><?php echo $row->first_name . " " . $row->last_name; ?></td>
                <td><?php echo $row->phone; ?></td>
                <td><?php echo $row->address; ?></td>
                <td><?php echo $row->email; ?></td>
                <td>
                    <?php if ($show_rejected) { ?>
                        <button class="badge badge-warning border-0" onclick="performAction('revert', <?php echo $row->id; ?>)"><i class="fas fa-undo"></i> Revert to Pending</button>
                    <?php } else { ?>
                        <a href="admin-manage-single-usr.php?u_id=<?php echo $row->id; ?>&action=approve" class="badge badge-success border-0"><i class="fas fa-check"></i> Approve</a>
                        <button class="badge badge-danger border-0" onclick="performAction('reject', <?php echo $row->id; ?>)">Reject</button>
                    <?php } ?>
                </td>
            </tr>
            <?php
        }
    } else {
        echo '<tr><td colspan="6" class="text-center text-muted">' . ($show_rejected ? 'No rejected users found.' : 'No pending approvals found.') . '</td></tr>';
    }
    exit;
}

// Handle Add User (Standard POST)
if (isset($_POST['add_user'])) {
    $u_fname = $_POST['u_fname'];
    $u_lname = $_POST['u_lname'];
    $u_phone = $_POST['u_phone'];
    $u_addr = $_POST['u_addr'];
    $u_email = $_POST['u_email'];
    $u_pwd = $_POST['u_pwd'];
    
    $query = "INSERT INTO users (first_name, last_name, phone, address, email, password, role, is_active) VALUES (?, ?, ?, ?, ?, ?, 'EMPLOYEE', 1)";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('ssssss', $u_fname, $u_lname, $u_phone, $u_addr, $u_email, $u_pwd);
    
    if ($stmt->execute()) {
        $succ = "User Added Successfully";
    } else {
        $err = "Error! Please Try Again Later";
    }
    $stmt->close();
}

// Initial Filter Logic (for first page load)
$filter_role = isset($_GET['role']) ? $_GET['role'] : 'ALL';
$show_deactivated = isset($_GET['show_deactivated']) && $_GET['show_deactivated'] == 'true';
$show_rejected = isset($_GET['show_rejected']) && $_GET['show_rejected'] == 'true';
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
                    setTimeout(function() {
                        swal("Success!", "<?php echo $succ; ?>", "success");
                    }, 100);
                </script>
            <?php } ?>

            <?php if (isset($err)) { ?>
                <script>
                    setTimeout(function() {
                        swal("Failed!", "<?php echo $err; ?>", "error");
                    }, 100);
                </script>
            <?php } ?>


            <div class="card mb-3">
                <div class="card-header d-flex align-items-center">
                    <a href="javascript:void(0);" onclick="window.location.replace('admin-dashboard.php')" class="btn btn-light btn-sm mr-3 text-primary font-weight-bold"><i class="fas fa-arrow-left"></i> Back</a>
                    <div class="flex-grow-1 text-center" style="margin-right: 60px;">
                        <i class="fas fa-user-plus"></i> Add New User
                    </div>
                </div>
                <div class="card-body text-center">
                    <a href="admin-add-user.php" class="btn btn-success">Add New User</a>
                </div>
            </div>

            <!-- Pending Approvals -->
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span id="pendingTableTitle"><i class="fas fa-user-clock"></i> Pending User Approvals</span>
                    
                    <!-- Toggle Switch for Rejected Users -->
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="rejectedSwitch" value="true" onchange="applyPendingFilters()">
                        <label class="custom-control-label font-weight-bold" for="rejectedSwitch">Show Rejected</label>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-striped" width="100%">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Contact</th>
                                <th>Address</th>
                                <th>Email</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody id="pendingTableBody">
                            <?php
                            // Initial load: Show Pending (0)
                            $ret = "SELECT * FROM users WHERE is_active = 0 ORDER BY id DESC";
                            $stmt = $mysqli->prepare($ret);
                            $stmt->execute();
                            $res = $stmt->get_result();
                            $cnt = 1;
                            if ($res->num_rows > 0) {
                                while ($row = $res->fetch_object()) {
                                    ?>
                                    <tr id="pending-row-<?php echo $row->id; ?>">
                                        <td><?php echo $cnt++; ?></td>
                                        <td><?php echo $row->first_name . " " . $row->last_name; ?></td>
                                        <td><?php echo $row->phone; ?></td>
                                        <td><?php echo $row->address; ?></td>
                                        <td><?php echo $row->email; ?></td>
                                        <td>
                                            <a href="admin-manage-single-usr.php?u_id=<?php echo $row->id; ?>&action=approve" class="badge badge-success border-0"><i class="fas fa-check"></i> Approve</a>
                                            <button class="badge badge-danger border-0" onclick="performAction('reject', <?php echo $row->id; ?>)">Reject</button>
                                        </td>
                                    </tr>
                                <?php } 
                            } else { ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No pending approvals found.</td>
                                </tr>
                            <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Registered Users -->
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span id="tableTitle"><i class="fas fa-users"></i> <?php echo $show_deactivated ? 'Deactivated Users' : 'Registered Users'; ?></span>
                    <div class="form-inline mx-auto">
                        <label class="mr-2 font-weight-bold">Filter by Role:</label>
                        <select id="roleFilter" class="form-control form-control-sm mr-3" onchange="applyFilters()">
                            <option value="ALL" <?php if($filter_role == 'ALL') echo 'selected'; ?>>All Roles</option>
                            <option value="EMPLOYEE" <?php if($filter_role == 'EMPLOYEE') echo 'selected'; ?>>Employee</option>
                            <option value="DRIVER" <?php if($filter_role == 'DRIVER') echo 'selected'; ?>>Driver</option>
                            <option value="MANAGER" <?php if($filter_role == 'MANAGER') echo 'selected'; ?>>Manager</option>
                            <option value="ADMIN" <?php if($filter_role == 'ADMIN') echo 'selected'; ?>>Admin</option>
                        </select>
                        
                        <!-- Toggle Switch for Deactivated Users -->
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="deactivatedSwitch" value="true" <?php if($show_deactivated) echo 'checked'; ?> onchange="applyFilters()">
                            <label class="custom-control-label font-weight-bold" for="deactivatedSwitch">Show Deactivated</label>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-striped" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Contact</th>
                                <th>Address</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            $active_status = $show_deactivated ? -1 : 1;
                            
                            if ($filter_role == 'ALL') {
                                $ret = "SELECT * FROM users WHERE is_active = ? ORDER BY id DESC";
                                $stmt = $mysqli->prepare($ret);
                                $stmt->bind_param('i', $active_status);
                            } else {
                                $ret = "SELECT * FROM users WHERE role = ? AND is_active = ? ORDER BY id DESC";
                                $stmt = $mysqli->prepare($ret);
                                $stmt->bind_param('si', $filter_role, $active_status);
                            }
                            
                            $stmt->execute();
                            $res = $stmt->get_result();
                            $cnt = 1;
                            
                            if ($res->num_rows > 0) {
                                while ($row = $res->fetch_object()) {
                                    ?>
                                    <tr id="user-row-<?php echo $row->id; ?>">
                                        <td><?php echo $cnt++; ?></td>
                                        <td><?php echo $row->first_name . " " . $row->last_name; ?></td>
                                        <td><?php echo $row->phone; ?></td>
                                        <td><?php echo $row->address; ?></td>
                                        <td><?php echo $row->email; ?></td>
                                        <td><span class="badge badge-info"><?php echo $row->role; ?></span></td>
                                        <td>
                                            <?php if ($show_deactivated) { ?>
                                                <button class="badge badge-success border-0" onclick="performAction('activate', <?php echo $row->id; ?>)"><i class="fas fa-check"></i> Reactivate</button>
                                            <?php } else { ?>
                                                <a href="admin-manage-single-usr.php?u_id=<?php echo $row->id; ?>" class="badge badge-success"><i class="fa fa-edit"></i> Update</a>
                                                <button class="badge badge-danger border-0" onclick="performAction('deactivate', <?php echo $row->id; ?>)"><i class="fa fa-trash"></i> Deactivate</button>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                <?php } 
                            } else { ?>
                                <tr>
                                    <td colspan="7" class="text-center text-danger font-weight-bold">
                                        <?php echo $show_deactivated ? 'No deactivated users found.' : 'No active users found for the selected role.'; ?>
                                    </td>
                                </tr>
                            <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer small text-muted">
                    <?php
                    date_default_timezone_set("Asia/Kolkata");
                    echo "Generated : " . date("h:i:sa");
                    ?>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- JS Scripts -->
<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="vendor/datatables/jquery.dataTables.js"></script>
<script src="vendor/datatables/dataTables.bootstrap4.js"></script>
<script src="vendor/js/sb-admin.min.js"></script>
<script src="vendor/js/demo/datatables-demo.js"></script>
<script src="vendor/js/swal.js"></script>

<script>
    function applyFilters() {
        var role = $('#roleFilter').val();
        var showDeactivated = $('#deactivatedSwitch').is(':checked');
        
        // Update header title
        var title = showDeactivated ? 'Deactivated Users' : 'Registered Users';
        $('#tableTitle').html('<i class="fas fa-users"></i> ' + title);

        $.ajax({
            url: 'admin-view-user.php',
            type: 'GET',
            data: { 
                ajax_filter: true, 
                role: role, 
                show_deactivated: showDeactivated 
            },
            success: function(response) {
                $('#dataTable tbody').html(response);
            },
            error: function() {
                swal("Error!", "Failed to fetch data.", "error");
            }
        });
    }

    function applyPendingFilters() {
        var showRejected = $('#rejectedSwitch').is(':checked');
        
        // Update header title
        var title = showRejected ? 'Rejected Users' : 'Pending User Approvals';
        $('#pendingTableTitle').html('<i class="fas fa-user-clock"></i> ' + title);

        $.ajax({
            url: 'admin-view-user.php',
            type: 'GET',
            data: { 
                ajax_pending_filter: true, 
                show_rejected: showRejected 
            },
            success: function(response) {
                $('#pendingTableBody').html(response);
            },
            error: function() {
                swal("Error!", "Failed to fetch data.", "error");
            }
        });
    }

    function performAction(action, id) {
        let actionText = "";
        if (action === 'deactivate') actionText = "deactivate this user";
        else if (action === 'activate') actionText = "reactivate this user";
        else if (action === 'reject') actionText = "reject this user";
        else if (action === 'revert') actionText = "revert this user to pending";

        swal({
            title: "Are you sure?",
            text: "Do you really want to " + actionText + "?",
            icon: "warning",
            buttons: true,
            dangerMode: true,
        })
        .then((willDo) => {
            if (willDo) {
                $.ajax({
                    url: 'admin-view-user.php',
                    type: 'GET',
                    data: { ajax_action: action, id: id },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            swal("Success!", response.message, "success");
                            // Remove the row from the table
                            if (action === 'reject' || action === 'revert') {
                                $('#pending-row-' + id).fadeOut(500, function() { $(this).remove(); });
                            } else {
                                $('#user-row-' + id).fadeOut(500, function() { $(this).remove(); });
                            }
                        } else {
                            swal("Error!", response.message, "error");
                        }
                    },
                    error: function(xhr, status, error) {
                        // Improved error logging
                        console.error("AJAX Error:", status, error);
                        console.log("Response:", xhr.responseText);
                        swal("Error!", "Something went wrong. Check console for details.", "error");
                    }
                });
            }
        });
    }
</script>

</body>
</html>
