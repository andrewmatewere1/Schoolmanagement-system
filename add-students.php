<?php
require_once 'includes/config.php';
require_admin();
require_once __DIR__.'/includes/profile_uploads.php';
$msg = null;
$error = null;

if(isset($_POST['submit']))
{
verify_csrf();
if(!srms_consume_operation($dbh,'student.register')) { $error='This registration was already submitted. Refresh the page before trying again.'; }
else {
$studentname=trim($_POST['fullanme']??'');
$roolid=trim($_POST['rollid']??''); 
$studentemail=trim($_POST['emailid']??''); 
$gender=$_POST['gender']; 
$classid=$_POST['class']; 
$dob=$_POST['dob']; $nokname=trim($_POST['next_of_kin_name']??''); $nokphone=trim($_POST['next_of_kin_phone']??'');
$status=1; $initialStudentPassword=bin2hex(random_bytes(5));
if ($studentname==='' || !filter_var($studentemail,FILTER_VALIDATE_EMAIL) || !$classid || !$dob || !$nokname || !$nokphone) { $error='Please enter a valid name, email, class, date of birth, next-of-kin name and phone number.'; } else {
$sql="INSERT INTO tblstudents(StudentName,RollId,StudentEmail,Gender,ClassId,DOB,NextOfKinName,NextOfKinPhone,GuardianEmail,Status) VALUES(:studentname,:roolid,:studentemail,:gender,:classid,:dob,:nokname,:nokphone,:guardianemail,:status)";
if ($roolid==='') { $roolid='STU-'.date('Y').'-'.strtoupper(bin2hex(random_bytes(4))); }
$check = $dbh->prepare('SELECT StudentId FROM tblstudents WHERE RollId=:rollid LIMIT 1');
$check->execute([':rollid'=>$roolid]);
if ($check->fetchColumn()) {
    $error='A student with Roll ID '.e($roolid).' is already registered in the selected class. Please use a different Roll ID or class.';
} else {
    try {
        $dbh->beginTransaction();
        $query = $dbh->prepare($sql);
        $query->bindParam(':studentname',$studentname,PDO::PARAM_STR);
        $query->bindParam(':roolid',$roolid,PDO::PARAM_STR);
        $query->bindParam(':studentemail',$studentemail,PDO::PARAM_STR);
        $query->bindParam(':gender',$gender,PDO::PARAM_STR);
        $query->bindParam(':classid',$classid,PDO::PARAM_INT);
        $query->bindParam(':dob',$dob,PDO::PARAM_STR);
        $query->bindParam(':status',$status,PDO::PARAM_INT);
        $query->bindParam(':nokname',$nokname,PDO::PARAM_STR);
        $query->bindParam(':nokphone',$nokphone,PDO::PARAM_STR);
        $guardianemail=filter_var(trim($_POST['guardian_email']??''),FILTER_VALIDATE_EMAIL) ?: null; $query->bindValue(':guardianemail',$guardianemail,PDO::PARAM_STR);
        $query->execute();
        $lastInsertId = (int)$dbh->lastInsertId();
        $studentUsername='student'.$lastInsertId;
        $uq=$dbh->prepare('SELECT 1 FROM admin WHERE UserName=:u1 UNION SELECT 1 FROM teachers WHERE username=:u2 LIMIT 1');$uq->execute([':u1'=>$studentUsername,':u2'=>$studentUsername]);if($uq->fetchColumn())$studentUsername='student'.$lastInsertId.bin2hex(random_bytes(2));
        $uq=$dbh->prepare('UPDATE tblstudents SET StudentUsername=:u,StudentPassword=:p WHERE StudentId=:id');$uq->execute([':u'=>$studentUsername,':p'=>password_hash($initialStudentPassword,PASSWORD_DEFAULT),':id'=>$lastInsertId]);
        srms_store_profile_photo($_FILES['profile_photo']??[], 'student', $lastInsertId, $dbh);
        // Lifecycle migration may not be installed during a staged upgrade; record is best-effort.
        try { $life=$dbh->prepare("INSERT INTO srms_student_lifecycle(student_id,event_type,to_class_id,event_date,notes,actor_admin_id) VALUES(:sid,'registered',:classid,CURDATE(),'Initial registration',:admin)"); $life->execute([':sid'=>$lastInsertId,':classid'=>$classid,':admin'=>$_SESSION['admin_id']??null]); } catch(Throwable $ignored) {}
        $dbh->commit();
        $msg = $lastInsertId ? 'Student registered successfully. Registration number: '.$roolid.' | Username: '.$studentUsername.' | Initial password: '.$initialStudentPassword.' (copy and share securely; it will not be shown again).' : 'Something went wrong. Please try again';
    } catch (Throwable $ex) {
        if ($dbh->inTransaction()) $dbh->rollBack();
        if ($ex instanceof PDOException && (int)($ex->errorInfo[1]??0) === 1062) {
            $error='A student with this Roll ID is already registered in the selected class.';
        } else {
            error_log('SRMS student registration error: '.$ex->getMessage());
            $error='Unable to register the student. Please check the details and try again.';
        }
    }
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
        <title>SMS Admin| Student Admission< </title>
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
                                        
                                                <form class="form-horizontal" method="post" enctype="multipart/form-data">
<?php echo csrf_field(); ?>
<?php echo srms_operation_field('student.register'); ?>

<div class="form-group">
<label for="default" class="col-sm-2 control-label">Full Name</label>
<div class="col-sm-10">
<input type="text" name="fullanme" class="form-control" id="fullanme" required="required" autocomplete="off">
</div>
</div>

<div class="form-group">
<label for="rollid" class="col-sm-2 control-label">Registration Number</label>
<div class="col-sm-10">
<input type="text" name="rollid" class="form-control" id="rollid" maxlength="100" placeholder="Leave blank to generate automatically" autocomplete="off">
</div>
</div>

<div class="form-group">
<label class="col-sm-2 control-label">Student Photo (optional)</label><div class="col-sm-10"><input type="file" name="profile_photo" class="form-control" accept="image/jpeg,image/png,image/webp"><small>JPG, PNG or WebP; maximum 3 MB.</small></div></div>
<div class="form-group">
<label for="default" class="col-sm-2 control-label">Email id)</label>
<div class="col-sm-10">
<input type="email" name="emailid" class="form-control" id="email" required="required" autocomplete="off">
</div>
</div>



<div class="form-group"><label class="col-sm-2 control-label">Next of Kin Name</label><div class="col-sm-10"><input type="text" name="next_of_kin_name" class="form-control" maxlength="150" required autocomplete="off"></div></div><div class="form-group"><label class="col-sm-2 control-label">Next of Kin Phone</label><div class="col-sm-10"><input type="tel" name="next_of_kin_phone" class="form-control" maxlength="30" required autocomplete="off" placeholder="e.g. +265 999 000 000"></div></div>
<div class="form-group"><label class="col-sm-2 control-label">Parent/Guardian Email</label><div class="col-sm-10"><input type="email" name="guardian_email" class="form-control" maxlength="190" placeholder="Optional email for result notifications"></div></div>


<div class="form-group">
<label for="default" class="col-sm-2 control-label">Gender</label>
<div class="col-sm-10">
<input type="radio" name="gender" value="Male" required="required" checked="">Male <input type="radio" name="gender" value="Female" required="required">Female <input type="radio" name="gender" value="Other" required="required">Other
</div>
</div>










                                                    <div class="form-group">
                                                        <label for="default" class="col-sm-2 control-label">Class</label>
                                                        <div class="col-sm-10">
 <select name="class" class="form-control" id="default" required="required">
<option value="">Select Class</option>
<?php $sql = "SELECT * from tblclasses";
$query = $dbh->prepare($sql);
$query->execute();
$results=$query->fetchAll(PDO::FETCH_OBJ);
if($query->rowCount() > 0)
{
foreach($results as $result)
{   ?>
<option value="<?php echo htmlentities($result->id); ?>"><?php echo htmlentities($result->ClassName); ?>&nbsp; Section-<?php echo htmlentities($result->Section); ?></option>
<?php }} ?>
 </select>
                                                        </div>
                                                    </div>
<div class="form-group">
                                                        <label for="date" class="col-sm-2 control-label">DOB</label>
                                                        <div class="col-sm-10">
                                                            <input type="date"  name="dob" class="form-control" id="date">
                                                        </div>
                                                    </div>
                                                    

                                                    
                                                    <div class="form-group">
                                                        <div class="col-sm-offset-2 col-sm-10">
                                                            <button type="submit" name="submit" class="btn btn-primary">Add</button>
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
