CREATE TABLE IF NOT EXISTS erp_material_requirement (
  id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  requirement_no VARCHAR(30) NOT NULL UNIQUE,
  requirement_type ENUM('MRP','PRODUCTION_ORDER','MANUAL','SALES_ORDER','DEMAND_PLAN') NOT NULL DEFAULT 'MANUAL',
  requirement_date DATE NOT NULL,
  required_from DATE NULL,
  required_to DATE NULL,
  plant_id INT NULL,
  plant_code VARCHAR(20) NULL,
  source_mrp_id BIGINT NULL,
  source_mrp_no VARCHAR(30) NULL,
  source_production_order_id BIGINT NULL,
  source_production_order_no VARCHAR(30) NULL,
  source_ref VARCHAR(100) NULL,
  requestor VARCHAR(100) NULL,
  department VARCHAR(100) NULL,
  priority ENUM('LOW','NORMAL','HIGH','URGENT') NOT NULL DEFAULT 'NORMAL',
  status ENUM('DRAFT','SUBMITTED','APPROVED','STAGED','ISSUED','CLOSED','CANCELLED') NOT NULL DEFAULT 'DRAFT',
  total_items INT NOT NULL DEFAULT 0,
  total_required_qty DECIMAL(18,5) NOT NULL DEFAULT 0,
  total_open_qty DECIMAL(18,5) NOT NULL DEFAULT 0,
  reason VARCHAR(255) NULL,
  remarks TEXT NULL,
  created_by VARCHAR(100) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_by VARCHAR(100) NULL,
  updated_at DATETIME NULL,
  submitted_by VARCHAR(100) NULL,
  submitted_at DATETIME NULL,
  approved_by VARCHAR(100) NULL,
  approved_at DATETIME NULL,
  cancelled_by VARCHAR(100) NULL,
  cancelled_at DATETIME NULL,
  cancel_reason VARCHAR(255) NULL,
  KEY idx_mr_date (requirement_date,required_from,required_to),
  KEY idx_mr_status (status),
  KEY idx_mr_source_mrp (source_mrp_id,source_mrp_no),
  KEY idx_mr_source_po (source_production_order_id,source_production_order_no),
  KEY idx_mr_plant (plant_id,plant_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_material_requirement_detail (
  id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  requirement_id BIGINT NOT NULL,
  line_no INT NOT NULL DEFAULT 10,
  material_code VARCHAR(100) NOT NULL,
  material_name VARCHAR(255) NULL,
  material_type VARCHAR(100) NULL,
  required_date DATE NOT NULL,
  required_qty DECIMAL(18,5) NOT NULL DEFAULT 0,
  approved_qty DECIMAL(18,5) NOT NULL DEFAULT 0,
  staged_qty DECIMAL(18,5) NOT NULL DEFAULT 0,
  issued_qty DECIMAL(18,5) NOT NULL DEFAULT 0,
  open_qty DECIMAL(18,5) NOT NULL DEFAULT 0,
  available_stock DECIMAL(18,5) NOT NULL DEFAULT 0,
  uom VARCHAR(30) NULL,
  source_type ENUM('MRP','PRODUCTION_ORDER','BOM','MANUAL','SALES_ORDER','DEMAND_PLAN') NOT NULL DEFAULT 'MANUAL',
  source_ref VARCHAR(100) NULL,
  source_line_id BIGINT NULL,
  parent_material_code VARCHAR(100) NULL,
  storage_location VARCHAR(30) NULL,
  procurement_type ENUM('IN_HOUSE','EXTERNAL','BOTH') NOT NULL DEFAULT 'EXTERNAL',
  issue_status ENUM('OPEN','PARTIAL','FULL','CANCELLED') NOT NULL DEFAULT 'OPEN',
  remarks VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_mrd_header (requirement_id),
  KEY idx_mrd_material (material_code),
  KEY idx_mrd_date (required_date),
  KEY idx_mrd_source (source_type,source_ref,source_line_id),
  CONSTRAINT fk_erp_material_requirement_detail_header FOREIGN KEY (requirement_id) REFERENCES erp_material_requirement(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

UPDATE sys_menu
SET nav_act='material_requirement',
    main_table='erp_material_requirement',
    tampil='Y'
WHERE url='material-requirement';

INSERT INTO sys_menu_role (id_menu,group_level,read_act,insert_act,update_act,delete_act,import_act)
SELECT 377,g.group_level,'Y',
       IF(g.group_level IN ('admin','system_administrator','ppic','manager_approver','produksi'),'Y','N'),
       IF(g.group_level IN ('admin','system_administrator','ppic','manager_approver'),'Y','N'),
       IF(g.group_level IN ('admin','system_administrator'),'Y','N'),
       'N'
FROM (SELECT DISTINCT group_level FROM sys_menu_role) g
WHERE NOT EXISTS (
  SELECT 1 FROM sys_menu_role r WHERE r.id_menu=377 AND r.group_level=g.group_level
);
