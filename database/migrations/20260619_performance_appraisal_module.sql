CREATE TABLE IF NOT EXISTS erp_performance_appraisal (
  id INT(11) NOT NULL AUTO_INCREMENT,
  appraisal_no VARCHAR(30) NOT NULL,
  employee_kpi_id INT(11) NOT NULL,
  employee_id INT(11) NOT NULL,
  cycle_year YEAR NOT NULL,
  appraisal_period ENUM('Q1','Q2','Q3','Q4','H1','H2','ANNUAL','PROBATION','SPECIAL') NOT NULL DEFAULT 'ANNUAL',
  appraisal_type ENUM('ANNUAL','MID_YEAR','PROBATION','PROJECT','SPECIAL') NOT NULL DEFAULT 'ANNUAL',
  department_code CHAR(8) DEFAULT NULL,
  job_title_id INT(11) DEFAULT NULL,
  appraiser_employee_id INT(11) DEFAULT NULL,
  hr_reviewer_employee_id INT(11) DEFAULT NULL,
  appraisal_date DATE NOT NULL,
  self_assessment_score DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  manager_kpi_score DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  competency_score DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  behavior_score DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  final_score DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  final_rating ENUM('A','B','C','D','E') NOT NULL DEFAULT 'C',
  appraisal_status ENUM('DRAFT','SELF_ASSESSED','MANAGER_REVIEW','SUBMITTED','APPROVED','REJECTED','RETURNED','CANCELLED') NOT NULL DEFAULT 'DRAFT',
  approval_status ENUM('NOT_SUBMITTED','PENDING','APPROVED','REJECTED','RETURNED') NOT NULL DEFAULT 'NOT_SUBMITTED',
  submitted_at DATETIME DEFAULT NULL,
  appraisal_approval_id INT(11) DEFAULT NULL,
  employee_comment TEXT DEFAULT NULL,
  manager_comment TEXT DEFAULT NULL,
  hr_comment TEXT DEFAULT NULL,
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
  UNIQUE KEY uq_perf_appraisal_no (appraisal_no),
  KEY idx_perf_appraisal_employee (employee_id,cycle_year,appraisal_period),
  KEY idx_perf_appraisal_status (appraisal_status,approval_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_performance_appraisal_detail (
  id INT(11) NOT NULL AUTO_INCREMENT,
  performance_appraisal_id INT(11) NOT NULL,
  employee_kpi_detail_id INT(11) DEFAULT NULL,
  line_no INT(11) NOT NULL,
  kpi_code VARCHAR(30) NOT NULL,
  kpi_name VARCHAR(150) NOT NULL,
  kpi_perspective ENUM('FINANCIAL','CUSTOMER','PROCESS','PEOPLE','QUALITY','SAFETY','COMPLIANCE','INNOVATION') NOT NULL DEFAULT 'PROCESS',
  target_text VARCHAR(150) DEFAULT NULL,
  unit_of_measure VARCHAR(30) DEFAULT NULL,
  weight DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  scoring_direction ENUM('HIGHER_BETTER','LOWER_BETTER','TARGET_BETTER','MILESTONE') NOT NULL DEFAULT 'HIGHER_BETTER',
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
  KEY idx_perf_appraisal_detail_header (performance_appraisal_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

UPDATE sys_menu
   SET nav_act='performance_appraisal',
       main_table='erp_performance_appraisal',
       icon='fa-star-half-o',
       dt_table='Y',
       tampil='Y'
 WHERE url='performance-appraisal';

INSERT INTO sys_menu_role (id_menu,group_level,read_act,insert_act,update_act,delete_act,import_act)
SELECT m.id,g.level,'Y',
       CASE WHEN g.level IN ('admin','system_administrator','hrd','manager_approver') THEN 'Y' ELSE 'N' END,
       CASE WHEN g.level IN ('admin','system_administrator','hrd','manager_approver') THEN 'Y' ELSE 'N' END,
       CASE WHEN g.level IN ('admin','system_administrator') THEN 'Y' ELSE 'N' END,
       'N'
  FROM sys_menu m
  JOIN sys_group_users g
 WHERE m.url='performance-appraisal'
   AND NOT EXISTS (SELECT 1 FROM sys_menu_role r WHERE r.id_menu=m.id AND r.group_level=g.level);

UPDATE erp_performance_appraisal
   SET created_by='admin',
       updated_by='admin'
 WHERE COALESCE(created_by,'')<>'admin'
    OR COALESCE(updated_by,'')<>'admin';

INSERT INTO erp_performance_appraisal (
  appraisal_no,employee_kpi_id,employee_id,cycle_year,appraisal_period,appraisal_type,department_code,job_title_id,
  appraiser_employee_id,hr_reviewer_employee_id,appraisal_date,self_assessment_score,manager_kpi_score,competency_score,
  behavior_score,final_score,final_rating,appraisal_status,approval_status,submitted_at,employee_comment,manager_comment,
  hr_comment,development_plan,reward_recommendation,improvement_required,sap_reference,remarks,created_by,created_at,updated_by,updated_at
)
SELECT 'PAPP-2026-EMP-0007',ek.id,ek.employee_id,ek.cycle_year,ek.appraisal_period,'ANNUAL',ek.department_code,ek.job_title_id,
       ek.appraiser_employee_id,ek.hr_reviewer_employee_id,'2026-06-19',84,86,82,88,85.00,'B','SUBMITTED','PENDING','2026-06-19 11:00:00',
       'Saya sudah mencapai target utama produksi dan siap improvement berikutnya.',
       'Performance stabil, perlu peningkatan dokumentasi output.',
       'Menunggu approval final.',
       'Coaching line balancing dan quality gate.',
       'Merit increase candidate','N','SAP-HCM-PAPP-2026-EMP-0007','Dummy performance appraisal operator produksi.',
       'admin','2026-06-19 11:00:00','admin','2026-06-19 11:00:00'
  FROM erp_employee_kpi ek
  JOIN erp_employee_master e ON e.id=ek.employee_id
 WHERE e.employee_no='EMP-0007'
   AND NOT EXISTS (SELECT 1 FROM erp_performance_appraisal x WHERE x.appraisal_no='PAPP-2026-EMP-0007');

INSERT INTO erp_performance_appraisal_detail (
  performance_appraisal_id,employee_kpi_detail_id,line_no,kpi_code,kpi_name,kpi_perspective,target_text,unit_of_measure,
  weight,scoring_direction,actual_value,actual_text,self_score,manager_score,final_score,weighted_score,evidence_ref,appraiser_comment,remarks
)
SELECT pa.id,ed.id,ed.line_no,ed.kpi_code,ed.kpi_name,ed.kpi_perspective,ed.target_text,ed.unit_of_measure,
       ed.weight,ed.scoring_direction,NULL,'Actual sesuai review supervisor',85,86,86,ROUND((86*ed.weight)/100,2),'Production report','Review supervisor selesai',ed.remarks
  FROM erp_performance_appraisal pa
  JOIN erp_employee_kpi_detail ed ON ed.employee_kpi_id=pa.employee_kpi_id
 WHERE pa.appraisal_no='PAPP-2026-EMP-0007'
   AND NOT EXISTS (SELECT 1 FROM erp_performance_appraisal_detail d WHERE d.performance_appraisal_id=pa.id);

INSERT INTO erp_performance_appraisal (
  appraisal_no,employee_kpi_id,employee_id,cycle_year,appraisal_period,appraisal_type,department_code,job_title_id,
  appraiser_employee_id,hr_reviewer_employee_id,appraisal_date,self_assessment_score,manager_kpi_score,competency_score,
  behavior_score,final_score,final_rating,appraisal_status,approval_status,submitted_at,employee_comment,manager_comment,
  hr_comment,development_plan,reward_recommendation,improvement_required,sap_reference,remarks,created_by,created_at,updated_by,updated_at
)
SELECT 'PAPP-2026-EMP-0009',ek.id,ek.employee_id,ek.cycle_year,ek.appraisal_period,'ANNUAL',ek.department_code,ek.job_title_id,
       ek.appraiser_employee_id,ek.hr_reviewer_employee_id,'2026-06-19',88,90,86,90,88.80,'B','MANAGER_REVIEW','NOT_SUBMITTED',NULL,
       'Inventory accuracy dan GR SLA berjalan baik.',
       'Warehouse KPI sangat baik, perlu finalisasi evidence.',
       '',
       'Advanced inventory control dan customs traceability refreshment.',
       'Promotion pool','N','SAP-HCM-PAPP-2026-EMP-0009','Dummy performance appraisal warehouse.',
       'admin','2026-06-19 11:10:00','admin','2026-06-19 11:10:00'
  FROM erp_employee_kpi ek
  JOIN erp_employee_master e ON e.id=ek.employee_id
 WHERE e.employee_no='EMP-0009'
   AND NOT EXISTS (SELECT 1 FROM erp_performance_appraisal x WHERE x.appraisal_no='PAPP-2026-EMP-0009');

INSERT INTO erp_performance_appraisal_detail (
  performance_appraisal_id,employee_kpi_detail_id,line_no,kpi_code,kpi_name,kpi_perspective,target_text,unit_of_measure,
  weight,scoring_direction,actual_value,actual_text,self_score,manager_score,final_score,weighted_score,evidence_ref,appraiser_comment,remarks
)
SELECT pa.id,ed.id,ed.line_no,ed.kpi_code,ed.kpi_name,ed.kpi_perspective,ed.target_text,ed.unit_of_measure,
       ed.weight,ed.scoring_direction,NULL,'Actual warehouse review',88,90,90,ROUND((90*ed.weight)/100,2),'Warehouse report','Review manager warehouse',ed.remarks
  FROM erp_performance_appraisal pa
  JOIN erp_employee_kpi_detail ed ON ed.employee_kpi_id=pa.employee_kpi_id
 WHERE pa.appraisal_no='PAPP-2026-EMP-0009'
   AND NOT EXISTS (SELECT 1 FROM erp_performance_appraisal_detail d WHERE d.performance_appraisal_id=pa.id);
