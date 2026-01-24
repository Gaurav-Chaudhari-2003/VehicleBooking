<?php
global $mysqli;
session_start();
include('../DATABASE FILE/config.php');
include('../DATABASE FILE/checklogin.php');
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
    
    // Vendor & Contract Details
    $v_ownership = $_POST['v_ownership']; // 'DEPARTMENT' or 'VENDOR'
    
    $vendor_name = ($v_ownership === 'VENDOR') ? trim($_POST['vendor_name']) : null;
    $vendor_phone = ($v_ownership === 'VENDOR') ? trim($_POST['vendor_phone']) : null;
    $vendor_email = ($v_ownership === 'VENDOR') ? trim($_POST['vendor_email']) : null;
    $vendor_addr = ($v_ownership === 'VENDOR') ? trim($_POST['vendor_addr']) : null;
    
    $contract_start = ($v_ownership === 'VENDOR') ? $_POST['contract_start'] : null;
    $contract_end = ($v_ownership === 'VENDOR') ? $_POST['contract_end'] : null;
    $contract_budget = ($v_ownership === 'VENDOR') ? $_POST['contract_budget'] : 0.00;
    $contract_status = ($v_ownership === 'VENDOR') ? $_POST['contract_status'] : 'ACTIVE';
    
    // Default Driver
    $default_driver_id = !empty($_POST['default_driver']) ? $_POST['default_driver'] : null;
    
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
        
        // Updated query to include default_driver_id
        // Note: Assuming 'default_driver_id' column has been added to 'vehicles' table as requested
        $query = "INSERT INTO vehicles (name, reg_no, category, fuel_type, capacity, status, image, ownership_type, default_driver_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('ssssisssi', $v_name, $v_reg_no, $v_category, $v_fuel_type, $v_capacity, $v_status, $v_dpic, $v_ownership, $default_driver_id);

        if ($stmt->execute()) {
            $new_vehicle_id = $stmt->insert_id;
            
            // Handle Vendor and Contract if ownership is VENDOR
            if ($v_ownership === 'VENDOR') {
                // 1. Check if vendor exists or create new
                $vendor_id = null;
                $check_vendor = $mysqli->prepare("SELECT id FROM vendors WHERE name = ?");
                $check_vendor->bind_param('s', $vendor_name);
                $check_vendor->execute();
                $check_vendor->store_result();
                
                if ($check_vendor->num_rows > 0) {
                    $check_vendor->bind_result($vendor_id);
                    $check_vendor->fetch();
                } else {
                    // Create a new vendor
                    $create_vendor = $mysqli->prepare("INSERT INTO vendors (name, phone, email, address) VALUES (?, ?, ?, ?)");
                    $create_vendor->bind_param('ssss', $vendor_name, $vendor_phone, $vendor_email, $vendor_addr);
                    $create_vendor->execute();
                    $vendor_id = $create_vendor->insert_id;
                    $create_vendor->close();
                }
                $check_vendor->close();
                
                // 2. Create Contract
                if ($vendor_id) {
                    $create_contract = $mysqli->prepare("INSERT INTO vehicle_contracts (vehicle_id, vendor_id, contract_start_date, contract_end_date, contract_budget, contract_status) VALUES (?, ?, ?, ?, ?, ?)");
                    $create_contract->bind_param('iissds', $new_vehicle_id, $vendor_id, $contract_start, $contract_end, $contract_budget, $contract_status);
                    $create_contract->execute();
                    $create_contract->close();
                }
            }
            
            // Insert Remark if provided
            if (!empty($v_remark)) {
                $remark_stmt = $mysqli->prepare("INSERT INTO entity_remarks (entity_type, entity_id, user_id, remark) VALUES ('VEHICLE', ?, ?, ?)");
                $remark_stmt->bind_param('iis', $new_vehicle_id, $aid, $v_remark);
                $remark_stmt->execute();
                
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
            $_SESSION['err'] = "Please Try Again Later. Error: " . $stmt->error;
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

// Fetch Active Drivers for Dropdown
$drivers = [];
$driver_query = "SELECT d.id, u.first_name, u.last_name 
                 FROM drivers d 
                 JOIN users u ON d.user_id = u.id 
                 WHERE d.status = 'ACTIVE'";
$driver_stmt = $mysqli->prepare($driver_query);
if ($driver_stmt) {
    $driver_stmt->execute();
    $driver_res = $driver_stmt->get_result();
    while ($d = $driver_res->fetch_object()) {
        $drivers[] = $d;
    }
    $driver_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Vehicle | Vehicle Booking System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Include Global Theme -->
    <?php include("../vendor/inc/theme-config.php"); ?>
    
    <style>
        body {
            background-color: #fff;
        }
        
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar styles are in sidebar.php */
        
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
        
        .form-control, .custom-select {
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            padding: 10px 15px;
            height: auto;
        }
        
        .form-control:focus, .custom-select:focus {
            box-shadow: 0 0 0 3px rgba(0, 121, 107, 0.1);
            border-color: var(--secondary-color);
        }
        
        .btn-add {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 12px 30px;
            font-weight: 600;
            box-shadow: 0 4px 10px rgba(0, 77, 64, 0.2);
            transition: all 0.3s;
        }
        
        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 77, 64, 0.3);
            color: white;
        }
        
        .custom-file-label {
            border-radius: 10px;
            padding: 10px 15px;
            height: auto;
        }
        
        .custom-file-label::after {
            height: 100%;
            padding: 10px 15px;
            border-radius: 0 10px 10px 0;
            background-color: #f8f9fa;
        }
    </style>
</head>

<body id="page-top">

<div class="dashboard-container">
    <!-- Sidebar -->
    <?php include("vendor/inc/sidebar.php"); ?>

    <div class="main-content">
        <div class="container-fluid mt-4">
            
            <?php if ($succ) { ?>
                <script src="vendor/js/swal.js"></script>
                <script>
                    setTimeout(function () {
                        swal("Success!", "<?php echo $succ; ?>", "success");
                    }, 100);
                </script>
            <?php } ?>

            <?php if ($err) { ?>
                <script src="vendor/js/swal.js"></script>
                <script>
                    setTimeout(function () {
                        swal("Failed!", "<?php echo $err; ?>", "error");
                    }, 100);
                </script>
            <?php } ?>

            <div class="card shadow border-0 rounded-lg">
                <div class="card-header bg-white py-3 d-flex align-items-center border-bottom-0">
                    <a href="javascript:void(0);" onclick="window.location.replace('admin-view-vehicle.php')" class="btn btn-sm btn-outline-secondary font-weight-bold rounded-pill px-3 mr-3"><i class="fas fa-arrow-left mr-1"></i> Back</a>
                    <h5 class="mb-0 font-weight-bold text-primary flex-grow-1 text-center" style="margin-right: 80px;">
                        <i class="fas fa-bus mr-2"></i> Add New Vehicle
                    </h5>
                </div>

                <div class="card-body bg-light p-4">
                    <form method="POST" enctype="multipart/form-data">

                        <!-- Section: Basic Info -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-body p-4">
                                <h6 class="text-uppercase text-muted small font-weight-bold mb-3 border-bottom pb-2">Basic Information</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="small font-weight-bold">Vehicle Name</label>
                                            <input type="text" required class="form-control" id="v_name" name="v_name" placeholder="e.g. Toyota Innova">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="small font-weight-bold">Registration Number</label>
                                            <input type="text" class="form-control" id="v_reg_no" name="v_reg_no" placeholder="e.g. MH-01-AB-1234">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="small font-weight-bold">Category</label>
                                            <select class="form-control custom-select" name="v_category" id="v_category">
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
                                            <label class="small font-weight-bold">Fuel Type</label>
                                            <select class="form-control custom-select" name="v_fuel_type" id="v_fuel_type">
                                                <option value="PETROL">Petrol</option>
                                                <option value="DIESEL">Diesel</option>
                                                <option value="CNG">CNG</option>
                                                <option value="ELECTRIC">Electric</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="small font-weight-bold">Capacity (Seats)</label>
                                            <input type="number" min="1" class="form-control" id="v_capacity" name="v_capacity" placeholder="e.g. 5">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section: Ownership & Vendor -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-body p-4">
                                <h6 class="text-uppercase text-muted small font-weight-bold mb-3 border-bottom pb-2">Ownership & Vendor Details</h6>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="small font-weight-bold">Ownership Type</label>
                                            <select class="form-control custom-select" name="v_ownership" id="v_ownership" onchange="toggleVendorFields()">
                                                <option value="DEPARTMENT">Departmental (Permanent)</option>
                                                <option value="VENDOR">Vendor Based (Contract)</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <!-- Default Driver Assignment -->
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="small font-weight-bold">Default Driver (Optional)</label>
                                            <div class="input-group">
                                                <select class="form-control custom-select" name="default_driver" id="default_driver">
                                                    <option value="">-- Select Existing Driver --</option>
                                                    <?php foreach ($drivers as $driver): ?>
                                                        <option value="<?php echo $driver->id; ?>">
                                                            <?php echo htmlspecialchars($driver->first_name . ' ' . $driver->last_name); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <div class="input-group-append">
                                                    <a href="admin-add-user.php" class="btn btn-outline-primary" title="Create New Driver"><i class="fas fa-plus"></i></a>
                                                </div>
                                            </div>
                                            <small class="form-text text-muted">Assign a default driver now or create a new one.</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Vendor Specific Fields -->
                                <div id="vendor-fields" style="display: none;" class="mt-3">
                                    <div class="bg-white p-3 rounded border">
                                        <h6 class="text-primary font-weight-bold mb-3 small text-uppercase">Vendor Information</h6>
                                        <div class="row mb-3">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label class="small font-weight-bold">Vendor Name</label>
                                                    <input type="text" class="form-control form-control-sm" name="vendor_name" id="vendor_name" placeholder="Enter vendor name">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label class="small font-weight-bold">Vendor Phone</label>
                                                    <input type="text" class="form-control form-control-sm" name="vendor_phone" id="vendor_phone" placeholder="Enter vendor phone">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label class="small font-weight-bold">Vendor Email</label>
                                                    <input type="email" class="form-control form-control-sm" name="vendor_email" id="vendor_email" placeholder="Enter vendor email">
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label class="small font-weight-bold">Vendor Address</label>
                                                    <textarea class="form-control form-control-sm" name="vendor_addr" id="vendor_addr" rows="1" placeholder="Enter vendor address"></textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <h6 class="text-primary font-weight-bold mb-3 small text-uppercase border-top pt-3">Contract Terms</h6>
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label class="small font-weight-bold">Start Date</label>
                                                    <input type="date" class="form-control form-control-sm" name="contract_start" id="contract_start">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label class="small font-weight-bold">End Date</label>
                                                    <input type="date" class="form-control form-control-sm" name="contract_end" id="contract_end">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label class="small font-weight-bold">Budget</label>
                                                    <input type="number" step="0.01" class="form-control form-control-sm" name="contract_budget" id="contract_budget" placeholder="0.00">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label class="small font-weight-bold">Status</label>
                                                    <select class="form-control form-control-sm custom-select" name="contract_status" id="contract_status">
                                                        <option value="ACTIVE">Active</option>
                                                        <option value="EXPIRED">Expired</option>
                                                        <option value="TERMINATED">Terminated</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section: Additional Info -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-body p-4">
                                <h6 class="text-uppercase text-muted small font-weight-bold mb-3 border-bottom pb-2">Additional Information</h6>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="small font-weight-bold">Vehicle Picture</label>
                                            <div class="custom-file">
                                                <input type="file" class="custom-file-input" id="v_dpic" name="v_dpic" accept="image/*" onchange="previewImage(event)">
                                                <label class="custom-file-label" for="v_dpic">Choose file...</label>
                                            </div>
                                            <div class="mt-3 text-center">
                                                <img id="imagePreview" src="../vendor/img/placeholder.png" alt="Image Preview" class="img-thumbnail shadow-sm" style="max-width: 200px; max-height: 150px; display: none;" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="small font-weight-bold">Remark (Optional)</label>
                                            <textarea class="form-control" name="v_remark" rows="4" placeholder="Enter any initial remarks about the vehicle..."></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-center mb-4">
                            <button type="submit" name="add_veh" class="btn btn-add btn-lg px-5 font-weight-bold shadow-sm rounded-pill">
                                <i class="fas fa-plus-circle mr-2"></i> Add Vehicle
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

<!-- Alerts Logic -->
<?php if ($succ): ?>
    <script>
        $(document).ready(function() {
            swal("Success!", "<?php echo $succ; ?>", "success");
        });
    </script>
<?php endif; ?>

<?php if ($err): ?>
    <script>
        $(document).ready(function() {
            swal("Failed!", "<?php echo $err; ?>", "error");
        });
    </script>
<?php endif; ?>

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

    function toggleVendorFields() {
        var ownership = document.getElementById("v_ownership").value;
        var vendorFields = document.getElementById("vendor-fields");
        var vendorInputs = vendorFields.querySelectorAll("input, select, textarea");
        
        if (ownership === "VENDOR") {
            vendorFields.style.display = "block";
            vendorInputs.forEach(input => {
                // Make required except maybe address/email if optional
                if(input.id !== 'vendor_addr' && input.id !== 'vendor_email') input.required = true;
            });
        } else {
            vendorFields.style.display = "none";
            vendorInputs.forEach(input => input.required = false);
        }
    }
</script>

</body>
</html>
