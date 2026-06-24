CREATE TABLE IF NOT EXISTS erp_system_config (
  id INT(11) NOT NULL AUTO_INCREMENT,
  config_group VARCHAR(50) NOT NULL,
  config_key VARCHAR(100) NOT NULL,
  config_label VARCHAR(150) NOT NULL,
  config_value TEXT NULL,
  default_value TEXT NULL,
  value_type ENUM('TEXT','NUMBER','DECIMAL','BOOLEAN','SELECT','PASSWORD','DATE','URL','EMAIL') NOT NULL DEFAULT 'TEXT',
  options_json TEXT NULL,
  description VARCHAR(255) NULL,
  sort_order INT(11) NOT NULL DEFAULT 0,
  is_sensitive ENUM('Y','N') NOT NULL DEFAULT 'N',
  updated_by VARCHAR(80) NULL,
  updated_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_erp_system_config_key (config_key),
  KEY idx_erp_system_config_group (config_group, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO erp_system_config
(config_group, config_key, config_label, config_value, default_value, value_type, options_json, description, sort_order, is_sensitive, updated_by, updated_at)
VALUES
('COMPANY_KB','default_company_code','Default Company Code','KB01','KB01','TEXT',NULL,'Kode company default untuk laporan, jurnal, dan dokumen.',10,'N','system',NOW()),
('COMPANY_KB','default_plant_code','Default Plant','PL01','PL01','TEXT',NULL,'Plant default untuk transaksi warehouse, produksi, dan laporan KB.',20,'N','system',NOW()),
('COMPANY_KB','default_currency','Default Currency','IDR','IDR','TEXT',NULL,'Mata uang lokal utama.',30,'N','system',NOW()),
('COMPANY_KB','fiscal_year_variant','Fiscal Year Variant','K4','K4','TEXT',NULL,'Variant tahun fiskal standar calendar year.',40,'N','system',NOW()),
('COMPANY_KB','kb_facility_type','Jenis Fasilitas KB','KAWASAN_BERIKAT','KAWASAN_BERIKAT','SELECT','["KAWASAN_BERIKAT","GB","PLB","KITE"]','Jenis fasilitas kepabeanan perusahaan.',50,'N','system',NOW()),

('DOCUMENT_NUMBERING','pr_number_format','PR Number Format','PR{YYYY}{MM}{00005}','PR{YYYY}{MM}{00005}','TEXT',NULL,'Format nomor Purchase Requisition.',10,'N','system',NOW()),
('DOCUMENT_NUMBERING','po_number_format','PO Number Format','PO{YYYY}{MM}{00005}','PO{YYYY}{MM}{00005}','TEXT',NULL,'Format nomor Purchase Order.',20,'N','system',NOW()),
('DOCUMENT_NUMBERING','gr_number_format','GR Number Format','GR{YYYY}{MM}{00005}','GR{YYYY}{MM}{00005}','TEXT',NULL,'Format nomor Goods Receipt.',30,'N','system',NOW()),
('DOCUMENT_NUMBERING','gi_number_format','GI Number Format','GI{YYYY}{MM}{00005}','GI{YYYY}{MM}{00005}','TEXT',NULL,'Format nomor Goods Issue.',40,'N','system',NOW()),
('DOCUMENT_NUMBERING','journal_number_format','Journal Number Format','FI{YYYY}{MM}{00005}','FI{YYYY}{MM}{00005}','TEXT',NULL,'Format nomor jurnal umum otomatis.',50,'N','system',NOW()),
('DOCUMENT_NUMBERING','production_order_format','Production Order Format','PROD{YYYY}{MM}{00005}','PROD{YYYY}{MM}{00005}','TEXT',NULL,'Format nomor production order.',60,'N','system',NOW()),

('POSTING_RULES','auto_post_gr','Auto Journal GR','Y','Y','BOOLEAN',NULL,'GR yang relevan otomatis posting jurnal.',10,'N','system',NOW()),
('POSTING_RULES','auto_post_gi','Auto Journal GI','Y','Y','BOOLEAN',NULL,'GI yang relevan otomatis posting jurnal.',20,'N','system',NOW()),
('POSTING_RULES','auto_post_adjustment','Auto Journal Stock Adjustment','Y','Y','BOOLEAN',NULL,'Stock adjustment otomatis posting jurnal.',30,'N','system',NOW()),
('POSTING_RULES','allow_unbalanced_journal','Allow Unbalanced Journal','N','N','BOOLEAN',NULL,'Jika N, jurnal posted wajib balance.',40,'N','system',NOW()),
('POSTING_RULES','default_inventory_account','Default Inventory Account','14300','14300','TEXT',NULL,'Akun default persediaan jika mapping spesifik belum ada.',50,'N','system',NOW()),
('POSTING_RULES','default_wip_account','Default WIP Account','14302','14302','TEXT',NULL,'Akun default work in process.',60,'N','system',NOW()),

('INVENTORY_RULES','prevent_negative_stock','Prevent Negative Stock','Y','Y','BOOLEAN',NULL,'Mencegah transaksi yang membuat stock minus.',10,'N','system',NOW()),
('INVENTORY_RULES','batch_lot_mandatory','Batch/Lot Mandatory','Y','Y','BOOLEAN',NULL,'Batch/lot wajib untuk material inventory-managed.',20,'N','system',NOW()),
('INVENTORY_RULES','customs_doc_mandatory_in_kb','Customs Doc Mandatory in KB','Y','Y','BOOLEAN',NULL,'Dokumen pabean wajib untuk stock kawasan berikat.',30,'N','system',NOW()),
('INVENTORY_RULES','default_stock_type','Default Stock Type','UNRESTRICTED','UNRESTRICTED','SELECT','["UNRESTRICTED","QUALITY","BLOCKED"]','Stock type default saat GR.',40,'N','system',NOW()),
('INVENTORY_RULES','stock_opname_requires_open_doc','Stock Opname Requires Open Doc','Y','Y','BOOLEAN',NULL,'Stock opname dibuat dari saldo stock/dokumen open.',50,'N','system',NOW()),

('CUSTOMS_CEISA','ceisa_base_url','CEISA Base URL','https://apis-gw.beacukai.go.id','https://apis-gw.beacukai.go.id','URL',NULL,'Endpoint base CEISA 4.0.',10,'N','system',NOW()),
('CUSTOMS_CEISA','ceisa_sender_code','CEISA Sender Code','','','TEXT',NULL,'Kode sender/identity untuk host-to-host CEISA.',20,'N','system',NOW()),
('CUSTOMS_CEISA','ceisa_username','CEISA Username','','','TEXT',NULL,'Username API CEISA.',30,'N','system',NOW()),
('CUSTOMS_CEISA','ceisa_password','CEISA Password','','','PASSWORD',NULL,'Password API CEISA, disimpan sebagai parameter sensitif.',40,'Y','system',NOW()),
('CUSTOMS_CEISA','default_bc_in','Default BC Masuk','BC 2.3','BC 2.3','TEXT',NULL,'Default dokumen pabean masuk.',50,'N','system',NOW()),
('CUSTOMS_CEISA','default_bc_out_local','Default BC Keluar Lokal','BC 2.5','BC 2.5','TEXT',NULL,'Default dokumen pabean keluar lokal.',60,'N','system',NOW()),

('APPROVAL_WORKFLOW','pr_approval_required','PR Approval Required','Y','Y','BOOLEAN',NULL,'Purchase Requisition wajib approval.',10,'N','system',NOW()),
('APPROVAL_WORKFLOW','po_approval_required','PO Approval Required','Y','Y','BOOLEAN',NULL,'Purchase Order wajib approval.',20,'N','system',NOW()),
('APPROVAL_WORKFLOW','so_approval_required','SO Approval Required','Y','Y','BOOLEAN',NULL,'Sales Order wajib approval.',30,'N','system',NOW()),
('APPROVAL_WORKFLOW','fi_posting_approval_required','FI Posting Approval Required','N','N','BOOLEAN',NULL,'Jurnal FI butuh approval sebelum posted.',40,'N','system',NOW()),
('APPROVAL_WORKFLOW','approval_escalation_days','Approval Escalation Days','3','3','NUMBER',NULL,'Hari sebelum approval dianggap overdue/escalated.',50,'N','system',NOW()),

('INTEGRATION','smtp_host','SMTP Host','','','TEXT',NULL,'Host email untuk notifikasi sistem.',10,'N','system',NOW()),
('INTEGRATION','smtp_port','SMTP Port','587','587','NUMBER',NULL,'Port SMTP.',20,'N','system',NOW()),
('INTEGRATION','smtp_user','SMTP User','','','TEXT',NULL,'User SMTP.',30,'N','system',NOW()),
('INTEGRATION','smtp_password','SMTP Password','','','PASSWORD',NULL,'Password SMTP.',40,'Y','system',NOW()),
('INTEGRATION','attendance_sync_mode','Attendance Sync Mode','DUMMY','DUMMY','SELECT','["DUMMY","MACHINE_API","FILE_IMPORT"]','Mode integrasi mesin attendance.',50,'N','system',NOW()),
('INTEGRATION','ftp_deploy_enabled','FTP Deploy Enabled','N','N','BOOLEAN',NULL,'Flag deployment via FTP.',60,'N','system',NOW()),

('SECURITY_AUDIT','activity_log_enabled','Activity Log Enabled','Y','Y','BOOLEAN',NULL,'Aktifkan audit log aktivitas.',10,'N','system',NOW()),
('SECURITY_AUDIT','hide_guest_log','Hide Guest Log','Y','Y','BOOLEAN',NULL,'Log user guest tidak ditampilkan di menu log aktivitas.',20,'N','system',NOW()),
('SECURITY_AUDIT','login_as_enabled','Login As Enabled','Y','Y','BOOLEAN',NULL,'Super admin boleh login as user lain.',30,'N','system',NOW()),
('SECURITY_AUDIT','session_timeout_minutes','Session Timeout Minutes','60','60','NUMBER',NULL,'Timeout session user dalam menit.',40,'N','system',NOW()),
('SECURITY_AUDIT','password_min_length','Password Minimum Length','8','8','NUMBER',NULL,'Minimum panjang password.',50,'N','system',NOW())
ON DUPLICATE KEY UPDATE
  config_label=VALUES(config_label),
  default_value=VALUES(default_value),
  value_type=VALUES(value_type),
  options_json=VALUES(options_json),
  description=VALUES(description),
  sort_order=VALUES(sort_order),
  is_sensitive=VALUES(is_sensitive);

UPDATE sys_menu
SET nav_act='system_configuration', main_table='erp_system_config', icon='fa-sliders', tampil='Y'
WHERE url='system-configuration';

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id, g.level,
       CASE WHEN g.level IN ('admin','system_administrator') THEN 'Y' ELSE COALESCE(r.read_act,'N') END,
       CASE WHEN g.level IN ('admin','system_administrator') THEN 'Y' ELSE COALESCE(r.insert_act,'N') END,
       CASE WHEN g.level IN ('admin','system_administrator') THEN 'Y' ELSE COALESCE(r.update_act,'N') END,
       CASE WHEN g.level IN ('admin','system_administrator') THEN 'Y' ELSE COALESCE(r.delete_act,'N') END,
       CASE WHEN g.level IN ('admin','system_administrator') THEN 'Y' ELSE COALESCE(r.import_act,'N') END
FROM sys_menu m
JOIN sys_group_users g
LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.level
WHERE m.url='system-configuration' AND r.id IS NULL;

UPDATE sys_menu_role r
JOIN sys_menu m ON m.id=r.id_menu AND m.url='system-configuration'
SET r.read_act='Y', r.insert_act='Y', r.update_act='Y', r.delete_act='Y', r.import_act='Y'
WHERE r.group_level IN ('admin','system_administrator');
