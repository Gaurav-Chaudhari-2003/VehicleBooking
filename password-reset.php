<?php
session_start();
include('DATABASE FILE/config.php');

require_once __DIR__ . '/vendor/autoload.php';

use Brevo\Client\Configuration;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Model\SendSmtpEmail;
use Dotenv\Dotenv;

/* =====================================================
   LOAD ENV USING COMPOSER DOTENV
===================================================== */
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey      = $_ENV['BREVO_API_KEY'];
$senderEmail = $_ENV['BREVO_SENDER_EMAIL'];
$senderName  = $_ENV['BREVO_SENDER_NAME'];

$succ = $err = $step = null;


/* =====================================================
   STEP 1 : SEND OTP
===================================================== */
if (isset($_POST['reset-pwd'])) {

    $email = trim($_POST['r_email']);
    global $mysqli;
    $stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        $err = "Email not registered.";
    }
    else {

        $otp = rand(100000, 999999);

        // Create table if missing
        $mysqli->query("
            CREATE TABLE IF NOT EXISTS password_resets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(150),
                otp VARCHAR(10),
                expires_at DATETIME,
                used BOOLEAN DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // invalidate old OTP
        $stmt = $mysqli->prepare("UPDATE password_resets SET used=1 WHERE email=?");
        $stmt->bind_param('s', $email);
        $stmt->execute();

        // insert new
        $stmt = $mysqli->prepare("
            INSERT INTO password_resets 
            (email, otp, expires_at)
            VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE))
        ");
        $stmt->bind_param('ss', $email, $otp);
        $stmt->execute();


        /* ============= BREVO EMAIL ============= */

        try {

            $config = Configuration::getDefaultConfiguration()
                    ->setApiKey('api-key', $apiKey);

            $api = new TransactionalEmailsApi(null, $config);

            $content = new SendSmtpEmail([
                    'subject' => 'Password Reset OTP',
                    'sender' => [
                            'name'  => $senderName,
                            'email' => $senderEmail
                    ],
                    'to' => [
                            ['email' => $email]
                    ],
                    'htmlContent' => "
                    <h3>Password Reset</h3>
                    <p>Your OTP is:</p>
                    <h2>$otp</h2>
                    <p>Valid for 15 minutes.</p>
                "
            ]);

            $api->sendTransacEmail($content);

            $_SESSION['reset_email'] = $email;
            $succ = "OTP sent to your email.";
            $step = "verify-otp";

        }
        catch (Exception $e) {
            $err = "Failed to send email: " . $e->getMessage();
        }
    }
}


/* =====================================================
   STEP 2 : VERIFY OTP
===================================================== */
if (isset($_POST['verify-otp'])) {

    $email = $_SESSION['reset_email'] ?? null;
    $otp   = trim($_POST['otp']);

    if (!$email) {
        $err = "Session expired. Start again.";
    }
    else {

        $stmt = $mysqli->prepare("
            SELECT id FROM password_resets
            WHERE email=? AND otp=? 
            AND expires_at > NOW() 
            AND used=0
        ");

        $stmt->bind_param('ss', $email, $otp);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {

            $stmt = $mysqli->prepare("
                UPDATE password_resets
                SET used=1
                WHERE email=?
            ");
            $stmt->bind_param('s', $email);
            $stmt->execute();

            $succ = "OTP verified. Set new password.";
            $step = "set-password";
        }
        else {
            $err = "Invalid or expired OTP.";
            $step = "verify-otp";
        }
    }
}


/* =====================================================
   STEP 3 : SET PASSWORD
===================================================== */
if (isset($_POST['set-password'])) {

    $email = $_SESSION['reset_email'] ?? null;
    $pass  = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

    if (!$email) {
        $err = "Session expired.";
    }
    else {

        $stmt = $mysqli->prepare("
            UPDATE users
            SET password=?
            WHERE email=?
        ");
        $stmt->bind_param('ss', $pass, $email);

        if ($stmt->execute()) {
            $succ = "Password updated successfully.";
            session_destroy();
        }
        else {
            $err = "Failed to update password.";
        }
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
