<?php
session_start();
include('vendor/inc/config.php');
include('vendor/inc/checklogin.php');
check_login();
$aid = $_SESSION['a_id'];

// Handle vehicle deletion
if (isset($_GET['del'])) {
    $id = intval($_GET['del']);
    $adn = "DELETE FROM tms_vehicle WHERE v_id=?";
    $stmt = $mysqli->prepare($adn);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    if ($stmt) {
        $succ = "Vehicle Removed";
    } else {
        $err = "Try Again Later";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<?php include('vendor/inc/head.php'); ?>

<body id="page-top">
<div id="wrapper">


    <div id="content-wrapper">

        <div class="container-fluid">

            <!-- Success/Error Messages -->
            <?php if (isset($succ)) { ?>
                <script>
                    setTimeout(function () {
                        swal("Success!", "<?php echo $succ; ?>!", "success");
                    }, 100);
                </script>
            <?php } ?>
            <?php if (isset($err)) { ?>
                <script>
                    setTimeout(function () {
                        swal("Failed!", "<?php echo $err; ?>!", "error");
                    }, 100);
                </script>
            <?php } ?>

            <!-- Add Vehicle Form -->
            <div class="card mb-3">
                <div class="card-header">
                    <i class="fas fa-user-plus"></i> Add New Vehicle
                </div>
                <div class="card-body text-center">
                    <a href="admin-add-vehicle.php" class="btn btn-success">Add New Vehicle</a>
                </div>
            </div>

            <!-- DataTables Example -->
            <div class="card mb-3">
                <div class="card-header">
                    <i class="fas fa-bus"></i>
                    Vehicles
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Registration Number</th>
                                <th>Driver</th>
                                <th>Passengers</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            $ret = "SELECT * FROM tms_vehicle";
                            $stmt = $mysqli->prepare($ret);
                            $stmt->execute();
                            $res = $stmt->get_result();
                            $cnt = 1;
                            while ($row = $res->fetch_object()) {
                                ?>
                                <tr>
                                    <td><?php echo $cnt; ?></td>
                                    <td><?php echo $row->v_name; ?></td>
                                    <td><?php echo $row->v_reg_no; ?></td>
                                    <td><?php echo $row->v_driver; ?></td>
                                    <td><?php echo $row->v_pass_no; ?></td>
                                    <td><?php echo $row->v_category; ?></td>
                                    <td>
                                        <?php
                                        if ($row->v_status == "Available") {
                                            echo '<span class="badge badge-success">' . $row->v_status . '</span>';
                                        } else {
                                            echo '<span class="badge badge-danger">' . $row->v_status . '</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <a href="admin-manage-single-vehicle.php?v_id=<?php echo $row->v_id; ?>" class="badge badge-success">Update</a>
                                        <a href="admin-manage-vehicle.php?del=<?php echo $row->v_id; ?>" class="badge badge-danger">Delete</a>
                                    </td>
                                </tr>
                                <?php
                                $cnt++;
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
<script src="js/sb-admin.min.js"></script>

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
</script>

<!-- Demo scripts for this page-->
<script src="js/demo/datatables-demo.js"></script>

</body>

</html>
