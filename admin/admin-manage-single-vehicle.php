<?php
session_start();
include('vendor/inc/config.php');
include('vendor/inc/checklogin.php');
check_login();
$aid = $_GET['v_id'];  // Get vehicle ID from URL

// Handle vehicle update
if (isset($_POST['update_veh'])) {
    $v_id = $_GET['v_id'];
    $v_name = $_POST['v_name'];
    $v_reg_no = $_POST['v_reg_no'];
    $v_category = $_POST['v_category'];
    $v_status = $_POST['v_status'];
    $v_driver = $_POST['v_driver'];
    $v_dpic = $_FILES["v_dpic"]["name"];

    // Handle image upload
    if (!empty($v_dpic)) {
        move_uploaded_file($_FILES["v_dpic"]["tmp_name"], "../vendor/img/" . $v_dpic);
    }

    // Update vehicle data
    $query = "UPDATE tms_vehicle SET v_name=?, v_reg_no=?, v_driver=?, v_category=?, v_dpic=?, v_status=? WHERE v_id=?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('ssssssi', $v_name, $v_reg_no, $v_driver, $v_category, $v_dpic, $v_status, $v_id);
    $stmt->execute();

    if ($stmt) {
        $succ = "Vehicle Updated Successfully!";
    } else {
        $err = "Please Try Again Later";
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
                        swal("Success!", "<?php echo $succ; ?>", "success");
                    }, 100);
                </script>
            <?php } ?>
            <?php if (isset($err)) { ?>
                <script>
                    setTimeout(function () {
                        swal("Error!", "<?php echo $err; ?>", "error");
                    }, 100);
                </script>
            <?php } ?>

            <!-- Update Vehicle Form -->
            <div class="card mb-3">
                <div class="card-header">
                    <i class="fas fa-edit"></i> Update Vehicle
                </div>
                <div class="card-body">
                    <?php
                    // Fetch vehicle details
                    $ret = "SELECT * FROM tms_vehicle WHERE v_id=?";
                    $stmt = $mysqli->prepare($ret);
                    $stmt->bind_param('i', $aid);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    while ($row = $res->fetch_object()) {
                        ?>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="v_name">Vehicle Name</label>
                                <input type="text" value="<?php echo $row->v_name; ?>" required class="form-control" id="v_name" name="v_name">
                            </div>

                            <div class="form-group">
                                <label for="v_reg_no">Vehicle Registration Number</label>
                                <input type="text" value="<?php echo $row->v_reg_no; ?>" class="form-control" id="v_reg_no" name="v_reg_no">
                            </div>

                            <div class="form-group">
                                <label for="v_driver">Driver</label>
                                <input type="text" value="<?php echo $row->v_driver; ?>" class="form-control" id="v_driver" name="v_driver">
                            </div>

                            <div class="form-group">
                                <label for="v_category">Vehicle Category</label>
                                <select class="form-control" name="v_category" id="v_category">
                                    <option <?php echo ($row->v_category == "CAR") ? 'selected' : ''; ?>>CAR</option>
                                    <option <?php echo ($row->v_category == "SUV") ? 'selected' : ''; ?>>SUV</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="v_status">Vehicle Status</label>
                                <select class="form-control" name="v_status" id="v_status">
                                    <option <?php echo ($row->v_status == "Booked") ? 'selected' : ''; ?>>Booked</option>
                                    <option <?php echo ($row->v_status == "Available") ? 'selected' : ''; ?>>Available</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="v_dpic">Vehicle Image</label>
                                <div class="card" style="width: 30rem;">
                                    <img src="../vendor/img/<?php echo $row->v_dpic; ?>" class="card-img-top" alt="Vehicle Image">
                                    <div class="card-body">
                                        <h5 class="card-title">Vehicle Picture</h5>
                                        <input type="file" class="form-control-file" id="v_dpic" name="v_dpic">
                                    </div>
                                </div>
                            </div>

                            <button type="submit" name="update_veh" class="btn btn-success">Update Vehicle</button>
                        </form>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap core JavaScript-->
<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

<!-- Core plugin JavaScript-->
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>

<!-- Custom scripts for all pages-->
<script src="vendor/js/sb-admin.min.js"></script>

<!-- SweetAlert -->
<script src="vendor/js/swal.js"></script>

</body>

</html>
