<?php
session_start();
include('admin/vendor/inc/config.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include("vendor/inc/head.php"); ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="CMPDI RI-4 Nagpur Vehicle Booking System - Official government portal for vehicle reservations">
    <meta name="author" content="CMPDI RI-4 Nagpur">
    <title>CMPDI RI-4 Nagpur | Official Vehicle Booking Portal</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #fafafa; /* Light background for a clean, professional look */
            color: #333; /* Standard dark text color for readability */
        }

        .hero-section {
            color: white;
            padding: 120px 0 80px;
            text-align: center;
        }
        .hero-section h1 {
            font-size: 3rem;
            font-weight: bold;
        }
        .vehicle-section {
            padding: 60px 0;
        }
        .vehicle-card img {
            max-height: 180px;
            object-fit: cover;
        }
        .vehicle-card {
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .testimonial-section {
            background-color: #e0f2f1; /* Soft teal background for testimonials */
            padding: 60px 0;
        }
        .testimonial {
            background: white;
            padding: 1.5rem;
            border-left: 4px solid #00796b; /* Dark teal for testimonial accents */
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .footer {
            background-color: #004d40; /* Dark teal for footer to match the navbar */
            padding: 1.5rem 0;
            margin-top: 60px;
        }
        .btn-success {
            background-color: #00796b; /* Deep teal for primary buttons */
            border-color: #00796b;
        }
        .btn-success:hover {
            background-color: #004d40; /* Darken button on hover */
            border-color: #004d40;
        }
    </style>
</head>
<body>

<?php include("vendor/inc/nav.php"); ?>

<!-- Hero Banner -->
<section class="hero-section" style="background-color: #e0f2f1;"> <!-- fallback if the image fails -->
    <div class="container text-dark"> <!-- added text-dark class -->
        <h1 class="mb-5" style="color: #004d40;">CMPDI RI-4, Nagpur</h1>
        <p class="mb-5" style="color: #00695c;">Official Vehicle Booking System for Internal Transport Requests</p>
        <a href="usr/user-login.php" class="btn btn-success btn-lg">Book a Vehicle</a>
    </div>
</section>


<!-- Vehicle Types -->
<section class="vehicle-section text-center">
    <div class="container">
        <h2 class="mb-5">Government Vehicle Fleet</h2>
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card vehicle-card">
                    <img src="vendor/img/city.png" class="card-img-top" alt="Honda City">
                    <div class="card-body">
                        <h5 class="card-title">Honda City</h5>
                        <p class="card-text">Ideal for executive city visits and official pickups.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card vehicle-card">
                    <img src="vendor/img/Marazzo.png" class="card-img-top" alt="Marazzo">
                    <div class="card-body">
                        <h5 class="card-title">Mahindra Marazzo</h5>
                        <p class="card-text">Spacious seating for departmental groups and team outings.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card vehicle-card">
                    <img src="vendor/img/bolero.png" class="card-img-top" alt="Bolero">
                    <div class="card-body">
                        <h5 class="card-title">Bolero</h5>
                        <p class="card-text">Perfect for rugged terrain and project site travel.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials -->
<section class="testimonial-section">
    <div class="container">
        <h2 class="text-center mb-5">Staff Testimonials</h2>
        <div class="row">
            <div class="col-md-4 mb-3">
                <div class="testimonial">
                    <p>“Highly efficient booking system — we now manage all transport requests seamlessly.”</p>
                    <small>- A. Kumar, Transport Dept.</small>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="testimonial">
                    <p>“User-friendly and saves a lot of paperwork. Very helpful.”</p>
                    <small>— R. Singh, Admin Office</small>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="testimonial">
                    <p>“The verification process is smooth and fast. Excellent update!”</p>
                    <small>- P. Joshi, CMPDI RI-4</small>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action -->
<div class="container text-center my-5">
    <a href="usr/user-login.php" class="btn btn-success btn-lg">Login to Book</a>
</div>

<!-- Footer -->
<footer class="footer text-white">
    <div class="container">
        <p>&copy; 2025 CMPDI RI-4, Nagpur | Central Mine Planning and Design Institute</p>
    </div>
</footer>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
