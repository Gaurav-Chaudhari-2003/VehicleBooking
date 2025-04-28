<?php
session_start();
include('vendor/inc/config.php');
include('vendor/inc/checklogin.php');
check_login();
$aid = $_SESSION['a_id'];

// Handling delete action
if (isset($_GET['del'])) {
    $id = intval($_GET['del']);
    $adn = "DELETE FROM tms_user WHERE u_id=?";
    $stmt = $mysqli->prepare($adn);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    if ($stmt) {
        $succ = "Driver Fired";
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

            <?php if (isset($succ)) { ?>
                <!-- Success Alert -->
                <script>
                    setTimeout(function () {
                        swal("Success!", "<?php echo $succ;?>!", "success");
                    }, 100);
                </script>
            <?php } ?>
            <?php if (isset($err)) { ?>
                <!-- Error Alert -->
                <script>
                    setTimeout(function () {
                        swal("Failed!", "<?php echo $err;?>!", "error");
                    }, 100);
                </script>
            <?php } ?>

            <!-- Add Driver Form -->
            <div class="card mb-3">
                <div class="card-header">
                    <i class="fas fa-user-plus"></i> Register New Driver
                </div>
                <div class="card-body text-center">
                    <a href="admin-add-driver.php" class="btn btn-success">Register New Driver</a>
                </div>
            </div>

            <!-- DataTables Example -->
            <div class="card mb-3">
                <div class="card-header">
                    <i class="fas fa-users"></i> Registered Drivers
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Contact</th>
                                <th>Address</th>
                                <th>Email</th>
                                <?php if (!isset($_GET['del'])) { ?>
                                    <th>Action</th>
                                <?php } ?>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            $ret = "SELECT * FROM tms_user WHERE u_category = 'Driver' ORDER BY RAND() LIMIT 1000";
                            $stmt = $mysqli->prepare($ret);
                            $stmt->execute();
                            $res = $stmt->get_result();
                            $cnt = 1;
                            while ($row = $res->fetch_object()) {
                                ?>
                                <tr>
                                    <td><?php echo $cnt; ?></td>
                                    <td><?php echo $row->u_fname; ?> <?php echo $row->u_lname; ?></td>
                                    <td><?php echo $row->u_phone; ?></td>
                                    <td><?php echo $row->u_addr; ?></td>
                                    <td><?php echo $row->u_email; ?></td>
                                    <?php if (!isset($_GET['del'])) { ?>
                                        <td>
                                            <a href="admin-manage-single-driver.php?u_id=<?php echo $row->u_id;?>" class="badge badge-success">Update</a>
                                            <a href="admin-manage-driver.php?del=<?php echo $row->u_id;?>" class="badge badge-danger">Fire</a>
                                        </td>
                                    <?php } ?>
                                </tr>
                                <?php $cnt = $cnt + 1; } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer small text-muted">Updated yesterday at 11:59 PM</div>
            </div>
        </div>
        <!-- /.container-fluid -->
    </div>
    <!-- /.content-wrapper -->
</div>
<!-- /#wrapper -->

<!-- Scroll to Top Button-->
<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

<!-- Logout Modal-->
<div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                <a class="btn btn-danger" href="admin-logout.php">Logout</a>
            </div>
        </div>
    </div>
</div>

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

<!-- Demo scripts for this page-->
<script src="js/demo/datatables-demo.js"></script>

<script>
    $(document).ready(function() {
        $('#dataTable').DataTable(); // Initialize DataTable with search functionality
    });
</script>

</body>
</html>
