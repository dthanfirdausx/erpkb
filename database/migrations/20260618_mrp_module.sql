CREATE TABLE IF NOT EXISTS erp_mrp_run (
  id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  mrp_no VARCHAR(30) NOT NULL UNIQUE,
  mrp_type ENUM('NET_CHANGE','REGENERATIVE','MANUAL') NOT NULL DEFAULT 'NET_CHANGE',
  planning_scope ENUM('PLANT','MATERIAL','DEMAND_PLAN') NOT NULL DEFAULT 'PLANT',
  plant_id INT NULL,
  plant_code VARCHAR(20) NULL,
  period_from DATE NOT NULL,
  period_to DATE NOT NULL,
  source_demand_id BIGINT NULL,
  status ENUM('DRAFT','RELEASED','CANCELLED') NOT NULL DEFAULT 'DRAFT',
  total_material INT NOT NULL DEFAULT 0,
  total_gross_req DECIMAL(18,5) NOT NULL DEFAULT 0,
  total_shortage DECIMAL(18,5) NOT NULL DEFAULT 0,
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
  KEY idx_mrp_period (period_from,period_to),
  KEY idx_mrp_status (status),
  KEY idx_mrp_plant (plant_id,plant_code),
  KEY idx_mrp_source_demand (source_demand_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_mrp_run_detail (
  id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  mrp_id BIGINT NOT NULL,
  line_no INT NOT NULL DEFAULT 10,
  material_code VARCHAR(100) NOT NULL,
  material_name VARCHAR(255) NULL,
  material_type VARCHAR(100) NULL,
  requirement_date DATE NOT NULL,
  gross_requirement DECIMAL(18,5) NOT NULL DEFAULT 0,
  available_stock DECIMAL(18,5) NOT NULL DEFAULT 0,
  open_supply DECIMAL(18,5) NOT NULL DEFAULT 0,
  safety_stock DECIMAL(18,5) NOT NULL DEFAULT 0,
  net_requirement DECIMAL(18,5) NOT NULL DEFAULT 0,
  planned_order_qty DECIMAL(18,5) NOT NULL DEFAULT 0,
  uom VARCHAR(30) NULL,
  procurement_type ENUM('IN_HOUSE','EXTERNAL','BOTH') NOT NULL DEFAULT 'EXTERNAL',
  source_type ENUM('DEMAND_PLAN','FORECAST','SALES_ORDER','MANUAL','BOM_EXPLOSION') NOT NULL DEFAULT 'MANUAL',
  source_ref VARCHAR(100) NULL,
  parent_material_code VARCHAR(100) NULL,
  exception_message VARCHAR(255) NULL,
  remarks VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_mrp_detail_header (mrp_id),
  KEY idx_mrp_detail_material (material_code),
  KEY idx_mrp_detail_date (requirement_date),
  KEY idx_mrp_detail_source (source_type,source_ref),
  CONSTRAINT fk_erp_mrp_run_detail_header FOREIGN KEY (mrp_id) REFERENCES erp_mrp_run(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

UPDATE sys_menu
SET nav_act='mrp',
    main_table='erp_mrp_run',
    tampil='Y'
WHERE url='mrp';

INSERT INTO sys_menu_role (id_menu,group_level,read_act,insert_act,update_act,delete_act,import_act)
SELECT 381,g.group_level,'Y',
       IF(g.group_level IN ('admin','system_administrator','ppic','manager_approver'),'Y','N'),
       IF(g.group_level IN ('admin','system_administrator','ppic','manager_approver'),'Y','N'),
       IF(g.group_level IN ('admin','system_administrator'),'Y','N'),
       'N'
FROM (SELECT DISTINCT group_level FROM sys_menu_role) g
WHERE NOT EXISTS (
  SELECT 1 FROM sys_menu_role r WHERE r.id_menu=381 AND r.group_level=g.group_level
);
