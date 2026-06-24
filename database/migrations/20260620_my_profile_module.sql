UPDATE sys_menu
SET nav_act='my_profile',
    main_table='erp_employee_master',
    icon='fa-user-circle',
    dt_table='N',
    tampil='Y',
    type_menu='page'
WHERE url='my-profile';

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id, g.level, 'Y', 'N', 'Y', 'N', 'N'
FROM sys_menu m
JOIN sys_group_users g ON g.level='employee_self_service'
WHERE m.url='my-profile'
  AND NOT EXISTS (
    SELECT 1 FROM sys_menu_role r
    WHERE r.id_menu=m.id AND r.group_level=g.level
  );

UPDATE sys_menu_role r
JOIN sys_menu m ON m.id=r.id_menu
SET r.read_act='Y',
    r.insert_act='N',
    r.update_act='Y',
    r.delete_act='N',
    r.import_act='N'
WHERE m.url='my-profile'
  AND r.group_level='employee_self_service';
