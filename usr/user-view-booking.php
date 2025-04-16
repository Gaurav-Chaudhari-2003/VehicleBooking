<?php
global $mysqli;
session_start();
include('vendor/inc/config.php');
include('vendor/inc/checklogin.php');
check_login();
$aid = $_SESSION['u_id'];
?>
<!DOCTYPE html>
<html lang="en">
<?php include("vendor/inc/head.php"); ?>

<body id="page-top">

<div class="container-fluid">
    <!-- Header -->
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center bg-info text-white">
            <h4 class="mb-0"><i class="fas fa-list"></i> My Bookings</h4>
            <a href="user-dashboard.php" class="btn btn-light btn-sm">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <!-- Booking Cards -->
    <div class="row" id="bookingCards">
        <?php
        $query = "
            SELECT u.u_id, u.u_fname, u.u_lname, u.u_phone, u.u_car_type, u.u_car_regno, u.u_car_bookdate, u.u_car_book_status,
                   v.v_dpic
            FROM tms_user u
            JOIN tms_vehicle v ON u.u_car_regno = v.v_reg_no
            WHERE u.u_id = ? AND u.u_car_book_status IS NOT NULL AND u.u_car_book_status != ''
        ";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('i', $aid);
        $stmt->execute();
        $res = $stmt->get_result();
        $numRows = $res->num_rows;

        $projectFolder = '/' . basename(dirname(__DIR__)) . '/';

        if ($numRows > 0) {
            while ($row = $res->fetch_object()) {
                $imagePath = $projectFolder . 'vendor/img/' . ($row->v_dpic ?: 'placeholder.png');
                ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <img src="<?php echo $imagePath; ?>" class="card-img-top enlargeable rounded-top"
                             style="height: 200px; object-fit: cover; cursor: pointer;"
                             alt="Vehicle Image" data-full="<?php echo $imagePath; ?>">
                        <div class="card-body">
                            <h5 class="card-title mb-1"><?php echo "{$row->u_fname} {$row->u_lname}"; ?></h5>
                            <p class="mb-1"><strong>Phone:</strong> <?php echo $row->u_phone; ?></p>
                            <p class="mb-1"><strong>Vehicle:</strong> <?php echo "{$row->u_car_type} ({$row->u_car_regno})"; ?></p>
                            <p class="mb-1"><strong>Booking Date:</strong> <?php echo $row->u_car_bookdate; ?></p>
                            <p>
                                <strong>Status:</strong>
                                <?php echo $row->u_car_book_status === "Pending"
                                    ? '<span class="badge badge-warning">Pending</span>'
                                    : '<span class="badge badge-success">Approved</span>'; ?>
                            </p>
                        </div>
                        <div class="card-footer bg-transparent text-end">
                            <a href="#" class="btn btn-danger btn-sm cancel-booking-btn" data-uid="<?php echo $row->u_id; ?>">
                                <i class="fas fa-times-circle"></i> Cancel Booking
                            </a>
                        </div>
                    </div>
                </div>
            <?php }
        } else { ?>
            <div class="col-12">
                <div class="alert alert-warning text-center shadow-sm p-4">
                    <h5 class="mb-3"><i class="fas fa-info-circle"></i> No Bookings Found</h5>
                    <p>You have no active bookings at the moment.</p>
                    <a href="user-book-vehicle.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-car-side"></i> Book a Vehicle Now
                    </a>
                </div>
            </div>
        <?php } ?>
    </div>

    <!-- Image Zoom Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content border-0 bg-transparent">
                <div class="modal-body p-0 text-center">
                    <img id="modalImage" class="img-fluid rounded" src="" alt="Zoomed Vehicle Image"
                         style="max-height: 90vh; object-fit: contain;">
                </div>
            </div>
        </div>
    </div>

    <!-- Cancel Booking Confirmation Modal -->
    <div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="cancelModalLabel">Cancel Booking</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    Are you sure you want to cancel this booking?
                    <input type="hidden" id="cancelBookingId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">No, Keep It</button>
                    <button type="button" class="btn btn-danger btn-sm" id="confirmCancelBtn">Yes, Cancel</button>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function () {
        // Image zoom modal
        $('.enlargeable').on('click', function () {
            $('#modalImage').attr('src', $(this).data('full'));
            $('#imageModal').modal('show');
        });

        // Cancel booking logic
        let cancelId = null;

        $('.cancel-booking-btn').on('click', function (e) {
            e.preventDefault();
            cancelId = $(this).data('uid');
            $('#cancelBookingId').val(cancelId);
            $('#cancelModal').modal('show');
        });

        $('#confirmCancelBtn').on('click', function () {
            const userId = $('#cancelBookingId').val();

            $.ajax({
                type: 'POST',
                url: 'user-delete-booking.php',
                data: { delete_booking: true, u_id: userId },
                success: function () {
                    $('#cancelModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Booking Cancelled',
                        text: 'Your booking has been successfully cancelled.',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => location.reload());
                },
                error: function () {
                    $('#cancelModal').modal('hide');
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to cancel the booking. Please try again.'
                    });
                }
            });
        });
    });
</script>

</body>
</html>
