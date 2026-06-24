CREATE TABLE IF NOT EXISTS erp_applicant_data (
  id INT(11) NOT NULL AUTO_INCREMENT,
  applicant_no VARCHAR(30) NOT NULL,
  vacancy_id INT(11) DEFAULT NULL,
  applicant_name VARCHAR(160) NOT NULL,
  gender ENUM('MALE','FEMALE','OTHER') NOT NULL DEFAULT 'OTHER',
  birth_place VARCHAR(80) DEFAULT NULL,
  birth_date DATE DEFAULT NULL,
  nationality CHAR(3) NOT NULL DEFAULT 'ID',
  identity_type ENUM('KTP','PASSPORT','KITAS','OTHER') NOT NULL DEFAULT 'KTP',
  identity_no VARCHAR(50) DEFAULT NULL,
  email VARCHAR(100) DEFAULT NULL,
  phone VARCHAR(50) DEFAULT NULL,
  address VARCHAR(255) DEFAULT NULL,
  city VARCHAR(100) DEFAULT NULL,
  postal_code VARCHAR(20) DEFAULT NULL,
  education_level ENUM('SMA_SMK','DIPLOMA','S1','S2','S3','OTHER') NOT NULL DEFAULT 'S1',
  major VARCHAR(100) DEFAULT NULL,
  university VARCHAR(150) DEFAULT NULL,
  graduation_year INT(4) DEFAULT NULL,
  gpa DECIMAL(4,2) DEFAULT NULL,
  current_company VARCHAR(150) DEFAULT NULL,
  current_position VARCHAR(150) DEFAULT NULL,
  years_experience DECIMAL(5,2) NOT NULL DEFAULT 0,
  expected_salary DECIMAL(18,2) NOT NULL DEFAULT 0,
  currency CHAR(3) NOT NULL DEFAULT 'IDR',
  source_channel VARCHAR(100) DEFAULT NULL,
  referred_by_employee_id INT(11) DEFAULT NULL,
  application_date DATE NOT NULL,
  available_start_date DATE DEFAULT NULL,
  applicant_status ENUM('NEW','SCREENING','SHORTLISTED','INTERVIEW','OFFER','HIRED','REJECTED','WITHDRAWN','BLACKLISTED') NOT NULL DEFAULT 'NEW',
  screening_score DECIMAL(5,2) NOT NULL DEFAULT 0,
  interview_score DECIMAL(5,2) NOT NULL DEFAULT 0,
  final_score DECIMAL(5,2) NOT NULL DEFAULT 0,
  recruiter_employee_id INT(11) DEFAULT NULL,
  cv_reference VARCHAR(255) DEFAULT NULL,
  portfolio_url VARCHAR(255) DEFAULT NULL,
  linkedin_url VARCHAR(255) DEFAULT NULL,
  skills TEXT DEFAULT NULL,
  screening_notes TEXT DEFAULT NULL,
  interview_notes TEXT DEFAULT NULL,
  rejection_reason VARCHAR(255) DEFAULT NULL,
  hired_employee_id INT(11) DEFAULT NULL,
  sap_reference VARCHAR(50) DEFAULT NULL,
  remarks TEXT DEFAULT NULL,
  created_by VARCHAR(50) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_by VARCHAR(50) DEFAULT NULL,
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_erp_applicant_no (applicant_no),
  KEY idx_erp_applicant_vacancy (vacancy_id),
  KEY idx_erp_applicant_status (applicant_status),
  KEY idx_erp_applicant_date (application_date),
  KEY idx_erp_applicant_name (applicant_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE sys_menu
   SET nav_act='applicant_data',
       main_table='erp_applicant_data',
       icon='fa-user-plus',
       dt_table='Y',
       tampil='Y'
 WHERE url='applicant-data';

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
 WHERE m.url='applicant-data'
   AND NOT EXISTS (
     SELECT 1 FROM sys_menu_role r
      WHERE r.id_menu=m.id AND r.group_level=g.level
   );

UPDATE sys_menu_role r
JOIN sys_menu m ON m.id=r.id_menu AND m.url='applicant-data'
   SET r.read_act='Y',
       r.insert_act=CASE WHEN r.group_level IN ('admin','system_administrator','hrd','manager_approver') THEN 'Y' ELSE 'N' END,
       r.update_act=CASE WHEN r.group_level IN ('admin','system_administrator','hrd','manager_approver') THEN 'Y' ELSE 'N' END,
       r.delete_act=CASE WHEN r.group_level IN ('admin','system_administrator') THEN 'Y' ELSE 'N' END,
       r.import_act='N';
