<?php
// Phase 4 production security helpers.

function srms_client_ip(): string {
    // Do not trust X-Forwarded-For unless the reverse proxy is explicitly configured.
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
}

function srms_login_key(string $username, string $ip): string {
    return hash('sha256', strtolower(trim($username)).'|'.$ip);
}

function login_rate_limit_status(PDO $dbh, string $username, ?string $ip = null): array {
    $ip = $ip ?? srms_client_ip();
    $key = srms_login_key($username, $ip);
    $windowMinutes = 15;
    $maxIdentityFailures = 5;
    $maxIpFailures = 30;

    try {
        $q = $dbh->prepare('SELECT COUNT(*) FROM security_login_attempts WHERE attempt_key=:k AND success=0 AND attempted_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)');
        $q->execute([':k'=>$key]);
        $identityFailures = (int)$q->fetchColumn();

        $q = $dbh->prepare('SELECT COUNT(*) FROM security_login_attempts WHERE ip_address=:ip AND success=0 AND attempted_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)');
        $q->execute([':ip'=>$ip]);
        $ipFailures = (int)$q->fetchColumn();

        if ($identityFailures >= $maxIdentityFailures || $ipFailures >= $maxIpFailures) {
            return [
                'allowed'=>false,
                'retry_after'=>$windowMinutes * 60,
                'identity_failures'=>$identityFailures,
                'ip_failures'=>$ipFailures,
            ];
        }
    } catch (Throwable $e) {
        // Security controls must not take the application down if the migration has not run yet.
        error_log('SRMS rate-limit check unavailable: '.$e->getMessage());
    }

    return ['allowed'=>true,'retry_after'=>0,'identity_failures'=>0,'ip_failures'=>0];
}

function login_rate_limit_record(PDO $dbh, string $username, bool $success, ?string $ip = null): void {
    $ip = $ip ?? srms_client_ip();
    $key = srms_login_key($username, $ip);
    try {
        $q = $dbh->prepare('INSERT INTO security_login_attempts(attempt_key,username_hash,ip_address,success,attempted_at) VALUES(:k,:u,:ip,:s,NOW())');
        $q->execute([
            ':k'=>$key,
            ':u'=>hash('sha256', strtolower(trim($username))),
            ':ip'=>$ip,
            ':s'=>$success ? 1 : 0,
        ]);
        // Keep this table small in normal operation.
        $dbh->exec('DELETE FROM security_login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 2 DAY)');
    } catch (Throwable $e) {
        error_log('SRMS rate-limit record unavailable: '.$e->getMessage());
    }
}

function login_rate_limit_clear(PDO $dbh, string $username, ?string $ip = null): void {
    $ip = $ip ?? srms_client_ip();
    try {
        $q = $dbh->prepare('DELETE FROM security_login_attempts WHERE attempt_key=:k');
        $q->execute([':k'=>srms_login_key($username, $ip)]);
    } catch (Throwable $e) {
        error_log('SRMS rate-limit clear unavailable: '.$e->getMessage());
    }
}

function phase4_validate_sql_upload(array $file, int $maxBytes = 52428800): array {
    if (!isset($file['error']) || (int)$file['error'] !== UPLOAD_ERR_OK) {
        return [false, 'Please select a valid SQL backup file.'];
    }
    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return [false, 'The uploaded file could not be verified as a browser upload.'];
    }
    if ((int)$file['size'] <= 0 || (int)$file['size'] > $maxBytes) {
        return [false, 'The SQL backup is empty or exceeds the 50 MB upload limit.'];
    }

    $name = (string)($file['name'] ?? '');
    if (!preg_match('/\A[a-zA-Z0-9][a-zA-Z0-9._ -]{0,180}\.sql\z/i', $name)) {
        return [false, 'Only a normal .sql backup filename is accepted.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowedMimes = ['text/plain','text/x-sql','application/sql','application/octet-stream'];
    if ($mime !== false && !in_array($mime, $allowedMimes, true)) {
        return [false, 'The uploaded file does not appear to be a SQL text backup.'];
    }

    $sample = file_get_contents($file['tmp_name'], false, null, 0, 262144);
    if ($sample === false || trim($sample) === '') {
        return [false, 'The uploaded SQL backup is empty.'];
    }
    if (preg_match('/<\?(?:php|=)?|<script\b/i', $sample)) {
        return [false, 'The uploaded file contains executable/web content and was rejected.'];
    }
    // Require common mysqldump/SQL markers. This is a safety gate, not a parser.
    if (!preg_match('/(?:CREATE\s+TABLE|INSERT\s+INTO|DROP\s+TABLE|SET\s+[^;]*FOREIGN_KEY_CHECKS|--\s+MySQL dump)/i', $sample)) {
        return [false, 'The file does not look like a valid MySQL SQL backup.'];
    }

    return [true, ''];
}
