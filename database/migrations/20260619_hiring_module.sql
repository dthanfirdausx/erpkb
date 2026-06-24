CREATE TABLE IF NOT EXISTS erp_hiring (
  id INT(11) NOT NULL AUTO_INCREMENT,
  hiring_no VARCHAR(30) NOT NULL,
  selection_result_id INT(11) DEFAULT NULL,
  applicant_id INT(11) NOT NULL,
  vacancy_id INT(11) DEFAULT NULL,
  hired_employee_id INT(11) DEFAULT NULL,
  hiring_date DATE NOT NULL,
  planned_join_date DATE DEFAULT NULL,
  actual_join_date DATE DEFAULT NULL,
  hiring_status ENUM('DRAFT','OFFER_PREPARED','OFFER_SENT','OFFER_ACCEPTED','PRE_EMPLOYMENT_CHECK','READY_TO_HIRE','HIRED','ONBOARDED','CANCELLED','DECLINED') NOT NULL DEFAULT 'DRAFT',
  offer_status ENUM('NOT_CREATED','DRAFT','SENT','NEGOTIATION','ACCEPTED','DECLINED','EXPIRED') NOT NULL DEFAULT 'NOT_CREATED',
  onboarding_status ENUM('NOT_STARTED','IN_PROGRESS','COMPLETED','BLOCKED') NOT NULL DEFAULT 'NOT_STARTED',
  contract_status ENUM('NOT_PREPARED','PREPARED','SIGNED','CANCELLED') NOT NULL DEFAULT 'NOT_PREPARED',
  document_status ENUM('PENDING','PARTIAL','COMPLETE','REJECTED') NOT NULL DEFAULT 'PENDING',
  medical_check_status ENUM('NOT_REQUIRED','PENDING','PASSED','FAILED') NOT NULL DEFAULT 'NOT_REQUIRED',
  background_check_status ENUM('NOT_REQUIRED','PENDING','PASSED','FAILED') NOT NULL DEFAULT 'NOT_REQUIRED',
  hiring_type ENUM('NEW_HIRE','REHIRE','INTERNAL_TRANSFER','CONTRACT_CONVERSION','TEMPORARY') NOT NULL DEFAULT 'NEW_HIRE',
  employment_type ENUM('PERMANENT','CONTRACT','DAILY_WORKER','INTERNSHIP','OUTSOURCE') NOT NULL DEFAULT 'PERMANENT',
  employee_group ENUM('DIRECTOR','MANAGER','STAFF','NON_STAFF','OPERATOR','CONTRACT','DAILY_WORKER','TRAINEE') NOT NULL DEFAULT 'STAFF',
  proposed_position_id INT(11) DEFAULT NULL,
  proposed_job_title_id INT(11) DEFAULT NULL,
  department_code CHAR(8) DEFAULT NULL,
  company_structure_id INT(11) DEFAULT NULL,
  work_location_id INT(11) DEFAULT NULL,
  cost_center_code VARCHAR(20) DEFAULT NULL,
  profit_center_code VARCHAR(20) DEFAULT NULL,
  pay_grade VARCHAR(30) DEFAULT NULL,
  salary_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
  currency CHAR(3) NOT NULL DEFAULT 'IDR',
  probation_months INT(11) NOT NULL DEFAULT 3,
  recruiter_employee_id INT(11) DEFAULT NULL,
  hiring_manager_employee_id INT(11) DEFAULT NULL,
  hr_pic_employee_id INT(11) DEFAULT NULL,
  offer_sent_date DATE DEFAULT NULL,
  offer_accepted_date DATE DEFAULT NULL,
  contract_signed_date DATE DEFAULT NULL,
  onboarding_start_date DATE DEFAULT NULL,
  onboarding_completed_date DATE DEFAULT NULL,
  checklist_total INT(11) NOT NULL DEFAULT 0,
  checklist_done INT(11) NOT NULL DEFAULT 0,
  cancellation_reason VARCHAR(255) DEFAULT NULL,
  decline_reason VARCHAR(255) DEFAULT NULL,
  offer_notes TEXT DEFAULT NULL,
  onboarding_notes TEXT DEFAULT NULL,
  hr_notes TEXT DEFAULT NULL,
  sap_reference VARCHAR(50) DEFAULT NULL,
  remarks TEXT DEFAULT NULL,
  created_by VARCHAR(50) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_by VARCHAR(50) DEFAULT NULL,
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_erp_hiring_no (hiring_no),
  KEY idx_erp_hiring_applicant (applicant_id),
  KEY idx_erp_hiring_selection (selection_result_id),
  KEY idx_erp_hiring_vacancy (vacancy_id),
  KEY idx_erp_hiring_date (hiring_date),
  KEY idx_erp_hiring_status (hiring_status,offer_status,onboarding_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE sys_menu
   SET nav_act='hiring',
       main_table='erp_hiring',
       icon='fa-handshake-o',
       dt_table='Y',
       tampil='Y'
 WHERE url='hiring';

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id,g.level,'Y',
       CASE WHEN g.level IN ('admin','system_administrator','hrd','manager_approver') THEN 'Y' ELSE 'N' END,
       CASE WHEN g.level IN ('admin','system_administrator','hrd','manager_approver') THEN 'Y' ELSE 'N' END,
       CASE WHEN g.level IN ('admin','system_administrator') THEN 'Y' ELSE 'N' END,
       'N'
  FROM sys_menu m JOIN sys_group_users g
 WHERE m.url='hiring'
   AND NOT EXISTS (SELECT 1 FROM sys_menu_role r WHERE r.id_menu=m.id AND r.group_level=g.level);

UPDATE sys_menu_role r
JOIN sys_menu m ON m.id=r.id_menu AND m.url='hiring'
   SET r.read_act='Y',
       r.insert_act=CASE WHEN r.group_level IN ('admin','system_administrator','hrd','manager_approver') THEN 'Y' ELSE 'N' END,
       r.update_act=CASE WHEN r.group_level IN ('admin','system_administrator','hrd','manager_approver') THEN 'Y' ELSE 'N' END,
       r.delete_act=CASE WHEN r.group_level IN ('admin','system_administrator') THEN 'Y' ELSE 'N' END,
       r.import_act='N';
