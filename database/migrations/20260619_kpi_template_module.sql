CREATE TABLE IF NOT EXISTS erp_kpi_template (
  id INT(11) NOT NULL AUTO_INCREMENT,
  template_no VARCHAR(30) NOT NULL,
  template_name VARCHAR(150) NOT NULL,
  template_type ENUM('CORPORATE','DEPARTMENT','JOB_TITLE','INDIVIDUAL','PROJECT') NOT NULL DEFAULT 'JOB_TITLE',
  cycle_type ENUM('MONTHLY','QUARTERLY','HALF_YEAR','ANNUAL','PROBATION','PROJECT') NOT NULL DEFAULT 'ANNUAL',
  appraisal_period ENUM('Q1','Q2','Q3','Q4','H1','H2','ANNUAL','PROBATION','SPECIAL') NOT NULL DEFAULT 'ANNUAL',
  department_code CHAR(8) DEFAULT NULL,
  job_title_id INT(11) DEFAULT NULL,
  employee_group ENUM('DIRECTOR','MANAGER','STAFF','NON_STAFF','OPERATOR','CONTRACT','DAILY_WORKER','TRAINEE','ALL') NOT NULL DEFAULT 'ALL',
  effective_from DATE NOT NULL,
  effective_to DATE NOT NULL DEFAULT '9999-12-31',
  total_weight DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  status ENUM('DRAFT','ACTIVE','INACTIVE','ARCHIVED') NOT NULL DEFAULT 'DRAFT',
  approval_required ENUM('Y','N') NOT NULL DEFAULT 'Y',
  calibration_required ENUM('Y','N') NOT NULL DEFAULT 'Y',
  owner_employee_id INT(11) DEFAULT NULL,
  sap_reference VARCHAR(60) DEFAULT NULL,
  remarks TEXT DEFAULT NULL,
  created_by VARCHAR(50) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_by VARCHAR(50) DEFAULT NULL,
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_kpi_template_no (template_no),
  KEY idx_kpi_template_scope (department_code,job_title_id,status),
  KEY idx_kpi_template_validity (effective_from,effective_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_kpi_template_detail (
  id INT(11) NOT NULL AUTO_INCREMENT,
  kpi_template_id INT(11) NOT NULL,
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
  remarks TEXT DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_kpi_template_detail_header (kpi_template_id),
  KEY idx_kpi_template_detail_code (kpi_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

UPDATE sys_menu
   SET nav_act='kpi_template',
       main_table='erp_kpi_template',
       icon='fa-bullseye',
       dt_table='Y',
       tampil='Y'
 WHERE url='kpi-template';

INSERT INTO sys_menu_role (id_menu,group_level,read_act,insert_act,update_act,delete_act,import_act)
SELECT m.id,g.level,'Y',
       CASE WHEN g.level IN ('admin','system_administrator','hrd','manager_approver') THEN 'Y' ELSE 'N' END,
       CASE WHEN g.level IN ('admin','system_administrator','hrd','manager_approver') THEN 'Y' ELSE 'N' END,
       CASE WHEN g.level IN ('admin','system_administrator') THEN 'Y' ELSE 'N' END,
       'N'
  FROM sys_menu m
  JOIN sys_group_users g
 WHERE m.url='kpi-template'
   AND NOT EXISTS (SELECT 1 FROM sys_menu_role r WHERE r.id_menu=m.id AND r.group_level=g.level);

UPDATE erp_kpi_template
   SET created_by='admin',
       updated_by='admin'
 WHERE COALESCE(created_by,'')<>'admin'
    OR COALESCE(updated_by,'')<>'admin';

INSERT INTO erp_kpi_template (
  template_no,template_name,template_type,cycle_type,appraisal_period,department_code,job_title_id,employee_group,
  effective_from,effective_to,total_weight,status,approval_required,calibration_required,owner_employee_id,sap_reference,remarks,
  created_by,created_at,updated_by,updated_at
)
SELECT 'KPI-TPL-PRD-OPR-2026','Production Operator KPI Template','JOB_TITLE','MONTHLY','ANNUAL',jt.department_code,jt.id,'OPERATOR',
       '2026-01-01','2026-12-31',100,'ACTIVE','Y','Y',mgr.id,'SAP-HCM-KPI-PRD-OPR-2026','Dummy KPI template untuk operator produksi.',
       'admin','2026-06-19 09:00:00','admin','2026-06-19 09:00:00'
  FROM erp_job_title jt
  LEFT JOIN erp_employee_master mgr ON mgr.employee_no='EMP-0004'
 WHERE jt.job_title_code='JT-MIX-OPR'
   AND NOT EXISTS (SELECT 1 FROM erp_kpi_template x WHERE x.template_no='KPI-TPL-PRD-OPR-2026');

INSERT INTO erp_kpi_template_detail (kpi_template_id,line_no,kpi_code,kpi_name,kpi_perspective,measure_type,target_value,target_text,unit_of_measure,weight,scoring_direction,data_source,review_frequency,mandatory,remarks)
SELECT t.id,1,'KPI-OUTPUT','Output Quantity Achievement','PROCESS','QUANTITATIVE',100.0000,'Min 100% dari target produksi','%',35,'HIGHER_BETTER','Production confirmation','MONTHLY','Y','Output sesuai target produksi'
  FROM erp_kpi_template t WHERE t.template_no='KPI-TPL-PRD-OPR-2026'
    AND NOT EXISTS (SELECT 1 FROM erp_kpi_template_detail d WHERE d.kpi_template_id=t.id AND d.kpi_code='KPI-OUTPUT');
INSERT INTO erp_kpi_template_detail (kpi_template_id,line_no,kpi_code,kpi_name,kpi_perspective,measure_type,target_value,target_text,unit_of_measure,weight,scoring_direction,data_source,review_frequency,mandatory,remarks)
SELECT t.id,2,'KPI-DEFECT','Defect Rate','QUALITY','QUANTITATIVE',2.0000,'Maksimal defect 2%','%',25,'LOWER_BETTER','Quality inspection','MONTHLY','Y','Kualitas output produksi'
  FROM erp_kpi_template t WHERE t.template_no='KPI-TPL-PRD-OPR-2026'
    AND NOT EXISTS (SELECT 1 FROM erp_kpi_template_detail d WHERE d.kpi_template_id=t.id AND d.kpi_code='KPI-DEFECT');
INSERT INTO erp_kpi_template_detail (kpi_template_id,line_no,kpi_code,kpi_name,kpi_perspective,measure_type,target_value,target_text,unit_of_measure,weight,scoring_direction,data_source,review_frequency,mandatory,remarks)
SELECT t.id,3,'KPI-SAFETY','Safety Compliance','SAFETY','QUANTITATIVE',0.0000,'Zero accident','case',20,'LOWER_BETTER','HSE report','MONTHLY','Y','Kepatuhan keselamatan kerja'
  FROM erp_kpi_template t WHERE t.template_no='KPI-TPL-PRD-OPR-2026'
    AND NOT EXISTS (SELECT 1 FROM erp_kpi_template_detail d WHERE d.kpi_template_id=t.id AND d.kpi_code='KPI-SAFETY');
INSERT INTO erp_kpi_template_detail (kpi_template_id,line_no,kpi_code,kpi_name,kpi_perspective,measure_type,target_value,target_text,unit_of_measure,weight,scoring_direction,data_source,review_frequency,mandatory,remarks)
SELECT t.id,4,'KPI-ATTEND','Attendance Discipline','PEOPLE','QUANTITATIVE',98.0000,'Minimal attendance 98%','%',20,'HIGHER_BETTER','Attendance','MONTHLY','Y','Disiplin kehadiran'
  FROM erp_kpi_template t WHERE t.template_no='KPI-TPL-PRD-OPR-2026'
    AND NOT EXISTS (SELECT 1 FROM erp_kpi_template_detail d WHERE d.kpi_template_id=t.id AND d.kpi_code='KPI-ATTEND');

INSERT INTO erp_kpi_template (
  template_no,template_name,template_type,cycle_type,appraisal_period,department_code,job_title_id,employee_group,
  effective_from,effective_to,total_weight,status,approval_required,calibration_required,owner_employee_id,sap_reference,remarks,
  created_by,created_at,updated_by,updated_at
)
SELECT 'KPI-TPL-WH-STF-2026','Warehouse Staff KPI Template','JOB_TITLE','MONTHLY','ANNUAL',jt.department_code,jt.id,'STAFF',
       '2026-01-01','2026-12-31',100,'ACTIVE','Y','Y',mgr.id,'SAP-HCM-KPI-WH-STF-2026','Dummy KPI template untuk warehouse staff.',
       'admin','2026-06-19 09:05:00','admin','2026-06-19 09:05:00'
  FROM erp_job_title jt
  LEFT JOIN erp_employee_master mgr ON mgr.employee_no='EMP-0005'
 WHERE jt.job_title_code='JT-WH-STF'
   AND NOT EXISTS (SELECT 1 FROM erp_kpi_template x WHERE x.template_no='KPI-TPL-WH-STF-2026');

INSERT INTO erp_kpi_template_detail (kpi_template_id,line_no,kpi_code,kpi_name,kpi_perspective,measure_type,target_value,target_text,unit_of_measure,weight,scoring_direction,data_source,review_frequency,mandatory,remarks)
SELECT t.id,1,'KPI-INV-ACC','Inventory Accuracy','PROCESS','QUANTITATIVE',99.5000,'Akurasi stok minimal 99.5%','%',40,'HIGHER_BETTER','Stock opname / stock card','MONTHLY','Y','Akurasi stok gudang'
  FROM erp_kpi_template t WHERE t.template_no='KPI-TPL-WH-STF-2026'
    AND NOT EXISTS (SELECT 1 FROM erp_kpi_template_detail d WHERE d.kpi_template_id=t.id AND d.kpi_code='KPI-INV-ACC');
INSERT INTO erp_kpi_template_detail (kpi_template_id,line_no,kpi_code,kpi_name,kpi_perspective,measure_type,target_value,target_text,unit_of_measure,weight,scoring_direction,data_source,review_frequency,mandatory,remarks)
SELECT t.id,2,'KPI-GR-SLA','Goods Receipt SLA','CUSTOMER','QUANTITATIVE',95.0000,'95% GR selesai tepat waktu','%',30,'HIGHER_BETTER','Goods receipt report','MONTHLY','Y','Kecepatan proses penerimaan'
  FROM erp_kpi_template t WHERE t.template_no='KPI-TPL-WH-STF-2026'
    AND NOT EXISTS (SELECT 1 FROM erp_kpi_template_detail d WHERE d.kpi_template_id=t.id AND d.kpi_code='KPI-GR-SLA');
INSERT INTO erp_kpi_template_detail (kpi_template_id,line_no,kpi_code,kpi_name,kpi_perspective,measure_type,target_value,target_text,unit_of_measure,weight,scoring_direction,data_source,review_frequency,mandatory,remarks)
SELECT t.id,3,'KPI-BC-TRACE','Customs Document Traceability','COMPLIANCE','QUALITATIVE',100.0000,'Semua issue/receipt bisa trace dokumen BC','%',30,'HIGHER_BETTER','Customs stock traceability','MONTHLY','Y','Traceability dokumen pabean'
  FROM erp_kpi_template t WHERE t.template_no='KPI-TPL-WH-STF-2026'
    AND NOT EXISTS (SELECT 1 FROM erp_kpi_template_detail d WHERE d.kpi_template_id=t.id AND d.kpi_code='KPI-BC-TRACE');
