<?php
session_start();
include('../DATABASE FILE/config.php');
include('../DATABASE FILE/checklogin.php');
check_login();
$aid = $_SESSION['a_id'];
global $mysqli;

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Auto-retire expired vendor vehicles
$today = date('Y-m-d');
$expire_query = "UPDATE vehicles v 
                 JOIN vehicle_contracts vc ON v.id = vc.vehicle_id 
                 SET v.status = 'RETIRED' 
                 WHERE v.ownership_type = 'VENDOR' 
                   AND v.status != 'RETIRED' 
                   AND vc.contract_end_date < ?";
$expire_stmt = $mysqli->prepare($expire_query);
$expire_stmt->bind_param('s', $today);
$expire_stmt->execute();
$expire_stmt->close();


// Handle AJAX Actions (Retire, Restore)
if (isset($_GET['ajax_action'])) {
    $action = $_GET['ajax_action'];
    $id = intval($_GET['id']);
    $response = ['status' => 'error', 'message' => 'Invalid action'];

    if ($action == 'retire') {
        $stmt = $mysqli->prepare("UPDATE vehicles SET status = 'RETIRED' WHERE id = ?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            $response = ['status' => 'success', 'message' => 'Vehicle Retired Successfully'];
        }
        $stmt->close();
    } elseif ($action == 'restore') {
        $stmt = $mysqli->prepare("UPDATE vehicles SET status = 'AVAILABLE' WHERE id = ?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            $response = ['status' => 'success', 'message' => 'Vehicle Restored Successfully'];
        }
        $stmt->close();
    }

    echo json_encode($response);
    exit;
}

// Handle AJAX Filter (Table Refresh)
if (isset($_GET['ajax_filter'])) {
    $show_retired = $_GET['show_retired'] == 'true';
    
    if ($show_retired) {
        $ret = "SELECT v.*, vc.contract_end_date 
                FROM vehicles v 
                LEFT JOIN vehicle_contracts vc ON v.id = vc.vehicle_id 
                WHERE v.status = 'RETIRED' 
                ORDER BY v.id DESC";
    } else {
        $ret = "SELECT v.*, vc.contract_end_date 
                FROM vehicles v 
                LEFT JOIN vehicle_contracts vc ON v.id = vc.vehicle_id 
                WHERE v.status != 'RETIRED' 
                ORDER BY v.id DESC";
    }
    
    $stmt = $mysqli->prepare($ret);
    $stmt->execute();
    $res = $stmt->get_result();
    $cnt = 1;
    
    if ($res->num_rows > 0) {
        while ($row = $res->fetch_object()) {
            $img = !empty($row->image) ? "../vendor/img/" . $row->image : "../vendor/img/placeholder.png";
            $ownership_badge = ($row->ownership_type == 'VENDOR')
                ? '<span class="badge bg-warning text-dark">Vendor</span>'
                : '<span class="badge bg-primary">Dept</span>';

            $contract_status = "";
            if ($row->ownership_type == 'VENDOR' && !empty($row->contract_end_date)) {
                $today = date('Y-m-d');
                if ($row->contract_end_date < $today) {
                    $contract_status = '<div class="mt-1"><small class="text-danger fw-bold"><i class="fas fa-exclamation-circle me-1"></i> Expired: ' . $row->contract_end_date . '</small></div>';
                } else {
                    $contract_status = '<div class="mt-1"><small class="text-muted">Ends: ' . $row->contract_end_date . '</small></div>';
                }
            }
            ?>
            <tr id="vehicle-row-<?php echo $row->id; ?>">
                <td class="ps-4"><?php echo $cnt; ?></td>
                <td class="p-2" style="width: 100px;">
                    <img src="<?php echo $img; ?>" alt="Vehicle" class="rounded shadow-sm" style="width: 80px; height: 60px; object-fit: cover;">
                </td>
                <td>
                    <div class="fw-bold text-dark"><?php echo $row->name; ?></div>
                    <div class="mt-1"><?php echo $ownership_badge; ?></div>
                    <?php echo $contract_status; ?>
                </td>
                <td class="font-monospace"><?php echo $row->reg_no; ?></td>
                <td><?php echo $row->category; ?></td>
                <td><?php echo $row->fuel_type; ?></td>
                <td><?php echo $row->capacity; ?> Seats</td>
                <td>
                    <?php
                    if ($row->status == "AVAILABLE") {
                        echo '<span class="badge bg-success">Available</span>';
                    } elseif ($row->status == "MAINTENANCE") {
                        echo '<span class="badge bg-warning text-dark">Maintenance</span>';
                    } elseif ($row->status == "RETIRED") {
                        echo '<span class="badge bg-secondary">Retired</span>';
                    } else {
                        echo '<span class="badge bg-danger">Booked</span>';
                    }
                    ?>
                </td>
                <td>
                    <?php if ($show_retired) { 
                        if ($row->ownership_type == 'VENDOR') {
                            echo '<a href="admin-manage-single-vehicle.php?v_id=' . $row->id . '" class="btn btn-sm btn-success rounded-pill px-3"><i class="fas fa-recycle me-1"></i> Restore</a>';
                        } else {
                            echo '<button class="btn btn-sm btn-success rounded-pill px-3" onclick="performAction(\'restore\', ' . $row->id . ')"><i class="fas fa-recycle me-1"></i> Restore</button>';
                        }
                    } else { ?>
                        <a href="admin-manage-single-vehicle.php?v_id=<?php echo $row->id; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 me-1"><i class="fa fa-edit"></i> Edit</a>
                        <button class="btn btn-sm btn-outline-danger rounded-pill px-3" onclick="performAction('retire', <?php echo $row->id; ?>)"><i class="fa fa-trash"></i></button>
                    <?php } ?>
                </td>
            </tr>
            <?php
            $cnt++;
        }
    } else {
        echo '<tr><td colspan="9" class="text-center text-muted py-4">' . ($show_retired ? 'No retired vehicles found.' : 'No active vehicles found.') . '</td></tr>';
    }
    exit;
}

$show_retired = isset($_GET['show_retired']) && $_GET['show_retired'] == 'true';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Vehicles - Vehicle Booking System</title>
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
        
        .btn-add-vehicle {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 10px 25px;
            font-weight: 600;
            box-shadow: 0 4px 10px rgba(0, 77, 64, 0.2);
            transition: all 0.3s;
        }
        
        .btn-add-vehicle:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 77, 64, 0.3);
            color: white;
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
                <h3 class="d-inline-block align-middle fw-bold text-dark mb-0">Vehicle Management</h3>
            </div>
            <a href="admin-add-vehicle.php" class="btn-add-vehicle">
                <i class="fas fa-bus me-2"></i> Add New Vehicle
            </a>
        </div>

        <!-- Vehicle Fleet Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-primary fw-bold" id="tableTitle"><i class="fas fa-bus me-2"></i> Vehicle Fleet</h5>
                
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="retiredSwitch" <?php if($show_retired) echo 'checked'; ?> onchange="applyFilters()">
                    <label class="form-check-label small fw-bold text-muted" for="retiredSwitch">Show Retired</label>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle" id="dataTable">
                        <thead>
                        <tr>
                            <th class="ps-4">#</th>
                            <th style="width: 100px;">Image</th>
                            <th>Name & Ownership</th>
                            <th>Reg No</th>
                            <th>Category</th>
                            <th>Fuel</th>
                            <th>Capacity</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        if ($show_retired) {
                            $ret = "SELECT v.*, vc.contract_end_date 
                                    FROM vehicles v 
                                    LEFT JOIN vehicle_contracts vc ON v.id = vc.vehicle_id 
                                    WHERE v.status = 'RETIRED' 
                                    ORDER BY v.id DESC";
                        } else {
                            $ret = "SELECT v.*, vc.contract_end_date 
                                    FROM vehicles v 
                                    LEFT JOIN vehicle_contracts vc ON v.id = vc.vehicle_id 
                                    WHERE v.status != 'RETIRED' 
                                    ORDER BY v.id DESC";
                        }
                        
                        $stmt = $mysqli->prepare($ret);
                        $stmt->execute();
                        $res = $stmt->get_result();
                        $cnt = 1;
                        
                        if ($res->num_rows > 0) {
                            while ($row = $res->fetch_object()) {
                                $img = !empty($row->image) ? "../vendor/img/" . $row->image : "../vendor/img/placeholder.png";
                                $ownership_badge = ($row->ownership_type == 'VENDOR')
                                    ? '<span class="badge bg-warning text-dark">Vendor</span>'
                                    : '<span class="badge bg-primary">Dept</span>';

                                $contract_status = "";
                                if ($row->ownership_type == 'VENDOR' && !empty($row->contract_end_date)) {
                                    $today = date('Y-m-d');
                                    if ($row->contract_end_date < $today) {
                                        $contract_status = '<div class="mt-1"><small class="text-danger fw-bold"><i class="fas fa-exclamation-circle me-1"></i> Expired: ' . $row->contract_end_date . '</small></div>';
                                    } else {
                                        $contract_status = '<div class="mt-1"><small class="text-muted">Ends: ' . $row->contract_end_date . '</small></div>';
                                    }
                                }
                                ?>
                                <tr id="vehicle-row-<?php echo $row->id; ?>">
                                    <td class="ps-4"><?php echo $cnt; ?></td>
                                    <td class="p-2">
                                        <img src="<?php echo $img; ?>" alt="Vehicle" class="rounded shadow-sm" style="width: 80px; height: 60px; object-fit: cover;">
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?php echo $row->name; ?></div>
                                        <div class="mt-1"><?php echo $ownership_badge; ?></div>
                                        <?php echo $contract_status; ?>
                                    </td>
                                    <td class="font-monospace"><?php echo $row->reg_no; ?></td>
                                    <td><?php echo $row->category; ?></td>
                                    <td><?php echo $row->fuel_type; ?></td>
                                    <td><?php echo $row->capacity; ?> Seats</td>
                                    <td>
                                        <?php
                                        if ($row->status == "AVAILABLE") {
                                            echo '<span class="badge bg-success">Available</span>';
                                        } elseif ($row->status == "MAINTENANCE") {
                                            echo '<span class="badge bg-warning text-dark">Maintenance</span>';
                                        } elseif ($row->status == "RETIRED") {
                                            echo '<span class="badge bg-secondary">Retired</span>';
                                        } else {
                                            echo '<span class="badge bg-danger">Booked</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($show_retired) { 
                                            if ($row->ownership_type == 'VENDOR') {
                                                echo '<a href="admin-manage-single-vehicle.php?v_id=' . $row->id . '" class="btn btn-sm btn-success rounded-pill px-3"><i class="fas fa-recycle me-1"></i> Restore</a>';
                                            } else {
                                                echo '<button class="btn btn-sm btn-success rounded-pill px-3" onclick="performAction(\'restore\', ' . $row->id . ')"><i class="fas fa-recycle me-1"></i> Restore</button>';
                                            }
                                        } else { ?>
                                            <a href="admin-manage-single-vehicle.php?v_id=<?php echo $row->id; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 me-1"><i class="fa fa-edit"></i> Edit</a>
                                            <button class="btn btn-sm btn-outline-danger rounded-pill px-3" onclick="performAction('retire', <?php echo $row->id; ?>)"><i class="fa fa-trash"></i></button>
                                        <?php } ?>
                                    </td>
                                </tr>
                                <?php
                                $cnt++;
                            }
                        } else {
                            echo '<tr><td colspan="9" class="text-center text-muted py-4">' . ($show_retired ? 'No retired vehicles found.' : 'No active vehicles found.') . '</td></tr>';
                        }
                        ?>
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
    $(document).ready(function () {
        $('#dataTable').DataTable({
            "dom": 'rtip',
            "pageLength": 10,
            "language": {
                "emptyTable": "No vehicles found"
            }
        });
    });

    function applyFilters() {
        var showRetired = $('#retiredSwitch').is(':checked');
        
        var title = showRetired ? 'Retired Vehicles' : 'Vehicle Fleet';
        $('#tableTitle').html('<i class="fas fa-bus me-2"></i> ' + title);

        $.ajax({
            url: 'admin-view-vehicle.php',
            type: 'GET',
            data: { 
                ajax_filter: true, 
                show_retired: showRetired 
            },
            success: function(response) {
                $('#dataTable tbody').html(response);
            },
            error: function() {
                swal("Error!", "Failed to fetch data.", "error");
            }
        });
    }

    function performAction(action, id) {
        let actionText = "";
        if (action === 'retire') actionText = "retire this vehicle";
        else if (action === 'restore') actionText = "restore this vehicle to service";

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
                    url: 'admin-view-vehicle.php',
                    type: 'GET',
                    data: { ajax_action: action, id: id },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            swal("Success!", response.message, "success");
                            $('#vehicle-row-' + id).fadeOut(500, function() { $(this).remove(); });
                        } else {
                            swal("Error!", response.message, "error");
                        }
                    },
                    error: function() {
                        swal("Error!", "Something went wrong.", "error");
                    }
                });
            }
        });
    }
</script>

</body>
</html>
