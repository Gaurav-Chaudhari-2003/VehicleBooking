<?php
global $mysqli;
session_start();
include('vendor/inc/config.php');
include('vendor/inc/checklogin.php');
$aid = $_SESSION['u_id'] ?? null;

if ($aid) {
    $userQuery = $mysqli->prepare("SELECT u_fname, u_lname, u_email, u_phone FROM tms_user WHERE u_id = ?");
    $userQuery->bind_param('i', $aid);
    $userQuery->execute();
    $userResult = $userQuery->get_result();
    $user = $userResult->fetch_object();
} else {
    $user = null;
}

check_login();
$aid = $_SESSION['u_id'];
$baseURL = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/';
$projectFolder = '/' . basename(dirname(__DIR__)) . '/';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>User Dashboard - Vehicle Booking System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #e0f7fa, #f8f9fa);
            font-family: 'Segoe UI', sans-serif;
        }

        .hover-translate {
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }

        .hover-translate:hover {
            transform: translateY(-4px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
        }

        .card {
            border-radius: 1rem;
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .profile-card {
            background-color: #ffffff;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 10px 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .profile-card h6 {
            margin: 0;
            font-weight: 600;
        }

        .vehicle-card img {
            height: 200px;
            object-fit: cover;
            border-radius: 10px 10px 0 0;
        }

        .vehicle-card .card {
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        @media (max-width: 576px) {
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>

<body>
<div class="container py-4">
    <!-- Header -->
    <div class="dashboard-header mb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
                <h2 class="fw-bold text-dark mb-1">User Dashboard</h2>
                <p class="text-muted mb-0">Welcome to the Vehicle Booking System</p>
            </div>
        </div>

        <div class="profile-card d-flex align-items-center gap-3 p-2 px-3 bg-white shadow-sm rounded-pill border border-light hover-translate"
             id="loadProfileBtn" style="cursor:pointer;">
            <i class="fas fa-user-circle fa-2x text-primary"></i>
            <div class="text-start">
                <?php if ($user): ?>
                    <h6 class="mb-0 text-dark fw-semibold"><?php echo htmlspecialchars($user->u_fname . ' ' . $user->u_lname); ?></h6>
                    <small class="text-muted"><?php echo htmlspecialchars($user->u_phone ?: $user->u_email); ?></small>
                <?php else: ?>
                    <h6 class="mb-0 text-dark fw-semibold">User</h6>
                    <small class="text-muted">No info available</small>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Dashboard Section -->
    <div class="dashboard-section py-4">
        <div class="row justify-content-center g-4">

            <!-- My Bookings Card -->
            <div class="col-sm-10 col-md-6 col-lg-4">
                <div class="card hover-translate bg-success text-white h-100 shadow-sm text-center" id="loadBookingsBtn" style="cursor:pointer;">
                    <div class="card-body d-flex flex-column align-items-center justify-content-center">
                        <i class="fas fa-clipboard-list fa-2x mb-3"></i>
                        <h5 class="fw-semibold">My Bookings</h5>
                    </div>
                    <div class="card-footer text-white small text-center">
                        View Bookings <i class="fas fa-angle-right ms-1"></i>
                    </div>
                </div>
            </div>

            <!-- Book Vehicle Card -->
            <div class="col-sm-10 col-md-6 col-lg-4">
                <div class="card hover-translate bg-warning text-dark h-100 shadow-sm text-center" id="loadBookingFormBtn" style="cursor:pointer;">
                    <div class="card-body d-flex flex-column align-items-center justify-content-center">
                        <i class="fas fa-car-side fa-2x mb-3"></i>
                        <h5 class="fw-semibold">Book Vehicle</h5>
                    </div>
                    <div class="card-footer text-dark small text-center">
                        Book Now <i class="fas fa-angle-right ms-1"></i>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Dynamic Content Container -->
    <div class="mt-5" id="dynamicContent"></div>

</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    $(document).ready(function () {
        // Load the booking page (usr-book-vehicle.php) by default on page load
        $('#dynamicContent').load('usr-book-vehicle.php');

        // When "My Bookings" card is clicked
        $('#loadBookingsBtn').on('click', function () {
            $('#dynamicContent').html('<div class="text-center py-5"><div class="spinner-border text-success" role="status"></div></div>');
            $('#dynamicContent').load('user-view-booking.php');
        });

        // When "Book Vehicle" card is clicked
        $('#loadBookingFormBtn').on('click', function () {
            $('#dynamicContent').html('<div class="text-center py-5"><div class="spinner-border text-warning" role="status"></div></div>');
            $('#dynamicContent').load('usr-book-vehicle.php');
        });

        // When "User Profile" card is clicked
        $('#loadProfileBtn').on('click', function () {
            $('#dynamicContent').html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>');
            $('#dynamicContent').load('user-view-profile.php');
        });

    });
</script>

</body>

</html>
