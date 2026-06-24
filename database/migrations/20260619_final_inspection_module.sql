UPDATE sys_menu
SET nav_act='final_inspection',
    page_name='Final Inspection',
    main_table='erp_inspection_lot',
    icon='fa-check-square-o',
    dt_table='N',
    tampil='Y',
    type_menu='page'
WHERE url='final-inspection';

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id, g.level, 'Y', 'Y', 'Y', 'N', 'Y'
FROM sys_menu m
JOIN sys_group_users g
LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.level
WHERE m.url='final-inspection'
  AND r.id IS NULL;

UPDATE sys_menu_role r
JOIN sys_menu m ON m.id=r.id_menu
SET r.read_act='Y', r.insert_act='Y', r.update_act='Y', r.import_act='Y'
WHERE m.url='final-inspection';
