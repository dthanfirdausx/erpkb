CREATE TABLE IF NOT EXISTS erp_training_registration (
  id INT NOT NULL AUTO_INCREMENT,
  registration_no VARCHAR(30) NOT NULL,
  training_plan_id INT NOT NULL,
  training_plan_participant_id INT DEFAULT NULL,
  employee_id INT NOT NULL,
  registration_date DATE NOT NULL,
  registration_source ENUM('PLAN_NOMINATION','MANUAL','MANAGER_REQUEST','EMPLOYEE_SELF_SERVICE') NOT NULL DEFAULT 'PLAN_NOMINATION',
  registration_status ENUM('REGISTERED','WAITLIST','CANCELLED','ATTENDED','NO_SHOW','COMPLETED') NOT NULL DEFAULT 'REGISTERED',
  approval_status ENUM('DRAFT','SUBMITTED','APPROVED','REJECTED') NOT NULL DEFAULT 'APPROVED',
  attendance_status ENUM('NOT_MARKED','PRESENT','ABSENT','PARTIAL') NOT NULL DEFAULT 'NOT_MARKED',
  check_in_time DATETIME DEFAULT NULL,
  check_out_time DATETIME DEFAULT NULL,
  learning_hours DECIMAL(8,2) NOT NULL DEFAULT 0,
  score DECIMAL(6,2) DEFAULT NULL,
  certificate_no VARCHAR(60) DEFAULT NULL,
  certificate_date DATE DEFAULT NULL,
  cancellation_reason VARCHAR(255) DEFAULT NULL,
  remarks TEXT DEFAULT NULL,
  created_by VARCHAR(50) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_by VARCHAR(50) DEFAULT NULL,
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_training_registration_no (registration_no),
  UNIQUE KEY uk_training_registration_employee (training_plan_id, employee_id),
  KEY idx_training_registration_plan (training_plan_id),
  KEY idx_training_registration_employee (employee_id),
  KEY idx_training_registration_date (registration_date),
  KEY idx_training_registration_status (registration_status, approval_status, attendance_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO erp_training_registration
(registration_no, training_plan_id, training_plan_participant_id, employee_id, registration_date, registration_source, registration_status, approval_status, attendance_status, learning_hours, remarks, created_by, updated_by, updated_at)
SELECT CONCAT('TRG-',DATE_FORMAT(CURDATE(),'%Y'),'-',LPAD(tpp.id,4,'0')), tpp.training_plan_id, tpp.id, tpp.employee_id, CURDATE(), 'PLAN_NOMINATION', 'REGISTERED', 'APPROVED', 'NOT_MARKED', 0, 'Auto registration dari dummy training plan', 'admin', 'admin', NOW()
FROM erp_training_plan_participant tpp
JOIN erp_training_plan tp ON tp.id=tpp.training_plan_id
WHERE tp.approval_status='APPROVED';

UPDATE sys_menu
SET nav_act='training_registration',
    main_table='erp_training_registration',
    dt_table='Y',
    icon='fa-pencil-square-o',
    tampil='Y'
WHERE url='training-registration';

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id, g.level,
  CASE WHEN g.level IN ('admin','system_administrator','hrd','manager_approver','auditor') THEN 'Y' ELSE 'N' END,
  CASE WHEN g.level IN ('admin','system_administrator','hrd') THEN 'Y' ELSE 'N' END,
  CASE WHEN g.level IN ('admin','system_administrator','hrd') THEN 'Y' ELSE 'N' END,
  CASE WHEN g.level IN ('admin','system_administrator') THEN 'Y' ELSE 'N' END,
  'N'
FROM sys_menu m
JOIN sys_group_users g
LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.level
WHERE m.url='training-registration' AND r.id IS NULL;
