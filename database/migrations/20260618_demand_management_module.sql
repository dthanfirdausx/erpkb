CREATE TABLE IF NOT EXISTS erp_demand_plan (
  id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  demand_no VARCHAR(30) NOT NULL UNIQUE,
  demand_type ENUM('PIR','SALES_ORDER','CUSTOMER_REQUIREMENT','SAFETY_STOCK','MANUAL') NOT NULL DEFAULT 'PIR',
  demand_version VARCHAR(20) NOT NULL DEFAULT 'BASE',
  plant_id INT NULL,
  plant_code VARCHAR(10) NULL,
  period_from DATE NOT NULL,
  period_to DATE NOT NULL,
  status ENUM('DRAFT','RELEASED','CLOSED','CANCELLED') NOT NULL DEFAULT 'DRAFT',
  total_qty DECIMAL(18,5) NOT NULL DEFAULT 0,
  source_forecast_id BIGINT NULL,
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
  KEY idx_demand_period (period_from,period_to),
  KEY idx_demand_status (status),
  KEY idx_demand_plant (plant_id,plant_code),
  KEY idx_demand_forecast (source_forecast_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_demand_plan_detail (
  id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  demand_id BIGINT NOT NULL,
  line_no INT NOT NULL DEFAULT 10,
  material_code VARCHAR(100) NOT NULL,
  material_name VARCHAR(255) NULL,
  period_date DATE NOT NULL,
  demand_qty DECIMAL(18,5) NOT NULL DEFAULT 0,
  uom VARCHAR(30) NULL,
  requirement_type ENUM('VSF','KE','LSF','MANUAL') NOT NULL DEFAULT 'VSF',
  source_type ENUM('FORECAST','SALES_ORDER','MANUAL','SAFETY_STOCK') NOT NULL DEFAULT 'MANUAL',
  source_ref VARCHAR(100) NULL,
  id_sales_order INT NULL,
  id_sales_order_detail BIGINT NULL,
  customer_code VARCHAR(30) NULL,
  customer_name VARCHAR(150) NULL,
  consumed_qty DECIMAL(18,5) NOT NULL DEFAULT 0,
  open_qty DECIMAL(18,5) NOT NULL DEFAULT 0,
  remarks VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_demand_detail_header (demand_id),
  KEY idx_demand_detail_material (material_code),
  KEY idx_demand_detail_period (period_date),
  KEY idx_demand_detail_so (id_sales_order,id_sales_order_detail),
  CONSTRAINT fk_erp_demand_detail_header FOREIGN KEY (demand_id) REFERENCES erp_demand_plan(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

UPDATE sys_menu
SET nav_act='demand_management',
    main_table='erp_demand_plan',
    tampil='Y'
WHERE url='demand-management';

INSERT INTO sys_menu_role (id_menu,group_level,read_act,insert_act,update_act,delete_act,import_act)
SELECT 555,g.group_level,'Y','N','N','N','N'
FROM (SELECT DISTINCT group_level FROM sys_menu_role) g
WHERE NOT EXISTS (
  SELECT 1 FROM sys_menu_role r WHERE r.id_menu=555 AND r.group_level=g.group_level
);

UPDATE sys_menu_role
SET read_act = IF(group_level IN ('admin','system_administrator','ppic','manager_approver','produksi','gudang','quality_control'),'Y','N'),
    insert_act = IF(group_level IN ('admin','system_administrator','ppic'),'Y','N'),
    update_act = IF(group_level IN ('admin','system_administrator','ppic'),'Y','N'),
    delete_act = IF(group_level IN ('admin','system_administrator'),'Y','N')
WHERE id_menu = 555;
