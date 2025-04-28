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
    $v_pass_no  = $_POST['v_pass_no'];
    $v_status   = $_POST['v_status'];
    $v_driver   = $_POST['v_driver'];

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

    $query = "INSERT INTO tms_vehicle (v_name, v_pass_no, v_reg_no, v_driver, v_category, v_dpic, v_status) 
              VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('sssssss', $v_name, $v_pass_no, $v_reg_no, $v_driver, $v_category, $v_dpic, $v_status);

    if ($stmt->execute()) {
        $_SESSION['succ'] = "Vehicle Added Successfully";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $_SESSION['err'] = "Please Try Again Later";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
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

            <div class="card">
                <div class="card-header">Add Vehicle</div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="v_name">Vehicle Name</label>
                            <input type="text" required class="form-control" id="v_name" name="v_name" placeholder="Enter vehicle name">
                        </div>

                        <div class="form-group">
                            <label for="v_reg_no">Vehicle Registration Number</label>
                            <input type="text" class="form-control" id="v_reg_no" name="v_reg_no" placeholder="Enter vehicle registration number">
                        </div>

                        <div class="form-group">
                            <label for="v_pass_no">Number of Seats</label>
                            <input type="number" min="1" class="form-control" id="v_pass_no" name="v_pass_no" placeholder="Enter number of seats">
                        </div>

                        <div class="form-group">
                            <label for="v_driver">Driver</label>
                            <select class="form-control" name="v_driver" id="v_driver">
                                <?php
                                $ret = "SELECT * FROM tms_user WHERE u_category = 'Driver'";
                                $stmt = $mysqli->prepare($ret);
                                $stmt->execute();
                                $res = $stmt->get_result();
                                while ($row = $res->fetch_object()) {
                                    echo "<option>{$row->u_fname} {$row->u_lname}</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="v_category">Vehicle Category</label>
                            <select class="form-control" name="v_category" id="v_category">
                                <option>Sedan</option>
                                <option>SUV</option>
                                <option>Truck</option>
                                <option>Van</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="v_status">Vehicle Status</label>
                            <select class="form-control" name="v_status" id="v_status">
                                <option>Available</option>
                                <option>Booked</option>
                                <option>Maintenance</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="v_dpic">Vehicle Picture</label>
                            <input type="file" class="form-control" id="v_dpic" name="v_dpic" accept="image/*" onchange="previewImage(event)">
                            <br>
                            <img id="imagePreview" src="vendor/img/placeholder.png" alt="Image Preview" style="max-width: 400px; border-radius: 8px; display: none;" />
                        </div>

                        <button type="submit" name="add_veh" class="btn btn-success">Add Vehicle</button>
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

        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>

</body>
</html>
