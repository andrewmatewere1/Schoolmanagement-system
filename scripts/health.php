<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only\n");
}

require_once dirname(__DIR__) . '/includes/config.php';

$failures = [];
$warnings = [];
$checks = [
    'database' => $dbh->query('SELECT 1')->fetchColumn() === 1 ? 'ok' : 'failed',
];

$migrationDir = dirname(__DIR__) . '/migrations';
$migrationFiles = glob($migrationDir . '/*.sql');
sort($migrationFiles, SORT_STRING);

foreach ($migrationFiles as $file) {
    $name = basename($file);
    $checksum = hash('sha256', file_get_contents($file));
    $q = $dbh->prepare('SELECT checksum FROM srms_schema_migrations WHERE migration_name=:name');
    $q->execute([':name' => $name]);
    $stored = $q->fetchColumn();
    if ($stored === false) {
        $warnings[] = 'Migration pending: ' . $name;
    } elseif (!hash_equals((string)$stored, $checksum)) {
        $failures[] = 'Migration changed after application: ' . $name;
    }
}

$tables = ['security_login_attempts', 'notification_queue', 'srms_operation_receipts', 'srms_schema_migrations'];
foreach ($tables as $table) {
    $q = $dbh->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=:table");
    $q->execute([':table' => $table]);
    if ((int)$q->fetchColumn() !== 1) {
        $failures[] = 'Required table missing: ' . $table;
    }
}

try {
    $queued = (int)$dbh->query("SELECT COUNT(*) FROM notification_queue WHERE status='queued'")->fetchColumn();
    $failed = (int)$dbh->query("SELECT COUNT(*) FROM notification_queue WHERE status='failed' AND attempts>=3")->fetchColumn();
    $checks['notifications'] = ['queued' => $queued, 'terminal_failures' => $failed];
    if ($failed > 0) {
        $warnings[] = $failed . ' notification(s) require attention';
    }
} catch (Throwable $e) {
    $checks['notifications'] = 'table unavailable';
}

echo json_encode(['status' => $failures ? 'critical' : ($warnings ? 'warning' : 'ok'), 'checks' => $checks, 'warnings' => $warnings, 'failures' => $failures], JSON_PRETTY_PRINT) . PHP_EOL;
exit($failures ? 1 : 0);
