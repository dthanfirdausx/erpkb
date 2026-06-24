CREATE TABLE IF NOT EXISTS erp_employee_contract (
  id INT(11) NOT NULL AUTO_INCREMENT,
  contract_no VARCHAR(30) NOT NULL,
  employee_id INT(11) NOT NULL,
  contract_type ENUM('PERMANENT','FIXED_TERM','PROBATION','INTERNSHIP','OUTSOURCING','DAILY_WORKER','CONSULTANT') NOT NULL DEFAULT 'FIXED_TERM',
  contract_status ENUM('DRAFT','ACTIVE','EXPIRED','TERMINATED','RENEWED','CANCELLED') NOT NULL DEFAULT 'DRAFT',
  contract_start DATE NOT NULL,
  contract_end DATE DEFAULT NULL,
  probation_start DATE DEFAULT NULL,
  probation_end DATE DEFAULT NULL,
  renewal_no INT(11) NOT NULL DEFAULT 0,
  previous_contract_id INT(11) DEFAULT NULL,
  department_code CHAR(8) DEFAULT NULL,
  job_title_id INT(11) DEFAULT NULL,
  position_id INT(11) DEFAULT NULL,
  company_structure_id INT(11) DEFAULT NULL,
  work_location_id INT(11) DEFAULT NULL,
  employee_group ENUM('DIRECTOR','MANAGER','STAFF','NON_STAFF','OPERATOR','CONTRACT','DAILY_WORKER','TRAINEE') NOT NULL DEFAULT 'STAFF',
  pay_grade VARCHAR(30) DEFAULT NULL,
  payroll_area VARCHAR(30) DEFAULT NULL,
  basic_salary DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  currency VARCHAR(3) NOT NULL DEFAULT 'IDR',
  working_hours_per_week DECIMAL(6,2) NOT NULL DEFAULT 40.00,
  notice_period_days INT(11) NOT NULL DEFAULT 30,
  contract_reason ENUM('NEW_HIRE','RENEWAL','PROMOTION','MUTATION','PROBATION_PASS','CORRECTION','OTHER') NOT NULL DEFAULT 'NEW_HIRE',
  approval_status ENUM('NOT_REQUIRED','PENDING','APPROVED','REJECTED') NOT NULL DEFAULT 'PENDING',
  approved_by_employee_id INT(11) DEFAULT NULL,
  approved_at DATETIME DEFAULT NULL,
  attachment_ref VARCHAR(255) DEFAULT NULL,
  termination_reason VARCHAR(255) DEFAULT NULL,
  sap_reference VARCHAR(50) DEFAULT NULL,
  remarks TEXT DEFAULT NULL,
  created_by VARCHAR(50) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_by VARCHAR(50) DEFAULT NULL,
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_employee_contract_no (contract_no),
  KEY idx_contract_employee (employee_id),
  KEY idx_contract_period (contract_start,contract_end),
  KEY idx_contract_status (contract_status,approval_status),
  KEY idx_contract_department (department_code),
  KEY idx_contract_position (position_id),
  KEY idx_contract_previous (previous_contract_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

UPDATE sys_menu
   SET nav_act='employee_contract',
       main_table='erp_employee_contract',
       icon='fa-file-text',
       dt_table='Y',
       tampil='Y'
 WHERE url='employee-contract';

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id, g.level, 'Y',
       IF(g.level IN ('admin','system_administrator','hrd'),'Y','N'),
       IF(g.level IN ('admin','system_administrator','hrd'),'Y','N'),
       IF(g.level IN ('admin','system_administrator'),'Y','N'),
       'N'
  FROM sys_menu m
  JOIN sys_group_users g
  LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.level
 WHERE m.url='employee-contract'
   AND r.id IS NULL;
