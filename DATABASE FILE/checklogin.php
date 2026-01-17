<?php
function check_login(): void
{
    // Fix: Check 'u_id' instead of 'a_id' to match user-login.php
    if (!isset($_SESSION['u_id']) || strlen($_SESSION['u_id']) == 0) {
        $host = $_SERVER['HTTP_HOST'];
        $uri  = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
        $extra = "user-login.php";
        header("Location: http://$host$uri/$extra");
        exit();
    }
}
