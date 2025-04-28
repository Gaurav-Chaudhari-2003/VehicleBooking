<?php
session_start();
include('vendor/inc/config.php');
include('vendor/inc/checklogin.php');
check_login();
$aid = $_SESSION['a_id'];

// Update User Logic
if (isset($_POST['update_user'])) {
    $u_id = $_GET['u_id'];
    $u_fname = $_POST['u_fname'];
    $u_lname = $_POST['u_lname'];
    $u_phone = $_POST['u_phone'];
    $u_addr = $_POST['u_addr'];
    $u_email = $_POST['u_email'];
    $u_pwd = $_POST['u_pwd'];
    $u_category = $_POST['u_category'];

    $query = "UPDATE tms_user SET u_fname=?, u_lname=?, u_phone=?, u_addr=?, u_category=?, u_email=?, u_pwd=? WHERE u_id=?";
    $stmt = $mysqli->prepare($query);
    $rc = $stmt->bind_param('sssssssi', $u_fname, $u_lname, $u_phone, $u_addr, $u_category, $u_email, $u_pwd, $u_id);
    $stmt->execute();

    if ($stmt) {
        $succ = "User Updated Successfully";
    } else {
        $err = "Update Failed. Try Again Later.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<?php include('vendor/inc/head.php'); ?>

<body id="page-top">

<div id="wrapper">

    <div id="content-wrapper" class="d-flex flex-column">

        <div class="container-fluid mt-4">

            <?php if (isset($succ)) { ?>
                <script>
                    setTimeout(function () {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: '<?php echo $succ; ?>',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }, 100);
                </script>
            <?php } ?>

            <?php if (isset($err)) { ?>
                <script>
                    setTimeout(function () {
                        Swal.fire({
                            icon: 'error',
                            title: 'Failed!',
                            text: '<?php echo $err; ?>',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }, 100);
                </script>
            <?php } ?>

            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Edit User Details</h4>
                </div>

                <div class="card-body">
                    <?php
                    $aid = $_GET['u_id'];
                    $ret = "SELECT * FROM tms_user WHERE u_id=?";
                    $stmt = $mysqli->prepare($ret);
                    $stmt->bind_param('i', $aid);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    while ($row = $res->fetch_object()) {
                        ?>
                        <form method="POST" class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">First Name</label>
                                <input type="text" value="<?php echo $row->u_fname; ?>" required class="form-control" name="u_fname">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Last Name</label>
                                <input type="text" value="<?php echo $row->u_lname; ?>" class="form-control" name="u_lname">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" value="<?php echo $row->u_phone; ?>" class="form-control" name="u_phone">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Address</label>
                                <input type="text" value="<?php echo $row->u_addr; ?>" class="form-control" name="u_addr">
                            </div>

                            <input type="hidden" name="u_category" value="User">

                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" value="<?php echo $row->u_email; ?>" class="form-control" name="u_email">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Password</label>
                                <input type="password" value="<?php echo $row->u_pwd; ?>" class="form-control" name="u_pwd">
                            </div>

                            <div class="col-12 text-end">
                                <button type="submit" name="update_user" class="btn btn-success px-4">
                                    Update User
                                </button>
                            </div>
                        </form>
                    <?php } ?>
                </div>
            </div>

        </div>

    </div>

</div>

<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="vendor/js/sb-admin.min.js"></script>
<script src="vendor/js/swal.js"></script>

</body>

</html>
