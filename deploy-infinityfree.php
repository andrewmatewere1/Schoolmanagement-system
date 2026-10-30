<?php
require_once __DIR__ . '/includes/config.php';
require_super_admin();

$message = '';
$error = '';
$directory = __DIR__ . '/migrations';
$files = glob($directory . '/*.sql') ?: [];
sort($files, SORT_STRING);

$dbh->exec(
    'CREATE TABLE IF NOT EXISTS srms_schema_migrations (
        migration_name VARCHAR(190) NOT NULL PRIMARY KEY,
        checksum CHAR(64) NOT NULL,
        applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
);

function infinity_split_sql(string $sql): array
{
    $parts = [];
    $buffer = '';
    $quote = null;
    $escaped = false;
    $length = strlen($sql);
    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];
        if ($quote !== null) {
            $buffer .= $char;
            if ($escaped) {
                $escaped = false;
            } elseif ($char === '\\') {
                $escaped = true;
            } elseif ($char === $quote) {
                $quote = null;
            }
            continue;
        }
        if ($char === "'" || $char === '"' || $char === '`') {
            $quote = $char;
            $buffer .= $char;
        } elseif ($char === ';') {
            $statement = trim($buffer);
            if ($statement !== '') {
                $parts[] = $statement;
            }
            $buffer = '';
        } else {
            $buffer .= $char;
        }
    }
    $statement = trim($buffer);
    if ($statement !== '') {
        $parts[] = $statement;
    }
    return $parts;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_migrations'])) {
    verify_csrf();
    $applied = 0;
    try {
        foreach ($files as $file) {
            $name = basename($file);
            $sql = file_get_contents($file);
            if ($sql === false) {
                throw new RuntimeException('Could not read migration: ' . $name);
            }
            $checksum = hash('sha256', $sql);
            $q = $dbh->prepare('SELECT checksum FROM srms_schema_migrations WHERE migration_name=:name');
            $q->execute([':name' => $name]);
            $row = $q->fetch();
            if ($row) {
                if (!hash_equals((string)$row['checksum'], $checksum)) {
                    throw new RuntimeException('Migration changed after it was applied: ' . $name);
                }
                continue;
            }
            foreach (infinity_split_sql($sql) as $statement) {
                $dbh->exec($statement);
            }
            $insert = $dbh->prepare('INSERT INTO srms_schema_migrations(migration_name,checksum,applied_at) VALUES(:name,:checksum,NOW())');
            $insert->execute([':name' => $name, ':checksum' => $checksum]);
            $applied++;
        }
        $message = $applied === 0 ? 'No pending migrations.' : $applied . ' migration(s) applied.';
    } catch (Throwable $e) {
        error_log('InfinityFree migration error: ' . $e->getMessage());
        $error = $e->getMessage();
    }
}

$rows = [];
foreach ($files as $file) {
    $name = basename($file);
    $checksum = hash('sha256', file_get_contents($file));
    $q = $dbh->prepare('SELECT checksum,applied_at FROM srms_schema_migrations WHERE migration_name=:name');
    $q->execute([':name' => $name]);
    $row = $q->fetch();
    $status = !$row ? 'Pending' : (hash_equals((string)$row['checksum'], $checksum) ? 'Applied' : 'Changed');
    $rows[] = ['name' => $name, 'status' => $status, 'applied_at' => $row['applied_at'] ?? ''];
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>InfinityFree Deployment | SRMS</title>
<link rel="stylesheet" href="css/bootstrap.min.css"><link rel="stylesheet" href="css/main.css">
<link rel="stylesheet" href="css/srms-modernization.css"><link rel="stylesheet" href="css/srms-responsive.css">
</head>
<body class="top-navbar-fixed"><div class="main-wrapper"><?php include 'includes/topbar.php';?><div class="content-wrapper"><div class="content-container"><?php include 'includes/leftbar.php';?><main class="main-page"><div class="container-fluid">
<h2>InfinityFree Deployment</h2>
<?php if($message):?><div class="alert alert-success"><?=e($message)?></div><?php endif;?>
<?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?>
<div class="alert alert-warning"><strong>One-time deployment tool:</strong> Import the baseline database first. Back up before running migrations, and remove this file after deployment.</div>
<div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Migration</th><th>Status</th><th>Applied</th></tr></thead><tbody>
<?php foreach($rows as $row):?><tr><td><?=e($row['name'])?></td><td><?=e($row['status'])?></td><td><?=e($row['applied_at']?:'—')?></td></tr><?php endforeach;?>
</tbody></table></div>
<form method="post"><?=csrf_field()?><input type="hidden" name="run_migrations" value="1"><button class="btn btn-primary">Apply Pending Migrations</button></form>
</div></main></div></div></div></body></html>
