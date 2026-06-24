CREATE TABLE IF NOT EXISTS erp_training_plan (
  id INT NOT NULL AUTO_INCREMENT,
  plan_code VARCHAR(30) NOT NULL,
  plan_name VARCHAR(160) NOT NULL,
  training_catalog_id INT NOT NULL,
  plan_year INT NOT NULL,
  plan_period ENUM('Q1','Q2','Q3','Q4','MONTHLY','ANNUAL','ADHOC') NOT NULL DEFAULT 'ANNUAL',
  planned_start_date DATE NOT NULL,
  planned_end_date DATE NOT NULL,
  target_department_code CHAR(8) DEFAULT NULL,
  target_job_title_id INT DEFAULT NULL,
  target_employee_group VARCHAR(50) DEFAULT NULL,
  priority ENUM('LOW','MEDIUM','HIGH','CRITICAL') NOT NULL DEFAULT 'MEDIUM',
  source_type ENUM('COMPETENCY_GAP','MANDATORY','MANAGER_REQUEST','SUCCESSION','REGULATORY','OTHER') NOT NULL DEFAULT 'COMPETENCY_GAP',
  planned_participant INT NOT NULL DEFAULT 0,
  budget_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
  currency VARCHAR(5) NOT NULL DEFAULT 'IDR',
  plan_owner VARCHAR(80) DEFAULT NULL,
  location VARCHAR(150) DEFAULT NULL,
  approval_status ENUM('DRAFT','SUBMITTED','APPROVED','REJECTED','CANCELLED','COMPLETED') NOT NULL DEFAULT 'DRAFT',
  execution_status ENUM('NOT_STARTED','SCHEDULED','IN_PROGRESS','COMPLETED','CANCELLED') NOT NULL DEFAULT 'NOT_STARTED',
  business_reason TEXT DEFAULT NULL,
  remarks TEXT DEFAULT NULL,
  created_by VARCHAR(50) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_by VARCHAR(50) DEFAULT NULL,
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_training_plan_code (plan_code),
  KEY idx_training_plan_catalog (training_catalog_id),
  KEY idx_training_plan_dates (planned_start_date, planned_end_date),
  KEY idx_training_plan_dept (target_department_code),
  KEY idx_training_plan_job (target_job_title_id),
  KEY idx_training_plan_status (approval_status, execution_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_training_plan_participant (
  id INT NOT NULL AUTO_INCREMENT,
  training_plan_id INT NOT NULL,
  employee_id INT NOT NULL,
  nomination_status ENUM('PLANNED','INVITED','REGISTERED','CANCELLED','COMPLETED') NOT NULL DEFAULT 'PLANNED',
  remarks VARCHAR(255) DEFAULT NULL,
  created_by VARCHAR(50) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_training_plan_employee (training_plan_id, employee_id),
  KEY idx_training_plan_participant_employee (employee_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO erp_training_plan
(plan_code, plan_name, training_catalog_id, plan_year, plan_period, planned_start_date, planned_end_date, target_department_code, target_job_title_id, target_employee_group, priority, source_type, planned_participant, budget_amount, currency, plan_owner, location, approval_status, execution_status, business_reason, remarks, created_by, updated_by, updated_at)
SELECT 'TPL-2026-0001','Annual Safety Refreshment 2026',tc.id,2026,'ANNUAL','2026-07-01','2026-12-31','DEP-PRD',NULL,'OPERATOR','HIGH','MANDATORY',60,0,'IDR','HR Learning Team','Training Room Plant','APPROVED','SCHEDULED','Mandatory refreshment keselamatan kerja untuk area produksi.','Dummy SAP-like training plan','admin','admin',NOW()
FROM erp_training_catalog tc
WHERE tc.training_code='TRN-SAF-001'
AND NOT EXISTS (SELECT 1 FROM erp_training_plan WHERE plan_code='TPL-2026-0001');

INSERT INTO erp_training_plan
(plan_code, plan_name, training_catalog_id, plan_year, plan_period, planned_start_date, planned_end_date, target_department_code, target_job_title_id, target_employee_group, priority, source_type, planned_participant, budget_amount, currency, plan_owner, location, approval_status, execution_status, business_reason, remarks, created_by, updated_by, updated_at)
SELECT 'TPL-2026-0002','Customs Compliance Certification Batch 1',tc.id,2026,'Q3','2026-08-05','2026-08-06','DEP-CUS',NULL,'STAFF','CRITICAL','REGULATORY',20,7500000,'IDR','HR Learning Team','External Provider / Online','SUBMITTED','NOT_STARTED','Kebutuhan compliance Kawasan Berikat dan audit trail dokumen BC.','Dummy SAP-like training plan','admin','admin',NOW()
FROM erp_training_catalog tc
WHERE tc.training_code='TRN-CUS-001'
AND NOT EXISTS (SELECT 1 FROM erp_training_plan WHERE plan_code='TPL-2026-0002');

INSERT IGNORE INTO erp_training_plan_participant (training_plan_id, employee_id, nomination_status, remarks, created_by)
SELECT tp.id,e.id,'PLANNED','Auto dummy participant','admin'
FROM erp_training_plan tp
JOIN erp_employee_master e ON e.department_code='DEP-PRD' AND e.employment_status IN ('ACTIVE','PROBATION','CONTRACT')
WHERE tp.plan_code='TPL-2026-0001'
LIMIT 5;

INSERT IGNORE INTO erp_training_plan_participant (training_plan_id, employee_id, nomination_status, remarks, created_by)
SELECT tp.id,e.id,'PLANNED','Auto dummy participant','admin'
FROM erp_training_plan tp
JOIN erp_employee_master e ON e.department_code='DEP-CUS' AND e.employment_status IN ('ACTIVE','PROBATION','CONTRACT')
WHERE tp.plan_code='TPL-2026-0002'
LIMIT 5;

UPDATE sys_menu
SET nav_act='training_plan',
    main_table='erp_training_plan',
    dt_table='Y',
    icon='fa-calendar',
    tampil='Y'
WHERE url='training-plan';

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id, g.level,
  CASE WHEN g.level IN ('admin','system_administrator','hrd','manager_approver','auditor') THEN 'Y' ELSE 'N' END,
  CASE WHEN g.level IN ('admin','system_administrator','hrd') THEN 'Y' ELSE 'N' END,
  CASE WHEN g.level IN ('admin','system_administrator','hrd') THEN 'Y' ELSE 'N' END,
  CASE WHEN g.level IN ('admin','system_administrator') THEN 'Y' ELSE 'N' END,
  'N'
FROM sys_menu m
JOIN sys_group_users g
LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.level
WHERE m.url='training-plan' AND r.id IS NULL;
