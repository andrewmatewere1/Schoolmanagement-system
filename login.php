<?php
require_once 'includes/config.php';
if(!empty($_SESSION['admin_id'])){header('Location: dashboard.php');exit;}
if(!empty($_SESSION['teacher_id'])){header('Location: teacher-dashboard.php');exit;}
if(!empty($_SESSION['student_id'])){header('Location: student-dashboard.php');exit;}
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();
 $username=trim((string)($_POST['username']??''));$password=(string)($_POST['password']??'');
 if($username===''||$password==='')$error='Enter your username and password.';
 else{
  $rate=login_rate_limit_status($dbh,$username);
  if(!$rate['allowed']){$error='Too many failed attempts. Please wait 15 minutes and try again.';http_response_code(429);}
  else{
   $admin=null;$teacher=null;$student=null;
   try{$q=$dbh->prepare('SELECT id,UserName,Password,Role,IsActive FROM admin WHERE UserName=:u LIMIT 1');$q->execute([':u'=>$username]);$admin=$q->fetch()?:null;}catch(Throwable $ignored){}
   $validAdmin=false;$legacy=false;
   if($admin&&(int)($admin['IsActive']??1)===1){if(password_verify($password,$admin['Password']))$validAdmin=true;elseif(preg_match('/^[a-f0-9]{32}$/i',(string)$admin['Password'])&&hash_equals(strtolower($admin['Password']),md5($password))){$validAdmin=true;$legacy=true;}}
   if($validAdmin){session_regenerate_id(true);$_SESSION['alogin']=$admin['UserName'];$_SESSION['admin_id']=(int)$admin['id'];$_SESSION['admin_role']=(string)($admin['Role']??'admin');try{$q=$dbh->prepare('UPDATE admin SET LastLoginAt=NOW() WHERE id=?');$q->execute([$admin['id']]);if($legacy){$q=$dbh->prepare('UPDATE admin SET Password=? WHERE id=?');$q->execute([password_hash($password,PASSWORD_DEFAULT),$admin['id']]);}}catch(Throwable $ignored){}login_rate_limit_record($dbh,$username,true);login_rate_limit_clear($dbh,$username);header('Location: dashboard.php');exit;}
   try{$q=$dbh->prepare('SELECT id,username,full_name,password,status FROM teachers WHERE username=:u LIMIT 1');$q->execute([':u'=>$username]);$teacher=$q->fetch()?:null;}catch(Throwable $ignored){}
   if($teacher&&(int)$teacher['status']===1&&password_verify($password,$teacher['password'])){session_regenerate_id(true);$_SESSION['teacher_login']=1;$_SESSION['teacher_id']=(int)$teacher['id'];$_SESSION['teacher_username']=$teacher['username'];$_SESSION['teacher_name']=$teacher['full_name'];try{$q=$dbh->prepare('UPDATE teachers SET last_login=NOW() WHERE id=?');$q->execute([$teacher['id']]);}catch(Throwable $ignored){}login_rate_limit_record($dbh,$username,true);login_rate_limit_clear($dbh,$username);header('Location: teacher-dashboard.php');exit;}
   try{$q=$dbh->prepare('SELECT StudentId,StudentName,StudentUsername,StudentPassword,Status FROM tblstudents WHERE StudentUsername=:u LIMIT 1');$q->execute([':u'=>$username]);$student=$q->fetch()?:null;}catch(Throwable $ignored){}
   if($student&&(int)$student['Status']===1&&!empty($student['StudentPassword'])&&password_verify($password,$student['StudentPassword'])){session_regenerate_id(true);$_SESSION['student_id']=(int)$student['StudentId'];$_SESSION['student_username']=$student['StudentUsername'];$_SESSION['student_name']=$student['StudentName'];login_rate_limit_record($dbh,$username,true);login_rate_limit_clear($dbh,$username);header('Location: student-dashboard.php');exit;}
   login_rate_limit_record($dbh,$username,false);$error='Invalid username or password, or the account is suspended.';
  }
 }
}
$schoolName=srms_school_name($dbh);
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#173d70"><title>Sign in | <?php echo e($schoolName);?></title><link rel="stylesheet" href="css/bootstrap.min.css"><link rel="stylesheet" href="css/srms-landing.css"><style>body{background:linear-gradient(135deg,#eef4fb,#f8fbff);min-height:100vh}.login-shell{min-height:100vh;display:grid;place-items:center;padding:24px}.login-card{width:min(100%,460px);background:white;border:1px solid #e2eaf3;border-radius:22px;padding:clamp(24px,5vw,42px);box-shadow:0 24px 70px rgba(22,53,91,.13)}.login-logo{width:54px;height:54px;border-radius:16px;display:grid;place-items:center;background:#173d70;color:white;font-size:24px;margin-bottom:20px}.login-card h1{font-size:27px;font-weight:800;letter-spacing:-.5px;color:#102e55}.login-card .form-control{height:48px;border-radius:9px}.login-card .btn{width:100%;height:48px;border-radius:9px;font-weight:700}.login-hint{font-size:13px;color:#65758b}</style><link rel="stylesheet" href="css/srms-responsive.css" media="screen">
</head><body><main class="login-shell"><section class="login-card"><div class="login-logo"><span class="glyphicon glyphicon-education"></span></div><p class="eyebrow">SECURE SCHOOL PORTAL</p><h1>Welcome back</h1><p class="login-hint">Sign in with your unique username. Your account role opens the correct portal automatically.</p><?php if($error):?><div class="alert alert-danger"><?php echo e($error);?></div><?php endif;?><form method="post" autocomplete="on"><input type="hidden" name="csrf_token" value="<?php echo e(csrf_token());?>"><div class="form-group"><label for="username">Username</label><input id="username" name="username" class="form-control" autocomplete="username" required autofocus value="<?php echo e($_POST['username']??'');?>"></div><div class="form-group"><label for="password">Password</label><input id="password" name="password" type="password" class="form-control" autocomplete="current-password" required></div><button class="btn btn-primary">Sign in securely</button></form><p class="login-hint text-center" style="margin-top:20px">Teachers · Students · Administrators</p><p class="text-center"><a href="index.php">Return to school home</a></p></section></main></body></html>