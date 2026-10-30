<?php
// SRMS central configuration and security helpers.
// Phase 4: secure session defaults. Secure cookies are enabled automatically on HTTPS.
$isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') || ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443);
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', $isHttps ? '1' : '0');
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

// Phase 4: baseline browser security headers. The CSP permits the legacy inline UI
// already present in this project while still restricting resource origins to this site.
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
header("Content-Security-Policy: default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'; object-src 'none'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self' data:; connect-src 'self'; frame-src 'self' https://www.youtube.com https://www.youtube-nocookie.com;");
if ($isHttps) { header('Strict-Transport-Security: max-age=31536000; includeSubDomains'); }

$localConfig = __DIR__ . '/config.local.php';
if (is_file($localConfig)) require_once $localConfig;

define('DB_HOST', defined('DB_HOST') ? DB_HOST : 'localhost');
define('DB_USER', defined('DB_USER') ? DB_USER : 'root');
define('DB_PASS', defined('DB_PASS') ? DB_PASS : '');
define('DB_NAME', defined('DB_NAME') ? DB_NAME : 'srms');
define('DB_PORT', defined('DB_PORT') ? DB_PORT : 3306);
define('APP_NAME', 'Student Result Management System');
date_default_timezone_set(defined('APP_TIMEZONE') ? APP_TIMEZONE : 'Asia/Kolkata');
require_once __DIR__ . '/security.php';

try {
    $dbh = new PDO('mysql:host='.DB_HOST.';port='.DB_PORT.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES=>false,
    ]);
} catch (PDOException $e) {
    error_log('SRMS database error: '.$e->getMessage()); http_response_code(500);
    exit('Database connection failed. Check includes/config.local.php and make sure MySQL is running.');
}
function e($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function csrf_token(): string { if(empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32)); return $_SESSION['csrf_token']; }
function csrf_field(): string { return '<input type="hidden" name="csrf_token" value="'.e(csrf_token()).'">'; }
function verify_csrf(): void { $token=$_POST['csrf_token']??''; if(!$token||empty($_SESSION['csrf_token'])||!hash_equals($_SESSION['csrf_token'],$token)){http_response_code(403);exit('Invalid security token. Please refresh the page and try again.');} }
function srms_operation_field(string $action): string {
    $token=bin2hex(random_bytes(24));
    return '<input type="hidden" name="srms_operation" value="'.e($token).'"><input type="hidden" name="srms_operation_action" value="'.e($action).'">';
}
function srms_consume_operation(PDO $dbh,string $action): bool {
    $token=trim((string)($_POST['srms_operation']??''));
    if($token===''||!preg_match('/^[a-f0-9]{48}$/i',$token)) return false;
    $postedAction=trim((string)($_POST['srms_operation_action']??''));
    if(!hash_equals($action,$postedAction)) return false;
    $principal=(string)($_SESSION['admin_id']??$_SESSION['teacher_id']??$_SESSION['student_id']??'0');
    $principalType=!empty($_SESSION['admin_id'])?'admin':(!empty($_SESSION['teacher_id'])?'teacher':(!empty($_SESSION['student_id'])?'student':'anonymous'));
    $key=hash('sha256',$principalType.'|'.$principal.'|'.$action.'|'.$token);
    try {
        $dbh->prepare('DELETE FROM srms_operation_receipts WHERE completed_at < DATE_SUB(NOW(),INTERVAL 1 DAY)')->execute();
        $q=$dbh->prepare('INSERT INTO srms_operation_receipts(operation_key,principal_type,principal_id,action_name,completed_at) VALUES(:key,:type,:id,:action,NOW())');
        $q->execute([':key'=>$key,':type'=>$principalType,':id'=>(int)$principal,':action'=>$action]);
        return true;
    } catch(PDOException $e) {
        if((int)($e->errorInfo[1]??0)===1062) return false;
        throw $e;
    }
}
function srms_school_name(PDO $connection = null): string {
    if (!$connection) { global $dbh; $connection=$dbh ?? null; }
    try { if ($connection instanceof PDO) { $q=$connection->prepare("SELECT setting_value FROM srms_system_settings WHERE setting_key='school_name'"); $q->execute(); $v=trim((string)$q->fetchColumn()); if($v!=='') return $v; } } catch(Throwable $ignored) {}
    return 'Student Result Management System';
}
function srms_module_for_page(): ?string {
    $map = [
      'dashboard.php'=>'dashboard','create-class.php'=>'student_class','manage-classes.php'=>'student_class','edit-class.php'=>'student_class',
      'add-students.php'=>'students','manage-students.php'=>'students','edit-student.php'=>'students','export-students.php'=>'students',
      'create-subject.php'=>'subjects','manage-subjects.php'=>'subjects','edit-subject.php'=>'subjects','add-subjectcombination.php'=>'subjects','manage-subjectcombination.php'=>'subjects',
      'add-teacher.php'=>'teachers','manage-teachers.php'=>'teachers','edit-teacher.php'=>'teachers','teacher-assignments.php'=>'teachers','teacher-workload.php'=>'teachers',
      'add-result.php'=>'results','manage-results.php'=>'results','edit-result.php'=>'results','find-result.php'=>'results','result-approvals.php'=>'results','result-history.php'=>'results','result.php'=>'results','report-card.php'=>'results','class-performance.php'=>'results','manage-grading.php'=>'results',
      'timetable.php'=>'timetable_exams','student-lifecycle.php'=>'student_lifecycle','finance-accounting.php'=>'finance_accounting','fee-configurations.php'=>'finance_accounting','fee-reports.php'=>'finance_accounting','fee-student-workspace.php'=>'finance_accounting','manage-fees.php'=>'finance_accounting','fee-payment-receipt.php'=>'finance_accounting',
      'add-notice.php'=>'notices','edit-notice.php'=>'notices','manage-notices.php'=>'notices','notice-details.php'=>'notices',
      'teacher-leave-management.php'=>'management','teacher-leave-settings.php'=>'management','teacher-leave-report.php'=>'management','audit-logs.php'=>'management','notifications.php'=>'management','notification-delivery.php'=>'management','operations.php'=>'management','academic-periods.php'=>'management','academic-archive.php'=>'management','guardian-accounts.php'=>'management','super-admin.php'=>'super_admin'
    ];
    return $map[basename((string)($_SERVER['SCRIPT_NAME']??''))] ?? null;
}
function srms_admin_has_permission(PDO $dbh, int $adminId, string $module, bool $manage = false): bool {
    if (is_super_admin()) return true;
    try {
        $q=$dbh->prepare('SELECT can_view,can_manage FROM srms_admin_permissions WHERE admin_id=:id AND module_key=:m');
        $q->execute([':id'=>$adminId,':m'=>$module]); $r=$q->fetch();
        if($r) return (int)$r[$manage?'can_manage':'can_view']===1;
        // Before phase14 is installed, preserve legacy access. After installation,
        // accounts without explicit assignments have no access except dashboard.
        $exists=(int)$dbh->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='srms_admin_permissions'")->fetchColumn();
        return $exists===0 || $module==='dashboard';
    } catch(Throwable $ignored) { return true; }
}
function require_admin_manage(): void { require_admin(); $m=srms_module_for_page(); global $dbh; if($m && !srms_admin_has_permission($dbh,(int)$_SESSION['admin_id'],$m,true)){http_response_code(403);exit('Access denied. Your administrator role does not allow managing this module.');} }
function require_admin(): void {
    if(empty($_SESSION['alogin']) || empty($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
    global $dbh;
    $module=srms_module_for_page();
    if($module && !is_super_admin()) {
        $manage = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
        if(!srms_admin_has_permission($dbh,(int)$_SESSION['admin_id'],$module,$manage)){http_response_code(403);exit('Access denied. Ask the Super Admin to assign the required view/manage permission.');}
    }
}
function current_admin_role(): string { return (string)($_SESSION['admin_role'] ?? 'admin'); }
function is_super_admin(): bool { return !empty($_SESSION['admin_id']) && current_admin_role() === 'super_admin'; }
function require_super_admin(): void { require_admin(); if (!is_super_admin()) { http_response_code(403); exit('Access denied. Super Admin privileges are required.'); } }
function require_teacher(): void { if(empty($_SESSION['teacher_login']) || empty($_SESSION['teacher_id'])) { header('Location: teacher-login.php'); exit; } }
function require_admin_or_teacher(): void { if(empty($_SESSION['alogin']) && empty($_SESSION['teacher_login'])) { header('Location: admin-login.php'); exit; } }
function current_teacher_id(): int { return (int)($_SESSION['teacher_id']??0); }
function teacher_has_class(PDO $dbh,int $teacherId,int $classId): bool { $q=$dbh->prepare('SELECT 1 FROM teacher_class_assignments WHERE teacher_id=:t AND class_id=:c LIMIT 1');$q->execute([':t'=>$teacherId,':c'=>$classId]);return (bool)$q->fetchColumn(); }
function teacher_has_student(PDO $dbh,int $teacherId,int $studentId,int $classId): bool { $q=$dbh->prepare('SELECT 1 FROM teacher_student_assignments WHERE teacher_id=:t AND student_id=:s AND class_id=:c LIMIT 1');$q->execute([':t'=>$teacherId,':s'=>$studentId,':c'=>$classId]);return (bool)$q->fetchColumn(); }
function teacher_can_grade(PDO $dbh,int $teacherId,int $studentId,int $classId): bool { return teacher_has_class($dbh,$teacherId,$classId) && teacher_has_student($dbh,$teacherId,$studentId,$classId); }
function audit_log(PDO $dbh,string $action,string $details=''): void { try{$u=$_SESSION['alogin']??(($_SESSION['teacher_username']??'teacher'));$q=$dbh->prepare('INSERT INTO admin_activity_logs(username,action,details,ip_address,created_at) VALUES(:u,:a,:d,:ip,NOW())');$q->execute([':u'=>$u,':a'=>$action,':d'=>$details,':ip'=>$_SERVER['REMOTE_ADDR']??'unknown']);}catch(Throwable $e){error_log('SRMS audit error: '.$e->getMessage());} }
function grade_for(float $percentage): array {
    // Use school-configured grading bands when the Phase 6 migration is installed.
    try { global $dbh; if(isset($dbh) && $dbh instanceof PDO) { $q=$dbh->prepare('SELECT grade,remark FROM grading_bands WHERE :p>=min_percentage AND :p2<=max_percentage ORDER BY sort_order LIMIT 1'); $q->execute([':p'=>$percentage,':p2'=>$percentage]); $r=$q->fetch(); if($r) return [(string)$r['grade'],(string)$r['remark']]; } } catch(Throwable $ignored) {}
    if($percentage>=80)return['A+','Excellent'];if($percentage>=70)return['A','Very Good'];if($percentage>=60)return['B','Good'];if($percentage>=50)return['C','Satisfactory'];if($percentage>=40)return['D','Pass'];return['F','Fail'];
}
function result_status_label(string $status): string { return ['draft'=>'Draft','submitted'=>'Under Review','rework'=>'Returned for Rework','approved'=>'Approved','pending'=>'Under Review','rejected'=>'Rejected','published'=>'Published','locked'=>'Locked'][$status] ?? ucfirst(str_replace('_',' ',$status)); }
if(($_SERVER['REQUEST_METHOD'] ?? 'GET')==='POST' && (isset($_SESSION['alogin']) || isset($_SESSION['teacher_login'])) && basename($_SERVER['SCRIPT_NAME'] ?? '')!=='get_student.php') verify_csrf();
