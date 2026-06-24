CREATE TABLE IF NOT EXISTS erp_leave_request (
  id INT(11) NOT NULL AUTO_INCREMENT,
  leave_no VARCHAR(30) NOT NULL,
  employee_id INT(11) NOT NULL,
  department_code CHAR(8) DEFAULT NULL,
  job_title_id INT(11) DEFAULT NULL,
  leave_type ENUM('ANNUAL_LEAVE','SICK_LEAVE','SPECIAL_LEAVE','MATERNITY_LEAVE','PATERNITY_LEAVE','MARRIAGE_LEAVE','BEREAVEMENT_LEAVE','UNPAID_LEAVE','PERMISSION','OTHER') NOT NULL DEFAULT 'ANNUAL_LEAVE',
  request_date DATE NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  start_half_day ENUM('FULL_DAY','AM','PM') NOT NULL DEFAULT 'FULL_DAY',
  end_half_day ENUM('FULL_DAY','AM','PM') NOT NULL DEFAULT 'FULL_DAY',
  total_days DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  leave_quota_before DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  leave_quota_after DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  reason TEXT DEFAULT NULL,
  attachment_ref VARCHAR(255) DEFAULT NULL,
  handover_to_employee_id INT(11) DEFAULT NULL,
  approver_employee_id INT(11) DEFAULT NULL,
  hr_reviewer_employee_id INT(11) DEFAULT NULL,
  workflow_status ENUM('DRAFT','SUBMITTED','MANAGER_APPROVED','HR_APPROVED','APPROVED','REJECTED','RETURNED','CANCELLED') NOT NULL DEFAULT 'DRAFT',
  approval_level ENUM('EMPLOYEE','MANAGER','HR','FINAL') NOT NULL DEFAULT 'EMPLOYEE',
  decision ENUM('PENDING','APPROVED','REJECTED','RETURNED','CANCELLED') NOT NULL DEFAULT 'PENDING',
  decision_by VARCHAR(50) DEFAULT NULL,
  decision_at DATETIME DEFAULT NULL,
  approver_note TEXT DEFAULT NULL,
  hr_note TEXT DEFAULT NULL,
  cancellation_reason VARCHAR(255) DEFAULT NULL,
  sap_reference VARCHAR(50) DEFAULT NULL,
  remarks TEXT DEFAULT NULL,
  created_by VARCHAR(50) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_by VARCHAR(50) DEFAULT NULL,
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_leave_no (leave_no),
  KEY idx_leave_employee (employee_id),
  KEY idx_leave_period (start_date,end_date),
  KEY idx_leave_status (workflow_status,decision),
  KEY idx_leave_dept (department_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

UPDATE sys_menu
   SET nav_act='leave_request',
       main_table='erp_leave_request',
       icon='fa-calendar-plus-o',
       dt_table='Y',
       tampil='Y'
 WHERE url='leave-request';

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id, g.level, 'Y',
       CASE WHEN g.level IN ('admin','system_administrator','hrd','manager_approver') THEN 'Y' ELSE 'N' END,
       CASE WHEN g.level IN ('admin','system_administrator','hrd','manager_approver') THEN 'Y' ELSE 'N' END,
       CASE WHEN g.level IN ('admin','system_administrator') THEN 'Y' ELSE 'N' END,
       'N'
  FROM sys_menu m
  JOIN sys_group_users g ON g.level IN ('admin','system_administrator','hrd','manager_approver','auditor')
 WHERE m.url='leave-request'
   AND NOT EXISTS (SELECT 1 FROM sys_menu_role r WHERE r.id_menu=m.id AND r.group_level=g.level);
