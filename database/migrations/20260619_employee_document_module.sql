CREATE TABLE IF NOT EXISTS erp_employee_document (
  id INT(11) NOT NULL AUTO_INCREMENT,
  document_no VARCHAR(30) NOT NULL,
  employee_id INT(11) NOT NULL,
  document_type ENUM('KTP','NPWP','KK','IJAZAH','SERTIFIKAT','KONTRAK','BPJS_KESEHATAN','BPJS_TK','PASSPORT','VISA','SIM','LICENSE','WARNING_LETTER','APPRAISAL','OTHER') NOT NULL DEFAULT 'OTHER',
  document_category ENUM('PERSONAL','EMPLOYMENT','PAYROLL_TAX','BENEFIT','LEGAL','TRAINING','PERFORMANCE','DISCIPLINARY','OTHER') NOT NULL DEFAULT 'PERSONAL',
  document_title VARCHAR(160) NOT NULL,
  document_number VARCHAR(80) DEFAULT NULL,
  issue_date DATE DEFAULT NULL,
  expiry_date DATE DEFAULT NULL,
  issuing_authority VARCHAR(160) DEFAULT NULL,
  issuing_country VARCHAR(3) NOT NULL DEFAULT 'ID',
  file_ref VARCHAR(255) DEFAULT NULL,
  file_name VARCHAR(160) DEFAULT NULL,
  file_type VARCHAR(30) DEFAULT NULL,
  confidential ENUM('Y','N') NOT NULL DEFAULT 'N',
  mandatory_document ENUM('Y','N') NOT NULL DEFAULT 'N',
  renewal_required ENUM('Y','N') NOT NULL DEFAULT 'N',
  verification_status ENUM('PENDING','VERIFIED','REJECTED') NOT NULL DEFAULT 'PENDING',
  verified_by_employee_id INT(11) DEFAULT NULL,
  verified_at DATETIME DEFAULT NULL,
  effective_from DATE NOT NULL DEFAULT '2026-01-01',
  effective_to DATE NOT NULL DEFAULT '9999-12-31',
  status ENUM('ACTIVE','INACTIVE','EXPIRED','ARCHIVED') NOT NULL DEFAULT 'ACTIVE',
  sap_reference VARCHAR(50) DEFAULT NULL,
  remarks TEXT DEFAULT NULL,
  created_by VARCHAR(50) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_by VARCHAR(50) DEFAULT NULL,
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_employee_document_no (document_no),
  KEY idx_emp_doc_employee (employee_id),
  KEY idx_emp_doc_type (document_type,document_category),
  KEY idx_emp_doc_expiry (expiry_date),
  KEY idx_emp_doc_verify (verification_status,status),
  KEY idx_emp_doc_effective (effective_from,effective_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

UPDATE sys_menu
   SET nav_act='employee_document',
       main_table='erp_employee_document',
       icon='fa-folder-open',
       dt_table='Y',
       tampil='Y'
 WHERE url='employee-document';

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id, g.level, 'Y',
       IF(g.level IN ('admin','system_administrator','hrd'),'Y','N'),
       IF(g.level IN ('admin','system_administrator','hrd'),'Y','N'),
       IF(g.level IN ('admin','system_administrator'),'Y','N'),
       'N'
  FROM sys_menu m
  JOIN sys_group_users g
  LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.level
 WHERE m.url='employee-document'
   AND r.id IS NULL;
