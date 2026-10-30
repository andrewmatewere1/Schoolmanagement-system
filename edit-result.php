<?php
require_once 'includes/config.php'; require_once 'includes/phase1.php';
require_admin();
$msg = null;
$error = null;
$stid=(int)($_GET['stid']??0);
$msg=''; $error='';

// Allow an administrator to return a teacher submission to rework.
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['reject_rework'])) {
    $submissionId=(int)($_POST['submission_id']??0);
    $reason=trim($_POST['rejection_reason']??'');
    if(!$submissionId || $reason==='') {
        $error='A valid submission and rejection reason are required.';
    } else {
        try {
            $dbh->beginTransaction();
            $q=$dbh->prepare("UPDATE result_submissions SET submission_status='rework', approval_state='pending', reviewed_by=:admin, reviewed_at=NOW(), rejection_reason=:reason, updated_at=NOW() WHERE id=:id AND student_id=:student AND submission_status IN ('pending','approved','rejected')");
            $q->execute([':admin'=>(int)$_SESSION['admin_id'],':reason'=>$reason,':id'=>$submissionId,':student'=>$stid]);
            if($q->rowCount()<1) throw new RuntimeException('The submission could not be returned for rework.');
            $r=$dbh->prepare("UPDATE tblresult SET result_status='rework', approval_state='pending', Published=0, published_by=NULL, published_at=NULL, rejection_reason=:reason, UpdationDate=NOW() WHERE submission_id=:id");
            $r->execute([':reason'=>$reason,':id'=>$submissionId]);
            $dbh->commit();
            audit_log($dbh,'RESULT_RETURN_REWORK','Admin returned submission '.$submissionId.' for student '.$stid.' to teacher.');
            $msg='The result has been returned to the teacher for rework.';
        } catch(Throwable $ex) {
            if($dbh->inTransaction()) $dbh->rollBack();
            $error=$ex->getMessage();
        }
    }
}
if(isset($_POST['submit'])) {
    $rowid=$_POST['id']??[]; $marks=$_POST['marks']??[];
    if (!is_array($rowid) || !is_array($marks) || count($rowid)!==count($marks)) {
        $error='Invalid result submission.';
    } else {
        try {
            $dbh->beginTransaction();
            foreach($rowid as $count=>$id) {
                $mrks=filter_var($marks[$count],FILTER_VALIDATE_INT,['options'=>['min_range'=>0,'max_range'=>100]]);
                if($mrks===false) throw new RuntimeException('Marks must be between 0 and 100.');
                $rq=$dbh->prepare('SELECT AcademicYear,Term,ExamType,Published FROM tblresult WHERE id=:id FOR UPDATE');
                $rq->execute([':id'=>(int)$id]); $old=$rq->fetch();
                if(!$old) throw new RuntimeException('Result record not found.');
                period_require_unlocked($dbh,$old['AcademicYear'],$old['Term'],$old['ExamType']);
                if((int)$old['Published']===1) throw new RuntimeException('Published results are locked. Unpublish the result before editing it.');
                snapshot_result($dbh,(int)$id,'admin',(int)$_SESSION['admin_id'],'ADMIN_EDIT','Administrator edited result');
                $q=$dbh->prepare("UPDATE tblresult SET marks=:mrks,Published=0,published_by=NULL,published_at=NULL,approval_state='approved',result_status='approved',approved_by=:admin,approved_at=NOW(),UpdationDate=NOW() WHERE id=:iid");
                $q->execute([':mrks'=>$mrks,':admin'=>(int)$_SESSION['admin_id'],':iid'=>(int)$id]);
            }
            $dbh->commit(); audit_log($dbh,'RESULT_EDIT','Updated results for student '.$stid); $msg='Result information updated successfully.';
        } catch(Throwable $e) { if($dbh->inTransaction())$dbh->rollBack(); $error=$e->getMessage(); }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
    	<meta name="viewport" content="width=device-width, initial-scale=1">
        <title>SMS Admin|  Student result info < </title>
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
                                    <h2 class="title">Student Result Info</h2>
                                
                                </div>
                                
                                <!-- /.col-md-6 text-right -->
                            </div>
                            <!-- /.row -->
                            <div class="row breadcrumb-div">
                                <div class="col-md-6">
                                    <ul class="breadcrumb">
                                        <li><a href="dashboard.php"><i class="fa fa-home"></i> Home</a></li>
                                
                                        <li class="active">Result Info</li>
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
                                                    <h5>Update the Result info</h5>
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

$ret = "SELECT tblstudents.StudentName,tblclasses.ClassName,tblclasses.Section from tblresult join tblstudents on tblresult.StudentId=tblresult.StudentId join tblsubjects on tblsubjects.id=tblresult.SubjectId join tblclasses on tblclasses.id=tblstudents.ClassId where tblstudents.StudentId=:stid limit 1";
$stmt = $dbh->prepare($ret);
$stmt->bindParam(':stid',$stid,PDO::PARAM_STR);
$stmt->execute();
$result=$stmt->fetchAll(PDO::FETCH_OBJ);
$cnt=1;
if($stmt->rowCount() > 0)
{
foreach($result as $row)
{  ?>


                                                    <div class="form-group">
                                            <label for="default" class="col-sm-2 control-label">Class</label>
                                                        <div class="col-sm-10">
<?php echo htmlentities($row->ClassName)?>(<?php echo htmlentities($row->Section)?>)
                                                        </div>
                                                    </div>
<div class="form-group">
<label for="default" class="col-sm-2 control-label">Full Name</label>
<div class="col-sm-10">
<?php echo htmlentities($row->StudentName);?>
</div>
</div>
<?php } }?>



<?php 
$sql = "SELECT distinct tblstudents.StudentName,tblstudents.StudentId,tblclasses.ClassName,tblclasses.Section,tblsubjects.SubjectName,tblresult.marks,tblresult.id as resultid,tblresult.AcademicYear,tblresult.Term,tblresult.ExamType,tblresult.submission_id from tblresult join tblstudents on tblstudents.StudentId=tblresult.StudentId join tblsubjects on tblsubjects.id=tblresult.SubjectId join tblclasses on tblclasses.id=tblstudents.ClassId where tblstudents.StudentId=:stid ";
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
<label for="default" class="col-sm-2 control-label"><?php echo htmlentities($result->SubjectName)?></label>
<div class="col-sm-10">
<input type="hidden" name="id[]" value="<?php echo htmlentities($result->resultid)?>">
<input type="text" name="marks[]" class="form-control" id="marks" value="<?php echo htmlentities($result->marks)?>" maxlength="5" required="required" autocomplete="off">
</div>
</div>




<?php }} ?>                                                    

                                                    
                                                    <div class="form-group">
                                                        <div class="col-sm-offset-2 col-sm-10">
                                                            <button type="submit" name="submit" class="btn btn-primary">Update</button>
                                                        </div>
                                                    </div>
                                                </form>

<?php
$subQ=$dbh->prepare("SELECT rs.id,rs.academic_year,rs.term,rs.exam_type,rs.submission_status,rs.rejection_reason,t.full_name FROM result_submissions rs JOIN teachers t ON t.id=rs.teacher_id WHERE rs.student_id=:student ORDER BY rs.updated_at DESC");
$subQ->execute([':student'=>$stid]);
$submissions=$subQ->fetchAll(PDO::FETCH_ASSOC);
if($submissions):
?>
<hr>
<h4>Teacher Submission Workflow</h4>
<p class="text-muted">Use this control when the administrator wants the teacher to correct and resubmit a result. Teachers cannot publish results.</p>
<?php foreach($submissions as $sub): ?>
<div class="panel panel-default"><div class="panel-body">
<strong><?php echo e($sub['academic_year'].' / '.$sub['term'].' / '.$sub['exam_type']); ?></strong>
<span class="label label-<?php echo $sub['submission_status']==='approved'?'success':($sub['submission_status']==='rework'?'warning':'info'); ?> pull-right"><?php echo e(result_status_label($sub['submission_status'])); ?></span>
<p class="text-muted">Teacher: <?php echo e($sub['full_name']); ?></p>
<?php if(!in_array($sub['submission_status'],['rework'],true)): ?>
<form method="post" class="row"><div class="col-sm-8"><input type="hidden" name="submission_id" value="<?php echo (int)$sub['id']; ?>"><div class="form-group"><label>Reason for rework</label><textarea name="rejection_reason" class="form-control" rows="2" required placeholder="Explain what the teacher must correct..."></textarea></div></div><div class="col-sm-4" style="padding-top:25px"><button type="submit" name="reject_rework" value="1" class="btn btn-warning" onclick="return confirm('Return this result to the teacher for rework?');">Reject &amp; Return for Rework</button></div></form>
<?php else: ?><div class="alert alert-warning">Already returned to the teacher for rework.<?php if($sub['rejection_reason']): ?> Reason: <?php echo e($sub['rejection_reason']); ?><?php endif; ?></div><?php endif; ?>
</div></div>
<?php endforeach; endif; ?>

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

