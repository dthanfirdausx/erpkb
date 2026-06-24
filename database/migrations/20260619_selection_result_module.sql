CREATE TABLE IF NOT EXISTS erp_selection_result (
  id INT(11) NOT NULL AUTO_INCREMENT,
  selection_no VARCHAR(30) NOT NULL,
  applicant_id INT(11) NOT NULL,
  vacancy_id INT(11) DEFAULT NULL,
  final_interview_id INT(11) DEFAULT NULL,
  selection_date DATE NOT NULL,
  selection_stage ENUM('SCREENING','INTERVIEW','FINAL','OFFER_DECISION','HIRING_DECISION') NOT NULL DEFAULT 'FINAL',
  selection_status ENUM('DRAFT','SUBMITTED','APPROVED','REJECTED','CANCELLED') NOT NULL DEFAULT 'DRAFT',
  decision_result ENUM('PENDING','SELECTED','REJECTED','HOLD','WAITING_LIST','REINTERVIEW') NOT NULL DEFAULT 'PENDING',
  ranking_no INT(11) DEFAULT NULL,
  screening_score DECIMAL(5,2) NOT NULL DEFAULT 0,
  interview_score DECIMAL(5,2) NOT NULL DEFAULT 0,
  assessment_score DECIMAL(5,2) NOT NULL DEFAULT 0,
  overall_score DECIMAL(5,2) NOT NULL DEFAULT 0,
  recommendation ENUM('PENDING','STRONG_HIRE','HIRE','HOLD','NO_HIRE','REINTERVIEW') NOT NULL DEFAULT 'PENDING',
  proposed_position_id INT(11) DEFAULT NULL,
  proposed_job_title_id INT(11) DEFAULT NULL,
  proposed_grade VARCHAR(30) DEFAULT NULL,
  proposed_salary DECIMAL(18,2) NOT NULL DEFAULT 0,
  currency CHAR(3) NOT NULL DEFAULT 'IDR',
  proposed_join_date DATE DEFAULT NULL,
  approved_by_employee_id INT(11) DEFAULT NULL,
  approved_at DATETIME DEFAULT NULL,
  rejected_reason VARCHAR(255) DEFAULT NULL,
  hold_reason VARCHAR(255) DEFAULT NULL,
  selection_committee_notes TEXT DEFAULT NULL,
  hr_notes TEXT DEFAULT NULL,
  user_notes TEXT DEFAULT NULL,
  next_action ENUM('NONE','CREATE_OFFER','SCHEDULE_REINTERVIEW','KEEP_TALENT_POOL','CLOSE_APPLICATION') NOT NULL DEFAULT 'NONE',
  sap_reference VARCHAR(50) DEFAULT NULL,
  remarks TEXT DEFAULT NULL,
  created_by VARCHAR(50) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_by VARCHAR(50) DEFAULT NULL,
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_erp_selection_no (selection_no),
  KEY idx_erp_selection_applicant (applicant_id),
  KEY idx_erp_selection_vacancy (vacancy_id),
  KEY idx_erp_selection_date (selection_date),
  KEY idx_erp_selection_status (selection_status,decision_result)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE sys_menu
   SET nav_act='selection_result',
       main_table='erp_selection_result',
       icon='fa-check-circle',
       dt_table='Y',
       tampil='Y'
 WHERE url='selection-result';

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id,g.level,'Y',
       CASE WHEN g.level IN ('admin','system_administrator','hrd','manager_approver') THEN 'Y' ELSE 'N' END,
       CASE WHEN g.level IN ('admin','system_administrator','hrd','manager_approver') THEN 'Y' ELSE 'N' END,
       CASE WHEN g.level IN ('admin','system_administrator') THEN 'Y' ELSE 'N' END,
       'N'
  FROM sys_menu m JOIN sys_group_users g
 WHERE m.url='selection-result'
   AND NOT EXISTS (SELECT 1 FROM sys_menu_role r WHERE r.id_menu=m.id AND r.group_level=g.level);

UPDATE sys_menu_role r
JOIN sys_menu m ON m.id=r.id_menu AND m.url='selection-result'
   SET r.read_act='Y',
       r.insert_act=CASE WHEN r.group_level IN ('admin','system_administrator','hrd','manager_approver') THEN 'Y' ELSE 'N' END,
       r.update_act=CASE WHEN r.group_level IN ('admin','system_administrator','hrd','manager_approver') THEN 'Y' ELSE 'N' END,
       r.delete_act=CASE WHEN r.group_level IN ('admin','system_administrator') THEN 'Y' ELSE 'N' END,
       r.import_act='N';
