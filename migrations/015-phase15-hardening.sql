CREATE TABLE IF NOT EXISTS security_login_attempts (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  attempt_key CHAR(64) NOT NULL,
  username_hash CHAR(64) NOT NULL,
  ip_address VARCHAR(45) NOT NULL,
  success TINYINT(1) NOT NULL DEFAULT 0,
  attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_login_key_time (attempt_key, success, attempted_at),
  KEY idx_login_ip_time (ip_address, success, attempted_at),
  KEY idx_login_time (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
