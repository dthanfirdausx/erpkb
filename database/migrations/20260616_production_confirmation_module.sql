-- Production Confirmation module support.

ALTER TABLE production_order_confirmation
  ADD COLUMN IF NOT EXISTS confirmation_no varchar(30) NULL AFTER id_confirmation,
  ADD COLUMN IF NOT EXISTS document_date date NULL AFTER confirmation_date,
  ADD COLUMN IF NOT EXISTS posting_date date NULL AFTER document_date,
  ADD COLUMN IF NOT EXISTS work_center varchar(50) NULL AFTER operation_no,
  ADD COLUMN IF NOT EXISTS operation_name varchar(100) NULL AFTER work_center,
  ADD COLUMN IF NOT EXISTS operator_name varchar(100) NULL AFTER operation_name,
  ADD COLUMN IF NOT EXISTS shift_code varchar(50) NULL AFTER operator_name,
  ADD COLUMN IF NOT EXISTS start_time datetime NULL AFTER shift_code,
  ADD COLUMN IF NOT EXISTS end_time datetime NULL AFTER start_time,
  ADD COLUMN IF NOT EXISTS labor_time decimal(18,2) DEFAULT 0.00 AFTER end_time,
  ADD COLUMN IF NOT EXISTS machine_time decimal(18,2) DEFAULT 0.00 AFTER labor_time,
  ADD COLUMN IF NOT EXISTS final_confirmation enum('Y','N') DEFAULT 'N' AFTER machine_time,
  ADD COLUMN IF NOT EXISTS status enum('POSTED','REVERSED') DEFAULT 'POSTED' AFTER final_confirmation,
  ADD COLUMN IF NOT EXISTS reversed_by varchar(100) NULL AFTER created_at,
  ADD COLUMN IF NOT EXISTS reversed_at datetime NULL AFTER reversed_by,
  ADD COLUMN IF NOT EXISTS reversal_reason text NULL AFTER reversed_at;

ALTER TABLE production_order_confirmation
  ADD UNIQUE KEY IF NOT EXISTS uq_confirmation_no (confirmation_no),
  ADD KEY IF NOT EXISTS idx_confirmation_date (confirmation_date),
  ADD KEY IF NOT EXISTS idx_posting_date (posting_date),
  ADD KEY IF NOT EXISTS idx_status (status),
  ADD KEY IF NOT EXISTS idx_operation_no (operation_no);

UPDATE sys_menu
SET nav_act='production_confirmation',
    main_table='production_order_confirmation',
    icon='fa-check-square-o',
    dt_table='Y',
    tampil='Y',
    type_menu='page'
WHERE url IN ('production-confirmation','ppic-production-confirmation');

INSERT INTO sys_menu_role (id_menu,group_level,read_act,insert_act,update_act,delete_act,import_act)
SELECT m.id,g.level,'Y',
       CASE WHEN g.level IN ('admin','system_administrator','ppic','produksi') THEN 'Y' ELSE 'N' END,
       CASE WHEN g.level IN ('admin','system_administrator','ppic','produksi') THEN 'Y' ELSE 'N' END,
       'N','N'
FROM sys_menu m
JOIN sys_group_users g ON g.level IN ('admin','system_administrator','ppic','produksi','quality_control','gudang','manager_approver','auditor')
LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.level
WHERE m.url IN ('production-confirmation','ppic-production-confirmation')
  AND r.id IS NULL;

UPDATE sys_menu_role r
JOIN sys_menu m ON m.id=r.id_menu
SET r.read_act='Y',
    r.insert_act=CASE WHEN r.group_level IN ('admin','system_administrator','ppic','produksi') THEN 'Y' ELSE r.insert_act END,
    r.update_act=CASE WHEN r.group_level IN ('admin','system_administrator','ppic','produksi') THEN 'Y' ELSE r.update_act END,
    r.delete_act='N',
    r.import_act='N'
WHERE m.url IN ('production-confirmation','ppic-production-confirmation');
