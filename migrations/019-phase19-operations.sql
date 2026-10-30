CREATE TABLE IF NOT EXISTS srms_job_runs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  job_name VARCHAR(100) NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'running',
  started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  finished_at DATETIME NULL,
  processed_count INT UNSIGNED NOT NULL DEFAULT 0,
  success_count INT UNSIGNED NOT NULL DEFAULT 0,
  failure_count INT UNSIGNED NOT NULL DEFAULT 0,
  message VARCHAR(1000) NULL,
  PRIMARY KEY (id),
  KEY idx_job_runs_name_time (job_name, started_at),
  KEY idx_job_runs_status (status, started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
