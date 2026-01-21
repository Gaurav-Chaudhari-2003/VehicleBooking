<?php
global $mysqli;
session_start();
include('../DATABASE FILE/config.php');
include('../DATABASE FILE/checklogin.php');
check_login();

$aid = $_SESSION['u_id'];

// Updated query to match new schema: users table
$query = "SELECT first_name, last_name, address, phone, email, created_at FROM users WHERE id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $aid);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_object();

// Calculate account age or join date
$joinDate = date('d M Y', strtotime($user->created_at));

// Fetch booking statistics
$statsQuery = "SELECT 
    COUNT(*) as total_bookings,
    SUM(CASE WHEN status = 'APPROVED' THEN 1 ELSE 0 END) as approved_bookings,
    SUM(CASE WHEN status = 'PENDING' THEN 1 ELSE 0 END) as pending_bookings
FROM bookings WHERE user_id = ?";
$statsStmt = $mysqli->prepare($statsQuery);
$statsStmt->bind_param('i', $aid);
$statsStmt->execute();
$stats = $statsStmt->get_result()->fetch_object();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile | Vehicle Booking System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Include Global Theme -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* Scoped styles for profile component */
        .profile-header-card {
            background: linear-gradient(135deg, #004d40 0%, #00796b 100%);
            color: white;
            border-radius: 15px;
            padding: 40px 20px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 10px 20px rgba(0, 77, 64, 0.2);
            position: relative;
            overflow: hidden;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            background: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: #004d40;
            font-size: 3rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .info-card {
            background: #fff;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            height: 100%;
            transition: transform 0.3s;
        }
        
        .info-card:hover {
            transform: translateY(-5px);
        }
        
        .info-icon {
            width: 40px;
            height: 40px;
            background: #e0f2f1;
            color: #00796b;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            font-size: 1.2rem;
        }
        
        .info-label {
            font-size: 0.85rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
        }
        
        .stat-card {
            background: #fff;
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            border: 1px solid #eee;
        }
        
        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: #004d40;
        }
        
        .stat-label {
            font-size: 0.8rem;
            color: #777;
            text-transform: uppercase;
        }
        
        .action-btn {
            border-radius: 50px;
            padding: 12px 25px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .modal-content {
            border-radius: 20px;
            border: none;
        }
        
        .modal-header {
            border-bottom: none;
            padding: 25px 25px 10px;
        }
        
        .modal-footer {
            border-top: none;
            padding: 10px 25px 25px;
        }
    </style>
</head>
<body>

<div class="container-fluid">
    
    <!-- Profile Header -->
    <div class="profile-header-card">
        <div class="profile-avatar">
            <i class="fas fa-user"></i>
        </div>
        <h3 class="fw-bold mb-1"><?php echo htmlspecialchars($user->first_name . ' ' . $user->last_name); ?></h3>
        <p class="mb-2 opacity-75">Employee</p>
        <span class="badge bg-white text-dark rounded-pill px-3 py-2">
            <i class="fas fa-calendar-alt me-1 text-success"></i> Joined: <?php echo $joinDate; ?>
        </span>
    </div>

    <!-- Stats Row -->
    <div class="row g-3 mb-4 justify-content-center">
        <div class="col-4 col-md-3">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats->total_bookings; ?></div>
                <div class="stat-label">Total Trips</div>
            </div>
        </div>
        <div class="col-4 col-md-3">
            <div class="stat-card">
                <div class="stat-number text-success"><?php echo $stats->approved_bookings ?? 0; ?></div>
                <div class="stat-label">Approved</div>
            </div>
        </div>
        <div class="col-4 col-md-3">
            <div class="stat-card">
                <div class="stat-number text-warning"><?php echo $stats->pending_bookings ?? 0; ?></div>
                <div class="stat-label">Pending</div>
            </div>
        </div>
    </div>

    <!-- Info Grid -->
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="info-card">
                <div class="info-icon">
                    <i class="fas fa-envelope"></i>
                </div>
                <div class="info-label">Email Address</div>
                <div class="info-value"><?php echo htmlspecialchars($user->email); ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="info-card">
                <div class="info-icon">
                    <i class="fas fa-phone"></i>
                </div>
                <div class="info-label">Contact Number</div>
                <div class="info-value"><?php echo htmlspecialchars($user->phone); ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="info-card">
                <div class="info-icon">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <div class="info-label">Address</div>
                <div class="info-value"><?php echo htmlspecialchars($user->address); ?></div>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="d-flex justify-content-center gap-3 flex-wrap">
        <a href="user-update-profile.php" class="btn btn-primary action-btn">
            <i class="fas fa-user-edit me-2"></i> Update Profile
        </a>
        <button class="btn btn-danger action-btn" data-bs-toggle="modal" data-bs-target="#logoutModal">
            <i class="fas fa-sign-out-alt me-2"></i> Logout
        </button>
    </div>

    <!-- Logout Modal -->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header justify-content-center">
                    <div class="text-center">
                        <i class="fas fa-sign-out-alt fa-3x text-danger mb-3"></i>
                        <h5 class="modal-title fw-bold">Ready to Leave?</h5>
                    </div>
                </div>
                <div class="modal-body text-center">
                    <p class="text-muted mb-0">Select "Logout" below if you are ready to end your current session.</p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <a href="user-dashboard.php?logout=true" class="btn btn-danger rounded-pill px-4 shadow-sm">Logout</a>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
