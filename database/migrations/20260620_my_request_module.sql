CREATE TABLE IF NOT EXISTS erp_employee_request (
  id INT(11) NOT NULL AUTO_INCREMENT,
  request_no VARCHAR(30) NOT NULL,
  employee_id INT(11) NOT NULL,
  employee_no VARCHAR(20) NULL,
  department_code CHAR(8) NULL,
  job_title_id INT(11) NULL,
  request_date DATE NOT NULL,
  request_category ENUM('EMPLOYEE_DATA','CERTIFICATE','CLAIM','BENEFIT','PAYROLL','ATTENDANCE_CORRECTION','DOCUMENT','FACILITY','OTHER') NOT NULL DEFAULT 'OTHER',
  request_type VARCHAR(80) NOT NULL,
  priority ENUM('LOW','NORMAL','HIGH','URGENT') NOT NULL DEFAULT 'NORMAL',
  required_date DATE NULL,
  subject VARCHAR(160) NOT NULL,
  description TEXT NULL,
  attachment_ref VARCHAR(255) NULL,
  approver_employee_id INT(11) NULL,
  hr_reviewer_employee_id INT(11) NULL,
  workflow_status ENUM('DRAFT','SUBMITTED','MANAGER_APPROVED','HR_REVIEW','APPROVED','REJECTED','RETURNED','CANCELLED','CLOSED') NOT NULL DEFAULT 'DRAFT',
  approval_level ENUM('EMPLOYEE','MANAGER','HR','FINAL') NOT NULL DEFAULT 'EMPLOYEE',
  decision ENUM('PENDING','APPROVED','REJECTED','RETURNED','CANCELLED','CLOSED') NOT NULL DEFAULT 'PENDING',
  decision_by VARCHAR(50) NULL,
  decision_at DATETIME NULL,
  manager_note TEXT NULL,
  hr_note TEXT NULL,
  cancellation_reason VARCHAR(255) NULL,
  resolution_note TEXT NULL,
  sap_reference VARCHAR(50) NULL,
  remarks TEXT NULL,
  created_by VARCHAR(50) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_by VARCHAR(50) NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_employee_request_no (request_no),
  KEY idx_employee_request_employee (employee_id),
  KEY idx_employee_request_date (request_date),
  KEY idx_employee_request_status (workflow_status, decision),
  KEY idx_employee_request_category (request_category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS erp_employee_request_history (
  id INT(11) NOT NULL AUTO_INCREMENT,
  request_id INT(11) NOT NULL,
  previous_status VARCHAR(40) NULL,
  new_status VARCHAR(40) NOT NULL,
  action_note TEXT NULL,
  action_by VARCHAR(50) NULL,
  action_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_employee_request_history_request (request_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE sys_menu
SET nav_act='my_request',
    main_table='erp_employee_request',
    icon='fa-inbox',
    dt_table='N',
    tampil='Y',
    type_menu='page'
WHERE url='my-request';

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id, g.level, 'Y', 'Y', 'Y', 'N', 'N'
FROM sys_menu m
JOIN sys_group_users g ON g.level='employee_self_service'
WHERE m.url='my-request'
  AND NOT EXISTS (
    SELECT 1 FROM sys_menu_role r
    WHERE r.id_menu=m.id AND r.group_level=g.level
  );

UPDATE sys_menu_role r
JOIN sys_menu m ON m.id=r.id_menu
SET r.read_act='Y',
    r.insert_act='Y',
    r.update_act='Y',
    r.delete_act='N',
    r.import_act='N'
WHERE m.url='my-request'
  AND r.group_level='employee_self_service';
