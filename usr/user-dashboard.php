<?php
// This file is now mainly for redirection or as a base if needed, 
// but since we are moving to separate pages, the main entry point for users 
// after login should probably be usr-book-vehicle.php or similar.
// However, keeping this as a redirector is fine.

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

check_login();

// Redirect to the booking page as the default dashboard view
header("Location: usr-book-vehicle.php");
exit;
?>
