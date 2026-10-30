<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only\n");
}

require_once dirname(__DIR__) . '/includes/config.php';

$failures = [];
$checks = 0;
$baseUrl = rtrim(getenv('SRMS_TEST_BASE_URL') ?: 'http://localhost:8080/srms', '/');

function check(bool $condition, string $message, array &$failures, int &$checks): void
{
    $checks++;
    if (!$condition) {
        $failures[] = $message;
    }
}

function http_check(string $url): array
{
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 5,
            'ignore_errors' => true,
            'follow_location' => false,
        ],
    ]);
    $body = @file_get_contents($url, false, $context);
    $headers = $http_response_header ?? [];
    $status = 0;
    foreach ($headers as $header) {
        if (preg_match('/^HTTP\/\S+\s+(\d{3})/', $header, $match)) {
            $status = (int)$match[1];
        }
    }
    return [$status, implode("\n", $headers), $body === false ? '' : $body];
}

$requiredTables = [
    'admin',
    'srms_schema_migrations',
    'security_login_attempts',
    'srms_profile_photos',
    'srms_timetable',
    'srms_examinations',
    'srms_student_lifecycle',
    'student_term_fees',
    'student_fee_payments',
    'grading_bands',
    'srms_finance_entries',
    'srms_budgets',
    'notification_queue',
    'tblstudents',
    'tblclasses',
    'tblsubjects',
];

foreach ($requiredTables as $table) {
    $q = $dbh->prepare(
        'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=:table_name'
    );
    $q->execute([':table_name' => $table]);
    check((int)$q->fetchColumn() === 1, 'Missing required table: ' . $table, $failures, $checks);
}

foreach (['015-phase15-hardening.sql'] as $migration) {
    $q = $dbh->prepare('SELECT COUNT(*) FROM srms_schema_migrations WHERE migration_name=:name');
    $q->execute([':name' => $migration]);
    check((int)$q->fetchColumn() === 1, 'Migration not applied: ' . $migration, $failures, $checks);
}

[$loginStatus, $loginHeaders, $loginBody] = http_check($baseUrl . '/login.php');
check($loginStatus === 200, 'Login page should return HTTP 200, got ' . $loginStatus, $failures, $checks);
check(stripos($loginHeaders, 'X-Content-Type-Options: nosniff') !== false, 'Login response missing X-Content-Type-Options', $failures, $checks);
check(stripos($loginHeaders, 'Content-Security-Policy:') !== false, 'Login response missing Content-Security-Policy', $failures, $checks);
check(stripos($loginBody, 'name="csrf_token"') !== false, 'Login form is missing its CSRF token', $failures, $checks);

[$dashboardStatus, $dashboardHeaders] = http_check($baseUrl . '/dashboard.php');
check($dashboardStatus === 302, 'Unauthenticated dashboard should redirect, got HTTP ' . $dashboardStatus, $failures, $checks);
check(stripos($dashboardHeaders, 'Location: login.php') !== false, 'Dashboard did not redirect to login.php', $failures, $checks);

$protectedRoutes = [
    'manage-students.php',
    'manage-teachers.php',
    'manage-results.php',
    'manage-fees.php',
    'manage-grading.php',
    'timetable.php',
    'student-lifecycle.php',
    'finance-accounting.php',
    'teacher-assignments.php',
    'teacher-leave-management.php',
    'result-approvals.php',
    'result-history.php',
    'edit-notice.php',
];

foreach ($protectedRoutes as $route) {
    [$status, $headers] = http_check($baseUrl . '/' . $route);
    check($status === 302, $route . ' should require authentication; got HTTP ' . $status, $failures, $checks);
    check(stripos($headers, 'Location: login.php') !== false, $route . ' did not redirect to login.php', $failures, $checks);
}

$navigationRoutes = [
    'teacher-assignments.php',
    'teacher-leave-management.php',
    'timetable.php',
    'student-lifecycle.php',
    'finance-accounting.php',
    'academic-periods.php',
    'result-approvals.php',
    'result-history.php',
    'edit-notice.php',
];

$root = dirname(__DIR__);
foreach ($navigationRoutes as $route) {
    $path = $root . DIRECTORY_SEPARATOR . $route;
    $source = is_file($path) ? file_get_contents($path) : '';
    check(
        str_contains($source, "includes/topbar.php") && str_contains($source, "includes/leftbar.php"),
        $route . ' is missing the shared topbar/sidebar navigation',
        $failures,
        $checks
    );
}

echo 'Integration checks run: ' . $checks . PHP_EOL;
if ($failures) {
    echo "FAILURES:\n- " . implode("\n- ", $failures) . PHP_EOL;
    exit(1);
}
echo "PASS: Phase 17 integration checks passed.\n";
