-- KPI monitoring snapshot for role-based management dashboard.

CREATE TABLE IF NOT EXISTS erp_kpi_monitoring_snapshot (
  id INT AUTO_INCREMENT PRIMARY KEY,
  snapshot_date DATE NOT NULL,
  kpi_code VARCHAR(80) NOT NULL,
  kpi_name VARCHAR(150) NOT NULL,
  kpi_area VARCHAR(80) NOT NULL,
  kpi_value DECIMAL(20,4) NOT NULL DEFAULT 0,
  target_value DECIMAL(20,4) NULL,
  unit_of_measure VARCHAR(30) NULL,
  status ENUM('GOOD','WARNING','CRITICAL','INFO') NOT NULL DEFAULT 'INFO',
  source_table VARCHAR(150) NULL,
  remarks VARCHAR(255) NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  UNIQUE KEY uk_erp_kpi_snapshot_date_code (snapshot_date, kpi_code),
  KEY idx_erp_kpi_snapshot_area (kpi_area, snapshot_date),
  KEY idx_erp_kpi_snapshot_status (status, snapshot_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

