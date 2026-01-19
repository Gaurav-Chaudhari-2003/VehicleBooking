<?php
include('../DATABASE FILE/config.php');
include('../DATABASE FILE/checklogin.php');

session_start(); // Required to use $_SESSION for message passing

// Display message if set in session
$message = $_SESSION['msg'] ?? null;
unset($_SESSION['msg']); // Clear message after displaying

if (isset($_POST['add_user'])) {
    $first_name = trim($_POST['u_fname']);
    $last_name = trim($_POST['u_lname']);
    $phone = trim($_POST['u_phone']);
    $address  = trim($_POST['u_addr']);
    $email = trim($_POST['u_email']);
    $password   = password_hash($_POST['u_pwd'], PASSWORD_DEFAULT);
    $remark = trim($_POST['remark']); // Capture remark
    $role = 'EMPLOYEE'; // Default role for new registrations
    $is_active = 0; // Pending approval

    global $mysqli;
    // Updated to use 'users' table as per new schema
    $check = $mysqli->prepare("SELECT email FROM users WHERE email = ?");
    $check->bind_param('s', $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $_SESSION['msg'] = ['type' => 'error', 'text' => 'Email already registered. Please use a different one.'];
    } else {
        // Updated INSERT query for 'users' table
        $query = "INSERT INTO users (first_name, last_name, phone, address, role, email, password, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('sssssssi', $first_name, $last_name, $phone, $address, $role, $email, $password, $is_active);

        if ($stmt->execute()) {
            $new_user_id = $mysqli->insert_id;
            
            // Insert remark if provided
            if (!empty($remark)) {
                $remark_query = "INSERT INTO entity_remarks (entity_type, entity_id, user_id, remark) VALUES (?, ?, ?, ?)";
                $remark_stmt = $mysqli->prepare($remark_query);
                $entity_type = 'USER';
                // user_id is the author of the remark, which is the user themselves in this case
                $remark_stmt->bind_param('siis', $entity_type, $new_user_id, $new_user_id, $remark);
                $remark_stmt->execute();
            }

            $_SESSION['msg'] = ['type' => 'success', 'text' => 'Registration submitted! Await admin approval.'];
        } else {
            $_SESSION['msg'] = ['type' => 'error', 'text' => 'Something went wrong. Please try again later.'];
        }
    }

    // 🔁 Redirect to avoid form re-submission
    header("Location: usr-register.php");
    exit();
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TMS Client - Register</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="vendor/css/sb-admin.css" rel="stylesheet">
</head>
<body class="bg-dark">

<?php if (!empty($message)) : ?>
    <script src="vendor/js/swal.js"></script>
    <script>
        setTimeout(() => {
            swal("<?php echo ucfirst($message['type']); ?>", "<?php echo $message['text']; ?>", "<?php echo $message['type']; ?>");
        }, 100);
    </script>
<?php endif; ?>

<div class="container">
    <div class="card card-register mx-auto mt-5">
        <div class="card-header">Create An Account</div>
        <div class="card-body">
            <form method="post">
                <div class="form-group">
                    <div class="form-row">
                        <div class="col-md-4">
                            <label for="fname">First name</label>
                            <input type="text" id="fname" name="u_fname" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label for="lname">Last name</label>
                            <input type="text" id="lname" name="u_lname" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label for="phone">Contact</label>
                            <input type="text" id="phone" name="u_phone" class="form-control" required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="addr">Address</label>
                    <input type="text" id="addr" name="u_addr" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="remark">Remarks (Optional)</label>
                    <textarea id="remark" name="remark" class="form-control" rows="2" placeholder="Any additional information..."></textarea>
                </div>

                <div class="form-group">
                    <label for="email">Email address</label>
                    <input type="email" id="email" name="u_email" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="pwd">Password</label>
                    <input type="password" id="pwd" name="u_pwd" class="form-control" required>
                </div>

                <button type="submit" name="add_user" class="btn btn-success btn-block">Create Account</button>
            </form>

            <div class="text-center mt-3">
                <a class="d-block small" href="user-login.php">Login Page</a>
                <a class="d-block small" href="../password-reset.php">Forgot Password?</a>
            </div>
        </div>
    </div>
</div>

<!-- JS Scripts -->
<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="vendor/js/swal.js"></script>
</body>
</html>
