<?php
session_start();
include('../DATABASE FILE/config.php');
include('../DATABASE FILE/checklogin.php');
check_login();

$aid = $_SESSION['u_id'];
global $mysqli;
$projectFolder = '/' . basename(dirname(__DIR__)) . '/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Bookings | Vehicle Booking System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Include Global Theme -->
    <!-- Note: This page is usually loaded via AJAX, but we include styles for direct access or fallback -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- SweetAlert -->
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>

    <!-- Custom Styling -->
    <style>
        /* Scoped styles for booking list */
        .vehicle-img { 
            height: 180px; 
            object-fit: cover; 
            border-radius: 15px 15px 0 0; 
            cursor: pointer; 
            transition: opacity 0.3s;
        }
        .vehicle-img:hover {
            opacity: 0.9;
        }
        
        .vehicle-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            background: #fff;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .vehicle-card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .card-body {
            padding: 20px;
            flex: 1;
        }
        
        .card-title {
            font-weight: 700;
            color: #333;
            margin-bottom: 15px;
        }
        
        .booking-detail {
            font-size: 0.9rem;
            color: #555;
            margin-bottom: 8px;
            display: flex;
            align-items: flex-start;
        }
        
        .booking-detail i {
            width: 20px;
            margin-right: 10px;
            color: #00796b; /* Theme secondary color */
            margin-top: 3px;
        }
        
        .card-footer {
            background-color: #fff;
            border-top: 1px solid #f0f0f0;
            padding: 15px 20px;
            border-radius: 0 0 15px 15px;
        }
        
        .badge {
            padding: 8px 12px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 0.8rem;
        }
        
        .bg-warning { background-color: #ffc107 !important; color: #333; }
        .bg-success { background-color: #28a745 !important; }
        .bg-danger { background-color: #dc3545 !important; }
        .bg-secondary { background-color: #6c757d !important; }
        
        .modal-content { 
            border-radius: 20px; 
            border: none;
            box-shadow: 0 15px 40px rgba(0,0,0,0.2);
        }
        
        .modal-header {
            border-bottom: 1px solid #eee;
            padding: 20px 30px;
            border-radius: 20px 20px 0 0;
        }
        
        .header-card {
            background: #fff;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            border-left: 5px solid #00796b;
        }
        
        .btn-cancel {
            border-radius: 30px;
            font-size: 0.85rem;
            padding: 6px 15px;
            font-weight: 600;
        }
    </style>
</head>

<body>
<div class="container-fluid">

    <!-- Header -->
    <div class="header-card d-flex justify-content-between align-items-center">
        <div>
            <h4 class="mb-1 fw-bold text-dark">My Bookings</h4>
            <p class="text-muted mb-0 small">Manage and track your vehicle requests</p>
        </div>
        <!-- Back button is handled by dashboard navigation usually, but kept for direct access -->
        <!-- <a href="user-dashboard.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3"><i class="fas fa-arrow-left me-1"></i> Back</a> -->
    </div>

    <!-- Booking Cards -->
    <div class="row g-4" id="bookingCards">
        <?php
        // Updated query to match new schema: bookings, vehicles, users tables
        $query = "
            SELECT b.id as booking_id, b.from_datetime, b.to_datetime, b.status, b.created_at, b.purpose,
               v.name as v_name, v.image as v_dpic, v.reg_no as v_regno,
               u.first_name, u.last_name, u.phone
            FROM bookings b 
            JOIN vehicles v ON b.vehicle_id = v.id
            JOIN users u ON b.user_id = u.id
            WHERE u.id = ?
            ORDER BY b.created_at DESC
        ";

        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('i', $aid);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows > 0) {
            while ($row = $res->fetch_object()) {
                $imagePath = $projectFolder . 'vendor/img/' . ($row->v_dpic ?: 'placeholder.png');
                
                // Format Dates
                $createdDate = date('d M Y, h:i A', strtotime($row->created_at));
                $fromDate = date('d M Y, h:i A', strtotime($row->from_datetime));
                $toDate = date('d M Y', strtotime($row->to_datetime));
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="vehicle-card">
                        <div class="position-relative">
                            <img src="<?= $imagePath ?>" class="vehicle-img w-100"
                                 alt="Vehicle Image" data-full="<?= $imagePath ?>">
                            <span class="position-absolute top-0 end-0 m-3 badge bg-light text-dark shadow-sm">
                                #<?= $row->booking_id ?>
                            </span>
                        </div>
                        
                        <div class="card-body">
                            <h5 class="card-title d-flex justify-content-between align-items-center">
                                <?= "$row->v_name" ?>
                                <small class="text-muted fw-normal fs-6"><?= $row->v_regno ?></small>
                            </h5>
                            
                            <div class="booking-detail">
                                <i class="far fa-calendar-plus"></i>
                                <div>
                                    <small class="text-muted d-block">Booked On</small>
                                    <span class="fw-semibold"><?= $createdDate ?></span>
                                </div>
                            </div>
                            
                            <div class="row mt-2">
                                <div class="col-6">
                                    <div class="booking-detail">
                                        <i class="fas fa-plane-departure text-success"></i>
                                        <div>
                                            <small class="text-muted d-block">From</small>
                                            <span class="fw-semibold small"><?= $fromDate ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="booking-detail">
                                        <i class="fas fa-plane-arrival text-danger"></i>
                                        <div>
                                            <small class="text-muted d-block">To</small>
                                            <span class="fw-semibold small"><?= $toDate ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3 pt-3 border-top">
                                <small class="text-muted d-block mb-1">Purpose</small>
                                <?php
                                    $fullRemarks = htmlspecialchars($row->purpose);
                                    $shortRemarks = strlen($fullRemarks) > 50 ? substr($fullRemarks, 0, 50) . '...' : $fullRemarks;
                                ?>
                                <p class="mb-0 small text-secondary fst-italic">
                                    <span class="remarks-text" data-full="<?= $fullRemarks ?>">
                                        <?= $shortRemarks ?>
                                        <?php if (strlen($fullRemarks) > 50): ?>
                                            <a href="#" class="text-primary toggle-remarks text-decoration-none fw-bold ms-1">Read more</a>
                                        <?php endif; ?>
                                    </span>
                                </p>
                            </div>

                        </div>
                        <div class="card-footer d-flex justify-content-between align-items-center">
                            <div>
                                <?php if ($row->status == "PENDING"): ?>
                                    <span class="badge bg-warning"><i class="fas fa-clock me-1"></i> Pending</span>
                                <?php elseif ($row->status == "APPROVED"): ?>
                                    <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> Approved</span>
                                <?php elseif ($row->status == "REJECTED"): ?>
                                    <span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i> Rejected</span>
                                <?php elseif ($row->status == "CANCELLED"): ?>
                                    <span class="badge bg-secondary"><i class="fas fa-ban me-1"></i> Cancelled</span>
                                <?php else: ?>
                                    <span class="badge bg-info"><?= $row->status ?></span>
                                <?php endif; ?>
                            </div>

                            <?php if ($row->status == 'PENDING'): ?>
                                <button class="btn btn-outline-danger btn-cancel cancel-booking-btn" data-booking-id="<?= $row->booking_id ?>">
                                    Cancel
                                </button>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            <?php }
        } else { ?>
            <div class="col-12">
                <div class="alert alert-light text-center shadow-sm p-5 rounded-4 border-0">
                    <div class="mb-3">
                        <i class="fas fa-clipboard-list fa-4x text-muted opacity-25"></i>
                    </div>
                    <h5 class="fw-bold text-secondary">No Bookings Found</h5>
                    <p class="text-muted mb-4">You haven't made any vehicle booking requests yet.</p>
                    <button onclick="$('#loadBookingFormBtn').click()" class="btn btn-success rounded-pill px-4 shadow-sm">
                        <i class="fas fa-plus me-2"></i> Book a Vehicle
                    </button>
                </div>
            </div>
        <?php } ?>
    </div>

    <!-- Image Zoom Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content bg-transparent border-0 shadow-none">
                <div class="modal-body text-center p-0">
                    <img id="modalImage" class="img-fluid rounded-3 shadow-lg" src="" alt="Zoomed Image"
                         style="max-height: 90vh; object-fit: contain;">
                </div>
            </div>
        </div>
    </div>

    <!-- Cancel Booking Modal -->
    <div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white border-0">
                    <h5 class="modal-title fw-bold"><i class="fas fa-exclamation-triangle me-2"></i> Cancel Booking</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <p class="mb-0 fs-5">Are you sure you want to cancel this booking request?</p>
                    <p class="text-muted small">This action cannot be undone.</p>
                    <input type="hidden" id="cancelBookingId">
                </div>
                <div class="modal-footer justify-content-center border-0 pb-4">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">No, Keep It</button>
                    <button type="button" class="btn btn-danger rounded-pill px-4 shadow-sm" id="confirmCancelBtn">Yes, Cancel Booking</button>
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
            span.html(full + ' <a href="#" class="text-primary toggle-remarks text-decoration-none fw-bold ms-1" style="font-size: 0.875rem;">Show less</a>');
            span.addClass('expanded');
        } else {
            const short = full.substring(0, 50) + '...';
            span.html(short + ' <a href="#" class="text-primary toggle-remarks text-decoration-none fw-bold ms-1" style="font-size: 0.875rem;">Read more</a>');
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
                        .then(() => {
                            // Reload content via dashboard logic if possible, or reload page
                            if(typeof loadContent === 'function') {
                                loadContent('user-view-booking.php', '#loadBookingsBtn');
                            } else {
                                location.reload();
                            }
                        });
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
