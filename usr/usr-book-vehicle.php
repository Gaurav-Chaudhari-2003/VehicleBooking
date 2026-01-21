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
    // Updated query to match new schema: users table
    $stmt = $mysqli->prepare("SELECT first_name, last_name, email, phone FROM users WHERE id = ?");
    $stmt->bind_param('i', $aid);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_object();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Available Vehicles | Vehicle Booking System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Include Global Theme -->
    <!-- Note: We are inside 'usr' folder, so path is '../vendor/inc/theme-config.php' -->
    <!-- However, this file is loaded via AJAX into user-dashboard.php which is also in 'usr'. -->
    <!-- If loaded directly, the path is correct. If loaded via AJAX, the relative paths in theme-config might need care, 
         but since theme-config uses CDN links, it's fine. -->
    
    <!-- We don't include theme-config here because this page is primarily loaded INSIDE user-dashboard.php 
         which already has the theme. Including it again might cause conflicts or double loading.
         BUT, if accessed directly, it needs styles. 
         Let's add a check or just include necessary specific styles for this component. -->

    <!-- CSS & Icons (Keep these as they are specific or CDN) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- SweetAlert -->
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>

    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <!-- Leaflet Control Geocoder CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
    <!-- Leaflet Routing Machine CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.css" />

    <style>
        /* Scoped styles for the booking component */
        .vehicle-img {
            height: 200px;
            object-fit: cover;
            border-radius: 10px 10px 0 0;
        }

        .vehicle-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
            border-radius: 15px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            height: 100%;
        }

        .vehicle-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .card-body {
            padding: 20px;
        }
        
        .card-title {
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }
        
        .card-text {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 20px;
        }

        .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: 0 15px 40px rgba(0,0,0,0.2);
        }
        
        .modal-header {
            border-bottom: 1px solid #eee;
            padding: 20px 30px;
            background-color: #fff;
            border-radius: 20px 20px 0 0;
        }
        
        .modal-title {
            font-weight: 700;
            color: #333;
        }
        
        .modal-body {
            padding: 30px;
        }
        
        .modal-footer {
            border-top: 1px solid #eee;
            padding: 20px 30px;
        }

        .btn-block {
            width: 100%;
            border-radius: 50px;
            padding: 10px;
            font-weight: 600;
        }
        
        .form-control {
            border-radius: 10px;
            padding: 10px 15px;
            border: 1px solid #ddd;
        }
        
        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(0, 121, 107, 0.1); /* Using theme secondary color */
            border-color: #00796b;
        }
        
        .input-group-text {
            border-radius: 10px 0 0 10px;
            border: 1px solid #ddd;
            border-right: none;
        }
        
        .input-group .form-control {
            border-radius: 0 10px 10px 0;
        }


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
        
        /* Map Styles */
        .map-container {
            height: 300px;
            width: 100%;
            border-radius: 15px;
            margin-bottom: 15px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        
        /* Hide default routing container if we want custom display */
        .leaflet-routing-container {
            display: none !important;
        }
        
        /* Filter Section */
        .filter-section {
            background: #fff;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <?php if (isset($_SESSION['msg'])): ?>
        <script>
            setTimeout(() => swal("Warning", "<?php echo $_SESSION['msg']; ?>", "error"), 100);
        </script>
        <?php unset($_SESSION['msg']); ?>
    <?php endif; ?>

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
        <?php
        $stmt = $mysqli->prepare("SELECT * FROM vehicles WHERE status = 'AVAILABLE'");
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_object()) {
            $imagePath = $projectFolder . 'vendor/img/' . ($row->image ?: 'placeholder.png');
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
                                            <input type="text" onkeydown="return false;" id="book_from_date<?= $row->id; ?>" name="book_from_date" class="form-control book-from-date" required placeholder="Select Date & Time">
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="book_to_date<?= $row->id; ?>" class="form-label fw-semibold small text-uppercase text-muted">To Date</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white"><i class="far fa-calendar-alt text-primary"></i></span>
                                            <input type="text" onkeydown="return false;" id="book_to_date<?= $row->id; ?>" name="book_to_date" class="form-control book-to-date" required placeholder="Select Date">
                                        </div>
                                    </div>
                                </div>

                                <!-- New Fields for Pickup and Drop Location -->
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="pickup_location<?= $row->id; ?>" class="form-label fw-semibold small text-uppercase text-muted">Pickup Location</label>
                                        <div class="input-group" onclick="openMap(<?= $row->id; ?>, 'pickup')" style="cursor: pointer;">
                                            <span class="input-group-text bg-success text-white border-success"><i class="fas fa-map-marker-alt"></i></span>
                                            <input type="text" id="pickup_location<?= $row->id; ?>" name="pickup_location" class="form-control pickup-input" required placeholder="Click to select on map" readonly style="background-color: #fff; cursor: pointer;">
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="drop_location<?= $row->id; ?>" class="form-label fw-semibold small text-uppercase text-muted">Drop Location</label>
                                        <div class="input-group" onclick="openMap(<?= $row->id; ?>, 'drop')" style="cursor: pointer;">
                                            <span class="input-group-text bg-danger text-white border-danger"><i class="fas fa-map-marker-alt"></i></span>
                                            <input type="text" id="drop_location<?= $row->id; ?>" name="drop_location" class="form-control drop-input" required placeholder="Click to select on map" readonly style="background-color: #fff; cursor: pointer;">
                                        </div>
                                    </div>
                                </div>

                                <!-- Map Container (Initially Hidden) -->
                                <div id="map-container-<?= $row->id; ?>" style="display:none; position: relative;" class="mb-3">
                                    <div id="map<?= $row->id; ?>" class="map-container"></div>
                                    
                                    <!-- Route Info -->
                                    <div id="route-info-<?= $row->id; ?>" class="alert alert-info text-center py-2 mb-2 shadow-sm border-0" style="display:none; border-radius: 10px;">
                                        <i class="fas fa-route text-primary"></i> 
                                        <strong class="ms-2">Distance:</strong> <span class="route-dist">0 km</span> 
                                        <span class="mx-2 text-muted">|</span> 
                                        <i class="fas fa-clock text-primary"></i> <strong class="ms-1">Est. Time:</strong> <span class="route-time">0 min</span>
                                    </div>

                                    <div class="text-center">
                                        <button type="button" class="btn btn-sm btn-secondary rounded-pill px-4" onclick="hideMap(<?= $row->id; ?>)">
                                            <i class="fas fa-check me-1"></i> Done / Close Map
                                        </button>
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
                        <img src="<?= $imagePath; ?>" class="card-img-top vehicle-img" alt="<?= $row->name; ?>">
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
                            <button type="button" class="btn btn-outline-success btn-block" data-bs-toggle="modal"
                                    data-bs-target="#bookModal<?= $row->id; ?>">
                                <i class="fas fa-calendar-check me-2"></i> Book Now
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php }
        $stmt->close(); ?>
    </div>
</div>

<!-- Image Zoom Modal -->
<div class="modal fade" id="imageModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content bg-transparent border-0 shadow-none">
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

        // Initially disable 'To Date' input
        toInput.disabled = true;

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
        });
    }

    // Map Initialization Logic
    let maps = {}; // Store map instances

    function openMap(vehicleId, type) {
        const mapContainer = document.getElementById(`map-container-${vehicleId}`);
        mapContainer.style.display = 'block';
        
        if (!maps[vehicleId]) {
            initMap(vehicleId, type);
        } else {
            maps[vehicleId].selectionStep = type;
            setTimeout(() => maps[vehicleId].invalidateSize(), 100);
            
            // Update helper text or visual cue if needed
            if(type === 'pickup') {
                swal("Select Pickup", "Click on the map or search to set Pickup Location", "info");
            } else {
                swal("Select Drop", "Click on the map or search to set Drop Location", "info");
            }
        }
    }
    
    function hideMap(vehicleId) {
        const mapContainer = document.getElementById(`map-container-${vehicleId}`);
        mapContainer.style.display = 'none';
    }

    function initMap(vehicleId, initialType) {
        const mapContainerId = `map${vehicleId}`;
        const modal = document.getElementById(`bookModal${vehicleId}`);
        
        // Default to Colombo
        const defaultLat = 6.9271;
        const defaultLng = 79.8612;
        
        const map = L.map(mapContainerId).setView([defaultLat, defaultLng], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);
        
        // Add Geocoder Control
        const geocoder = L.Control.geocoder({
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
            
            // Trigger selection logic for the found location
            const latlng = e.geocode.center;
            const address = e.geocode.name;
            handleLocationSelection(latlng.lat, latlng.lng, address);
        })
        .addTo(map);

        // Create a custom control for "Locate Me"
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
            onRemove: function(map) {
                // Nothing to do here
            }
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
                (error) => {
                    console.log("Geolocation permission denied or error: " + error.message);
                }
            );
        }

        let pickupMarker = null;
        let dropMarker = null;
        let pickupLatLng = null;
        let dropLatLng = null;
        let routingControl = null;
        
        // Store selection step on the map object
        map.selectionStep = initialType;

        const pickupInput = modal.querySelector('.pickup-input');
        const dropInput = modal.querySelector('.drop-input');

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
                    createMarker: function() { return null; }, // Use our own markers
                    addWaypoints: false, // Disable adding new waypoints
                    fitSelectedRoutes: true,
                    show: false // Hide the default itinerary container
                }).on('routesfound', function(e) {
                    var routes = e.routes;
                    var summary = routes[0].summary;
                    // summary.totalDistance is in meters
                    // summary.totalTime is in seconds
                    
                    const distKm = (summary.totalDistance / 1000).toFixed(1);
                    
                    // Format time as X hr Y min
                    const totalSeconds = summary.totalTime;
                    const hours = Math.floor(totalSeconds / 3600);
                    const minutes = Math.round((totalSeconds % 3600) / 60);
                    
                    let timeString = "";
                    if (hours > 0) {
                        timeString += hours + " hr " + minutes + " min";
                    } else {
                        timeString += minutes + " min";
                    }
                    
                    // Update UI
                    const infoDiv = document.getElementById(`route-info-${vehicleId}`);
                    infoDiv.style.display = 'block';
                    infoDiv.querySelector('.route-dist').innerText = distKm + " km";
                    infoDiv.querySelector('.route-time').innerText = timeString;
                    
                }).addTo(map);
            }
        }
        
        // Helper function to handle selection (reused by click and search)
        function handleLocationSelection(lat, lng, address) {
             if (map.selectionStep === 'pickup') {
                if (pickupMarker) map.removeLayer(pickupMarker);
                pickupMarker = L.marker([lat, lng], {icon: greenIcon}).addTo(map)
                    .bindPopup("Pickup Location: " + address).openPopup();
                pickupInput.value = address;
                pickupLatLng = L.latLng(lat, lng);
                
                // Update route (in case drop is already set)
                updateRoute();
                
                // Auto close map for pickup
                hideMap(vehicleId);
                
            } else {
                if (dropMarker) map.removeLayer(dropMarker);
                dropMarker = L.marker([lat, lng], {icon: redIcon}).addTo(map)
                    .bindPopup("Drop Location: " + address).openPopup();
                dropInput.value = address;
                dropLatLng = L.latLng(lat, lng);
                
                // Update route
                updateRoute();
                
                // Don't auto close for drop
            }
        }

        map.on('click', async function(e) {
            const lat = e.latlng.lat;
            const lng = e.latlng.lng;
            const coords = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;

            // Reverse Geocoding
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

        maps[vehicleId] = map;
        
        // Initial alert
        if(initialType === 'pickup') {
            swal("Select Pickup", "Click on the map or search to set Pickup Location", "info");
        } else {
            swal("Select Drop", "Click on the map or search to set Drop Location", "info");
        }
    }

    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('shown.bs.modal', function () {
            setDateLimits(modal);
            // Map is now initialized on click, not on show
        });
    });

    function validateBookingDates(form) {
        const fromDate = form.querySelector('.book-from-date').value;
        const toDate = form.querySelector('.book-to-date').value;

        if (!fromDate || !toDate) {
            swal("Error", "Please select both From and To dates.", "error");
            return false;
        }

        if (new Date(fromDate) > new Date(toDate + " 23:59:59")) {
            swal("Error", "The 'From Date' cannot be later than the 'To Date'.", "error");
            return false;
        }

        return true;
    }
</script>

<script>
    $(function () {
        function filterVehicles() {
            const query = $('#searchInput').val().toLowerCase().trim();
            const seatFilter = $('#seatFilter').val().trim();
            // const driverFilter = $('#driverFilter').val().toLowerCase().trim(); // Removed

            $('.vehicle-card-wrapper').each(function () {
                const name = $(this).find('.card-title').text().toLowerCase();
                const reg = $(this).find('.card-text').text().toLowerCase();
                // const driver = $(this).data('driver'); // Driver filter disabled
                // const seats = String($(this).data('seats')); // Need to add data-seats back to wrapper if filtering by wrapper
                // Actually, the previous code had data-seats on the card, but now we have a wrapper.
                // Let's fix the selector logic.
                
                // We need to find the seats from the badge text or add data attribute back.
                const seatsText = $(this).find('.badge').text(); 
                // " 5 Seats" -> "5"
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
