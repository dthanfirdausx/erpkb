CREATE TABLE IF NOT EXISTS erp_work_schedule (
  id INT(11) NOT NULL AUTO_INCREMENT,
  schedule_code VARCHAR(30) NOT NULL,
  schedule_name VARCHAR(150) NOT NULL,
  schedule_type ENUM('FIXED','FLEXIBLE','SHIFT','ROTATION','REMOTE','PART_TIME') NOT NULL DEFAULT 'FIXED',
  schedule_category ENUM('OFFICE','PRODUCTION','WAREHOUSE','SALES','SUPPORT','REMOTE','OTHER') NOT NULL DEFAULT 'OFFICE',
  calendar_id INT(11) DEFAULT NULL,
  calendar_code VARCHAR(30) DEFAULT NULL,
  default_shift_id INT(11) DEFAULT NULL,
  default_shift_code VARCHAR(20) DEFAULT NULL,
  work_location_id INT(11) DEFAULT NULL,
  department_code CHAR(8) DEFAULT NULL,
  employee_group ENUM('DIRECTOR','MANAGER','STAFF','NON_STAFF','OPERATOR','CONTRACT','DAILY_WORKER','TRAINEE') NOT NULL DEFAULT 'STAFF',
  monday ENUM('Y','N') NOT NULL DEFAULT 'Y',
  tuesday ENUM('Y','N') NOT NULL DEFAULT 'Y',
  wednesday ENUM('Y','N') NOT NULL DEFAULT 'Y',
  thursday ENUM('Y','N') NOT NULL DEFAULT 'Y',
  friday ENUM('Y','N') NOT NULL DEFAULT 'Y',
  saturday ENUM('Y','N') NOT NULL DEFAULT 'N',
  sunday ENUM('Y','N') NOT NULL DEFAULT 'N',
  planned_start TIME DEFAULT NULL,
  planned_end TIME DEFAULT NULL,
  break_minutes INT(11) NOT NULL DEFAULT 60,
  working_hours_per_day DECIMAL(6,2) NOT NULL DEFAULT 8.00,
  working_hours_per_week DECIMAL(6,2) NOT NULL DEFAULT 40.00,
  grace_in_minutes INT(11) NOT NULL DEFAULT 0,
  grace_out_minutes INT(11) NOT NULL DEFAULT 0,
  overtime_eligible ENUM('Y','N') NOT NULL DEFAULT 'Y',
  attendance_required ENUM('Y','N') NOT NULL DEFAULT 'Y',
  valid_from DATE NOT NULL DEFAULT '2026-01-01',
  valid_to DATE NOT NULL DEFAULT '9999-12-31',
  schedule_status ENUM('DRAFT','ACTIVE','INACTIVE') NOT NULL DEFAULT 'DRAFT',
  sap_reference VARCHAR(50) DEFAULT NULL,
  remarks TEXT DEFAULT NULL,
  created_by VARCHAR(50) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_by VARCHAR(50) DEFAULT NULL,
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_work_schedule_code (schedule_code),
  KEY idx_work_schedule_type (schedule_type,schedule_category),
  KEY idx_work_schedule_calendar (calendar_id),
  KEY idx_work_schedule_shift (default_shift_id),
  KEY idx_work_schedule_location (work_location_id),
  KEY idx_work_schedule_dept (department_code),
  KEY idx_work_schedule_status (schedule_status),
  KEY idx_work_schedule_validity (valid_from,valid_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

UPDATE sys_menu
   SET nav_act='work_schedule',
       main_table='erp_work_schedule',
       icon='fa-calendar',
       dt_table='Y',
       tampil='Y'
 WHERE url='work-schedule';

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id, g.level, 'Y',
       IF(g.level IN ('admin','system_administrator','hrd'),'Y','N'),
       IF(g.level IN ('admin','system_administrator','hrd'),'Y','N'),
       IF(g.level IN ('admin','system_administrator'),'Y','N'),
       'N'
  FROM sys_menu m
  JOIN sys_group_users g
  LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.level
 WHERE m.url='work-schedule'
   AND r.id IS NULL;
