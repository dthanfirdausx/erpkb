CREATE TABLE IF NOT EXISTS erp_interview_schedule (
  id INT(11) NOT NULL AUTO_INCREMENT,
  interview_no VARCHAR(30) NOT NULL,
  applicant_id INT(11) NOT NULL,
  vacancy_id INT(11) DEFAULT NULL,
  interview_round INT(11) NOT NULL DEFAULT 1,
  interview_type ENUM('HR','TECHNICAL','USER','PANEL','FINAL','ONLINE') NOT NULL DEFAULT 'HR',
  interview_method ENUM('ONSITE','ONLINE','PHONE') NOT NULL DEFAULT 'ONSITE',
  interview_status ENUM('DRAFT','SCHEDULED','CONFIRMED','COMPLETED','RESCHEDULED','CANCELLED','NO_SHOW') NOT NULL DEFAULT 'DRAFT',
  schedule_date DATE NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  timezone VARCHAR(50) NOT NULL DEFAULT 'Asia/Jakarta',
  location VARCHAR(150) DEFAULT NULL,
  meeting_link VARCHAR(255) DEFAULT NULL,
  recruiter_employee_id INT(11) DEFAULT NULL,
  primary_interviewer_employee_id INT(11) DEFAULT NULL,
  hr_interviewer_employee_id INT(11) DEFAULT NULL,
  technical_interviewer_employee_id INT(11) DEFAULT NULL,
  hiring_manager_employee_id INT(11) DEFAULT NULL,
  confirmation_sent ENUM('Y','N') NOT NULL DEFAULT 'N',
  confirmation_sent_at DATETIME DEFAULT NULL,
  applicant_confirmed ENUM('Y','N') NOT NULL DEFAULT 'N',
  applicant_confirmed_at DATETIME DEFAULT NULL,
  overall_score DECIMAL(5,2) NOT NULL DEFAULT 0,
  recommendation ENUM('PENDING','PASS','HOLD','FAIL','REINTERVIEW') NOT NULL DEFAULT 'PENDING',
  result_notes TEXT DEFAULT NULL,
  agenda TEXT DEFAULT NULL,
  preparation_notes TEXT DEFAULT NULL,
  cancellation_reason VARCHAR(255) DEFAULT NULL,
  sap_reference VARCHAR(50) DEFAULT NULL,
  remarks TEXT DEFAULT NULL,
  created_by VARCHAR(50) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_by VARCHAR(50) DEFAULT NULL,
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_erp_interview_no (interview_no),
  KEY idx_erp_interview_applicant (applicant_id),
  KEY idx_erp_interview_vacancy (vacancy_id),
  KEY idx_erp_interview_schedule (schedule_date,start_time),
  KEY idx_erp_interview_status (interview_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS erp_interview_schedule_panel (
  id INT(11) NOT NULL AUTO_INCREMENT,
  interview_id INT(11) NOT NULL,
  line_no INT(11) NOT NULL,
  interviewer_employee_id INT(11) NOT NULL,
  interviewer_role ENUM('HR','TECHNICAL','USER','HIRING_MANAGER','OBSERVER') NOT NULL DEFAULT 'USER',
  attendance_status ENUM('INVITED','CONFIRMED','ATTENDED','ABSENT') NOT NULL DEFAULT 'INVITED',
  score DECIMAL(5,2) NOT NULL DEFAULT 0,
  feedback TEXT DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_erp_interview_panel_header (interview_id),
  KEY idx_erp_interview_panel_employee (interviewer_employee_id),
  CONSTRAINT fk_erp_interview_panel_header FOREIGN KEY (interview_id) REFERENCES erp_interview_schedule(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE sys_menu
   SET nav_act='interview_schedule',
       main_table='erp_interview_schedule',
       icon='fa-calendar-check-o',
       dt_table='Y',
       tampil='Y'
 WHERE url='interview-schedule';

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id,g.level,'Y',
       CASE WHEN g.level IN ('admin','system_administrator','hrd','manager_approver') THEN 'Y' ELSE 'N' END,
       CASE WHEN g.level IN ('admin','system_administrator','hrd','manager_approver') THEN 'Y' ELSE 'N' END,
       CASE WHEN g.level IN ('admin','system_administrator') THEN 'Y' ELSE 'N' END,
       'N'
  FROM sys_menu m JOIN sys_group_users g
 WHERE m.url='interview-schedule'
   AND NOT EXISTS (SELECT 1 FROM sys_menu_role r WHERE r.id_menu=m.id AND r.group_level=g.level);

UPDATE sys_menu_role r
JOIN sys_menu m ON m.id=r.id_menu AND m.url='interview-schedule'
   SET r.read_act='Y',
       r.insert_act=CASE WHEN r.group_level IN ('admin','system_administrator','hrd','manager_approver') THEN 'Y' ELSE 'N' END,
       r.update_act=CASE WHEN r.group_level IN ('admin','system_administrator','hrd','manager_approver') THEN 'Y' ELSE 'N' END,
       r.delete_act=CASE WHEN r.group_level IN ('admin','system_administrator') THEN 'Y' ELSE 'N' END,
       r.import_act='N';
