<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/includes/config.php';

$username = trim((string) getenv('SRMS_ADMIN_USERNAME'));
$password = (string) getenv('SRMS_ADMIN_PASSWORD');
$role = strtolower(trim((string) getenv('SRMS_ADMIN_ROLE')));

if ($role === '') {
    $role = 'admin';
}
if (!preg_match('/^[A-Za-z0-9_.@-]{3,100}$/', $username)) {
    fwrite(STDERR, "Set SRMS_ADMIN_USERNAME to 3-100 letters, numbers, dots, underscores, @ or hyphens.\n");
    exit(1);
}
if (strlen($password) < 12) {
    fwrite(STDERR, "Set SRMS_ADMIN_PASSWORD to at least 12 characters.\n");
    exit(1);
}
if (!in_array($role, ['admin', 'super_admin'], true)) {
    fwrite(STDERR, "SRMS_ADMIN_ROLE must be either admin or super_admin.\n");
    exit(1);
}

try {
    $requiredColumns = $dbh->query(
        "SELECT COLUMN_NAME FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admin'"
    )->fetchAll(PDO::FETCH_COLUMN);

    foreach (['Role', 'IsActive', 'CreatedAt'] as $column) {
        if (!in_array($column, $requiredColumns, true)) {
            fwrite(STDERR, "Phase 13 is not installed. Import phase13-super-admin.sql before creating an account.\n");
            exit(1);
        }
    }

    $studentColumns = $dbh->query(
        "SELECT COLUMN_NAME FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblstudents'"
    )->fetchAll(PDO::FETCH_COLUMN);
    $hasStudentUsername = in_array('StudentUsername', $studentColumns, true);

    $checkSql = 'SELECT 1 FROM admin WHERE UserName = :admin_username
                 UNION SELECT 1 FROM teachers WHERE username = :teacher_username';
    $checkParams = [
        ':admin_username' => $username,
        ':teacher_username' => $username,
    ];
    if ($hasStudentUsername) {
        $checkSql .= ' UNION SELECT 1 FROM tblstudents WHERE StudentUsername = :student_username';
        $checkParams[':student_username'] = $username;
    }
    $checkSql .= ' LIMIT 1';

    $check = $dbh->prepare($checkSql);
    $check->execute($checkParams);
    if ($check->fetchColumn()) {
        fwrite(STDERR, "The username is already in use.\n");
        exit(1);
    }

    $insert = $dbh->prepare(
        'INSERT INTO admin
            (UserName, Password, updationDate, Role, IsActive, CreatedAt)
         VALUES
            (:username, :password, NOW(), :role, 1, NOW())'
    );
    $insert->execute([
        ':username' => $username,
        ':password' => password_hash($password, PASSWORD_DEFAULT),
        ':role' => $role,
    ]);

    fwrite(STDOUT, "Created {$role} account '{$username}'.\n");
} catch (Throwable $e) {
    error_log('SRMS administrator provisioning failed: ' . $e->getMessage());
    fwrite(STDERR, "Administrator provisioning failed. Check the database migration and application logs.\n");
    exit(1);
}
