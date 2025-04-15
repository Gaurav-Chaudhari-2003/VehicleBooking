<?php
global $mysqli;
session_start();
include('vendor/inc/config.php');
include('vendor/inc/checklogin.php');
check_login();
$aid = $_SESSION['u_id'];

// Dynamic base URL to fix relative paths
$baseURL = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/';
$projectFolder = '/' . basename(dirname(__DIR__)) . '/';
?>
<!DOCTYPE html>
<html lang="en">

<?php include('vendor/inc/head.php'); ?>

<body id="page-top">
<?php include('vendor/inc/nav.php'); ?>

<div id="wrapper">
    <?php include('vendor/inc/sidebar.php'); ?>

    <div id="content-wrapper">
        <div class="container-fluid">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="user-dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item">Vehicle</li>
                <li class="breadcrumb-item active">Book Vehicle</li>
            </ol>

            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div><i class="fas fa-bus"></i> Available Vehicles</div>
                    <div>
                        <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Search vehicles...">
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="seatFilter"></label><select id="seatFilter" class="form-control">
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
                            <label for="driverFilter"></label><input type="text" id="driverFilter" class="form-control" placeholder="Filter by Driver">
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
                                        <a href="user-confirm-booking.php?v_id=<?php echo $row->v_id; ?>" class="btn btn-outline-success btn-block">
                                            <i class="fa fa-clipboard"></i> Book Vehicle
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
                <div class="card-footer small text-muted">
                    <?php
                    date_default_timezone_set("Asia/Kolkata");
                    echo "Generated At : " . date("h:i:sa");
                    ?>
                </div>
            </div>
        </div>

        <?php include("vendor/inc/footer.php"); ?>
    </div>
</div>

<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

<!-- Logout Modal-->
<div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                <a class="btn btn-danger" href="user-logout.php">Logout</a>
            </div>
        </div>
    </div>
</div>

<!-- Scripts (relative to baseURL) -->
<script src="<?php echo $baseURL; ?>vendor/jquery/jquery.min.js"></script>
<script src="<?php echo $baseURL; ?>vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo $baseURL; ?>vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="<?php echo $baseURL; ?>vendor/js/sb-admin.min.js"></script>

<!-- Working Search + Filter Script -->
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
    });
</script>

<script>
    $(document).ready(function () {
        // Existing filter logic ...

        // Image click to show modal
        $('.vehicle-card').on('click', '.enlargeable', function () {
            const imgSrc = $(this).data('full');
            $('#modalImage').attr('src', imgSrc);
            $('#imageModal').modal('show');
        });
    });
</script>


<!-- Styling -->
<style>
    .vehicle-card img {
        width: 100%;
        height: 300px;
        object-fit: cover;
        border-radius: 8px;
    }

    .vehicle-card .card {
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .vehicle-card .card-body {
        flex-grow: 1;
    }

    .vehicle-card .card-title {
        font-size: 1.1rem;
        font-weight: bold;
    }

    .vehicle-card .card-text {
        font-size: 0.9rem;
    }

    .vehicle-card .btn {
        margin-top: auto;
    }
</style>

<style>
    #modalImage {
        border-radius: 10px;
        box-shadow: 0 0 20px rgba(0,0,0,0.6);
        transition: 0.3s ease-in-out;
    }
</style>


<!-- Image Modal -->
<div class="modal fade" id="imageModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
        <div class="modal-content bg-transparent border-0">
            <div class="modal-body p-0 text-center">
                <img src="" id="modalImage" class="img-fluid w-100" style="max-height: 90vh; object-fit: contain;">
            </div>
        </div>
    </div>
</div>


</body>
</html>
