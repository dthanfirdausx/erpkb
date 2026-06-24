UPDATE sys_menu
SET nav_act='approval_center',
    main_table='purchase_requisition_approval',
    page_name='Approval Center',
    icon='fa-check-square-o',
    tampil='Y'
WHERE url='approval-center';

UPDATE sys_menu_role
SET read_act='Y', update_act='Y', insert_act='N', delete_act='N', import_act='N'
WHERE id_menu=(SELECT id FROM sys_menu WHERE url='approval-center' LIMIT 1)
  AND group_level IN ('admin','system_administrator','manager_approver','purchasing');

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id, g.level, 'Y', 'N', 'Y', 'N', 'N'
FROM sys_menu m
JOIN sys_group_users g ON g.level IN ('admin','system_administrator','manager_approver','purchasing')
LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.level
WHERE m.url='approval-center'
  AND r.id IS NULL;
