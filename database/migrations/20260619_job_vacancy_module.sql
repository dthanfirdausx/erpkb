CREATE TABLE IF NOT EXISTS erp_job_vacancy (
  id INT(11) NOT NULL AUTO_INCREMENT,
  vacancy_no VARCHAR(30) NOT NULL,
  vacancy_title VARCHAR(150) NOT NULL,
  manpower_plan_id INT(11) DEFAULT NULL,
  manpower_plan_detail_id INT(11) DEFAULT NULL,
  position_id INT(11) DEFAULT NULL,
  job_title_id INT(11) DEFAULT NULL,
  company_structure_id INT(11) DEFAULT NULL,
  department_code CHAR(8) DEFAULT NULL,
  cost_center_code VARCHAR(20) DEFAULT NULL,
  profit_center_code VARCHAR(20) DEFAULT NULL,
  work_location_id INT(11) DEFAULT NULL,
  vacancy_type ENUM('NEW_POSITION','REPLACEMENT','INTERNAL_TRANSFER','TEMPORARY','PROJECT') NOT NULL DEFAULT 'NEW_POSITION',
  employment_type ENUM('PERMANENT','CONTRACT','DAILY_WORKER','INTERNSHIP','OUTSOURCE') NOT NULL DEFAULT 'PERMANENT',
  employee_group ENUM('DIRECTOR','MANAGER','STAFF','NON_STAFF','OPERATOR','CONTRACT','DAILY_WORKER','TRAINEE') NOT NULL DEFAULT 'STAFF',
  pay_grade VARCHAR(30) DEFAULT NULL,
  priority ENUM('LOW','MEDIUM','HIGH','CRITICAL') NOT NULL DEFAULT 'MEDIUM',
  vacancy_status ENUM('DRAFT','OPEN','SCREENING','INTERVIEW','OFFER','HIRED','CANCELLED','CLOSED') NOT NULL DEFAULT 'DRAFT',
  headcount_requested DECIMAL(12,2) NOT NULL DEFAULT 1,
  headcount_approved DECIMAL(12,2) NOT NULL DEFAULT 0,
  headcount_filled DECIMAL(12,2) NOT NULL DEFAULT 0,
  salary_min DECIMAL(18,2) NOT NULL DEFAULT 0,
  salary_max DECIMAL(18,2) NOT NULL DEFAULT 0,
  currency CHAR(3) NOT NULL DEFAULT 'IDR',
  posting_date DATE DEFAULT NULL,
  closing_date DATE DEFAULT NULL,
  target_join_date DATE DEFAULT NULL,
  recruiter_employee_id INT(11) DEFAULT NULL,
  hiring_manager_employee_id INT(11) DEFAULT NULL,
  publish_internal ENUM('Y','N') NOT NULL DEFAULT 'Y',
  publish_external ENUM('Y','N') NOT NULL DEFAULT 'N',
  source_channel VARCHAR(100) DEFAULT NULL,
  applicant_count INT(11) NOT NULL DEFAULT 0,
  shortlisted_count INT(11) NOT NULL DEFAULT 0,
  interview_count INT(11) NOT NULL DEFAULT 0,
  offer_count INT(11) NOT NULL DEFAULT 0,
  hired_count INT(11) NOT NULL DEFAULT 0,
  job_description TEXT DEFAULT NULL,
  qualification_requirement TEXT DEFAULT NULL,
  responsibilities TEXT DEFAULT NULL,
  benefits TEXT DEFAULT NULL,
  cancellation_reason VARCHAR(255) DEFAULT NULL,
  sap_reference VARCHAR(50) DEFAULT NULL,
  remarks TEXT DEFAULT NULL,
  created_by VARCHAR(50) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_by VARCHAR(50) DEFAULT NULL,
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_erp_job_vacancy_no (vacancy_no),
  KEY idx_erp_job_vacancy_status (vacancy_status),
  KEY idx_erp_job_vacancy_period (posting_date, closing_date),
  KEY idx_erp_job_vacancy_org (department_code, position_id, job_title_id),
  KEY idx_erp_job_vacancy_plan (manpower_plan_id, manpower_plan_detail_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE sys_menu
   SET nav_act='job_vacancy',
       main_table='erp_job_vacancy',
       icon='fa-bullhorn',
       dt_table='Y',
       tampil='Y'
 WHERE url='job-vacancy';

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id,
       g.level,
       'Y',
       CASE WHEN g.level IN ('admin','system_administrator','hrd','manager_approver') THEN 'Y' ELSE 'N' END,
       CASE WHEN g.level IN ('admin','system_administrator','hrd','manager_approver') THEN 'Y' ELSE 'N' END,
       CASE WHEN g.level IN ('admin','system_administrator') THEN 'Y' ELSE 'N' END,
       'N'
  FROM sys_menu m
  JOIN sys_group_users g
 WHERE m.url='job-vacancy'
   AND NOT EXISTS (
     SELECT 1 FROM sys_menu_role r
      WHERE r.id_menu=m.id AND r.group_level=g.level
   );

UPDATE sys_menu_role r
JOIN sys_menu m ON m.id=r.id_menu AND m.url='job-vacancy'
   SET r.read_act='Y',
       r.insert_act=CASE WHEN r.group_level IN ('admin','system_administrator','hrd','manager_approver') THEN 'Y' ELSE 'N' END,
       r.update_act=CASE WHEN r.group_level IN ('admin','system_administrator','hrd','manager_approver') THEN 'Y' ELSE 'N' END,
       r.delete_act=CASE WHEN r.group_level IN ('admin','system_administrator') THEN 'Y' ELSE 'N' END,
       r.import_act='N';
