<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only\n");
}

require_once dirname(__DIR__) . '/includes/config.php';

$dbh->exec(
    'CREATE TABLE IF NOT EXISTS srms_schema_migrations (
        migration_name VARCHAR(190) NOT NULL PRIMARY KEY,
        checksum CHAR(64) NOT NULL,
        applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
);

$directory = dirname(__DIR__) . '/migrations';
$command = strtolower($argv[1] ?? 'up');
$files = glob($directory . '/*.sql');
sort($files, SORT_STRING);

if (!is_array($files)) {
    $files = [];
}

function migration_status(PDO $dbh, string $name, string $checksum): ?array {
    $query = $dbh->prepare('SELECT migration_name, checksum, applied_at FROM srms_schema_migrations WHERE migration_name = :name');
    $query->execute([':name' => $name]);
    $row = $query->fetch();
    return $row ?: null;
}

function split_sql_statements(string $sql): array {
    $statements = [];
    $buffer = '';
    $quote = null;
    $escaped = false;
    $length = strlen($sql);

    for ($i = 0; $i < $length; $i++) {
        $character = $sql[$i];
        if ($quote !== null) {
            $buffer .= $character;
            if ($escaped) {
                $escaped = false;
            } elseif ($character === '\\') {
                $escaped = true;
            } elseif ($character === $quote) {
                $quote = null;
            }
            continue;
        }
        if ($character === "'" || $character === '"' || $character === '`') {
            $quote = $character;
            $buffer .= $character;
        } elseif ($character === ';') {
            $statement = trim($buffer);
            if ($statement !== '') {
                $statements[] = $statement;
            }
            $buffer = '';
        } else {
            $buffer .= $character;
        }
    }

    $statement = trim($buffer);
    if ($statement !== '') {
        $statements[] = $statement;
    }
    return $statements;
}

if ($command === 'status') {
    foreach ($files as $file) {
        $name = basename($file);
        $checksum = hash('sha256', file_get_contents($file));
        $status = migration_status($dbh, $name, $checksum);
        echo $name . ': ' . ($status ? ($status['checksum'] === $checksum ? 'applied' : 'changed') : 'pending') . PHP_EOL;
    }
    exit(0);
}

if ($command !== 'up') {
    fwrite(STDERR, "Usage: php scripts/migrate.php [up|status]\n");
    exit(1);
}

$applied = 0;
foreach ($files as $file) {
    $name = basename($file);
    $sql = file_get_contents($file);
    if ($sql === false) {
        throw new RuntimeException('Could not read migration: ' . $name);
    }
    $checksum = hash('sha256', $sql);
    $status = migration_status($dbh, $name, $checksum);
    if ($status) {
        if ($status['checksum'] !== $checksum) {
            throw new RuntimeException("Migration has changed after being applied: {$name}");
        }
        continue;
    }

    try {
        foreach (split_sql_statements($sql) as $statement) {
            $dbh->exec($statement);
        }
        $insert = $dbh->prepare(
            'INSERT INTO srms_schema_migrations(migration_name, checksum, applied_at) VALUES(:name, :checksum, NOW())'
        );
        $insert->execute([':name' => $name, ':checksum' => $checksum]);
        echo "Applied {$name}\n";
        $applied++;
    } catch (Throwable $e) {
        throw $e;
    }
}

echo "Migration complete. Applied {$applied} migration(s).\n";
