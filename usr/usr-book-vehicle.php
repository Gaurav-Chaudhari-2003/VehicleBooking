<?php
// Start a session if none exists
if (session_status() === PHP_SESSION_NONE) session_start();

// Get booked dates
if (isset($_GET['fetch_booked_dates']) && isset($_GET['v_id'])) {
    include_once('vendor/inc/config.php');
    $vehicleId = $_GET['v_id'];

    $stmt = $mysqli->prepare("
        SELECT book_from_date, book_to_date, status 
        FROM tms_booking 
        WHERE vehicle_id = ? AND (status = 'Approved' OR status = 'Pending')
    ");
    $stmt->bind_param('i', $vehicleId);
    $stmt->execute();
    $result = $stmt->get_result();

    $bookedRanges = ['approved' => [], 'pending' => []];
    while ($row = $result->fetch_assoc()) {
        if ($row['status'] === 'Approved') {
            $bookedRanges['approved'][] = [
                'from' => $row['book_from_date'],
                'to' => $row['book_to_date']
            ];
        } elseif ($row['status'] === 'Pending') {
            $bookedRanges['pending'][] = [
                'from' => $row['book_from_date'],
                'to' => $row['book_to_date']
            ];
        }
    }

    header('Content-Type: application/json');
    echo json_encode($bookedRanges);
    exit();
}



// Include configuration and authentication check
include_once('vendor/inc/config.php');
include_once('vendor/inc/checklogin.php');
check_login(); // Redirects to the login page if not authenticated

global $mysqli;

// Define a project folder path dynamically
$projectFolder = '/' . basename(dirname(__DIR__)) . '/';

// Get user ID from a session
$aid = $_SESSION['u_id'] ?? null;

// Fetch logged-in user information
$user = null;
if ($aid) {
    $stmt = $mysqli->prepare("SELECT u_fname, u_lname, u_email, u_phone FROM tms_user WHERE u_id = ?");
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

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- SweetAlert for alerts -->
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>

    <!-- Flatpickr Date Picker CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <!-- Custom Styling -->
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

        .flatpickr-day.pending {
            background-color: #ffeb3b !important;
            border-radius: 50%;
        }
        .flatpickr-day.approved {
            background-color: #f44336 !important;
            border-radius: 50%;
        }
        .flatpickr-day.available {
            background-color: #c8e6c9 !important;
            border-radius: 50%;
        }

    </style>
</head>
<body>
<div class="container my-4">

    <!-- Show SweetAlert warning if a session message exists -->
    <?php if (isset($_SESSION['msg'])): ?>
        <script>
            setTimeout(() => swal("Warning", "<?php echo $_SESSION['msg']; ?>", "error"), 100);
        </script>
        <?php unset($_SESSION['msg']); ?>
    <?php endif; ?>

    <!-- Vehicles Card Header with Search Input -->
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center bg-info text-white">
            <div><i class="fas fa-bus"></i> Available Vehicles</div>
            <label for="searchInput"></label>
            <input type="text" id="searchInput" class="form-control form-control-sm w-auto" placeholder="Search vehicles...">
        </div>

        <div class="card-body">
            <!-- Filters -->
            <div class="row mb-3">
                <!-- Seat Filter Dropdown -->
                <div class="col-md-3">
                    <label for="seatFilter"></label>
                    <select id="seatFilter" class="form-control">
                        <option value="">Filter by Seats</option>
                        <?php
                        // Fetch unique seat counts for filtering
                        $seatStmt = $mysqli->prepare("SELECT DISTINCT v_pass_no FROM tms_vehicle WHERE v_status = 'Available' ORDER BY v_pass_no");
                        $seatStmt->execute();
                        $seatResult = $seatStmt->get_result();
                        while ($seatRow = $seatResult->fetch_object()) {
                            echo "<option value='$seatRow->v_pass_no'>$seatRow->v_pass_no</option>";
                        }
                        $seatStmt->close();
                        ?>
                    </select>
                </div>

                <!-- Driver Filter Input -->
                <div class="col-md-3">
                    <label for="driverFilter"></label>
                    <input type="text" id="driverFilter" class="form-control" placeholder="Filter by Driver">
                </div>
            </div>

            <!-- Vehicle Cards Section -->
            <div class="row" id="vehicleCards">
                <?php
                // Fetch all available vehicles
                $stmt = $mysqli->prepare("SELECT * FROM tms_vehicle WHERE v_status = 'Available'");
                $stmt->execute();
                $res = $stmt->get_result();
                while ($row = $res->fetch_object()) {
                    $imagePath = $projectFolder . 'vendor/img/' . ($row->v_dpic ?: 'placeholder.png');
                    ?>

                    <!-- Booking Modal -->
                    <div class="modal fade" id="bookModal<?php echo $row->v_id; ?>" tabindex="-1" aria-labelledby="bookModalLabel<?php echo $row->v_id; ?>" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow-lg rounded-4">
                                <form method="POST" action="user-confirm-booking.php">
                                    <div class="modal-header bg-warning text-dark rounded-top-4">
                                        <h5 class="modal-title">Confirm Vehicle Booking</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>

                                    <div class="modal-body">
                                        <!-- Vehicle Details -->
                                        <div class="mb-2">
                                            <strong>Category:</strong> <?= $row->v_category; ?><br>
                                            <strong>Reg. No:</strong> <?= $row->v_reg_no; ?>
                                        </div>

                                        <!-- Date Inputs -->
                                        <div class="row">
                                            <div class="col-6 mb-3">
                                                <label for="book_from_date<?= $row->v_id; ?>" class="form-label">From Date</label>
                                                <input type="date" onkeydown="return false;" id="book_from_date<?= $row->v_id; ?>" name="book_from_date" class="form-control book-from-date" required>
                                            </div>
                                            <div class="col-6 mb-3">
                                                <label for="book_to_date<?= $row->v_id; ?>" class="form-label">To Date</label>
                                                <input type="date" onkeydown="return false;" id="book_to_date<?= $row->v_id; ?>" name="book_to_date" class="form-control book-to-date" required>
                                            </div>
                                        </div>
                                        <small class="text-muted">* Yellow dates are under pending review and may still be booked</small>


                                        <!-- Hidden Fields -->
                                        <input type="hidden" name="v_id" value="<?= $row->v_id; ?>">
                                        <input type="hidden" name="u_car_type" value="<?= $row->v_category; ?>">
                                        <input type="hidden" name="u_car_regno" value="<?= $row->v_reg_no; ?>">
                                        <input type="hidden" name="u_car_book_status" value="Pending">
                                    </div>

                                    <!-- Modal Actions -->
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" name="book_vehicle" class="btn btn-success">Confirm Booking</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Vehicle Card -->
                    <div class="col-md-4 mb-4 vehicle-card"
                         data-seats="<?= $row->v_pass_no; ?>"
                         data-driver="<?= strtolower($row->v_driver); ?>">
                        <div class="card h-100">
                            <img src="<?= $imagePath; ?>" class="card-img-top vehicle-img" alt="<?= $row->v_name; ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?= $row->v_name; ?></h5>
                                <p class="card-text">
                                    <strong>Reg No:</strong> <?= $row->v_reg_no; ?><br>
                                    <strong>Seats:</strong> <?= $row->v_pass_no; ?><br>
                                    <strong>Driver:</strong> <?= $row->v_driver; ?>
                                </p>
                                <button type="button" class="btn btn-outline-success btn-block" data-bs-toggle="modal"
                                        data-bs-target="#bookModal<?= $row->v_id; ?>">
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

<!-- Fullscreen Image Modal for Zooming Images -->
<div class="modal fade" id="imageModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content bg-transparent border-0">
            <div class="modal-body p-0 text-center">
                <img src="" id="modalImage" class="img-fluid w-100" style="max-height: 90vh; object-fit: contain;" alt="">
            </div>
        </div>
    </div>
</div>

<!-- JS Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Flatpickr for date selection -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
    // Fetch booked date ranges from server
    async function fetchBookedDates(vehicleId) {
        const res = await fetch(`<?php echo basename(__FILE__); ?>?fetch_booked_dates=1&v_id=${vehicleId}`);
        return res.json(); // returns { approved: [...], pending: [...] }
    }



    // Prepare flatpickr options, disabling booked ranges
    function buildFlatpickrOptions(bookedData, minDate) {
        const approved = bookedData.approved || [];
        const pending = bookedData.pending || [];

        const disableDates = approved.map(range => ({
            from: range.from,
            to: range.to
        }));

        const pendingDates = [];
        pending.forEach(range => {
            const from = new Date(range.from);
            const to = new Date(range.to);
            for (let d = new Date(from); d <= to; d.setDate(d.getDate() + 1)) {
                pendingDates.push(new Date(d)); // store Date objects
            }
        });

        const approvedDates = [];
        approved.forEach(range => {
            const from = new Date(range.from);
            const to = new Date(range.to);
            for (let d = new Date(from); d <= to; d.setDate(d.getDate() + 1)) {
                approvedDates.push(new Date(d)); // store Date objects
            }
        });

        return {

            minDate: minDate,
            dateFormat: "Y-m-d",
            disable: disableDates, // Only disable approved dates
            onDayCreate: function (dObj, dStr, fp, dayElem) {
                const date = dayElem.dateObj;
                const today = new Date();
                today.setHours(0, 0, 0, 0); // Clear time for accurate comparison

                const isPending = pendingDates.some(d => d.toDateString() === date.toDateString());
                const isApproved = approvedDates.some(d => d.toDateString() === date.toDateString());

                // Style for past dates
                if (date < today) {
                    dayElem.style.backgroundColor = '#e0e0e0'; // Grey
                    dayElem.style.color = '#9e9e9e'; // Dim text
                    dayElem.style.borderRadius = '50%';
                    dayElem.title = "Past date";
                    return;
                }

                // Style for approved (red), pending (yellow), available (green)
                if (isApproved) {
                    dayElem.style.backgroundColor = '#f44336'; // Red
                    dayElem.style.color = '#ffffff'; // White text
                    dayElem.style.borderRadius = '50%';
                    dayElem.title = "Approved booking";
                } else if (isPending) {
                    dayElem.style.backgroundColor = '#ffeb3b'; // Yellow
                    dayElem.style.color = '#000000'; // Black text
                    dayElem.style.borderRadius = '50%';
                    dayElem.title = "Pending booking";
                } else {
                    dayElem.style.backgroundColor = '#008000'; // Green
                    dayElem.style.color = '#ffffff'; // White text
                    dayElem.style.borderRadius = '50%';
                    dayElem.title = "Available";
                }

                // Optionally adjust size/layout
                dayElem.style.fontWeight = 'bold';
                dayElem.style.lineHeight = '30px';
                dayElem.style.textAlign = 'center';
                dayElem.style.margin = '1px';
            }

        };
    }



    // Setup date restrictions when modal is shown
    function setDateLimits(modal) {
        const today = new Date().toISOString().split('T')[0];
        const fromInput = modal.querySelector('.book-from-date');
        const toInput = modal.querySelector('.book-to-date');
        const vehicleId = modal.querySelector('input[name="v_id"]').value;

        if (!fromInput || !toInput || !vehicleId) return;

        fetchBookedDates(vehicleId).then(bookedData => {
            const fromPicker = flatpickr(fromInput, buildFlatpickrOptions(bookedData, today));

            fromPicker.config.onChange.push(function (selectedDates, dateStr) {
                if (selectedDates.length > 0) {
                    toInput.disabled = false;
                    flatpickr(toInput, buildFlatpickrOptions(bookedData, dateStr));
                }
            });
        });
    }


    // Bind modal open event to initialize date logic
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('shown.bs.modal', function () {
            setDateLimits(modal);
        });
    });
</script>

<script>
    // Filter vehicle cards based on search and filter criteria
    $(function () {
        function filterVehicles() {
            const query = $('#searchInput').val().toLowerCase().trim();
            const seatFilter = $('#seatFilter').val().trim();
            const driverFilter = $('#driverFilter').val().toLowerCase().trim();

            $('.vehicle-card').each(function () {
                const name = $(this).find('.card-title').text().toLowerCase();
                const reg = $(this).find('.card-text').text().toLowerCase();
                const driver = $(this).data('driver');
                const seats = String($(this).data('seats'));

                const matchesQuery = name.includes(query) || reg.includes(query);
                const matchesSeats = seatFilter === '' || seats === seatFilter;
                const matchesDriver = driver.includes(driverFilter);

                $(this).toggle(matchesQuery && matchesSeats && matchesDriver);
            });
        }

        // Bind filter events
        $('#searchInput, #seatFilter, #driverFilter').on('input change', filterVehicles);
    });
</script>

</body>
</html>
