CREATE TABLE IF NOT EXISTS erp_holiday_calendar (
  id INT(11) NOT NULL AUTO_INCREMENT,
  holiday_code VARCHAR(30) NOT NULL,
  holiday_name VARCHAR(150) NOT NULL,
  holiday_date DATE NOT NULL,
  holiday_end_date DATE DEFAULT NULL,
  holiday_type ENUM('PUBLIC_HOLIDAY','COMPANY_HOLIDAY','COLLECTIVE_LEAVE','REGIONAL_HOLIDAY','RELIGIOUS_HOLIDAY','SPECIAL_NON_WORKING','HALF_DAY') NOT NULL DEFAULT 'PUBLIC_HOLIDAY',
  holiday_scope ENUM('NATIONAL','REGIONAL','PLANT','COMPANY','LOCATION') NOT NULL DEFAULT 'NATIONAL',
  calendar_id INT(11) DEFAULT NULL,
  calendar_code VARCHAR(30) DEFAULT NULL,
  plant_code VARCHAR(20) DEFAULT NULL,
  region_code VARCHAR(30) DEFAULT NULL,
  country CHAR(3) NOT NULL DEFAULT 'ID',
  work_location_id INT(11) DEFAULT NULL,
  working_hours DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  paid_holiday ENUM('Y','N') NOT NULL DEFAULT 'Y',
  recurring_annual ENUM('Y','N') NOT NULL DEFAULT 'N',
  source_reference VARCHAR(100) DEFAULT NULL,
  holiday_status ENUM('DRAFT','ACTIVE','INACTIVE') NOT NULL DEFAULT 'DRAFT',
  sap_reference VARCHAR(50) DEFAULT NULL,
  remarks TEXT DEFAULT NULL,
  created_by VARCHAR(50) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_by VARCHAR(50) DEFAULT NULL,
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_holiday_code (holiday_code),
  KEY idx_holiday_date (holiday_date,holiday_end_date),
  KEY idx_holiday_type (holiday_type),
  KEY idx_holiday_scope (holiday_scope),
  KEY idx_holiday_calendar (calendar_id),
  KEY idx_holiday_status (holiday_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

UPDATE sys_menu
   SET nav_act='holiday_calendar',
       main_table='erp_holiday_calendar',
       icon='fa-calendar-times-o',
       dt_table='Y',
       tampil='Y'
 WHERE url='holiday-calendar';

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id, g.level, 'Y',
       IF(g.level IN ('admin','system_administrator','hrd'),'Y','N'),
       IF(g.level IN ('admin','system_administrator','hrd'),'Y','N'),
       IF(g.level IN ('admin','system_administrator'),'Y','N'),
       'N'
  FROM sys_menu m
  JOIN sys_group_users g
  LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.level
 WHERE m.url='holiday-calendar'
   AND r.id IS NULL;
