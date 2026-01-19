<?php
session_start();
include('../DATABASE FILE/config.php');
include('../DATABASE FILE/checklogin.php');
check_login();
$aid = $_GET['v_id'];  // Get vehicle ID from URL

// Handle vehicle update
if (isset($_POST['update_veh'])) {
    $v_id = $_GET['v_id'];
    $v_name = $_POST['v_name'];
    $v_reg_no = $_POST['v_reg_no'];
    $v_category = $_POST['v_category'];
    $v_fuel_type = $_POST['v_fuel_type']; 
    $v_capacity = $_POST['v_capacity'];   
    $v_status = $_POST['v_status'];
    $v_dpic = $_FILES["v_dpic"]["name"];
    
    // Vendor Details
    $v_ownership = $_POST['v_ownership']; // 'DEPARTMENT' or 'VENDOR'
    
    $vendor_name = ($v_ownership === 'VENDOR') ? trim($_POST['vendor_name']) : null;
    $vendor_phone = ($v_ownership === 'VENDOR') ? trim($_POST['vendor_phone']) : null;
    $vendor_email = ($v_ownership === 'VENDOR') ? trim($_POST['vendor_email']) : null;
    $vendor_addr = ($v_ownership === 'VENDOR') ? trim($_POST['vendor_addr']) : null;
    
    $contract_start = ($v_ownership === 'VENDOR') ? $_POST['contract_start'] : null;
    $contract_end = ($v_ownership === 'VENDOR') ? $_POST['contract_end'] : null;
    $contract_budget = ($v_ownership === 'VENDOR') ? $_POST['contract_budget'] : 0.00;
    $contract_status = ($v_ownership === 'VENDOR') ? $_POST['contract_status'] : 'ACTIVE';

    global $mysqli;

    // Check if registration number already exists for another vehicle
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
            $query = "UPDATE vehicles SET name=?, reg_no=?, category=?, fuel_type=?, capacity=?, image=?, status=?, ownership_type=? WHERE id=?";
            $stmt = $mysqli->prepare($query);
            $stmt->bind_param('ssssisssi', $v_name, $v_reg_no, $v_category, $v_fuel_type, $v_capacity, $v_dpic, $v_status, $v_ownership, $v_id);
        } else {
            // Update without changing image
            $query = "UPDATE vehicles SET name=?, reg_no=?, category=?, fuel_type=?, capacity=?, status=?, ownership_type=? WHERE id=?";
            $stmt = $mysqli->prepare($query);
            $stmt->bind_param('ssssissi', $v_name, $v_reg_no, $v_category, $v_fuel_type, $v_capacity, $v_status, $v_ownership, $v_id);
        }
        
        if ($stmt->execute()) {
            // Handle Vendor and Contract Logic
            if ($v_ownership === 'VENDOR') {
                // 1. Check/Create Vendor
                $vendor_id = null;
                $check_vendor = $mysqli->prepare("SELECT id FROM vendors WHERE name = ?");
                $check_vendor->bind_param('s', $vendor_name);
                $check_vendor->execute();
                $check_vendor->store_result();
                
                if ($check_vendor->num_rows > 0) {
                    $check_vendor->bind_result($vendor_id);
                    $check_vendor->fetch();
                    
                    // Optional: Update vendor details if needed
                    $update_vendor = $mysqli->prepare("UPDATE vendors SET phone=?, email=?, address=? WHERE id=?");
                    $update_vendor->bind_param('sssi', $vendor_phone, $vendor_email, $vendor_addr, $vendor_id);
                    $update_vendor->execute();
                    $update_vendor->close();
                    
                } else {
                    $create_vendor = $mysqli->prepare("INSERT INTO vendors (name, phone, email, address) VALUES (?, ?, ?, ?)");
                    $create_vendor->bind_param('ssss', $vendor_name, $vendor_phone, $vendor_email, $vendor_addr);
                    $create_vendor->execute();
                    $vendor_id = $create_vendor->insert_id;
                    $create_vendor->close();
                }
                $check_vendor->close();

                // 2. Update/Create Contract
                // Check if there is an active contract for this vehicle
                $check_contract = $mysqli->prepare("SELECT id FROM vehicle_contracts WHERE vehicle_id = ? ORDER BY created_at DESC LIMIT 1");
                $check_contract->bind_param('i', $v_id);
                $check_contract->execute();
                $check_contract->store_result();
                
                if ($check_contract->num_rows > 0) {
                    $check_contract->bind_result($contract_id);
                    $check_contract->fetch();
                    // Update existing contract
                    $update_contract = $mysqli->prepare("UPDATE vehicle_contracts SET vendor_id=?, contract_start_date=?, contract_end_date=?, contract_budget=?, contract_status=? WHERE id=?");
                    $update_contract->bind_param('issdsi', $vendor_id, $contract_start, $contract_end, $contract_budget, $contract_status, $contract_id);
                    $update_contract->execute();
                    $update_contract->close();
                } else {
                    // Create new contract
                    $create_contract = $mysqli->prepare("INSERT INTO vehicle_contracts (vehicle_id, vendor_id, contract_start_date, contract_end_date, contract_budget, contract_status) VALUES (?, ?, ?, ?, ?, ?)");
                    $create_contract->bind_param('iissds', $v_id, $vendor_id, $contract_start, $contract_end, $contract_budget, $contract_status);
                    $create_contract->execute();
                    $create_contract->close();
                }
                $check_contract->close();
            }
            
            $succ = "Vehicle Updated Successfully!";
        } else {
            $err = "Please Try Again Later. Error: " . $stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<?php include('vendor/inc/head.php'); ?>

<body id="page-top" style="background-color: #f8f9fc;">

<div id="wrapper">
    <div id="content-wrapper">
        <div class="container-fluid mt-4">
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
            <div class="card shadow border-0 rounded-lg">
                <div class="card-header bg-white py-3 d-flex align-items-center border-bottom-0">
                    <a href="javascript:void(0);" onclick="window.location.replace('admin-view-vehicle.php')" class="btn btn-sm btn-outline-secondary font-weight-bold rounded-pill px-3 mr-3"><i class="fas fa-arrow-left mr-1"></i> Back</a>
                    <h5 class="mb-0 font-weight-bold text-primary flex-grow-1 text-center" style="margin-right: 80px;">
                        <i class="fas fa-edit mr-2"></i> Update Vehicle Details
                    </h5>
                </div>
                
                <div class="card-body bg-light p-4">
                    <?php
                    // Fetch vehicle details
                    $ret = "SELECT v.*, vc.contract_start_date, vc.contract_end_date, vc.contract_budget, vc.contract_status, 
                                   ven.name as vendor_name, ven.phone as vendor_phone, ven.email as vendor_email, ven.address as vendor_addr
                            FROM vehicles v 
                            LEFT JOIN vehicle_contracts vc ON v.id = vc.vehicle_id 
                            LEFT JOIN vendors ven ON vc.vendor_id = ven.id
                            WHERE v.id=? 
                            ORDER BY vc.created_at DESC LIMIT 1"; 
                            
                    $stmt = $mysqli->prepare($ret);
                    $stmt->bind_param('i', $aid);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    while ($row = $res->fetch_object()) {
                        ?>
                        <form method="POST" enctype="multipart/form-data">
                            
                            <!-- Section: Basic Info -->
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-body p-4">
                                    <h6 class="text-uppercase text-muted small font-weight-bold mb-3 border-bottom pb-2">Basic Information</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="small font-weight-bold">Vehicle Name</label>
                                                <input type="text" value="<?php echo $row->name; ?>" required class="form-control" id="v_name" name="v_name">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="small font-weight-bold">Registration Number</label>
                                                <div class="input-group">
                                                    <input type="text" value="<?php echo $row->reg_no; ?>" class="form-control" id="v_reg_no" name="v_reg_no" readonly required>
                                                    <div class="input-group-append">
                                                        <button class="btn btn-outline-secondary" type="button" onclick="enableRegEdit()"><i class="fas fa-pen"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="small font-weight-bold">Category</label>
                                                <select class="form-control custom-select" name="v_category" id="v_category">
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
                                                <label class="small font-weight-bold">Fuel Type</label>
                                                <select class="form-control custom-select" name="v_fuel_type" id="v_fuel_type">
                                                    <option value="PETROL" <?php if($row->fuel_type == 'PETROL') echo 'selected'; ?>>Petrol</option>
                                                    <option value="DIESEL" <?php if($row->fuel_type == 'DIESEL') echo 'selected'; ?>>Diesel</option>
                                                    <option value="CNG" <?php if($row->fuel_type == 'CNG') echo 'selected'; ?>>CNG</option>
                                                    <option value="ELECTRIC" <?php if($row->fuel_type == 'ELECTRIC') echo 'selected'; ?>>Electric</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="small font-weight-bold">Capacity (Seats)</label>
                                                <input type="number" min="1" value="<?php echo $row->capacity; ?>" class="form-control" id="v_capacity" name="v_capacity">
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
                                                    <option value="DEPARTMENT" <?php if(($row->ownership_type ?? 'DEPARTMENT') == 'DEPARTMENT') echo 'selected'; ?>>Departmental (Permanent)</option>
                                                    <option value="VENDOR" <?php if(($row->ownership_type ?? '') == 'VENDOR') echo 'selected'; ?>>Vendor Based (Contract)</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Vendor Specific Fields -->
                                    <div id="vendor-fields" style="display: <?php echo (($row->ownership_type ?? '') == 'VENDOR') ? 'block' : 'none'; ?>;" class="mt-3">
                                        <div class="bg-white p-3 rounded border">
                                            <h6 class="text-primary font-weight-bold mb-3 small text-uppercase">Vendor Information</h6>
                                            <div class="row mb-3">
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label class="small font-weight-bold">Vendor Name</label>
                                                        <input type="text" class="form-control form-control-sm" name="vendor_name" id="vendor_name" value="<?php echo $row->vendor_name ?? ''; ?>" placeholder="Enter vendor name">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label class="small font-weight-bold">Vendor Phone</label>
                                                        <input type="text" class="form-control form-control-sm" name="vendor_phone" id="vendor_phone" value="<?php echo $row->vendor_phone ?? ''; ?>" placeholder="Enter vendor phone">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label class="small font-weight-bold">Vendor Email</label>
                                                        <input type="email" class="form-control form-control-sm" name="vendor_email" id="vendor_email" value="<?php echo $row->vendor_email ?? ''; ?>" placeholder="Enter vendor email">
                                                    </div>
                                                </div>
                                                <div class="col-md-12">
                                                    <div class="form-group">
                                                        <label class="small font-weight-bold">Vendor Address</label>
                                                        <textarea class="form-control form-control-sm" name="vendor_addr" id="vendor_addr" rows="1" placeholder="Enter vendor address"><?php echo $row->vendor_addr ?? ''; ?></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <h6 class="text-primary font-weight-bold mb-3 small text-uppercase border-top pt-3">Contract Terms</h6>
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label class="small font-weight-bold">Start Date</label>
                                                        <input type="date" class="form-control form-control-sm" name="contract_start" id="contract_start" value="<?php echo $row->contract_start_date ?? ''; ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label class="small font-weight-bold">End Date</label>
                                                        <input type="date" class="form-control form-control-sm" name="contract_end" id="contract_end" value="<?php echo $row->contract_end_date ?? ''; ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label class="small font-weight-bold">Budget</label>
                                                        <input type="number" step="0.01" class="form-control form-control-sm" name="contract_budget" id="contract_budget" value="<?php echo $row->contract_budget ?? ''; ?>" placeholder="0.00">
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label class="small font-weight-bold">Status</label>
                                                        <select class="form-control form-control-sm custom-select" name="contract_status" id="contract_status">
                                                            <option value="ACTIVE" <?php if(($row->contract_status ?? '') == 'ACTIVE') echo 'selected'; ?>>Active</option>
                                                            <option value="EXPIRED" <?php if(($row->contract_status ?? '') == 'EXPIRED') echo 'selected'; ?>>Expired</option>
                                                            <option value="TERMINATED" <?php if(($row->contract_status ?? '') == 'TERMINATED') echo 'selected'; ?>>Terminated</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Section: Status & Image -->
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-body p-4">
                                    <h6 class="text-uppercase text-muted small font-weight-bold mb-3 border-bottom pb-2">Status & Media</h6>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="small font-weight-bold">Current Status</label>
                                                <select class="form-control custom-select" name="v_status" id="v_status">
                                                    <option value="AVAILABLE" <?php if($row->status == 'AVAILABLE') echo 'selected'; ?>>Available</option>
                                                    <option value="IN_SERVICE" <?php if($row->status == 'IN_SERVICE') echo 'selected'; ?>>In Service</option>
                                                    <option value="MAINTENANCE" <?php if($row->status == 'MAINTENANCE') echo 'selected'; ?>>Maintenance</option>
                                                    <option value="RETIRED" <?php if($row->status == 'RETIRED') echo 'selected'; ?>>Retired</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="small font-weight-bold">Vehicle Image</label>
                                                <div class="d-flex align-items-center">
                                                    <img src="../vendor/img/<?php echo !empty($row->image) ? $row->image : 'placeholder.png'; ?>" class="img-thumbnail shadow-sm mr-3" style="width: 100px; height: 70px; object-fit: cover;">
                                                    <div class="custom-file">
                                                        <input type="file" class="custom-file-input" id="v_dpic" name="v_dpic">
                                                        <label class="custom-file-label" for="v_dpic">Change...</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-center mt-4 mb-4">
                                <button type="submit" name="update_veh" class="btn btn-success btn-lg px-5 font-weight-bold shadow-sm rounded-pill">
                                    <i class="fas fa-save mr-2"></i> Update Vehicle
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
    
    // Update file input label
    $('.custom-file-input').on('change', function() {
        let fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').addClass("selected").html(fileName);
    });
</script>

</body>

</html>
