<?php
require_once 'includes/config.php';
require_admin();
$msg = null;
$error = null;


$stid=intval($_GET['stid']);

if(isset($_POST['submit']))
{
$studentname=$_POST['fullanme'];
$roolid=$_POST['rollid']; 
$studentemail=$_POST['emailid']; 
$gender=$_POST['gender']; 
$classid=$_POST['class']; 
$dob=$_POST['dob']; $nokname=trim($_POST['next_of_kin_name']??''); $nokphone=trim($_POST['next_of_kin_phone']??'');
$status=$_POST['status'];
$sql="update tblstudents set StudentName=:studentname,RollId=:roolid,StudentEmail=:studentemail,Gender=:gender,ClassId=:classid,DOB=:dob,NextOfKinName=:nokname,NextOfKinPhone=:nokphone,GuardianEmail=:guardianemail,Status=:status,UpdationDate=NOW() where StudentId=:stid ";
$check = $dbh->prepare('SELECT StudentId FROM tblstudents WHERE RollId=:rollid AND ClassId=:classid AND StudentId<>:stid LIMIT 1');
$check->execute([':rollid'=>$roolid, ':classid'=>$classid, ':stid'=>$stid]);
if ($check->fetchColumn()) {
    $error='Another student with Roll ID '.e($roolid).' is already registered in the selected class.';
} else {
    try {
        $query = $dbh->prepare($sql);
        $query->bindParam(':studentname',$studentname,PDO::PARAM_STR);
        $query->bindParam(':roolid',$roolid,PDO::PARAM_STR);
        $query->bindParam(':studentemail',$studentemail,PDO::PARAM_STR);
        $query->bindParam(':gender',$gender,PDO::PARAM_STR);
        $query->bindParam(':dob',$dob,PDO::PARAM_STR);
        $query->bindParam(':classid',$classid,PDO::PARAM_INT);
        $query->bindParam(':status',$status,PDO::PARAM_INT);
        $query->bindParam(':stid',$stid,PDO::PARAM_INT);
        $query->bindParam(':nokname',$nokname,PDO::PARAM_STR);
        $query->bindParam(':nokphone',$nokphone,PDO::PARAM_STR);
        $guardianemail=filter_var(trim($_POST['guardian_email']??''),FILTER_VALIDATE_EMAIL) ?: null; $query->bindValue(':guardianemail',$guardianemail,PDO::PARAM_STR);
        $query->execute();
        $msg='Student info updated successfully';
    } catch (PDOException $ex) {
        if ((int)$ex->errorInfo[1] === 1062) {
            $error='Another student with this Roll ID is already registered in the selected class.';
        } else {
            error_log('SRMS student update error: '.$ex->getMessage());
            $error='Unable to update the student. Please check the details and try again.';
        }
    }
}
}


?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
    	<meta name="viewport" content="width=device-width, initial-scale=1">
        <title>SMS Admin| Edit Student < </title>
        <link rel="stylesheet" href="css/bootstrap.min.css" media="screen" >
        <link rel="stylesheet" href="css/font-awesome.min.css" media="screen" >
        <link rel="stylesheet" href="css/animate-css/animate.min.css" media="screen" >
        <link rel="stylesheet" href="css/lobipanel/lobipanel.min.css" media="screen" >
        <link rel="stylesheet" href="css/prism/prism.css" media="screen" >
        <link rel="stylesheet" href="css/select2/select2.min.css" >
        <link rel="stylesheet" href="css/main.css"><link rel="stylesheet" href="css/srms-modernization.css"><link rel="stylesheet" href="css/srms-responsive.css" media="screen" >
        <script src="js/modernizr/modernizr.min.js"></script>
    </head>
    <body class="top-navbar-fixed">
        <div class="main-wrapper">

            <!-- ========== TOP NAVBAR ========== -->
  <?php include('includes/topbar.php');?> 
            <!-- ========== WRAPPER FOR BOTH SIDEBARS & MAIN CONTENT ========== -->
            <div class="content-wrapper">
                <div class="content-container">

                    <!-- ========== LEFT SIDEBAR ========== -->
                   <?php include('includes/leftbar.php');?>  
                    <!-- /.left-sidebar -->

                    <div class="main-page">

                     <div class="container-fluid">
                            <div class="row page-title-div">
                                <div class="col-md-6">
                                    <h2 class="title">Student Admission</h2>
                                
                                </div>
                                
                                <!-- /.col-md-6 text-right -->
                            </div>
                            <!-- /.row -->
                            <div class="row breadcrumb-div">
                                <div class="col-md-6">
                                    <ul class="breadcrumb">
                                        <li><a href="dashboard.php"><i class="fa fa-home"></i> Home</a></li>
                                
                                        <li class="active">Student Admission</li>
                                    </ul>
                                </div>
                             
                            </div>
                            <!-- /.row -->
                        </div>
                        <div class="container-fluid">
                           
                        <div class="row">
                                    <div class="col-md-12">
                                        <div class="panel">
                                            <div class="panel-heading">
                                                <div class="panel-title">
                                                    <h5>Fill the Student info</h5>
                                                </div>
                                            </div>
                                            <div class="panel-body">
<?php if($msg){?>
<div class="alert alert-success left-icon-alert" role="alert">
 <strong>Well done!</strong><?php echo htmlentities($msg); ?>
 </div><?php } 
else if($error){?>
    <div class="alert alert-danger left-icon-alert" role="alert">
                                            <strong>Oh snap!</strong> <?php echo htmlentities($error); ?>
                                        </div><?php } ?>
                                        
                                                <form class="form-horizontal" method="post">
<?php echo csrf_field(); ?>
<?php 

$sql = "SELECT tblstudents.StudentName,tblstudents.RollId,tblstudents.RegDate,tblstudents.StudentId,tblstudents.Status,tblstudents.StudentEmail,tblstudents.Gender,tblstudents.DOB,tblstudents.ClassId,tblstudents.NextOfKinName,tblstudents.NextOfKinPhone,tblstudents.GuardianEmail,tblclasses.ClassName,tblclasses.Section from tblstudents join tblclasses on tblclasses.id=tblstudents.ClassId where tblstudents.StudentId=:stid";
$query = $dbh->prepare($sql);
$query->bindParam(':stid',$stid,PDO::PARAM_STR);
$query->execute();
$results=$query->fetchAll(PDO::FETCH_OBJ);
$cnt=1;
if($query->rowCount() > 0)
{
foreach($results as $result)
{  ?>


<div class="form-group">
<label for="default" class="col-sm-2 control-label">Full Name</label>
<div class="col-sm-10">
<input type="text" name="fullanme" class="form-control" id="fullanme" value="<?php echo htmlentities($result->StudentName)?>" required="required" autocomplete="off">
</div>
</div>

<div class="form-group">
<label for="default" class="col-sm-2 control-label">Rool Id</label>
<div class="col-sm-10">
<input type="text" name="rollid" class="form-control" id="rollid" value="<?php echo htmlentities($result->RollId)?>" maxlength="5" required="required" autocomplete="off">
</div>
</div>

<div class="form-group">
<label for="default" class="col-sm-2 control-label">Email id)</label>
<div class="col-sm-10">
<input type="email" name="emailid" class="form-control" id="email" value="<?php echo htmlentities($result->StudentEmail)?>" required="required" autocomplete="off">
</div>
</div>



<div class="form-group">
<label for="default" class="col-sm-2 control-label">Gender</label>
<div class="col-sm-10">
<?php  $gndr=$result->Gender;
if($gndr=="Male")
{
?>
<input type="radio" name="gender" value="Male" required="required" checked>Male <input type="radio" name="gender" value="Female" required="required">Female <input type="radio" name="gender" value="Other" required="required">Other
<?php }?>
<?php  
if($gndr=="Female")
{
?>
<input type="radio" name="gender" value="Male" required="required" >Male <input type="radio" name="gender" value="Female" required="required" checked>Female <input type="radio" name="gender" value="Other" required="required">Other
<?php }?>
<?php  
if($gndr=="Other")
{
?>
<input type="radio" name="gender" value="Male" required="required" >Male <input type="radio" name="gender" value="Female" required="required">Female <input type="radio" name="gender" value="Other" required="required" checked>Other
<?php }?>


</div>
</div>



                                                    <div class="form-group">
                                                        <label for="default" class="col-sm-2 control-label">Class</label>
                                                        <div class="col-sm-10">
<select name="class" class="form-control" required><?php $cls=$dbh->query("SELECT id,ClassName,Section FROM tblclasses ORDER BY ClassName,Section")->fetchAll(PDO::FETCH_OBJ); foreach($cls as $c): ?><option value="<?php echo (int)$c->id; ?>" <?php echo ((int)$c->id===(int)$result->ClassId)?"selected":""; ?>><?php echo htmlentities($c->ClassName); ?> - <?php echo htmlentities($c->Section); ?></option><?php endforeach; ?></select>
                                                        </div>
                                                    </div>
<div class="form-group">
                                                        <label for="date" class="col-sm-2 control-label">DOB</label>
                                                        <div class="col-sm-10">
                <input type="date"  name="dob" class="form-control" value="<?php echo htmlentities($result->DOB)?>" id="date">
                                                        </div>
                                                    </div>
<div class="form-group">
<label for="default" class="col-sm-2 control-label">Reg Date: </label>
<div class="col-sm-10">
<?php echo htmlentities($result->RegDate)?>
</div>
</div>

<div class="form-group"><label class="col-sm-2 control-label">Next of Kin Name</label><div class="col-sm-10"><input type="text" name="next_of_kin_name" class="form-control" value="<?php echo htmlentities($result->NextOfKinName??'')?>" maxlength="150" required></div></div><div class="form-group"><label class="col-sm-2 control-label">Next of Kin Phone</label><div class="col-sm-10"><input type="tel" name="next_of_kin_phone" class="form-control" value="<?php echo htmlentities($result->NextOfKinPhone??'')?>" maxlength="30" required></div></div><div class="form-group"><label class="col-sm-2 control-label">Parent/Guardian Email</label><div class="col-sm-10"><input type="email" name="guardian_email" class="form-control" value="<?php echo htmlentities($result->GuardianEmail??'')?>" maxlength="190" placeholder="Optional email for result notifications"></div></div>

<div class="form-group">
<label for="default" class="col-sm-2 control-label">Status</label>
<div class="col-sm-10">
<?php  $stats=$result->Status;
if($stats=="1")
{
?>
<input type="radio" name="status" value="1" required="required" checked>Active <input type="radio" name="status" value="0" required="required">Block 
<?php }?>
<?php  
if($stats=="0")
{
?>
<input type="radio" name="status" value="1" required="required" >Active <input type="radio" name="status" value="0" required="required" checked>Block 
<?php }?>



</div>
</div>

<?php }} ?>                                                    

                                                    
                                                    <div class="form-group">
                                                        <div class="col-sm-offset-2 col-sm-10">
                                                            <button type="submit" name="submit" class="btn btn-primary">Update</button>
                                                        </div>
                                                    </div>
                                                </form>

                                            </div>
                                        </div>
                                    </div>
                                    <!-- /.col-md-12 -->
                                </div>
                    </div>
                </div>
                <!-- /.content-container -->
            </div>
            <!-- /.content-wrapper -->
        </div>
        <!-- /.main-wrapper -->
        <script src="js/jquery/jquery-2.2.4.min.js"></script>
        <script src="js/bootstrap/bootstrap.min.js"></script>
        <script src="js/pace/pace.min.js"></script>
        <script src="js/lobipanel/lobipanel.min.js"></script>
        <script src="js/iscroll/iscroll.js"></script>
        <script src="js/prism/prism.js"></script>
        <script src="js/select2/select2.min.js"></script>
        <script src="js/main.js"></script>
        <script>
            $(function($) {
                $(".js-states").select2();
                $(".js-states-limit").select2({
                    maximumSelectionLength: 2
                });
                $(".js-states-hide").select2({
                    minimumResultsForSearch: Infinity
                });
            });
        </script>
    </body>
</html>

