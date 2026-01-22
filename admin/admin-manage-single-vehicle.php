<?php
session_start();
include('../DATABASE FILE/config.php');
include('../DATABASE FILE/checklogin.php');
check_login();
$aid = $_SESSION['a_id'];

$succ = $err = "";

// Update Vehicle Logic
if (isset($_POST['update_veh'])) {
    $v_id = $_GET['v_id'];
    $v_name     = $_POST['v_name'];
    $v_reg_no   = $_POST['v_reg_no'];
    $v_category = $_POST['v_category'];
    $v_fuel_type = $_POST['v_fuel_type'];
    $v_capacity = $_POST['v_capacity'];
    $v_status   = $_POST['v_status'];
    $v_remark   = trim($_POST['v_remark']);
    
    // Vendor & Contract Details
    $v_ownership = $_POST['v_ownership'];
    
    $vendor_name = ($v_ownership === 'VENDOR') ? trim($_POST['vendor_name']) : null;
    $vendor_phone = ($v_ownership === 'VENDOR') ? trim($_POST['vendor_phone']) : null;
    $vendor_email = ($v_ownership === 'VENDOR') ? trim($_POST['vendor_email']) : null;
    $vendor_addr = ($v_ownership === 'VENDOR') ? trim($_POST['vendor_addr']) : null;
    
    $contract_start = ($v_ownership === 'VENDOR') ? $_POST['contract_start'] : null;
    $contract_end = ($v_ownership === 'VENDOR') ? $_POST['contract_end'] : null;
    $contract_budget = ($v_ownership === 'VENDOR') ? $_POST['contract_budget'] : 0.00;
    $contract_status = ($v_ownership === 'VENDOR') ? $_POST['contract_status'] : 'ACTIVE';

    global $mysqli;
    
    // Check if reg no exists for another vehicle
    $check_stmt = $mysqli->prepare("SELECT id FROM vehicles WHERE reg_no = ? AND id != ?");
    $check_stmt->bind_param('si', $v_reg_no, $v_id);
    $check_stmt->execute();
    $check_stmt->store_result();
    
    if ($check_stmt->num_rows > 0) {
        $err = "Vehicle with this Registration Number already exists!";
    } else {
        // Handle Image Upload
        $v_dpic = $_POST['current_image']; // Default to existing
        if (isset($_FILES["v_dpic"]) && $_FILES["v_dpic"]["error"] === 0) {
            $check = getimagesize($_FILES["v_dpic"]["tmp_name"]);
            if ($check !== false) {
                $target_dir = "../vendor/img/";
                $new_img_name = basename($_FILES["v_dpic"]["name"]);
                $target_file = $target_dir . $new_img_name;
                if (move_uploaded_file($_FILES["v_dpic"]["tmp_name"], $target_file)) {
                    $v_dpic = $new_img_name;
                }
            }
        }

        // Update Vehicle Table
        $query = "UPDATE vehicles SET name=?, reg_no=?, category=?, fuel_type=?, capacity=?, status=?, image=?, ownership_type=? WHERE id=?";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('ssssisssi', $v_name, $v_reg_no, $v_category, $v_fuel_type, $v_capacity, $v_status, $v_dpic, $v_ownership, $v_id);
        
        if ($stmt->execute()) {
            // Handle Vendor & Contract
            if ($v_ownership === 'VENDOR') {
                // Check/Create Vendor
                $vendor_id = null;
                $check_vendor = $mysqli->prepare("SELECT id FROM vendors WHERE name = ?");
                $check_vendor->bind_param('s', $vendor_name);
                $check_vendor->execute();
                $check_vendor->store_result();
                
                if ($check_vendor->num_rows > 0) {
                    $check_vendor->bind_result($vendor_id);
                    $check_vendor->fetch();
                    // Update vendor details
                    $up_vendor = $mysqli->prepare("UPDATE vendors SET phone=?, email=?, address=? WHERE id=?");
                    $up_vendor->bind_param('sssi', $vendor_phone, $vendor_email, $vendor_addr, $vendor_id);
                    $up_vendor->execute();
                    $up_vendor->close();
                } else {
                    $create_vendor = $mysqli->prepare("INSERT INTO vendors (name, phone, email, address) VALUES (?, ?, ?, ?)");
                    $create_vendor->bind_param('ssss', $vendor_name, $vendor_phone, $vendor_email, $vendor_addr);
                    $create_vendor->execute();
                    $vendor_id = $create_vendor->insert_id;
                    $create_vendor->close();
                }
                $check_vendor->close();
                
                // Update/Create Contract
                $check_contract = $mysqli->prepare("SELECT id FROM vehicle_contracts WHERE vehicle_id = ?");
                $check_contract->bind_param('i', $v_id);
                $check_contract->execute();
                $check_contract->store_result();
                
                if ($check_contract->num_rows > 0) {
                    $up_contract = $mysqli->prepare("UPDATE vehicle_contracts SET vendor_id=?, contract_start_date=?, contract_end_date=?, contract_budget=?, contract_status=? WHERE vehicle_id=?");
                    $up_contract->bind_param('issdsi', $vendor_id, $contract_start, $contract_end, $contract_budget, $contract_status, $v_id);
                    $up_contract->execute();
                    $up_contract->close();
                } else {
                    $create_contract = $mysqli->prepare("INSERT INTO vehicle_contracts (vehicle_id, vendor_id, contract_start_date, contract_end_date, contract_budget, contract_status) VALUES (?, ?, ?, ?, ?, ?)");
                    $create_contract->bind_param('iissds', $v_id, $vendor_id, $contract_start, $contract_end, $contract_budget, $contract_status);
                    $create_contract->execute();
                    $create_contract->close();
                }
                $check_contract->close();
            }
            
            // Insert Remark if provided
            if (!empty($v_remark)) {
                $remark_stmt = $mysqli->prepare("INSERT INTO entity_remarks (entity_type, entity_id, user_id, remark) VALUES ('VEHICLE', ?, ?, ?)");
                $remark_stmt->bind_param('iis', $v_id, $aid, $v_remark);
                $remark_stmt->execute();
                
                $last_remark_id = $remark_stmt->insert_id;
                $update_veh = $mysqli->prepare("UPDATE vehicles SET last_remark_id = ? WHERE id = ?");
                $update_veh->bind_param('ii', $last_remark_id, $v_id);
                $update_veh->execute();
                $update_veh->close();
                $remark_stmt->close();
            }
            
            $succ = "Vehicle Updated Successfully";
        } else {
            $err = "Update Failed. Try Again Later.";
        }
        $stmt->close();
    }
    $check_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Review Booking | Vehicle Booking System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Include Global Theme -->
    <?php include("../vendor/inc/theme-config.php"); ?>

    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <!-- Leaflet Control Geocoder CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
    <!-- Leaflet Routing Machine CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.css" />

    <style>
        body { background-color: #fff; }
        .main-content { flex: 1; padding: 30px; margin-left: 260px; background-color: #f8f9fa; }

        .card { border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .card-header { background-color: #fff; border-bottom: 1px solid #eee; padding: 15px 20px; border-radius: 15px 15px 0 0 !important; font-weight: 700; color: var(--primary-color); }

        /* Form Styles */
        .form-control, .form-select { border-radius: 10px; padding: 10px 15px; border: 1px solid #ddd; }
        .form-control:focus, .form-select:focus { box-shadow: 0 0 0 3px rgba(0, 121, 107, 0.1); border-color: var(--secondary-color); }

        .card:hover {
            transform: translateY(-2px);
        }

        .card img {
            background: #f8f9fa;
        }

    </style>
</head>

<body id="page-top" style="background-color: #f8f9fc;">

<div id="wrapper">
    <!-- Main Content -->
    <div class="main-content">
        <!-- Sidebar -->
        <?php include("vendor/inc/sidebar.php"); ?>
        <div class="container-fluid mt-4">

            <?php if ($succ) { ?>
                <script>
                    setTimeout(function () {
                        swal("Success!", "<?php echo $succ; ?>", "success");
                    }, 100);
                </script>
            <?php } ?>

            <?php if ($err) { ?>
                <script>
                    setTimeout(function () {
                        swal("Failed!", "<?php echo $err; ?>", "error");
                    }, 100);
                </script>
            <?php } ?>

            <div class="card shadow border-0 rounded-lg mb-4">
                <div class="card-header bg-white py-3 d-flex align-items-center border-bottom-0">
                    <a href="javascript:void(0);" onclick="window.location.replace('admin-view-vehicle.php')" class="btn btn-sm btn-outline-secondary font-weight-bold rounded-pill px-3 mr-3"><i class="fas fa-arrow-left mr-1"></i> Back</a>
                    <h5 class="mb-0 font-weight-bold text-primary flex-grow-1 text-center" style="margin-right: 80px;">
                        <i class="fas fa-edit mr-2"></i> Update Vehicle Details
                    </h5>
                </div>

                <div class="card-body bg-light p-4">
                    <?php
                    $v_id = $_GET['v_id'];
                    // Fetch vehicle details with contract info
                    $ret = "SELECT v.*, vc.contract_start_date, vc.contract_end_date, vc.contract_budget, vc.contract_status, ven.name as vendor_name, ven.phone as vendor_phone, ven.email as vendor_email, ven.address as vendor_addr
                            FROM vehicles v 
                            LEFT JOIN vehicle_contracts vc ON v.id = vc.vehicle_id 
                            LEFT JOIN vendors ven ON vc.vendor_id = ven.id
                            WHERE v.id=?";
                    $stmt = $mysqli->prepare($ret);
                    $stmt->bind_param('i', $v_id);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    while ($row = $res->fetch_object()) {
                        $img = !empty($row->image) ? "../vendor/img/" . $row->image : "../vendor/img/placeholder.png";
                    ?>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="current_image" value="<?php echo $row->image; ?>">
                            
                            <div class="row">
                                <!-- Left Column: Basic Info -->
                                <div class="col-lg-6 mb-4">
                                    <div class="card border-0 shadow-sm h-100">
                                        <div class="card-body p-4">
                                            <h6 class="text-uppercase text-muted small font-weight-bold mb-3 border-bottom pb-2">Basic Information</h6>
                                            
                                            <div class="text-center mb-4">
                                                <img src="<?php echo $img; ?>" class="img-fluid rounded shadow-sm" style="max-height: 150px; object-fit: cover;">
                                            </div>

                                            <div class="form-row">
                                                <div class="form-group col-md-6">
                                                    <label class="small font-weight-bold">Vehicle Name</label>
                                                    <input type="text" required class="form-control" name="v_name" value="<?php echo $row->name; ?>">
                                                </div>
                                                <div class="form-group col-md-6">
                                                    <label class="small font-weight-bold">Registration Number</label>
                                                    <input type="text" class="form-control" name="v_reg_no" value="<?php echo $row->reg_no; ?>">
                                                </div>
                                            </div>

                                            <div class="form-row">
                                                <div class="form-group col-md-6">
                                                    <label class="small font-weight-bold">Category</label>
                                                    <select class="form-control custom-select" name="v_category">
                                                        <option value="SEDAN" <?php if($row->category == 'SEDAN') echo 'selected'; ?>>Sedan</option>
                                                        <option value="SUV" <?php if($row->category == 'SUV') echo 'selected'; ?>>SUV</option>
                                                        <option value="BUS" <?php if($row->category == 'BUS') echo 'selected'; ?>>Bus</option>
                                                        <option value="TRUCK" <?php if($row->category == 'TRUCK') echo 'selected'; ?>>Truck</option>
                                                        <option value="VAN" <?php if($row->category == 'VAN') echo 'selected'; ?>>Van</option>
                                                    </select>
                                                </div>
                                                <div class="form-group col-md-6">
                                                    <label class="small font-weight-bold">Fuel Type</label>
                                                    <select class="form-control custom-select" name="v_fuel_type">
                                                        <option value="PETROL" <?php if($row->fuel_type == 'PETROL') echo 'selected'; ?>>Petrol</option>
                                                        <option value="DIESEL" <?php if($row->fuel_type == 'DIESEL') echo 'selected'; ?>>Diesel</option>
                                                        <option value="CNG" <?php if($row->fuel_type == 'CNG') echo 'selected'; ?>>CNG</option>
                                                        <option value="ELECTRIC" <?php if($row->fuel_type == 'ELECTRIC') echo 'selected'; ?>>Electric</option>
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <div class="form-row">
                                                <div class="form-group col-md-6">
                                                    <label class="small font-weight-bold">Capacity</label>
                                                    <input type="number" min="1" class="form-control" name="v_capacity" value="<?php echo $row->capacity; ?>">
                                                </div>
                                                <div class="form-group col-md-6">
                                                    <label class="small font-weight-bold">Status</label>
                                                    <select class="form-control custom-select" name="v_status">
                                                        <option value="AVAILABLE" <?php if($row->status == 'AVAILABLE') echo 'selected'; ?>>Available</option>
                                                        <option value="BOOKED" <?php if($row->status == 'BOOKED') echo 'selected'; ?>>Booked</option>
                                                        <option value="MAINTENANCE" <?php if($row->status == 'MAINTENANCE') echo 'selected'; ?>>Maintenance</option>
                                                        <option value="RETIRED" <?php if($row->status == 'RETIRED') echo 'selected'; ?>>Retired</option>
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label class="small font-weight-bold">Update Image</label>
                                                <div class="custom-file">
                                                    <input type="file" class="custom-file-input" id="v_dpic" name="v_dpic" accept="image/*">
                                                    <label class="custom-file-label" for="v_dpic">Choose file...</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Right Column: Ownership & Contract -->
                                <div class="col-lg-6 mb-4">
                                    <div class="card border-0 shadow-sm h-100">
                                        <div class="card-body p-4">
                                            <h6 class="text-uppercase text-muted small font-weight-bold mb-3 border-bottom pb-2">Ownership & Contract</h6>
                                            
                                            <div class="form-group">
                                                <label class="small font-weight-bold">Ownership Type</label>
                                                <select class="form-control custom-select" name="v_ownership" id="v_ownership" onchange="toggleVendorFields()">
                                                    <option value="DEPARTMENT" <?php if($row->ownership_type == 'DEPARTMENT') echo 'selected'; ?>>Departmental (Permanent)</option>
                                                    <option value="VENDOR" <?php if($row->ownership_type == 'VENDOR') echo 'selected'; ?>>Vendor Based (Contract)</option>
                                                </select>
                                            </div>

                                            <!-- Vendor Specific Fields -->
                                            <div id="vendor-fields" style="display: <?php echo ($row->ownership_type == 'VENDOR') ? 'block' : 'none'; ?>;" class="mt-3">
                                                <div class="bg-light p-3 rounded border">
                                                    <h6 class="text-primary font-weight-bold mb-3 small text-uppercase">Vendor Information</h6>
                                                    <div class="form-row">
                                                        <div class="form-group col-md-6">
                                                            <label class="small font-weight-bold">Vendor Name</label>
                                                            <input type="text" class="form-control form-control-sm" name="vendor_name" value="<?php echo $row->vendor_name; ?>">
                                                        </div>
                                                        <div class="form-group col-md-6">
                                                            <label class="small font-weight-bold">Vendor Phone</label>
                                                            <input type="text" class="form-control form-control-sm" name="vendor_phone" value="<?php echo $row->vendor_phone; ?>">
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="small font-weight-bold">Vendor Email</label>
                                                        <input type="email" class="form-control form-control-sm" name="vendor_email" value="<?php echo $row->vendor_email; ?>">
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="small font-weight-bold">Vendor Address</label>
                                                        <textarea class="form-control form-control-sm" name="vendor_addr" rows="1"><?php echo $row->vendor_addr; ?></textarea>
                                                    </div>

                                                    <h6 class="text-primary font-weight-bold mb-3 small text-uppercase border-top pt-3">Contract Terms</h6>
                                                    <div class="form-row">
                                                        <div class="form-group col-md-6">
                                                            <label class="small font-weight-bold">Start Date</label>
                                                            <input type="date" class="form-control form-control-sm" name="contract_start" value="<?php echo $row->contract_start_date; ?>">
                                                        </div>
                                                        <div class="form-group col-md-6">
                                                            <label class="small font-weight-bold">End Date</label>
                                                            <input type="date" class="form-control form-control-sm" name="contract_end" value="<?php echo $row->contract_end_date; ?>">
                                                        </div>
                                                    </div>
                                                    <div class="form-row">
                                                        <div class="form-group col-md-6">
                                                            <label class="small font-weight-bold">Budget</label>
                                                            <input type="number" step="0.01" class="form-control form-control-sm" name="contract_budget" value="<?php echo $row->contract_budget; ?>">
                                                        </div>
                                                        <div class="form-group col-md-6">
                                                            <label class="small font-weight-bold">Contract Status</label>
                                                            <select class="form-control form-control-sm custom-select" name="contract_status">
                                                                <option value="ACTIVE" <?php if($row->contract_status == 'ACTIVE') echo 'selected'; ?>>Active</option>
                                                                <option value="EXPIRED" <?php if($row->contract_status == 'EXPIRED') echo 'selected'; ?>>Expired</option>
                                                                <option value="TERMINATED" <?php if($row->contract_status == 'TERMINATED') echo 'selected'; ?>>Terminated</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Remarks Section -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="card border-0 shadow-sm">
                                        <div class="card-body p-4">
                                            <h6 class="text-uppercase text-muted small font-weight-bold mb-3 border-bottom pb-2">Admin Remarks</h6>
                                            <div class="form-group mb-0">
                                                <textarea name="v_remark" class="form-control" rows="3" placeholder="Enter any update notes..."></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group text-center mt-4 mb-5">
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

<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="vendor/js/sb-admin.min.js"></script>
<script src="vendor/js/swal.js"></script>

<script>
    function toggleVendorFields() {
        var ownership = document.getElementById("v_ownership").value;
        var vendorFields = document.getElementById("vendor-fields");
        
        if (ownership === "VENDOR") {
            vendorFields.style.display = "block";
        } else {
            vendorFields.style.display = "none";
        }
    }
    
    // File input label update
    $(".custom-file-input").on("change", function() {
        var fileName = $(this).val().split("\\").pop();
        $(this).siblings(".custom-file-label").addClass("selected").html(fileName);
    });
</script>
<style>
    .main-content { flex: 1; padding: 30px; margin-left: 260px; background-color: #f8f9fa; }
</style>

</body>

</html>
