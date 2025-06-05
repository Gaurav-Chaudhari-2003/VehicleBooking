<?php
include('vendor/inc/config.php');

session_start(); // Required to use $_SESSION for message passing

if (isset($_POST['add_user'])) {
    $u_fname = trim($_POST['u_fname']);
    $u_lname = trim($_POST['u_lname']);
    $u_phone = trim($_POST['u_phone']);
    $u_addr  = trim($_POST['u_addr']);
    $u_email = trim($_POST['u_email']);
    $u_pwd   = password_hash($_POST['u_pwd'], PASSWORD_DEFAULT);
    $u_category = 'User';

    $check = $mysqli->prepare("SELECT u_email FROM tms_pending_user WHERE u_email = ?");
    $check->bind_param('s', $u_email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $_SESSION['msg'] = ['type' => 'error', 'text' => 'Email already registered. Please use a different one.'];
    } else {
        $query = "INSERT INTO tms_pending_user (u_fname, u_lname, u_phone, u_addr, u_category, u_email, u_pwd) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('sssssss', $u_fname, $u_lname, $u_phone, $u_addr, $u_category, $u_email, $u_pwd);

        if ($stmt->execute()) {
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

                <input type="hidden" name="u_category" value="User">

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
                <a class="d-block small" href="profile/usr-forgot-pwd.php">Forgot Password?</a>
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
