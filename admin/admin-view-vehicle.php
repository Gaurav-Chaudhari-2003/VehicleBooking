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
        $ret = "SELECT * FROM vehicles WHERE status = 'RETIRED' ORDER BY id DESC";
    } else {
        $ret = "SELECT * FROM vehicles WHERE status != 'RETIRED' ORDER BY id DESC";
    }
    
    $stmt = $mysqli->prepare($ret);
    $stmt->execute();
    $res = $stmt->get_result();
    $cnt = 1;
    
    if ($res->num_rows > 0) {
        while ($row = $res->fetch_object()) {
            $img = !empty($row->image) ? "../vendor/img/" . $row->image : "../vendor/img/placeholder.png";
            ?>
            <tr id="vehicle-row-<?php echo $row->id; ?>">
                <td><?php echo $cnt; ?></td>
                <td class="p-0 text-center align-middle" style="width: 100px;">
                    <img src="<?php echo $img; ?>" alt="Vehicle Image" style="width: 100%; height: 80px; object-fit: cover; display: block;">
                </td>
                <td><?php echo $row->name; ?></td>
                <td><?php echo $row->reg_no; ?></td>
                <td><?php echo $row->category; ?></td>
                <td><?php echo $row->fuel_type; ?></td>
                <td><?php echo $row->capacity; ?></td>
                <td>
                    <?php
                    if ($row->status == "AVAILABLE") {
                        echo '<span class="badge badge-success">' . $row->status . '</span>';
                    } elseif ($row->status == "MAINTENANCE") {
                        echo '<span class="badge badge-warning">' . $row->status . '</span>';
                    } elseif ($row->status == "RETIRED") {
                        echo '<span class="badge badge-secondary">' . $row->status . '</span>';
                    } else {
                        echo '<span class="badge badge-danger">' . $row->status . '</span>';
                    }
                    ?>
                </td>
                <td>
                    <?php if ($show_retired) { ?>
                        <button class="badge badge-success border-0" onclick="performAction('restore', <?php echo $row->id; ?>)"><i class="fas fa-recycle"></i> Restore</button>
                    <?php } else { ?>
                        <a href="admin-manage-single-vehicle.php?v_id=<?php echo $row->id; ?>" class="badge badge-success"><i class="fa fa-edit"></i> Update</a>
                        <button class="badge badge-danger border-0" onclick="performAction('retire', <?php echo $row->id; ?>)"><i class="fa fa-trash"></i> Retire</button>
                    <?php } ?>
                </td>
            </tr>
            <?php
            $cnt++;
        }
    } else {
        echo '<tr><td colspan="9" class="text-center text-danger font-weight-bold">' . ($show_retired ? 'No retired vehicles found.' : 'No active vehicles found.') . '</td></tr>';
    }
    exit;
}

// Initial State
$show_retired = isset($_GET['show_retired']) && $_GET['show_retired'] == 'true';
?>

<!DOCTYPE html>
<html lang="en">

<?php include('vendor/inc/head.php'); ?>

<body id="page-top">
<div id="wrapper">


    <div id="content-wrapper">

        <div class="container-fluid">

            <!-- Add Vehicle Form -->
            <div class="card mb-3">
                <div class="card-header d-flex align-items-center">
                    <a href="javascript:void(0);" onclick="window.location.replace('admin-dashboard.php')" class="btn btn-light btn-sm mr-3 text-primary font-weight-bold"><i class="fas fa-arrow-left"></i> Back</a>
                    <div class="flex-grow-1 text-center" style="margin-right: 60px;">
                        <i class="fas fa-bus"></i> Add New Vehicle
                    </div>
                </div>
                <div class="card-body text-center">
                    <a href="admin-add-vehicle.php" class="btn btn-success">Add New Vehicle</a>
                </div>
            </div>

            <!-- DataTables Example -->
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span id="tableTitle"><i class="fas fa-bus"></i> <?php echo $show_retired ? 'Retired Vehicles' : 'Vehicle Fleet'; ?></span>
                    
                    <!-- Toggle Switch for Retired Vehicles -->
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="retiredSwitch" value="true" <?php if($show_retired) echo 'checked'; ?> onchange="applyFilters()">
                        <label class="custom-control-label font-weight-bold" for="retiredSwitch">Show Retired</label>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th style="width: 100px;">Image</th>
                                <th>Name</th>
                                <th>Registration Number</th>
                                <th>Category</th>
                                <th>Fuel Type</th>
                                <th>Capacity</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            if ($show_retired) {
                                $ret = "SELECT * FROM vehicles WHERE status = 'RETIRED' ORDER BY id DESC";
                            } else {
                                $ret = "SELECT * FROM vehicles WHERE status != 'RETIRED' ORDER BY id DESC";
                            }
                            
                            $stmt = $mysqli->prepare($ret);
                            $stmt->execute();
                            $res = $stmt->get_result();
                            $cnt = 1;
                            
                            if ($res->num_rows > 0) {
                                while ($row = $res->fetch_object()) {
                                    $img = !empty($row->image) ? "../vendor/img/" . $row->image : "../vendor/img/placeholder.png";
                                    ?>
                                    <tr id="vehicle-row-<?php echo $row->id; ?>">
                                        <td><?php echo $cnt; ?></td>
                                        <td class="p-0 text-center align-middle" style="width: 100px;">
                                            <img src="<?php echo $img; ?>" alt="Vehicle Image" style="width: 100%; height: 80px; object-fit: cover; display: block;">
                                        </td>
                                        <td><?php echo $row->name; ?></td>
                                        <td><?php echo $row->reg_no; ?></td>
                                        <td><?php echo $row->category; ?></td>
                                        <td><?php echo $row->fuel_type; ?></td>
                                        <td><?php echo $row->capacity; ?></td>
                                        <td>
                                            <?php
                                            if ($row->status == "AVAILABLE") {
                                                echo '<span class="badge badge-success">' . $row->status . '</span>';
                                            } elseif ($row->status == "MAINTENANCE") {
                                                echo '<span class="badge badge-warning">' . $row->status . '</span>';
                                            } elseif ($row->status == "RETIRED") {
                                                echo '<span class="badge badge-secondary">' . $row->status . '</span>';
                                            } else {
                                                echo '<span class="badge badge-danger">' . $row->status . '</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php if ($show_retired) { ?>
                                                <button class="badge badge-success border-0" onclick="performAction('restore', <?php echo $row->id; ?>)"><i class="fas fa-recycle"></i> Restore</button>
                                            <?php } else { ?>
                                                <a href="admin-manage-single-vehicle.php?v_id=<?php echo $row->id; ?>" class="badge badge-success"><i class="fa fa-edit"></i> Update</a>
                                                <button class="badge badge-danger border-0" onclick="performAction('retire', <?php echo $row->id; ?>)"><i class="fa fa-trash"></i> Retire</button>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                    <?php
                                    $cnt++;
                                }
                            } else {
                                echo '<tr><td colspan="9" class="text-center text-danger font-weight-bold">' . ($show_retired ? 'No retired vehicles found.' : 'No active vehicles found.') . '</td></tr>';
                            }
                            ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.container-fluid -->
    </div>
    <!-- /.content-wrapper -->

</div>
<!-- /#wrapper -->


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

<!-- Initialize DataTable with Search -->
<script>
    $(document).ready(function () {
        $('#dataTable').DataTable({
            "searching": true, // Enable search functionality
            "ordering": true,  // Enable column sorting
            "paging": true,    // Enable paging for large tables
            "info": true       // Show information about rows
        });
    });

    function applyFilters() {
        var showRetired = $('#retiredSwitch').is(':checked');
        
        // Update header title
        var title = showRetired ? 'Retired Vehicles' : 'Vehicle Fleet';
        $('#tableTitle').html('<i class="fas fa-bus"></i> ' + title);

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
                            // Remove the row from the table
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

<!-- Demo scripts for this page-->
<script src="vendor/js/demo/datatables-demo.js"></script>

</body>

</html>
