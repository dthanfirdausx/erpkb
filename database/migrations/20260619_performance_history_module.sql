CREATE TABLE IF NOT EXISTS erp_performance_history (
  id INT(11) NOT NULL AUTO_INCREMENT,
  history_no VARCHAR(30) NOT NULL,
  performance_appraisal_id INT(11) DEFAULT NULL,
  appraisal_no VARCHAR(30) DEFAULT NULL,
  employee_kpi_id INT(11) DEFAULT NULL,
  employee_kpi_no VARCHAR(30) DEFAULT NULL,
  employee_id INT(11) NOT NULL,
  employee_no VARCHAR(30) NOT NULL,
  full_name VARCHAR(150) NOT NULL,
  employee_group VARCHAR(50) DEFAULT NULL,
  department_code CHAR(8) DEFAULT NULL,
  job_title_id INT(11) DEFAULT NULL,
  cycle_year YEAR NOT NULL,
  appraisal_period ENUM('Q1','Q2','Q3','Q4','H1','H2','ANNUAL','PROBATION','SPECIAL') NOT NULL DEFAULT 'ANNUAL',
  appraisal_type ENUM('ANNUAL','MID_YEAR','PROBATION','PROJECT','SPECIAL') NOT NULL DEFAULT 'ANNUAL',
  appraisal_date DATE NOT NULL,
  appraiser_employee_id INT(11) DEFAULT NULL,
  hr_reviewer_employee_id INT(11) DEFAULT NULL,
  self_assessment_score DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  manager_kpi_score DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  competency_score DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  behavior_score DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  final_score DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  final_rating ENUM('A','B','C','D','E') NOT NULL DEFAULT 'C',
  appraisal_status VARCHAR(30) DEFAULT NULL,
  approval_status VARCHAR(30) DEFAULT NULL,
  history_status ENUM('ACTIVE','ARCHIVED','LOCKED','VOID') NOT NULL DEFAULT 'ACTIVE',
  audit_source ENUM('APPRAISAL_SNAPSHOT','APPROVAL_SNAPSHOT','MANUAL_IMPORT') NOT NULL DEFAULT 'APPRAISAL_SNAPSHOT',
  submitted_at DATETIME DEFAULT NULL,
  archived_at DATETIME DEFAULT NULL,
  development_plan TEXT DEFAULT NULL,
  reward_recommendation VARCHAR(150) DEFAULT NULL,
  improvement_required ENUM('Y','N') NOT NULL DEFAULT 'N',
  sap_reference VARCHAR(60) DEFAULT NULL,
  remarks TEXT DEFAULT NULL,
  created_by VARCHAR(50) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_by VARCHAR(50) DEFAULT NULL,
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_perf_history_no (history_no),
  KEY idx_perf_history_employee (employee_id,cycle_year,appraisal_period),
  KEY idx_perf_history_rating (final_rating,history_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_performance_history_detail (
  id INT(11) NOT NULL AUTO_INCREMENT,
  performance_history_id INT(11) NOT NULL,
  source_detail_id INT(11) DEFAULT NULL,
  line_no INT(11) NOT NULL,
  kpi_code VARCHAR(30) NOT NULL,
  kpi_name VARCHAR(150) NOT NULL,
  kpi_perspective VARCHAR(30) DEFAULT NULL,
  target_text VARCHAR(150) DEFAULT NULL,
  unit_of_measure VARCHAR(30) DEFAULT NULL,
  weight DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  actual_value DECIMAL(18,4) DEFAULT NULL,
  actual_text VARCHAR(150) DEFAULT NULL,
  self_score DECIMAL(8,2) DEFAULT NULL,
  manager_score DECIMAL(8,2) DEFAULT NULL,
  final_score DECIMAL(8,2) DEFAULT NULL,
  weighted_score DECIMAL(8,2) DEFAULT NULL,
  evidence_ref VARCHAR(150) DEFAULT NULL,
  appraiser_comment TEXT DEFAULT NULL,
  remarks TEXT DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_perf_history_detail_header (performance_history_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

UPDATE sys_menu
   SET nav_act='performance_history',
       main_table='erp_performance_history',
       icon='fa-history',
       dt_table='Y',
       tampil='Y'
 WHERE url='performance-history';

INSERT INTO sys_menu_role (id_menu,group_level,read_act,insert_act,update_act,delete_act,import_act)
SELECT m.id,g.level,'Y',
       CASE WHEN g.level IN ('admin','system_administrator','hrd') THEN 'Y' ELSE 'N' END,
       CASE WHEN g.level IN ('admin','system_administrator','hrd') THEN 'Y' ELSE 'N' END,
       CASE WHEN g.level IN ('admin','system_administrator') THEN 'Y' ELSE 'N' END,
       'N'
  FROM sys_menu m
  JOIN sys_group_users g
 WHERE m.url='performance-history'
   AND NOT EXISTS (SELECT 1 FROM sys_menu_role r WHERE r.id_menu=m.id AND r.group_level=g.level);

UPDATE erp_performance_history
   SET created_by='admin', updated_by='admin'
 WHERE COALESCE(created_by,'')<>'admin' OR COALESCE(updated_by,'')<>'admin';

INSERT INTO erp_performance_history (
  history_no,performance_appraisal_id,appraisal_no,employee_kpi_id,employee_kpi_no,employee_id,employee_no,full_name,employee_group,
  department_code,job_title_id,cycle_year,appraisal_period,appraisal_type,appraisal_date,appraiser_employee_id,hr_reviewer_employee_id,
  self_assessment_score,manager_kpi_score,competency_score,behavior_score,final_score,final_rating,appraisal_status,approval_status,
  history_status,audit_source,submitted_at,archived_at,development_plan,reward_recommendation,improvement_required,sap_reference,remarks,
  created_by,created_at,updated_by,updated_at
)
SELECT CONCAT('PHIST-',pa.appraisal_no),pa.id,pa.appraisal_no,pa.employee_kpi_id,ek.employee_kpi_no,pa.employee_id,e.employee_no,e.full_name,e.employee_group,
       pa.department_code,pa.job_title_id,pa.cycle_year,pa.appraisal_period,pa.appraisal_type,pa.appraisal_date,pa.appraiser_employee_id,pa.hr_reviewer_employee_id,
       pa.self_assessment_score,pa.manager_kpi_score,pa.competency_score,pa.behavior_score,pa.final_score,pa.final_rating,pa.appraisal_status,pa.approval_status,
       IF(pa.appraisal_status='APPROVED','LOCKED','ACTIVE'),'APPRAISAL_SNAPSHOT',pa.submitted_at,NOW(),pa.development_plan,pa.reward_recommendation,pa.improvement_required,pa.sap_reference,pa.remarks,
       'admin',NOW(),'admin',NOW()
  FROM erp_performance_appraisal pa
  JOIN erp_employee_master e ON e.id=pa.employee_id
  LEFT JOIN erp_employee_kpi ek ON ek.id=pa.employee_kpi_id
 WHERE NOT EXISTS (SELECT 1 FROM erp_performance_history h WHERE h.performance_appraisal_id=pa.id);

INSERT INTO erp_performance_history_detail (
  performance_history_id,source_detail_id,line_no,kpi_code,kpi_name,kpi_perspective,target_text,unit_of_measure,weight,actual_value,actual_text,
  self_score,manager_score,final_score,weighted_score,evidence_ref,appraiser_comment,remarks
)
SELECT h.id,d.id,d.line_no,d.kpi_code,d.kpi_name,d.kpi_perspective,d.target_text,d.unit_of_measure,d.weight,d.actual_value,d.actual_text,
       d.self_score,d.manager_score,d.final_score,d.weighted_score,d.evidence_ref,d.appraiser_comment,d.remarks
  FROM erp_performance_history h
  JOIN erp_performance_appraisal_detail d ON d.performance_appraisal_id=h.performance_appraisal_id
 WHERE NOT EXISTS (SELECT 1 FROM erp_performance_history_detail x WHERE x.performance_history_id=h.id);
