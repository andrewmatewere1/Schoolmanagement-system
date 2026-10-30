<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only\n");
}

require_once dirname(__DIR__) . '/includes/config.php';

$mysqldump = getenv('SRMS_MYSQLDUMP') ?: '';
if ($mysqldump === '') {
    $candidates = [
        'C:\\xampp\\mysql\\bin\\mysqldump.exe',
        'C:\\Program Files\\MariaDB\\bin\\mysqldump.exe',
    ];
    foreach ($candidates as $candidate) {
        if (is_file($candidate)) {
            $mysqldump = $candidate;
            break;
        }
    }
}
if ($mysqldump === '' || !is_file($mysqldump)) {
    fwrite(STDERR, "mysqldump was not found. Set SRMS_MYSQLDUMP to the executable path.\n");
    exit(2);
}

$storage = dirname(__DIR__) . '/storage/backups';
if (!is_dir($storage) && !mkdir($storage, 0700, true)) {
    fwrite(STDERR, "Could not create private backup directory.\n");
    exit(2);
}

$file = $storage . '/srms-' . date('Ymd-His') . '.sql';
$command = [
    $mysqldump,
    '--single-transaction',
    '--routines',
    '--events',
    '--default-character-set=utf8mb4',
    '--host=' . DB_HOST,
    '--user=' . DB_USER,
];
if (DB_PASS !== '') {
    $command[] = '--password=' . DB_PASS;
}
$command[] = DB_NAME;

$descriptors = [
    0 => ['pipe', 'r'],
    1 => ['file', $file, 'w'],
    2 => ['pipe', 'w'],
];
$process = proc_open($command, $descriptors, $pipes);
if (!is_resource($process)) {
    fwrite(STDERR, "Could not start mysqldump.\n");
    exit(2);
}
fclose($pipes[0]);
$stderr = stream_get_contents($pipes[2]);
fclose($pipes[2]);
$exitCode = proc_close($process);

if ($exitCode !== 0 || !is_file($file) || filesize($file) < 100) {
    if (is_file($file)) {
        unlink($file);
    }
    fwrite(STDERR, 'Database backup failed: ' . trim(substr($stderr, 0, 1000)) . "\n");
    exit(1);
}
chmod($file, 0600);
echo 'Database backup created: ' . $file . "\n";
