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

    <!-- CSS & Icons -->
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
        .vehicle-img {
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
        }

        .vehicle-card .card {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transition: transform 0.2s ease;
        }

        .vehicle-card .card:hover {
            transform: translateY(-4px);
        }

        .modal-content {
            border-radius: 12px;
        }

        .btn-block {
            width: 100%;
        }

        body {
            background-color: #f4f6f9;
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
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .input-group-text {
            cursor: pointer;
        }
        
        /* Hide default routing container if we want custom display */
        .leaflet-routing-container {
            display: none !important;
        }
    </style>
</head>
<body>
<div class="container my-4">
    <?php if (isset($_SESSION['msg'])): ?>
        <script>
            setTimeout(() => swal("Warning", "<?php echo $_SESSION['msg']; ?>", "error"), 100);
        </script>
        <?php unset($_SESSION['msg']); ?>
    <?php endif; ?>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center bg-info text-white">
            <div><i class="fas fa-bus"></i> Available Vehicles</div>
            <label for="searchInput"></label><input type="text" id="searchInput"
                                                    class="form-control form-control-sm w-auto"
                                                    placeholder="Search vehicles...">
        </div>

        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3">
                    <label for="seatFilter"></label><select id="seatFilter" class="form-control">
                        <option value="">Filter by Seats</option>
                        <?php
                        // Updated query to match new schema: vehicles table
                        $seatStmt = $mysqli->prepare("SELECT DISTINCT capacity FROM vehicles WHERE status = 'AVAILABLE' ORDER BY capacity");
                        $seatStmt->execute();
                        $seatResult = $seatStmt->get_result();
                        while ($seatRow = $seatResult->fetch_object()) {
                            echo "<option value='$seatRow->capacity'>$seatRow->capacity</option>";
                        }
                        $seatStmt->close();
                        ?>
                    </select>
                </div>
                <!-- Driver filter removed as it's not directly available in vehicles table -->
            </div>

            <div class="row" id="vehicleCards">
                <?php
                // Updated query to match new schema: vehicles table
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
                            <div class="modal-content border-0 shadow-lg rounded-4">
                                <form method="POST" action="user-confirm-booking.php" onsubmit="return validateBookingDates(this);">
                                    <div class="modal-header bg-warning text-dark rounded-top-4">
                                        <h5 class="modal-title" id="bookModalLabel<?php echo $row->id; ?>">
                                            Confirm Vehicle Booking
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                aria-label="Close"></button>
                                    </div>

                                    <div class="modal-body">
                                        <div class="mb-2">
                                            <strong>Category:</strong> <?= $row->category; ?><br>
                                            <strong>Reg. No:</strong> <?= $row->reg_no; ?>
                                        </div>

                                        <div class="row">
                                            <div class="col-6 mb-3">
                                                <label for="book_from_date<?= $row->id; ?>" class="form-label">From Date</label>
                                                <div class="input-group">
                                                    <input type="text" onkeydown="return false;" id="book_from_date<?= $row->id; ?>" name="book_from_date" class="form-control book-from-date" required placeholder="Select Date & Time">
                                                    <button type="button" class="btn btn-outline-secondary date-picker-btn"><i class="fa fa-calendar-alt"></i></button>
                                                </div>
                                            </div>
                                            <div class="col-6 mb-3">
                                                <label for="book_to_date<?= $row->id; ?>" class="form-label">To Date</label>
                                                <div class="input-group">
                                                    <input type="text" onkeydown="return false;" id="book_to_date<?= $row->id; ?>" name="book_to_date" class="form-control book-to-date" required placeholder="Select Date">
                                                    <button type="button" class="btn btn-outline-secondary date-picker-btn"><i class="fa fa-calendar-alt"></i></button>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- New Fields for Pickup and Drop Location -->
                                        <div class="row">
                                            <div class="col-6 mb-3">
                                                <label for="pickup_location<?= $row->id; ?>" class="form-label">Pickup Location</label>
                                                <div class="input-group" onclick="openMap(<?= $row->id; ?>, 'pickup')">
                                                    <span class="input-group-text bg-success text-white"><i class="fa fa-map-marker-alt"></i></span>
                                                    <input type="text" id="pickup_location<?= $row->id; ?>" name="pickup_location" class="form-control pickup-input" required placeholder="Click to select on map" readonly style="background-color: #fff; cursor: pointer;">
                                                </div>
                                            </div>
                                            <div class="col-6 mb-3">
                                                <label for="drop_location<?= $row->id; ?>" class="form-label">Drop Location</label>
                                                <div class="input-group" onclick="openMap(<?= $row->id; ?>, 'drop')">
                                                    <span class="input-group-text bg-danger text-white"><i class="fa fa-map-marker-alt"></i></span>
                                                    <input type="text" id="drop_location<?= $row->id; ?>" name="drop_location" class="form-control drop-input" required placeholder="Click to select on map" readonly style="background-color: #fff; cursor: pointer;">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Map Container (Initially Hidden) -->
                                        <div id="map-container-<?= $row->id; ?>" style="display:none; position: relative;" class="mb-3">
                                            <div id="map<?= $row->id; ?>" class="map-container"></div>
                                            
                                            <!-- Route Info -->
                                            <div id="route-info-<?= $row->id; ?>" class="alert alert-info text-center py-2 mb-2" style="display:none;">
                                                <i class="fas fa-route"></i> 
                                                <strong>Distance:</strong> <span class="route-dist">0 km</span> &nbsp;|&nbsp; 
                                                <i class="fas fa-car"></i> <strong>Est. Time:</strong> <span class="route-time">0 min</span>
                                            </div>

                                            <div class="text-center">
                                                <button type="button" class="btn btn-sm btn-secondary" onclick="hideMap(<?= $row->id; ?>)">
                                                    <i class="fa fa-check"></i> Done / Close Map
                                                </button>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="purpose<?= $row->id; ?>" class="form-label">Purpose</label>
                                            <textarea id="purpose<?= $row->id; ?>" name="purpose" class="form-control" rows="2" placeholder="Purpose of booking (optional)"></textarea>
                                        </div>

                                        <!-- Hidden Inputs -->
                                        <input type="hidden" name="v_id" value="<?= $row->id; ?>">
                                        <input type="hidden" name="u_car_type" value="<?= $row->category; ?>">
                                        <input type="hidden" name="u_car_regno" value="<?= $row->reg_no; ?>">
                                        <input type="hidden" name="u_car_book_status" value="Pending">
                                    </div>

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                            Cancel
                                        </button>
                                        <button type="submit" name="book_vehicle" class="btn btn-success">
                                            Confirm Booking
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>


                    <!-- Card -->
                    <div class="col-md-4 mb-4 vehicle-card"
                         data-seats="<?= $row->capacity; ?>"
                         data-driver=""> <!-- Driver info removed as it's not in vehicle table -->
                        <div class="card h-100">
                            <img src="<?= $imagePath; ?>" class="card-img-top vehicle-img" alt="<?= $row->name; ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?= $row->name; ?></h5>
                                <p class="card-text">
                                    <strong>Reg No:</strong> <?= $row->reg_no; ?><br>
                                    <strong>Seats:</strong> <?= $row->capacity; ?><br>
                                    <!-- <strong>Driver:</strong> Driver info unavailable -->
                                </p>
                                <button type="button" class="btn btn-outline-success btn-block" data-bs-toggle="modal"
                                        data-bs-target="#bookModal<?= $row->id; ?>">
                                    <i class="fa fa-clipboard"></i> Book Vehicle
                                </button>
                            </div>
                        </div>
                    </div>
                <?php }
                $stmt->close(); ?>
            </div>
        </div>
    </div>
</div>

<!-- Image Zoom Modal -->
<div class="modal fade" id="imageModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content bg-transparent border-0">
            <div class="modal-body p-0 text-center">
                <img src="" id="modalImage" class="img-fluid w-100" style="max-height: 90vh; object-fit: contain;"
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

            $('.vehicle-card').each(function () {
                const name = $(this).find('.card-title').text().toLowerCase();
                const reg = $(this).find('.card-text').text().toLowerCase();
                // const driver = $(this).data('driver'); // Driver filter disabled
                const seats = String($(this).data('seats'));

                const matchesQuery = name.includes(query) || reg.includes(query);
                const matchesSeats = seatFilter === '' || seats === seatFilter;
                // const matchesDriver = driver.includes(driverFilter); // Driver filter disabled

                $(this).toggle(matchesQuery && matchesSeats);
            });
        }

        $('#searchInput, #seatFilter').on('input change', filterVehicles);
        // Removed driver filter event listener
    });
</script>

</body>
</html>
