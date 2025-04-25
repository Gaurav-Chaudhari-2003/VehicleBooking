<?php
global $mysqli;
session_start();
include('vendor/inc/config.php');
include('vendor/inc/checklogin.php');
check_login();
$aid = $_SESSION['a_id'];
?>
<!DOCTYPE html>
<html lang="en">
<?php include('vendor/inc/head.php'); ?>
<body id="page-top">

<?php include("vendor/inc/nav.php"); ?>

<div id="wrapper">

    <!-- Sidebar -->
    <?php include('vendor/inc/sidebar.php'); ?>

    <div id="content-wrapper">

        <div class="container-fluid">

            <!-- Breadcrumbs-->
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Bookings</a></li>
                <li class="breadcrumb-item active">View</li>
            </ol>

            <!-- Bookings -->
            <div class="card mb-3">
                <div class="card-header">
                    <i class="fas fa-table"></i> Bookings
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Vehicle Type</th>
                                <th>Vehicle Reg No</th>
                                <th>Booking Dates</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            $ret = "
                SELECT 
                    b.booking_id, b.book_from_date, b.book_to_date, b.status, 
                    v.v_name, v.v_reg_no, 
                    u.u_fname, u.u_lname, u.u_phone 
                FROM tms_booking b
                JOIN tms_vehicle v ON b.vehicle_id = v.v_id
                JOIN tms_user u ON b.user_id = u.u_id
                ORDER BY b.book_from_date DESC
              ";

                            $stmt = $mysqli->prepare($ret);
                            $stmt->execute();
                            $res = $stmt->get_result();
                            $cnt = 1;
                            while ($row = $res->fetch_object()) {
                                ?>
                                <tr>
                                    <td><?= $cnt++; ?></td>
                                    <td><?= $row->u_fname . ' ' . $row->u_lname; ?></td>
                                    <td><?= $row->u_phone; ?></td>
                                    <td><?= $row->v_name; ?></td>
                                    <td><?= $row->v_reg_no; ?></td>
                                    <td><?= date("d M Y", strtotime($row->book_from_date)) . " → " . date("d M Y", strtotime($row->book_to_date)); ?></td>
                                    <td>
                                        <?php if ($row->status == "Pending") : ?>
                                            <span class="badge badge-warning"><?= $row->status; ?></span>
                                        <?php elseif ($row->status == "Approved") : ?>
                                            <span class="badge badge-success"><?= $row->status; ?></span>
                                        <?php else : ?>
                                            <span class="badge badge-danger"><?= $row->status; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($row->status == "Pending") : ?>
                                            <a href="admin-approve-booking.php?booking_id=<?= $row->booking_id; ?>" class="badge badge-success">
                                                <i class="fa fa-check"></i> Approve
                                            </a>
                                        <?php endif; ?>
                                        <a href="admin-delete-booking.php?booking_id=<?= $row->booking_id; ?>" class="badge badge-danger">
                                            <i class="fa fa-trash"></i> Delete
                                        </a>
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
                    echo "Last updated at " . date("h:i A");
                    ?>
                </div>
            </div>

        </div>
        <!-- /.container-fluid -->

        <!-- Footer -->
        <?php include("vendor/inc/footer.php"); ?>
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

<!-- Scripts -->
<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="vendor/datatables/jquery.dataTables.js"></script>
<script src="vendor/datatables/dataTables.bootstrap4.js"></script>
<script src="js/sb-admin.min.js"></script>
<script src="js/demo/datatables-demo.js"></script>

</body>
</html>
