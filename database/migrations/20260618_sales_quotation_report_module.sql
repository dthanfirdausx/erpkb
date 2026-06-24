UPDATE sys_menu
SET nav_act='sales_quotation_report',
    page_name='Sales Quotation Report',
    url='sales-quotation-report',
    main_table='sales_quotation',
    icon='fa-file-text-o',
    tampil='Y',
    dt_table='Y',
    type_menu='page'
WHERE url='sales-quotation-report';

INSERT INTO sys_menu_role (id_menu,group_level,read_act,insert_act,update_act,delete_act,import_act)
SELECT m.id,g.group_level,'Y','Y','Y','Y','Y'
FROM sys_menu m
JOIN (SELECT DISTINCT group_level FROM sys_menu_role WHERE COALESCE(group_level,'')<>'') g
LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.group_level
WHERE m.url='sales-quotation-report' AND r.id IS NULL;

UPDATE sys_menu_role r
JOIN sys_menu m ON m.id=r.id_menu
SET r.read_act='Y',r.insert_act='Y',r.update_act='Y',r.delete_act='Y',r.import_act='Y'
WHERE m.url='sales-quotation-report';
