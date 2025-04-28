<?php
session_start();
require_once 'vendor/inc/config.php';
require_once 'vendor/inc/checklogin.php';
check_login();
$aid = $_SESSION['a_id'];

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
    if ($where) {
        $stmt->bind_param("s", $param);
    }
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return $count;
}

$user_count = count_items('tms_user', 'u_category', 'User');
$driver_count = count_items('tms_user', 'u_category', 'Driver');
$vehicle_count = count_items('tms_vehicle');

// Set timezone once
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
        <div class="profile-card d-flex align-items-center gap-3 hover-translate">
            <i class="fas fa-user-shield fa-2x text-primary"></i>
            <div>
                <h6 class="mb-0 text-dark"><?= htmlspecialchars($admin->a_name ?? 'Admin'); ?></h6>
                <small class="text-muted"><?= htmlspecialchars($admin->a_email ?? ''); ?></small>
            </div>
        </div>
    </div>

    <!-- Dashboard Cards -->
    <div class="row g-4">
        <?php
        $cards = [
            ['count' => $user_count, 'title' => 'Users', 'icon' => 'fas fa-users', 'color' => 'primary', 'link' => 'admin-view-user.php'],
            ['count' => $driver_count, 'title' => 'Drivers', 'icon' => 'fas fa-id-card', 'color' => 'success', 'link' => 'admin-view-driver.php'],
            ['count' => $vehicle_count, 'title' => 'Vehicles', 'icon' => 'fas fa-bus', 'color' => 'warning', 'link' => 'admin-view-vehicle.php'],
        ];
        foreach ($cards as $card):
            ?>
            <div class="col-md-4">
                <div class="card text-center hover-translate shadow-sm">
                    <div class="card-body">
                        <i class="<?= $card['icon']; ?> fa-3x text-<?= $card['color']; ?> mb-3"></i>
                        <h5 class="card-title"><?= $card['count'] . " " . $card['title']; ?></h5>
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
                    <?php
                    if ($stmt = $mysqli->prepare("
                        SELECT b.booking_id, b.book_from_date, b.book_to_date, b.status, b.created_at,
                               v.v_name, v.v_reg_no, u.u_fname, u.u_lname, u.u_phone
                        FROM tms_booking b
                        JOIN tms_vehicle v ON b.vehicle_id = v.v_id
                        JOIN tms_user u ON b.user_id = u.u_id
                        ORDER BY b.book_from_date DESC
                    ")) {
                        $stmt->execute();
                        $res = $stmt->get_result();
                        $cnt = 1;
                        while ($row = $res->fetch_object()):
                            ?>
                            <tr>
                                <td><?= htmlspecialchars("$row->booking_id"); ?></td>
                                <td><?= htmlspecialchars("$row->u_fname $row->u_lname"); ?></td>
                                <td><?= htmlspecialchars($row->u_phone); ?></td>
                                <td><?= htmlspecialchars($row->v_name); ?></td>
                                <td><?= htmlspecialchars($row->v_reg_no); ?></td>
                                <td><?= htmlspecialchars($row->created_at); ?></td>
                                <td><?= date("d M Y", strtotime($row->book_from_date)) . " → " . date("d M Y", strtotime($row->book_to_date)); ?></td>
                                <td>
                                <span class="badge bg-<?=
                                $row->status == 'Pending' ? 'warning text-dark' :
                                    ($row->status == 'Approved' ? 'success' : 'danger')
                                ?>"><?= htmlspecialchars($row->status); ?></span>
                                </td>
                                <td>
                                    <?php if ($row->status == "Pending"): ?>
                                        <a href="admin-approve-booking.php?booking_id=<?= $row->booking_id; ?>" class="btn btn-success btn-sm">
                                            <i class="fa fa-check"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="admin-delete-booking.php?booking_id=<?= $row->booking_id; ?>" class="btn btn-danger btn-sm">
                                        <i class="fa fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php
                        endwhile;
                        $stmt->close();
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer text-muted small text-end">
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
        $('#dataTable').DataTable();
    });
</script>
</body>
</html>
