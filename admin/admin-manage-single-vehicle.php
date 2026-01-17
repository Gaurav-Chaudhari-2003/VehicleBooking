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
    $v_fuel_type = $_POST['v_fuel_type']; // New field
    $v_capacity = $_POST['v_capacity'];   // Renamed from v_pass_no
    $v_status = $_POST['v_status'];
    $v_dpic = $_FILES["v_dpic"]["name"];

    global $mysqli;

    // Check if registration number already exists for another vehicle
    // New Schema: vehicles table
    $check_query = "SELECT id FROM vehicles WHERE reg_no = ? AND id != ?";
    $check_stmt = $mysqli->prepare($check_query);
    $check_stmt->bind_param('si', $v_reg_no, $v_id);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        $err = "This registration number is already in use by another vehicle!";
    } else {
        // Handle image upload
        if (!empty($v_dpic)) {
            move_uploaded_file($_FILES["v_dpic"]["tmp_name"], "../vendor/img/" . $v_dpic);
            // New Schema: vehicles table
            // Columns: name, reg_no, category, fuel_type, capacity, image, status
            $query = "UPDATE vehicles SET name=?, reg_no=?, category=?, fuel_type=?, capacity=?, image=?, status=? WHERE id=?";
            $stmt = $mysqli->prepare($query);
            $stmt->bind_param('ssssissi', $v_name, $v_reg_no, $v_category, $v_fuel_type, $v_capacity, $v_dpic, $v_status, $v_id);
        } else {
            // Update without changing image
            $query = "UPDATE vehicles SET name=?, reg_no=?, category=?, fuel_type=?, capacity=?, status=? WHERE id=?";
            $stmt = $mysqli->prepare($query);
            $stmt->bind_param('ssssisi', $v_name, $v_reg_no, $v_category, $v_fuel_type, $v_capacity, $v_status, $v_id);
        }
        
        if ($stmt->execute()) {
            $succ = "Vehicle Updated Successfully!";
        } else {
            $err = "Please Try Again Later";
        }
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
                <div class="card-header d-flex align-items-center">
                    <a href="javascript:void(0);" onclick="window.location.replace('admin-view-vehicle.php')" class="btn btn-light btn-sm mr-3 text-primary font-weight-bold"><i class="fas fa-arrow-left"></i> Back</a>
                    <div class="flex-grow-1 text-center" style="margin-right: 60px;">
                        <i class="fas fa-edit"></i> Update Vehicle
                    </div>
                </div>
                <div class="card-body">
                    <?php
                    // Fetch vehicle details
                    // New Schema: vehicles table
                    $ret = "SELECT * FROM vehicles WHERE id=?";
                    $stmt = $mysqli->prepare($ret);
                    $stmt->bind_param('i', $aid);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    while ($row = $res->fetch_object()) {
                        ?>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold">Vehicle Name</label>
                                        <input type="text" value="<?php echo $row->name; ?>" required class="form-control" id="v_name" name="v_name">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold">Registration Number</label>
                                        <div class="input-group">
                                            <input type="text" value="<?php echo $row->reg_no; ?>" class="form-control" id="v_reg_no" name="v_reg_no" readonly required>
                                            <div class="input-group-append">
                                                <button class="btn btn-outline-secondary" type="button" onclick="enableRegEdit()">Edit</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="font-weight-bold">Category</label>
                                        <select class="form-control" name="v_category" id="v_category">
                                            <option value="SEDAN" <?php if($row->category == 'SEDAN') echo 'selected'; ?>>Sedan</option>
                                            <option value="SUV" <?php if($row->category == 'SUV') echo 'selected'; ?>>SUV</option>
                                            <option value="BUS" <?php if($row->category == 'BUS') echo 'selected'; ?>>Bus</option>
                                            <option value="TRUCK" <?php if($row->category == 'TRUCK') echo 'selected'; ?>>Truck</option>
                                            <option value="VAN" <?php if($row->category == 'VAN') echo 'selected'; ?>>Van</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="font-weight-bold">Fuel Type</label>
                                        <select class="form-control" name="v_fuel_type" id="v_fuel_type">
                                            <option value="PETROL" <?php if($row->fuel_type == 'PETROL') echo 'selected'; ?>>Petrol</option>
                                            <option value="DIESEL" <?php if($row->fuel_type == 'DIESEL') echo 'selected'; ?>>Diesel</option>
                                            <option value="CNG" <?php if($row->fuel_type == 'CNG') echo 'selected'; ?>>CNG</option>
                                            <option value="ELECTRIC" <?php if($row->fuel_type == 'ELECTRIC') echo 'selected'; ?>>Electric</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="font-weight-bold">Capacity (Seats)</label>
                                        <input type="number" min="1" value="<?php echo $row->capacity; ?>" class="form-control" id="v_capacity" name="v_capacity">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="font-weight-bold">Status</label>
                                <select class="form-control" name="v_status" id="v_status">
                                    <option value="AVAILABLE" <?php if($row->status == 'AVAILABLE') echo 'selected'; ?>>Available</option>
                                    <option value="IN_SERVICE" <?php if($row->status == 'IN_SERVICE') echo 'selected'; ?>>In Service</option>
                                    <option value="MAINTENANCE" <?php if($row->status == 'MAINTENANCE') echo 'selected'; ?>>Maintenance</option>
                                    <option value="RETIRED" <?php if($row->status == 'RETIRED') echo 'selected'; ?>>Retired</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="font-weight-bold">Vehicle Image</label>
                                <div class="card" style="width: 18rem;">
                                    <img src="../vendor/img/<?php echo !empty($row->image) ? $row->image : 'placeholder.png'; ?>" class="card-img-top" alt="Vehicle Image">
                                    <div class="card-body">
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" id="v_dpic" name="v_dpic">
                                            <label class="custom-file-label" for="v_dpic">Change Image...</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-center mt-4">
                                <button type="submit" name="update_veh" class="btn btn-success btn-lg px-5 shadow-sm">
                                    <i class="fas fa-save"></i> Update Vehicle
                                </button>
                            </div>
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

<script>
    function enableRegEdit() {
        swal({
            title: "Are you sure?",
            text: "Changing the registration number is a critical action.",
            icon: "warning",
            buttons: true,
            dangerMode: true,
        })
        .then((willEdit) => {
            if (willEdit) {
                document.getElementById('v_reg_no').removeAttribute('readonly');
                document.getElementById('v_reg_no').focus();
            }
        });
    }
    
    // Update file input label
    $('.custom-file-input').on('change', function() {
        let fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').addClass("selected").html(fileName);
    });
</script>

</body>

</html>
