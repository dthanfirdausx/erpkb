CREATE TABLE IF NOT EXISTS erp_forecast (
  id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  forecast_no VARCHAR(30) NOT NULL UNIQUE,
  forecast_type ENUM('SALES','PRODUCTION','CUSTOMER','SAFETY_STOCK') NOT NULL DEFAULT 'SALES',
  forecast_version VARCHAR(20) NOT NULL DEFAULT 'BASE',
  plant_id INT NULL,
  plant_code VARCHAR(10) NULL,
  customer_code VARCHAR(30) NULL,
  customer_name VARCHAR(150) NULL,
  period_from DATE NOT NULL,
  period_to DATE NOT NULL,
  status ENUM('DRAFT','RELEASED','CLOSED','CANCELLED') NOT NULL DEFAULT 'DRAFT',
  total_qty DECIMAL(18,5) NOT NULL DEFAULT 0,
  remarks TEXT NULL,
  created_by VARCHAR(100) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_by VARCHAR(100) NULL,
  updated_at DATETIME NULL,
  released_by VARCHAR(100) NULL,
  released_at DATETIME NULL,
  cancelled_by VARCHAR(100) NULL,
  cancelled_at DATETIME NULL,
  cancel_reason VARCHAR(255) NULL,
  KEY idx_forecast_period (period_from,period_to),
  KEY idx_forecast_status (status),
  KEY idx_forecast_plant (plant_id,plant_code),
  KEY idx_forecast_customer (customer_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_forecast_detail (
  id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  forecast_id BIGINT NOT NULL,
  line_no INT NOT NULL DEFAULT 10,
  material_code VARCHAR(100) NOT NULL,
  material_name VARCHAR(255) NULL,
  material_type VARCHAR(100) NULL,
  period_month DATE NOT NULL,
  forecast_qty DECIMAL(18,5) NOT NULL DEFAULT 0,
  uom VARCHAR(30) NULL,
  source_type ENUM('MANUAL','SALES_HISTORY','CUSTOMER_COMMITMENT','FORECAST_UPLOAD') NOT NULL DEFAULT 'MANUAL',
  confidence_percent DECIMAL(5,2) NOT NULL DEFAULT 100.00,
  remarks VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_forecast_detail_header (forecast_id),
  KEY idx_forecast_detail_material (material_code),
  KEY idx_forecast_detail_period (period_month),
  CONSTRAINT fk_erp_forecast_detail_header FOREIGN KEY (forecast_id) REFERENCES erp_forecast(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

UPDATE sys_menu
SET nav_act='forecast',
    main_table='erp_forecast',
    tampil='Y'
WHERE url='forecast';

UPDATE sys_menu
SET tampil='N'
WHERE url LIKE 'forecast-legacy-%';

INSERT INTO sys_menu_role (id_menu,group_level,read_act,insert_act,update_act,delete_act,import_act)
SELECT 529,g.group_level,'Y',
       IF(g.group_level IN ('admin','system_administrator','ppic','manager_approver'),'Y','N'),
       IF(g.group_level IN ('admin','system_administrator','ppic','manager_approver'),'Y','N'),
       IF(g.group_level IN ('admin','system_administrator'),'Y','N'),
       'N'
FROM (SELECT DISTINCT group_level FROM sys_menu_role) g
WHERE NOT EXISTS (
  SELECT 1 FROM sys_menu_role r WHERE r.id_menu=529 AND r.group_level=g.group_level
);

UPDATE sys_menu_role
SET read_act = IF(group_level IN ('admin','system_administrator','ppic','manager_approver','produksi','gudang','quality_control'),'Y','N'),
    insert_act = IF(group_level IN ('admin','system_administrator','ppic'),'Y','N'),
    update_act = IF(group_level IN ('admin','system_administrator','ppic'),'Y','N'),
    delete_act = IF(group_level IN ('admin','system_administrator'),'Y','N')
WHERE id_menu = 529;
