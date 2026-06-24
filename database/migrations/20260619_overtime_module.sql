CREATE TABLE IF NOT EXISTS erp_overtime (
  id INT(11) NOT NULL AUTO_INCREMENT,
  overtime_no VARCHAR(30) NOT NULL,
  employee_id INT(11) NOT NULL,
  employee_no VARCHAR(20) DEFAULT NULL,
  department_code CHAR(8) DEFAULT NULL,
  cost_center_code VARCHAR(20) DEFAULT NULL,
  attendance_id INT(11) DEFAULT NULL,
  attendance_no VARCHAR(30) DEFAULT NULL,
  shift_schedule_id INT(11) DEFAULT NULL,
  assignment_no VARCHAR(30) DEFAULT NULL,
  overtime_date DATE NOT NULL,
  planned_start DATETIME DEFAULT NULL,
  planned_end DATETIME DEFAULT NULL,
  actual_start DATETIME DEFAULT NULL,
  actual_end DATETIME DEFAULT NULL,
  requested_hours DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  approved_hours DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  payable_hours DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  rate_multiplier DECIMAL(6,2) NOT NULL DEFAULT 1.50,
  hourly_rate DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  estimated_amount DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  overtime_type ENUM('REGULAR_OT','HOLIDAY_OT','WEEKEND_OT','CALL_OUT','PROJECT_OT','EMERGENCY_OT') NOT NULL DEFAULT 'REGULAR_OT',
  overtime_reason VARCHAR(150) NOT NULL,
  request_source ENUM('ATTENDANCE','MANUAL','IMPORT','MOBILE','WEB') NOT NULL DEFAULT 'MANUAL',
  overtime_status ENUM('DRAFT','REQUESTED','APPROVED','REJECTED','POSTED','CANCELLED') NOT NULL DEFAULT 'DRAFT',
  requested_by VARCHAR(50) DEFAULT NULL,
  requested_at DATETIME DEFAULT NULL,
  approved_by VARCHAR(50) DEFAULT NULL,
  approved_at DATETIME DEFAULT NULL,
  posted_by VARCHAR(50) DEFAULT NULL,
  posted_at DATETIME DEFAULT NULL,
  reject_reason VARCHAR(255) DEFAULT NULL,
  sap_reference VARCHAR(50) DEFAULT NULL,
  remarks TEXT DEFAULT NULL,
  created_by VARCHAR(50) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_by VARCHAR(50) DEFAULT NULL,
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_overtime_no (overtime_no),
  KEY idx_overtime_employee (employee_id),
  KEY idx_overtime_date (overtime_date),
  KEY idx_overtime_dept (department_code),
  KEY idx_overtime_attendance (attendance_id),
  KEY idx_overtime_status (overtime_status),
  KEY idx_overtime_type (overtime_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

UPDATE sys_menu
   SET nav_act='overtime',
       main_table='erp_overtime',
       icon='fa-clock-o',
       dt_table='Y',
       tampil='Y'
 WHERE url='overtime';

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id, g.level, 'Y',
       IF(g.level IN ('admin','system_administrator','hrd'),'Y','N'),
       IF(g.level IN ('admin','system_administrator','hrd'),'Y','N'),
       IF(g.level IN ('admin','system_administrator'),'Y','N'),
       'N'
  FROM sys_menu m
  JOIN sys_group_users g
  LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.level
 WHERE m.url='overtime'
   AND r.id IS NULL;
