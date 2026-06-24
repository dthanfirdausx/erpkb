UPDATE sys_menu
SET nav_act='goods_receipt_report',
    page_name='Goods Receipt Report',
    url='goods-receipt-report',
    main_table='detail_transaksi',
    icon='fa-download',
    urutan_menu=2,
    parent_name='Warehouse Reports',
    dt_table='Y',
    tampil='Y',
    type_menu='page'
WHERE url='goods-receipt-report';

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id, g.group_level, 'Y', 'N', 'N', 'N', 'N'
FROM sys_menu m
JOIN (SELECT DISTINCT group_level FROM sys_menu_role WHERE group_level IS NOT NULL AND group_level<>'') g
WHERE m.url='goods-receipt-report'
  AND NOT EXISTS (
    SELECT 1 FROM sys_menu_role r
    WHERE r.id_menu=m.id AND r.group_level=g.group_level
  );
