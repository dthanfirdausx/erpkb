CREATE TABLE IF NOT EXISTS erp_leave_approval (
  id INT(11) NOT NULL AUTO_INCREMENT,
  approval_no VARCHAR(30) NOT NULL,
  leave_request_id INT(11) NOT NULL,
  approval_step ENUM('MANAGER','HR','FINAL') NOT NULL DEFAULT 'MANAGER',
  approver_employee_id INT(11) DEFAULT NULL,
  decision ENUM('PENDING','APPROVED','REJECTED','RETURNED','CANCELLED') NOT NULL DEFAULT 'PENDING',
  decision_date DATETIME DEFAULT NULL,
  approval_note TEXT DEFAULT NULL,
  previous_status VARCHAR(40) DEFAULT NULL,
  new_status VARCHAR(40) DEFAULT NULL,
  created_by VARCHAR(50) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_by VARCHAR(50) DEFAULT NULL,
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_leave_approval_no (approval_no),
  KEY idx_leave_approval_request (leave_request_id),
  KEY idx_leave_approval_decision (decision,approval_step)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

UPDATE sys_menu
   SET nav_act='leave_approval',
       main_table='erp_leave_approval',
       icon='fa-check',
       dt_table='Y',
       tampil='Y'
 WHERE url='leave-approval';

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id, g.level, 'Y',
       CASE WHEN g.level IN ('admin','system_administrator','hrd','manager_approver') THEN 'Y' ELSE 'N' END,
       CASE WHEN g.level IN ('admin','system_administrator','hrd','manager_approver') THEN 'Y' ELSE 'N' END,
       CASE WHEN g.level IN ('admin','system_administrator') THEN 'Y' ELSE 'N' END,
       'N'
  FROM sys_menu m
  JOIN sys_group_users g ON g.level IN ('admin','system_administrator','hrd','manager_approver','auditor')
 WHERE m.url='leave-approval'
   AND NOT EXISTS (SELECT 1 FROM sys_menu_role r WHERE r.id_menu=m.id AND r.group_level=g.level);
