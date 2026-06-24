CREATE TABLE IF NOT EXISTS erp_attendance (
  id INT(11) NOT NULL AUTO_INCREMENT,
  attendance_no VARCHAR(30) NOT NULL,
  employee_id INT(11) NOT NULL,
  employee_no VARCHAR(20) DEFAULT NULL,
  department_code CHAR(8) DEFAULT NULL,
  shift_schedule_id INT(11) DEFAULT NULL,
  assignment_no VARCHAR(30) DEFAULT NULL,
  shift_id INT(11) DEFAULT NULL,
  shift_code VARCHAR(20) DEFAULT NULL,
  work_location_id INT(11) DEFAULT NULL,
  attendance_date DATE NOT NULL,
  planned_start DATETIME DEFAULT NULL,
  planned_end DATETIME DEFAULT NULL,
  actual_clock_in DATETIME DEFAULT NULL,
  actual_clock_out DATETIME DEFAULT NULL,
  break_minutes INT(11) NOT NULL DEFAULT 60,
  planned_hours DECIMAL(6,2) NOT NULL DEFAULT 8.00,
  actual_hours DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  late_minutes INT(11) NOT NULL DEFAULT 0,
  early_leave_minutes INT(11) NOT NULL DEFAULT 0,
  overtime_hours DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  attendance_type ENUM('REGULAR','OVERTIME','BUSINESS_TRIP','TRAINING','REMOTE','LEAVE','SICK','ABSENT') NOT NULL DEFAULT 'REGULAR',
  attendance_source ENUM('MACHINE','MANUAL','IMPORT','MOBILE','WEB') NOT NULL DEFAULT 'MANUAL',
  attendance_status ENUM('DRAFT','RECORDED','APPROVED','POSTED','REJECTED','CANCELLED') NOT NULL DEFAULT 'DRAFT',
  absence_reason VARCHAR(100) DEFAULT NULL,
  correction_reason VARCHAR(255) DEFAULT NULL,
  approved_by VARCHAR(50) DEFAULT NULL,
  approved_at DATETIME DEFAULT NULL,
  posted_by VARCHAR(50) DEFAULT NULL,
  posted_at DATETIME DEFAULT NULL,
  sap_reference VARCHAR(50) DEFAULT NULL,
  remarks TEXT DEFAULT NULL,
  created_by VARCHAR(50) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_by VARCHAR(50) DEFAULT NULL,
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_attendance_no (attendance_no),
  UNIQUE KEY uq_attendance_employee_date (employee_id,attendance_date),
  KEY idx_attendance_date (attendance_date),
  KEY idx_attendance_employee (employee_id),
  KEY idx_attendance_dept (department_code),
  KEY idx_attendance_shift_schedule (shift_schedule_id),
  KEY idx_attendance_status (attendance_status),
  KEY idx_attendance_type (attendance_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

UPDATE sys_menu
   SET nav_act='attendance',
       main_table='erp_attendance',
       icon='fa-calendar-check-o',
       dt_table='Y',
       tampil='Y'
 WHERE url='attendance';

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id, g.level, 'Y',
       IF(g.level IN ('admin','system_administrator','hrd'),'Y','N'),
       IF(g.level IN ('admin','system_administrator','hrd'),'Y','N'),
       IF(g.level IN ('admin','system_administrator'),'Y','N'),
       'N'
  FROM sys_menu m
  JOIN sys_group_users g
  LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.level
 WHERE m.url='attendance'
   AND r.id IS NULL;
