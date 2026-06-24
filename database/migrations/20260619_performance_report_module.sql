CREATE TABLE IF NOT EXISTS erp_appraisal_approval (
  id INT(11) NOT NULL AUTO_INCREMENT,
  appraisal_no VARCHAR(30) NOT NULL,
  cycle_year YEAR NOT NULL,
  appraisal_period ENUM('Q1','Q2','Q3','Q4','H1','H2','ANNUAL','PROBATION','SPECIAL') NOT NULL DEFAULT 'ANNUAL',
  appraisal_type ENUM('ANNUAL','MID_YEAR','PROBATION','PROJECT','SPECIAL') NOT NULL DEFAULT 'ANNUAL',
  employee_id INT(11) NOT NULL,
  appraiser_employee_id INT(11) NOT NULL,
  second_appraiser_employee_id INT(11) DEFAULT NULL,
  hr_reviewer_employee_id INT(11) DEFAULT NULL,
  department_code CHAR(8) DEFAULT NULL,
  job_title_id INT(11) DEFAULT NULL,
  appraisal_date DATE NOT NULL,
  submitted_at DATETIME DEFAULT NULL,
  kpi_score DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  competency_score DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  behavior_score DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  final_score DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  final_rating ENUM('A','B','C','D','E') NOT NULL DEFAULT 'C',
  calibration_status ENUM('DRAFT','SUBMITTED','MANAGER_APPROVED','HR_REVIEW','APPROVED','REJECTED','RETURNED','CANCELLED') NOT NULL DEFAULT 'DRAFT',
  approval_level ENUM('MANAGER','SECOND_MANAGER','HR','FINAL') NOT NULL DEFAULT 'MANAGER',
  decision ENUM('PENDING','APPROVED','REJECTED','RETURNED') NOT NULL DEFAULT 'PENDING',
  decision_by VARCHAR(50) DEFAULT NULL,
  decision_at DATETIME DEFAULT NULL,
  manager_comment TEXT DEFAULT NULL,
  hr_comment TEXT DEFAULT NULL,
  employee_comment TEXT DEFAULT NULL,
  development_plan TEXT DEFAULT NULL,
  reward_recommendation VARCHAR(150) DEFAULT NULL,
  improvement_required ENUM('Y','N') NOT NULL DEFAULT 'N',
  remarks TEXT DEFAULT NULL,
  created_by VARCHAR(50) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_by VARCHAR(50) DEFAULT NULL,
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_appraisal_no (appraisal_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

UPDATE sys_menu
   SET nav_act='performance_report',
       main_table='erp_appraisal_approval',
       icon='fa-line-chart',
       dt_table='Y',
       tampil='Y'
 WHERE url='performance-report';

INSERT INTO sys_menu_role (id_menu,group_level,read_act,insert_act,update_act,delete_act,import_act)
SELECT m.id,g.level,'Y','N','N','N','N'
  FROM sys_menu m
  JOIN sys_group_users g
 WHERE m.url='performance-report'
   AND NOT EXISTS (
     SELECT 1 FROM sys_menu_role r WHERE r.id_menu=m.id AND r.group_level=g.level
   );

UPDATE sys_menu_role r
JOIN sys_menu m ON m.id=r.id_menu AND m.url='performance-report'
SET r.read_act='Y',
    r.insert_act='N',
    r.update_act='N',
    r.delete_act='N',
    r.import_act='N';

UPDATE erp_appraisal_approval
   SET created_by='admin',
       updated_by='admin'
 WHERE COALESCE(created_by,'')<>'admin'
    OR COALESCE(updated_by,'')<>'admin';

INSERT INTO erp_appraisal_approval (
  appraisal_no, cycle_year, appraisal_period, appraisal_type, employee_id, appraiser_employee_id,
  second_appraiser_employee_id, hr_reviewer_employee_id, department_code, job_title_id, appraisal_date,
  submitted_at, kpi_score, competency_score, behavior_score, final_score, final_rating,
  calibration_status, approval_level, decision, decision_by, decision_at, manager_comment, hr_comment,
  employee_comment, development_plan, reward_recommendation, improvement_required, remarks,
  created_by, created_at, updated_by, updated_at
)
SELECT 'PRF-DUMMY-001', 2026, 'H1', 'MID_YEAR', e.id, e.manager_employee_id,
       gm.id, hr.id, e.department_code, e.job_title_id, '2026-06-14',
       '2026-06-14 09:00:00', 94.00, 90.00, 92.00, 92.40, 'A',
       'APPROVED', 'FINAL', 'APPROVED', 'admin', '2026-06-14 15:30:00',
       'Konsisten melampaui target dan menjadi role model tim.',
       'Disetujui sebagai high performer untuk succession pool.',
       'Siap mengambil tanggung jawab lintas fungsi.',
       'Leadership mentoring dan project improvement lintas departemen.',
       'Promotion / talent pool', 'N', 'Dummy performance report high performer.',
       'admin','2026-06-14 09:00:00','admin','2026-06-14 15:30:00'
  FROM erp_employee_master e
  JOIN erp_employee_master gm ON gm.employee_no='EMP-0001'
  JOIN erp_employee_master hr ON hr.employee_no='EMP-0003'
 WHERE e.employee_no='EMP-0006'
   AND NOT EXISTS (SELECT 1 FROM erp_appraisal_approval x WHERE x.appraisal_no='PRF-DUMMY-001');

INSERT INTO erp_appraisal_approval (
  appraisal_no, cycle_year, appraisal_period, appraisal_type, employee_id, appraiser_employee_id,
  second_appraiser_employee_id, hr_reviewer_employee_id, department_code, job_title_id, appraisal_date,
  submitted_at, kpi_score, competency_score, behavior_score, final_score, final_rating,
  calibration_status, approval_level, decision, manager_comment, hr_comment, employee_comment,
  development_plan, reward_recommendation, improvement_required, remarks,
  created_by, created_at, updated_by, updated_at
)
SELECT 'PRF-DUMMY-002', 2026, 'H1', 'MID_YEAR', e.id, e.manager_employee_id,
       NULL, hr.id, e.department_code, e.job_title_id, '2026-06-15',
       '2026-06-15 10:20:00', 63.00, 66.00, 70.00, 65.90, 'D',
       'HR_REVIEW', 'HR', 'PENDING',
       'Target belum stabil dan perlu pendampingan kualitas output.',
       'Masuk watchlist performance improvement plan.',
       'Membutuhkan coaching lebih intensif.',
       'PIP 60 hari, coaching supervisor, dan review mingguan.',
       'Performance improvement plan', 'Y', 'Dummy performance report low performer.',
       'admin','2026-06-15 10:20:00','admin','2026-06-15 10:20:00'
  FROM erp_employee_master e
  JOIN erp_employee_master hr ON hr.employee_no='EMP-0003'
 WHERE e.employee_no='EMP-0008'
   AND NOT EXISTS (SELECT 1 FROM erp_appraisal_approval x WHERE x.appraisal_no='PRF-DUMMY-002');
