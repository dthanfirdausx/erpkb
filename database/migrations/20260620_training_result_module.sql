CREATE TABLE IF NOT EXISTS erp_training_result (
  id INT NOT NULL AUTO_INCREMENT,
  result_no VARCHAR(30) NOT NULL,
  training_registration_id INT NOT NULL,
  result_date DATE NOT NULL,
  evaluation_method ENUM('EXAM','PRACTICAL','OBSERVATION','ATTENDANCE_ONLY','MIXED') NOT NULL DEFAULT 'EXAM',
  pre_test_score DECIMAL(6,2) DEFAULT NULL,
  post_test_score DECIMAL(6,2) DEFAULT NULL,
  final_score DECIMAL(6,2) DEFAULT NULL,
  passing_score DECIMAL(6,2) DEFAULT NULL,
  result_status ENUM('DRAFT','PASSED','FAILED','INCOMPLETE','NOT_EVALUATED') NOT NULL DEFAULT 'DRAFT',
  completion_status ENUM('NOT_STARTED','IN_PROGRESS','COMPLETED','CANCELLED') NOT NULL DEFAULT 'COMPLETED',
  competency_achieved ENUM('Y','N','PARTIAL') NOT NULL DEFAULT 'Y',
  certificate_issued ENUM('Y','N') NOT NULL DEFAULT 'N',
  certificate_no VARCHAR(60) DEFAULT NULL,
  certificate_date DATE DEFAULT NULL,
  certificate_valid_until DATE DEFAULT NULL,
  evaluator_name VARCHAR(120) DEFAULT NULL,
  training_feedback_score DECIMAL(6,2) DEFAULT NULL,
  trainer_feedback_score DECIMAL(6,2) DEFAULT NULL,
  improvement_note TEXT DEFAULT NULL,
  follow_up_action TEXT DEFAULT NULL,
  remarks TEXT DEFAULT NULL,
  created_by VARCHAR(50) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_by VARCHAR(50) DEFAULT NULL,
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_training_result_no (result_no),
  UNIQUE KEY uk_training_result_registration (training_registration_id),
  KEY idx_training_result_date (result_date),
  KEY idx_training_result_status (result_status, completion_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO erp_training_result
(result_no, training_registration_id, result_date, evaluation_method, final_score, passing_score, result_status, completion_status, competency_achieved, certificate_issued, evaluator_name, remarks, created_by, updated_by, updated_at)
SELECT CONCAT('TRS-',DATE_FORMAT(CURDATE(),'%Y'),'-',LPAD(tr.id,4,'0')), tr.id, CURDATE(), 'ATTENDANCE_ONLY', tr.score, NULL,
  CASE WHEN tr.registration_status='COMPLETED' THEN 'PASSED' ELSE 'NOT_EVALUATED' END,
  CASE WHEN tr.registration_status='COMPLETED' THEN 'COMPLETED' ELSE 'IN_PROGRESS' END,
  CASE WHEN tr.registration_status='COMPLETED' THEN 'Y' ELSE 'PARTIAL' END,
  CASE WHEN tr.certificate_no IS NOT NULL AND tr.certificate_no<>'' THEN 'Y' ELSE 'N' END,
  'HR Learning Team', 'Auto seed dari training registration', 'admin', 'admin', NOW()
FROM erp_training_registration tr
WHERE tr.registration_status IN ('ATTENDED','COMPLETED');

UPDATE sys_menu
SET nav_act='training_result',
    main_table='erp_training_result',
    dt_table='Y',
    icon='fa-bar-chart',
    tampil='Y'
WHERE url='training-result';

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
WHERE m.url='training-result' AND r.id IS NULL;
