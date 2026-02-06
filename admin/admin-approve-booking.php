<?php
global $mysqli;

use includes\BrevoMailer;
use includes\MailTemplates;

session_start();
include('../DATABASE FILE/config.php');
include('../DATABASE FILE/checklogin.php');
require_once '../includes/BrevoMailer.php';
require_once '../includes/MailTemplates.php';

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
    $driver_id = !empty($_POST['driver_id']) ? $_POST['driver_id'] : null;

    // Capture editable fields
    $from_datetime = $_POST['from_datetime'];
    $to_datetime = $_POST['to_datetime'];

    if (strlen($to_datetime) == 10) $to_datetime .= ' 23:59:59';

    $pickup_location = $_POST['pickup_location'];
    $drop_location = $_POST['drop_location'];

    $conflict_found = false;

    // VALIDATION: Check if a driver is selected for approval
    if ($status_enum === 'APPROVED' && empty($driver_id)) {
        $conflict_found = true;
        $err = "Cannot approve! Please assign a valid driver.";
    }

    // CONFLICT CHECK
    if (!$conflict_found && $status_enum === 'APPROVED') {
        // 1. Vehicle Conflict Check
        $v_stmt = $mysqli->prepare("SELECT vehicle_id FROM bookings WHERE id = ?");
        $v_stmt->bind_param('i', $booking_id);
        $v_stmt->execute();
        $v_res = $v_stmt->get_result();
        if ($v_row = $v_res->fetch_assoc()) {
            $vehicle_id = $v_row['vehicle_id'];

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

        // 2. Driver Conflict Check
        if (!$conflict_found && $driver_id) {
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
        $query = "UPDATE bookings SET status = ?, driver_id = ?, from_datetime = ?, to_datetime = ?, pickup_location = ?, drop_location = ? WHERE id = ?";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('sissssi', $status_enum, $driver_id, $from_datetime, $to_datetime, $pickup_location, $drop_location, $booking_id);
        $stmt->execute();

        if ($stmt->affected_rows >= 0) {

            // Insert Admin Remark
            if (!empty($admin_remarks)) {
                $entity_type = 'BOOKING';
                $remark_query = "INSERT INTO entity_remarks (entity_type, entity_id, user_id, remark) VALUES (?, ?, ?, ?)";
                $remark_stmt = $mysqli->prepare($remark_query);
                $remark_stmt->bind_param('siis', $entity_type, $booking_id, $aid, $admin_remarks);
                $remark_stmt->execute();

                $last_remark_id = $mysqli->insert_id;
                $update_remark_id = $mysqli->prepare("UPDATE bookings SET last_remark_id = ? WHERE id = ?");
                $update_remark_id->bind_param('ii', $last_remark_id, $booking_id);
                $update_remark_id->execute();
            }

            // Reject conflicting PENDING bookings
            if ($status_enum === 'APPROVED') {
                $detailsQuery = "SELECT vehicle_id FROM bookings WHERE id = ?";
                $detailsStmt = $mysqli->prepare($detailsQuery);
                $detailsStmt->bind_param('i', $booking_id);
                $detailsStmt->execute();
                $detailsResult = $detailsStmt->get_result();

                if ($row = $detailsResult->fetch_assoc()) {
                    $vehicle_id = $row['vehicle_id'];
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

            // Log operation
            $action = "Booking " . ucfirst(strtolower($status_enum));
            $log_remark = "Admin updated status to $status_enum. Remarks: $admin_remarks. Driver Assigned ID: " . ($driver_id ?? 'None');
            $entity_type = 'BOOKING';

            $hist_stmt = $mysqli->prepare("INSERT INTO operation_history (entity_type, entity_id, action, performed_by, remark) VALUES (?, ?, ?, ?, ?)");
            $hist_stmt->bind_param('sisis', $entity_type, $booking_id, $action, $aid, $log_remark);
            $hist_stmt->execute();

            $_SESSION['flash_success'] = "Booking has been " . strtolower($status_enum) . " successfully.";

            // ===== FETCH FULL DETAILS FOR MAIL =====
            $q = $mysqli->prepare("
                SELECT
                    b.from_datetime,
                    b.to_datetime,
                    b.pickup_location,
                    b.drop_location,
                    b.purpose,
                
                    u.first_name AS uf,
                    u.last_name  AS ul,
                    u.email      AS uemail,
                    u.phone      AS uphone,
                
                    v.name       AS vehicle,
                    v.reg_no     AS v_reg_no,
                    v.image      AS v_image,
                
                    d.user_id    AS duid,
                
                    ud.first_name AS df,
                    ud.last_name  AS dl,
                    ud.email      AS demail,
                    ud.phone      AS dphone
                
                FROM bookings b
                JOIN users u      ON b.user_id = u.id
                JOIN vehicles v   ON b.vehicle_id = v.id
                
                LEFT JOIN drivers d  ON b.driver_id = d.id
                LEFT JOIN users ud   ON d.user_id = ud.id
                
                WHERE b.id = ?"
            );

            $q->bind_param('i', $booking_id);
            $q->execute();
            $D = $q->get_result()->fetch_assoc();

            if ($D) {
                $from = date('d M Y h:i A', strtotime($D['from_datetime']));
                $to = date('d M Y h:i A', strtotime($D['to_datetime']));
                $userName = trim($D['uf'] . ' ' . $D['ul']);
                $driverName = trim(($D['df'] ?? '') . ' ' . ($D['dl'] ?? ''));
                $driverPhone = $D['dphone'] ?: 'Not Assigned';

                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                $host = $_SERVER['HTTP_HOST'];
                $baseUrl = $protocol . "://" . $host . $projectFolder;

                $userData = [
                        'status' => $status_enum,
                        'user_name' => $userName,
                        'vehicle' => $D['vehicle'] ?? 'N/A',
                        'vehicle_reg_no' => $D['v_reg_no'] ?? 'N/A',
                        'vehicle_image' => $D['v_image'] ?? '',
                        'base_url' => $baseUrl,
                        'from' => $from,
                        'to' => $to,
                        'pickup' => $D['pickup_location'] ?? 'N/A',
                        'drop' => $D['drop_location'] ?? 'N/A',
                        'driver' => $driverName ?: 'Not Assigned',
                        'driver_phone' => $driverPhone,
                        'remark' => $admin_remarks ?: 'No remarks'
                ];

                $ics = MailTemplates::ics($userData);

                try {
                    $html = MailTemplates::userMail($userData);
                    BrevoMailer::send($D['uemail'], $userName, "Booking $status_enum", $html, $ics);
                } catch (Exception $e) {
                    error_log("MAIL USER ERROR: " . $e->getMessage());
                }

                if ($status_enum === 'APPROVED' && !empty($D['demail'])) {
                    $driverData = [
                            'driver_name' => $driverName,
                            'user' => $userName,
                            'user_phone' => $D['uphone'],
                            'vehicle' => $D['vehicle'],
                            'from' => $from,
                            'to' => $to,
                            'pickup' => $D['pickup_location'],
                            'drop' => $D['drop_location']
                    ];
                    try {
                        $html2 = MailTemplates::driverMail($driverData);
                        BrevoMailer::send($D['demail'], $driverName, "Driver Assignment – {$D['vehicle']}", $html2, $ics);
                    } catch (Exception $e) {
                        error_log("MAIL DRIVER ERROR: " . $e->getMessage());
                    }
                }
            }

            echo "<script>setTimeout(function() { window.location.href = 'admin-dashboard.php'; }, 1500);</script>";
            exit();
        } else {
            $err = "Failed to update booking.";
        }
    }
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
        .dashboard-container { display: flex; min-height: 100vh; }
        .main-content { flex: 1; padding: 30px; margin-left: 260px; background-color: #f8f9fa; }
        
        .card { border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .card-header { background-color: #fff; border-bottom: 1px solid #eee; padding: 15px 20px; border-radius: 15px 15px 0 0 !important; font-weight: 700; color: var(--primary-color); }
        
        /* Map Styles */
        .map-container { height: 300px; width: 100%; border-radius: 15px; margin-bottom: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .input-group-text { cursor: pointer; }
        .leaflet-routing-container { display: none !important; }
        
        /* Form Styles */
        .form-control, .form-select { border-radius: 10px; padding: 10px 15px; border: 1px solid #ddd; }
        .form-control:focus, .form-select:focus { box-shadow: 0 0 0 3px rgba(0, 121, 107, 0.1); border-color: var(--secondary-color); }
        
        /* Custom styles for Flatpickr */
        .flatpickr-day.pending { background-color: #ffc107 !important; color: black !important; border-color: #ffc107 !important; }
        .flatpickr-day.booked { background-color: #ff4d4d !important; color: white !important; border-color: #ff4d4d !important; }
        .flatpickr-day.overlap-restricted { background-color: #e0e0e0 !important; color: #aaaaaa !important; border-color: #e0e0e0 !important; cursor: not-allowed; }
        
        .driver-option-busy { background-color: #ffebee; color: #c62828; }
        
        .info-label { font-size: 0.75rem; text-transform: uppercase; color: #888; font-weight: 600; margin-bottom: 2px; }
        .info-value { font-size: 0.95rem; font-weight: 500; color: #333; margin-bottom: 10px; }
        
        .vehicle-thumb { width: 100%; height: 120px; object-fit: cover; border-radius: 10px; cursor: pointer; }

        .card:hover {
            transform: translateY(-2px);
        }

        .card img {
            background: #f8f9fa;
        }
        
        /* Modal Styles */
        .modal-content { border-radius: 20px; border: none; box-shadow: 0 15px 40px rgba(0,0,0,0.2); }

    </style>
</head>

<body id="page-top">

<div class="dashboard-container">
    <!-- Sidebar -->
    <?php include("vendor/inc/sidebar.php"); ?>
    
    <!-- Main Content -->
    <div class="main-content">

        <div class="container-fluid">

            <!-- Success/Error Messages -->
            <?php if (isset($succ)) { ?>
                <script>setTimeout(function () { swal("Success!", "<?php echo $succ; ?>", "success"); }, 100);</script>
            <?php } ?>
            <?php if (isset($err)) { ?>
                <script>setTimeout(function () { swal("Failed!", "<?php echo $err; ?>", "error"); }, 100);</script>
            <?php } ?>

            <?php
            $booking_id = $_GET['booking_id'];
            
            // Fetch Booking Details
            $ret = "SELECT b.id as booking_id, b.from_datetime, b.to_datetime, b.status, b.purpose, b.driver_id, b.pickup_location, b.drop_location, b.created_at, b.vehicle_id,
                           u.first_name, u.last_name, u.email, u.phone, u.address, 
                           v.name as v_name, v.category as v_category, v.reg_no as v_reg_no, v.capacity as v_capacity, v.fuel_type as v_fuel, v.image as v_image, v.default_driver_id
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
                
                // Determine which driver to select by default
                // 1. If booking already has a driver assigned, use that.
                // 2. If not, use the vehicle's default driver.
                $selected_driver_id = $row->driver_id ? $row->driver_id : $row->default_driver_id;
                
                $vehicleImage = $projectFolder . 'vendor/img/vehicles_img/' . ($row->v_image ?: 'placeholder.png');
                ?>
                
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <a href="admin-dashboard.php" class="btn btn-outline-secondary rounded-pill px-3 me-3">
                            <i class="fas fa-arrow-left me-2"></i> Dashboard
                        </a>
                        <h3 class="d-inline-block align-middle fw-bold text-dark mb-0">Review Booking #<?php echo $row->booking_id; ?></h3>
                    </div>
                    <span class="badge <?php echo ($row->status == 'PENDING' ? 'bg-warning text-dark' : ($row->status == 'APPROVED' ? 'bg-success' : 'bg-danger')); ?> fs-6 px-3 py-2 rounded-pill">
                        <?php echo $row->status; ?>
                    </span>
                </div>

                <form method="POST">
                    <!-- Hidden input for vehicle ID to be used by JS -->
                    <input type="hidden" id="vehicle_id" value="<?php echo $row->vehicle_id; ?>">
                    <input type="hidden" id="current_booking_id" value="<?php echo $row->booking_id; ?>">

                    <div class="row">
                        <!-- Left Column: Journey Details -->
                        <div class="col-lg-8">
                            <div class="card h-100">
                                <div class="card-header"><i class="fas fa-map-marked-alt me-2"></i> Journey Details</div>
                                <div class="card-body">
                                    
                                    <!-- Map Container (Always Visible) -->
                                    <div id="map-container" style="position: relative;" class="mb-3">
                                        <div id="map" class="map-container"></div>
                                        
                                        <!-- Route Info -->
                                        <div id="route-info" class="alert alert-info text-center py-2 mb-2" style="display:none;">
                                            <i class="fas fa-route"></i> 
                                            <strong>Distance:</strong> <span class="route-dist">0 km</span> &nbsp;|&nbsp; 
                                            <i class="fas fa-car"></i> <strong>Est. Time:</strong> <span class="route-time">0 min</span>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold small text-muted">Pickup Location</label>
                                            <div class="input-group" onclick="openMap('pickup')">
                                                <span class="input-group-text bg-success text-white"><i class="fas fa-map-marker-alt"></i></span>
                                                <input type="text" id="pickup_location" name="pickup_location" class="form-control pickup-input" value="<?php echo htmlspecialchars($row->pickup_location); ?>" required placeholder="Click to select on map" readonly style="background-color: #fff; cursor: pointer;">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold small text-muted">Drop Location</label>
                                            <div class="input-group" onclick="openMap('drop')">
                                                <span class="input-group-text bg-danger text-white"><i class="fas fa-map-marker-alt"></i></span>
                                                <input type="text" id="drop_location" name="drop_location" class="form-control drop-input" value="<?php echo htmlspecialchars($row->drop_location); ?>" required placeholder="Click to select on map" readonly style="background-color: #fff; cursor: pointer;">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold small text-muted">From Date & Time</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light"><i class="far fa-calendar-alt"></i></span>
                                                <input type="text" id="from_datetime" name="from_datetime" class="form-control book-from-date" value="<?php echo date('Y-m-d H:i', strtotime($row->from_datetime)); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold small text-muted">To Date</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light"><i class="far fa-calendar-alt"></i></span>
                                                <input type="text" id="to_datetime" name="to_datetime" class="form-control book-to-date" value="<?php echo date('Y-m-d', strtotime($row->to_datetime)); ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-0">
                                        <label class="form-label fw-bold small text-muted">Purpose</label>
                                        <div class="p-3 bg-light rounded border text-muted">
                                            <i class="fas fa-quote-left me-2 opacity-50"></i> <?php echo htmlspecialchars($row->purpose); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Sidebar Info & Actions -->
                        <div class="col-lg-4">
                            
                            <!-- Requester Info -->
                            <div class="card mb-3">
                                <div class="card-header"><i class="fas fa-user me-2"></i> Requester</div>
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 45px; height: 45px;">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark"><?php echo $row->first_name . ' ' . $row->last_name; ?></div>
                                            <div class="small text-muted"><?php echo $row->email; ?></div>
                                        </div>
                                    </div>
                                    <div class="info-label">Contact</div>
                                    <div class="info-value"><i class="fas fa-phone me-2 text-muted"></i> <?php echo $row->phone; ?></div>
                                    <div class="info-label">Address</div>
                                    <div class="info-value"><i class="fas fa-map-pin me-2 text-muted"></i> <?php echo $row->address; ?></div>
                                </div>
                            </div>

                            <!-- Vehicle Info -->
                            <div class="card mb-3">
                                <div class="card-header"><i class="fas fa-bus me-2"></i> Vehicle</div>
                                <div class="card-body">
                                    <div class="d-flex gap-3">
                                        <img src="<?php echo $vehicleImage; ?>" class="rounded vehicle-thumb" style="width: 80px; height: 80px;" onclick="openImageModal('<?php echo $vehicleImage; ?>')">
                                        <div>
                                            <div class="fw-bold text-dark"><?php echo $row->v_name; ?></div>
                                            <div class="badge bg-light text-dark border mb-1"><?php echo $row->v_category; ?></div>
                                            <div class="small text-muted font-monospace"><?php echo $row->v_reg_no; ?></div>
                                            <div class="small text-muted mt-1"><i class="fas fa-gas-pump me-1"></i> <?php echo $row->v_fuel; ?> | <i class="fas fa-chair me-1"></i> <?php echo $row->v_capacity; ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>


                            <!-- Action Console -->
                            <div class="card">
                                <div class="card-header bg-light"><i class="fas fa-cogs me-2"></i> Action Console</div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold small text-muted">Assign Driver</label>
                                        <select name="driver_id" id="driver_select" class="form-select">
                                            <option value="">-- Select Driver --</option>
                                            <?php foreach ($drivers as $driver): ?>
                                                <option value="<?php echo $driver->id; ?>" class="driver-option" data-driver-id="<?php echo $driver->id; ?>" <?php echo ($selected_driver_id == $driver->id) ? 'selected' : ''; ?>>
                                                    <?php 
                                                    echo htmlspecialchars($driver->first_name . ' ' . $driver->last_name);
                                                    echo " (Exp: " . $driver->experience_years . "y)";
                                                    ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div id="driver_warning" class="text-danger small mt-1 fw-bold" style="display:none;"></div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-bold small text-muted">Admin Remarks</label>
                                        <textarea name="admin_remarks" class="form-control" rows="3" placeholder="Enter notes..."></textarea>
                                    </div>

                                    <div class="d-grid gap-2">
                                        <button type="submit" name="approve_booking" value="Approved" class="btn btn-success fw-bold shadow-sm" onclick="return validateApprove()">
                                            <i class="fas fa-check-circle me-2"></i> Approve Booking
                                        </button>
                                        <button type="submit" name="approve_booking" value="Cancelled" class="btn btn-outline-danger fw-bold">
                                            <i class="fas fa-times-circle me-2"></i> Reject Booking
                                        </button>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </form>
            <?php } ?>

        </div>
    </div>
</div>

<!-- Image Zoom Modal -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content bg-white border-0 shadow-none">
            <div class="modal-body text-center p-0">
                <img id="modalImage" class="img-fluid rounded-3 shadow-lg" src="" alt="Zoomed Image"
                     style="max-height: 90vh; object-fit: contain;">
            </div>
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
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<!-- Leaflet Control Geocoder JS -->
<script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
<!-- Leaflet Routing Machine JS -->
<script src="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.js"></script>

<script>
    function validateApprove() {
        const driver = document.getElementById("driver_select").value;
        // Only require driver if approving
        // We can't easily check which button was clicked here without more logic, 
        // but the PHP side handles validation. 
        // For client side, we can check if the active element is the approve button.
        // However, simpler to let PHP handle it or just warn.
        return true;
    }

    // Reusing logic from user-confirm-booking.php (adapted for admin)
    
    async function fetchBookedDates(vehicleId) {
        const res = await fetch(`../usr/get-approved-dates.php?v_id=${vehicleId}`);
        return res.json();
    }

    async function fetchPendingDates(vehicleId) {
        const res = await fetch(`../usr/get-pending-dates.php?v_id=${vehicleId}`);
        return res.json();
    }
    
    async function fetchBusyDrivers(start, end, excludeId) {
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

    function buildFlatpickrOptions(approvedRanges, pendingRanges, minDate, enableTime = false) {
        return {
            enableTime: enableTime,
            dateFormat: enableTime ? "Y-m-d H:i" : "Y-m-d",
            minDate: minDate,
            disable: [], 
            onDayCreate: function (dObj, dStr, fp, dayElem) {
                const date = dayElem.dateObj;
                const dateString = flatpickr.formatDate(date, "Y-m-d");
                for (const range of approvedRanges) {
                    const rFrom = range.book_from_date.substring(0, 10);
                    const rTo = range.book_to_date.substring(0, 10);
                    if (dateString >= rFrom && dateString <= rTo) {
                        dayElem.classList.add("booked");
                        dayElem.title = "Car already booked and approved";
                        break;
                    }
                }
                for (const range of pendingRanges) {
                    const rFrom = range.book_from_date.substring(0, 10);
                    const rTo = range.book_to_date.substring(0, 10);
                    if (dateString >= rFrom && dateString <= rTo) {
                        dayElem.classList.add("pending");
                        dayElem.title = "Car pending approval";
                        break;
                    }
                }
            }
        };
    }

    function initDatePickers() {
        const vehicleId = document.getElementById('vehicle_id').value;
        const fromInput = document.getElementById('from_datetime');
        const toInput = document.getElementById('to_datetime');

        if (!vehicleId || !fromInput || !toInput) return;

        const today = new Date();
        const pastDate = new Date(today);
        pastDate.setDate(today.getDate() - 30); 
        const minDateStr = pastDate.toISOString().split('T')[0];
        
        if (fromInput.value && toInput.value) {
            updateDriverAvailability(fromInput.value, toInput.value);
        }

        Promise.all([fetchBookedDates(vehicleId), fetchPendingDates(vehicleId)]).then(([approvedRanges, pendingRanges]) => {
            approvedRanges.sort((a, b) => new Date(a.book_from_date) - new Date(b.book_from_date));

            const fromPicker = flatpickr(fromInput, buildFlatpickrOptions(approvedRanges, pendingRanges, minDateStr, true));
            const toOptions = buildFlatpickrOptions(approvedRanges, pendingRanges, minDateStr, false);
            const toPicker = flatpickr(toInput, toOptions);

            fromPicker.config.onChange.push(function(selectedDates, dateStr) {
                if (selectedDates.length > 0) {
                    const dateOnly = dateStr.split(' ')[0];
                    toPicker.set('minDate', dateOnly);
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
    
    // --- Map Logic for Admin ---
    let map = null;
    let pickupMarker = null;
    let dropMarker = null;
    let pickupLatLng = null;
    let dropLatLng = null;
    let routingControl = null;
    let mapSelectionStep = 'pickup';

    function openMap(type) {
        // const mapContainer = document.getElementById('map-container');
        // mapContainer.style.display = 'block'; // No longer needed
        
        mapSelectionStep = type;
        
        if (!map) {
            initMap();
        } else {
            setTimeout(() => map.invalidateSize(), 100);
        }
        
        // Optional: Scroll to map
        document.getElementById('map-container').scrollIntoView({behavior: "smooth"});

        if(type === 'pickup') {
            swal("Select Pickup", "Click on the map or search to set Pickup Location", "info");
        } else {
            swal("Select Drop", "Click on the map or search to set Drop Location", "info");
        }
    }
    
    // function hideMap() { ... } // Removed

    function initMap() {
        // Default to Colombo
        const defaultLat = 6.9271;
        const defaultLng = 79.8612;
        
        map = L.map('map').setView([defaultLat, defaultLng], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);
        
        // Geocoder
        L.Control.geocoder({
            defaultMarkGeocode: false
        })
        .on('markgeocode', function(e) {
            const bbox = e.geocode.bbox;
            const poly = L.polygon([
                bbox.getSouthEast(),
                bbox.getNorthEast(),
                bbox.getNorthWest(),
                bbox.getSouthWest()
            ]);
            map.fitBounds(poly.getBounds());
            
            const latlng = e.geocode.center;
            const address = e.geocode.name;
            handleLocationSelection(latlng.lat, latlng.lng, address);
        })
        .addTo(map);

        // Locate Me Control
        L.Control.Locate = L.Control.extend({
            onAdd: function(map) {
                const container = L.DomUtil.create('div', 'leaflet-bar leaflet-control leaflet-control-custom');
                container.style.backgroundColor = 'white';
                container.style.width = '30px';
                container.style.height = '30px';
                container.style.cursor = 'pointer';
                container.style.display = 'flex';
                container.style.alignItems = 'center';
                container.style.justifyContent = 'center';
                container.title = "Jump to Current Location";

                const icon = L.DomUtil.create('i', 'fas fa-crosshairs', container);
                icon.style.fontSize = '18px';
                icon.style.color = '#333';

                container.onclick = function() {
                    if (navigator.geolocation) {
                        navigator.geolocation.getCurrentPosition(
                            (position) => {
                                const lat = position.coords.latitude;
                                const lng = position.coords.longitude;
                                map.setView([lat, lng], 15);
                            },
                            (error) => {
                                swal("Error", "Could not get your location: " + error.message, "error");
                            }
                        );
                    } else {
                        swal("Error", "Geolocation is not supported by this browser.", "error");
                    }
                }
                return container;
            },
            onRemove: function(map) { }
        });

        L.control.locate = function(opts) {
            return new L.Control.Locate(opts);
        }
        L.control.locate({ position: 'topleft' }).addTo(map);

        // Try to get user location
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    map.setView([lat, lng], 15);
                },
                (error) => { console.log("Geolocation error: " + error.message); }
            );
        }

        // Custom Icons
        const greenIcon = new L.Icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });

        const redIcon = new L.Icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });
        
        // Pre-populate markers if addresses exist
        const initialPickup = document.getElementById('pickup_location').value;
        const initialDrop = document.getElementById('drop_location').value;
        
        if (initialPickup) {
            geocodeAndSetMarker(initialPickup, 'pickup');
        }
        if (initialDrop) {
            geocodeAndSetMarker(initialDrop, 'drop');
        }
        
        async function geocodeAndSetMarker(address, type) {
            try {
                const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}`);
                const data = await response.json();
                if (data && data.length > 0) {
                    const lat = parseFloat(data[0].lat);
                    const lng = parseFloat(data[0].lon);
                    
                    if (type === 'pickup') {
                        if (pickupMarker) map.removeLayer(pickupMarker);
                        pickupMarker = L.marker([lat, lng], {icon: greenIcon}).addTo(map)
                            .bindPopup("Pickup Location: " + address);
                        pickupLatLng = L.latLng(lat, lng);
                    } else {
                        if (dropMarker) map.removeLayer(dropMarker);
                        dropMarker = L.marker([lat, lng], {icon: redIcon}).addTo(map)
                            .bindPopup("Drop Location: " + address);
                        dropLatLng = L.latLng(lat, lng);
                    }
                    
                    // If both are set, update route and fit bounds
                    if (pickupLatLng && dropLatLng) {
                        updateRoute();
                        const bounds = L.latLngBounds([pickupLatLng, dropLatLng]);
                        map.fitBounds(bounds, { padding: [50, 50] });
                    } else if (pickupLatLng) {
                        map.setView(pickupLatLng, 13);
                    }
                }
            } catch (error) {
                console.error("Geocoding failed for " + type, error);
            }
        }

        map.on('click', async function(e) {
            const lat = e.latlng.lat;
            const lng = e.latlng.lng;
            const coords = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;

            let address = coords;
            try {
                const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`);
                const data = await response.json();
                if (data && data.display_name) {
                    address = data.display_name;
                }
            } catch (error) {
                console.error("Geocoding failed", error);
            }
            
            handleLocationSelection(lat, lng, address);
        });
        
        function handleLocationSelection(lat, lng, address) {
            const pickupInput = document.getElementById('pickup_location');
            const dropInput = document.getElementById('drop_location');

             if (mapSelectionStep === 'pickup') {
                if (pickupMarker) map.removeLayer(pickupMarker);
                pickupMarker = L.marker([lat, lng], {icon: greenIcon}).addTo(map)
                    .bindPopup("Pickup Location: " + address).openPopup();
                pickupInput.value = address;
                pickupLatLng = L.latLng(lat, lng);
                
                updateRoute();
                // hideMap(); // Removed auto-close
                
            } else {
                if (dropMarker) map.removeLayer(dropMarker);
                dropMarker = L.marker([lat, lng], {icon: redIcon}).addTo(map)
                    .bindPopup("Drop Location: " + address).openPopup();
                dropInput.value = address;
                dropLatLng = L.latLng(lat, lng);
                
                updateRoute();
                // Don't auto close for drop
            }
        }
        
        function updateRoute() {
            if (pickupLatLng && dropLatLng) {
                if (routingControl) {
                    map.removeControl(routingControl);
                }

                routingControl = L.Routing.control({
                    waypoints: [
                        pickupLatLng,
                        dropLatLng
                    ],
                    routeWhileDragging: false,
                    createMarker: function() { return null; },
                    addWaypoints: false,
                    fitSelectedRoutes: true,
                    show: false
                }).on('routesfound', function(e) {
                    var routes = e.routes;
                    var summary = routes[0].summary;
                    
                    const distKm = (summary.totalDistance / 1000).toFixed(1);
                    
                    const totalSeconds = summary.totalTime;
                    const hours = Math.floor(totalSeconds / 3600);
                    const minutes = Math.round((totalSeconds % 3600) / 60);
                    
                    let timeString = "";
                    if (hours > 0) {
                        timeString += hours + " hr " + minutes + " min";
                    } else {
                        timeString += minutes + " min";
                    }
                    
                    const infoDiv = document.getElementById('route-info');
                    infoDiv.style.display = 'block';
                    infoDiv.querySelector('.route-dist').innerText = distKm + " km";
                    infoDiv.querySelector('.route-time').innerText = timeString;
                    
                }).addTo(map);
            }
        }

    }

    function openImageModal(src) {
        document.getElementById('modalImage').src = src;
        var myModal = new bootstrap.Modal(document.getElementById('imageModal'));
        myModal.show();
    }

    document.addEventListener('DOMContentLoaded', function() {
        initDatePickers();
        initMap(); // Initialize map on load
        
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
