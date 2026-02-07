<?php
if (session_status() === PHP_SESSION_NONE) session_start();

include('../DATABASE FILE/config.php');
include('../DATABASE FILE/checklogin.php');
check_login();

global $mysqli;

$projectFolder = '/' . basename(dirname(__DIR__)) . '/';
$aid = $_SESSION['u_id'] ?? null;

$user = null;
if ($aid) {
    $stmt = $mysqli->prepare("SELECT first_name, last_name, email, phone FROM users WHERE id = ?");
    $stmt->bind_param('i', $aid);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_object();
    $stmt->close();
}

// Handle Date Filtering
$filter_from = $_GET['filter_from'] ?? '';
$filter_to = $_GET['filter_to'] ?? '';

$available_vehicles = [];
$show_book_button = false;

$query = "SELECT * FROM vehicles WHERE status = 'AVAILABLE'";

if ($filter_from && $filter_to) {
    $show_book_button = true;
    $booked_sql = "
        SELECT DISTINCT vehicle_id 
        FROM bookings 
        WHERE status = 'APPROVED' 
        AND (
            (? <= to_datetime) AND (? >= from_datetime)
        )
    ";
    $query .= " AND id NOT IN ($booked_sql)";
}

$stmt = $mysqli->prepare($query);

if ($filter_from && $filter_to) {
    $stmt->bind_param('ss', $filter_from, $filter_to);
}

$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_object()) {
    $available_vehicles[] = $row;
}
$stmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Book Vehicle | Vehicle Booking System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Include Global Theme -->
    <?php include("../vendor/inc/theme-config.php"); ?>

    <!-- CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- SweetAlert -->
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>

    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.css" />

    <style>
        body { background-color: #f4f7f6; }
        .dashboard-container { display: flex; min-height: 100vh; }

        /* Sidebar is loaded via include, styles are in sidebar.php */

        .main-content {
            flex: 1;
            padding: 30px;
            margin-left: 260px; /* Match sidebar width */
        }

        .vehicle-img { height: 200px; object-fit: cover; border-radius: 10px 10px 0 0; cursor: pointer; }
        .vehicle-card { transition: transform 0.3s ease, box-shadow 0.3s ease; border: none; border-radius: 15px; overflow: hidden; background: #fff; box-shadow: 0 5px 15px rgba(0,0,0,0.05); height: 100%; }
        .vehicle-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .card-body { padding: 20px; }
        .card-title { font-weight: 700; color: #333; margin-bottom: 10px; }
        .card-text { color: #666; font-size: 0.95rem; margin-bottom: 20px; }
        .modal-content { border-radius: 20px; border: none; box-shadow: 0 15px 40px rgba(0,0,0,0.2); }
        .modal-header { border-bottom: 1px solid #eee; padding: 20px 30px; background-color: #fff; border-radius: 20px 20px 0 0; }
        .modal-title { font-weight: 700; color: #333; }
        .modal-body { padding: 30px; }
        .modal-footer { border-top: 1px solid #eee; padding: 20px 30px; }
        .btn-block { width: 100%; border-radius: 50px; padding: 10px; font-weight: 600; }
        .form-control { border-radius: 10px; padding: 10px 15px; border: 1px solid #ddd; }
        .form-control:focus { box-shadow: 0 0 0 3px rgba(0, 121, 107, 0.1); border-color: #00796b; }
        .input-group-text { border-radius: 10px 0 0 10px; border: 1px solid #ddd; border-right: none; }
        .input-group .form-control { border-radius: 0 10px 10px 0; }
        .flatpickr-day.pending { background-color: #ffc107 !important; color: black !important; border-color: #ffc107 !important; }
        .flatpickr-day.booked { background-color: #ff4d4d !important; color: white !important; border-color: #ff4d4d !important; }
        .flatpickr-day.overlap-restricted { background-color: #e0e0e0 !important; color: #aaaaaa !important; border-color: #e0e0e0 !important; cursor: not-allowed; }
        .map-container { height: 300px; width: 100%; border-radius: 15px; margin-bottom: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .leaflet-routing-container { display: none !important; }
        .filter-section { background: #fff; padding: 20px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); margin-bottom: 30px; }

        .content-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
    </style>
</head>
<body>

<div class="dashboard-container">
    <!-- Sidebar -->
    <?php include("vendor/inc/sidebar.php"); ?>

    <div class="main-content">
        <div class="content-header">
            <h2 class="fw-bold text-dark mb-0">Book a Vehicle</h2>
            <div class="d-flex align-items-center">
                <div class="text-end me-3">
                    <h6 class="mb-0 text-dark fw-semibold"><?php echo htmlspecialchars($user->first_name ?? 'User'); ?></h6>
                    <small class="text-muted">Employee</small>
                </div>
                <i class="fas fa-user-circle fa-2x text-primary"></i>
            </div>
        </div>

        <?php if (isset($_SESSION['msg'])): ?>
            <script>
                setTimeout(() => swal("Warning", "<?php echo $_SESSION['msg']; ?>", "error"), 100);
            </script>
            <?php unset($_SESSION['msg']); ?>
        <?php endif; ?>

        <!-- Date Filter Section -->
        <div class="filter-section mb-4">
            <h5 class="fw-bold mb-3"><i class="fas fa-calendar-alt text-primary me-2"></i> Select Journey Dates</h5>
            <form method="GET" action="usr-book-vehicle.php" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small text-muted fw-bold text-uppercase">Journey Start</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="far fa-calendar-alt text-primary"></i></span>
                        <input type="text" name="filter_from" id="filter_from" class="form-control bg-white" placeholder="Select Start Date & Time" value="<?= htmlspecialchars($filter_from) ?>" required readonly>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label small text-muted fw-bold text-uppercase">Return Date</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="far fa-calendar-alt text-primary"></i></span>
                        <input type="text" name="filter_to" id="filter_to" class="form-control bg-white" placeholder="Select Return Date & Time" value="<?= htmlspecialchars($filter_to) ?>" required readonly>
                    </div>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100 rounded-pill"><i class="fas fa-filter me-2"></i> Find Available Vehicles</button>
                </div>
            </form>
        </div>

        <!-- Filter & Search Header -->
        <div class="filter-section d-flex justify-content-between align-items-center flex-wrap gap-3">
            <h4 class="mb-0 fw-bold text-dark"><i class="fas fa-bus me-2 text-success"></i> Available Vehicles</h4>

            <div class="d-flex gap-3">
                <select id="seatFilter" class="form-select form-select-sm" style="width: 150px; border-radius: 20px;">
                    <option value="">Filter by Seats</option>
                    <?php
                    $seatStmt = $mysqli->prepare("SELECT DISTINCT capacity FROM vehicles WHERE status = 'AVAILABLE' ORDER BY capacity");
                    $seatStmt->execute();
                    $seatResult = $seatStmt->get_result();
                    while ($seatRow = $seatResult->fetch_object()) {
                        echo "<option value='$seatRow->capacity'>$seatRow->capacity Seats</option>";
                    }
                    $seatStmt->close();
                    ?>
                </select>

                <div class="input-group input-group-sm" style="width: 250px;">
                    <span class="input-group-text bg-light border-end-0" style="border-radius: 20px 0 0 20px;"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" id="searchInput" class="form-control border-start-0" placeholder="Search vehicles..." style="border-radius: 0 20px 20px 0;">
                </div>
            </div>
        </div>

        <div class="row g-4" id="vehicleCards">
            <?php if (empty($available_vehicles)): ?>
                <div class="col-12 text-center py-5">
                    <div class="text-muted">
                        <i class="fas fa-car-crash fa-3x mb-3"></i>
                        <h5>No vehicles available for the selected dates.</h5>
                        <p>Please try different dates or clear the filter.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($available_vehicles as $row):
                    $imagePath = $projectFolder . 'vendor/img/vehicles_img/' . ($row->image ?: 'placeholder.png');
                    ?>
                    <!-- Modal -->
                    <div class="modal fade" id="bookModal<?php echo $row->id; ?>" tabindex="-1"
                         aria-labelledby="bookModalLabel<?php echo $row->id; ?>" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content">
                                <form method="POST" action="user-confirm-booking.php" onsubmit="return validateBookingDates(this);">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="bookModalLabel<?php echo $row->id; ?>">
                                            Book <?php echo $row->name; ?>
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                aria-label="Close"></button>
                                    </div>

                                    <div class="modal-body">
                                        <div class="mb-4 d-flex align-items-center gap-3 p-3 bg-light rounded-3">
                                            <img src="<?= $imagePath; ?>" class="rounded" style="width: 80px; height: 60px; object-fit: cover;">
                                            <div>
                                                <h6 class="mb-1 fw-bold"><?= $row->name; ?></h6>
                                                <small class="text-muted"><?= $row->category; ?> | <?= $row->reg_no; ?></small>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="book_from_date<?= $row->id; ?>" class="form-label fw-semibold small text-uppercase text-muted">From Date</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-white"><i class="far fa-calendar-alt text-primary"></i></span>
                                                    <input type="text" onkeydown="return false;" id="book_from_date<?= $row->id; ?>" name="book_from_date" class="form-control book-from-date" required placeholder="Select Date & Time" value="<?= htmlspecialchars($filter_from) ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="book_to_date<?= $row->id; ?>" class="form-label fw-semibold small text-uppercase text-muted">To Date</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-white"><i class="far fa-calendar-alt text-primary"></i></span>
                                                    <input type="text" onkeydown="return false;" id="book_to_date<?= $row->id; ?>" name="book_to_date" class="form-control book-to-date" required placeholder="Select Date" value="<?= htmlspecialchars($filter_to) ?>">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Map Container (Always Visible) -->
                                        <div id="map-container-<?= $row->id; ?>" class="mb-3 position-relative">
                                            <div id="map<?= $row->id; ?>" class="map-container"></div>

                                            <!-- Route Info -->
                                            <div id="route-info-<?= $row->id; ?>" class="alert alert-info text-center py-2 mb-2 shadow-sm border-0" style="display:none; border-radius: 10px;">
                                                <i class="fas fa-route text-primary"></i>
                                                <strong class="ms-2">Distance:</strong> <span class="route-dist">0 km</span>
                                                <span class="mx-2 text-muted">|</span>
                                                <i class="fas fa-clock text-primary"></i> <strong class="ms-1">Est. Time:</strong> <span class="route-time">0 min</span>
                                            </div>
                                            <div class="text-center small text-muted mb-2">
                                                <i class="fas fa-info-circle"></i> Tap map to set <strong>Pickup</strong>, then <strong>Drop-off</strong>. Tap again to reset.
                                            </div>
                                        </div>

                                        <!-- Fields for Pickup and Drop Location -->
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="pickup_location<?= $row->id; ?>" class="form-label fw-semibold small text-uppercase text-muted">Pickup Location</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-success text-white border-success"><i class="fas fa-map-marker-alt"></i></span>
                                                    <input type="text" id="pickup_location<?= $row->id; ?>" name="pickup_location" class="form-control pickup-input" required placeholder="Select on map" readonly style="background-color: #fff;">
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="drop_location<?= $row->id; ?>" class="form-label fw-semibold small text-uppercase text-muted">Drop Location</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-danger text-white border-danger"><i class="fas fa-map-marker-alt"></i></span>
                                                    <input type="text" id="drop_location<?= $row->id; ?>" name="drop_location" class="form-control drop-input" required placeholder="Select on map" readonly style="background-color: #fff;">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="purpose<?= $row->id; ?>" class="form-label fw-semibold small text-uppercase text-muted">Purpose</label>
                                            <textarea id="purpose<?= $row->id; ?>" name="purpose" class="form-control" rows="2" placeholder="Briefly describe the purpose of booking (optional)"></textarea>
                                        </div>

                                        <!-- Hidden Inputs -->
                                        <input type="hidden" name="v_id" value="<?= $row->id; ?>">
                                        <input type="hidden" name="u_car_type" value="<?= $row->category; ?>">
                                        <input type="hidden" name="u_car_regno" value="<?= $row->reg_no; ?>">
                                        <input type="hidden" name="u_car_book_status" value="Pending">
                                    </div>

                                    <div class="modal-footer bg-light">
                                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">
                                            Cancel
                                        </button>
                                        <button type="submit" name="book_vehicle" class="btn btn-success rounded-pill px-4 shadow-sm">
                                            Confirm Booking
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>


                    <!-- Card -->
                    <div class="col-md-6 col-lg-4 vehicle-card-wrapper">
                        <div class="vehicle-card h-100">
                            <div class="position-relative">
                                <img src="<?= $imagePath; ?>" class="card-img-top vehicle-img" alt="<?= $row->name; ?>" onclick="openImageModal('<?= $imagePath; ?>')">
                                <span class="position-absolute top-0 end-0 m-3 badge bg-white text-dark shadow-sm rounded-pill px-3 py-2">
                                    <i class="fas fa-chair text-success me-1"></i> <?= $row->capacity; ?> Seats
                                </span>
                            </div>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?= $row->name; ?></h5>
                                <p class="card-text mb-4">
                                    <small class="text-muted d-block mb-1">Registration No.</small>
                                    <span class="fw-bold text-dark"><?= $row->reg_no; ?></span>
                                </p>
                                <div class="mt-auto">
                                    <?php if ($show_book_button): ?>
                                        <button type="button" class="btn btn-outline-success btn-block" data-bs-toggle="modal"
                                                data-bs-target="#bookModal<?= $row->id; ?>">
                                            <i class="fas fa-calendar-check me-2"></i> Book Now
                                        </button>
                                    <?php else: ?>
                                        <div class="text-center text-muted small bg-light rounded-pill py-2">
                                            <i class="fas fa-info-circle me-1"></i> Select dates to book
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Image Zoom Modal -->
<div class="modal fade" id="imageModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content bg-white border-0 shadow-none">
            <div class="modal-body p-0 text-center">
                <img src="" id="modalImage" class="img-fluid w-100 rounded-3 shadow-lg" style="max-height: 90vh; object-fit: contain;"
                     alt="">
            </div>
        </div>
    </div>
</div>

<!-- JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<!-- Leaflet Control Geocoder JS -->
<script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
<!-- Leaflet Routing Machine JS -->
<script src="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.js"></script>

<script>
    // Initialize Global Date Filter Pickers
    flatpickr("#filter_from", {
        enableTime: true,
        dateFormat: "Y-m-d H:i",
        minDate: "today",
        onChange: function(selectedDates, dateStr, instance) {
            // Optional: Set minDate for filter_to
            const toPicker = document.querySelector("#filter_to")._flatpickr;
            if (toPicker) {
                toPicker.set('minDate', dateStr);
            }
        }
    });

    flatpickr("#filter_to", {
        enableTime: true,
        dateFormat: "Y-m-d H:i",
        minDate: "today"
    });

    async function fetchBookedDates(vehicleId) {
        const res = await fetch(`get-approved-dates.php?v_id=${vehicleId}`);
        return res.json();
    }

    async function fetchPendingDates(vehicleId) {
        const res = await fetch(`get-pending-dates.php?v_id=${vehicleId}`);
        return res.json();
    }

    function buildFlatpickrOptions(approvedRanges, pendingRanges, minDate) {
        const disabled = [];

        // Disable approved dates
        approvedRanges.forEach(range => {
            disabled.push({
                from: range.book_from_date,
                to: range.book_to_date
            });
        });

        const options = {
            minDate: minDate,
            dateFormat: "Y-m-d",
            disable: disabled,
            onDayCreate: function(dObj, dStr, fp, dayElem) {
                const date = dayElem.dateObj;
                const dateString = flatpickr.formatDate(date, "Y-m-d");

                // Check if date is in approved ranges
                for (const range of approvedRanges) {
                    const rFrom = range.book_from_date.substring(0, 10);
                    const rTo = range.book_to_date.substring(0, 10);
                    if (dateString >= rFrom && dateString <= rTo) {
                        dayElem.classList.add("booked");
                        dayElem.title = "Car already booked and approved to another person for this dates"; // Add tooltip
                        break;
                    }
                }

                // Check if date is in pending ranges
                for (const range of pendingRanges) {
                    const rFrom = range.book_from_date.substring(0, 10);
                    const rTo = range.book_to_date.substring(0, 10);
                    if (dateString >= rFrom && dateString <= rTo) {
                        dayElem.classList.add("pending");
                        dayElem.title = "Car already choose by another person for this dates, but not approved."; // Add tooltip
                        break;
                    }
                }
            }
        };

        return options;
    }

    function setDateLimits(modal) {
        // Calculate date 15 days ago
        const today = new Date();
        const pastDate = new Date(today);
        pastDate.setDate(today.getDate() - 15);
        const minDateStr = pastDate.toISOString().split('T')[0];

        const fromInput = modal.querySelector('.book-from-date');
        const toInput = modal.querySelector('.book-to-date');
        const vehicleId = modal.querySelector('input[name="v_id"]').value;

        if (!fromInput || !toInput || !vehicleId) return;

        // If dates are pre-filled from filter, don't disable To Date initially
        if (!toInput.value) {
            toInput.disabled = true;
        }

        Promise.all([fetchBookedDates(vehicleId), fetchPendingDates(vehicleId)]).then(([approvedRanges, pendingRanges]) => {
            // Sort approvedRanges by date
            approvedRanges.sort((a, b) => new Date(a.book_from_date) - new Date(b.book_from_date));

            const fromOptions = buildFlatpickrOptions(approvedRanges, pendingRanges, minDateStr);
            fromOptions.enableTime = true;
            fromOptions.dateFormat = "Y-m-d H:i";
            const fromPicker = flatpickr(fromInput, fromOptions);

            // Attach click event to the calendar button
            const fromBtn = fromInput.nextElementSibling;
            if(fromBtn) {
                fromBtn.addEventListener('click', () => fromPicker.open());
            }

            // Ensure when 'From Date' changes, 'To Date' is enabled
            fromPicker.config.onChange.push(function(selectedDates, dateStr) {
                if (selectedDates.length > 0) {
                    const fromDate = selectedDates[0];

                    // Enable the 'To Date' input
                    toInput.disabled = false;

                    // Find the next approved booking start date
                    let nextBookingStart = null;
                    for (const range of approvedRanges) {
                        const rangeStart = new Date(range.book_from_date);
                        if (rangeStart > fromDate) {
                            nextBookingStart = rangeStart;
                            break;
                        }
                    }

                    // Build options for To Date
                    // Use date part only for minDate to allow same-day drop off
                    const dateOnlyStr = dateStr.split(' ')[0];
                    const toOptions = buildFlatpickrOptions(approvedRanges, pendingRanges, dateOnlyStr);

                    if (nextBookingStart) {
                        // Calculate maxDate = nextBookingStart - 1 day
                        const maxDate = new Date(nextBookingStart);
                        maxDate.setDate(maxDate.getDate() - 1);
                        toOptions.maxDate = maxDate;
                    }

                    // Custom onDayCreate for overlap restriction
                    const baseOnDayCreate = toOptions.onDayCreate;
                    toOptions.onDayCreate = function(dObj, dStr, fp, dayElem) {
                        baseOnDayCreate(dObj, dStr, fp, dayElem);

                        if (toOptions.maxDate && dObj > toOptions.maxDate) {
                            dayElem.classList.add("overlap-restricted");
                            dayElem.title = "Cannot book past this date due to an upcoming approved booking overlap.";
                        }
                    };

                    // Initialize To Date picker
                    const toPicker = flatpickr(toInput, toOptions);

                    // Attach click event to the calendar button
                    const toBtn = toInput.nextElementSibling;
                    if(toBtn) {
                        toBtn.addEventListener('click', () => toPicker.open());
                    }
                }
            });

            // If pre-filled, initialize To Date picker immediately
            if (fromInput.value) {
                if (toInput.value) {
                    const toOptions = buildFlatpickrOptions(approvedRanges, pendingRanges, minDateStr);
                    toOptions.enableTime = true;
                    toOptions.dateFormat = "Y-m-d H:i";
                    flatpickr(toInput, toOptions);
                }
            }
        });
    }

    // Map Initialization Logic
    let maps = {}; // Store map instances

    function initMap(vehicleId) {
        const mapContainerId = `map${vehicleId}`;
        const modal = document.getElementById(`bookModal${vehicleId}`);

        if (maps[vehicleId]) {
            setTimeout(() => maps[vehicleId].map.invalidateSize(), 100);
            return;
        }

        // Default to Colombo
        const defaultLat = 6.9271;
        const defaultLng = 79.8612;

        const map = L.map(mapContainerId).setView([defaultLat, defaultLng], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        // State for this map
        const state = {
            pickup: null, // { latlng, marker, address }
            drop: null,   // { latlng, marker, address }
            routingControl: null
        };

        const pickupInput = modal.querySelector('.pickup-input');
        const dropInput = modal.querySelector('.drop-input');
        const routeInfoDiv = document.getElementById(`route-info-${vehicleId}`);

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

        // Helper: Reverse Geocode
        async function reverseGeocode(lat, lng) {
            try {
                const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`);
                const data = await response.json();
                return data.display_name || `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
            } catch (error) {
                console.error("Geocoding failed", error);
                return `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
            }
        }

        // Helper: Update Route
        function updateRoute() {
            if (state.pickup && state.drop) {
                if (state.routingControl) {
                    map.removeControl(state.routingControl);
                }

                state.routingControl = L.Routing.control({
                    waypoints: [
                        state.pickup.latlng,
                        state.drop.latlng
                    ],
                    routeWhileDragging: false,
                    createMarker: function() { return null; }, // Use our own markers
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

                    routeInfoDiv.style.display = 'block';
                    routeInfoDiv.querySelector('.route-dist').innerText = distKm + " km";
                    routeInfoDiv.querySelector('.route-time').innerText = timeString;
                }).addTo(map);
            } else {
                if (state.routingControl) {
                    map.removeControl(state.routingControl);
                    state.routingControl = null;
                }
                routeInfoDiv.style.display = 'none';
            }
        }

        // Helper: Set Pickup
        async function setPickup(latlng, address) {
            if (state.pickup && state.pickup.marker) {
                map.removeLayer(state.pickup.marker);
            }

            const marker = L.marker(latlng, {icon: greenIcon, draggable: true}).addTo(map);
            marker.bindPopup("Pickup: " + address).openPopup();

            marker.on('dragend', async function(e) {
                const newPos = e.target.getLatLng();
                const newAddr = await reverseGeocode(newPos.lat, newPos.lng);
                state.pickup.latlng = newPos;
                state.pickup.address = newAddr;
                pickupInput.value = newAddr;
                marker.setPopupContent("Pickup: " + newAddr);
                updateRoute();
            });

            state.pickup = { latlng: latlng, marker: marker, address: address };
            pickupInput.value = address;
            updateRoute();
        }

        // Helper: Set Drop
        async function setDrop(latlng, address) {
            if (state.drop && state.drop.marker) {
                map.removeLayer(state.drop.marker);
            }

            const marker = L.marker(latlng, {icon: redIcon, draggable: true}).addTo(map);
            marker.bindPopup("Drop: " + address).openPopup();

            marker.on('dragend', async function(e) {
                const newPos = e.target.getLatLng();
                const newAddr = await reverseGeocode(newPos.lat, newPos.lng);
                state.drop.latlng = newPos;
                state.drop.address = newAddr;
                dropInput.value = newAddr;
                marker.setPopupContent("Drop: " + newAddr);
                updateRoute();
            });

            state.drop = { latlng: latlng, marker: marker, address: address };
            dropInput.value = address;
            updateRoute();
        }

        // Helper: Reset Map
        function resetMap() {
            if (state.pickup && state.pickup.marker) map.removeLayer(state.pickup.marker);
            if (state.drop && state.drop.marker) map.removeLayer(state.drop.marker);
            if (state.routingControl) map.removeControl(state.routingControl);

            state.pickup = null;
            state.drop = null;
            state.routingControl = null;

            pickupInput.value = '';
            dropInput.value = '';
            routeInfoDiv.style.display = 'none';
        }

        // Map Click Handler
        map.on('click', async function(e) {
            const lat = e.latlng.lat;
            const lng = e.latlng.lng;
            const address = await reverseGeocode(lat, lng);

            if (!state.pickup) {
                await setPickup(e.latlng, address);
            } else if (!state.drop) {
                await setDrop(e.latlng, address);
            } else {
                // Reset and set pickup
                resetMap();
                await setPickup(e.latlng, address);
            }
        });

        // Add Geocoder Control
        L.Control.geocoder({
            defaultMarkGeocode: false
        })
            .on('markgeocode', async function(e) {
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

                if (!state.pickup) {
                    await setPickup(latlng, address);
                } else if (!state.drop) {
                    await setDrop(latlng, address);
                } else {
                    resetMap();
                    await setPickup(latlng, address);
                }
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
                container.title = "Current Location";

                const icon = L.DomUtil.create('i', 'fas fa-crosshairs', container);
                icon.style.fontSize = '18px';
                icon.style.color = '#333';

                container.onclick = function() {
                    if (navigator.geolocation) {
                        navigator.geolocation.getCurrentPosition(
                            async (position) => {
                                const lat = position.coords.latitude;
                                const lng = position.coords.longitude;
                                const latlng = L.latLng(lat, lng);
                                map.setView(latlng, 15);

                                // Behavior: Reset and set pickup to current location
                                resetMap();
                                const address = await reverseGeocode(lat, lng);
                                await setPickup(latlng, address);
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
            }
        });

        L.control.locate = function(opts) { return new L.Control.Locate(opts); }
        L.control.locate({ position: 'topleft' }).addTo(map);

        maps[vehicleId] = { map: map, state: state };
    }

    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('shown.bs.modal', function () {
            setDateLimits(modal);

            // Initialize map when modal opens
            const vehicleId = modal.querySelector('input[name="v_id"]').value;
            initMap(vehicleId);
        });
    });

    function validateBookingDates(form) {
        const fromDate = form.querySelector('.book-from-date').value;
        const toDate = form.querySelector('.book-to-date').value;
        const pickup = form.querySelector('.pickup-input').value;
        const drop = form.querySelector('.drop-input').value;

        if (!fromDate || !toDate) {
            swal("Error", "Please select both From and To dates.", "error");
            return false;
        }

        if (new Date(fromDate) > new Date(toDate + " 23:59:59")) {
            swal("Error", "The 'From Date' cannot be later than the 'To Date'.", "error");
            return false;
        }

        if (!pickup || !drop) {
            swal("Error", "Please select Pickup and Drop locations on the map.", "error");
            return false;
        }

        return true;
    }

    function openImageModal(src) {
        document.getElementById('modalImage').src = src;
        var myModal = new bootstrap.Modal(document.getElementById('imageModal'));
        myModal.show();
    }
</script>

<script>
    $(function () {
        function filterVehicles() {
            const query = $('#searchInput').val().toLowerCase().trim();
            const seatFilter = $('#seatFilter').val().trim();

            $('.vehicle-card-wrapper').each(function () {
                const name = $(this).find('.card-title').text().toLowerCase();
                const reg = $(this).find('.card-text').text().toLowerCase();
                const seatsText = $(this).find('.badge').text();
                const seats = seatsText.replace(/[^0-9]/g, '');

                const matchesQuery = name.includes(query) || reg.includes(query);
                const matchesSeats = seatFilter === '' || seats === seatFilter;

                $(this).toggle(matchesQuery && matchesSeats);
            });
        }

        $('#searchInput, #seatFilter').on('input change', filterVehicles);
    });
</script>

</body>
</html>
