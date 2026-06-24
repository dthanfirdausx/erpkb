CREATE TABLE IF NOT EXISTS erp_appraisal_approval (
  id INT(11) NOT NULL AUTO_INCREMENT,
  appraisal_no VARCHAR(30) NOT NULL,
  cycle_year YEAR NOT NULL,
  appraisal_period ENUM('Q1','Q2','Q3','Q4','H1','H2','ANNUAL','PROBATION','SPECIAL') NOT NULL DEFAULT 'ANNUAL',
  appraisal_type ENUM('ANNUAL','MID_YEAR','PROBATION','PROJECT','SPECIAL') NOT NULL DEFAULT 'ANNUAL',
  employee_id INT(11) NOT NULL,
  appraiser_employee_id INT(11) NOT NULL,
  second_appraiser_employee_id INT(11) DEFAULT NULL,
  hr_reviewer_employee_id INT(11) DEFAULT NULL,
  department_code CHAR(8) DEFAULT NULL,
  job_title_id INT(11) DEFAULT NULL,
  appraisal_date DATE NOT NULL,
  submitted_at DATETIME DEFAULT NULL,
  kpi_score DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  competency_score DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  behavior_score DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  final_score DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  final_rating ENUM('A','B','C','D','E') NOT NULL DEFAULT 'C',
  calibration_status ENUM('DRAFT','SUBMITTED','MANAGER_APPROVED','HR_REVIEW','APPROVED','REJECTED','RETURNED','CANCELLED') NOT NULL DEFAULT 'DRAFT',
  approval_level ENUM('MANAGER','SECOND_MANAGER','HR','FINAL') NOT NULL DEFAULT 'MANAGER',
  decision ENUM('PENDING','APPROVED','REJECTED','RETURNED') NOT NULL DEFAULT 'PENDING',
  decision_by VARCHAR(50) DEFAULT NULL,
  decision_at DATETIME DEFAULT NULL,
  manager_comment TEXT DEFAULT NULL,
  hr_comment TEXT DEFAULT NULL,
  employee_comment TEXT DEFAULT NULL,
  development_plan TEXT DEFAULT NULL,
  reward_recommendation VARCHAR(150) DEFAULT NULL,
  improvement_required ENUM('Y','N') NOT NULL DEFAULT 'N',
  remarks TEXT DEFAULT NULL,
  created_by VARCHAR(50) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_by VARCHAR(50) DEFAULT NULL,
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_appraisal_no (appraisal_no),
  KEY idx_appraisal_employee (employee_id),
  KEY idx_appraisal_status (calibration_status, decision),
  KEY idx_appraisal_cycle (cycle_year, appraisal_period),
  KEY idx_appraisal_department (department_code),
  KEY idx_appraisal_date (appraisal_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

UPDATE sys_menu
   SET nav_act='appraisal_approval',
       main_table='erp_appraisal_approval',
       icon='fa-check-square',
       dt_table='Y',
       tampil='Y'
 WHERE url='appraisal-approval';

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id, g.level, 'Y',
       CASE WHEN g.level IN ('admin','system_administrator','hrd','manager_approver') THEN 'Y' ELSE 'N' END,
       CASE WHEN g.level IN ('admin','system_administrator','hrd','manager_approver') THEN 'Y' ELSE 'N' END,
       CASE WHEN g.level IN ('admin','system_administrator') THEN 'Y' ELSE 'N' END,
       'N'
  FROM sys_menu m
  JOIN sys_group_users g ON g.level IN ('admin','system_administrator','hrd','manager_approver','auditor')
 WHERE m.url='appraisal-approval'
   AND NOT EXISTS (
     SELECT 1 FROM sys_menu_role r WHERE r.id_menu=m.id AND r.group_level=g.level
   );
