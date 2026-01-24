<div class="sidebar">
    <div class="sidebar-header">
        <img src="https://www.cmpdi.co.in/sites/default/files/cmpdi_new_logo_10012025.png" alt="Logo" class="sidebar-logo">
        <h5 class="mb-0">CMPDI RI-4</h5>
        <small>Vehicle Booking</small>
    </div>
    
    <ul class="nav flex-column sidebar-nav">
        <li class="nav-item">
            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'usr-book-vehicle.php') ? 'active' : ''; ?>" href="usr-book-vehicle.php">
                <i class="fas fa-car"></i> Book Vehicle
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'user-view-booking.php') ? 'active' : ''; ?>" href="user-view-booking.php">
                <i class="fas fa-clipboard-list"></i> My Bookings
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'user-view-profile.php') ? 'active' : ''; ?>" href="user-view-profile.php">
                <i class="fas fa-user-circle"></i> My Profile
            </a>
        </li>
    </ul>
    
    <div class="sidebar-footer">
        <a href="user-dashboard.php?logout=true" class="btn btn-sm btn-outline-light w-100">
            <i class="fas fa-sign-out-alt mr-2"></i> Logout
        </a>
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
        height: 100vh;
        top: 0;
        left: 0;
        z-index: 1000;
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

    /* Adjust main content to account for fixed sidebar */
    .main-content {
        margin-left: 260px;
        padding: 30px;
        width: calc(100% - 260px);
    }
</style>
