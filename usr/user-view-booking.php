<?php
session_start();
include('vendor/inc/config.php');
include('vendor/inc/checklogin.php');
check_login();

$aid = $_SESSION['u_id'];
global $mysqli;
$projectFolder = '/' . basename(dirname(__DIR__)) . '/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include("vendor/inc/head.php"); ?>

    <!-- Bootstrap 5.3 & Font Awesome 6.4 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- SweetAlert -->
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>

    <!-- Custom Styling -->
    <style>
        body { background-color: #f4f6f9; }
        .vehicle-img { height: 200px; object-fit: cover; border-radius: 8px; cursor: pointer; }
        .vehicle-card .card {
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transition: transform 0.2s ease;
            border-radius: 12px;
        }
        .vehicle-card .card:hover { transform: translateY(-4px); }
        .modal-content { border-radius: 12px; }
    </style>
    <title>My Bookings</title>
</head>

<body>
<div class="container-fluid py-3">

    <!-- Header -->
    <div class="card mb-3 shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center bg-info text-white">
            <h4 class="mb-0"><i class="fas fa-list"></i> My Bookings</h4>
            <a href="user-dashboard.php" class="btn btn-light btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
        </div>
    </div>

    <!-- Booking Cards -->
    <div class="row" id="bookingCards">
        <?php
        $query = "
            SELECT b.booking_id, b.book_from_date, b.book_to_date, b.status, b.created_at, b.remarks, b.admin_remarks,
               v.v_name, v.v_dpic, 
               u.u_fname, u.u_lname, u.u_phone, u.u_car_type, u.u_car_regno
            FROM tms_booking b 
            JOIN tms_vehicle v ON b.vehicle_id = v.v_id
            JOIN tms_user u ON b.user_id = u.u_id
            WHERE u.u_id = ?

        ";

        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('i', $aid);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows > 0) {
            while ($row = $res->fetch_object()) {
                $imagePath = $projectFolder . 'vendor/img/' . ($row->v_dpic ?: 'placeholder.png');
                ?>
                <div class="col-md-4 mb-4 vehicle-card">
                    <div class="card h-100">
                        <img src="<?= $imagePath ?>" class="vehicle-img card-img-top"
                             alt="Vehicle Image" data-full="<?= $imagePath ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?= "$row->v_name" ?></h5>
                            <p class="mb-1"><strong>Booking Id:</strong> <?= $row->booking_id ?></p>
                            <p class="mb-1"><strong>Booking Created Date:</strong> <?= $row->created_at ?></p>
                            <p class="mb-1"><strong>From Date:</strong> <?= $row->book_from_date ?></p>
                            <p class="mb-1"><strong>To Date:</strong> <?= $row->book_to_date ?></p>
                            <?php
                                $fullRemarks = htmlspecialchars($row->remarks);
                                $shortRemarks = strlen($fullRemarks) > 20 ? substr($fullRemarks, 0, 20) . '...' : $fullRemarks;
                                ?>
                                <p class="mb-1">
                                    <strong>Message:</strong>
                                    <span class="remarks-text" data-full="<?= $fullRemarks ?>">
                                <?= $shortRemarks ?>
                                        <?php if (strlen($fullRemarks) > 20): ?>
                                            <a href="#" class="text-primary toggle-remarks" style="font-size: 0.875rem;">Read more</a>
                                        <?php endif; ?>
                                </span>
                            </p>

                            <?php if (!empty($row->admin_remarks)): ?>
                                <p class="mb-1">
                                    <strong>Admin Remark:</strong> <?= htmlspecialchars($row->admin_remarks); ?>
                                </p>
                            <?php endif; ?>


                        </div>
                        <div class="card-footer bg-transparent d-flex justify-content-between align-items-center">
                            <div>
                                <strong>Status:</strong>
                                <?php if ($row->status == "Pending"): ?>
                                    <span class="badge bg-warning text-dark">Pending</span>
                                <?php elseif ($row->status == "Approved"): ?>
                                    <span class="badge bg-success">Approved</span>
                                <?php else: ?>
                                    <span class="badge bg-danger"><?= $row->status ?></span>
                                <?php endif; ?>
                            </div>

                            <?php if ($row->status == 'Pending'): ?>
                                <a href="#" class="btn btn-sm btn-danger cancel-booking-btn" data-booking-id="<?= $row->booking_id ?>">
                                    <i class="fas fa-times-circle"></i> Cancel Booking
                                </a>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            <?php }
        } else { ?>
            <div class="col-12">
                <div class="alert alert-warning text-center shadow-sm p-4">
                    <h5 class="mb-3"><i class="fas fa-info-circle"></i> No Bookings Found</h5>
                    <p>You have no active bookings at the moment.</p>
                    <a href="user-dashboard.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-car-side"></i> Book a Vehicle Now
                    </a>
                </div>
            </div>
        <?php } ?>
    </div>

    <!-- Image Zoom Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content bg-transparent border-0">
                <div class="modal-body text-center p-0">
                    <img id="modalImage" class="img-fluid rounded" src="" alt="Zoomed Image"
                         style="max-height: 90vh; object-fit: contain;">
                </div>
            </div>
        </div>
    </div>

    <!-- Cancel Booking Modal -->
    <div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Cancel Booking</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to cancel this booking?
                    <input type="hidden" id="cancelBookingId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">No, Keep It</button>
                    <button type="button" class="btn btn-danger btn-sm" id="confirmCancelBtn">Yes, Cancel</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>

    $('.toggle-remarks').on('click', function (e) {
        e.preventDefault();
        const span = $(this).closest('.remarks-text');
        const full = span.data('full');
        const isExpanded = span.hasClass('expanded');

        if (!isExpanded) {
            span.html(full + ' <a href="#" class="text-primary toggle-remarks" style="font-size: 0.875rem;">Show less</a>');
            span.addClass('expanded');
        } else {
            const short = full.substring(0, 20) + '...';
            span.html(short + ' <a href="#" class="text-primary toggle-remarks" style="font-size: 0.875rem;">Read more</a>');
            span.removeClass('expanded');
        }

        // Rebind event to new link (because .html() removed old binding)
        $('.toggle-remarks').off('click').on('click', arguments.callee);
    });


    $(document).ready(function () {
        $('.vehicle-img').on('click', function () {
            $('#modalImage').attr('src', $(this).data('full'));
            $('#imageModal').modal('show');
        });

        let cancelId = null;
        $('.cancel-booking-btn').on('click', function (e) {
            e.preventDefault();
            cancelId = $(this).data('booking-id');
            $('#cancelBookingId').val(cancelId);
            $('#cancelModal').modal('show');
        });

        $('#confirmCancelBtn').on('click', function () {
            const bookingId = $('#cancelBookingId').val();

            $.ajax({
                type: 'POST',
                url: 'user-delete-booking.php',  // Corrected path
                data: { delete_booking: true, booking_id: bookingId },
                success: function () {
                    $('#cancelModal').modal('hide');
                    swal("Booking Cancelled", "Your booking has been successfully cancelled.", "success")
                        .then(() => location.reload());
                },
                error: function () {
                    $('#cancelModal').modal('hide');
                    swal("Error", "Failed to cancel the booking. Please try again.", "error");
                }
            });
        });
    });
</script>


</body>
</html>
