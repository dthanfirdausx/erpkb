UPDATE sys_menu
SET nav_act='customs_inventory_report',
    page_name='Customs Inventory Report',
    url='customs-inventory-report',
    main_table='stock_layer',
    icon='fa-file-text',
    urutan_menu=6,
    parent_name='Warehouse Reports',
    dt_table='Y',
    tampil='Y',
    type_menu='page'
WHERE url='customs-inventory-report';

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id, g.group_level, 'Y', 'N', 'N', 'N', 'N'
FROM sys_menu m
JOIN (SELECT DISTINCT group_level FROM sys_menu_role WHERE group_level IS NOT NULL AND group_level<>'') g
WHERE m.url='customs-inventory-report'
  AND NOT EXISTS (
    SELECT 1 FROM sys_menu_role r
    WHERE r.id_menu=m.id AND r.group_level=g.group_level
  );
