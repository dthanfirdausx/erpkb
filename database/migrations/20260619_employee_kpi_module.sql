CREATE TABLE IF NOT EXISTS erp_employee_kpi (
  id INT(11) NOT NULL AUTO_INCREMENT,
  employee_kpi_no VARCHAR(30) NOT NULL,
  employee_id INT(11) NOT NULL,
  kpi_template_id INT(11) NOT NULL,
  cycle_year YEAR NOT NULL,
  appraisal_period ENUM('Q1','Q2','Q3','Q4','H1','H2','ANNUAL','PROBATION','SPECIAL') NOT NULL DEFAULT 'ANNUAL',
  assignment_type ENUM('ANNUAL','MID_YEAR','PROBATION','PROJECT','SPECIAL') NOT NULL DEFAULT 'ANNUAL',
  department_code CHAR(8) DEFAULT NULL,
  job_title_id INT(11) DEFAULT NULL,
  appraiser_employee_id INT(11) DEFAULT NULL,
  hr_reviewer_employee_id INT(11) DEFAULT NULL,
  effective_from DATE NOT NULL,
  effective_to DATE NOT NULL DEFAULT '9999-12-31',
  total_weight DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  target_status ENUM('DRAFT','ASSIGNED','ACKNOWLEDGED','IN_REVIEW','COMPLETED','CANCELLED') NOT NULL DEFAULT 'DRAFT',
  approval_status ENUM('NOT_REQUIRED','PENDING','APPROVED','REJECTED','RETURNED') NOT NULL DEFAULT 'PENDING',
  acknowledged_at DATETIME DEFAULT NULL,
  approved_by VARCHAR(50) DEFAULT NULL,
  approved_at DATETIME DEFAULT NULL,
  sap_reference VARCHAR(60) DEFAULT NULL,
  remarks TEXT DEFAULT NULL,
  created_by VARCHAR(50) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_by VARCHAR(50) DEFAULT NULL,
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_employee_kpi_no (employee_kpi_no),
  KEY idx_employee_kpi_employee (employee_id,cycle_year,appraisal_period),
  KEY idx_employee_kpi_status (target_status,approval_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_employee_kpi_detail (
  id INT(11) NOT NULL AUTO_INCREMENT,
  employee_kpi_id INT(11) NOT NULL,
  template_detail_id INT(11) DEFAULT NULL,
  line_no INT(11) NOT NULL,
  kpi_code VARCHAR(30) NOT NULL,
  kpi_name VARCHAR(150) NOT NULL,
  kpi_perspective ENUM('FINANCIAL','CUSTOMER','PROCESS','PEOPLE','QUALITY','SAFETY','COMPLIANCE','INNOVATION') NOT NULL DEFAULT 'PROCESS',
  measure_type ENUM('QUANTITATIVE','QUALITATIVE','MILESTONE') NOT NULL DEFAULT 'QUANTITATIVE',
  target_value DECIMAL(18,4) DEFAULT NULL,
  target_text VARCHAR(150) DEFAULT NULL,
  unit_of_measure VARCHAR(30) DEFAULT NULL,
  weight DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  scoring_direction ENUM('HIGHER_BETTER','LOWER_BETTER','TARGET_BETTER','MILESTONE') NOT NULL DEFAULT 'HIGHER_BETTER',
  minimum_score DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  maximum_score DECIMAL(8,2) NOT NULL DEFAULT 100.00,
  data_source VARCHAR(120) DEFAULT NULL,
  review_frequency ENUM('DAILY','WEEKLY','MONTHLY','QUARTERLY','HALF_YEAR','ANNUAL','ON_DEMAND') NOT NULL DEFAULT 'MONTHLY',
  mandatory ENUM('Y','N') NOT NULL DEFAULT 'Y',
  actual_value DECIMAL(18,4) DEFAULT NULL,
  actual_text VARCHAR(150) DEFAULT NULL,
  score DECIMAL(8,2) DEFAULT NULL,
  weighted_score DECIMAL(8,2) DEFAULT NULL,
  remarks TEXT DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_employee_kpi_detail_header (employee_kpi_id),
  KEY idx_employee_kpi_detail_code (kpi_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

UPDATE sys_menu
   SET nav_act='employee_kpi',
       main_table='erp_employee_kpi',
       icon='fa-tasks',
       dt_table='Y',
       tampil='Y'
 WHERE url='employee-kpi';

INSERT INTO sys_menu_role (id_menu,group_level,read_act,insert_act,update_act,delete_act,import_act)
SELECT m.id,g.level,'Y',
       CASE WHEN g.level IN ('admin','system_administrator','hrd','manager_approver') THEN 'Y' ELSE 'N' END,
       CASE WHEN g.level IN ('admin','system_administrator','hrd','manager_approver') THEN 'Y' ELSE 'N' END,
       CASE WHEN g.level IN ('admin','system_administrator') THEN 'Y' ELSE 'N' END,
       'N'
  FROM sys_menu m
  JOIN sys_group_users g
 WHERE m.url='employee-kpi'
   AND NOT EXISTS (SELECT 1 FROM sys_menu_role r WHERE r.id_menu=m.id AND r.group_level=g.level);

UPDATE erp_employee_kpi
   SET created_by='admin',
       updated_by='admin'
 WHERE COALESCE(created_by,'')<>'admin'
    OR COALESCE(updated_by,'')<>'admin';

INSERT INTO erp_employee_kpi (
  employee_kpi_no,employee_id,kpi_template_id,cycle_year,appraisal_period,assignment_type,department_code,job_title_id,
  appraiser_employee_id,hr_reviewer_employee_id,effective_from,effective_to,total_weight,target_status,approval_status,
  acknowledged_at,approved_by,approved_at,sap_reference,remarks,created_by,created_at,updated_by,updated_at
)
SELECT 'EKPI-2026-EMP-0007',e.id,t.id,2026,'ANNUAL','ANNUAL',e.department_code,e.job_title_id,e.manager_employee_id,hr.id,
       '2026-01-01','2026-12-31',t.total_weight,'ASSIGNED','APPROVED','2026-06-19 09:30:00','admin','2026-06-19 09:35:00',
       'SAP-HCM-EKPI-2026-EMP-0007','Dummy employee KPI assignment operator produksi.','admin','2026-06-19 09:30:00','admin','2026-06-19 09:35:00'
  FROM erp_employee_master e
  JOIN erp_kpi_template t ON t.template_no='KPI-TPL-PRD-OPR-2026'
  LEFT JOIN erp_employee_master hr ON hr.employee_no='EMP-0003'
 WHERE e.employee_no='EMP-0007'
   AND NOT EXISTS (SELECT 1 FROM erp_employee_kpi x WHERE x.employee_kpi_no='EKPI-2026-EMP-0007');

INSERT INTO erp_employee_kpi_detail (
  employee_kpi_id,template_detail_id,line_no,kpi_code,kpi_name,kpi_perspective,measure_type,target_value,target_text,unit_of_measure,
  weight,scoring_direction,minimum_score,maximum_score,data_source,review_frequency,mandatory,remarks
)
SELECT ek.id,td.id,td.line_no,td.kpi_code,td.kpi_name,td.kpi_perspective,td.measure_type,td.target_value,td.target_text,td.unit_of_measure,
       td.weight,td.scoring_direction,td.minimum_score,td.maximum_score,td.data_source,td.review_frequency,td.mandatory,td.remarks
  FROM erp_employee_kpi ek
  JOIN erp_kpi_template_detail td ON td.kpi_template_id=ek.kpi_template_id
 WHERE ek.employee_kpi_no='EKPI-2026-EMP-0007'
   AND NOT EXISTS (SELECT 1 FROM erp_employee_kpi_detail d WHERE d.employee_kpi_id=ek.id);

INSERT INTO erp_employee_kpi (
  employee_kpi_no,employee_id,kpi_template_id,cycle_year,appraisal_period,assignment_type,department_code,job_title_id,
  appraiser_employee_id,hr_reviewer_employee_id,effective_from,effective_to,total_weight,target_status,approval_status,
  acknowledged_at,approved_by,approved_at,sap_reference,remarks,created_by,created_at,updated_by,updated_at
)
SELECT 'EKPI-2026-EMP-0009',e.id,t.id,2026,'ANNUAL','ANNUAL',e.department_code,e.job_title_id,e.manager_employee_id,hr.id,
       '2026-01-01','2026-12-31',t.total_weight,'ACKNOWLEDGED','APPROVED','2026-06-19 10:00:00','admin','2026-06-19 10:05:00',
       'SAP-HCM-EKPI-2026-EMP-0009','Dummy employee KPI assignment warehouse.','admin','2026-06-19 10:00:00','admin','2026-06-19 10:05:00'
  FROM erp_employee_master e
  JOIN erp_kpi_template t ON t.template_no='KPI-TPL-WH-STF-2026'
  LEFT JOIN erp_employee_master hr ON hr.employee_no='EMP-0003'
 WHERE e.employee_no='EMP-0009'
   AND NOT EXISTS (SELECT 1 FROM erp_employee_kpi x WHERE x.employee_kpi_no='EKPI-2026-EMP-0009');

INSERT INTO erp_employee_kpi_detail (
  employee_kpi_id,template_detail_id,line_no,kpi_code,kpi_name,kpi_perspective,measure_type,target_value,target_text,unit_of_measure,
  weight,scoring_direction,minimum_score,maximum_score,data_source,review_frequency,mandatory,remarks
)
SELECT ek.id,td.id,td.line_no,td.kpi_code,td.kpi_name,td.kpi_perspective,td.measure_type,td.target_value,td.target_text,td.unit_of_measure,
       td.weight,td.scoring_direction,td.minimum_score,td.maximum_score,td.data_source,td.review_frequency,td.mandatory,td.remarks
  FROM erp_employee_kpi ek
  JOIN erp_kpi_template_detail td ON td.kpi_template_id=ek.kpi_template_id
 WHERE ek.employee_kpi_no='EKPI-2026-EMP-0009'
   AND NOT EXISTS (SELECT 1 FROM erp_employee_kpi_detail d WHERE d.employee_kpi_id=ek.id);
