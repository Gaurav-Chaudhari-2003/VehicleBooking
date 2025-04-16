<?php
session_start();
include('vendor/inc/config.php');
include('vendor/inc/checklogin.php');
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
        body { background-color: #f8f9fa; }
        .card { border-radius: 1rem; }
        .vehicle-card img { height: 200px; object-fit: cover; border-radius: 10px 10px 0 0; }
        .vehicle-card .card { height: 100%; display: flex; flex-direction: column; }
    </style>
</head>

<body>
<div class="container py-4">
    <div class="text-center mb-5">
        <h2>User Dashboard</h2>
        <p class="text-muted">Welcome to the Vehicle Booking System</p>
    </div>

    <!-- Dashboard Cards -->
    <div class="row g-4 dashboard-section">
        <div class="col-md-4">
            <div class="card card-hover bg-primary text-white h-100 shadow-sm">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div><i class="fas fa-user card-body-icon"></i></div>
                    <h5 class="mb-0">My Profile</h5>
                </div>
                <a href="user-view-profile.php" class="card-footer text-white text-decoration-none text-center">
                    View Profile <i class="fas fa-angle-right ms-1"></i>
                </a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-hover bg-success text-white h-100 shadow-sm">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div><i class="fas fa-clipboard-list card-body-icon"></i></div>
                    <h5 class="mb-0">My Bookings</h5>
                </div>
                <a href="user-view-booking.php" class="card-footer text-white text-decoration-none text-center">
                    View Bookings <i class="fas fa-angle-right ms-1"></i>
                </a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-hover bg-warning text-dark h-100 shadow-sm">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div><i class="fas fa-car-side card-body-icon"></i></div>
                    <h5 class="mb-0">Book Vehicle</h5>
                </div>
                <a href="usr-book-vehicle.php" class="card-footer text-dark text-decoration-none text-center">
                    Book Now <i class="fas fa-angle-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Include Available Vehicles Section -->
    <div class="mt-5">
        <?php include('usr-book-vehicle.php'); ?>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
