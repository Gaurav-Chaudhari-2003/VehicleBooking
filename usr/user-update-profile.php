<?php
session_start();
include('../DATABASE FILE/config.php');
include('../DATABASE FILE/checklogin.php');
check_login();

$u_id = $_SESSION['u_id'];
$succ = $err = "";

// Handle Profile Update
if (isset($_POST['update_profile'])) {
    $u_fname = $_POST['u_fname'];
    $u_lname = $_POST['u_lname'];
    $u_phone = $_POST['u_phone'];
    $u_addr  = $_POST['u_addr'];
    $u_email = $_POST['u_email'];

    global $mysqli;
    $stmt = $mysqli->prepare("UPDATE users SET first_name=?, last_name=?, phone=?, address=?, email=? WHERE id=?");
    if ($stmt) {
        $stmt->bind_param('sssssi', $u_fname, $u_lname, $u_phone, $u_addr, $u_email, $u_id);
        if ($stmt->execute()) {
            $succ = "Profile Updated Successfully!";
        } else {
            $err = "Execution Failed. Please Try Again";
        }
    } else {
        $err = "Database Error. Please Try Again";
    }
}

// Handle Password Change
if (isset($_POST['change_password'])) {
    $old_pwd = $_POST['old_pwd'];
    $new_pwd = $_POST['new_pwd'];
    $confirm_pwd = $_POST['confirm_pwd'];

    global $mysqli;
    // Fetch current password hash
    $stmt = $mysqli->prepare("SELECT password FROM users WHERE id=?");
    $stmt->bind_param('i', $u_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $user_auth = $res->fetch_object();

    if ($user_auth) {
        // Verify old password
        if (password_verify($old_pwd, $user_auth->password)) {
            if ($new_pwd === $confirm_pwd) {
                $new_hashed = password_hash($new_pwd, PASSWORD_DEFAULT);
                $update = $mysqli->prepare("UPDATE users SET password=? WHERE id=?");
                $update->bind_param('si', $new_hashed, $u_id);
                if ($update->execute()) {
                    $succ = "Password Changed Successfully!";
                } else {
                    $err = "Failed to update password.";
                }
            } else {
                $err = "New passwords do not match.";
            }
        } else {
            $err = "Incorrect current password.";
        }
    } else {
        $err = "User not found.";
    }
}

// Fetch current user data
$stmt = $mysqli->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param('i', $u_id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_object();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Profile | Vehicle Booking System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Include Global Theme -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- SweetAlert -->
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>

    <style>
        /* Scoped styles for update profile component */
        .profile-card-wrapper {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            overflow: hidden;
            background: #fff;
            min-height: 600px;
        }

        .sidebar-section {
            background: linear-gradient(135deg, #004d40 0%, #00796b 100%);
            color: white;
            position: relative;
            overflow: hidden;
        }

        /* Decorative circles */
        .circle-decoration {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
        }
        .circle-1 { width: 200px; height: 200px; top: -50px; left: -50px; }
        .circle-2 { width: 150px; height: 150px; bottom: -30px; right: -30px; }

        .avatar-container {
            width: 120px;
            height: 120px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            backdrop-filter: blur(5px);
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .avatar-icon {
            font-size: 3.5rem;
            color: #fff;
        }

        .form-section {
            padding: 40px;
        }

        .form-floating > .form-control {
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            background-color: #f8f9fa;
        }

        .form-floating > .form-control:focus {
            background-color: #fff;
            border-color: #00796b;
            box-shadow: 0 0 0 4px rgba(0, 121, 107, 0.1);
        }

        .form-floating > label {
            color: #666;
        }

        .btn-update {
            background-color: #00796b;
            border-color: #00796b;
            color: white;
            border-radius: 50px;
            padding: 12px 40px;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(0, 121, 107, 0.3);
        }
        
        .btn-update:hover {
            background-color: #004d40;
            border-color: #004d40;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 121, 107, 0.4);
        }
        
        .btn-cancel {
            border-radius: 50px;
            padding: 12px 30px;
            font-weight: 600;
            color: #666;
        }
        
        .btn-cancel:hover {
            background-color: #f1f1f1;
            color: #333;
        }

        /* Tabs Styling */
        .nav-pills .nav-link {
            color: #666;
            font-weight: 600;
            border-radius: 50px;
            padding: 10px 25px;
            margin-right: 10px;
            transition: all 0.3s;
        }
        
        .nav-pills .nav-link.active {
            background-color: #00796b;
            color: #fff;
            box-shadow: 0 4px 10px rgba(0, 121, 107, 0.2);
        }
        
        .nav-pills .nav-link:hover:not(.active) {
            background-color: #f1f1f1;
            color: #00796b;
        }
        
        .password-toggle {
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
            z-index: 10;
        }
    </style>
</head>
<body>

<div class="container-fluid py-4">

    <!-- Back Button -->
    <div class="mb-3">
        <a href="user-dashboard.php" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
        </a>
    </div>

    <!-- Show SweetAlert on success -->
    <?php if (!empty($succ)): ?>
        <script>
            setTimeout(function () {
                swal("Success", "<?php echo $succ; ?>", "success")
                .then(() => {
                    if(typeof loadContent === 'function') {
                        loadContent('user-view-profile.php', '#loadProfileBtn');
                    } else {
                        window.location.href = 'user-dashboard.php';
                    }
                });
            }, 100);
        </script>
    <?php endif; ?>

    <!-- Show SweetAlert on error -->
    <?php if (!empty($err)): ?>
        <script>
            setTimeout(function () {
                swal("Error", "<?php echo $err; ?>", "error");
            }, 100);
        </script>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-xl-10 col-lg-11">
            
            <div class="card profile-card-wrapper">
                <div class="row g-0 h-100">
                    
                    <!-- Left Sidebar Section -->
                    <div class="col-lg-4 sidebar-section d-flex flex-column align-items-center justify-content-center p-5 text-center">
                        <div class="circle-decoration circle-1"></div>
                        <div class="circle-decoration circle-2"></div>
                        
                        <div class="position-relative z-1">
                            <div class="avatar-container mx-auto">
                                <i class="fas fa-user-cog avatar-icon"></i>
                            </div>
                            <h3 class="fw-bold mb-2"><?php echo htmlspecialchars($row->first_name); ?></h3>
                            <p class="mb-4 opacity-75">Manage your account settings and security preferences.</p>
                            
                            <div class="d-none d-lg-block mt-4">
                                <small class="d-block opacity-50 text-uppercase letter-spacing-1 mb-2">Member Since</small>
                                <span class="fw-bold"><?php echo date('F Y', strtotime($row->created_at)); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Right Form Section -->
                    <div class="col-lg-8 form-section">
                        
                        <!-- Tabs -->
                        <ul class="nav nav-pills mb-4" id="pills-tab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="pills-profile-tab" data-bs-toggle="pill" data-bs-target="#pills-profile" type="button" role="tab" aria-controls="pills-profile" aria-selected="true">
                                    <i class="fas fa-user-edit me-2"></i> Edit Profile
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="pills-security-tab" data-bs-toggle="pill" data-bs-target="#pills-security" type="button" role="tab" aria-controls="pills-security" aria-selected="false">
                                    <i class="fas fa-lock me-2"></i> Security
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content" id="pills-tabContent">
                            
                            <!-- Profile Tab -->
                            <div class="tab-pane fade show active" id="pills-profile" role="tabpanel" aria-labelledby="pills-profile-tab">
                                <form method="post">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="text" name="u_fname" id="u_fname" class="form-control" placeholder="First Name" value="<?php echo htmlspecialchars($row->first_name); ?>" required>
                                                <label for="u_fname">First Name</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="text" name="u_lname" id="u_lname" class="form-control" placeholder="Last Name" value="<?php echo htmlspecialchars($row->last_name); ?>" required>
                                                <label for="u_lname">Last Name</label>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="text" name="u_phone" id="u_phone" class="form-control" placeholder="Phone Number" value="<?php echo htmlspecialchars($row->phone); ?>" required>
                                                <label for="u_phone">Phone Number</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="email" name="u_email" id="u_email" class="form-control" placeholder="Email Address" value="<?php echo htmlspecialchars($row->email); ?>" required>
                                                <label for="u_email">Email Address</label>
                                            </div>
                                        </div>
                                        
                                        <div class="col-12">
                                            <div class="form-floating">
                                                <textarea name="u_addr" id="u_addr" class="form-control" placeholder="Address" style="height: 100px" required><?php echo htmlspecialchars($row->address); ?></textarea>
                                                <label for="u_addr">Residential Address</label>
                                            </div>
                                        </div>
                                        
                                        <div class="col-12 d-flex justify-content-end align-items-center gap-3 mt-4">
                                            <a href="user-dashboard.php" class="btn btn-cancel">Cancel</a>
                                            <button type="submit" name="update_profile" class="btn btn-update">
                                                Save Changes <i class="fas fa-arrow-right ms-2"></i>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            
                            <!-- Security Tab -->
                            <div class="tab-pane fade" id="pills-security" role="tabpanel" aria-labelledby="pills-security-tab">
                                <form method="post">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <div class="alert alert-light border-start border-4 border-warning shadow-sm" role="alert">
                                                <i class="fas fa-shield-alt text-warning me-2"></i>
                                                Ensure your account is using a long, random password to stay secure.
                                            </div>
                                        </div>
                                        
                                        <div class="col-12">
                                            <div class="form-floating position-relative">
                                                <input type="password" name="old_pwd" id="old_pwd" class="form-control" placeholder="Current Password" required>
                                                <label for="old_pwd">Current Password</label>
                                                <i class="fas fa-eye password-toggle" onclick="togglePassword('old_pwd', this)"></i>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="form-floating position-relative">
                                                <input type="password" name="new_pwd" id="new_pwd" class="form-control" placeholder="New Password" required>
                                                <label for="new_pwd">New Password</label>
                                                <i class="fas fa-eye password-toggle" onclick="togglePassword('new_pwd', this)"></i>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-floating position-relative">
                                                <input type="password" name="confirm_pwd" id="confirm_pwd" class="form-control" placeholder="Confirm New Password" required>
                                                <label for="confirm_pwd">Confirm New Password</label>
                                                <i class="fas fa-eye password-toggle" onclick="togglePassword('confirm_pwd', this)"></i>
                                            </div>
                                        </div>
                                        
                                        <div class="col-12 d-flex justify-content-end align-items-center gap-3 mt-4">
                                            <button type="submit" name="change_password" class="btn btn-update bg-danger border-danger hover:bg-dark">
                                                Update Password <i class="fas fa-key ms-2"></i>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            
                        </div>
                    </div>
                    
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Scripts -->
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
