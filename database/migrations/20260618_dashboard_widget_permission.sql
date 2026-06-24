-- Dashboard widget permission master.
-- This migration creates role-based widget permissions for Management Dashboard.

CREATE TABLE IF NOT EXISTS dashboard_widget (
  id INT AUTO_INCREMENT PRIMARY KEY,
  widget_code VARCHAR(80) NOT NULL,
  widget_name VARCHAR(150) NOT NULL,
  widget_category VARCHAR(80) NOT NULL,
  widget_type ENUM('KPI','CHART','TABLE','ALERT','LINK') NOT NULL DEFAULT 'KPI',
  source_module VARCHAR(80) NULL,
  source_url VARCHAR(120) NULL,
  icon VARCHAR(60) NULL,
  color VARCHAR(30) NULL,
  sequence_no INT NOT NULL DEFAULT 0,
  is_active ENUM('Y','N') NOT NULL DEFAULT 'Y',
  description VARCHAR(255) NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  UNIQUE KEY uk_dashboard_widget_code (widget_code),
  KEY idx_dashboard_widget_category (widget_category, sequence_no),
  KEY idx_dashboard_widget_active (is_active, sequence_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS dashboard_widget_role (
  id INT AUTO_INCREMENT PRIMARY KEY,
  widget_id INT NOT NULL,
  group_level VARCHAR(50) NOT NULL,
  can_view ENUM('Y','N') NOT NULL DEFAULT 'Y',
  can_drilldown ENUM('Y','N') NOT NULL DEFAULT 'Y',
  can_export ENUM('Y','N') NOT NULL DEFAULT 'N',
  sequence_no INT NOT NULL DEFAULT 0,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  UNIQUE KEY uk_dashboard_widget_role (widget_id, group_level),
  KEY idx_dashboard_widget_role_group (group_level, can_view),
  CONSTRAINT fk_dashboard_widget_role_widget FOREIGN KEY (widget_id) REFERENCES dashboard_widget(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS dashboard_user_preference (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  widget_id INT NOT NULL,
  is_pinned ENUM('Y','N') NOT NULL DEFAULT 'Y',
  is_hidden ENUM('Y','N') NOT NULL DEFAULT 'N',
  grid_x INT NOT NULL DEFAULT 0,
  grid_y INT NOT NULL DEFAULT 0,
  grid_w INT NOT NULL DEFAULT 3,
  grid_h INT NOT NULL DEFAULT 1,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  UNIQUE KEY uk_dashboard_user_widget (user_id, widget_id),
  KEY idx_dashboard_user_preference_user (user_id, is_hidden),
  CONSTRAINT fk_dashboard_user_preference_widget FOREIGN KEY (widget_id) REFERENCES dashboard_widget(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO dashboard_widget
  (widget_code, widget_name, widget_category, widget_type, source_module, source_url, icon, color, sequence_no, description, created_at, updated_at)
VALUES
  ('MGMT_COMPANY_SCORECARD','Company Scorecard','Management','KPI','management_dashboard','management-dashboard','fa-dashboard','navy',10,'Ringkasan KPI lintas divisi untuk manajemen.',NOW(),NOW()),
  ('MGMT_PROFIT_LOSS_MTD','Profit & Loss MTD','Finance','KPI','laporan_rugi_laba','laporan-laba-rugi','fa-line-chart','green',20,'Laba rugi bulan berjalan.',NOW(),NOW()),
  ('FIN_CASH_BANK_BALANCE','Cash & Bank Balance','Finance','KPI','bank_reconciliation','bank-reconciliation','fa-bank','aqua',30,'Saldo kas dan bank untuk monitoring likuiditas.',NOW(),NOW()),
  ('FIN_AR_OVERDUE','AR Aging Overdue','Finance','ALERT','ar_aging','ar-aging','fa-clock-o','yellow',40,'Piutang jatuh tempo berdasarkan AR Aging.',NOW(),NOW()),
  ('FIN_AP_OVERDUE','AP Aging Overdue','Finance','ALERT','ap_aging','ap-aging','fa-clock-o','red',50,'Utang jatuh tempo berdasarkan AP Aging.',NOW(),NOW()),
  ('FIN_VENDOR_INVOICE_DUE','Vendor Invoice Due','Finance','ALERT','vendor_invoice','vendor-invoice','fa-file-text-o','orange',60,'Vendor invoice yang perlu dibayar.',NOW(),NOW()),
  ('FIN_CUSTOMER_INVOICE_OPEN','Customer Invoice Open','Finance','ALERT','customer_invoice','customer-invoice','fa-file-text','blue',70,'Customer invoice outstanding.',NOW(),NOW()),
  ('FIN_JOURNAL_DRAFT','Journal Draft / Unposted','Finance','ALERT','jurnal_umum','jurnal-umum','fa-book','yellow',80,'Jurnal draft atau belum posting.',NOW(),NOW()),
  ('FIN_CLOSING_STATUS','Financial Closing Status','Finance','ALERT','financial_closing','financial-closing','fa-lock','purple',90,'Status closing period dan blocker.',NOW(),NOW()),
  ('FIN_VAT_POSITION','VAT Position','Tax','KPI','vat_report','vat-report','fa-percent','teal',100,'Posisi PPN masukan dan keluaran.',NOW(),NOW()),

  ('WH_STOCK_VALUE','Inventory Value','Warehouse','KPI','inventory_valuation_report','inventory-valuation-report','fa-cubes','green',110,'Nilai persediaan berdasarkan stock valuation.',NOW(),NOW()),
  ('WH_STOCK_CRITICAL','Critical Stock','Warehouse','ALERT','stock_overview','stock-overview','fa-warning','red',120,'Material dengan stok kritis atau negatif.',NOW(),NOW()),
  ('WH_GR_TODAY','Goods Receipt Today','Warehouse','KPI','goods_receipt_report','goods-receipt-report','fa-download','blue',130,'Penerimaan barang hari ini.',NOW(),NOW()),
  ('WH_GI_TODAY','Goods Issue Today','Warehouse','KPI','goods_issue_report','goods-issue-report','fa-upload','orange',140,'Pengeluaran barang hari ini.',NOW(),NOW()),
  ('WH_SLOW_MOVING','Slow Moving Stock','Warehouse','ALERT','slow_moving_stock','slow-moving-stock','fa-hourglass-half','yellow',150,'Material slow moving.',NOW(),NOW()),
  ('WH_BATCH_TRACE','Batch / Lot Traceability','Warehouse','LINK','batch_lot_traceability','batch-lot-traceability','fa-random','purple',160,'Akses cepat trace batch dan lot.',NOW(),NOW()),
  ('WH_CUSTOMS_STOCK','Customs Stock Traceability','Customs','LINK','customs_stock_traceability','customs-stock-traceability','fa-file-text-o','teal',170,'Trace stok berdasarkan dokumen pabean.',NOW(),NOW()),
  ('WH_TRANSFER_PENDING','Transfer Pending','Warehouse','ALERT','transfer_history','transfer-history','fa-exchange','aqua',180,'Transfer posting yang perlu monitoring.',NOW(),NOW()),
  ('WH_BLOCKED_STOCK','Blocked Stock','Quality','ALERT','release_gr_blocked_stock','release-gr-blocked-stock','fa-ban','red',190,'Stok blocked yang perlu release/keputusan.',NOW(),NOW()),

  ('PUR_PR_PENDING','PR Pending Approval','Purchasing','ALERT','approval_center','approval-center','fa-check-square-o','yellow',200,'Purchase requisition menunggu approval.',NOW(),NOW()),
  ('PUR_RFQ_PENDING','RFQ Pending','Purchasing','ALERT','rfq','request-for-quotation','fa-envelope-o','orange',210,'RFQ yang masih proses.',NOW(),NOW()),
  ('PUR_PO_OUTSTANDING','PO Outstanding','Purchasing','ALERT','purchase_order','purchase-order','fa-shopping-cart','blue',220,'PO yang belum selesai diterima.',NOW(),NOW()),
  ('PUR_GR_PENDING','GR Pending for PO','Purchasing','ALERT','gr_for_po','gr-for-po','fa-truck','green',230,'PO yang perlu ditindaklanjuti penerimaannya.',NOW(),NOW()),
  ('PUR_VENDOR_EVALUATION','Vendor Evaluation','Purchasing','KPI','vendor_evaluation','vendor-evaluation','fa-star-half-o','purple',240,'Evaluasi vendor berdasarkan performa.',NOW(),NOW()),

  ('SD_QUOTATION_FOLLOWUP','Quotation Follow Up','Sales','ALERT','quotation_follow_up','quotation-follow-up','fa-comments-o','yellow',250,'Quotation yang perlu follow up.',NOW(),NOW()),
  ('SD_SALES_ORDER_OPEN','Sales Order Open','Sales','KPI','sales_order_monitoring','sales-order-monitoring','fa-shopping-bag','blue',260,'Sales order open dan status pemenuhannya.',NOW(),NOW()),
  ('SD_DELIVERY_PENDING','Delivery Pending','Sales','ALERT','outbound_delivery','outbound-delivery','fa-truck','orange',270,'Outbound delivery yang belum selesai.',NOW(),NOW()),
  ('SD_BILLING_PENDING','Billing Pending','Sales','ALERT','sales_invoice','sales-invoice','fa-file-text','red',280,'Delivery/order yang belum invoice.',NOW(),NOW()),
  ('SD_BILLING_HISTORY','Billing History','Sales','LINK','billing_history','billing-history','fa-history','green',290,'Akses history billing.',NOW(),NOW()),

  ('PP_MRP_EXCEPTION','MRP Exception','PPIC','ALERT','mrp','mrp','fa-cogs','red',300,'Exception MRP dan kebutuhan material.',NOW(),NOW()),
  ('PP_MATERIAL_SHORTAGE','Material Shortage','PPIC','ALERT','material_availability','material-availability','fa-warning','yellow',310,'Material shortage untuk produksi.',NOW(),NOW()),
  ('PP_PROD_ORDER_PLAN','Production Order Plan','PPIC','KPI','production_order_monitoring','production-order-monitoring','fa-industry','blue',320,'Monitoring production order planned/released.',NOW(),NOW()),
  ('PP_WIP_MONITORING','WIP Monitoring','PPIC','KPI','wip_monitoring','wip-monitoring','fa-tasks','purple',330,'Work in process dan order berjalan.',NOW(),NOW()),
  ('PP_FORECAST_DEMAND','Forecast & Demand','PPIC','CHART','demand_management','demand-management','fa-area-chart','aqua',340,'Demand dan forecast planning.',NOW(),NOW()),

  ('PRD_MY_ORDERS','My Production Orders','Production','TABLE','my_production_orders','my-production-orders','fa-list','blue',350,'Production order yang menjadi tanggung jawab produksi.',NOW(),NOW()),
  ('PRD_OUTPUT_TODAY','Production Output Today','Production','KPI','output_monitoring','output-monitoring','fa-check','green',360,'Output produksi hari ini.',NOW(),NOW()),
  ('PRD_CONFIRMATION_PENDING','Confirmation Pending','Production','ALERT','production_confirmation','production-confirmation','fa-pencil-square-o','yellow',370,'Konfirmasi produksi yang belum selesai.',NOW(),NOW()),
  ('PRD_DOWNTIME_TODAY','Downtime Today','Production','ALERT','input_downtime','input-downtime','fa-clock-o','red',380,'Downtime produksi hari ini.',NOW(),NOW()),
  ('PRD_ACTIVITY_LOG','Production Activity Log','Production','LINK','production_activity_log','production-activity-log','fa-history','purple',390,'Log aktivitas shop floor.',NOW(),NOW()),

  ('QC_INCOMING_PENDING','Incoming QC Pending','Quality','ALERT','gr_blocked_stock','gr-blocked-stock','fa-search','yellow',400,'Material masuk yang perlu quality decision.',NOW(),NOW()),
  ('QC_PRODUCTION_QUALITY','Production Quality Issue','Quality','ALERT','production_monitoring','production-monitoring','fa-shield','red',410,'Issue mutu di produksi.',NOW(),NOW()),
  ('QC_BLOCKED_RELEASE','Blocked Stock Release','Quality','ALERT','release_gr_blocked_stock','release-gr-blocked-stock','fa-unlock','orange',420,'Release blocked stock setelah keputusan QC.',NOW(),NOW()),

  ('CUS_BC_IN_OUT','Customs In/Out Summary','Customs','KPI','customs_inventory_report','customs-inventory-report','fa-exchange','teal',430,'Ringkasan pemasukan/pengeluaran dokumen BC.',NOW(),NOW()),
  ('CUS_BC_DOC_PENDING','BC Document Pending','Customs','ALERT','customs_receiving_monitor','customs-receiving-monitor','fa-file-o','yellow',440,'Dokumen pabean yang perlu dilengkapi.',NOW(),NOW()),
  ('CUS_TRACEABILITY_ALERT','Customs Traceability Alert','Customs','ALERT','customs_stock_traceability','customs-stock-traceability','fa-sitemap','red',450,'Alert traceability dokumen pabean.',NOW(),NOW()),

  ('SYS_USER_ACTIVITY','User Activity Log','System','TABLE','log_aktifitas','log-aktifitas','fa-history','gray',460,'Monitoring aktivitas user.',NOW(),NOW()),
  ('SYS_MENU_ROLE_COVERAGE','Menu Role Coverage','System','KPI','menu_management','group-permission','fa-key','purple',470,'Monitoring kelengkapan role menu.',NOW(),NOW()),
  ('SYS_INTEGRATION_HEALTH','Integration Health','System','ALERT','service_permission','service-permission','fa-plug','red',480,'Status integrasi dan service permission.',NOW(),NOW())
ON DUPLICATE KEY UPDATE
  widget_name=VALUES(widget_name),
  widget_category=VALUES(widget_category),
  widget_type=VALUES(widget_type),
  source_module=VALUES(source_module),
  source_url=VALUES(source_url),
  icon=VALUES(icon),
  color=VALUES(color),
  sequence_no=VALUES(sequence_no),
  description=VALUES(description),
  is_active='Y',
  updated_at=NOW();

INSERT INTO dashboard_widget_role
  (widget_id, group_level, can_view, can_drilldown, can_export, sequence_no, created_at, updated_at)
SELECT w.id, g.level, 'Y', 'Y',
       CASE
         WHEN g.level IN ('admin','system_administrator','manager_approver','auditor','finance_akunting') THEN 'Y'
         ELSE 'N'
       END can_export,
       w.sequence_no, NOW(), NOW()
FROM dashboard_widget w
JOIN sys_group_users g
WHERE
  g.level IN ('admin','system_administrator')
  OR (g.level='manager_approver' AND w.widget_category IN ('Management','Finance','Warehouse','Purchasing','Sales','PPIC','Production','Quality','Customs'))
  OR (g.level='auditor' AND w.widget_category IN ('Finance','Warehouse','Purchasing','Sales','PPIC','Production','Quality','Customs','System'))
  OR (g.level='finance_akunting' AND w.widget_code IN (
    'MGMT_PROFIT_LOSS_MTD','FIN_CASH_BANK_BALANCE','FIN_AR_OVERDUE','FIN_AP_OVERDUE','FIN_VENDOR_INVOICE_DUE',
    'FIN_CUSTOMER_INVOICE_OPEN','FIN_JOURNAL_DRAFT','FIN_CLOSING_STATUS','FIN_VAT_POSITION','WH_STOCK_VALUE',
    'SD_BILLING_PENDING','SD_BILLING_HISTORY'
  ))
  OR (g.level='gudang' AND w.widget_code IN (
    'WH_STOCK_VALUE','WH_STOCK_CRITICAL','WH_GR_TODAY','WH_GI_TODAY','WH_SLOW_MOVING','WH_BATCH_TRACE',
    'WH_CUSTOMS_STOCK','WH_TRANSFER_PENDING','WH_BLOCKED_STOCK','PUR_PO_OUTSTANDING','PUR_GR_PENDING',
    'PP_MATERIAL_SHORTAGE','CUS_BC_IN_OUT','CUS_TRACEABILITY_ALERT'
  ))
  OR (g.level='beacukai' AND w.widget_code IN (
    'WH_CUSTOMS_STOCK','CUS_BC_IN_OUT','CUS_BC_DOC_PENDING','CUS_TRACEABILITY_ALERT','WH_GR_TODAY','WH_GI_TODAY',
    'WH_BATCH_TRACE','WH_STOCK_VALUE'
  ))
  OR (g.level='purchasing' AND w.widget_code IN (
    'PUR_PR_PENDING','PUR_RFQ_PENDING','PUR_PO_OUTSTANDING','PUR_GR_PENDING','PUR_VENDOR_EVALUATION',
    'FIN_VENDOR_INVOICE_DUE','WH_GR_TODAY','WH_BLOCKED_STOCK'
  ))
  OR (g.level='sales' AND w.widget_code IN (
    'SD_QUOTATION_FOLLOWUP','SD_SALES_ORDER_OPEN','SD_DELIVERY_PENDING','SD_BILLING_PENDING','SD_BILLING_HISTORY',
    'FIN_AR_OVERDUE','FIN_CUSTOMER_INVOICE_OPEN'
  ))
  OR (g.level='ppic' AND w.widget_code IN (
    'PP_MRP_EXCEPTION','PP_MATERIAL_SHORTAGE','PP_PROD_ORDER_PLAN','PP_WIP_MONITORING','PP_FORECAST_DEMAND',
    'WH_STOCK_CRITICAL','WH_STOCK_VALUE','PUR_PO_OUTSTANDING','PUR_GR_PENDING','PRD_OUTPUT_TODAY'
  ))
  OR (g.level='produksi' AND w.widget_code IN (
    'PRD_MY_ORDERS','PRD_OUTPUT_TODAY','PRD_CONFIRMATION_PENDING','PRD_DOWNTIME_TODAY','PRD_ACTIVITY_LOG',
    'PP_WIP_MONITORING','PP_MATERIAL_SHORTAGE','WH_BATCH_TRACE'
  ))
  OR (g.level='quality_control' AND w.widget_code IN (
    'QC_INCOMING_PENDING','QC_PRODUCTION_QUALITY','QC_BLOCKED_RELEASE','WH_BLOCKED_STOCK','WH_BATCH_TRACE',
    'PRD_OUTPUT_TODAY','PP_WIP_MONITORING'
  ))
ON DUPLICATE KEY UPDATE
  can_view='Y',
  can_drilldown='Y',
  can_export=VALUES(can_export),
  sequence_no=VALUES(sequence_no),
  updated_at=NOW();

-- Keep existing roles, but mark widgets inactive for groups that no longer exist.
UPDATE dashboard_widget_role r
LEFT JOIN sys_group_users g ON g.level = r.group_level
SET r.can_view = 'N', r.updated_at = NOW()
WHERE g.level IS NULL;
