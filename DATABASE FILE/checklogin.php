<?php
function check_login(): void
{
    // Check if either user ID or admin ID is set in the session.
    // If neither is set, the user is not logged in at all.
    if ((!isset($_SESSION['u_id']) || strlen($_SESSION['u_id']) == 0) && (!isset($_SESSION['a_id']) || strlen($_SESSION['a_id']) == 0)) {
        $host = $_SERVER['HTTP_HOST'];
        $uri  = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
        $extra = "user-login.php";
        header("Location: http://$host$uri/$extra");
        exit();
    }
}
