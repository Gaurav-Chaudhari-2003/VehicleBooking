<?php
if (session_status() === PHP_SESSION_NONE) session_start();

include_once('vendor/inc/config.php');
include_once('vendor/inc/checklogin.php');
check_login();

global $mysqli;

$projectFolder = '/' . basename(dirname(__DIR__)) . '/';
$aid = $_SESSION['u_id'] ?? null;

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

    <!-- CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- SweetAlert -->
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>

    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">


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
                <div class="col-md-3">
                    <label for="driverFilter"></label><input type="text" id="driverFilter" class="form-control"
                                                             placeholder="Filter by Driver">
                </div>
            </div>

            <div class="row" id="vehicleCards">
                <?php
                $stmt = $mysqli->prepare("SELECT * FROM tms_vehicle WHERE v_status = 'Available'");
                $stmt->execute();
                $res = $stmt->get_result();
                while ($row = $res->fetch_object()) {
                    $imagePath = $projectFolder . 'vendor/img/' . ($row->v_dpic ?: 'placeholder.png');
                    ?>
                    <!-- Modal -->
                    <div class="modal fade" id="bookModal<?php echo $row->v_id; ?>" tabindex="-1"
                         aria-labelledby="bookModalLabel<?php echo $row->v_id; ?>" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow-lg rounded-4">
                                <form method="POST" action="user-confirm-booking.php">
                                    <div class="modal-header bg-warning text-dark rounded-top-4">
                                        <h5 class="modal-title" id="bookModalLabel<?php echo $row->v_id; ?>">
                                            Confirm Vehicle Booking
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                aria-label="Close"></button>
                                    </div>

                                    <div class="modal-body">
                                        <div class="mb-2">
                                            <strong>Category:</strong> <?= $row->v_category; ?><br>
                                            <strong>Reg. No:</strong> <?= $row->v_reg_no; ?>
                                        </div>

                                        <div class="row">
                                            <div class="col-6 mb-3">
                                                <label for="book_from_date<?= $row->v_id; ?>" class="form-label">From
                                                    Date</label>
                                                <input type="date" onkeydown="return false;" id="book_from_date<?= $row->v_id; ?>" name="book_from_date" class="form-control book-from-date" required>
                                            </div>
                                            <div class="col-6 mb-3">
                                                <label for="book_to_date<?= $row->v_id; ?>" class="form-label">To
                                                    Date</label>
                                                <input type="date" onkeydown="return false;" id="book_to_date<?= $row->v_id; ?>" name="book_to_date" class="form-control book-to-date" required>
                                            </div>
                                        </div>

                                        <!-- Hidden Inputs -->
                                        <input type="hidden" name="v_id" value="<?= $row->v_id; ?>">
                                        <input type="hidden" name="u_car_type" value="<?= $row->v_category; ?>">
                                        <input type="hidden" name="u_car_regno" value="<?= $row->v_reg_no; ?>">
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

<script>
    async function fetchBookedDates(vehicleId) {
        const res = await fetch(`get-booked-dates.php?v_id=${vehicleId}`);
        return res.json();
    }

    function buildFlatpickrOptions(bookedRanges, minDate, linkedTo = null) {
        const disabled = [];

        // Convert ranges to flatpickr format
        bookedRanges.forEach(range => {
            disabled.push({
                from: range.book_from_date,
                to: range.book_to_date
            });
        });

        const options = {
            minDate: minDate,
            dateFormat: "Y-m-d",
            disable: disabled,
        };

        return options;
    }

    function setDateLimits(modal) {
        const today = new Date().toISOString().split('T')[0];
        const fromInput = modal.querySelector('.book-from-date');
        const toInput = modal.querySelector('.book-to-date');
        const vehicleId = modal.querySelector('input[name="v_id"]').value;

        if (!fromInput || !toInput || !vehicleId) return;

        // Initially disable 'To Date' input
        toInput.disabled = true;

        fetchBookedDates(vehicleId).then(bookedRanges => {
            const fromPicker = flatpickr(fromInput, buildFlatpickrOptions(bookedRanges, today));

            // Ensure when 'From Date' changes, 'To Date' is enabled
            fromPicker.config.onChange.push(function(selectedDates, dateStr) {
                if (selectedDates.length > 0) {
                    // Enable the 'To Date' input
                    toInput.disabled = false;

                    // Set 'To Date' min date as 'From Date'
                    flatpickr(toInput, buildFlatpickrOptions(bookedRanges, dateStr));
                }
            });
        });
    }

    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('shown.bs.modal', function () {
            setDateLimits(modal);
        });
    });
</script>




<script>
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

        $('#searchInput, #seatFilter, #driverFilter').on('input change', filterVehicles);
    });
</script>

<!-- Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

</body>
</html>