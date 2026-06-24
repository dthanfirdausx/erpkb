UPDATE sys_menu
SET nav_act='quality_dashboard',
    page_name='Quality Dashboard',
    main_table='data_ng',
    icon='fa-dashboard',
    dt_table='N',
    tampil='Y',
    type_menu='page'
WHERE url='quality-dashboard';

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id, g.level, 'Y', 'N', 'N', 'N', 'Y'
FROM sys_menu m
JOIN sys_group_users g
LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.level
WHERE m.url='quality-dashboard'
  AND r.id IS NULL;

UPDATE sys_menu_role r
JOIN sys_menu m ON m.id=r.id_menu
SET r.read_act='Y', r.import_act='Y'
WHERE m.url='quality-dashboard';
