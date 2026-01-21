<?php
// Get current page name to set active state
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar">
    <div class="sidebar-header">
        <img src="https://www.cmpdi.co.in/sites/default/files/cmpdi_new_logo_10012025.png" alt="Logo" class="sidebar-logo">
        <h5 class="mb-0">CMPDI RI-4</h5>
        <small>Admin Portal</small>
    </div>
    
    <ul class="nav flex-column sidebar-nav">
        <li class="nav-item">
            <a class="nav-link <?php echo ($current_page == 'admin-dashboard.php') ? 'active' : ''; ?>" href="admin-dashboard.php">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($current_page == 'admin-view-user.php') ? 'active' : ''; ?>" href="admin-view-user.php">
                <i class="fas fa-users"></i> Manage Users
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($current_page == 'admin-view-vehicle.php') ? 'active' : ''; ?>" href="admin-view-vehicle.php">
                <i class="fas fa-bus"></i> Manage Vehicles
            </a>
        </li>
        <!-- Add more links as needed -->
    </ul>
    
    <div class="sidebar-footer">
        <form method="post" action="admin-dashboard.php" class="m-0">
            <button type="submit" name="logout" class="btn btn-sm btn-outline-light w-100">
                <i class="fas fa-sign-out-alt mr-2"></i> Logout
            </button>
        </form>
    </div>
</div>

<style>
    .sidebar {
        width: 260px;
        background-color: var(--primary-color);
        color: #fff;
        padding: 20px;
        display: flex;
        flex-direction: column;
        box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        position: fixed;
        top: 0;
        bottom: 0;
        left: 0;
        z-index: 100;
    }
    
    .sidebar-header {
        text-align: center;
        margin-bottom: 30px;
    }
    
    .sidebar-logo {
        width: 180px;
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
        text-decoration: none;
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
    
    /* Adjust main content to respect sidebar width */
    .main-content {
        flex: 1;
        padding: 30px;
        margin-left: 260px; /* Width of sidebar */
        background-color: #f8f9fa;
        min-height: 100vh;
    }
    
    @media (max-width: 768px) {
        .sidebar {
            width: 70px;
            padding: 10px;
        }
        .sidebar-header h5, .sidebar-header small, .sidebar-nav .nav-link span, .sidebar-footer button span {
            display: none;
        }
        .sidebar-logo {
            width: 40px;
        }
        .main-content {
            margin-left: 70px;
        }
        .sidebar-nav .nav-link {
            justify-content: center;
            padding: 10px;
        }
        .sidebar-nav .nav-link i {
            margin-right: 0;
        }
    }
</style>
