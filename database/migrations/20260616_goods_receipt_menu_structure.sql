-- SAP-style Goods Receipt menu structure while preserving current transaction URLs.

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT '', 'Goods Receipt', '', '', 'fa-download', 2, 334, 'Warehouse', 'Y', 'Y', 'main'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE parent=334 AND page_name='Goods Receipt' AND type_menu='main');

SET @gr_parent=(SELECT id FROM sys_menu WHERE parent=334 AND page_name='Goods Receipt' AND type_menu='main' LIMIT 1);

UPDATE sys_menu SET page_name='Stock Overview', urutan_menu=1, parent_name='Warehouse' WHERE id=341;
UPDATE sys_menu SET page_name='GR for Purchase Order', parent=@gr_parent, parent_name='Goods Receipt', urutan_menu=1, icon='fa-shopping-cart' WHERE id=315;
UPDATE sys_menu SET page_name='GR from Production Order', parent=@gr_parent, parent_name='Goods Receipt', urutan_menu=5, icon='fa-industry' WHERE id=355;
UPDATE sys_menu SET page_name='Production Receipt Inbox', parent=@gr_parent, parent_name='Goods Receipt', urutan_menu=6, icon='fa-inbox' WHERE id=353;
UPDATE sys_menu SET nav_act='gr_blocked_stock', main_table='pemasukan', page_name='GR Blocked Stock (103)', icon='fa-lock', parent=@gr_parent, parent_name='Goods Receipt', urutan_menu=3, tampil='Y' WHERE url='gr-blocked-stock';
UPDATE sys_menu SET nav_act='release_gr_blocked_stock', main_table='pemasukan', page_name='Release GR Blocked Stock (105)', icon='fa-unlock', parent=@gr_parent, parent_name='Goods Receipt', urutan_menu=4, tampil='Y' WHERE url='release-gr-blocked-stock';
UPDATE sys_menu SET tampil='N' WHERE id=352;

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','GR Without Purchase Order','gr-without-po','pemasukan','fa-file-o',2,@gr_parent,'Goods Receipt','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='gr-without-po');
INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'gr_blocked_stock','GR Blocked Stock (103)','gr-blocked-stock','pemasukan','fa-lock',3,@gr_parent,'Goods Receipt','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='gr-blocked-stock');
INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'release_gr_blocked_stock','Release GR Blocked Stock (105)','release-gr-blocked-stock','pemasukan','fa-unlock',4,@gr_parent,'Goods Receipt','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='release-gr-blocked-stock');
INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Return to Vendor','return-to-vendor','detail_transaksi','fa-reply',7,@gr_parent,'Goods Receipt','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='return-to-vendor');
INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Material Documents','material-documents','detail_transaksi','fa-file-text-o',8,@gr_parent,'Goods Receipt','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='material-documents');
INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','GR Reversal (102)','gr-reversal','detail_transaksi','fa-undo',9,@gr_parent,'Goods Receipt','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='gr-reversal');
INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Customs Receiving Monitor','customs-receiving-monitor','pemasukan','fa-file-text',10,@gr_parent,'Goods Receipt','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='customs-receiving-monitor');

-- Shift the remaining Warehouse groups after Goods Receipt.
UPDATE sys_menu SET urutan_menu=3 WHERE id=317;
UPDATE sys_menu SET urutan_menu=4 WHERE id=338;
UPDATE sys_menu SET urutan_menu=5 WHERE id=349;
UPDATE sys_menu SET urutan_menu=6 WHERE id=350;

-- Parent visibility follows Warehouse access for operational roles.
INSERT INTO sys_menu_role (id_menu,group_level,read_act,insert_act,update_act,delete_act,import_act)
SELECT @gr_parent,r.group_level,r.read_act,'N','N','N','N'
FROM sys_menu_role r
LEFT JOIN sys_menu_role existing ON existing.id_menu=@gr_parent AND existing.group_level=r.group_level
WHERE r.id_menu=334 AND r.read_act='Y' AND existing.id IS NULL;

-- Admin and warehouse can access all GR workspaces. Other roles receive monitoring access where relevant.
INSERT INTO sys_menu_role (id_menu,group_level,read_act,insert_act,update_act,delete_act,import_act)
SELECT m.id,x.group_level,'Y',x.write_act,x.write_act,'N','N'
FROM sys_menu m
JOIN (
 SELECT 'admin' group_level,'Y' write_act UNION ALL
 SELECT 'gudang','Y'
) x
LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=x.group_level
WHERE m.url IN ('gr-without-po','gr-blocked-stock','release-gr-blocked-stock','return-to-vendor','material-documents','gr-reversal','customs-receiving-monitor')
AND r.id IS NULL;

INSERT INTO sys_menu_role (id_menu,group_level,read_act,insert_act,update_act,delete_act,import_act)
SELECT m.id,x.group_level,'Y','N','N','N','N'
FROM sys_menu m
JOIN (
 SELECT 'purchasing' group_level,'material-documents' url UNION ALL
 SELECT 'purchasing','return-to-vendor' UNION ALL
 SELECT 'purchasing','customs-receiving-monitor' UNION ALL
 SELECT 'quality_control','gr-blocked-stock' UNION ALL
 SELECT 'quality_control','release-gr-blocked-stock' UNION ALL
 SELECT 'quality_control','material-documents' UNION ALL
 SELECT 'beacukai','material-documents' UNION ALL
 SELECT 'beacukai','customs-receiving-monitor' UNION ALL
 SELECT 'auditor','material-documents' UNION ALL
 SELECT 'auditor','customs-receiving-monitor'
) x ON x.url=m.url
LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=x.group_level
WHERE r.id IS NULL;

-- Read access to the existing PO receipt for purchasing and customs monitoring.
UPDATE sys_menu_role SET read_act='Y' WHERE id_menu=315 AND group_level IN ('purchasing','beacukai');

-- Production receipt remains operational for warehouse and visible to PPIC.
UPDATE sys_menu_role SET read_act='Y',insert_act='Y',update_act='Y',delete_act='N'
WHERE id_menu IN (353,355) AND group_level='gudang';
UPDATE sys_menu_role SET read_act='Y',insert_act='N',update_act='N',delete_act='N'
WHERE id_menu IN (353,355) AND group_level='ppic';

-- Keep the Goods Receipt branch limited to relevant functions.
UPDATE sys_menu_role SET read_act='N' WHERE id_menu=@gr_parent AND group_level='sales';
