<?php
require_once 'includes/config.php';
require_admin();
$msg=''; $error='';
$id=(int)($_GET['id']??$_POST['id']??0);
$q=$dbh->prepare('SELECT * FROM tblnotice WHERE id=:id LIMIT 1');
$q->execute([':id'=>$id]);
$notice=$q->fetch(PDO::FETCH_ASSOC);
if(!$notice){ http_response_code(404); exit('Notice not found.'); }
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    $title=trim($_POST['noticetitle']??'');
    $details=trim($_POST['noticedetails']??'');
    $published=(int)($_POST['published']??1) ? 1 : 0;
    if($title==='' || $details==='') $error='Notice title and details are required.';
    else {
        try {
            $u=$dbh->prepare('UPDATE tblnotice SET noticeTitle=:title, noticeDetails=:details, Published=:published WHERE id=:id');
            $u->execute([':title'=>$title,':details'=>$details,':published'=>$published,':id'=>$id]);
            audit_log($dbh,'NOTICE_UPDATE','Updated notice ID '.$id);
            $msg='Notice updated successfully.';
            $q->execute([':id'=>$id]); $notice=$q->fetch(PDO::FETCH_ASSOC);
        } catch(Throwable $e) { $error='Unable to update notice.'; error_log($e->getMessage()); }
    }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Edit Notice | SRMS</title><link rel="stylesheet" href="css/bootstrap.min.css"><link rel="stylesheet" href="css/font-awesome.min.css"><link rel="stylesheet" href="css/main.css"><link rel="stylesheet" href="css/srms-modernization.css"><link rel="stylesheet" href="css/srms-responsive.css"></head><body class="top-navbar-fixed"><div class="main-wrapper"><?php include 'includes/topbar.php';?><div class="content-wrapper"><div class="content-container"><?php include 'includes/leftbar.php';?><main class="main-page"><div class="container-fluid"><div class="row page-title-div"><div class="col-md-12"><h2 class="title">Edit Notice</h2></div></div><div class="panel"><div class="panel-heading"><h4>Edit Notice</h4></div><div class="panel-body"><?php if($msg):?><div class="alert alert-success"><?php echo e($msg);?></div><?php endif;?><?php if($error):?><div class="alert alert-danger"><?php echo e($error);?></div><?php endif;?><form method="post"><?php echo csrf_field();?><input type="hidden" name="id" value="<?php echo $id;?>"><div class="form-group"><label>Notice Title</label><input class="form-control" name="noticetitle" maxlength="255" required value="<?php echo e($notice['noticeTitle']);?>"></div><div class="form-group"><label>Notice Details</label><textarea class="form-control" name="noticedetails" rows="8" required><?php echo e($notice['noticeDetails']);?></textarea></div><div class="checkbox"><label><input type="checkbox" name="published" value="1" <?php echo !empty($notice['Published'])?'checked':'';?>> Published / visible on public homepage</label></div><button class="btn btn-primary">Update Notice</button> <a href="manage-notices.php" class="btn btn-default">Cancel</a></form></div></div></div></main></div></div></div></div><script src="js/jquery/jquery-2.2.4.min.js"></script><script src="js/bootstrap/bootstrap.min.js"></script></body></html>