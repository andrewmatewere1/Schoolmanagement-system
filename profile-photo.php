<?php
require_once __DIR__.'/includes/config.php';
if(empty($_SESSION['alogin']) && empty($_SESSION['teacher_login']) && empty($_SESSION['student_id'])){http_response_code(404);exit;}
$type=$_GET['type']??'';$id=(int)($_GET['id']??0);if(!in_array($type,['student','teacher','admin'],true)||$id<1){http_response_code(404);exit;}
if($type==='admin' && (empty($_SESSION['admin_id']) || $id!==(int)$_SESSION['admin_id'])){http_response_code(404);exit;}
if($type==='student' && !empty($_SESSION['student_id']) && $id!==(int)$_SESSION['student_id']){http_response_code(404);exit;}
if(!empty($_SESSION['teacher_login'])){if($type==='teacher' && $id!==(int)($_SESSION['teacher_id']??0)){http_response_code(404);exit;}if($type==='student'){try{$q=$dbh->prepare('SELECT ClassId FROM tblstudents WHERE StudentId=?');$q->execute([$id]);$classId=(int)$q->fetchColumn();if(!$classId||!teacher_has_class($dbh,(int)$_SESSION['teacher_id'],$classId)){http_response_code(404);exit;}}catch(Throwable $ignore){http_response_code(404);exit;}}}
try{$q=$dbh->prepare('SELECT stored_filename,mime_type FROM srms_profile_photos WHERE person_type=? AND person_id=?');$q->execute([$type,$id]);$row=$q->fetch();if(!$row){http_response_code(404);exit;}$path=__DIR__.'/uploads/profiles/'.$row['stored_filename'];if(!is_file($path)){http_response_code(404);exit;}header('Content-Type: '.$row['mime_type']);header('X-Content-Type-Options: nosniff');header('Cache-Control: private, max-age=3600');header('Content-Length: '.filesize($path));readfile($path);}catch(Throwable $e){http_response_code(404);}
