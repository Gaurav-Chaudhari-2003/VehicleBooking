<?php
include('../DATABASE FILE/config.php');
include('../DATABASE FILE/checklogin.php');

// Load Composer's autoloader for Brevo
require_once __DIR__ . '/../vendor/autoload.php';

use Brevo\Client\Configuration;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Model\SendSmtpEmail;
use Dotenv\Dotenv;
use includes\OTPMailTemplate;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$apiKey      = $_ENV['BREVO_API_KEY'];
$senderEmail = $_ENV['BREVO_SENDER_EMAIL'];
$senderName  = $_ENV['BREVO_SENDER_NAME'];

session_start();

// Display message if set in session
$message = $_SESSION['msg'] ?? null;
unset($_SESSION['msg']);

// --- STEP 1: Handle Registration Form Submission ---
if (isset($_POST['add_user'])) {
    $first_name = trim($_POST['u_fname']);
    $last_name = trim($_POST['u_lname']);
    $phone = trim($_POST['u_phone']);
    $address  = trim($_POST['u_addr']);
    $email = trim($_POST['u_email']);
    $password = $_POST['u_pwd']; // Will hash after OTP verification
    $remark = trim($_POST['remark']);
    $role = trim($_POST['u_role']); // Get selected role
    
    // Driver specific fields
    $d_license_no = trim($_POST['d_license_no'] ?? '');
    $d_license_expiry = $_POST['d_license_expiry'] ?? null;

    // Validate role
    $allowed_roles = ['EMPLOYEE', 'DRIVER'];
    if (!in_array($role, $allowed_roles)) {
        $_SESSION['msg'] = ['type' => 'error', 'text' => 'Invalid role selected.'];
        header("Location: usr-register.php");
        exit();
    }

    global $mysqli;
    
    // Check if email already exists
    $check = $mysqli->prepare("SELECT email FROM users WHERE email = ?");
    $check->bind_param('s', $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $_SESSION['msg'] = ['type' => 'error', 'text' => 'Email already registered. Please use a different one.'];
        header("Location: usr-register.php");
        exit();
    } else {
        // Generate OTP
        $otp = rand(100000, 999999);
        
        // Store registration data in session temporarily
        $_SESSION['temp_user'] = [
            'first_name' => $first_name,
            'last_name' => $last_name,
            'phone' => $phone,
            'address' => $address,
            'email' => $email,
            'password' => $password, // Plain text, will hash later
            'remark' => $remark,
            'role' => $role, // Store role
            'd_license_no' => $d_license_no,
            'd_license_expiry' => $d_license_expiry,
            'otp' => $otp,
            'otp_expiry' => time() + 600 // 10 minutes expiry
        ];

        // Send OTP Email
        try {
            $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $apiKey);
            $apiInstance = new TransactionalEmailsApi(null, $config);

            $sendSmtpEmail = new SendSmtpEmail([
                'subject' => 'Verify Your Email - Vehicle Booking System',
                'sender' => ['name' => $senderName, 'email' => $senderEmail],
                'to' => [['email' => $email, 'name' => "$first_name $last_name"]],
                'htmlContent' => OTPMailTemplate::getHtml($otp, "$first_name $last_name")
            ]);

            $apiInstance->sendTransacEmail($sendSmtpEmail);
            
            // Redirect to OTP verification step
            $_SESSION['verification_step'] = true;
            header("Location: usr-register.php");
            exit();

        } catch (Exception $e) {
            $_SESSION['msg'] = ['type' => 'error', 'text' => 'Failed to send OTP. Please try again. Error: ' . $e->getMessage()];
            unset($_SESSION['temp_user']); // Clear temp data
            header("Location: usr-register.php");
            exit();
        }
    }
}

// --- STEP 2: Handle OTP Verification ---
if (isset($_POST['verify_otp'])) {
    $entered_otp = trim($_POST['otp_code']);
    
    if (!isset($_SESSION['temp_user'])) {
        $_SESSION['msg'] = ['type' => 'error', 'text' => 'Session expired. Please register again.'];
        unset($_SESSION['verification_step']);
        header("Location: usr-register.php");
        exit();
    }

    $temp_user = $_SESSION['temp_user'];

    if (time() > $temp_user['otp_expiry']) {
        $_SESSION['msg'] = ['type' => 'error', 'text' => 'OTP has expired. Please register again.'];
        unset($_SESSION['temp_user']);
        unset($_SESSION['verification_step']);
    } elseif ($entered_otp == $temp_user['otp']) {
        // OTP Verified - Insert User into Database
        global $mysqli;
        
        $hashed_password = password_hash($temp_user['password'], PASSWORD_DEFAULT);
        $role = $temp_user['role']; // Use stored role
        $is_active = 0; // Pending admin approval

        $query = "INSERT INTO users (first_name, last_name, phone, address, role, email, password, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('sssssssi', 
            $temp_user['first_name'], 
            $temp_user['last_name'], 
            $temp_user['phone'], 
            $temp_user['address'], 
            $role, 
            $temp_user['email'], 
            $hashed_password, 
            $is_active
        );

        if ($stmt->execute()) {
            $new_user_id = $mysqli->insert_id;
            $last_remark_id = null;
            
            // Insert remark if provided
            if (!empty($temp_user['remark'])) {
                $remark_query = "INSERT INTO entity_remarks (entity_type, entity_id, user_id, remark) VALUES (?, ?, ?, ?)";
                $remark_stmt = $mysqli->prepare($remark_query);
                $entity_type = 'USER';
                $remark_stmt->bind_param('siis', $entity_type, $new_user_id, $new_user_id, $temp_user['remark']);
                $remark_stmt->execute();
                $last_remark_id = $remark_stmt->insert_id;
            }

            // If Role is DRIVER, insert into 'drivers' table
            if ($role === 'DRIVER') {
                $d_license_no = $temp_user['d_license_no'];
                $d_license_expiry = $temp_user['d_license_expiry'];
                if(empty($d_license_expiry)) $d_license_expiry = null;
                $d_experience = 0; // Default
                $d_status = 'ACTIVE'; // Default

                $driver_stmt = $mysqli->prepare("INSERT INTO drivers (user_id, license_no, license_expiry, experience_years, status, last_remark_id) VALUES (?, ?, ?, ?, ?, ?)");
                $driver_stmt->bind_param('issisi', $new_user_id, $d_license_no, $d_license_expiry, $d_experience, $d_status, $last_remark_id);
                $driver_stmt->execute();
                $driver_stmt->close();
            }

            $_SESSION['msg'] = ['type' => 'success', 'text' => 'Account created successfully! Please wait for admin approval before logging in.'];
            unset($_SESSION['temp_user']);
            unset($_SESSION['verification_step']);
        } else {
            $_SESSION['msg'] = ['type' => 'error', 'text' => 'Database error. Please try again.'];
        }
    } else {
        $_SESSION['msg'] = ['type' => 'error', 'text' => 'Invalid OTP. Please try again.'];
    }
    
    header("Location: usr-register.php");
    exit();
}

// --- STEP 3: Handle Cancel Registration ---
if (isset($_POST['cancel_registration'])) {
    unset($_SESSION['temp_user']);
    unset($_SESSION['verification_step']);
    header("Location: usr-register.php");
    exit();
}

// --- STEP 4: Handle Resend OTP ---
if (isset($_POST['resend_otp'])) {
    if (isset($_SESSION['temp_user'])) {
        $temp_user = $_SESSION['temp_user'];
        $otp = rand(100000, 999999);
        
        // Update OTP in session
        $_SESSION['temp_user']['otp'] = $otp;
        $_SESSION['temp_user']['otp_expiry'] = time() + 600; // Reset expiry to 10 mins

        // Send OTP Email
        try {
            $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $apiKey);
            $apiInstance = new TransactionalEmailsApi(null, $config);

            $sendSmtpEmail = new SendSmtpEmail([
                'subject' => 'Resend: Verify Your Email - Vehicle Booking System',
                'sender' => ['name' => $senderName, 'email' => $senderEmail],
                'to' => [['email' => $temp_user['email'], 'name' => $temp_user['first_name'] . ' ' . $temp_user['last_name']]],
                'htmlContent' => OTPMailTemplate::getHtml($otp, $temp_user['first_name'] . ' ' . $temp_user['last_name'])
            ]);

            $apiInstance->sendTransacEmail($sendSmtpEmail);
            $_SESSION['msg'] = ['type' => 'success', 'text' => 'OTP resent successfully!'];

        } catch (Exception $e) {
            $_SESSION['msg'] = ['type' => 'error', 'text' => 'Failed to resend OTP. Error: ' . $e->getMessage()];
        }
    }
    header("Location: usr-register.php");
    exit();
}

// Check if we are in verification mode
$is_verification = isset($_SESSION['verification_step']) && $_SESSION['verification_step'] === true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Client Registration | Vehicle Booking System</title>
    
    <!-- Include Global Theme -->
    <?php include("../vendor/inc/theme-config.php"); ?>
    
    <style>
        body {
            background-color: #f0f2f5;
            font-family: 'Segoe UI', 'Roboto', sans-serif;
        }

        .main-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .auth-card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.08);
            overflow: hidden;
            width: 100%;
            max-width: 1100px;
            display: flex;
            flex-wrap: wrap;
            position: relative;
        }
        
        .auth-sidebar {
            background: linear-gradient(135deg, #004d40 0%, #00796b 100%);
            width: 40%;
            padding: 40px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        /* Decorative circles */
        .circle-decoration {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
        }
        .circle-1 { width: 300px; height: 300px; top: -100px; left: -100px; }
        .circle-2 { width: 200px; height: 200px; bottom: -50px; right: -50px; }

        .sidebar-content {
            position: relative;
            z-index: 2;
        }
        
        .logo-container {
            width: 100px;
            height: 100px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 25px;
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255,255,255,0.3);
        }

        .logo-img {
            width: 260px;
            height: auto;
        }

        .auth-form-side {
            width: 60%;
            padding: 50px;
            background-color: #fff;
            position: relative; /* For loader positioning */
        }

        .form-title {
            font-weight: 800;
            color: #333;
            margin-bottom: 10px;
            font-size: 1.8rem;
        }

        .form-subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        
        .form-floating > .form-control, .form-floating > .form-select {
            border-radius: 12px;
            border: 1px solid #e9ecef;
            background-color: #f8f9fa;
            height: 50px;
        }
        
        .form-floating > .form-control:focus, .form-floating > .form-select:focus {
            background-color: #fff;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 4px rgba(0, 121, 107, 0.1);
        }

        .form-floating > label {
            padding-top: 12px;
        }
        
        .btn-register {
            background-color: var(--primary-color);
            border: none;
            color: white;
            border-radius: 50px;
            padding: 14px 40px;
            font-weight: 700;
            font-size: 1rem;
            transition: all 0.3s;
            box-shadow: 0 10px 20px rgba(0, 77, 64, 0.15);
            width: 100%;
        }
        
        .btn-register:hover {
            background-color: var(--secondary-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(0, 77, 64, 0.25);
        }
        
        .back-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            color: #666;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            transition: transform 0.2s;
            z-index: 10;
            background: white;
            padding: 8px 15px;
            border-radius: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .back-btn:hover {
            transform: translateX(-3px);
            text-decoration: none;
            color: var(--primary-color);
        }
        
        .login-link {
            color: var(--primary-color);
            font-weight: 700;
            text-decoration: none;
        }
        
        .login-link:hover {
            text-decoration: underline;
        }
        
        .password-toggle {
            position: absolute;
            top: 65%;
            right: 20px;
            cursor: pointer;
            color: #999;
            z-index: 10;
        }
        
        .otp-input {
            letter-spacing: 8px;
            font-size: 2rem;
            text-align: center;
            font-weight: 700;
            height: 60px !important;
        }

        /* Loader Overlay */
        .loader-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            display: none; /* Hidden by default */
            justify-content: center;
            align-items: center;
            z-index: 100;
            border-radius: 0 20px 20px 0;
        }

        .loader-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 991px) {
            .auth-sidebar { width: 100%; padding: 40px 20px; }
            .auth-form-side { width: 100%; padding: 40px 20px; }
            .circle-1 { width: 150px; height: 150px; }
            .loader-overlay { border-radius: 0 0 20px 20px; }
        }
    </style>
</head>
<body>

<div class="main-wrapper">
    <a href="../index.php" class="back-btn">
        <i class="fas fa-arrow-left mr-2"></i> Home
    </a>

    <?php if (!empty($message)) : ?>
        <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
        <script>
            setTimeout(() => {
                swal("<?php echo ucfirst($message['type']); ?>", "<?php echo $message['text']; ?>", "<?php echo $message['type']; ?>");
            }, 100);
        </script>
    <?php endif; ?>

    <div class="auth-card">
        <!-- Sidebar -->
        <div class="auth-sidebar">
            <div class="circle-decoration circle-1"></div>
            <div class="circle-decoration circle-2"></div>

            <div class="sidebar-content">
                <div class="logo-container mx-auto">
                    <img src="https://www.cmpdi.co.in/sites/default/files/cmpdi_new_logo_10012025.png" alt="Logo" class="logo-img">
                </div>
                <h2 class="fw-bold mb-3">CMPDI RI-4</h2>
                <p class="mb-0 opacity-75 lead">Official Vehicle Booking Portal</p>
                <div class="mt-4 pt-4 border-top border-light opacity-50 w-50 mx-auto"></div>
                <p class="mt-4 small opacity-75">Secure • Efficient • Reliable</p>
            </div>
        </div>

        <!-- Form Side -->
        <div class="auth-form-side">
            
            <!-- Loader Overlay -->
            <div class="loader-overlay" id="loader">
                <div class="text-center">
                    <div class="loader-spinner mb-3 mx-auto"></div>
                    <h6 class="text-muted fw-bold">Processing... Please wait</h6>
                </div>
            </div>

            <?php if (!$is_verification): ?>
            <!-- REGISTRATION FORM -->
            <div class="d-flex flex-column h-100 justify-content-center">
                <h3 class="form-title">Create Account</h3>
                <p class="form-subtitle">Fill in your details to register for access.</p>

                <form method="post" onsubmit="showLoader()">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-floating">
                                <label for="fname">First Name</label>
                                <input type="text" id="fname" name="u_fname" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <label for="lname">Last Name</label>
                                <input type="text" id="lname" name="u_lname" class="form-control" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-floating">
                                <label for="phone">Contact Number</label>
                                <input type="text" id="phone" name="u_phone" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="u_email" class="form-control" required>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-floating">
                                <label for="role">Role</label>
                                <select class="form-control" id="role" name="u_role" required onchange="toggleDriverFields()">
                                    <option value="" selected disabled>Select Role</option>
                                    <option value="EMPLOYEE">Employee</option>
                                    <option value="DRIVER">Driver</option>
                                </select>
                            </div>
                        </div>

                        <!-- Driver Specific Fields -->
                        <div id="driver-fields" style="display: none;" class="col-12">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <label for="license_no">License Number</label>
                                        <input type="text" id="license_no" name="d_license_no" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <label for="license_expiry">License Expiry Date</label>
                                        <input type="date" id="license_expiry" name="d_license_expiry" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-floating">
                                <label for="addr">Department / Camp</label>
                                <input type="text" id="addr" name="u_addr" class="form-control" required>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-floating">
                                <label for="remark">Remarks</label>
                                <textarea id="remark" name="remark" class="form-control" style="height: 80px" required></textarea>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-floating position-relative">
                                <label for="pwd">Password</label>
                                <input type="password" id="pwd" name="u_pwd" class="form-control" required>
                                <i class="fas fa-eye password-toggle" onclick="togglePassword('pwd', this)"></i>
                            </div>
                        </div>

                        <div class="col-12 mt-4">
                            <button type="submit" name="add_user" class="btn btn-register">
                                Register Account <i class="fas fa-arrow-right ms-2"></i>
                            </button>
                        </div>
                    </div>
                </form>

                <div class="text-center mt-4">
                    <p class="mb-0 text-muted">Already have an account? <a href="user-login.php" class="login-link">Login Here</a></p>
                </div>
            </div>

            <?php else: ?>
            <!-- OTP VERIFICATION FORM -->
            <div class="d-flex flex-column h-100 justify-content-center text-center">
                <div class="mb-4">
                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                        <i class="fas fa-envelope-open-text fa-2x text-primary"></i>
                    </div>
                </div>
                
                <h3 class="form-title">Verify Your Email</h3>
                <p class="form-subtitle mb-4">
                    We've sent a 6-digit verification code to<br>
                    <span class="fw-bold text-dark"><?php echo htmlspecialchars($_SESSION['temp_user']['email']); ?></span>
                </p>
                
                <form method="post" class="w-100 mx-auto" style="max-width: 400px;" onsubmit="showLoader()">
                    <div class="form-group mb-4">
                        <input type="text" name="otp_code" class="form-control otp-input" placeholder="000000" maxlength="6" required autofocus autocomplete="off">
                    </div>

                    <button type="submit" name="verify_otp" class="btn btn-register mb-3">
                        Verify & Create Account
                    </button>

                    <!-- Resend OTP Logic -->
                    <div class="mb-3">
                        <button type="submit" name="resend_otp" id="resendBtn" class="btn btn-link text-muted small text-decoration-none" disabled>
                            Resend OTP in <span id="countdown">30</span>s
                        </button>
                    </div>

                    <button type="submit" name="cancel_registration" class="btn btn-link text-danger small text-decoration-none hover-underline">
                        <i class="fas fa-times-circle me-1"></i> Cancel Registration
                    </button>
                </form>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- JS Scripts -->
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
    
    function showLoader() {
        document.getElementById('loader').style.display = 'flex';
    }

    function toggleDriverFields() {
        var role = document.getElementById("role").value;
        var driverFields = document.getElementById("driver-fields");
        var licenseNo = document.getElementById("license_no");
        var licenseExpiry = document.getElementById("license_expiry");
        
        if (role === "DRIVER") {
            driverFields.style.display = "block";
            licenseNo.required = true;
            licenseExpiry.required = true;
        } else {
            driverFields.style.display = "none";
            licenseNo.required = false;
            licenseExpiry.required = false;
        }
    }
    
    // Countdown Timer for Resend OTP
    <?php if ($is_verification): ?>
    let timeLeft = 30; // Initial countdown
    const resendBtn = document.getElementById('resendBtn');
    const countdownSpan = document.getElementById('countdown');
    
    const timer = setInterval(() => {
        timeLeft--;
        countdownSpan.textContent = timeLeft;
        
        if (timeLeft <= 0) {
            clearInterval(timer);
            resendBtn.disabled = false;
            resendBtn.innerHTML = "Resend OTP";
            resendBtn.classList.remove('text-muted');
            resendBtn.classList.add('text-primary');
        }
    }, 1000);
    <?php endif; ?>
</script>
</body>
</html>
