CREATE TABLE IF NOT EXISTS erp_training_catalog (
  id INT NOT NULL AUTO_INCREMENT,
  training_code VARCHAR(30) NOT NULL,
  training_name VARCHAR(150) NOT NULL,
  training_category ENUM('TECHNICAL','QUALITY','SAFETY','COMPLIANCE','LEADERSHIP','SOFT_SKILL','ONBOARDING','CERTIFICATION','OTHER') NOT NULL DEFAULT 'TECHNICAL',
  delivery_method ENUM('CLASSROOM','ONLINE','BLENDED','ON_THE_JOB','WORKSHOP','EXTERNAL') NOT NULL DEFAULT 'CLASSROOM',
  training_level ENUM('BASIC','INTERMEDIATE','ADVANCED','EXPERT') NOT NULL DEFAULT 'BASIC',
  training_type ENUM('MANDATORY','OPTIONAL','CERTIFICATION','REFRESHER') NOT NULL DEFAULT 'OPTIONAL',
  provider_type ENUM('INTERNAL','EXTERNAL') NOT NULL DEFAULT 'INTERNAL',
  provider_name VARCHAR(120) DEFAULT NULL,
  duration_hours DECIMAL(8,2) NOT NULL DEFAULT 0,
  validity_months INT NOT NULL DEFAULT 0,
  target_audience VARCHAR(255) DEFAULT NULL,
  competency_area VARCHAR(120) DEFAULT NULL,
  prerequisite TEXT DEFAULT NULL,
  learning_objective TEXT DEFAULT NULL,
  syllabus TEXT DEFAULT NULL,
  assessment_required ENUM('Y','N') NOT NULL DEFAULT 'N',
  passing_score DECIMAL(6,2) DEFAULT NULL,
  certificate_required ENUM('Y','N') NOT NULL DEFAULT 'N',
  cost_estimate DECIMAL(18,2) NOT NULL DEFAULT 0,
  currency VARCHAR(5) NOT NULL DEFAULT 'IDR',
  max_participant INT NOT NULL DEFAULT 0,
  owner_department_code CHAR(8) DEFAULT NULL,
  cost_center_code VARCHAR(20) DEFAULT NULL,
  sap_reference VARCHAR(60) DEFAULT NULL,
  status ENUM('DRAFT','ACTIVE','INACTIVE','OBSOLETE') NOT NULL DEFAULT 'DRAFT',
  valid_from DATE NOT NULL DEFAULT '2026-01-01',
  valid_to DATE NOT NULL DEFAULT '9999-12-31',
  remarks TEXT DEFAULT NULL,
  created_by VARCHAR(50) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_by VARCHAR(50) DEFAULT NULL,
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_training_code (training_code),
  KEY idx_training_category (training_category),
  KEY idx_delivery_method (delivery_method),
  KEY idx_training_type (training_type),
  KEY idx_provider_type (provider_type),
  KEY idx_owner_department (owner_department_code),
  KEY idx_status_validity (status, valid_from, valid_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO erp_training_catalog
(training_code, training_name, training_category, delivery_method, training_level, training_type, provider_type, provider_name, duration_hours, validity_months, target_audience, competency_area, prerequisite, learning_objective, syllabus, assessment_required, passing_score, certificate_required, cost_estimate, currency, max_participant, owner_department_code, cost_center_code, sap_reference, status, valid_from, valid_to, remarks, created_by, updated_by, updated_at)
SELECT 'TRN-SAF-001','Keselamatan Kerja Dasar','SAFETY','CLASSROOM','BASIC','MANDATORY','INTERNAL','HSE Internal Trainer',8,12,'Seluruh karyawan area produksi dan warehouse','Occupational Health & Safety','-','Peserta memahami hazard, APD, emergency response, dan permit dasar.','1. Safety induction; 2. APD; 3. Emergency; 4. Incident reporting','Y',80,'Y',0,'IDR',30,'DEP-HR',NULL,'SAP-LSO-SAF-001','ACTIVE','2026-01-01','9999-12-31','Mandatory annual refreshment', 'admin','admin',NOW()
WHERE NOT EXISTS (SELECT 1 FROM erp_training_catalog WHERE training_code='TRN-SAF-001');

INSERT INTO erp_training_catalog
(training_code, training_name, training_category, delivery_method, training_level, training_type, provider_type, provider_name, duration_hours, validity_months, target_audience, competency_area, prerequisite, learning_objective, syllabus, assessment_required, passing_score, certificate_required, cost_estimate, currency, max_participant, owner_department_code, cost_center_code, sap_reference, status, valid_from, valid_to, remarks, created_by, updated_by, updated_at)
SELECT 'TRN-QM-001','Quality Inspection & NCR Handling','QUALITY','WORKSHOP','INTERMEDIATE','OPTIONAL','INTERNAL','Quality Control',12,0,'QC Inspector, Produksi, Warehouse','Quality Management','Memahami basic inspection','Peserta mampu melakukan inspection recording dan NCR follow up.','1. Inspection plan; 2. Defect catalog; 3. NCR flow; 4. Usage decision','Y',75,'N',0,'IDR',20,'DEP-QA',NULL,'SAP-QM-TRN-001','ACTIVE','2026-01-01','9999-12-31','Terkait proses QM', 'admin','admin',NOW()
WHERE NOT EXISTS (SELECT 1 FROM erp_training_catalog WHERE training_code='TRN-QM-001');

INSERT INTO erp_training_catalog
(training_code, training_name, training_category, delivery_method, training_level, training_type, provider_type, provider_name, duration_hours, validity_months, target_audience, competency_area, prerequisite, learning_objective, syllabus, assessment_required, passing_score, certificate_required, cost_estimate, currency, max_participant, owner_department_code, cost_center_code, sap_reference, status, valid_from, valid_to, remarks, created_by, updated_by, updated_at)
SELECT 'TRN-CUS-001','Kepabeanan Kawasan Berikat','COMPLIANCE','BLENDED','INTERMEDIATE','CERTIFICATION','EXTERNAL','Customs Consultant',16,24,'Beacukai internal, warehouse, finance, sales export','Customs Compliance','Memahami dasar dokumen BC','Peserta mampu memahami alur BC masuk/keluar, traceability, dan audit trail.','1. BC 2.3; 2. BC 2.5; 3. Mutasi; 4. Traceability; 5. Audit finding','Y',80,'Y',7500000,'IDR',25,'DEP-CUS',NULL,'SAP-GTS-KB-001','ACTIVE','2026-01-01','9999-12-31','Training compliance KB', 'admin','admin',NOW()
WHERE NOT EXISTS (SELECT 1 FROM erp_training_catalog WHERE training_code='TRN-CUS-001');

INSERT INTO erp_training_catalog
(training_code, training_name, training_category, delivery_method, training_level, training_type, provider_type, provider_name, duration_hours, validity_months, target_audience, competency_area, prerequisite, learning_objective, syllabus, assessment_required, passing_score, certificate_required, cost_estimate, currency, max_participant, owner_department_code, cost_center_code, sap_reference, status, valid_from, valid_to, remarks, created_by, updated_by, updated_at)
SELECT 'TRN-LEAD-001','Supervisor Leadership Essentials','LEADERSHIP','CLASSROOM','INTERMEDIATE','OPTIONAL','INTERNAL','HR Learning Team',14,0,'Supervisor dan calon supervisor','Leadership','Rekomendasi atasan','Peserta mampu mengelola target tim, coaching, dan problem solving.','1. Team management; 2. Coaching; 3. KPI; 4. Conflict handling','N',NULL,'N',0,'IDR',18,'DEP-HR',NULL,'SAP-HCM-LSO-LEAD-001','ACTIVE','2026-01-01','9999-12-31','Leadership path', 'admin','admin',NOW()
WHERE NOT EXISTS (SELECT 1 FROM erp_training_catalog WHERE training_code='TRN-LEAD-001');

INSERT INTO erp_training_catalog
(training_code, training_name, training_category, delivery_method, training_level, training_type, provider_type, provider_name, duration_hours, validity_months, target_audience, competency_area, prerequisite, learning_objective, syllabus, assessment_required, passing_score, certificate_required, cost_estimate, currency, max_participant, owner_department_code, cost_center_code, sap_reference, status, valid_from, valid_to, remarks, created_by, updated_by, updated_at)
SELECT 'TRN-ERP-001','ERP Basic Navigation & Transaction Discipline','ONBOARDING','ONLINE','BASIC','MANDATORY','INTERNAL','ERP Key User',4,0,'User ERP baru','ERP Operation','User login aktif','Peserta memahami navigasi ERP, input data benar, dan konsekuensi audit trail.','1. Master data; 2. Transaction flow; 3. Approval; 4. Log activity','Y',70,'N',0,'IDR',50,'DEP-IT',NULL,'SAP-ENABLE-001','ACTIVE','2026-01-01','9999-12-31','Onboarding ERP', 'admin','admin',NOW()
WHERE NOT EXISTS (SELECT 1 FROM erp_training_catalog WHERE training_code='TRN-ERP-001');

UPDATE sys_menu
SET nav_act='training_catalog',
    main_table='erp_training_catalog',
    dt_table='Y',
    icon='fa-book',
    tampil='Y'
WHERE url='training-catalog';

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
WHERE m.url='training-catalog' AND r.id IS NULL;
