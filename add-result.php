<?php
require_once 'includes/config.php'; require_once 'includes/phase1.php'; require_admin();
$msg = null;
$error = null;
$msg=''; $error='';
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['submit'])) {
    verify_csrf();
    if(!srms_consume_operation($dbh,'result.save')) { $error='This result submission was already processed.'; }
    else {
    $class=(int)($_POST['class']??0); $studentid=(int)($_POST['studentid']??0);
    $academic_year=trim($_POST['academic_year']??date('Y').'/'.(date('Y')+1));
    $term=trim($_POST['term']??'Final'); $exam_type=trim($_POST['exam_type']??'Final Examination');
    $marks=$_POST['marks']??[];
    if (!$class || !$studentid || !$academic_year || !$term || !$exam_type || !is_array($marks)) { $error='Please complete all required fields.'; }
    else {
        $st=$dbh->prepare('SELECT StudentId FROM tblstudents WHERE StudentId=:sid AND ClassId=:cid AND Status=1'); $st->execute([':sid'=>$studentid,':cid'=>$class]);
        if (!$st->fetch()) $error='Selected student does not belong to the selected class.';
        else {
            $sub=$dbh->prepare('SELECT SubjectId FROM tblsubjectcombination WHERE ClassId=:cid AND status=1 ORDER BY SubjectId'); $sub->execute([':cid'=>$class]); $subjects=$sub->fetchAll(PDO::FETCH_COLUMN);
            if (count($marks)!==count($subjects)) $error='The submitted subjects do not match the class subject combination.';
            else {
                try { period_ensure($dbh,$academic_year,$term,$exam_type); period_require_unlocked($dbh,$academic_year,$term,$exam_type); $dbh->beginTransaction();
                    foreach ($subjects as $i=>$sid) { $mark=filter_var($marks[$i],FILTER_VALIDATE_INT,['options'=>['min_range'=>0,'max_range'=>100]]); if ($mark===false) throw new RuntimeException('Every mark must be a whole number from 0 to 100.');
                        $q=$dbh->prepare('SELECT id FROM tblresult WHERE StudentId=:student AND ClassId=:class AND SubjectId=:subject AND AcademicYear=:year AND Term=:term AND ExamType=:exam LIMIT 1');
                        $q->execute([':student'=>$studentid,':class'=>$class,':subject'=>$sid,':year'=>$academic_year,':term'=>$term,':exam'=>$exam_type,':admin'=>(int)$_SESSION['admin_id']]); $existing=$q->fetchColumn();
                        if ($existing) { $pq=$dbh->prepare('SELECT Published FROM tblresult WHERE id=:id'); $pq->execute([':id'=>(int)$existing]); if((int)$pq->fetchColumn()===1) throw new RuntimeException('This result is already published and locked. Unpublish it before editing.'); snapshot_result($dbh,(int)$existing,'admin',(int)$_SESSION['admin_id'],'ADMIN_EDIT','Admin edited result'); $u=$dbh->prepare("UPDATE tblresult SET marks=:marks, Published=0, result_status='approved', approval_state='approved', approved_by=:admin, approved_at=NOW(), UpdationDate=NOW() WHERE id=:id"); $u->execute([':marks'=>$mark,':admin'=>(int)$_SESSION['admin_id'],':id'=>$existing]); }
                        else { $u=$dbh->prepare("INSERT INTO tblresult(StudentId,ClassId,SubjectId,marks,AcademicYear,Term,ExamType,MaxMarks,Published,result_status,approval_state,approved_by,approved_at) VALUES(:student,:class,:subject,:marks,:year,:term,:exam,100,0,'approved','approved',:admin,NOW())"); $u->execute([':student'=>$studentid,':class'=>$class,':subject'=>$sid,':marks'=>$mark,':year'=>$academic_year,':term'=>$term,':exam'=>$exam_type,':admin'=>(int)$_SESSION['admin_id']]); }
                    }
                    $dbh->commit(); audit_log($dbh,'RESULT_SAVE','Saved result for student '.$studentid.' / '.$academic_year.' / '.$exam_type); $msg='Result saved successfully.';
                } catch(Throwable $e) { if($dbh->inTransaction()) $dbh->rollBack(); $error=$e->getMessage(); error_log('SRMS result save: '.$e->getMessage()); }
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
        <title>SMS Admin| Add Result </title>
        <link rel="stylesheet" href="css/bootstrap.min.css" media="screen" >
        <link rel="stylesheet" href="css/font-awesome.min.css" media="screen" >
        <link rel="stylesheet" href="css/animate-css/animate.min.css" media="screen" >
        <link rel="stylesheet" href="css/lobipanel/lobipanel.min.css" media="screen" >
        <link rel="stylesheet" href="css/prism/prism.css" media="screen" >
        <link rel="stylesheet" href="css/select2/select2.min.css" >
        <link rel="stylesheet" href="css/main.css"><link rel="stylesheet" href="css/srms-modernization.css"><link rel="stylesheet" href="css/srms-responsive.css" media="screen" >
        <script src="js/modernizr/modernizr.min.js"></script>
        <script>
function getStudent(val) {
    $.ajax({
    type: "POST",
    url: "get_student.php",
    data:{classid:val,csrf_token:'<?php echo e(csrf_token()); ?>'},
    success: function(data){
        $("#studentid").html(data);
        
    }
    });
$.ajax({
        type: "POST",
        url: "get_student.php",
        data:{classid1:val,csrf_token:'<?php echo e(csrf_token()); ?>'},
        success: function(data){
            $("#subject").html(data);
            
        }
        });
}
    </script>
<script>

function getresult(val,clid) 
{   
    
var clid=$(".clid").val();
var val=$(".stid").val();;
var abh=clid+'$'+val;
//alert(abh);
    $.ajax({
        type: "POST",
        url: "get_student.php",
        data:{studclass:abh,csrf_token:'<?php echo e(csrf_token()); ?>'},
        success: function(data){
            $("#reslt").html(data);
            
        }
        });
}
</script>


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
                                    <h2 class="title">Declare Result</h2>
                                
                                </div>
                                
                                <!-- /.col-md-6 text-right -->
                            </div>
                            <!-- /.row -->
                            <div class="row breadcrumb-div">
                                <div class="col-md-6">
                                    <ul class="breadcrumb">
                                        <li><a href="dashboard.php"><i class="fa fa-home"></i> Home</a></li>
                                
                                        <li class="active">Student Result</li>
                                    </ul>
                                </div>
                             
                            </div>
                            <!-- /.row -->
                        </div>
                        <div class="container-fluid">
                           
                        <div class="row">
                                    <div class="col-md-12">
                                        <div class="panel">
                                           
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
<?php echo srms_operation_field('result.save'); ?>
<div class="form-group"><label class="col-sm-2 control-label">Academic Year</label><div class="col-sm-10"><input name="academic_year" class="form-control" required value="<?php echo e($_POST['academic_year'] ?? (date('Y').'/'.(date('Y')+1))); ?>" placeholder="2026/2027"></div></div>
<div class="form-group"><label class="col-sm-2 control-label">Term</label><div class="col-sm-10"><select name="term" class="form-control"><option>Term 1</option><option>Term 2</option><option selected>Final</option></select></div></div>
<div class="form-group"><label class="col-sm-2 control-label">Examination</label><div class="col-sm-10"><input name="exam_type" class="form-control" required value="<?php echo e($_POST['exam_type'] ?? 'Final Examination'); ?>"></div></div>

 <div class="form-group">
<label for="default" class="col-sm-2 control-label">Class</label>
 <div class="col-sm-10">
 <select name="class" class="form-control clid" id="classid" onChange="getStudent(this.value);" required="required">
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
                                                        <label for="date" class="col-sm-2 control-label ">Student Name</label>
                                                        <div class="col-sm-10">
                                                    <select name="studentid" class="form-control stid" id="studentid" required="required" onChange="getresult(this.value);">
                                                    </select>
                                                        </div>
                                                    </div>

                                                    <div class="form-group">
                                                      
                                                        <div class="col-sm-10">
                                                    <div  id="reslt">
                                                    </div>
                                                        </div>
                                                    </div>
                                                    
<div class="form-group">
                                                        <label for="date" class="col-sm-2 control-label">Subjects</label>
                                                        <div class="col-sm-10">
                                                    <div  id="subject">
                                                    </div>
                                                        </div>
                                                    </div>


                                                    
                                                    <div class="form-group">
                                                        <div class="col-sm-offset-2 col-sm-10">
                                                            <button type="submit" name="submit" id="submit" class="btn btn-primary">Declare Result</button>
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
