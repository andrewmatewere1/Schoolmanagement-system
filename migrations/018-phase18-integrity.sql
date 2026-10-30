CREATE TABLE IF NOT EXISTS srms_operation_receipts (
  operation_key CHAR(64) NOT NULL,
  principal_type VARCHAR(20) NOT NULL,
  principal_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
  action_name VARCHAR(100) NOT NULL,
  completed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (operation_key),
  KEY idx_operation_principal (principal_type, principal_id),
  KEY idx_operation_completed (completed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
