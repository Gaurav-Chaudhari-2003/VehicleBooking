<?php
global $mysqli;
session_start();
include('vendor/inc/config.php');
include('vendor/inc/checklogin.php');
check_login();

$alert_type = '';
$alert_title = '';
$alert_text = '';
$redirect_url = 'user-dashboard.php';

if (isset($_POST['book_vehicle'])) {
    $u_id = $_SESSION['u_id'];
    $u_car_type = $_POST['u_car_type'];
    $u_car_regno  = $_POST['u_car_regno'];
    $u_car_bookdate = $_POST['u_car_bookdate'];
    $u_car_book_status  = $_POST['u_car_book_status'];

    // STEP 1: Check for existing booking with status Pending or Approved
    $statusStmt = $mysqli->prepare("SELECT u_car_book_status FROM tms_user WHERE u_id = ? AND (u_car_book_status = 'Pending' OR u_car_book_status = 'Approved')");
    $statusStmt->bind_param('i', $u_id);
    $statusStmt->execute();
    $statusResult = $statusStmt->get_result();

    if ($statusResult->num_rows > 0) {
        $statusRow = $statusResult->fetch_assoc();
        $status = $statusRow['u_car_book_status'];

        if ($status === 'Pending') {
            $alert_type = 'warning';
            $alert_title = 'Warning';
            $alert_text = "You already have a pending vehicle booking request. Please cancel it before making a new one.";
        } elseif ($status === 'Approved') {
            $alert_type = 'warning';
            $alert_title = 'Warning';
            $alert_text = "You already have an approved vehicle booking. Please complete or cancel it before requesting another.";
        }
    } else {
        // Update vehicle booking in the database
        $query = "UPDATE tms_user SET u_car_type=?, u_car_bookdate=?, u_car_regno=?, u_car_book_status=? WHERE u_id=?";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('ssssi', $u_car_type, $u_car_bookdate, $u_car_regno, $u_car_book_status, $u_id);

        if ($stmt->execute()) {
            $alert_type = 'success';
            $alert_title = 'Success';
            $alert_text = 'Your vehicle booking has been successfully submitted!';
        } else {
            $alert_type = 'error';
            $alert_title = 'Error';
            $alert_text = 'There was an issue submitting your booking. Please try again later.';
        }
    }
}
?>

<?php include("vendor/inc/head.php"); ?>
<body>
<!-- Ensure SweetAlert is loaded -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Display SweetAlert upon action -->
<?php if (!empty($alert_type)): ?>
    <script>
        console.log("Alert Type: <?php echo $alert_type; ?>");  // Debugging: Check if the alert is set
        console.log("Alert Title: <?php echo $alert_title; ?>");  // Debugging: Check if the title is set
        console.log("Alert Text: <?php echo $alert_text; ?>");  // Debugging: Check if the text is set

        setTimeout(() => {
            Swal.fire({
                icon: '<?php echo $alert_type; ?>',
                title: '<?php echo $alert_title; ?>',
                text: '<?php echo $alert_text; ?>',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            }).then(() => {
                window.location.href = '<?php echo $redirect_url; ?>';
            });
        }, 100);
    </script>
<?php endif; ?>
</body>
