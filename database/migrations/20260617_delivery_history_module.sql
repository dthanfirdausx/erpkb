-- =====================================================
-- DELIVERY HISTORY MODULE
-- SAP SD document flow monitoring
-- =====================================================

UPDATE sys_menu
SET nav_act='delivery_history',
    main_table='erp_outbound_delivery',
    page_name='Delivery History',
    tampil='Y'
WHERE url='delivery-history';

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id, g.group_level, 'Y', 'N', 'N', 'N', 'N'
FROM sys_menu m
JOIN (
  SELECT DISTINCT group_level FROM sys_menu_role WHERE COALESCE(group_level,'') <> ''
) g
WHERE m.url='delivery-history'
  AND NOT EXISTS (
    SELECT 1 FROM sys_menu_role r WHERE r.id_menu=m.id AND r.group_level=g.group_level
  );
