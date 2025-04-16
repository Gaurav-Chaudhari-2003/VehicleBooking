<?php
// Assumes session and DB are already initialized
global $mysqli;
$baseURL = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/';
$projectFolder = '/' . basename(dirname(__DIR__)) . '/';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$aid = $_SESSION['u_id'];
?>

<?php if (isset($_SESSION['msg'])): ?>
    <script>
        setTimeout(function () {
            swal("Info", "<?php echo $_SESSION['msg']; ?>", "info");
        }, 100);
    </script>
    <?php unset($_SESSION['msg']); ?>
<?php endif; ?>


<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center bg-info text-white">
        <div><i class="fas fa-bus"></i> Available Vehicles</div>
        <div>
            <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Search vehicles...">
        </div>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-3">
                <select id="seatFilter" class="form-control">
                    <option value="">Filter by Seats</option>
                    <?php
                    $seatQuery = "SELECT DISTINCT v_pass_no FROM tms_vehicle WHERE v_status = 'Available' ORDER BY v_pass_no ASC";
                    $seatStmt = $mysqli->prepare($seatQuery);
                    $seatStmt->execute();
                    $seatResult = $seatStmt->get_result();
                    while ($seatRow = $seatResult->fetch_object()) {
                        echo '<option value="' . $seatRow->v_pass_no . '">' . $seatRow->v_pass_no . '</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-3">
                <input type="text" id="driverFilter" class="form-control" placeholder="Filter by Driver">
            </div>
        </div>





        <div class="row" id="vehicleCards">
            <?php
            $ret = "SELECT * FROM tms_vehicle WHERE v_status = 'Available'";
            $stmt = $mysqli->prepare($ret);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_object()) {
                $imagePath = $projectFolder . 'vendor/img/' . ($row->v_dpic ? $row->v_dpic : 'placeholder.png');
                ?>


                <!-- Booking Confirmation Modal -->
                <div class="modal fade" id="bookModal<?php echo $row->v_id; ?>" tabindex="-1" aria-labelledby="bookModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <form method="POST" action="user-confirm-booking.php">
                                <div class="modal-header bg-warning text-dark">
                                    <h5 class="modal-title" id="bookModalLabel">
                                        Confirm Vehicle Booking
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <p><strong>Category:</strong> <?php echo $row->v_category; ?></p>
                                    <p><strong>Reg. No:</strong> <?php echo $row->v_reg_no; ?></p>
                                    <p><strong>Booking Date:</strong></p>
                                    <input type="date" name="u_car_bookdate" class="form-control mb-2" required>

                                    <input type="hidden" name="v_id" value="<?php echo $row->v_id; ?>">
                                    <input type="hidden" name="u_car_type" value="<?php echo $row->v_category; ?>">
                                    <input type="hidden" name="u_car_regno" value="<?php echo $row->v_reg_no; ?>">
                                    <input type="hidden" name="u_car_book_status" value="Pending">
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" name="book_vehicle" class="btn btn-success">Confirm Booking</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>




                <div class="col-md-4 mb-4 vehicle-card"
                     data-name="<?php echo strtolower($row->v_name); ?>"
                     data-reg="<?php echo strtolower($row->v_reg_no); ?>"
                     data-seats="<?php echo $row->v_pass_no; ?>"
                     data-driver="<?php echo strtolower($row->v_driver); ?>">
                    <div class="card h-100">
                        <img src="<?php echo $imagePath; ?>" class="card-img-top vehicle-img enlargeable" alt="<?php echo $row->v_name; ?>" data-full="<?php echo $imagePath; ?>">

                        <div class="card-body">
                            <h5 class="card-title"><?php echo $row->v_name; ?></h5>
                            <p class="card-text">
                                <strong>Reg No:</strong> <?php echo $row->v_reg_no; ?><br>
                                <strong>Seats:</strong> <?php echo $row->v_pass_no; ?><br>
                                <strong>Driver:</strong> <?php echo $row->v_driver; ?>
                            </p>
                            <!-- Book Vehicle Button triggers modal -->
                            <button type="button"
                                    class="btn btn-outline-success btn-block"
                                    data-bs-toggle="modal"
                                    data-bs-target="#bookModal<?php echo $row->v_id; ?>">
                                <i class="fa fa-clipboard"></i> Book Vehicle
                            </button>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</div>

<!-- Image Modal for zooming -->
<div class="modal fade" id="imageModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
        <div class="modal-content bg-transparent border-0">
            <div class="modal-body p-0 text-center">
                <img src="" id="modalImage" class="img-fluid w-100" style="max-height: 90vh; object-fit: contain;">
            </div>
        </div>
    </div>
</div>

<!-- Filter and Search Script -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>

<script>
    $(document).ready(function () {
        function filterVehicles() {
            const query = $('#searchInput').val().toLowerCase().trim();
            const seatFilter = $('#seatFilter').val().trim();
            const driverFilter = $('#driverFilter').val().toLowerCase().trim();

            $('.vehicle-card').each(function () {
                const name = $(this).data('name');
                const reg = $(this).data('reg');
                const seats = String($(this).data('seats'));
                const driver = $(this).data('driver');

                const matchesQuery = name.includes(query) || reg.includes(query) || driver.includes(query);
                const matchesSeats = seatFilter === '' || seats === seatFilter;
                const matchesDriver = driver.includes(driverFilter);

                $(this).toggle(matchesQuery && matchesSeats && matchesDriver);
            });
        }

        $('#searchInput, #seatFilter, #driverFilter').on('input change', filterVehicles);

        // When an image inside .vehicle-card with class .enlargeable is clicked
        $('.vehicle-card').on('click', '.enlargeable', function () {
            const imgSrc = $(this).data('full'); // Get the full image URL from data-full attribute
            $('#modalImage').attr('src', imgSrc); // Set the src of the modal image to the full image URL
            $('#imageModal').modal('show'); // Show the modal
        });
    });
</script>
