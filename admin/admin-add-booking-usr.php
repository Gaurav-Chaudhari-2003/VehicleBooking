<?php
  session_start();
  include('../DATABASE FILE/config.php');
  include('../DATABASE FILE/checklogin.php');
  check_login();
  $aid=$_SESSION['a_id'];
  
  //Add Booking
  if(isset($_POST['book_vehicle']))
    {
            $u_id = $_GET['u_id'];
            $vehicle_id = $_POST['vehicle_id']; // Changed from u_car_regno to vehicle_id
            $book_from_date = $_POST['book_from_date'];
            $book_to_date = $_POST['book_to_date'];
            $status = $_POST['status'];
            $pickup_location = $_POST['pickup_location'];
            $drop_location = $_POST['drop_location'];
            $purpose = $_POST['purpose'];
            
            // New Schema: bookings table
            // Columns: user_id, vehicle_id, from_datetime, to_datetime, pickup_location, drop_location, purpose, status
            
            // Ensure dates are formatted correctly (append time if needed)
            if (strlen($book_from_date) == 10) $book_from_date .= ' 00:00:00';
            if (strlen($book_to_date) == 10) $book_to_date .= ' 23:59:59';

            $query="INSERT INTO bookings (user_id, vehicle_id, from_datetime, to_datetime, pickup_location, drop_location, purpose, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            global $mysqli;
            $stmt = $mysqli->prepare($query);
            $stmt->bind_param('iissssss', $u_id, $vehicle_id, $book_from_date, $book_to_date, $pickup_location, $drop_location, $purpose, $status);
            
            if($stmt->execute())
            {
                $succ = "User Booking Added";
                
                // Log the operation
                $action = "Admin Created Booking";
                $log_remark = "Booking created for User ID: $u_id, Vehicle ID: $vehicle_id";
                $entity_type = 'BOOKING';
                $entity_id = $stmt->insert_id;
                
                $hist_stmt = $mysqli->prepare("INSERT INTO operation_history (entity_type, entity_id, action, performed_by, remark) VALUES (?, ?, ?, ?, ?)");
                $hist_stmt->bind_param('sisis', $entity_type, $entity_id, $action, $aid, $log_remark);
                $hist_stmt->execute();
            }
            else 
            {
                $err = "Please Try Again Later. Error: " . $stmt->error;
            }
            $stmt->close();
    }
?>
<!DOCTYPE html>
<html lang="en">

<?php include('vendor/inc/head.php');?>

<body id="page-top">

  <div id="wrapper">

    <!-- Sidebar -->
    <?php include("vendor/inc/sidebar.php");?>
    <!--End Sidebar-->
    <div id="content-wrapper">

      <div class="container-fluid">
      <?php if(isset($succ)) {?>
                        <!--This code for injecting an alert-->
        <script>
                    setTimeout(function () 
                    { 
                        swal("Success!","<?php echo $succ;?>!","success");
                    },
                        100);
        </script>

        <?php } ?>
        <?php if(isset($err)) {?>
        <!--This code for injecting an alert-->
        <script>
                    setTimeout(function () 
                    { 
                        swal("Failed!","<?php echo $err;?>!","error");
                    },
                        100);
        </script>

        <?php } ?>

        <!-- Breadcrumbs-->
        <ol class="breadcrumb">
          <li class="breadcrumb-item">
            <a href="#">Bookings</a>
          </li>
          <li class="breadcrumb-item active">Add</li>
        </ol>
        <hr>
        <div class="card">
        <div class="card-header">
          Add Booking
        </div>
        <div class="card-body">
          <!--Add User Form-->
          <?php
            $u_id=$_GET['u_id'];
            // Updated query to match new schema: users table
            $ret="select * from users where id=?";
            $stmt= $mysqli->prepare($ret) ;
            $stmt->bind_param('i',$u_id);
            $stmt->execute() ;//ok
            $res=$stmt->get_result();
            //$cnt=1;
            while($row=$res->fetch_object())
        {
        ?>
          <form method ="POST"> 
            <div class="form-group">
                <label for="exampleInputEmail1">First Name</label>
                <input type="text" value="<?php echo $row->first_name;?>" readonly class="form-control" name="u_fname">
            </div>
            <div class="form-group">
                <label for="exampleInputEmail1">Last Name</label>
                <input type="text" class="form-control" value="<?php echo $row->last_name;?>" readonly name="u_lname">
            </div>
            <div class="form-group">
                <label for="exampleInputEmail1">Contact</label>
                <input type="text" class="form-control" value="<?php echo $row->phone;?>" readonly name="u_phone">
            </div>
            <div class="form-group">
                <label for="exampleInputEmail1">Address</label>
                <input type="text" class="form-control" value="<?php echo $row->address;?>" readonly name="u_addr">
            </div>
            
            <div class="form-group">
                <label for="exampleInputEmail1">Email address</label>
                <input type="email" value="<?php echo $row->email;?>" class="form-control" readonly name="u_email">
            </div>

            <div class="form-group">
              <label for="exampleFormControlSelect1">Vehicle (Reg No - Name - Category)</label>
              <select class="form-control" name="vehicle_id" id="exampleFormControlSelect1">
                <?php
                // Updated query to match new schema: vehicles table
                $ret="SELECT * FROM vehicles WHERE status = 'AVAILABLE'"; 
                $stmt= $mysqli->prepare($ret) ;
                $stmt->execute() ;//ok
                $res=$stmt->get_result();
                while($veh=$res->fetch_object())
                {
                ?>
                <option value="<?php echo $veh->id;?>"><?php echo $veh->reg_no . ' - ' . $veh->name . ' - ' . $veh->category;?></option>
                <?php }?> 
              </select>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>From Date</label>
                    <input type="date" class="form-control" name="book_from_date" required>
                </div>
                <div class="form-group col-md-6">
                    <label>To Date</label>
                    <input type="date" class="form-control" name="book_to_date" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Pickup Location</label>
                    <input type="text" class="form-control" name="pickup_location" required>
                </div>
                <div class="form-group col-md-6">
                    <label>Drop Location</label>
                    <input type="text" class="form-control" name="drop_location" required>
                </div>
            </div>
            
            <div class="form-group">
                <label>Purpose</label>
                <textarea class="form-control" name="purpose" rows="2"></textarea>
            </div>

            <div class="form-group">
              <label for="exampleFormControlSelect1">Booking Status</label>
              <select class="form-control" name="status" id="exampleFormControlSelect1">
                <option value="APPROVED">Approved</option>
                <option value="PENDING">Pending</option>
              </select>
            </div>

            <button type="submit" name="book_vehicle" class="btn btn-success">Confirm Booking</button>
          </form>
          <!-- End Form-->
        <?php }?>
        </div>
      </div>
       
      <hr>
     

      <!-- Sticky Footer -->
      <?php include("vendor/inc/footer.php");?>

    </div>
    <!-- /.content-wrapper -->

  </div>
  <!-- /#wrapper -->

  <!-- Scroll to Top Button-->
  <a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
  </a>

  <!-- Logout Modal-->
  <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
          <button class="close" type="button" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">×</span>
          </button>
        </div>
        <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
        <div class="modal-footer">
          <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
          <a class="btn btn-danger" href="admin-logout.php">Logout</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap core JavaScript-->
  <script src="vendor/jquery/jquery.min.js"></script>
  <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

  <!-- Core plugin JavaScript-->
  <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

  <!-- Page level plugin JavaScript-->
  <script src="vendor/chart.js/Chart.min.js"></script>
  <script src="vendor/datatables/jquery.dataTables.js"></script>
  <script src="vendor/datatables/dataTables.bootstrap4.js"></script>

  <!-- Custom scripts for all pages-->
  <script src="vendor/js/sb-admin.min.js"></script>

  <!-- Demo scripts for this page-->
  <script src="vendor/js/demo/datatables-demo.js"></script>
  <script src="vendor/js/demo/chart-area-demo.js"></script>
 <!--INject Sweet alert js-->
 <script src="vendor/js/swal.js"></script>

</body>

</html>
