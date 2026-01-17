<?php
session_start();

// Handle logout on POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    
    // Prevent caching
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    
    // Use JS to replace history and redirect
    echo '<script type="text/javascript">';
    echo 'window.location.replace("../index.php");';
    echo '</script>';
    exit;
}

// Include configs and auth
require_once 'vendor/inc/config.php';
require_once 'vendor/inc/checklogin.php';
check_login();
$aid = $_SESSION['a_id'] ?? null;

// Prevent caching to ensure back button doesn't show stale page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Redirect if not logged in
if (!$aid) {
    header("Location: ../index.php");
    exit;
}

// Fetch admin profile
$admin = null;
global $mysqli;
// Updated to use 'users' table as per new schema
if ($stmt = $mysqli->prepare("SELECT first_name, last_name, email FROM users WHERE id = ?")) {
    $stmt->bind_param("i", $aid);
    $stmt->execute();
    global $first_name, $last_name, $email;
    $stmt->bind_result($first_name, $last_name, $email);
    $stmt->fetch();
    $admin = (object) ['a_name' => $first_name . ' ' . $last_name, 'a_email' => $email];
    $stmt->close();
}

// Utility function to count rows
function count_items($table, $where = null, $param = null) {
    global $mysqli;
    $query = "SELECT COUNT(*) FROM $table" . ($where ? " WHERE $where = ?" : "");
    $stmt = $mysqli->prepare($query);
    if ($where) $stmt->bind_param("s", $param);
    $stmt->execute();
    global $count;
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return $count;
}

// Updated counts based on new schema roles
$employee_count = count_items('users', 'role', 'EMPLOYEE');
$driver_count = count_items('users', 'role', 'DRIVER');
$manager_count = count_items('users', 'role', 'MANAGER');
$admin_count = count_items('users', 'role', 'ADMIN');
$vehicle_count = count_items('vehicles'); // Table name changed to 'vehicles'

date_default_timezone_set("Asia/Kolkata");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Vehicle Booking System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free/css/all.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #e0f7fa, #f8f9fa);
            font-family: 'Segoe UI', sans-serif;
        }
        .hover-translate {
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            cursor: pointer; /* Indicate clickable */
        }
        .hover-translate:hover {
            transform: translateY(-4px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.08);
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
            background-color: #fff;
            border: 1px solid #dee2e6;
            border-radius: 50px;
            padding: 10px 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            cursor: pointer;
        }
        
        /* Custom Stats Styling */
        .stats-container {
            display: flex;
            justify-content: space-around;
            padding: 15px 0;
        }
        .stat-box {
            text-align: center;
            padding: 10px 20px;
            border-radius: 10px;
            background-color: #f8f9fa;
            min-width: 100px;
        }
        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            display: block;
        }
        .stat-label {
            font-size: 0.85rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .text-primary-custom { color: #0d6efd; }
        .text-success-custom { color: #198754; }
        .text-info-custom { color: #0dcaf0; }
        .text-warning-custom { color: #ffc107; }
    </style>
    <script>
        // Force browser back button to redirect to project homepage
        history.pushState(null, null, location.href);
        window.onpopstate = function () {
            window.location.replace("../index.php");
        };
    </script>
</head>
<body>
<div class="container py-4">
    <!-- Header -->
    <div class="dashboard-header mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">Admin Dashboard</h2>
            <p class="text-muted mb-0">Manage the Vehicle Booking System efficiently</p>
        </div>
        <!-- Profile Card with Logout Dropdown -->
        <div class="profile-container">
            <div class="profile-card d-flex align-items-center justify-content-between gap-3 hover-translate p-2 bg-white rounded-3 shadow-sm" id="profileToggle">
                <div class="d-flex align-items-center gap-3">
                    <i class="fas fa-user-shield fa-2x text-primary"></i>
                    <div>
                        <h6 class="mb-0 text-dark"><?= htmlspecialchars($admin->a_name ?? 'Admin'); ?></h6>
                        <small class="text-muted"><?= htmlspecialchars($admin->a_email ?? ''); ?></small>
                    </div>
                </div>
                <!-- ▼ Arrow Icon to suggest dropdown -->
                <i id="profileArrow" class="fas fa-chevron-down text-muted transition"></i>
            </div>

            <!-- Logout Drawer -->
            <div id="logoutDrawer" class="logout-drawer">
                <form method="post" class="m-0">
                    <button type="submit" name="logout">Logout</button>
                </form>
            </div>

        </div>


        <style>
            .profile-container {
                position: relative;
                width: fit-content;
                z-index: 10;
                cursor: pointer;
            }

            .profile-card {
                position: relative;
                z-index: 20;
                transition: box-shadow 0.3s;
            }

            /* Glow on hover to hint interaction */
            .profile-card:hover {
                box-shadow: 0 0 10px rgba(0, 123, 255, 0.3);
            }

            /* Arrow animation */
            #profileArrow {
                transition: transform 0.3s ease;
            }

            .logout-drawer.active + #profileArrow {
                transform: rotate(180deg);
            }

            /* Logout drawer (unchanged from before) */
            .logout-drawer {
                position: absolute;
                top: 100%;
                right: 0;
                margin-top: -8px;
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 0.75rem;
                box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
                width: 100%;
                transform: translateY(-20px) scale(0.95);
                opacity: 0;
                visibility: hidden;
                transition: opacity 0.3s ease, transform 0.3s ease;
                z-index: 5;
                overflow: hidden;
            }

            .logout-drawer.active {
                transform: translateY(8px) scale(1);
                opacity: 1;
                visibility: visible;
            }

            .logout-drawer button {
                border: none;
                background: #f8f9fa;
                width: 100%;
                padding: 10px;
                font-weight: 500;
                border-radius: 0.75rem;
                transition: background 0.3s;
            }

            .logout-drawer button:hover {
                background: #dc3545;
                color: #fff;
            }

        </style>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const toggle = document.getElementById('profileToggle');
                const drawer = document.getElementById('logoutDrawer');
                const arrow = document.getElementById('profileArrow');

                toggle.addEventListener('click', function (e) {
                    e.stopPropagation();
                    drawer.classList.toggle('active');
                    arrow.classList.toggle('rotated');
                });

                document.addEventListener('click', function (e) {
                    if (!toggle.contains(e.target) && !drawer.contains(e.target)) {
                        drawer.classList.remove('active');
                        arrow.classList.remove('rotated');
                    }
                });
            });

        </script>





    </div>

    <!-- Dashboard Cards -->
    <div class="row g-4">
        <!-- Users Card -->
        <div class="col-md-6">
            <div class="card text-center hover-translate shadow-sm h-100" onclick="window.location.replace('admin-view-user.php')">
                <div class="card-body">
                    <h5 class="card-title mb-3"><i class="fas fa-users text-primary me-2"></i> System Users</h5>
                    
                    <div class="stats-container">
                        <div class="stat-box">
                            <span class="stat-number text-primary-custom" id="employee-count"><?= $employee_count; ?></span>
                            <span class="stat-label">Employees</span>
                        </div>
                        <div class="stat-box">
                            <span class="stat-number text-success-custom" id="driver-count"><?= $driver_count; ?></span>
                            <span class="stat-label">Drivers</span>
                        </div>
                        <div class="stat-box">
                            <span class="stat-number text-info-custom" id="manager-count"><?= $manager_count; ?></span>
                            <span class="stat-label">Managers</span>
                        </div>
                        <div class="stat-box">
                            <span class="stat-number text-warning-custom" id="admin-count"><?= $admin_count; ?></span>
                            <span class="stat-label">Admins</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vehicles Card -->
        <div class="col-md-6">
            <div class="card text-center hover-translate shadow-sm h-100" onclick="window.location.replace('admin-view-vehicle.php')">
                <div class="card-body d-flex flex-column justify-content-center align-items-center">
                    <i class="fas fa-bus fa-3x text-warning mb-3"></i>
                    <h5 class="card-title" id="vehicle-count"><?= $vehicle_count . " Vehicles"; ?></h5>
                    <p class="text-muted">Total registered vehicles in fleet</p>
                </div>
            </div>
        </div>
    </div>



    <!-- Bookings Table -->
    <div class="card mb-3 mt-4">
        <div class="card-header">
            <i class="fas fa-bus"></i> Recent Bookings
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover" id="dataTable" width="100%">
                    <thead class="table-light">
                    <tr>
                        <th>B_ID</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Vehicle Type</th>
                        <th>Reg No</th>
                        <th>Booked ON</th>
                        <th>Booking For Dates</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <!-- Data will be populated by JS -->
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer text-muted small text-end" id="last-updated">
            Last updated at <?= date("h:i A"); ?>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function () {
        const table = $('#dataTable').DataTable();

        function fetchData() {
            $.ajax({
                url: 'fetch-dashboard-data.php',
                method: 'GET',
                dataType: 'json',
                success: function (data) {
                    // Update counts
                    $('#employee-count').text(data.employee_count);
                    $('#driver-count').text(data.driver_count);
                    $('#manager-count').text(data.manager_count);
                    $('#admin-count').text(data.admin_count);

                    $('#vehicle-count').text(data.vehicle_count + ' Vehicles');
                    $('#last-updated').text('Last updated at ' + data.last_updated);

                    // Update table
                    table.clear();
                    data.bookings.forEach(function (row) {
                        const statusClass = {
                            'Pending': 'warning text-dark',
                            'Approved': 'success',
                            'Completed': 'secondary',
                            'Rejected': 'danger',
                            'Cancelled': 'danger'
                        };
                        const badgeClass = statusClass[row.status] || 'secondary';
                        
                        let actions = '';
                        if (row.status === 'Pending') {
                            actions += `<a href="javascript:void(0);" onclick="window.location.replace('admin-approve-booking.php?booking_id=${row.booking_id}')" class="btn btn-success btn-sm"><i class="fa fa-check"></i></a> `;
                        }
                        actions += `<a href="javascript:void(0);" onclick="window.location.replace('admin-delete-booking.php?booking_id=${row.booking_id}')" class="btn btn-danger btn-sm"><i class="fa fa-trash"></i></a>`;

                        const fromDate = new Date(row.book_from_date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
                        const toDate = new Date(row.book_to_date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
                        
                        const bookedOn = new Date(row.created_at).toLocaleString('en-GB', { 
                            day: '2-digit', 
                            month: 'short', 
                            year: '2-digit', 
                            hour: '2-digit', 
                            minute: '2-digit', 
                            hour12: true 
                        }).replace(',', '');

                        table.row.add([
                            row.booking_id,
                            `${row.u_fname} ${row.u_lname}`,
                            row.u_phone,
                            row.v_name,
                            row.v_reg_no,
                            bookedOn,
                            `${fromDate} → ${toDate}`,
                            `<span class="badge bg-${badgeClass}">${row.status}</span>`,
                            actions
                        ]);
                    });
                    table.draw(false); // false to keep pagination
                }
            });
        }

        // Fetch data every 5 seconds
        setInterval(fetchData, 5000);
        fetchData(); // Initial fetch
    });
</script>
</body>
</html>
