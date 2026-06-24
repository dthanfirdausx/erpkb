CREATE TABLE IF NOT EXISTS erp_manpower_plan (
  id INT(11) NOT NULL AUTO_INCREMENT,
  plan_no VARCHAR(30) NOT NULL,
  plan_name VARCHAR(150) NOT NULL,
  plan_year INT(4) NOT NULL,
  plan_version VARCHAR(20) NOT NULL DEFAULT 'V1',
  planning_type ENUM('ANNUAL','QUARTERLY','MONTHLY','PROJECT','REPLACEMENT') NOT NULL DEFAULT 'ANNUAL',
  planning_status ENUM('DRAFT','SUBMITTED','APPROVED','REJECTED','CLOSED') NOT NULL DEFAULT 'DRAFT',
  period_from DATE NOT NULL,
  period_to DATE NOT NULL,
  company_structure_id INT(11) DEFAULT NULL,
  department_code CHAR(8) DEFAULT NULL,
  cost_center_code VARCHAR(20) DEFAULT NULL,
  profit_center_code VARCHAR(20) DEFAULT NULL,
  budget_currency CHAR(3) NOT NULL DEFAULT 'IDR',
  total_current_headcount DECIMAL(12,2) NOT NULL DEFAULT 0,
  total_planned_headcount DECIMAL(12,2) NOT NULL DEFAULT 0,
  total_requested_headcount DECIMAL(12,2) NOT NULL DEFAULT 0,
  total_gap_headcount DECIMAL(12,2) NOT NULL DEFAULT 0,
  total_budget_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
  approved_by_employee_id INT(11) DEFAULT NULL,
  approved_at DATETIME DEFAULT NULL,
  sap_reference VARCHAR(50) DEFAULT NULL,
  remarks TEXT DEFAULT NULL,
  created_by VARCHAR(50) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_by VARCHAR(50) DEFAULT NULL,
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_erp_manpower_plan_no (plan_no),
  KEY idx_erp_manpower_plan_period (period_from, period_to),
  KEY idx_erp_manpower_plan_status (planning_status),
  KEY idx_erp_manpower_plan_org (company_structure_id, department_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS erp_manpower_plan_detail (
  id INT(11) NOT NULL AUTO_INCREMENT,
  plan_id INT(11) NOT NULL,
  line_no INT(11) NOT NULL,
  department_code CHAR(8) DEFAULT NULL,
  position_id INT(11) DEFAULT NULL,
  job_title_id INT(11) DEFAULT NULL,
  employee_group ENUM('DIRECTOR','MANAGER','STAFF','NON_STAFF','OPERATOR','CONTRACT','DAILY_WORKER','TRAINEE') NOT NULL DEFAULT 'STAFF',
  pay_grade VARCHAR(30) DEFAULT NULL,
  current_headcount DECIMAL(12,2) NOT NULL DEFAULT 0,
  current_fte DECIMAL(12,2) NOT NULL DEFAULT 0,
  planned_headcount DECIMAL(12,2) NOT NULL DEFAULT 0,
  planned_fte DECIMAL(12,2) NOT NULL DEFAULT 0,
  requested_headcount DECIMAL(12,2) NOT NULL DEFAULT 0,
  approved_headcount DECIMAL(12,2) NOT NULL DEFAULT 0,
  gap_headcount DECIMAL(12,2) NOT NULL DEFAULT 0,
  hire_type ENUM('NEW_HIRE','REPLACEMENT','TRANSFER','CONTRACT_EXTENSION','OUTSOURCE') NOT NULL DEFAULT 'NEW_HIRE',
  priority ENUM('LOW','MEDIUM','HIGH','CRITICAL') NOT NULL DEFAULT 'MEDIUM',
  target_hire_date DATE DEFAULT NULL,
  estimated_monthly_cost DECIMAL(18,2) NOT NULL DEFAULT 0,
  budget_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
  recruitment_status ENUM('NOT_STARTED','OPEN','IN_PROGRESS','OFFER','HIRED','CANCELLED') NOT NULL DEFAULT 'NOT_STARTED',
  reason TEXT DEFAULT NULL,
  remarks TEXT DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_erp_manpower_plan_detail_plan (plan_id),
  KEY idx_erp_manpower_plan_detail_position (position_id),
  KEY idx_erp_manpower_plan_detail_job (job_title_id),
  CONSTRAINT fk_erp_manpower_plan_detail_plan FOREIGN KEY (plan_id) REFERENCES erp_manpower_plan(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE sys_menu
   SET nav_act='manpower_planning',
       main_table='erp_manpower_plan',
       icon='fa-users',
       dt_table='Y',
       tampil='Y'
 WHERE url='manpower-planning';

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
 WHERE m.url='manpower-planning'
   AND NOT EXISTS (
     SELECT 1 FROM sys_menu_role r
      WHERE r.id_menu=m.id AND r.group_level=g.level
   );

UPDATE sys_menu_role r
JOIN sys_menu m ON m.id=r.id_menu AND m.url='manpower-planning'
   SET r.read_act='Y',
       r.insert_act=CASE WHEN r.group_level IN ('admin','system_administrator','hrd','manager_approver') THEN 'Y' ELSE 'N' END,
       r.update_act=CASE WHEN r.group_level IN ('admin','system_administrator','hrd','manager_approver') THEN 'Y' ELSE 'N' END,
       r.delete_act=CASE WHEN r.group_level IN ('admin','system_administrator') THEN 'Y' ELSE 'N' END,
       r.import_act='N';
