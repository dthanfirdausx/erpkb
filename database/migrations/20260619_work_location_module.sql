CREATE TABLE IF NOT EXISTS erp_work_location (
  id INT(11) NOT NULL AUTO_INCREMENT,
  location_code VARCHAR(20) NOT NULL,
  location_name VARCHAR(150) NOT NULL,
  location_type ENUM('HEAD_OFFICE','BRANCH_OFFICE','PLANT','WAREHOUSE','SALES_OFFICE','REMOTE','FIELD','CUSTOMER_SITE','OTHER') NOT NULL DEFAULT 'PLANT',
  company_structure_id INT(11) DEFAULT NULL,
  plant_id INT(11) DEFAULT NULL,
  storage_location_id INT(11) DEFAULT NULL,
  cost_center_code VARCHAR(20) DEFAULT NULL,
  profit_center_code VARCHAR(20) DEFAULT NULL,
  country CHAR(3) NOT NULL DEFAULT 'ID',
  province VARCHAR(100) DEFAULT NULL,
  city VARCHAR(100) DEFAULT NULL,
  district VARCHAR(100) DEFAULT NULL,
  postal_code VARCHAR(20) DEFAULT NULL,
  address VARCHAR(255) DEFAULT NULL,
  latitude DECIMAL(10,7) DEFAULT NULL,
  longitude DECIMAL(10,7) DEFAULT NULL,
  timezone VARCHAR(50) NOT NULL DEFAULT 'Asia/Jakarta',
  work_location_category ENUM('PRIMARY','SECONDARY','TEMPORARY','VIRTUAL') NOT NULL DEFAULT 'PRIMARY',
  attendance_required ENUM('Y','N') NOT NULL DEFAULT 'Y',
  geo_fence_radius_meter INT(11) DEFAULT NULL,
  capacity_headcount INT(11) NOT NULL DEFAULT 0,
  working_calendar_code VARCHAR(50) DEFAULT NULL,
  default_shift_code VARCHAR(50) DEFAULT NULL,
  contact_person VARCHAR(100) DEFAULT NULL,
  phone VARCHAR(50) DEFAULT NULL,
  email VARCHAR(100) DEFAULT NULL,
  valid_from DATE NOT NULL DEFAULT '2026-01-01',
  valid_to DATE NOT NULL DEFAULT '9999-12-31',
  sap_reference VARCHAR(50) DEFAULT NULL,
  status ENUM('DRAFT','ACTIVE','INACTIVE') NOT NULL DEFAULT 'DRAFT',
  inactive_reason VARCHAR(255) DEFAULT NULL,
  remarks TEXT DEFAULT NULL,
  created_by VARCHAR(50) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_by VARCHAR(50) DEFAULT NULL,
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_work_location_code (location_code),
  KEY idx_work_location_type (location_type,status),
  KEY idx_work_location_org (company_structure_id),
  KEY idx_work_location_plant (plant_id,storage_location_id),
  KEY idx_work_location_city (city),
  KEY idx_work_location_validity (valid_from,valid_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

UPDATE sys_menu
   SET nav_act='work_location',
       main_table='erp_work_location',
       icon='fa-map-marker',
       dt_table='Y',
       tampil='Y'
 WHERE url='work-location';

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id, g.level, 'Y',
       CASE WHEN g.level IN ('admin','system_administrator','hrd') THEN 'Y' ELSE 'N' END,
       CASE WHEN g.level IN ('admin','system_administrator','hrd') THEN 'Y' ELSE 'N' END,
       CASE WHEN g.level IN ('admin','system_administrator') THEN 'Y' ELSE 'N' END,
       'N'
  FROM sys_menu m
  JOIN sys_group_users g ON g.level IN ('admin','system_administrator','hrd','manager_approver','auditor')
 WHERE m.url='work-location'
   AND NOT EXISTS (
     SELECT 1 FROM sys_menu_role r WHERE r.id_menu=m.id AND r.group_level=g.level
   );
