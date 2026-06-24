-- =====================================================
-- SALES & DISTRIBUTION MENU STRUCTURE
-- SAP-style SD grouping:
-- Pre-Sales, Sales Order Management, Delivery & Shipping,
-- Billing, Reports
-- =====================================================

SET @sales_parent := (
  SELECT id
  FROM sys_menu
  WHERE parent = 0
    AND (id = 402 OR LOWER(COALESCE(page_name,'')) IN ('sales','sales & distribution'))
  ORDER BY CASE WHEN id = 402 THEN 0 ELSE 1 END, id
  LIMIT 1
);

INSERT INTO sys_menu (nav_act, page_name, url, main_table, icon, urutan_menu, parent, parent_name, dt_table, tampil, type_menu)
SELECT '', 'Sales & Distribution', '', '', 'fa-line-chart', 8, 0, NULL, 'Y', 'Y', 'main'
WHERE @sales_parent IS NULL;

SET @sales_parent := (
  SELECT id
  FROM sys_menu
  WHERE parent = 0
    AND (LOWER(COALESCE(page_name,'')) IN ('sales','sales & distribution') OR id = 402)
  ORDER BY CASE WHEN id = 402 THEN 0 ELSE 1 END, id
  LIMIT 1
);

UPDATE sys_menu
SET nav_act = '',
    page_name = 'Sales & Distribution',
    url = '',
    main_table = '',
    icon = 'fa-line-chart',
    urutan_menu = 8,
    parent = 0,
    parent_name = NULL,
    dt_table = 'Y',
    tampil = 'Y',
    type_menu = 'main'
WHERE id = @sales_parent;

-- Hide SD menus that are outside the requested structure.
UPDATE sys_menu
SET tampil = 'N'
WHERE url IN ('sales-return');

-- =====================================================
-- GROUP HELPERS
-- =====================================================

INSERT INTO sys_menu (nav_act, page_name, url, main_table, icon, urutan_menu, parent, parent_name, dt_table, tampil, type_menu)
SELECT '', 'Pre-Sales', '', '', 'fa-comments-o', 1, @sales_parent, 'Sales & Distribution', 'Y', 'Y', 'main'
WHERE NOT EXISTS (
  SELECT 1 FROM sys_menu WHERE parent = @sales_parent AND page_name = 'Pre-Sales' AND type_menu = 'main'
);

INSERT INTO sys_menu (nav_act, page_name, url, main_table, icon, urutan_menu, parent, parent_name, dt_table, tampil, type_menu)
SELECT '', 'Sales Order Management', '', '', 'fa-shopping-cart', 2, @sales_parent, 'Sales & Distribution', 'Y', 'Y', 'main'
WHERE NOT EXISTS (
  SELECT 1 FROM sys_menu WHERE parent = @sales_parent AND page_name = 'Sales Order Management' AND type_menu = 'main'
);

INSERT INTO sys_menu (nav_act, page_name, url, main_table, icon, urutan_menu, parent, parent_name, dt_table, tampil, type_menu)
SELECT '', 'Delivery & Shipping', '', '', 'fa-truck', 3, @sales_parent, 'Sales & Distribution', 'Y', 'Y', 'main'
WHERE NOT EXISTS (
  SELECT 1 FROM sys_menu WHERE parent = @sales_parent AND page_name = 'Delivery & Shipping' AND type_menu = 'main'
);

INSERT INTO sys_menu (nav_act, page_name, url, main_table, icon, urutan_menu, parent, parent_name, dt_table, tampil, type_menu)
SELECT '', 'Billing', '', '', 'fa-file-text-o', 4, @sales_parent, 'Sales & Distribution', 'Y', 'Y', 'main'
WHERE NOT EXISTS (
  SELECT 1 FROM sys_menu WHERE parent = @sales_parent AND page_name = 'Billing' AND type_menu = 'main'
);

INSERT INTO sys_menu (nav_act, page_name, url, main_table, icon, urutan_menu, parent, parent_name, dt_table, tampil, type_menu)
SELECT '', 'Reports', '', '', 'fa-bar-chart', 5, @sales_parent, 'Sales & Distribution', 'Y', 'Y', 'main'
WHERE NOT EXISTS (
  SELECT 1 FROM sys_menu WHERE parent = @sales_parent AND page_name = 'Reports' AND type_menu = 'main'
);

SET @pre_sales := (SELECT id FROM sys_menu WHERE parent = @sales_parent AND page_name = 'Pre-Sales' AND type_menu = 'main' LIMIT 1);
SET @so_mgmt := (SELECT id FROM sys_menu WHERE parent = @sales_parent AND page_name = 'Sales Order Management' AND type_menu = 'main' LIMIT 1);
SET @delivery := (SELECT id FROM sys_menu WHERE parent = @sales_parent AND page_name = 'Delivery & Shipping' AND type_menu = 'main' LIMIT 1);
SET @billing := (SELECT id FROM sys_menu WHERE parent = @sales_parent AND page_name = 'Billing' AND type_menu = 'main' LIMIT 1);
SET @reports := (SELECT id FROM sys_menu WHERE parent = @sales_parent AND page_name = 'Reports' AND type_menu = 'main' LIMIT 1);

UPDATE sys_menu SET nav_act='', url='', main_table='', parent_name='Sales & Distribution', tampil='Y', type_menu='main'
WHERE id IN (@pre_sales, @so_mgmt, @delivery, @billing, @reports);

UPDATE sys_menu SET icon='fa-comments-o', urutan_menu=1 WHERE id=@pre_sales;
UPDATE sys_menu SET icon='fa-shopping-cart', urutan_menu=2 WHERE id=@so_mgmt;
UPDATE sys_menu SET icon='fa-truck', urutan_menu=3 WHERE id=@delivery;
UPDATE sys_menu SET icon='fa-file-text-o', urutan_menu=4 WHERE id=@billing;
UPDATE sys_menu SET icon='fa-bar-chart', urutan_menu=5 WHERE id=@reports;

-- =====================================================
-- PRE-SALES
-- =====================================================

INSERT INTO sys_menu (nav_act, page_name, url, main_table, icon, urutan_menu, parent, parent_name, dt_table, tampil, type_menu)
SELECT 'erp_workspace', 'Customer Inquiry', 'customer-inquiry', 'sales_inquiry', 'fa-question-circle', 1, @pre_sales, 'Pre-Sales', 'Y', 'Y', 'page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url = 'customer-inquiry');

INSERT INTO sys_menu (nav_act, page_name, url, main_table, icon, urutan_menu, parent, parent_name, dt_table, tampil, type_menu)
SELECT 'erp_workspace', 'Sales Quotation', 'sales-quotation', 'sales_quotation', 'fa-file-text-o', 2, @pre_sales, 'Pre-Sales', 'Y', 'Y', 'page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url = 'sales-quotation');

INSERT INTO sys_menu (nav_act, page_name, url, main_table, icon, urutan_menu, parent, parent_name, dt_table, tampil, type_menu)
SELECT 'erp_workspace', 'Quotation Follow Up', 'quotation-follow-up', 'sales_quotation', 'fa-phone', 3, @pre_sales, 'Pre-Sales', 'Y', 'Y', 'page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url = 'quotation-follow-up');

UPDATE sys_menu
SET parent=@pre_sales, parent_name='Pre-Sales', nav_act='erp_workspace', page_name='Customer Inquiry',
    main_table='sales_inquiry', icon='fa-question-circle', urutan_menu=1, dt_table='Y', tampil='Y', type_menu='page'
WHERE url='customer-inquiry';

UPDATE sys_menu
SET parent=@pre_sales, parent_name='Pre-Sales', nav_act='erp_workspace', page_name='Sales Quotation',
    main_table='sales_quotation', icon='fa-file-text-o', urutan_menu=2, dt_table='Y', tampil='Y', type_menu='page'
WHERE url='sales-quotation';

UPDATE sys_menu
SET parent=@pre_sales, parent_name='Pre-Sales', nav_act='erp_workspace', page_name='Quotation Follow Up',
    main_table='sales_quotation', icon='fa-phone', urutan_menu=3, dt_table='Y', tampil='Y', type_menu='page'
WHERE url='quotation-follow-up';

-- =====================================================
-- SALES ORDER MANAGEMENT
-- =====================================================

INSERT INTO sys_menu (nav_act, page_name, url, main_table, icon, urutan_menu, parent, parent_name, dt_table, tampil, type_menu)
SELECT 'sales_order', 'Sales Order', 'sales-order', 'sales_order', 'fa-shopping-cart', 1, @so_mgmt, 'Sales Order Management', 'Y', 'Y', 'page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url = 'sales-order');

INSERT INTO sys_menu (nav_act, page_name, url, main_table, icon, urutan_menu, parent, parent_name, dt_table, tampil, type_menu)
SELECT 'erp_workspace', 'Sales Order Approval', 'sales-order-approval', 'sales_order', 'fa-check-circle', 2, @so_mgmt, 'Sales Order Management', 'Y', 'Y', 'page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url = 'sales-order-approval');

INSERT INTO sys_menu (nav_act, page_name, url, main_table, icon, urutan_menu, parent, parent_name, dt_table, tampil, type_menu)
SELECT 'erp_workspace', 'Sales Order Monitoring', 'sales-order-monitoring', 'sales_order', 'fa-dashboard', 3, @so_mgmt, 'Sales Order Management', 'Y', 'Y', 'page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url = 'sales-order-monitoring');

UPDATE sys_menu
SET parent=@so_mgmt, parent_name='Sales Order Management', nav_act='sales_order', page_name='Sales Order',
    main_table='sales_order', icon='fa-shopping-cart', urutan_menu=1, dt_table='Y', tampil='Y', type_menu='page'
WHERE url='sales-order';

UPDATE sys_menu
SET parent=@so_mgmt, parent_name='Sales Order Management', nav_act='erp_workspace', page_name='Sales Order Approval',
    main_table='sales_order', icon='fa-check-circle', urutan_menu=2, dt_table='Y', tampil='Y', type_menu='page'
WHERE url='sales-order-approval';

UPDATE sys_menu
SET parent=@so_mgmt, parent_name='Sales Order Management', nav_act='erp_workspace', page_name='Sales Order Monitoring',
    main_table='sales_order', icon='fa-dashboard', urutan_menu=3, dt_table='Y', tampil='Y', type_menu='page'
WHERE url='sales-order-monitoring';

-- =====================================================
-- DELIVERY & SHIPPING
-- =====================================================

INSERT INTO sys_menu (nav_act, page_name, url, main_table, icon, urutan_menu, parent, parent_name, dt_table, tampil, type_menu)
SELECT 'erp_workspace', 'Outbound Delivery', 'outbound-delivery', 'sales_order', 'fa-truck', 1, @delivery, 'Delivery & Shipping', 'Y', 'Y', 'page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url = 'outbound-delivery');

INSERT INTO sys_menu (nav_act, page_name, url, main_table, icon, urutan_menu, parent, parent_name, dt_table, tampil, type_menu)
SELECT 'picking_pengeluaran', 'Picking', 'picking-pengeluaran', 'pengeluaran_temp', 'fa-list-alt', 2, @delivery, 'Delivery & Shipping', 'Y', 'Y', 'page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url = 'picking-pengeluaran');

INSERT INTO sys_menu (nav_act, page_name, url, main_table, icon, urutan_menu, parent, parent_name, dt_table, tampil, type_menu)
SELECT 'packing_list', 'Packing List', 'packing-list', 'packing_list', 'fa-archive', 3, @delivery, 'Delivery & Shipping', 'Y', 'Y', 'page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url = 'packing-list');

INSERT INTO sys_menu (nav_act, page_name, url, main_table, icon, urutan_menu, parent, parent_name, dt_table, tampil, type_menu)
SELECT 'surat_jalan', 'Surat Jalan', 'surat-jalan', 'surat_jalan', 'fa-file-text-o', 4, @delivery, 'Delivery & Shipping', 'Y', 'Y', 'page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url = 'surat-jalan');

INSERT INTO sys_menu (nav_act, page_name, url, main_table, icon, urutan_menu, parent, parent_name, dt_table, tampil, type_menu)
SELECT 'pengeluaran_hamparan', 'Goods Issue for Delivery', 'pengeluaran-hamparan', 'pengeluaran_hamparan', 'fa-upload', 5, @delivery, 'Delivery & Shipping', 'Y', 'Y', 'page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url = 'pengeluaran-hamparan');

INSERT INTO sys_menu (nav_act, page_name, url, main_table, icon, urutan_menu, parent, parent_name, dt_table, tampil, type_menu)
SELECT 'erp_workspace', 'Delivery History', 'delivery-history', 'surat_jalan', 'fa-history', 6, @delivery, 'Delivery & Shipping', 'Y', 'Y', 'page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url = 'delivery-history');

UPDATE sys_menu
SET parent=@delivery, parent_name='Delivery & Shipping', nav_act='erp_workspace', page_name='Outbound Delivery',
    main_table='sales_order', icon='fa-truck', urutan_menu=1, dt_table='Y', tampil='Y', type_menu='page'
WHERE url='outbound-delivery';

UPDATE sys_menu
SET parent=@delivery, parent_name='Delivery & Shipping', nav_act='picking_pengeluaran', page_name='Picking',
    main_table='pengeluaran_temp', icon='fa-list-alt', urutan_menu=2, dt_table='Y', tampil='Y', type_menu='page'
WHERE url='picking-pengeluaran';

UPDATE sys_menu
SET parent=@delivery, parent_name='Delivery & Shipping', nav_act='packing_list', page_name='Packing List',
    main_table='packing_list', icon='fa-archive', urutan_menu=3, dt_table='Y', tampil='Y', type_menu='page'
WHERE url='packing-list';

UPDATE sys_menu
SET parent=@delivery, parent_name='Delivery & Shipping', nav_act='surat_jalan', page_name='Surat Jalan',
    main_table='surat_jalan', icon='fa-file-text-o', urutan_menu=4, dt_table='Y', tampil='Y', type_menu='page'
WHERE url='surat-jalan';

UPDATE sys_menu
SET parent=@delivery, parent_name='Delivery & Shipping', nav_act='pengeluaran_hamparan', page_name='Goods Issue for Delivery',
    main_table='pengeluaran_hamparan', icon='fa-upload', urutan_menu=5, dt_table='Y', tampil='Y', type_menu='page'
WHERE url='pengeluaran-hamparan';

UPDATE sys_menu
SET parent=@delivery, parent_name='Delivery & Shipping', nav_act='erp_workspace', page_name='Delivery History',
    main_table='surat_jalan', icon='fa-history', urutan_menu=6, dt_table='Y', tampil='Y', type_menu='page'
WHERE url='delivery-history';

-- Keep old inbound picking hidden. Outbound picking uses picking-pengeluaran.
UPDATE sys_menu
SET tampil='N'
WHERE url='picking';

-- =====================================================
-- BILLING
-- =====================================================

INSERT INTO sys_menu (nav_act, page_name, url, main_table, icon, urutan_menu, parent, parent_name, dt_table, tampil, type_menu)
SELECT 'sales_invoice', 'Sales Invoice', 'sales-invoice', 'sales_invoice', 'fa-file-text', 1, @billing, 'Billing', 'Y', 'Y', 'page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url = 'sales-invoice');

INSERT INTO sys_menu (nav_act, page_name, url, main_table, icon, urutan_menu, parent, parent_name, dt_table, tampil, type_menu)
SELECT 'erp_workspace', 'Billing History', 'billing-history', 'sales_invoice', 'fa-history', 2, @billing, 'Billing', 'Y', 'Y', 'page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url = 'billing-history');

UPDATE sys_menu
SET parent=@billing, parent_name='Billing', nav_act='sales_invoice', page_name='Sales Invoice',
    main_table='sales_invoice', icon='fa-file-text', urutan_menu=1, dt_table='Y', tampil='Y', type_menu='page'
WHERE url='sales-invoice';

UPDATE sys_menu
SET parent=@billing, parent_name='Billing', nav_act='erp_workspace', page_name='Billing History',
    main_table='sales_invoice', icon='fa-history', urutan_menu=2, dt_table='Y', tampil='Y', type_menu='page'
WHERE url='billing-history';

-- =====================================================
-- REPORTS
-- =====================================================

INSERT INTO sys_menu (nav_act, page_name, url, main_table, icon, urutan_menu, parent, parent_name, dt_table, tampil, type_menu)
SELECT 'erp_workspace', 'Sales Quotation Report', 'sales-quotation-report', 'sales_quotation', 'fa-file-text-o', 1, @reports, 'Reports', 'Y', 'Y', 'page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url = 'sales-quotation-report');

INSERT INTO sys_menu (nav_act, page_name, url, main_table, icon, urutan_menu, parent, parent_name, dt_table, tampil, type_menu)
SELECT 'erp_workspace', 'Sales Order Report', 'sales-order-report', 'sales_order', 'fa-bar-chart', 2, @reports, 'Reports', 'Y', 'Y', 'page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url = 'sales-order-report');

INSERT INTO sys_menu (nav_act, page_name, url, main_table, icon, urutan_menu, parent, parent_name, dt_table, tampil, type_menu)
SELECT 'erp_workspace', 'Delivery Report', 'delivery-report', 'surat_jalan', 'fa-truck', 3, @reports, 'Reports', 'Y', 'Y', 'page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url = 'delivery-report');

INSERT INTO sys_menu (nav_act, page_name, url, main_table, icon, urutan_menu, parent, parent_name, dt_table, tampil, type_menu)
SELECT 'erp_workspace', 'Billing Report', 'billing-report', 'sales_invoice', 'fa-money', 4, @reports, 'Reports', 'Y', 'Y', 'page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url = 'billing-report');

UPDATE sys_menu
SET parent=@reports, parent_name='Reports', nav_act='erp_workspace', page_name='Sales Quotation Report',
    main_table='sales_quotation', icon='fa-file-text-o', urutan_menu=1, dt_table='Y', tampil='Y', type_menu='page'
WHERE url='sales-quotation-report';

UPDATE sys_menu
SET parent=@reports, parent_name='Reports', nav_act='erp_workspace', page_name='Sales Order Report',
    main_table='sales_order', icon='fa-bar-chart', urutan_menu=2, dt_table='Y', tampil='Y', type_menu='page'
WHERE url='sales-order-report';

UPDATE sys_menu
SET parent=@reports, parent_name='Reports', nav_act='erp_workspace', page_name='Delivery Report',
    main_table='surat_jalan', icon='fa-truck', urutan_menu=3, dt_table='Y', tampil='Y', type_menu='page'
WHERE url='delivery-report';

UPDATE sys_menu
SET parent=@reports, parent_name='Reports', nav_act='erp_workspace', page_name='Billing Report',
    main_table='sales_invoice', icon='fa-money', urutan_menu=4, dt_table='Y', tampil='Y', type_menu='page'
WHERE url='billing-report';

-- =====================================================
-- MENU ROLES
-- Ensure every visible Sales & Distribution menu has a role row.
-- Existing module permissions are preserved.
-- =====================================================

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id, g.group_level, 'Y', 'N', 'N', 'N', 'N'
FROM sys_menu m
JOIN (
  SELECT DISTINCT group_level
  FROM sys_menu_role
  WHERE COALESCE(group_level,'') <> ''
) g
WHERE m.tampil = 'Y'
  AND (
    m.id = @sales_parent
    OR m.parent IN (@sales_parent, @pre_sales, @so_mgmt, @delivery, @billing, @reports)
  )
  AND NOT EXISTS (
    SELECT 1
    FROM sys_menu_role r
    WHERE r.id_menu = m.id
      AND r.group_level = g.group_level
  );
