<?php
session_start();
include('usr/vendor/inc/config.php');
require_once __DIR__ . '/vendor/autoload.php'; // Composer autoloader

use Brevo\Client\Configuration;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Model\SendSmtpEmail;

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['BREVO_API_KEY'];


$succ = $err = $step = null;

if (isset($_POST['reset-pwd'])) {
    $email = $_POST['r_email'];

    // Check if email exists
    $stmt = $mysqli->prepare("SELECT u_id FROM tms_user WHERE u_email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $otp = rand(100000, 999999);

        $stmt = $mysqli->prepare("DELETE FROM tms_pwd_resets WHERE r_email = ?");
        if ($stmt) {
            $stmt->bind_param('s', $email);
            $stmt->execute();
        } else {
            die("Prepare failed: " . $mysqli->error);
        }


        $insert = $mysqli->prepare("INSERT INTO tms_pwd_resets (r_email, otp, expires_at, created_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE), NOW())");
        $insert->bind_param('si', $email, $otp);
        $insert->execute();




        try {
            $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $apiKey);
            $apiInstance = new TransactionalEmailsApi(null, $config);

            $emailContent = new SendSmtpEmail([
                'subject' => 'Password Reset OTP',
                'sender' => ['name' => 'Vehicle Booking - CMPDI', 'email' => 'ictcmpdiri4@gmail.com'],
                'to' => [['email' => $email]],
                'htmlContent' => "<p>Your OTP is <strong>$otp</strong>. Valid for 15 minutes.</p>",
            ]);

            $apiInstance->sendTransacEmail($emailContent);
            $succ = "OTP sent to your email.";
            $step = "verify-otp";
            $_SESSION['reset_email'] = $email;
        } catch (Exception $e) {
            $err = "Failed to send email. " . $e->getMessage();
        }
    } else {
        $err = "Email not registered.";
    }
}

if (isset($_POST['verify-otp'])) {
    $email = $_SESSION['reset_email'];
    $otp = $_POST['otp'];

    $query = "SELECT r_id FROM tms_pwd_resets WHERE r_email = ? AND otp = ? AND expires_at > NOW() AND used = 0";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('si', $email, $otp);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 1) {
        $mysqli->query("UPDATE tms_pwd_resets SET used = 1 WHERE r_email = '$email'");
        $succ = "OTP verified. Please set your new password.";
        $step = "set-password";
    } else {
        $err = "Invalid or expired OTP.";
        $step = "verify-otp";
    }
}

if (isset($_POST['set-password'])) {
    $email = $_SESSION['reset_email'];
    $pass = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

    $stmt = $mysqli->prepare("UPDATE tms_user SET u_pwd = ? WHERE u_email = ?");
    $stmt->bind_param('ss', $pass, $email);
    if ($stmt->execute()) {
        $succ = "Password updated successfully.";
        session_destroy();
    } else {
        $err = "Failed to update password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Password Reset | Vehicle Booking System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="usr/vendor/css/sb-admin.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

</head>
<body class="bg-dark">

<?php if ($succ): ?>
    <script>setTimeout(() => { Swal.fire("Success!", "<?= $succ ?>", "success"); }, 100);</script>
<?php endif; ?>
<?php if ($err): ?>
    <script>setTimeout(() => { Swal.fire("Error!", "<?= $err ?>", "error"); }, 100);</script>
<?php endif; ?>

<div class="container">
    <div class="card card-login mx-auto mt-5">
        <div class="card-header">Reset Password</div>
        <div class="card-body">
            <div class="text-center mb-4">
                <h4>Forgot your password?</h4>
                <p>Enter your email to get an OTP for password reset.</p>
            </div>
            <?php if (!$step): ?>
                <form method="POST">
                    <div class="form-group">
                        <div class="form-label-group">
                            <input type="email" name="r_email" class="form-control" placeholder="Email address" required autofocus>
                            <label>Email address</label>
                        </div>
                    </div>
                    <input type="submit" name="reset-pwd" class="btn btn-success btn-block" value="Send OTP">
                </form>
            <?php elseif ($step == 'verify-otp'): ?>
                <form method="POST">
                    <div class="form-group">
                        <label>Enter OTP sent to your email</label>
                        <input type="text" name="otp" class="form-control" required>
                    </div>
                    <input type="submit" name="verify-otp" class="btn btn-info btn-block" value="Verify OTP">
                </form>
            <?php elseif ($step == 'set-password'): ?>
                <form method="POST">
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" class="form-control" required>
                    </div>
                    <input type="submit" name="set-password" class="btn btn-primary btn-block" value="Update Password">
                </form>
            <?php endif; ?>
            <div class="text-center mt-3">
                <a class="d-block small" href="usr/user-login.php">Back to Login</a>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>



</body>
</html>
