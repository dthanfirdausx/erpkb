CREATE TABLE IF NOT EXISTS erp_certification (
  id INT NOT NULL AUTO_INCREMENT,
  certification_no VARCHAR(30) NOT NULL,
  training_result_id INT NOT NULL,
  employee_id INT NOT NULL,
  certification_type ENUM('INTERNAL','EXTERNAL','REGULATORY','PROFESSIONAL','SAFETY','QUALITY','CUSTOMS','OTHER') NOT NULL DEFAULT 'INTERNAL',
  certification_name VARCHAR(160) NOT NULL,
  issuing_body VARCHAR(160) DEFAULT NULL,
  issue_date DATE NOT NULL,
  valid_from DATE NOT NULL,
  valid_until DATE DEFAULT NULL,
  renewal_required ENUM('Y','N') NOT NULL DEFAULT 'N',
  renewal_alert_days INT NOT NULL DEFAULT 30,
  certification_status ENUM('DRAFT','ACTIVE','EXPIRING','EXPIRED','SUSPENDED','REVOKED','RENEWED') NOT NULL DEFAULT 'DRAFT',
  compliance_status ENUM('COMPLIANT','WARNING','NON_COMPLIANT','NOT_REQUIRED') NOT NULL DEFAULT 'COMPLIANT',
  certificate_no VARCHAR(80) DEFAULT NULL,
  certificate_file_ref VARCHAR(255) DEFAULT NULL,
  score DECIMAL(6,2) DEFAULT NULL,
  remarks TEXT DEFAULT NULL,
  created_by VARCHAR(50) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_by VARCHAR(50) DEFAULT NULL,
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_certification_no (certification_no),
  UNIQUE KEY uk_certification_result (training_result_id),
  KEY idx_certification_employee (employee_id),
  KEY idx_certification_validity (valid_until),
  KEY idx_certification_status (certification_status, compliance_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO erp_certification
(certification_no, training_result_id, employee_id, certification_type, certification_name, issuing_body, issue_date, valid_from, valid_until, renewal_required, renewal_alert_days, certification_status, compliance_status, certificate_no, score, remarks, created_by, updated_by, updated_at)
SELECT CONCAT('CRT-',DATE_FORMAT(CURDATE(),'%Y'),'-',LPAD(trr.id,4,'0')), trr.id, reg.employee_id,
  CASE WHEN tc.training_category='COMPLIANCE' THEN 'REGULATORY' WHEN tc.training_category='SAFETY' THEN 'SAFETY' WHEN tc.training_category='QUALITY' THEN 'QUALITY' WHEN tc.training_category='CERTIFICATION' THEN 'PROFESSIONAL' ELSE 'INTERNAL' END,
  tc.training_name, COALESCE(tc.provider_name,'HR Learning Team'), COALESCE(trr.certificate_date,trr.result_date), COALESCE(trr.certificate_date,trr.result_date), trr.certificate_valid_until,
  CASE WHEN trr.certificate_valid_until IS NULL THEN 'N' ELSE 'Y' END, 30,
  CASE WHEN trr.certificate_valid_until IS NOT NULL AND trr.certificate_valid_until < CURDATE() THEN 'EXPIRED' ELSE 'ACTIVE' END,
  CASE WHEN trr.certificate_valid_until IS NOT NULL AND trr.certificate_valid_until < CURDATE() THEN 'NON_COMPLIANT' ELSE 'COMPLIANT' END,
  trr.certificate_no, trr.final_score, 'Auto seed dari training result certificate', 'admin', 'admin', NOW()
FROM erp_training_result trr
JOIN erp_training_registration reg ON reg.id=trr.training_registration_id
JOIN erp_training_plan tp ON tp.id=reg.training_plan_id
JOIN erp_training_catalog tc ON tc.id=tp.training_catalog_id
WHERE trr.result_status='PASSED' AND trr.completion_status='COMPLETED';

UPDATE sys_menu
SET nav_act='certification',
    main_table='erp_certification',
    dt_table='Y',
    icon='fa-certificate',
    tampil='Y'
WHERE url='certification';

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
WHERE m.url='certification' AND r.id IS NULL;
