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
include('../DATABASE FILE/config.php');
include('../DATABASE FILE/checklogin.php');
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
    
    <!-- Include Global Theme -->
    <?php include("../vendor/inc/theme-config.php"); ?>
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <style>
        body {
            background-color: #fff; /* Override theme background for a cleaner dashboard */
        }
        
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar styles are now in sidebar.php */
        
        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .profile-dropdown {
            cursor: pointer;
        }
        
        .profile-dropdown .dropdown-menu {
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border: none;
        }
        
        /* Stats Cards */
        .stat-card {
            background: #fff;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s;
            height: 100%;
            border: none;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 15px;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
            line-height: 1;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        /* Table Styling */
        .table-card {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border: none;
            overflow: hidden;
        }
        
        .table-card .card-header {
            background: #fff;
            border-bottom: 1px solid #eee;
            padding: 20px;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .table thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #eee;
            color: #555;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
        }
        
        .badge {
            padding: 6px 10px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 0.75rem;
        }
        
        .bg-warning { background-color: #fff3cd !important; color: #856404; }
        .bg-success { background-color: #d1e7dd !important; color: #0f5132; }
        .bg-danger { background-color: #f8d7da !important; color: #842029; }
        .bg-secondary { background-color: #e2e3e5 !important; color: #41464b; }
        
        /* Colors for stats */
        .icon-primary { background-color: #e3f2fd; color: #0d6efd; }
        .icon-success { background-color: #d1e7dd; color: #198754; }
        .icon-info { background-color: #cff4fc; color: #0dcaf0; }
        .icon-warning { background-color: #fff3cd; color: #ffc107; }
        .icon-purple { background-color: #e0cffc; color: #6f42c1; }
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

<div class="dashboard-container">
    <!-- Sidebar -->
    <?php include("vendor/inc/sidebar.php"); ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="content-header">
            <div>
                <h2 class="fw-bold text-dark mb-0">Dashboard Overview</h2>
                <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($admin->a_name); ?></p>
            </div>
            
            <div class="dropdown profile-dropdown">
                <div class="d-flex align-items-center" data-bs-toggle="dropdown">
                    <div class="text-end me-3 d-none d-md-block">
                        <h6 class="mb-0 text-dark fw-semibold"><?php echo htmlspecialchars($admin->a_name); ?></h6>
                        <small class="text-muted">Administrator</small>
                    </div>
                    <div class="bg-white rounded-circle shadow-sm p-1">
                        <i class="fas fa-user-shield fa-2x text-primary"></i>
                    </div>
                </div>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <form method="post" class="m-0">
                            <button type="submit" name="logout" class="dropdown-item text-danger">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- Stats Grid -->
        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="stat-card" onclick="window.location.href='admin-view-user.php'" style="cursor: pointer;">
                    <div class="stat-icon icon-primary">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="stat-value"><?= $employee_count; ?></div>
                    <div class="stat-label">Employees</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" onclick="window.location.href='admin-view-user.php'" style="cursor: pointer;">
                    <div class="stat-icon icon-success">
                        <i class="fas fa-id-card"></i>
                    </div>
                    <div class="stat-value"><?= $driver_count; ?></div>
                    <div class="stat-label">Drivers</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" onclick="window.location.href='admin-view-vehicle.php'" style="cursor: pointer;">
                    <div class="stat-icon icon-warning">
                        <i class="fas fa-bus"></i>
                    </div>
                    <div class="stat-value"><?= $vehicle_count; ?></div>
                    <div class="stat-label">Total Vehicles</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon icon-purple">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="stat-value"><?= $admin_count; ?></div>
                    <div class="stat-label">Admins</div>
                </div>
            </div>
        </div>

        <!-- Recent Bookings Table -->
        <div class="card table-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-list me-2"></i> Recent Bookings</span>
                <span class="badge bg-secondary fw-normal" id="last-updated">Updated just now</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="dataTable" width="100%">
                        <thead>
                        <tr>
                            <th class="ps-4">ID</th>
                            <th>User</th>
                            <th>Contact</th>
                            <th>Vehicle</th>
                            <th>Reg No</th>
                            <th>Booked On</th>
                            <th>Journey Dates</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <!-- Data populated by JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<script>
    $(document).ready(function () {
        const table = $('#dataTable').DataTable({
            "order": [[ 0, "desc" ]],
            "pageLength": 10,
            "dom": 'rtip', // Hide default search and length change for cleaner look
            "language": {
                "emptyTable": "No bookings found"
            }
        });

        function fetchData() {
            $.ajax({
                url: 'fetch-dashboard-data.php',
                method: 'GET',
                dataType: 'json',
                success: function (data) {
                    // Update counts (optional if you want real-time stats update)
                    // ...

                    $('#last-updated').text('Updated at ' + new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}));

                    // Update table
                    table.clear();
                    data.bookings.forEach(function (row) {
                        const statusClass = {
                            'PENDING': 'bg-warning',
                            'APPROVED': 'bg-success',
                            'COMPLETED': 'bg-secondary',
                            'REJECTED': 'bg-danger',
                            'CANCELLED': 'bg-danger'
                        };
                        const badgeClass = statusClass[row.status] || 'bg-secondary';
                        
                        let actions = `<a href="admin-approve-booking.php?booking_id=${row.booking_id}" class="btn btn-sm btn-outline-primary rounded-pill px-3"><i class="fa fa-edit me-1"></i> Manage</a>`;

                        const fromDate = new Date(row.from_datetime).toLocaleDateString('en-GB', { day: '2-digit', month: 'short' });
                        const toDate = new Date(row.to_datetime).toLocaleDateString('en-GB', { day: '2-digit', month: 'short' });

                        const bookedOn = new Date(row.created_at).toLocaleString('en-GB', { 
                            day: '2-digit', 
                            month: 'short', 
                            hour: '2-digit', 
                            minute: '2-digit', 
                            hour12: true 
                        }).replace(',', '');

                        table.row.add([
                            `<span class="ps-2 fw-bold">${row.booking_id}</span>`,
                            `<div class="d-flex align-items-center">
                                <div><div class="fw-bold">${row.u_fname} ${row.u_lname}</div></div>
                             </div>`,
                            row.u_phone,
                            row.v_name,
                            `<span class="font-monospace">${row.v_reg_no}</span>`,
                            bookedOn,
                            `<small>${fromDate} <i class="fas fa-arrow-right mx-1 text-muted" style="font-size:0.7rem"></i> ${toDate}</small>`,
                            `<span class="badge ${badgeClass}">${row.status}</span>`,
                            `<div class="text-end pe-2">${actions}</div>`
                        ]);
                    });
                    table.draw(false);
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
