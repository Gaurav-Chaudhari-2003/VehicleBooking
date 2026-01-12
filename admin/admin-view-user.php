<?php
session_start();
include('vendor/inc/config.php');
include('vendor/inc/checklogin.php');
check_login();
$aid = $_SESSION['a_id'];

// Handle Add User (Admin manually adding a user)
if (isset($_POST['add_user'])) {
    $u_fname = $_POST['u_fname'];
    $u_lname = $_POST['u_lname'];
    $u_phone = $_POST['u_phone'];
    $u_addr = $_POST['u_addr'];
    $u_email = $_POST['u_email'];
    $u_pwd = $_POST['u_pwd'];
    $u_category = "User";

    // Default values for fields that don't have a default value in DB
    $u_car_type = '';
    $u_car_regno = '';
    $u_car_bookdate = '';
    $u_car_book_status = '';

    $query = "INSERT INTO tms_user (u_fname, u_lname, u_phone, u_addr, u_category, u_email, u_pwd, u_car_type, u_car_regno, u_car_bookdate, u_car_book_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('sssssssssss', $u_fname, $u_lname, $u_phone, $u_addr, $u_category, $u_email, $u_pwd, $u_car_type, $u_car_regno, $u_car_bookdate, $u_car_book_status);
    if ($stmt->execute()) {
        $succ = "User Added Successfully";
    } else {
        $err = "Error! Please Try Again Later";
    }
}

// Handle Delete User
if (isset($_GET['del'])) {
    $id = intval($_GET['del']);
    $adn = "DELETE FROM tms_user WHERE u_id = ?";
    $stmt = $mysqli->prepare($adn);
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        $succ = "User Deleted Successfully";
    } else {
        $err = "Unable to Delete User";
    }
}

// Approve pending user
if (isset($_GET['approve'])) {
    $id = intval($_GET['approve']);
    $query = "SELECT * FROM tms_pending_user WHERE id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Default values for fields that don't have a default value in DB
        $u_car_type = '';
        $u_car_regno = '';
        $u_car_bookdate = '';
        $u_car_book_status = '';

        $insert = "INSERT INTO tms_user (u_fname, u_lname, u_phone, u_addr, u_category, u_email, u_pwd, u_car_type, u_car_regno, u_car_bookdate, u_car_book_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt2 = $mysqli->prepare($insert);
        $stmt2->bind_param('sssssssssss', $user['u_fname'], $user['u_lname'], $user['u_phone'], $user['u_addr'], $user['u_category'], $user['u_email'], $user['u_pwd'], $u_car_type, $u_car_regno, $u_car_bookdate, $u_car_book_status);
        if ($stmt2->execute()) {
            $delete = $mysqli->prepare("DELETE FROM tms_pending_user WHERE id = ?");
            $delete->bind_param("i", $id);
            $delete->execute();
            $succ = "User Approved Successfully";
        } else {
            $err = "Failed to Approve User";
        }
    } else {
        $err = "User Not Found";
    }
}

// Reject pending user
if (isset($_GET['reject'])) {
    $id = intval($_GET['reject']);
    $stmt = $mysqli->prepare("DELETE FROM tms_pending_user WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $succ = "User Rejected and Removed Successfully";
    } else {
        $err = "Failed to Reject User";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<?php include('vendor/inc/head.php'); ?>

<body id="page-top">

<div id="wrapper">
    <div id="content-wrapper">
        <div class="container-fluid">

            <?php if (isset($_SESSION['succ'])) { ?>
                <script>
                    setTimeout(function() {
                        swal("Success!", "<?php echo $_SESSION['succ']; ?>", "success");
                    }, 100);
                </script>
                <?php unset($_SESSION['succ']); ?>
            <?php } ?>

            <?php if (isset($_SESSION['err'])) { ?>
                <script>
                    setTimeout(function() {
                        swal("Failed!", "<?php echo $_SESSION['err']; ?>", "error");
                    }, 100);
                </script>
                <?php unset($_SESSION['err']); ?>
            <?php } ?>


            <div class="card mb-3">
                <div class="card-header">
                    <i class="fas fa-user-plus"></i> Add New User
                </div>
                <div class="card-body text-center">
                    <a href="admin-add-user.php" class="btn btn-success">Add New User</a>
                </div>
            </div>

            <!-- Pending Approvals -->
            <div class="card mb-3">
                <div class="card-header">
                    <i class="fas fa-user-clock"></i> Pending User Approvals
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-striped" width="100%">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Contact</th>
                                <th>Address</th>
                                <th>Email</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            $ret = "SELECT * FROM tms_pending_user ORDER BY id DESC";
                            $stmt = $mysqli->prepare($ret);
                            $stmt->execute();
                            $res = $stmt->get_result();
                            $cnt = 1;
                            while ($row = $res->fetch_object()) {
                                ?>
                                <tr>
                                    <td><?php echo $cnt++; ?></td>
                                    <td><?php echo $row->u_fname . " " . $row->u_lname; ?></td>
                                    <td><?php echo $row->u_phone; ?></td>
                                    <td><?php echo $row->u_addr; ?></td>
                                    <td><?php echo $row->u_email; ?></td>
                                    <td>
                                        <a href="?approve=<?php echo $row->id; ?>" class="badge badge-success" onclick="return confirm('Approve this user?');">Approve</a>
                                        <a href="?reject=<?php echo $row->id; ?>" class="badge badge-danger" onclick="return confirm('Reject this user?');">Reject</a>
                                    </td>
                                </tr>
                            <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Registered Users -->
            <div class="card mb-3">
                <div class="card-header">
                    <i class="fas fa-users"></i> Registered Users
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-striped" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Contact</th>
                                <th>Address</th>
                                <th>Email</th>
                                <th>Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            $ret = "SELECT * FROM tms_user WHERE u_category = 'User' ORDER BY u_id DESC";
                            $stmt = $mysqli->prepare($ret);
                            $stmt->execute();
                            $res = $stmt->get_result();
                            $cnt = 1;
                            while ($row = $res->fetch_object()) {
                                ?>
                                <tr>
                                    <td><?php echo $cnt++; ?></td>
                                    <td><?php echo $row->u_fname . " " . $row->u_lname; ?></td>
                                    <td><?php echo $row->u_phone; ?></td>
                                    <td><?php echo $row->u_addr; ?></td>
                                    <td><?php echo $row->u_email; ?></td>
                                    <td>
                                        <a href="admin-manage-single-usr.php?u_id=<?php echo $row->u_id; ?>" class="badge badge-success"><i class="fa fa-edit"></i> Update</a>
                                        <a href="?del=<?php echo $row->u_id; ?>" class="badge badge-danger" onclick="return confirm('Do you really want to delete this user?');"><i class="fa fa-trash"></i> Delete</a>
                                    </td>
                                </tr>
                            <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer small text-muted">
                    <?php
                    date_default_timezone_set("Asia/Kolkata");
                    echo "Generated : " . date("h:i:sa");
                    ?>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- JS Scripts -->
<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="vendor/datatables/jquery.dataTables.js"></script>
<script src="vendor/datatables/dataTables.bootstrap4.js"></script>
<script src="vendor/js/sb-admin.min.js"></script>
<script src="vendor/js/demo/datatables-demo.js"></script>
<script src="vendor/js/swal.js"></script>

</body>
</html>