<?php
function check_login()
{
	if(strlen($_SESSION['a_id'])==0)
		{
			$host = $_SERVER['HTTP_HOST'];
			$uri  = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
			$extra="user-login.php";
			$_SESSION["a_id"]="";
			header("Location: https://$host$uri/$extra");
		}
	}

