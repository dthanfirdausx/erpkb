UPDATE sys_menu
SET nav_act='team_attendance',
    main_table='erp_attendance',
    icon='fa-users',
    dt_table='N',
    tampil='Y'
WHERE url='team-attendance';

INSERT INTO sys_menu_role (id_menu, group_level, insert_act, update_act, delete_act, read_act)
SELECT m.id, g.level, 'N', 'N', 'N', 'Y'
FROM sys_menu m
JOIN sys_group_users g ON g.level='manager_approver'
WHERE m.url='team-attendance'
  AND NOT EXISTS (
    SELECT 1 FROM sys_menu_role r
    WHERE r.id_menu=m.id AND r.group_level=g.level
  );

UPDATE sys_menu_role r
JOIN sys_menu m ON m.id=r.id_menu
JOIN sys_group_users g ON g.level=r.group_level
SET r.read_act='Y', r.insert_act='N', r.update_act='N', r.delete_act='N'
WHERE m.url='team-attendance' AND g.level='manager_approver';
