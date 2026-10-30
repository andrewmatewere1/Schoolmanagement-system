<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only\n");
}

require_once dirname(__DIR__) . '/includes/config.php';

$jobName = 'retention';
$job = $dbh->prepare("INSERT INTO srms_job_runs(job_name,status,started_at) VALUES(:name,'running',NOW())");
$job->execute([':name' => $jobName]);
$runId = (int)$dbh->lastInsertId();
$processed = 0;
$deleted = 0;

try {
    $tables = [
        ['security_login_attempts', 'attempted_at', 2],
        ['srms_operation_receipts', 'completed_at', 1],
    ];
    foreach ($tables as [$table, $column, $days]) {
        $q = $dbh->prepare("DELETE FROM {$table} WHERE {$column} < DATE_SUB(NOW(), INTERVAL {$days} DAY)");
        $q->execute();
        $count = $q->rowCount();
        $processed++;
        $deleted += $count;
    }
    $q = $dbh->prepare("DELETE FROM notification_queue WHERE status IN ('sent','skipped') AND created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)");
    $q->execute();
    $deleted += $q->rowCount();
    $processed++;

    $q = $dbh->prepare('UPDATE srms_job_runs SET status=:status,finished_at=NOW(),processed_count=:processed,success_count=:success,failure_count=0,message=:message WHERE id=:id');
    $q->execute([
        ':status' => 'success',
        ':processed' => $processed,
        ':success' => $deleted,
        ':message' => $deleted . ' expired operational rows deleted',
        ':id' => $runId,
    ]);
    echo "Retention complete. Deleted {$deleted} expired rows.\n";
} catch (Throwable $e) {
    $q = $dbh->prepare("UPDATE srms_job_runs SET status='failed',finished_at=NOW(),processed_count=:processed,failure_count=1,message=:message WHERE id=:id");
    $q->execute([':processed' => $processed, ':message' => substr($e->getMessage(), 0, 1000), ':id' => $runId]);
    fwrite(STDERR, 'Retention failed: ' . $e->getMessage() . "\n");
    exit(1);
}
