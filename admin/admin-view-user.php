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
            // Determine role badge color
            $roleBadgeClass = 'bg-secondary'; // Default
            if ($row->role == 'ADMIN') $roleBadgeClass = 'bg-danger';
            elseif ($row->role == 'MANAGER') $roleBadgeClass = 'bg-primary';
            elseif ($row->role == 'DRIVER') $roleBadgeClass = 'bg-success';
            elseif ($row->role == 'EMPLOYEE') $roleBadgeClass = 'bg-info';
            ?>
            <tr id="user-row-<?php echo $row->id; ?>">
                <td><?php echo $cnt++; ?></td>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="bg-light rounded-circle p-2 me-2"><i class="fas fa-user text-secondary"></i></div>
                        <div>
                            <div class="fw-bold"><?php echo $row->first_name . " " . $row->last_name; ?></div>
                            <small class="text-muted"><?php echo $row->email; ?></small>
                        </div>
                    </div>
                </td>
                <td><?php echo $row->phone; ?></td>
                <td><?php echo $row->address; ?></td>
                <td><span class="badge <?php echo $roleBadgeClass; ?> text-white"><?php echo $row->role; ?></span></td>
                <td>
                    <?php if ($show_deactivated) { ?>
                        <button class="btn btn-sm btn-success rounded-pill px-3" onclick="performAction('activate', <?php echo $row->id; ?>)"><i class="fas fa-check me-1"></i> Reactivate</button>
                    <?php } else { ?>
                        <a href="admin-manage-single-usr.php?u_id=<?php echo $row->id; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 me-1"><i class="fa fa-edit"></i> Edit</a>
                        <!-- Deactivate button removed from here as requested -->
                    <?php } ?>
                </td>
            </tr>
            <?php
        }
    } else {
        echo '<tr><td colspan="6" class="text-center text-muted py-4">' . ($show_deactivated ? 'No deactivated users found.' : 'No active users found for the selected role.') . '</td></tr>';
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
                <td>
                    <div class="fw-bold"><?php echo $row->first_name . " " . $row->last_name; ?></div>
                    <small class="text-muted"><?php echo $row->email; ?></small>
                </td>
                <td><?php echo $row->phone; ?></td>
                <td><?php echo $row->address; ?></td>
                <td>
                    <?php if ($show_rejected) { ?>
                        <button class="btn btn-sm btn-warning rounded-pill px-3" onclick="performAction('revert', <?php echo $row->id; ?>)"><i class="fas fa-undo me-1"></i> Revert</button>
                    <?php } else { ?>
                        <a href="admin-manage-single-usr.php?u_id=<?php echo $row->id; ?>&action=approve" class="btn btn-sm btn-success rounded-pill px-3 me-1"><i class="fas fa-check me-1"></i> Approve</a>
                        <button class="btn btn-sm btn-danger rounded-pill px-3" onclick="performAction('reject', <?php echo $row->id; ?>)">Reject</button>
                    <?php } ?>
                </td>
            </tr>
            <?php
        }
    } else {
        echo '<tr><td colspan="5" class="text-center text-muted py-4">' . ($show_rejected ? 'No rejected users found.' : 'No pending approvals found.') . '</td></tr>';
    }
    exit;
}

// Initial Filter Logic (for first page load)
$filter_role = isset($_GET['role']) ? $_GET['role'] : 'ALL';
$show_deactivated = isset($_GET['show_deactivated']) && $_GET['show_deactivated'] == 'true';
$show_rejected = isset($_GET['show_rejected']) && $_GET['show_rejected'] == 'true';

// Check for pending approvals count
$pending_count_query = "SELECT COUNT(*) FROM users WHERE is_active = 0";
$pending_stmt = $mysqli->prepare($pending_count_query);
$pending_stmt->execute();
$pending_stmt->bind_result($pending_count);
$pending_stmt->fetch();
$pending_stmt->close();

// Check for rejected users count
$rejected_count_query = "SELECT COUNT(*) FROM users WHERE is_active = -2";
$rejected_stmt = $mysqli->prepare($rejected_count_query);
$rejected_stmt->execute();
$rejected_stmt->bind_result($rejected_count);
$rejected_stmt->fetch();
$rejected_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Users - Vehicle Booking System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Include Global Theme -->
    <?php include("../vendor/inc/theme-config.php"); ?>
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <style>
        body {
            background-color: #fff;
        }
        
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar styles are now in sidebar.php */
        
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
        
        .table thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #eee;
            color: #555;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
        }
        
        .btn-add-user {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 10px 25px;
            font-weight: 600;
            box-shadow: 0 4px 10px rgba(0, 77, 64, 0.2);
            transition: all 0.3s;
        }
        
        .btn-add-user:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 77, 64, 0.3);
            color: white;
        }
        
        .filter-select {
            border-radius: 20px;
            border: 1px solid #ddd;
            padding: 5px 15px;
            font-size: 0.9rem;
            margin: 0 30px;
        }
    </style>
</head>

<body id="page-top">

<div class="dashboard-container">
    <!-- Sidebar -->
    <?php include("vendor/inc/sidebar.php"); ?>
    
    <!-- Main Content -->
    <div class="main-content">

        <!-- Back Button & Title -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="d-inline-block align-middle fw-bold text-dark mb-0">User Management</h3>
            </div>
            <a href="admin-add-user.php" class="btn-add-user">
                <i class="fas fa-user-plus me-2"></i> Add New User
            </a>
        </div>

        <!-- Pending Approvals (Hidden if 0) -->
        <div class="card" id="pendingApprovalsCard" style="<?php echo ($pending_count == 0) ? 'display: none;' : ''; ?>">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-warning fw-bold" id="pendingTableTitle"><i class="fas fa-user-clock me-2"></i> Pending Approvals</h5>
                
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="rejectedSwitch" onchange="applyPendingFilters()">
                    <label class="form-check-label small fw-bold text-muted" for="rejectedSwitch">Show Rejected</label>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                        <tr>
                            <th class="ps-4">#</th>
                            <th>User Details</th>
                            <th>Contact</th>
                            <th>Address</th>
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
                                    <td class="ps-4"><?php echo $cnt++; ?></td>
                                    <td>
                                        <div class="fw-bold"><?php echo $row->first_name . " " . $row->last_name; ?></div>
                                        <small class="text-muted"><?php echo $row->email; ?></small>
                                    </td>
                                    <td><?php echo $row->phone; ?></td>
                                    <td><?php echo $row->address; ?></td>
                                    <td>
                                        <a href="admin-manage-single-usr.php?u_id=<?php echo $row->id; ?>&action=approve" class="btn btn-sm btn-success rounded-pill px-3 me-1"><i class="fas fa-check me-1"></i> Approve</a>
                                        <button class="btn btn-sm btn-danger rounded-pill px-3" onclick="performAction('reject', <?php echo $row->id; ?>)">Reject</button>
                                    </td>
                                </tr>
                            <?php } 
                        } else { ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No pending approvals found.</td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Show Rejected Users Button (Only if Pending=0 and Rejected>0) -->
        <?php if ($pending_count == 0 && $rejected_count > 0): ?>
        <div class="text-end mb-3">
            <button class="btn btn-outline-danger rounded-pill btn-sm" onclick="$('#pendingApprovalsCard').show(); $('#rejectedSwitch').prop('checked', true).trigger('change'); $(this).hide();">
                <i class="fas fa-user-times me-1"></i> View Rejected Users (<?php echo $rejected_count; ?>)
            </button>
        </div>
        <?php endif; ?>

        <!-- Registered Users -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                <h5 class="mb-0 text-primary fw-bold" id="tableTitle"><i class="fas fa-users me-2"></i> Registered Users</h5>
                
                <div class="d-flex align-items-center gap-3">
                    <select id="roleFilter" class="form-select form-select-sm filter-select" onchange="applyFilters()" style="width: 150px;">
                        <option value="ALL" <?php if($filter_role == 'ALL') echo 'selected'; ?>>All Roles</option>
                        <option value="EMPLOYEE" <?php if($filter_role == 'EMPLOYEE') echo 'selected'; ?>>Employee</option>
                        <option value="DRIVER" <?php if($filter_role == 'DRIVER') echo 'selected'; ?>>Driver</option>
                        <option value="MANAGER" <?php if($filter_role == 'MANAGER') echo 'selected'; ?>>Manager</option>
                        <option value="ADMIN" <?php if($filter_role == 'ADMIN') echo 'selected'; ?>>Admin</option>
                    </select>
                    
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="deactivatedSwitch" <?php if($show_deactivated) echo 'checked'; ?> onchange="applyFilters()">
                        <label class="form-check-label small fw-bold text-muted" for="deactivatedSwitch">Show Deactivated</label>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle" id="dataTable">
                        <thead>
                        <tr>
                            <th class="ps-4">#</th>
                            <th>User Details</th>
                            <th>Contact</th>
                            <th>Address</th>
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
                                // Determine role badge color
                                $roleBadgeClass = 'bg-secondary'; // Default
                                if ($row->role == 'ADMIN') $roleBadgeClass = 'bg-danger';
                                elseif ($row->role == 'MANAGER') $roleBadgeClass = 'bg-primary';
                                elseif ($row->role == 'DRIVER') $roleBadgeClass = 'bg-success';
                                elseif ($row->role == 'EMPLOYEE') $roleBadgeClass = 'bg-info';
                                ?>
                                <tr id="user-row-<?php echo $row->id; ?>">
                                    <td class="ps-4"><?php echo $cnt++; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div>
                                                <div class="fw-bold"><?php echo $row->first_name . " " . $row->last_name; ?></div>
                                                <small class="text-muted"><?php echo $row->email; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo $row->phone; ?></td>
                                    <td><?php echo $row->address; ?></td>
                                    <td><span class="badge <?php echo $roleBadgeClass; ?> text-white"><?php echo $row->role; ?></span></td>
                                    <td>
                                        <?php if ($show_deactivated) { ?>
                                            <button class="btn btn-sm btn-success rounded-pill px-3" onclick="performAction('activate', <?php echo $row->id; ?>)"><i class="fas fa-check me-1"></i> Reactivate</button>
                                        <?php } else { ?>
                                            <a href="admin-manage-single-usr.php?u_id=<?php echo $row->id; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 me-1"><i class="fa fa-edit"></i> Edit</a>
                                            <!-- Deactivate button removed from here as requested -->
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php } 
                        } else { ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <?php echo $show_deactivated ? 'No deactivated users found.' : 'No active users found for the selected role.'; ?>
                                </td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="vendor/js/swal.js"></script>

<script>
    $(document).ready(function() {
        $('#dataTable').DataTable({
            "dom": 'rtip',
            "pageLength": 10,
            "language": {
                "emptyTable": "No users found"
            }
        });
    });

    function applyFilters() {
        var role = $('#roleFilter').val();
        var showDeactivated = $('#deactivatedSwitch').is(':checked');
        
        var title = showDeactivated ? 'Deactivated Users' : 'Registered Users';
        $('#tableTitle').html('<i class="fas fa-users me-2"></i> ' + title);

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
        
        var title = showRejected ? 'Rejected Users' : 'Pending User Approvals';
        $('#pendingTableTitle').html('<i class="fas fa-user-clock me-2"></i> ' + title);

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
                        console.error("AJAX Error:", status, error);
                        swal("Error!", "Something went wrong.", "error");
                    }
                });
            }
        });
    }
</script>

</body>
</html>
