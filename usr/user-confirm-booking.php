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
    $vehicle_id = $_POST['v_id']; // Assuming you're passing vehicle_id in the form
    $book_from_date = $_POST['book_from_date'];
    $book_to_date = $_POST['book_to_date'];
    $status = 'Pending'; // Default status
    $remarks = isset($_POST['remarks']) && trim($_POST['remarks']) !== '' ? trim($_POST['remarks']) : 'NA';


    // STEP 1: Check for existing booking conflicts in the tms_booking table (for Pending or Approved status)
    $statusStmt = $mysqli->prepare("SELECT * FROM tms_booking WHERE vehicle_id = ? AND ((? BETWEEN book_from_date AND book_to_date) OR (? BETWEEN book_from_date AND book_to_date)) AND status = 'Approved'");
    $statusStmt->bind_param('iss', $vehicle_id, $book_from_date, $book_to_date);
    $statusStmt->execute();
    $statusResult = $statusStmt->get_result();

    if ($statusResult->num_rows > 0) {
        // There is a conflict with the selected booking dates
        $alert_type = 'warning';
        $alert_title = 'Warning';
        $alert_text = "This vehicle is already booked for the selected date range. Please choose a different range.";
    } else {
        // STEP 2: Insert the new booking into the tms_booking table
        $query = "INSERT INTO tms_booking (user_id, vehicle_id, book_from_date, book_to_date, status, remarks) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('iissss', $u_id, $vehicle_id, $book_from_date, $book_to_date, $status, $remarks);

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
