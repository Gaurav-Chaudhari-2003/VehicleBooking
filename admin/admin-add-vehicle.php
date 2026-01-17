<?php
global $mysqli;
session_start();
include('vendor/inc/config.php');
include('vendor/inc/checklogin.php');
check_login();
$aid = $_SESSION['a_id'];

$succ = $err = "";

// Add Vehicle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_veh'])) {
    $v_name     = $_POST['v_name'];
    $v_reg_no   = $_POST['v_reg_no'];
    $v_category = $_POST['v_category'];
    $v_fuel_type = $_POST['v_fuel_type']; 
    $v_capacity = $_POST['v_capacity'];   
    $v_status   = "AVAILABLE"; 
    $v_remark   = trim($_POST['v_remark']);
    
    // Check if vehicle registration number already exists
    $check_stmt = $mysqli->prepare("SELECT reg_no FROM vehicles WHERE reg_no = ?");
    $check_stmt->bind_param('s', $v_reg_no);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        $_SESSION['err'] = "Vehicle with this Registration Number already exists!";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        // Image Upload Logic
        $v_dpic = 'placeholder.png'; // default
        if (isset($_FILES["v_dpic"]) && $_FILES["v_dpic"]["error"] === 0) {
            $check = getimagesize($_FILES["v_dpic"]["tmp_name"]);
            if ($check !== false) {
                $target_dir = "../vendor/img/";
                $v_dpic = basename($_FILES["v_dpic"]["name"]);
                $target_file = $target_dir . $v_dpic;
                move_uploaded_file($_FILES["v_dpic"]["tmp_name"], $target_file);
            }
        }

        // New Schema: vehicles table
        // Columns: name, reg_no, category, fuel_type, capacity, status, image
        $query = "INSERT INTO vehicles (name, reg_no, category, fuel_type, capacity, status, image) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('ssssiss', $v_name, $v_reg_no, $v_category, $v_fuel_type, $v_capacity, $v_status, $v_dpic);

        if ($stmt->execute()) {
            $new_vehicle_id = $stmt->insert_id;
            
            // Insert Remark if provided
            if (!empty($v_remark)) {
                // entity_type='VEHICLE', entity_id=new_vehicle_id, user_id=admin_id
                $remark_stmt = $mysqli->prepare("INSERT INTO entity_remarks (entity_type, entity_id, user_id, remark) VALUES ('VEHICLE', ?, ?, ?)");
                $remark_stmt->bind_param('iis', $new_vehicle_id, $aid, $v_remark);
                $remark_stmt->execute();
                
                // Update last_remark_id in vehicles table
                $last_remark_id = $remark_stmt->insert_id;
                $update_veh = $mysqli->prepare("UPDATE vehicles SET last_remark_id = ? WHERE id = ?");
                $update_veh->bind_param('ii', $last_remark_id, $new_vehicle_id);
                $update_veh->execute();
                $update_veh->close();
                
                $remark_stmt->close();
            }

            $_SESSION['succ'] = "Vehicle Added Successfully";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $_SESSION['err'] = "Please Try Again Later";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
        $stmt->close();
    }
    $check_stmt->close();
}

// Load messages from session and clear them
if (isset($_SESSION['succ'])) {
    $succ = $_SESSION['succ'];
    unset($_SESSION['succ']);
}
if (isset($_SESSION['err'])) {
    $err = $_SESSION['err'];
    unset($_SESSION['err']);
}
?>

<!DOCTYPE html>
<html lang="en">
<?php include('vendor/inc/head.php');?>

<body id="page-top">


<div id="wrapper">


    <div id="content-wrapper">
        <div class="container-fluid">
            <?php if ($succ): ?>
                <script>
                    setTimeout(function () {
                        swal("Success!", "<?php echo $succ; ?>", "success");
                    }, 100);
                </script>
            <?php endif; ?>

            <?php if ($err): ?>
                <script>
                    setTimeout(function () {
                        swal("Failed!", "<?php echo $err; ?>", "error");
                    }, 100);
                </script>
            <?php endif; ?>

            <div class="card shadow-lg border-0 rounded-lg mt-4">
                <div class="card-header bg-primary text-white d-flex align-items-center">
                    <a href="javascript:void(0);" onclick="window.location.replace('admin-dashboard.php')" class="btn btn-light btn-sm mr-3 text-primary font-weight-bold"><i class="fas fa-arrow-left"></i> Back</a>
                    <h4 class="font-weight-bold mb-0 flex-grow-1 text-center" style="margin-right: 60px;"><i class="fas fa-bus"></i> Add New Vehicle</h4>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold">Vehicle Name</label>
                                    <input type="text" required class="form-control" id="v_name" name="v_name" placeholder="Enter vehicle name">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold">Registration Number</label>
                                    <input type="text" class="form-control" id="v_reg_no" name="v_reg_no" placeholder="Enter vehicle registration number">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="font-weight-bold">Category</label>
                                    <select class="form-control" name="v_category" id="v_category">
                                        <option value="SEDAN">Sedan</option>
                                        <option value="SUV">SUV</option>
                                        <option value="BUS">Bus</option>
                                        <option value="TRUCK">Truck</option>
                                        <option value="VAN">Van</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="font-weight-bold">Fuel Type</label>
                                    <select class="form-control" name="v_fuel_type" id="v_fuel_type">
                                        <option value="PETROL">Petrol</option>
                                        <option value="DIESEL">Diesel</option>
                                        <option value="CNG">CNG</option>
                                        <option value="ELECTRIC">Electric</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="font-weight-bold">Capacity (Seats)</label>
                                    <input type="number" min="1" class="form-control" id="v_capacity" name="v_capacity" placeholder="Enter number of seats">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Remark (Optional)</label>
                            <textarea class="form-control" name="v_remark" rows="2" placeholder="Enter any initial remarks about the vehicle..."></textarea>
                        </div>

                        <div class="form-group" style="display:none">
                            <label for="v_status">Vehicle Status</label>
                            <input type="text" class="form-control" id="v_status" name="v_status" value="AVAILABLE" readonly>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Vehicle Picture</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="v_dpic" name="v_dpic" accept="image/*" onchange="previewImage(event)">
                                <label class="custom-file-label" for="v_dpic">Choose file...</label>
                            </div>
                            <div class="mt-3 text-center">
                                <img id="imagePreview" src="vendor/img/placeholder.png" alt="Image Preview" class="img-thumbnail shadow-sm" style="max-width: 300px; max-height: 200px; display: none;" />
                            </div>
                        </div>

                        <div class="text-center mt-4">
                            <button type="submit" name="add_veh" class="btn btn-success btn-lg px-5 shadow-sm">
                                <i class="fas fa-plus-circle"></i> Add Vehicle
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Scripts -->
<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="vendor/chart.js/Chart.min.js"></script>
<script src="vendor/datatables/jquery.dataTables.js"></script>
<script src="vendor/datatables/dataTables.bootstrap4.js"></script>
<script src="vendor/js/sb-admin.min.js"></script>
<script src="vendor/js/demo/datatables-demo.js"></script>
<script src="vendor/js/demo/chart-area-demo.js"></script>
<script src="vendor/js/swal.js"></script>
<script>
    function previewImage(event) {
        const input = event.target;
        const preview = document.getElementById('imagePreview');
        const label = document.querySelector('.custom-file-label');

        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'inline-block';
            };
            reader.readAsDataURL(input.files[0]);
            label.textContent = input.files[0].name;
        }
    }
</script>

</body>
</html>
