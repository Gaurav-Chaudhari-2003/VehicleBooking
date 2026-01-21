<?php
session_start();
include('DATABASE FILE/config.php');

require_once __DIR__ . '/vendor/autoload.php';

use Brevo\Client\Configuration;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Model\SendSmtpEmail;
use Dotenv\Dotenv;
use includes\OTPMailTemplate;

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
    $stmt = $mysqli->prepare("SELECT id, first_name, last_name FROM users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        $otp = rand(100000, 999999);

        // Create a table if missing
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
            $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $apiKey);
            $api = new TransactionalEmailsApi(null, $config);

            $content = new SendSmtpEmail([
                    'subject' => 'Password Reset OTP',
                    'sender' => ['name' => $senderName, 'email' => $senderEmail],
                    'to' => [['email' => $email]],
                    'htmlContent' => OTPMailTemplate::getHtml($otp, $user['first_name'])
            ]);

            $api->sendTransacEmail($content);

            $_SESSION['reset_email'] = $email;
            $succ = "OTP sent to your email.";
            $step = "verify-otp";

        } catch (Exception $e) {
            $err = "Failed to send email: " . $e->getMessage();
        }
    } else {
        $err = "Email not registered.";
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

        global $mysqli;
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
    
    <!-- Include Global Theme -->
    <?php include("vendor/inc/theme-config.php"); ?>
    
    <style>
        body {
            background: linear-gradient(135deg, var(--accent-color) 0%, #ffffff 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        
        .reset-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
            background: #fff;
        }
        
        .card-header {
            background: linear-gradient(135deg, #004d40 0%, #00796b 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-bottom: none;
        }
        
        .card-body {
            padding: 40px;
        }
        
        .form-floating > .form-control {
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            background-color: #f8f9fa;
        }
        
        .form-floating > .form-control:focus {
            background-color: #fff;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 4px rgba(0, 121, 107, 0.1);
        }
        
        .btn-submit {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 12px 40px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(0, 77, 64, 0.2);
        }
        
        .btn-submit:hover {
            background-color: var(--secondary-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 77, 64, 0.3);
        }
        
        .back-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            transition: transform 0.2s;
            z-index: 10;
        }
        
        .back-btn:hover {
            transform: translateX(-3px);
            text-decoration: none;
            color: var(--secondary-color);
        }
        
        .password-toggle {
            position: absolute;
            top: 65%;
            right: 15px;
            cursor: pointer;
            color: #666;
            z-index: 10;
        }
    </style>
</head>
<body>

<a href="index.php" class="back-btn">
    <i class="fas fa-arrow-left mr-2"></i> Back to Home
</a>

<?php if ($succ): ?>
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <script>setTimeout(() => { swal("Success!", "<?= $succ ?>", "success"); }, 100);</script>
<?php endif; ?>
<?php if ($err): ?>
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <script>setTimeout(() => { swal("Error!", "<?= $err ?>", "error"); }, 100);</script>
<?php endif; ?>

<div class="container">
    <div class="reset-card mx-auto">
        <div class="card-header">
            <i class="fas fa-key fa-3x mb-3"></i>
            <h3 class="fw-bold mb-1">Password Reset</h3>
        </div>
        <div class="card-body">
            
            <?php if (!$step): ?>
                <p class="text-center text-muted mb-4">Enter your email to receive a One-Time Password (OTP).</p>
                <form method="POST">
                    <div class="form-floating mb-3">
                        <label for="r_email">Email address</label>
                        <input type="email" name="r_email" id="r_email" class="form-control" placeholder="abc@gmail.com" required autofocus>
                    </div>
                    <button type="submit" name="reset-pwd" class="btn btn-submit w-100">Send OTP</button>
                </form>
            <?php elseif ($step == 'verify-otp'): ?>
                <p class="text-center text-muted mb-4">Enter the OTP sent to <strong><?= htmlspecialchars($_SESSION['reset_email']) ?></strong>.</p>
                <form method="POST">
                    <div class="form-floating mb-3">
                        <label for="otp">Enter OTP</label>
                        <input type="text" name="otp" id="otp" class="form-control" placeholder="000000" required>
                    </div>
                    <button type="submit" name="verify-otp" class="btn btn-submit w-100">Verify OTP</button>
                </form>
            <?php elseif ($step == 'set-password'): ?>
                <p class="text-center text-muted mb-4">Create a new password for your account.</p>
                <form method="POST">
                    <div class="form-floating mb-3 position-relative">
                        <label for="new_password">New Password</label>
                        <input type="password" name="new_password" id="new_password" class="form-control" placeholder="********" required>
                        <i class="fas fa-eye password-toggle" onclick="togglePassword('new_password', this)"></i>
                    </div>
                    <button type="submit" name="set-password" class="btn btn-submit w-100">Update Password</button>
                </form>
            <?php endif; ?>
            
            <div class="text-center mt-4">
                <a class="d-block small text-muted" href="usr/user-login.php">Back to Login</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function togglePassword(inputId, icon) {
        const input = document.getElementById(inputId);
        if (input.type === "password") {
            input.type = "text";
            icon.classList.remove("fa-eye");
            icon.classList.add("fa-eye-slash");
        } else {
            input.type = "password";
            icon.classList.remove("fa-eye-slash");
            icon.classList.add("fa-eye");
        }
    }
</script>

</body>
</html>
