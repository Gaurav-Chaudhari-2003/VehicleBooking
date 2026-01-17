<?php
global $mysqli;
session_start();
include('../DATABASE FILE/config.php');
include('../DATABASE FILE/checklogin.php');
check_login();
$aid = $_SESSION['a_id'];
$projectFolder = '/' . basename(dirname(__DIR__)) . '/';

if (isset($_POST['approve_booking'])) {
    $booking_id = $_GET['booking_id'];
    $booking_status = $_POST['approve_booking']; // takes value from clicked button (APPROVED or REJECTED)
    
    $status_enum = strtoupper($booking_status);
    if ($status_enum == 'CANCELLED') $status_enum = 'REJECTED'; // Admin usually rejects.
    if ($booking_status == 'Approved') $status_enum = 'APPROVED';

    $admin_remarks = $_POST['admin_remarks'];
    $driver_id = isset($_POST['driver_id']) && !empty($_POST['driver_id']) ? $_POST['driver_id'] : null;
    
    // Capture editable fields
    $from_datetime = $_POST['from_datetime'];
    $to_datetime = $_POST['to_datetime'];
    
    // Simple check if it's just a date (length 10: YYYY-MM-DD)
    if (strlen($from_datetime) == 10) $from_datetime .= ' 00:00:00';
    if (strlen($to_datetime) == 10) $to_datetime .= ' 23:59:59'; // Assuming 'To' implies inclusive end of day

    $pickup_location = $_POST['pickup_location'];
    $drop_location = $_POST['drop_location'];

    $conflict_found = false;

    // VALIDATION: Check if driver is selected for approval
    if ($status_enum === 'APPROVED' && empty($driver_id)) {
        $conflict_found = true;
        $err = "Cannot approve! Please assign a valid driver.";
    }

    // CONFLICT CHECK: Before approving, check if the dates conflict with any other APPROVED booking for the same vehicle
    if (!$conflict_found && $status_enum === 'APPROVED') {
        // 1. Vehicle Conflict Check
        $v_stmt = $mysqli->prepare("SELECT vehicle_id FROM bookings WHERE id = ?");
        $v_stmt->bind_param('i', $booking_id);
        $v_stmt->execute();
        $v_res = $v_stmt->get_result();
        if ($v_row = $v_res->fetch_assoc()) {
            $vehicle_id = $v_row['vehicle_id'];
            
            // Check for overlaps with other APPROVED bookings for the same vehicle
            $conflict_query = "SELECT id FROM bookings 
                               WHERE vehicle_id = ? 
                                 AND status = 'APPROVED' 
                                 AND id != ? 
                                 AND (from_datetime <= ? AND to_datetime >= ?)";
            
            $c_stmt = $mysqli->prepare($conflict_query);
            $c_stmt->bind_param('iiss', $vehicle_id, $booking_id, $to_datetime, $from_datetime);
            $c_stmt->execute();
            $c_stmt->store_result();
            
            if ($c_stmt->num_rows > 0) {
                $conflict_found = true;
                $err = "Cannot approve! This vehicle is already booked (Approved) for the selected dates.";
            }
        }

        // 2. Driver Conflict Check (if a driver is assigned)
        if (!$conflict_found && $driver_id) {
            // Check if this driver is assigned to any other APPROVED booking that overlaps with these dates
            // Logic: Same driver, APPROVED status, different booking ID, overlapping dates
            $driver_conflict_query = "SELECT id FROM bookings 
                                      WHERE driver_id = ? 
                                        AND status = 'APPROVED' 
                                        AND id != ? 
                                        AND (from_datetime <= ? AND to_datetime >= ?)";
            
            $dc_stmt = $mysqli->prepare($driver_conflict_query);
            $dc_stmt->bind_param('iiss', $driver_id, $booking_id, $to_datetime, $from_datetime);
            $dc_stmt->execute();
            $dc_stmt->store_result();
            
            if ($dc_stmt->num_rows > 0) {
                $conflict_found = true;
                $err = "Cannot approve! The selected driver is already assigned to another vehicle for these dates.";
            }
        }
    }

    if (!$conflict_found) {
        // 1. Update the status of the current booking
        // Also update driver_id and editable fields if provided
        $query = "UPDATE bookings SET status = ?, driver_id = ?, from_datetime = ?, to_datetime = ?, pickup_location = ?, drop_location = ? WHERE id = ?";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('sissssi', $status_enum, $driver_id, $from_datetime, $to_datetime, $pickup_location, $drop_location, $booking_id);
        $stmt->execute();

        if ($stmt) {
            // Insert Admin Remark
            if (!empty($admin_remarks)) {
                $entity_type = 'BOOKING';
                $remark_query = "INSERT INTO entity_remarks (entity_type, entity_id, user_id, remark) VALUES (?, ?, ?, ?)";
                $remark_stmt = $mysqli->prepare($remark_query);
                $remark_stmt->bind_param('siis', $entity_type, $booking_id, $aid, $admin_remarks);
                $remark_stmt->execute();
                
                // Update last_remark_id in bookings table
                $last_remark_id = $mysqli->insert_id;
                $update_remark_id = $mysqli->prepare("UPDATE bookings SET last_remark_id = ? WHERE id = ?");
                $update_remark_id->bind_param('ii', $last_remark_id, $booking_id);
                $update_remark_id->execute();
            }

            // 2. If the booking is APPROVED, automatically reject conflicting PENDING bookings
            if ($status_enum === 'APPROVED') {
                // Fetch details of the approved booking to get vehicle_id and dates
                $detailsQuery = "SELECT vehicle_id FROM bookings WHERE id = ?";
                $detailsStmt = $mysqli->prepare($detailsQuery);
                $detailsStmt->bind_param('i', $booking_id);
                $detailsStmt->execute();
                $detailsResult = $detailsStmt->get_result();
                
                if ($row = $detailsResult->fetch_assoc()) {
                    $vehicle_id = $row['vehicle_id'];

                    // Reject conflicting Pending bookings for the same vehicle
                    $rejectQuery = "UPDATE bookings 
                                    SET status = 'REJECTED'
                                    WHERE vehicle_id = ? 
                                      AND status = 'PENDING' 
                                      AND id != ? 
                                      AND (from_datetime <= ? AND to_datetime >= ?)";
                    
                    $rejectStmt = $mysqli->prepare($rejectQuery);
                    $rejectStmt->bind_param('iiss', $vehicle_id, $booking_id, $to_datetime, $from_datetime);
                    $rejectStmt->execute();
                }
            }

            // Log the operation
            $action = "Booking " . ucfirst(strtolower($status_enum));
            $log_remark = "Admin updated status to $status_enum. Remarks: $admin_remarks. Driver Assigned ID: " . ($driver_id ?? 'None');
            $entity_type = 'BOOKING';
            
            $hist_stmt = $mysqli->prepare("INSERT INTO operation_history (entity_type, entity_id, action, performed_by, remark) VALUES (?, ?, ?, ?, ?)");
            $hist_stmt->bind_param('sisis', $entity_type, $booking_id, $action, $aid, $log_remark);
            $hist_stmt->execute();

            // Optional: Set a flash message in session if needed
            $_SESSION['flash_success'] = "Booking has been " . strtolower($status_enum) . " successfully.";

            // Redirect to dashboard after short delay
            echo "<script>
            setTimeout(function() {
                window.location.href = 'admin-dashboard.php';
            }, 1500);
        </script>";
            exit(); // Kill the current page execution
        } else {
            $err = "Failed to update booking.";
        }
    }
}


?>

<!DOCTYPE html>
<html lang="en">
<?php include('vendor/inc/head.php'); ?>
<!-- Flatpickr CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
    /* Custom styles for Flatpickr */
    .flatpickr-day.pending {
        background-color: #ffc107 !important;
        color: black !important;
        border-color: #ffc107 !important;
    }
    .flatpickr-day.booked {
        background-color: #ff4d4d !important;
        color: white !important;
        border-color: #ff4d4d !important;
    }
    .flatpickr-day.overlap-restricted {
        background-color: #e0e0e0 !important;
        color: #aaaaaa !important;
        border-color: #e0e0e0 !important;
        cursor: not-allowed;
    }
    
    /* Driver Dropdown Styles */
    .driver-option-busy {
        background-color: #ffebee;
        color: #c62828;
    }
</style>

<body id="page-top" style="overflow-x: hidden; background-color: #f8f9fc;">
<div id="wrapper">

    <div id="content-wrapper">

        <div class="container-fluid p-3">

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
                        swal("Failed!", "<?php echo $err; ?>", "error");
                    }, 100);
                </script>
            <?php } ?>

            <?php
            $booking_id = $_GET['booking_id'];
            
            // Fetch Booking Details
            $ret = "SELECT b.id as booking_id, b.from_datetime, b.to_datetime, b.status, b.purpose, b.driver_id, b.pickup_location, b.drop_location, b.created_at, b.vehicle_id,
                           u.first_name, u.last_name, u.email, u.phone, u.address, 
                           v.name as v_name, v.category as v_category, v.reg_no as v_reg_no, v.capacity as v_capacity, v.fuel_type as v_fuel, v.image as v_image
                    FROM bookings b
                    JOIN users u ON b.user_id = u.id
                    JOIN vehicles v ON b.vehicle_id = v.id
                    WHERE b.id = ?";
            $stmt = $mysqli->prepare($ret);
            $stmt->bind_param('i', $booking_id);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res->fetch_object();
            $stmt->close();

            if ($row) {
                // Fetch Active Drivers
                $drivers = [];
                $driver_query = "SELECT d.id, u.first_name, u.last_name, d.license_no, d.experience_years 
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
                
                $vehicleImage = $projectFolder . 'vendor/img/' . ($row->v_image ?: 'placeholder.png');
                ?>
                
                <div class="card shadow border-0 rounded-lg">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom-0">
                        <h5 class="mb-0 font-weight-bold text-primary"><i class="fas fa-clipboard-check mr-2"></i> Review Booking #<?php echo $row->booking_id; ?></h5>
                        <a href="admin-dashboard.php" class="btn btn-sm btn-outline-secondary font-weight-bold rounded-pill px-3"><i class="fas fa-arrow-left mr-1"></i> Back to Dashboard</a>
                    </div>
                    <div class="card-body bg-light p-4">
                        <form method="POST">
                            <!-- Hidden input for vehicle ID to be used by JS -->
                            <input type="hidden" id="vehicle_id" value="<?php echo $row->vehicle_id; ?>">
                            <input type="hidden" id="current_booking_id" value="<?php echo $row->booking_id; ?>">
                            
                            <!-- Row 1: User and Vehicle/Trip -->
                            <div class="row">
                                <!-- User Details -->
                                <div class="col-lg-4 mb-3">
                                    <div class="card border-0 shadow-sm h-100">
                                        <div class="card-body p-3">
                                            <h6 class="text-uppercase text-muted small font-weight-bold mb-3 border-bottom pb-2">User Details</h6>
                                            <div class="d-flex align-items-center mb-3">
                                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mr-3" style="width: 40px; height: 40px;">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                                <div>
                                                    <div class="font-weight-bold text-dark"><?php echo $row->first_name . ' ' . $row->last_name; ?></div>
                                                    <div class="small text-muted"><?php echo $row->phone; ?></div>
                                                </div>
                                            </div>
                                            <div class="small text-muted mb-1"><i class="fas fa-envelope mr-2 text-gray-400"></i> <?php echo $row->email; ?></div>
                                            <div class="small text-muted"><i class="fas fa-map-marker-alt mr-2 text-gray-400"></i> <?php echo $row->address; ?></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Vehicle & Trip Details -->
                                <div class="col-lg-8 mb-3">
                                    <div class="card border-0 shadow-sm h-100">
                                        <div class="card-body p-3">
                                            <h6 class="text-uppercase text-muted small font-weight-bold mb-3 border-bottom pb-2">Vehicle & Trip</h6>
                                            
                                            <div class="row">
                                                <div class="col-md-5 border-right">
                                                    <div class="d-flex mb-3">
                                                        <img src="<?php echo $vehicleImage; ?>" class="rounded mr-3" style="width: 80px; height: 60px; object-fit: cover;">
                                                        <div>
                                                            <div class="font-weight-bold text-dark"><?php echo $row->v_name; ?></div>
                                                            <div class="small text-muted"><?php echo $row->v_reg_no; ?></div>
                                                            <div class="badge badge-light border mt-1"><?php echo $row->v_category; ?></div>
                                                        </div>
                                                    </div>
                                                    <div class="small text-muted">
                                                        <i class="fas fa-gas-pump mr-1"></i> <?php echo $row->v_fuel; ?> | 
                                                        <i class="fas fa-chair mr-1"></i> <?php echo $row->v_capacity; ?> Seats
                                                    </div>
                                                </div>
                                                
                                                <div class="col-md-7">
                                                    <div class="row small mb-2">
                                                        <div class="col-6">
                                                            <label class="text-muted mb-0 font-weight-bold">From</label>
                                                            <input type="text" id="from_datetime" name="from_datetime" class="form-control form-control-sm book-from-date" value="<?php echo date('Y-m-d', strtotime($row->from_datetime)); ?>">
                                                        </div>
                                                        <div class="col-6">
                                                            <label class="text-muted mb-0 font-weight-bold">To</label>
                                                            <input type="text" id="to_datetime" name="to_datetime" class="form-control form-control-sm book-to-date" value="<?php echo date('Y-m-d', strtotime($row->to_datetime)); ?>">
                                                        </div>
                                                    </div>

                                                    <div class="row small mb-2">
                                                        <div class="col-6">
                                                            <label class="text-muted mb-0 font-weight-bold">Pickup</label>
                                                            <input type="text" name="pickup_location" class="form-control form-control-sm" value="<?php echo htmlspecialchars($row->pickup_location); ?>">
                                                        </div>
                                                        <div class="col-6">
                                                            <label class="text-muted mb-0 font-weight-bold">Drop</label>
                                                            <input type="text" name="drop_location" class="form-control form-control-sm" value="<?php echo htmlspecialchars($row->drop_location); ?>">
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="bg-light p-2 rounded small text-muted mt-2 border text-truncate" title="<?php echo htmlspecialchars($row->purpose); ?>">
                                                        <i class="fas fa-quote-left mr-1 text-gray-400"></i> <?php echo htmlspecialchars($row->purpose); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Row 2: Action & Assignment -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="card border-0 shadow-sm mb-3">
                                        <div class="card-body p-3">
                                            <h6 class="text-uppercase text-muted small font-weight-bold mb-3 border-bottom pb-2">Action & Assignment</h6>
                                            
                                            <div class="row">
                                                <div class="col-md-4 border-right">
                                                    <div class="form-group mb-3">
                                                        <label class="small font-weight-bold mb-1">Assign Driver</label>
                                                        <select name="driver_id" id="driver_select" class="form-control form-control-sm custom-select">
                                                            <option value="">-- Select Driver --</option>
                                                            <?php foreach ($drivers as $driver): ?>
                                                                <option value="<?php echo $driver->id; ?>" class="driver-option" data-driver-id="<?php echo $driver->id; ?>" <?php echo ($row->driver_id == $driver->id) ? 'selected' : ''; ?>>
                                                                    <?php 
                                                                    echo htmlspecialchars($driver->first_name . ' ' . $driver->last_name);
                                                                    echo " (Exp: " . $driver->experience_years . "y)";
                                                                    ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <div id="driver_warning" class="text-danger small mt-1" style="display:none; font-weight:bold;"></div>
                                                    </div>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <span class="small text-muted">Current Status:</span>
                                                        <span class="badge badge-pill badge-<?php echo ($row->status == 'PENDING' ? 'warning' : ($row->status == 'APPROVED' ? 'success' : 'danger')); ?> px-3"><?php echo $row->status; ?></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-5 border-right">
                                                    <div class="form-group mb-0 h-100">
                                                        <label class="small font-weight-bold mb-1">Admin Remarks</label>
                                                        <textarea name="admin_remarks" class="form-control form-control-sm h-75" placeholder="Enter any remarks or notes for this booking..."></textarea>
                                                    </div>
                                                </div>

                                                <div class="col-md-3 d-flex flex-column justify-content-center">
                                                    <button type="submit" name="approve_booking" value="Approved" class="btn btn-success btn-sm btn-block font-weight-bold shadow-sm mb-2" onclick="return validateApprove()">
                                                        <i class="fas fa-check mr-1"></i> Approve / Update
                                                    </button>
                                                    <button type="submit" name="approve_booking" value="Cancelled" class="btn btn-outline-danger btn-sm btn-block font-weight-bold shadow-sm">
                                                        <i class="fas fa-times mr-1"></i> Reject
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            <?php } ?>

        </div>
    </div>
</div>

<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="vendor/datatables/jquery.dataTables.js"></script>
<script src="vendor/datatables/dataTables.bootstrap4.js"></script>
<script src="vendor/js/sb-admin.min.js"></script>
<script src="vendor/js/swal.js"></script>
<!-- Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
    function validateApprove() {
        var driver = document.getElementById("driver_select").value;
        if (driver == "") {
            swal("Error!", "Please assign a driver before approving.", "error");
            return false;
        }
        return true;
    }

    // Reusing logic from user-confirm-booking.php (adapted for admin)
    // Note: Paths to get-approved-dates.php need to be correct relative to admin folder.
    // Assuming get-approved-dates.php is in 'usr' folder, we need '../usr/get-approved-dates.php'
    
    async function fetchBookedDates(vehicleId) {
        const res = await fetch(`../usr/get-approved-dates.php?v_id=${vehicleId}`);
        return res.json();
    }

    async function fetchPendingDates(vehicleId) {
        const res = await fetch(`../usr/get-pending-dates.php?v_id=${vehicleId}`);
        return res.json();
    }
    
    async function fetchBusyDrivers(start, end, excludeId) {
        // Ensure we check the full day range
        let startFull = start;
        let endFull = end;
        
        if (start.length === 10) startFull += ' 00:00:00';
        if (end.length === 10) endFull += ' 23:59:59';
        
        const res = await fetch(`get-busy-drivers.php?start=${encodeURIComponent(startFull)}&end=${encodeURIComponent(endFull)}&exclude_id=${excludeId}`);
        return res.json();
    }

    function updateDriverAvailability(start, end) {
        const excludeId = document.getElementById('current_booking_id').value;
        const driverSelect = document.getElementById('driver_select');
        const warningMsg = document.getElementById('driver_warning');
        
        fetchBusyDrivers(start, end, excludeId).then(busyDriverIds => {
            const options = driverSelect.querySelectorAll('option');
            let selectedIsBusy = false;
            
            options.forEach(option => {
                const driverId = parseInt(option.value);
                if (driverId && busyDriverIds.includes(driverId)) {
                    option.classList.add('driver-option-busy');
                    option.title = "Driver is already assigned to another vehicle for these dates.";
                    if (option.selected) selectedIsBusy = true;
                } else {
                    option.classList.remove('driver-option-busy');
                    option.title = "";
                }
            });
            
            if (selectedIsBusy) {
                warningMsg.style.display = 'block';
                warningMsg.textContent = "Warning: Selected driver is already assigned to another vehicle for these dates.";
            } else {
                warningMsg.style.display = 'none';
            }
        });
    }

    function buildFlatpickrOptions(approvedRanges, pendingRanges, minDate) {
        // Admin override: No disabled dates
        // We still want to show the visual indicators (colors) but allow selection.
        // So we pass an empty array to 'disable'.
        
        const options = {
            enableTime: false,
            dateFormat: "Y-m-d",
            minDate: minDate,
            disable: [], // Empty array = no disabled dates
            onDayCreate: function(dObj, dStr, fp, dayElem) {
                const date = dayElem.dateObj;
                const dateString = flatpickr.formatDate(date, "Y-m-d");

                // Check if date is in approved ranges
                for (const range of approvedRanges) {
                    if (dateString >= range.book_from_date && dateString <= range.book_to_date) {
                        dayElem.classList.add("booked");
                        dayElem.title = "Car already booked and approved";
                        break;
                    }
                }

                // Check if date is in pending ranges
                for (const range of pendingRanges) {
                    if (dateString >= range.book_from_date && dateString <= range.book_to_date) {
                        dayElem.classList.add("pending");
                        dayElem.title = "Car pending approval";
                        break;
                    }
                }
            }
        };

        return options;
    }

    function initDatePickers() {
        const vehicleId = document.getElementById('vehicle_id').value;
        const fromInput = document.getElementById('from_datetime');
        const toInput = document.getElementById('to_datetime');

        if (!vehicleId || !fromInput || !toInput) return;

        // Calculate date 15 days ago (or just use today for admin flexibility)
        const today = new Date();
        const pastDate = new Date(today);
        pastDate.setDate(today.getDate() - 30); // Allow admin to see/edit past bookings more freely
        const minDateStr = pastDate.toISOString().split('T')[0];
        
        // Initial check for driver availability based on loaded dates
        if (fromInput.value && toInput.value) {
            updateDriverAvailability(fromInput.value, toInput.value);
        }

        Promise.all([fetchBookedDates(vehicleId), fetchPendingDates(vehicleId)]).then(([approvedRanges, pendingRanges]) => {
            // Sort approvedRanges by date
            approvedRanges.sort((a, b) => new Date(a.book_from_date) - new Date(b.book_from_date));

            const fromPicker = flatpickr(fromInput, buildFlatpickrOptions(approvedRanges, pendingRanges, minDateStr));
            
            // Initialize To Date picker with similar options
            // Note: Admin might want to override overlaps, but visual cues are helpful.
            // We'll use the same logic but maybe less restrictive on 'disable' if admin needs to force.
            // For now, keeping it consistent with user view to prevent double booking errors.
            
            const toOptions = buildFlatpickrOptions(approvedRanges, pendingRanges, minDateStr);
            const toPicker = flatpickr(toInput, toOptions);

            // Update To Date minDate when From Date changes
            fromPicker.config.onChange.push(function(selectedDates, dateStr) {
                if (selectedDates.length > 0) {
                    toPicker.set('minDate', dateStr);
                    // Check driver availability when dates change
                    const toDate = toInput.value;
                    if (toDate) {
                         updateDriverAvailability(dateStr, toDate);
                    }
                }
            });
            
            toPicker.config.onChange.push(function(selectedDates, dateStr) {
                const fromDate = fromInput.value;
                if (fromDate) {
                    updateDriverAvailability(fromDate, dateStr);
                }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        initDatePickers();
        
        // Add listener for driver selection change
        document.getElementById('driver_select').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const warningMsg = document.getElementById('driver_warning');
            if (selectedOption.classList.contains('driver-option-busy')) {
                 warningMsg.style.display = 'block';
                 warningMsg.textContent = "Warning: Selected driver is already assigned to another vehicle for these dates.";
            } else {
                 warningMsg.style.display = 'none';
            }
        });
    });
</script>

</body>
</html>
