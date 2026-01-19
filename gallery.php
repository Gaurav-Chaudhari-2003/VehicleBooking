<?php
global $mysqli;
session_start();
include('DATABASE FILE/config.php'); // Corrected path
?>
<!DOCTYPE html>
<html lang="en">

<!--Head-->
<?php include("vendor/inc/head.php");?>
<!--End Head-->

<body>

  <!-- Navigation -->
  <?php include("vendor/inc/nav.php");?>

  <!-- Page Content -->
  <div class="container">

    <!-- Page Heading/Breadcrumbs -->
    <h1 class="mt-4 mb-3">Our Gallery
    </h1>

    <ol class="breadcrumb">
      <li class="breadcrumb-item">
        <a href="index.php">Home</a>
      </li>
      <li class="breadcrumb-item active">Gallery</li>
    </ol>
    <?php
      // Updated query to match new schema: vehicles table
      $ret="SELECT * FROM vehicles ORDER BY RAND() LIMIT 10 "; 
      $stmt= $mysqli->prepare($ret) ;
      $stmt->execute() ;//ok
      $res=$stmt->get_result();
      $cnt=1;
      while($row=$res->fetch_object())
      {
        // Updated column names: image, category, name, capacity, reg_no
        $imagePath = 'vendor/img/' . ($row->image ?: 'placeholder.png');
    ?>
    <!-- Project One -->
    <div class="row">
      <div class="col-md-7">
        <a href="#">
          <img class="img-fluid rounded mb-3 mb-md-0" src="<?php echo $imagePath;?>" alt="">
        </a>
      </div>
      <div class="col-md-5">
        <h3><?php echo $row->category;?></h3>
        <ul class="list-group list-group-horizontal">
        <li class="list-group-item"><?php echo $row->name;?></li>
          <li class="list-group-item"><?php echo $row->capacity ;?> Seats</li>
          <li class="list-group-item"><span class="badge badge-success">Available</span></li>
          <li class="list-group-item"><?php echo $row->reg_no;?></li>
        </ul><br>
        <a class="btn btn-success" href="usr/">Hire Vehicle
          <span class="glyphicon glyphicon-chevron-right"></span>
        </a>
      </div>
    </div>
    <!-- /.row -->

    <hr>
      <?php }?>
    
  <hr>
  
</div>  

  <?php include("vendor/inc/footer.php");?>

  <!-- Bootstrap core JavaScript -->
  <script src="vendor/jquery/jquery.min.js"></script>
  <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

</body>

</html>
