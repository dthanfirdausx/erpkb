CREATE TABLE IF NOT EXISTS erp_shift_schedule (
  id INT(11) NOT NULL AUTO_INCREMENT,
  assignment_no VARCHAR(30) NOT NULL,
  employee_id INT(11) NOT NULL,
  employee_no VARCHAR(20) DEFAULT NULL,
  department_code CHAR(8) DEFAULT NULL,
  work_schedule_id INT(11) DEFAULT NULL,
  work_schedule_code VARCHAR(30) DEFAULT NULL,
  shift_id INT(11) NOT NULL,
  shift_code VARCHAR(20) DEFAULT NULL,
  work_location_id INT(11) DEFAULT NULL,
  schedule_from DATE NOT NULL,
  schedule_to DATE NOT NULL,
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
  planned_hours_per_day DECIMAL(6,2) NOT NULL DEFAULT 8.00,
  rotation_group VARCHAR(30) DEFAULT NULL,
  roster_type ENUM('REGULAR','ROTATION','OVERTIME','REPLACEMENT','TEMPORARY') NOT NULL DEFAULT 'REGULAR',
  source_type ENUM('MANUAL','WORK_SCHEDULE','ROTATION_PLAN','IMPORT') NOT NULL DEFAULT 'MANUAL',
  overtime_eligible ENUM('Y','N') NOT NULL DEFAULT 'Y',
  attendance_required ENUM('Y','N') NOT NULL DEFAULT 'Y',
  assignment_status ENUM('DRAFT','PLANNED','RELEASED','CANCELLED') NOT NULL DEFAULT 'DRAFT',
  release_by VARCHAR(50) DEFAULT NULL,
  release_at DATETIME DEFAULT NULL,
  cancel_reason VARCHAR(255) DEFAULT NULL,
  sap_reference VARCHAR(50) DEFAULT NULL,
  remarks TEXT DEFAULT NULL,
  created_by VARCHAR(50) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_by VARCHAR(50) DEFAULT NULL,
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_shift_schedule_assignment (assignment_no),
  KEY idx_shift_schedule_employee (employee_id),
  KEY idx_shift_schedule_period (schedule_from,schedule_to),
  KEY idx_shift_schedule_shift (shift_id),
  KEY idx_shift_schedule_dept (department_code),
  KEY idx_shift_schedule_status (assignment_status),
  KEY idx_shift_schedule_work_schedule (work_schedule_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

UPDATE sys_menu
   SET nav_act='shift_schedule',
       main_table='erp_shift_schedule',
       icon='fa-clock-o',
       dt_table='Y',
       tampil='Y'
 WHERE url='shift-schedule';

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id, g.level, 'Y',
       IF(g.level IN ('admin','system_administrator','hrd'),'Y','N'),
       IF(g.level IN ('admin','system_administrator','hrd'),'Y','N'),
       IF(g.level IN ('admin','system_administrator'),'Y','N'),
       'N'
  FROM sys_menu m
  JOIN sys_group_users g
  LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.level
 WHERE m.url='shift-schedule'
   AND r.id IS NULL;
