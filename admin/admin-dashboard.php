<?php
session_start();

// Handle logout on POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: /VehicleBooking/index.php");
    exit;
}

// Include configs and auth
require_once 'vendor/inc/config.php';
require_once 'vendor/inc/checklogin.php';
check_login();
$aid = $_SESSION['a_id'] ?? null;

// Redirect if not logged in
if (!$aid) {
    header("Location: /VehicleBooking/index.php");
    exit;
}

// Fetch admin profile
$admin = null;
if ($stmt = $mysqli->prepare("SELECT a_name, a_email FROM tms_admin WHERE a_id = ?")) {
    $stmt->bind_param("i", $aid);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_object();
    $stmt->close();
}

// Utility function to count rows
function count_items($table, $where = null, $param = null) {
    global $mysqli;
    $query = "SELECT COUNT(*) FROM $table" . ($where ? " WHERE $where = ?" : "");
    $stmt = $mysqli->prepare($query);
    if ($where) $stmt->bind_param("s", $param);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return $count;
}

$user_count = count_items('tms_user', 'u_category', 'User');
$driver_count = count_items('tms_user', 'u_category', 'Driver');
$vehicle_count = count_items('tms_vehicle');

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
    </style>
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
        <?php
        $cards = [
            ['id' => 'user-count', 'count' => $user_count, 'title' => 'Users', 'icon' => 'fas fa-users', 'color' => 'primary', 'link' => 'admin-view-user.php'],
            ['id' => 'driver-count', 'count' => $driver_count, 'title' => 'Drivers', 'icon' => 'fas fa-id-card', 'color' => 'success', 'link' => 'admin-view-driver.php'],
            ['id' => 'vehicle-count', 'count' => $vehicle_count, 'title' => 'Vehicles', 'icon' => 'fas fa-bus', 'color' => 'warning', 'link' => 'admin-view-vehicle.php'],
        ];
        foreach ($cards as $card):
            ?>
            <div class="col-md-4">
                <div class="card text-center hover-translate shadow-sm">
                    <div class="card-body">
                        <i class="<?= $card['icon']; ?> fa-3x text-<?= $card['color']; ?> mb-3"></i>
                        <h5 class="card-title" id="<?= $card['id']; ?>"><?= $card['count'] . " " . $card['title']; ?></h5>
                        <a href="<?= $card['link']; ?>" class="btn btn-outline-<?= $card['color']; ?> btn-sm mt-2">View Details</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>



    <!-- Bookings Table -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="fas fa-bus"></i> Vehicles
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
                    $('#user-count').text(data.user_count + ' Users');
                    $('#driver-count').text(data.driver_count + ' Drivers');
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
                            actions += `<a href="admin-approve-booking.php?booking_id=${row.booking_id}" class="btn btn-success btn-sm"><i class="fa fa-check"></i></a> `;
                        }
                        actions += `<a href="admin-delete-booking.php?booking_id=${row.booking_id}" class="btn btn-danger btn-sm"><i class="fa fa-trash"></i></a>`;

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
