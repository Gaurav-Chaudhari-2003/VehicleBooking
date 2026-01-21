<?php
session_start();
include('DATABASE FILE/config.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="CMPDI RI-4 Nagpur Vehicle Booking System - Official government portal for vehicle reservations">
    <meta name="author" content="CMPDI RI-4 Nagpur">
    <title>CMPDI RI-4 Nagpur | Vehicle Booking</title>

    <!-- Include Global Theme -->
    <?php include("vendor/inc/theme-config.php"); ?>
</head>
<body>

<!-- Top Bar for Admin Access -->
<div class="top-bar">
    <?php if(!isset($_SESSION['u_id'])): ?>
        <a href="admin/admin-login.php" class="btn-admin">
            <i class="fas fa-user-shield mr-1"></i> Admin Portal
        </a>
    <?php endif; ?>
</div>

<!-- Hero Section -->
<section class="hero-section">
    <div class="hero-content">
        <img src="https://www.cmpdi.co.in/sites/default/files/cmpdi_new_logo_10012025.png" alt="CMPDI Logo" class="hero-logo">
        <h1 class="hero-title">Vehicle Booking Portal</h1>
        <p class="hero-subtitle">CMPDI Regional Institute-4, Nagpur</p>
        
        <div class="d-flex justify-content-center flex-wrap">
            <?php if(isset($_SESSION['u_id'])): ?>
                <a href="usr/user-dashboard.php" class="btn btn-success btn-hero mr-3 mb-3">
                    <i class="fas fa-tachometer-alt mr-2"></i> Dashboard
                </a>
                <a href="usr/usr-book-vehicle.php" class="btn btn-outline-success btn-hero mb-3">
                    <i class="fas fa-car mr-2"></i> Book Now
                </a>
            <?php else: ?>
                <a href="usr/user-login.php" class="btn btn-success btn-hero mr-3 mb-3">
                    <i class="fas fa-sign-in-alt mr-2"></i> Login
                </a>
                <a href="usr/usr-register.php" class="btn btn-outline-success btn-hero mb-3">
                    <i class="fas fa-user-plus mr-2"></i> Register
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Simple Features Grid -->
<section class="features-section">
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <div class="feature-box">
                    <i class="fas fa-calendar-check feature-icon"></i>
                    <h5 class="feature-title">Easy Booking</h5>
                    <p class="feature-text">Streamlined process to book official vehicles quickly and efficiently.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-box">
                    <i class="fas fa-map-marked-alt feature-icon"></i>
                    <h5 class="feature-title">Smart Routing</h5>
                    <p class="feature-text">Interactive maps with distance estimation for precise journey planning.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-box">
                    <i class="fas fa-shield-alt feature-icon"></i>
                    <h5 class="feature-title">Secure & Official</h5>
                    <p class="feature-text">Authorized access ensuring secure, tracked, and approved transport.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <p class="mb-1">&copy; <?php echo date('Y'); ?> CMPDI RI-4, Nagpur. All Rights Reserved.</p>
        <small>Central Mine Planning & Design Institute</small>
    </div>
</footer>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
