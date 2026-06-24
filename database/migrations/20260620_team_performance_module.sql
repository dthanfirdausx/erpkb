UPDATE sys_menu
SET nav_act='team_performance',
    main_table='erp_performance_appraisal',
    icon='fa-line-chart',
    dt_table='N',
    tampil='Y'
WHERE url='team-performance';

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id, g.level, 'Y', 'N', 'N', 'N', 'N'
FROM sys_menu m
JOIN sys_group_users g ON g.level='manager_approver'
WHERE m.url='team-performance'
  AND NOT EXISTS (
    SELECT 1 FROM sys_menu_role r
    WHERE r.id_menu=m.id AND r.group_level=g.level
  );

UPDATE sys_menu_role r
JOIN sys_menu m ON m.id=r.id_menu
JOIN sys_group_users g ON g.level=r.group_level
SET r.read_act='Y', r.insert_act='N', r.update_act='N', r.delete_act='N'
WHERE m.url='team-performance' AND g.level='manager_approver';
