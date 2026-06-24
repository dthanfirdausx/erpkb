UPDATE sys_menu
   SET nav_act='employee_report',
       main_table='erp_employee_master',
       icon='fa-file-text-o',
       dt_table='Y',
       tampil='Y'
 WHERE url='employee-report';

UPDATE erp_employee_master
   SET created_by=CASE WHEN created_by IS NULL OR created_by='' OR created_by='codex' THEN 'admin' ELSE created_by END,
       updated_by=CASE WHEN updated_by IS NULL OR updated_by='' OR updated_by='codex' THEN 'admin' ELSE updated_by END,
       updated_at=COALESCE(updated_at,NOW())
 WHERE created_by IS NULL
    OR created_by=''
    OR created_by='codex'
    OR updated_by IS NULL
    OR updated_by=''
    OR updated_by='codex';

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id,g.level,'Y','N','N','N','N'
  FROM sys_menu m JOIN sys_group_users g
 WHERE m.url='employee-report'
   AND NOT EXISTS (SELECT 1 FROM sys_menu_role r WHERE r.id_menu=m.id AND r.group_level=g.level);

UPDATE sys_menu_role r
JOIN sys_menu m ON m.id=r.id_menu AND m.url='employee-report'
   SET r.read_act='Y',
       r.insert_act='N',
       r.update_act='N',
       r.delete_act='N',
       r.import_act='N';
