<?php
require_once 'includes/config.php';
require_super_admin();

$migrationRows = [];
$migrationFiles = glob(__DIR__ . '/migrations/*.sql');
sort($migrationFiles, SORT_STRING);
foreach ($migrationFiles as $file) {
    $name = basename($file);
    $checksum = hash('sha256', file_get_contents($file));
    $q = $dbh->prepare('SELECT checksum,applied_at FROM srms_schema_migrations WHERE migration_name=:name');
    $q->execute([':name' => $name]);
    $row = $q->fetch();
    $migrationRows[] = [
        'name' => $name,
        'status' => !$row ? 'Pending' : (!$row['checksum'] || !hash_equals((string)$row['checksum'], $checksum) ? 'Changed' : 'Applied'),
        'applied_at' => $row['applied_at'] ?? '',
    ];
}

$jobs = $dbh->query('SELECT * FROM srms_job_runs ORDER BY started_at DESC,id DESC LIMIT 20')->fetchAll();
$backups = [];
$backupDir = __DIR__ . '/storage/backups';
if (is_dir($backupDir)) {
    foreach (glob($backupDir . '/*.sql') ?: [] as $file) {
        $backups[] = ['name' => basename($file), 'size' => filesize($file), 'date' => date('Y-m-d H:i:s', filemtime($file))];
    }
    usort($backups, fn($a, $b) => strcmp($b['date'], $a['date']));
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Operations | SRMS</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/font-awesome.min.css">
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/srms-modernization.css">
    <link rel="stylesheet" href="css/srms-responsive.css">
</head>
<body class="top-navbar-fixed">
<div class="main-wrapper">
<?php include 'includes/topbar.php'; ?>
<div class="content-wrapper"><div class="content-container">
<?php include 'includes/leftbar.php'; ?>
<main class="main-page"><div class="container-fluid">
    <h2>Operations Health</h2>
    <p>Use the PHP CLI scripts below with Windows Task Scheduler for production monitoring, backups, and retention.</p>
    <p><a class="btn btn-warning" href="deploy-infinityfree.php">Open InfinityFree Migration Runner</a> <small>Use only during hosting migration, then remove the runner file.</small></p>
    <div class="panel panel-default"><div class="panel-heading"><strong>Migration health</strong></div><div class="panel-body table-responsive">
        <table class="table table-bordered"><thead><tr><th>Migration</th><th>Status</th><th>Applied</th></tr></thead><tbody>
        <?php foreach ($migrationRows as $row): ?>
        <tr><td><?=e($row['name'])?></td><td><?=e($row['status'])?></td><td><?=e($row['applied_at']?:'—')?></td></tr>
        <?php endforeach; ?>
        </tbody></table>
        <code>php scripts\migrate.php status</code>
        <code style="margin-left:10px">php scripts\health.php</code>
    </div></div>
    <div class="panel panel-default"><div class="panel-heading"><strong>Recent jobs</strong></div><div class="panel-body table-responsive">
        <table class="table table-bordered"><thead><tr><th>Job</th><th>Status</th><th>Started</th><th>Finished</th><th>Success</th><th>Failed</th><th>Message</th></tr></thead><tbody>
        <?php foreach ($jobs as $job): ?><tr><td><?=e($job['job_name'])?></td><td><?=e($job['status'])?></td><td><?=e($job['started_at'])?></td><td><?=e($job['finished_at']?:'—')?></td><td><?=e((string)$job['success_count'])?></td><td><?=e((string)$job['failure_count'])?></td><td><?=e($job['message']?:'')?></td></tr><?php endforeach;?>
        <?php if (!$jobs): ?><tr><td colspan="7">No recorded jobs yet.</td></tr><?php endif;?>
        </tbody></table>
        <code>php scripts\retention.php</code>
    </div></div>
    <div class="panel panel-default"><div class="panel-heading"><strong>Database backups</strong></div><div class="panel-body">
        <code>php scripts\backup-db.php</code>
        <div class="table-responsive" style="margin-top:12px"><table class="table table-bordered"><thead><tr><th>File</th><th>Date</th><th>Size</th></tr></thead><tbody>
        <?php foreach ($backups as $backup): ?><tr><td><?=e($backup['name'])?></td><td><?=e($backup['date'])?></td><td><?=number_format($backup['size']/1024,1)?> KB</td></tr><?php endforeach;?>
        <?php if (!$backups): ?><tr><td colspan="3">No local backups have been created.</td></tr><?php endif;?>
        </tbody></table></div>
    </div></div>
</div></main>
</div></div>
</div>
<script src="js/jquery/jquery-2.2.4.min.js"></script>
<script src="js/bootstrap/bootstrap.min.js"></script>
</body>
</html>
