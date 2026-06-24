ALTER TABLE dept
  ADD COLUMN IF NOT EXISTS dept_short_name varchar(50) NULL AFTER nm_dept,
  ADD COLUMN IF NOT EXISTS dept_type enum('FUNCTIONAL','OPERATIONAL','SUPPORT','SALES','PRODUCTION','WAREHOUSE','QUALITY','FINANCE','HR') NOT NULL DEFAULT 'FUNCTIONAL' AFTER dept_short_name,
  ADD COLUMN IF NOT EXISTS parent_dept_code char(8) NULL AFTER dept_type,
  ADD COLUMN IF NOT EXISTS company_structure_id int(11) NULL AFTER parent_dept_code,
  ADD COLUMN IF NOT EXISTS cost_center_code varchar(20) NULL AFTER company_structure_id,
  ADD COLUMN IF NOT EXISTS profit_center_code varchar(20) NULL AFTER cost_center_code,
  ADD COLUMN IF NOT EXISTS manager_user_id int(11) NULL AFTER profit_center_code,
  ADD COLUMN IF NOT EXISTS functional_area varchar(50) NULL AFTER manager_user_id,
  ADD COLUMN IF NOT EXISTS valid_from date NOT NULL DEFAULT '2026-01-01' AFTER functional_area,
  ADD COLUMN IF NOT EXISTS valid_to date NOT NULL DEFAULT '9999-12-31' AFTER valid_from,
  ADD COLUMN IF NOT EXISTS status enum('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE' AFTER valid_to,
  ADD COLUMN IF NOT EXISTS sap_reference varchar(50) NULL AFTER status,
  ADD COLUMN IF NOT EXISTS remarks text NULL AFTER sap_reference,
  ADD COLUMN IF NOT EXISTS created_by varchar(50) NULL AFTER remarks,
  ADD COLUMN IF NOT EXISTS created_at datetime NOT NULL DEFAULT current_timestamp() AFTER created_by,
  ADD COLUMN IF NOT EXISTS updated_by varchar(50) NULL AFTER created_at,
  ADD COLUMN IF NOT EXISTS updated_at datetime NULL AFTER updated_by;

CREATE INDEX IF NOT EXISTS idx_dept_parent ON dept(parent_dept_code);
CREATE INDEX IF NOT EXISTS idx_dept_company_structure ON dept(company_structure_id);
CREATE INDEX IF NOT EXISTS idx_dept_cost_center ON dept(cost_center_code);
CREATE INDEX IF NOT EXISTS idx_dept_profit_center ON dept(profit_center_code);
CREATE INDEX IF NOT EXISTS idx_dept_status ON dept(status);

UPDATE sys_menu
   SET nav_act='department', main_table='dept', icon='fa-sitemap', dt_table='Y', tampil='Y'
 WHERE url='hrd-department';

INSERT INTO sys_menu_role (id_menu,group_level,read_act,insert_act,update_act,delete_act,import_act)
SELECT m.id,g.level,'Y',
       IF(g.level IN ('admin','system_administrator','hrd'),'Y','N'),
       IF(g.level IN ('admin','system_administrator','hrd'),'Y','N'),
       IF(g.level IN ('admin','system_administrator'),'Y','N'),
       'N'
FROM sys_menu m
JOIN sys_group_users g
LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.level
WHERE m.url='hrd-department' AND r.id IS NULL;
