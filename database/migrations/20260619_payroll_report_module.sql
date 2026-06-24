UPDATE sys_menu
SET nav_act='payroll_report',
    main_table='erp_payroll_history',
    icon='fa-file-text-o',
    dt_table='Y',
    tampil='Y'
WHERE url='payroll-report';

INSERT INTO sys_menu_role (id_menu,group_level,read_act,insert_act,update_act,delete_act,import_act)
SELECT m.id,g.level,'Y','N','N','N','N'
FROM sys_group_users g
JOIN sys_menu m ON m.url='payroll-report'
WHERE NOT EXISTS (
  SELECT 1 FROM sys_menu_role r WHERE r.group_level=g.level AND r.id_menu=m.id
);

UPDATE sys_menu_role r
JOIN sys_menu m ON m.id=r.id_menu AND m.url='payroll-report'
SET r.read_act='Y',
    r.insert_act='N',
    r.update_act='N',
    r.delete_act='N',
    r.import_act='N';

UPDATE erp_payroll_process
SET created_by='admin',
    updated_by='admin'
WHERE COALESCE(created_by,'')<>'admin'
   OR COALESCE(updated_by,'')<>'admin';

UPDATE erp_payroll_posting
SET created_by='admin',
    updated_by='admin'
WHERE COALESCE(created_by,'')<>'admin'
   OR COALESCE(updated_by,'')<>'admin';

UPDATE erp_payslip
SET created_by='admin',
    updated_by='admin'
WHERE COALESCE(created_by,'')<>'admin'
   OR COALESCE(updated_by,'')<>'admin';

UPDATE erp_payroll_history
SET created_by='admin',
    updated_by='admin'
WHERE COALESCE(created_by,'')<>'admin'
   OR COALESCE(updated_by,'')<>'admin';
