<?php
require_once 'includes/config.php'; require_admin();
$msg = null;
$error = null;
$msg=''; $error='';
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['submit'])) {
    $current=$_POST['password']??''; $new=$_POST['newpassword']??''; $confirm=$_POST['confirmpassword']??'';
    if (strlen($new)<8) $error='New password must be at least 8 characters.';
    elseif ($new!==$confirm) $error='New password and confirmation do not match.';
    else {
        $stmt=$dbh->prepare('SELECT id,Password FROM admin WHERE UserName=:u LIMIT 1'); $stmt->execute([':u'=>$_SESSION['alogin']]); $a=$stmt->fetch();
        $valid=$a && (password_verify($current,$a['Password']) || (preg_match('/^[a-f0-9]{32}$/i',$a['Password']) && hash_equals(strtolower($a['Password']),md5($current))));
        if (!$valid) $error='Your current password is incorrect.';
        else { $hash=password_hash($new,PASSWORD_DEFAULT); $q=$dbh->prepare('UPDATE admin SET Password=:p,updationDate=NOW() WHERE id=:id'); $q->execute([':p'=>$hash,':id'=>$a['id']]); audit_log($dbh,'PASSWORD_CHANGE','Administrator changed password'); $msg='Password changed successfully.'; }
    }
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Change Password | SRMS</title><link rel="stylesheet" href="css/bootstrap.min.css"><link rel="stylesheet" href="css/font-awesome.min.css"><link rel="stylesheet" href="css/main.css"><link rel="stylesheet" href="css/srms-modernization.css"><link rel="stylesheet" href="css/srms-responsive.css"></head><body class="top-navbar-fixed"><div class="main-wrapper"><?php include 'includes/topbar.php';?><div class="content-wrapper"><div class="content-container"><?php include 'includes/leftbar.php';?><div class="main-page"><div class="container-fluid"><h2 class="title">Admin Change Password</h2><section class="section"><div class="row"><div class="col-md-8 col-md-offset-2"><div class="panel"><div class="panel-heading"><h5>Change Password</h5></div><div class="panel-body">
<?php if($msg): ?><div class="alert alert-success"><?php echo e($msg);?></div><?php endif;?><?php if($error): ?><div class="alert alert-danger"><?php echo e($error);?></div><?php endif;?>
<form method="post" autocomplete="off"><?php echo csrf_field(); ?><div class="form-group"><label>Current Password</label><input type="password" name="password" class="form-control" required></div><div class="form-group"><label>New Password</label><input type="password" name="newpassword" class="form-control" required minlength="8"></div><div class="form-group"><label>Confirm Password</label><input type="password" name="confirmpassword" class="form-control" required minlength="8"></div><button name="submit" class="btn btn-primary">Change Password</button></form>
</div></div></div></div></section></div></div></div></div><script src="js/jquery/jquery-2.2.4.min.js"></script><script src="js/bootstrap/bootstrap.min.js"></script><script src="js/main.js"></script></body></html>
