<?php
global $mysqli;
session_start();
include('../DATABASE FILE/config.php');
include('../DATABASE FILE/checklogin.php');

// Logout Logic
if (isset($_GET['logout'])) {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header("Location: user-login.php");
    exit;
}

// Check login status first
check_login();
$aid = $_SESSION['u_id'];

// Fetch user data
$user = null;
if ($aid) {
    $userQuery = $mysqli->prepare("SELECT first_name, last_name, email, phone FROM users WHERE id = ?");
    if ($userQuery) {
        $userQuery->bind_param('i', $aid);
        $userQuery->execute();
        $userResult = $userQuery->get_result();
        $user = $userResult->fetch_object();
    }
}

$baseURL = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/';
$projectFolder = '/' . basename(dirname(__DIR__)) . '/';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>User Dashboard - Vehicle Booking System</title>
    
    <!-- Include Global Theme -->
    <?php include("../vendor/inc/theme-config.php"); ?>
    
    <style>
        body {
            background-color: #fff; /* Override theme background for a cleaner dashboard */
        }
        
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 260px;
            background-color: var(--primary-color);
            color: #fff;
            padding: 20px;
            display: flex;
            flex-direction: column;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .sidebar-logo {
            width: 80px;
            margin-bottom: 10px;
        }
        
        .sidebar-nav .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 5px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
        }
        
        .sidebar-nav .nav-link i {
            margin-right: 15px;
            width: 20px;
            text-align: center;
        }
        
        .sidebar-nav .nav-link:hover, .sidebar-nav .nav-link.active {
            background-color: var(--secondary-color);
            color: #fff;
            font-weight: 600;
        }
        
        .sidebar-footer {
            margin-top: auto;
            text-align: center;
        }
        
        .main-content {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
        }
        
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
        
        #dynamicContent {
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
    </style>
</head>

<body>
<div class="dashboard-container">
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="https://www.cmpdi.co.in/sites/default/files/cmpdi_new_logo_10012025.png" alt="Logo" class="sidebar-logo">
            <h5 class="mb-0">CMPDI RI-4</h5>
            <small>Vehicle Booking</small>
        </div>
        
        <ul class="nav flex-column sidebar-nav">
            <li class="nav-item">
                <a class="nav-link active" href="#" id="loadBookingFormBtn">
                    <i class="fas fa-car"></i> Book Vehicle
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" id="loadBookingsBtn">
                    <i class="fas fa-clipboard-list"></i> My Bookings
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" id="loadProfileBtn">
                    <i class="fas fa-user-circle"></i> My Profile
                </a>
            </li>
        </ul>
        
        <div class="sidebar-footer">
            <a href="?logout=true" class="btn btn-sm btn-outline-light w-100">
                <i class="fas fa-sign-out-alt mr-2"></i> Logout
            </a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="content-header">
            <h2 class="fw-bold text-dark mb-0" id="pageTitle">Book a Vehicle</h2>
            
            <div class="dropdown profile-dropdown">
                <div class="d-flex align-items-center" data-toggle="dropdown">
                    <div class="text-right mr-3">
                        <h6 class="mb-0 text-dark fw-semibold"><?php echo htmlspecialchars($user->first_name ?? 'User'); ?></h6>
                        <small class="text-muted">Employee</small>
                    </div>
                    <i class="fas fa-user-circle fa-2x text-primary"></i>
                </div>
                <div class="dropdown-menu dropdown-menu-right">
                    <a class="dropdown-item" href="#" id="loadProfileBtnDropdown">
                        <i class="fas fa-user mr-2"></i> Profile
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item text-danger" href="?logout=true">
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Dynamic Content Container -->
        <div id="dynamicContent">
            <!-- Content will be loaded here via AJAX -->
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    $(document).ready(function () {
        const pageTitles = {
            'usr-book-vehicle.php': 'Book a Vehicle',
            'user-view-booking.php': 'My Bookings',
            'user-view-profile.php': 'My Profile'
        };

        function loadContent(page, navLink) {
            // Set active class on nav link
            $('.sidebar-nav .nav-link').removeClass('active');
            $(navLink).addClass('active');
            
            // Update page title
            $('#pageTitle').text(pageTitles[page] || 'Dashboard');

            // Load content
            $('#dynamicContent').html('<div class="text-center py-5"><div class="spinner-border text-success" role="status"></div></div>');
            $('#dynamicContent').load(page, function(response, status, xhr) {
                if (status == "error") {
                    $('#dynamicContent').html('<div class="alert alert-danger">Sorry, but there was an error loading the content.</div>');
                }
            });
        }

        // Load the booking page by default
        loadContent('usr-book-vehicle.php', '#loadBookingFormBtn');

        // Sidebar navigation clicks
        $('#loadBookingFormBtn').on('click', function (e) {
            e.preventDefault();
            loadContent('usr-book-vehicle.php', this);
        });

        $('#loadBookingsBtn').on('click', function (e) {
            e.preventDefault();
            loadContent('user-view-booking.php', this);
        });

        $('#loadProfileBtn, #loadProfileBtnDropdown').on('click', function (e) {
            e.preventDefault();
            loadContent('user-view-profile.php', '#loadProfileBtn');
        });

    });
</script>

</body>

</html>
