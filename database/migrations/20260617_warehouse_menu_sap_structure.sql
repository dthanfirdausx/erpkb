-- SAP-style Warehouse menu structure.
-- Developed modules keep their real nav_act; not-yet-developed menus point to erp_workspace.

SET @warehouse_parent := 334;

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT '', 'Goods Receipt', '', '', 'fa-download', 1, @warehouse_parent, 'Warehouse', 'Y', 'Y', 'main'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE parent=@warehouse_parent AND page_name='Goods Receipt' AND type_menu='main');
SET @gr_parent := (SELECT id FROM sys_menu WHERE parent=@warehouse_parent AND page_name='Goods Receipt' AND type_menu='main' LIMIT 1);
UPDATE sys_menu SET parent=@warehouse_parent,parent_name='Warehouse',icon='fa-download',urutan_menu=1,tampil='Y',type_menu='main' WHERE id=@gr_parent;

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT '', 'Goods Issue', '', '', 'fa-upload', 2, @warehouse_parent, 'Warehouse', 'Y', 'Y', 'main'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE parent=@warehouse_parent AND page_name='Goods Issue' AND type_menu='main');
SET @gi_parent := (SELECT id FROM sys_menu WHERE parent=@warehouse_parent AND page_name='Goods Issue' AND type_menu='main' LIMIT 1);
UPDATE sys_menu SET parent=@warehouse_parent,parent_name='Warehouse',icon='fa-upload',urutan_menu=2,tampil='Y',type_menu='main' WHERE id=@gi_parent;

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT '', 'Stock Transfer', '', '', 'fa-exchange', 3, @warehouse_parent, 'Warehouse', 'Y', 'Y', 'main'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE parent=@warehouse_parent AND page_name='Stock Transfer' AND type_menu='main');
SET @st_parent := (SELECT id FROM sys_menu WHERE parent=@warehouse_parent AND page_name='Stock Transfer' AND type_menu='main' LIMIT 1);
UPDATE sys_menu SET parent=@warehouse_parent,parent_name='Warehouse',icon='fa-exchange',urutan_menu=3,tampil='Y',type_menu='main' WHERE id=@st_parent;

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT '', 'Inventory Management', '', '', 'fa-cubes', 4, @warehouse_parent, 'Warehouse', 'Y', 'Y', 'main'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE parent=@warehouse_parent AND page_name='Inventory Management' AND type_menu='main');
SET @im_parent := (SELECT id FROM sys_menu WHERE parent=@warehouse_parent AND page_name='Inventory Management' AND type_menu='main' LIMIT 1);
UPDATE sys_menu SET parent=@warehouse_parent,parent_name='Warehouse',icon='fa-cubes',urutan_menu=4,tampil='Y',type_menu='main' WHERE id=@im_parent;

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT '', 'Physical Inventory', '', '', 'fa-check-square-o', 5, @warehouse_parent, 'Warehouse', 'Y', 'Y', 'main'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE parent=@warehouse_parent AND page_name='Physical Inventory' AND type_menu='main');
SET @pi_parent := (SELECT id FROM sys_menu WHERE parent=@warehouse_parent AND page_name='Physical Inventory' AND type_menu='main' LIMIT 1);
UPDATE sys_menu SET parent=@warehouse_parent,parent_name='Warehouse',icon='fa-check-square-o',urutan_menu=5,tampil='Y',type_menu='main' WHERE id=@pi_parent;

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT '', 'Warehouse Reports', '', '', 'fa-bar-chart', 6, @warehouse_parent, 'Warehouse', 'Y', 'Y', 'main'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE parent=@warehouse_parent AND LOWER(page_name)='warehouse reports' AND type_menu='main');
SET @wr_parent := (SELECT id FROM sys_menu WHERE parent=@warehouse_parent AND LOWER(page_name)='warehouse reports' AND type_menu='main' ORDER BY id LIMIT 1);
UPDATE sys_menu SET page_name='Warehouse Reports',parent=@warehouse_parent,parent_name='Warehouse',icon='fa-bar-chart',urutan_menu=6,tampil='Y',type_menu='main' WHERE id=@wr_parent;

-- Goods Receipt
UPDATE sys_menu SET page_name='GR for Purchase Order',url='pemasukan-hamparan',nav_act='pemasukan_hamparan',main_table='pemasukan_baru',icon='fa-download',urutan_menu=1,parent=@gr_parent,parent_name='Goods Receipt',dt_table='Y',tampil='Y',type_menu='page' WHERE url='pemasukan-hamparan';
UPDATE sys_menu SET page_name='GR without Purchase Order',url='gr-without-po',nav_act='gr_without_po',main_table='pemasukan',icon='fa-download',urutan_menu=2,parent=@gr_parent,parent_name='Goods Receipt',dt_table='Y',tampil='Y',type_menu='page' WHERE url='gr-without-po';
UPDATE sys_menu SET page_name='GR Blocked Stock',url='gr-blocked-stock',nav_act='gr_blocked_stock',main_table='pemasukan',icon='fa-lock',urutan_menu=3,parent=@gr_parent,parent_name='Goods Receipt',dt_table='Y',tampil='Y',type_menu='page' WHERE url='gr-blocked-stock';
UPDATE sys_menu SET page_name='Release GR Blocked Stock',url='release-gr-blocked-stock',nav_act='release_gr_blocked_stock',main_table='pemasukan',icon='fa-unlock',urutan_menu=4,parent=@gr_parent,parent_name='Goods Receipt',dt_table='Y',tampil='Y',type_menu='page' WHERE url='release-gr-blocked-stock';
UPDATE sys_menu SET page_name='GR from Production Order',url='gr-from-production-order',nav_act='gr_from_production_order',main_table='erp_gr_production',icon='fa-industry',urutan_menu=5,parent=@gr_parent,parent_name='Goods Receipt',dt_table='Y',tampil='Y',type_menu='page' WHERE id=355 OR url IN ('incoming-terima','gr-from-production-order');
UPDATE sys_menu SET page_name='Return to Vendor',url='return-to-vendor',nav_act='return_to_vendor',main_table='erp_vendor_return',icon='fa-reply',urutan_menu=6,parent=@gr_parent,parent_name='Goods Receipt',dt_table='Y',tampil='Y',type_menu='page' WHERE url='return-to-vendor';
UPDATE sys_menu SET page_name='Material Documents',url='material-documents',nav_act='material_document',main_table='detail_transaksi',icon='fa-file-text-o',urutan_menu=7,parent=@gr_parent,parent_name='Goods Receipt',dt_table='Y',tampil='Y',type_menu='page' WHERE url='material-documents';
UPDATE sys_menu SET page_name='Customs Receiving Monitor',url='customs-receiving-monitor',nav_act='customs_receiving_monitor',main_table='pemasukan',icon='fa-file-text',urutan_menu=8,parent=@gr_parent,parent_name='Goods Receipt',dt_table='Y',tampil='Y',type_menu='page' WHERE url='customs-receiving-monitor';

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'customs_receiving_monitor','Customs Receiving Monitor','customs-receiving-monitor','pemasukan','fa-file-text',8,@gr_parent,'Goods Receipt','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='customs-receiving-monitor');

-- Goods Issue
UPDATE sys_menu SET page_name='Issue to Production',url='issue-to-production',nav_act='issue_to_production',main_table='erp_issue_production',icon='fa-industry',urutan_menu=1,parent=@gi_parent,parent_name='Goods Issue',dt_table='Y',tampil='Y',type_menu='page' WHERE url='issue-to-production';
UPDATE sys_menu SET page_name='Issue to Cost Center',url='issue-to-cost-center',nav_act='issue_to_cost_center',main_table='erp_issue_cost_center',icon='fa-building-o',urutan_menu=2,parent=@gi_parent,parent_name='Goods Issue',dt_table='Y',tampil='Y',type_menu='page' WHERE url='issue-to-cost-center';
UPDATE sys_menu SET page_name='Issue to Asset',url='issue-to-asset',nav_act='issue_to_asset',main_table='erp_issue_asset',icon='fa-cubes',urutan_menu=3,parent=@gi_parent,parent_name='Goods Issue',dt_table='Y',tampil='Y',type_menu='page' WHERE url='issue-to-asset';
UPDATE sys_menu SET page_name='Scrap Issue',url='scrap-issue',nav_act='scrap_issue',main_table='erp_scrap_issue',icon='fa-trash-o',urutan_menu=4,parent=@gi_parent,parent_name='Goods Issue',dt_table='Y',tampil='Y',type_menu='page' WHERE url='scrap-issue';
UPDATE sys_menu SET page_name='Sample Issue',url='sample-issue',nav_act='sample_issue',main_table='erp_sample_issue',icon='fa-flask',urutan_menu=5,parent=@gi_parent,parent_name='Goods Issue',dt_table='Y',tampil='Y',type_menu='page' WHERE url='sample-issue';
UPDATE sys_menu SET page_name='Return to Vendor',url='gi-return-to-vendor',nav_act='erp_workspace',main_table='erp_vendor_return',icon='fa-reply',urutan_menu=6,parent=@gi_parent,parent_name='Goods Issue',dt_table='Y',tampil='Y',type_menu='page' WHERE url='gi-return-to-vendor';
UPDATE sys_menu SET page_name='Other Goods Issue',url='other-goods-issue',nav_act='other_goods_issue',main_table='erp_other_goods_issue',icon='fa-sign-out',urutan_menu=7,parent=@gi_parent,parent_name='Goods Issue',dt_table='Y',tampil='Y',type_menu='page' WHERE url='other-goods-issue';
UPDATE sys_menu SET page_name='Goods Issue History',url='goods-issue-history',nav_act='goods_issue_history',main_table='detail_transaksi',icon='fa-history',urutan_menu=8,parent=@gi_parent,parent_name='Goods Issue',dt_table='Y',tampil='Y',type_menu='page' WHERE url='goods-issue-history';

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Return to Vendor','gi-return-to-vendor','erp_vendor_return','fa-reply',6,@gi_parent,'Goods Issue','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='gi-return-to-vendor');

-- Stock Transfer
UPDATE sys_menu SET page_name='Transfer Posting',url='transfer-posting',nav_act='transfer_produksi',main_table='transfer',icon='fa-exchange',urutan_menu=1,parent=@st_parent,parent_name='Stock Transfer',dt_table='Y',tampil='Y',type_menu='page' WHERE id=350 OR url IN ('transfer-produksi','transfer-posting');

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Storage Location Transfer','storage-location-transfer','detail_transaksi','fa-map-marker',2,@st_parent,'Stock Transfer','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='storage-location-transfer');
INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Storage Bin Transfer','storage-bin-transfer','detail_transaksi','fa-th',3,@st_parent,'Stock Transfer','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='storage-bin-transfer');
INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Stock Type Transfer','stock-type-transfer','detail_transaksi','fa-tags',4,@st_parent,'Stock Transfer','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='stock-type-transfer');
INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Transfer History','transfer-history','detail_transaksi','fa-history',5,@st_parent,'Stock Transfer','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='transfer-history');

-- Inventory Management
UPDATE sys_menu SET page_name='Stock Overview',url='stock-pemasukan',nav_act='stock_pemasukan',main_table='vtotalstockpemasukan',icon='fa-cubes',urutan_menu=1,parent=@im_parent,parent_name='Inventory Management',dt_table='Y',tampil='Y',type_menu='page' WHERE url='stock-pemasukan';
INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Stock Card','stock-card','detail_transaksi','fa-list-alt',2,@im_parent,'Inventory Management','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='stock-card');
INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Stock Aging','stock-aging','stock_layer','fa-clock-o',3,@im_parent,'Inventory Management','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='stock-aging');
INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Batch / Lot Traceability','batch-lot-traceability','stock_layer','fa-random',4,@im_parent,'Inventory Management','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='batch-lot-traceability');
INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Customs Stock Traceability','customs-stock-traceability','stock_layer','fa-file-text',5,@im_parent,'Inventory Management','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='customs-stock-traceability');
INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Slow Moving Stock','slow-moving-stock','stock_layer','fa-hourglass-half',6,@im_parent,'Inventory Management','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='slow-moving-stock');

-- Physical Inventory
INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Cycle Count','cycle-count','stock_layer','fa-refresh',1,@pi_parent,'Physical Inventory','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='cycle-count');
INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Stock Opname','stock-opname','stock_layer','fa-check-square-o',2,@pi_parent,'Physical Inventory','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='stock-opname');
INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Count Entry','count-entry','stock_layer','fa-pencil-square-o',3,@pi_parent,'Physical Inventory','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='count-entry');
INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Difference Posting','difference-posting','detail_transaksi','fa-balance-scale',4,@pi_parent,'Physical Inventory','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='difference-posting');
INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Physical Inventory History','physical-inventory-history','detail_transaksi','fa-history',5,@pi_parent,'Physical Inventory','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='physical-inventory-history');

-- Warehouse Reports
INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Material Movement Report','material-movement-report','detail_transaksi','fa-file-text-o',1,@wr_parent,'Warehouse Reports','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='material-movement-report');
INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Goods Receipt Report','goods-receipt-report','detail_transaksi','fa-download',2,@wr_parent,'Warehouse Reports','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='goods-receipt-report');
INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Goods Issue Report','goods-issue-report','detail_transaksi','fa-upload',3,@wr_parent,'Warehouse Reports','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='goods-issue-report');
INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Transfer Posting Report','transfer-posting-report','detail_transaksi','fa-exchange',4,@wr_parent,'Warehouse Reports','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='transfer-posting-report');
INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Inventory Valuation Report','inventory-valuation-report','stock_layer','fa-money',5,@wr_parent,'Warehouse Reports','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='inventory-valuation-report');
INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Customs Inventory Report','customs-inventory-report','stock_layer','fa-file-text',6,@wr_parent,'Warehouse Reports','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='customs-inventory-report');

UPDATE sys_menu
SET tampil='N'
WHERE parent=@wr_parent
  AND COALESCE(url,'') NOT IN (
    'material-movement-report',
    'goods-receipt-report',
    'goods-issue-report',
    'transfer-posting-report',
    'inventory-valuation-report',
    'customs-inventory-report'
  );

-- Hide old direct Warehouse menus and legacy placeholders that are not part of the requested structure.
UPDATE sys_menu
SET tampil='N'
WHERE parent=@warehouse_parent
  AND id NOT IN (@gr_parent,@gi_parent,@st_parent,@im_parent,@pi_parent,@wr_parent)
  AND COALESCE(url,'') IN ('packing-list','picking','transfer-produksi','stock-pemasukan');

UPDATE sys_menu
SET tampil='N'
WHERE parent IN (@gr_parent,@gi_parent)
  AND url IN ('inbox-incoming','gr-reversal','picking-pengeluaran','gi-production-order','gi-production-reversal','gi-cost-center','pengeluaran-hamparan','surat-jalan','mutasi-scrap','gi-sampling-rnd','goods-issue-monitor','outbound-customs-monitor');

-- Read-only access for workspace/monitor pages. Transaction modules keep their existing insert/update policies unless already configured.
INSERT INTO sys_menu_role (id_menu,group_level,read_act,insert_act,update_act,delete_act,import_act)
SELECT m.id,g.level,'Y','N','N','N','N'
FROM sys_menu m
JOIN sys_group_users g ON g.level IN ('admin','system_administrator','gudang','produksi','ppic','sales','beacukai','auditor','finance_akunting','manager_approver','quality_control')
LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.level
WHERE m.url IN (
  'storage-location-transfer','storage-bin-transfer','stock-type-transfer','transfer-history',
  'stock-card','stock-aging','batch-lot-traceability','customs-stock-traceability','slow-moving-stock',
  'cycle-count','stock-opname','count-entry','difference-posting','physical-inventory-history',
  'material-movement-report','goods-receipt-report','goods-issue-report','transfer-posting-report','inventory-valuation-report','customs-inventory-report',
  'gi-return-to-vendor'
)
AND r.id IS NULL;

UPDATE sys_menu_role r
JOIN sys_menu m ON m.id=r.id_menu
SET r.read_act='Y',
    r.insert_act=CASE WHEN m.nav_act='erp_workspace' THEN 'N' ELSE r.insert_act END,
    r.update_act=CASE WHEN m.nav_act='erp_workspace' THEN 'N' ELSE r.update_act END,
    r.delete_act=CASE WHEN m.nav_act='erp_workspace' THEN 'N' ELSE r.delete_act END,
    r.import_act='N'
WHERE m.url IN (
  'storage-location-transfer','storage-bin-transfer','stock-type-transfer','transfer-history',
  'stock-card','stock-aging','batch-lot-traceability','customs-stock-traceability','slow-moving-stock',
  'cycle-count','stock-opname','count-entry','difference-posting','physical-inventory-history',
  'material-movement-report','goods-receipt-report','goods-issue-report','transfer-posting-report','inventory-valuation-report','customs-inventory-report',
  'gi-return-to-vendor'
);
