
<?php
require_once 'includes/config.php'; require_once 'includes/phase1.php'; require_once 'includes/guardian_notifications.php';
require_admin();

// Initialize page feedback variables before rendering.
$msg = null;
$error = null;

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['toggle_publish'])) {
    verify_csrf();
    if(!srms_consume_operation($dbh,'result.publish.toggle')) { header('Location: manage-results.php'); exit; }
    $sid=(int)$_POST['student_id']; $cid=(int)$_POST['class_id']; $year=trim($_POST['academic_year']); $term=trim($_POST['term']??'Final'); $exam=trim($_POST['exam_type']); $published=(int)$_POST['published'];
    period_ensure($dbh,$year,$term,$exam);
    // Avoid reusing named PDO parameters in the same prepared statement (HY093 with native prepares).
    $newPublished = $published ? 0 : 1;
    $publishedBy = $newPublished ? (int)$_SESSION['admin_id'] : null;
    $publishedAt = $newPublished ? date('Y-m-d H:i:s') : null;
    $q=$dbh->prepare("UPDATE tblresult SET Published=:new_published, published_by=:published_by, published_at=:published_at, UpdationDate=NOW() WHERE StudentId=:student_id AND ClassId=:class_id AND AcademicYear=:academic_year AND Term=:term AND ExamType=:exam_type AND approval_state='approved' AND result_status='approved'");
    $q->execute([':new_published'=>$newPublished,':published_by'=>$publishedBy,':published_at'=>$publishedAt,':student_id'=>$sid,':class_id'=>$cid,':academic_year'=>$year,':term'=>$term,':exam_type'=>$exam]);
    audit_log($dbh,'RESULT_PUBLISH',($published?'Unpublished':'Published').' result for student '.$sid.' / '.$year.' / '.$exam);
    if ($newPublished && $q->rowCount()>0) { try { srms_queue_published_result_notifications($dbh,$sid,$year,$term,$exam); } catch(Throwable $notifyError) { error_log('SRMS guardian notification queue error: '.$notifyError->getMessage()); } }
    header('Location: manage-results.php'); exit;
}


?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
    	<meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Admin Manage Students</title>
        <link rel="stylesheet" href="css/bootstrap.min.css" media="screen" >
        <link rel="stylesheet" href="css/font-awesome.min.css" media="screen" >
        <link rel="stylesheet" href="css/animate-css/animate.min.css" media="screen" >
        <link rel="stylesheet" href="css/lobipanel/lobipanel.min.css" media="screen" >
        <link rel="stylesheet" href="css/prism/prism.css" media="screen" > <!-- USED FOR DEMO HELP - YOU CAN REMOVE IT -->
        <link rel="stylesheet" type="text/css" href="js/DataTables/datatables.min.css"/>
        <link rel="stylesheet" href="css/main.css"><link rel="stylesheet" href="css/srms-modernization.css"><link rel="stylesheet" href="css/srms-responsive.css" media="screen" >
        <script src="js/modernizr/modernizr.min.js"></script>
          <style>
        .errorWrap {
    padding: 10px;
    margin: 0 0 20px 0;
    background: #fff;
    border-left: 4px solid #dd3d36;
    -webkit-box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);
    box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);
}
.succWrap{
    padding: 10px;
    margin: 0 0 20px 0;
    background: #fff;
    border-left: 4px solid #5cb85c;
    -webkit-box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);
    box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);
}
        </style>
    </head>
    <body class="top-navbar-fixed">
        <div class="main-wrapper">

            <!-- ========== TOP NAVBAR ========== -->
   <?php include('includes/topbar.php');?> 
            <!-- ========== WRAPPER FOR BOTH SIDEBARS & MAIN CONTENT ========== -->
            <div class="content-wrapper">
                <div class="content-container">
<?php include('includes/leftbar.php');?>  

                    <div class="main-page">
                        <div class="container-fluid">
                            <div class="row page-title-div">
                                <div class="col-md-6">
                                    <h2 class="title">Manage Results</h2>
                                
                                </div>
                                
                                <!-- /.col-md-6 text-right -->
                            </div>
                            <!-- /.row -->
                            <div class="row breadcrumb-div">
                                <div class="col-md-6">
                                    <ul class="breadcrumb">
            							<li><a href="dashboard.php"><i class="fa fa-home"></i> Home</a></li>
                                        <li> Results</li>
            							<li class="active">Manage Results</li>
            						</ul>
                                </div>
                             
                            </div>
                            <!-- /.row -->
                        </div>
                        <!-- /.container-fluid -->

                        <section class="section">
                            <div class="container-fluid">

                             

                                <div class="row">
                                    <div class="col-md-12">

                                        <div class="panel">
                                            <div class="panel-heading">
                                                <div class="panel-title">
                                                    <h5>View Students Result Info</h5>
                                                </div>
                                            </div>
<?php if($msg){?>
<div class="alert alert-success left-icon-alert" role="alert">
 <strong>Well done!</strong><?php echo htmlentities($msg); ?>
 </div><?php } 
else if($error){?>
    <div class="alert alert-danger left-icon-alert" role="alert">
                                            <strong>Oh snap!</strong> <?php echo htmlentities($error); ?>
                                        </div><?php } ?>
                                        
                                            <div class="panel-body p-20">

                                                <table id="example" class="display table table-striped table-bordered" cellspacing="0" width="100%">
                                                    <thead>
                                                        <tr>
                                                            <th>#</th>
                                                            <th>Student Name</th>
                                                            <th>Roll Id</th>
                                                            <th>Class</th>
                                                            <th>Academic Year</th>
                                                            <th>Examination</th>
                                                            <th>Approval</th><th>Published</th>
                                                            <th>Action</th>
                                                            <th>Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tfoot>
                                                        <tr>
                                                          <th>#</th>
                                                            <th>Student Name</th>
                                                            <th>Roll Id</th>
                                                            <th>Class</th>
                                                            <th>Academic Year</th>
                                                            <th>Examination</th>
                                                            <th>Approval</th><th>Published</th>
                                                            <th>Action</th>
                                                            <th>Action</th>
                                                        </tr>
                                                    </tfoot>
                                                    <tbody>
<?php $sql = "SELECT tblstudents.StudentName,tblstudents.RollId,tblstudents.RegDate,tblstudents.StudentId,tblstudents.Status,tblclasses.id AS ClassId,tblclasses.ClassName,tblclasses.Section,tblresult.AcademicYear,tblresult.Term,tblresult.ExamType,MAX(tblresult.Published) AS Published, CASE WHEN MIN(CASE WHEN tblresult.approval_state='approved' THEN 1 ELSE 0 END)=1 THEN 'approved' ELSE 'pending' END AS ApprovalState FROM tblresult JOIN tblstudents ON tblstudents.StudentId=tblresult.StudentId JOIN tblclasses ON tblclasses.id=tblresult.ClassId GROUP BY tblstudents.StudentId,tblclasses.id,tblresult.AcademicYear,tblresult.Term,tblresult.ExamType ORDER BY MAX(tblresult.PostingDate) DESC";
$query = $dbh->prepare($sql);
$query->execute();
$results=$query->fetchAll(PDO::FETCH_OBJ);
$cnt=1;
if($query->rowCount() > 0)
{
foreach($results as $result)
{   ?>
<tr>
 <td><?php echo htmlentities($cnt);?></td>
                                                            <td><?php echo htmlentities($result->StudentName);?></td>
                                                            <td><?php echo htmlentities($result->RollId);?></td>
                                                            <td><?php echo htmlentities($result->ClassName);?>(<?php echo htmlentities($result->Section);?>)</td>
                                                            <td><?php echo htmlentities($result->AcademicYear);?></td>
<td><?php echo htmlentities($result->ExamType);?></td>
<td><span class="label label-<?php echo $result->ApprovalState==='approved'?'success':'warning'; ?>"><?php echo e(result_status_label($result->ApprovalState)); ?></span></td><td><span class="label label-<?php echo $result->Published?'success':'warning'; ?>"><?php echo $result->Published?'Published':'Not Published'; ?></span></td>
<td>
<a href="edit-result.php?stid=<?php echo htmlentities($result->StudentId);?>" class="btn btn-primary btn-xs">Edit</a>
<?php if($result->ApprovalState==='approved'): ?><form method="post" style="display:inline"><?php echo csrf_field(); ?><?php echo srms_operation_field('result.publish.toggle'); ?><input type="hidden" name="toggle_publish" value="1"><input type="hidden" name="student_id" value="<?php echo (int)$result->StudentId; ?>"><input type="hidden" name="class_id" value="<?php echo (int)$result->ClassId; ?>"><input type="hidden" name="academic_year" value="<?php echo e($result->AcademicYear); ?>"><input type="hidden" name="term" value="<?php echo e($result->Term ?? "Final"); ?>"><input type="hidden" name="exam_type" value="<?php echo e($result->ExamType); ?>"><input type="hidden" name="published" value="<?php echo (int)$result->Published; ?>"><button class="btn btn-<?php echo $result->Published?'warning':'success'; ?> btn-xs"><?php echo $result->Published?'Unpublish':'Publish'; ?></button></form><?php else: ?><span class="text-muted">Awaiting approval</span><?php endif; ?>
</td>
</tr>
<?php $cnt=$cnt+1;}} ?>
                                                       
                                                    
                                                    </tbody>
                                                </table>

                                         
                                                <!-- /.col-md-12 -->
                                            </div>
                                        </div>
                                    </div>
                                    <!-- /.col-md-6 -->

                                                               
                                                </div>
                                                <!-- /.col-md-12 -->
                                            </div>
                                        </div>
                                        <!-- /.panel -->
                                    </div>
                                    <!-- /.col-md-6 -->

                                </div>
                                <!-- /.row -->

                            </div>
                            <!-- /.container-fluid -->
                        </section>
                        <!-- /.section -->

                    </div>
                    <!-- /.main-page -->

                    

                </div>
                <!-- /.content-container -->
            </div>
            <!-- /.content-wrapper -->

        </div>
        <!-- /.main-wrapper -->

        <!-- ========== COMMON JS FILES ========== -->
        <script src="js/jquery/jquery-2.2.4.min.js"></script>
        <script src="js/bootstrap/bootstrap.min.js"></script>
        <script src="js/pace/pace.min.js"></script>
        <script src="js/lobipanel/lobipanel.min.js"></script>
        <script src="js/iscroll/iscroll.js"></script>

        <!-- ========== PAGE JS FILES ========== -->
        <script src="js/prism/prism.js"></script>
        <script src="js/DataTables/datatables.min.js"></script>

        <!-- ========== THEME JS ========== -->
        <script src="js/main.js"></script>
        <script>
            $(function($) {
                $('#example').DataTable();

                $('#example2').DataTable( {
                    "scrollY":        "300px",
                    "scrollCollapse": true,
                    "paging":         false
                } );

                $('#example3').DataTable();
            });
        </script>
    </body>
</html>
