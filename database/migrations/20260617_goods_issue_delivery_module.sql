-- =====================================================
-- GOODS ISSUE FOR DELIVERY MODULE
-- SAP SD movement type 601, linked to Outbound Delivery
-- =====================================================

CREATE TABLE IF NOT EXISTS erp_goods_issue_delivery (
  id INT(11) NOT NULL AUTO_INCREMENT,
  gi_no VARCHAR(30) NOT NULL,
  delivery_id INT(11) NOT NULL,
  delivery_no VARCHAR(30) NULL,
  id_sales_order INT(11) NULL,
  no_sales_order VARCHAR(30) NULL,
  customer_code VARCHAR(30) NULL,
  customer_name VARCHAR(150) NULL,
  document_date DATE NOT NULL,
  posting_date DATE NOT NULL,
  movement_type VARCHAR(5) NOT NULL DEFAULT '601',
  shipping_point VARCHAR(50) NULL,
  vehicle_no VARCHAR(50) NULL,
  driver_name VARCHAR(100) NULL,
  reference_surat_jalan VARCHAR(50) NULL,
  outbound_bc_type VARCHAR(20) NULL,
  outbound_bc_purpose_code VARCHAR(20) NULL,
  outbound_bc_purpose VARCHAR(100) NULL,
  outbound_no_aju VARCHAR(50) NULL,
  outbound_tgl_aju DATE NULL,
  outbound_no_daftar VARCHAR(50) NULL,
  outbound_tgl_daftar DATE NULL,
  outbound_customs_office VARCHAR(100) NULL,
  outbound_destination_country VARCHAR(100) NULL,
  outbound_customs_remarks TEXT NULL,
  status ENUM('POSTED','REVERSED','CANCELLED') NOT NULL DEFAULT 'POSTED',
  total_qty DECIMAL(18,5) NOT NULL DEFAULT 0,
  total_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
  remarks TEXT NULL,
  created_by VARCHAR(50) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  reversed_by VARCHAR(50) NULL,
  reversed_at DATETIME NULL,
  reversal_reason VARCHAR(255) NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_erp_gid_no (gi_no),
  KEY idx_erp_gid_delivery (delivery_id),
  KEY idx_erp_gid_posting (posting_date),
  KEY idx_erp_gid_status (status),
  CONSTRAINT fk_erp_gid_delivery FOREIGN KEY (delivery_id)
    REFERENCES erp_outbound_delivery(id)
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE erp_goods_issue_delivery
  ADD COLUMN IF NOT EXISTS outbound_bc_type VARCHAR(20) NULL AFTER reference_surat_jalan,
  ADD COLUMN IF NOT EXISTS outbound_bc_purpose_code VARCHAR(20) NULL AFTER outbound_bc_type,
  ADD COLUMN IF NOT EXISTS outbound_bc_purpose VARCHAR(100) NULL AFTER outbound_bc_purpose_code,
  ADD COLUMN IF NOT EXISTS outbound_no_aju VARCHAR(50) NULL AFTER outbound_bc_purpose,
  ADD COLUMN IF NOT EXISTS outbound_tgl_aju DATE NULL AFTER outbound_no_aju,
  ADD COLUMN IF NOT EXISTS outbound_no_daftar VARCHAR(50) NULL AFTER outbound_tgl_aju,
  ADD COLUMN IF NOT EXISTS outbound_tgl_daftar DATE NULL AFTER outbound_no_daftar,
  ADD COLUMN IF NOT EXISTS outbound_customs_office VARCHAR(100) NULL AFTER outbound_tgl_daftar,
  ADD COLUMN IF NOT EXISTS outbound_destination_country VARCHAR(100) NULL AFTER outbound_customs_office,
  ADD COLUMN IF NOT EXISTS outbound_customs_remarks TEXT NULL AFTER outbound_destination_country;

CREATE TABLE IF NOT EXISTS erp_goods_issue_delivery_detail (
  id INT(11) NOT NULL AUTO_INCREMENT,
  gi_id INT(11) NOT NULL,
  delivery_detail_id INT(11) NOT NULL,
  line_no INT(11) NOT NULL DEFAULT 10,
  material_code VARCHAR(100) NULL,
  material_name VARCHAR(150) NULL,
  qty DECIMAL(18,5) NOT NULL DEFAULT 0,
  uom VARCHAR(30) NULL,
  price DECIMAL(18,5) NOT NULL DEFAULT 0,
  amount DECIMAL(18,2) NOT NULL DEFAULT 0,
  stock_type VARCHAR(20) NOT NULL DEFAULT 'UNRESTRICTED',
  remarks VARCHAR(255) NULL,
  PRIMARY KEY (id),
  KEY idx_erp_gidd_header (gi_id),
  KEY idx_erp_gidd_delivery_detail (delivery_detail_id),
  KEY idx_erp_gidd_material (material_code),
  CONSTRAINT fk_erp_gidd_header FOREIGN KEY (gi_id)
    REFERENCES erp_goods_issue_delivery(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS erp_goods_issue_delivery_trace (
  id INT(11) NOT NULL AUTO_INCREMENT,
  gi_id INT(11) NOT NULL,
  gi_detail_id INT(11) NOT NULL,
  stock_layer_id INT(11) NOT NULL,
  material_doc_id BIGINT(20) NULL,
  qty DECIMAL(18,5) NOT NULL DEFAULT 0,
  price DECIMAL(18,5) NOT NULL DEFAULT 0,
  amount DECIMAL(18,2) NOT NULL DEFAULT 0,
  stock_type VARCHAR(20) NULL,
  plant_id INT(11) NULL,
  storage_location_id INT(11) NULL,
  storage_bin_id INT(11) NULL,
  no_bpb VARCHAR(50) NULL,
  no_aju VARCHAR(50) NULL,
  no_dokpab VARCHAR(50) NULL,
  jenis_dokpab VARCHAR(20) NULL,
  hs_code VARCHAR(50) NULL,
  lot_no VARCHAR(100) NULL,
  source_ref_table VARCHAR(50) NULL,
  source_ref_id INT(11) NULL,
  PRIMARY KEY (id),
  KEY idx_erp_gidt_header (gi_id),
  KEY idx_erp_gidt_detail (gi_detail_id),
  KEY idx_erp_gidt_layer (stock_layer_id),
  KEY idx_erp_gidt_bc (no_aju,no_dokpab),
  CONSTRAINT fk_erp_gidt_header FOREIGN KEY (gi_id)
    REFERENCES erp_goods_issue_delivery(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_erp_gidt_detail FOREIGN KEY (gi_detail_id)
    REFERENCES erp_goods_issue_delivery_detail(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS erp_goods_issue_delivery_history (
  id INT(11) NOT NULL AUTO_INCREMENT,
  gi_id INT(11) NOT NULL,
  status_lama VARCHAR(30) NULL,
  status_baru VARCHAR(30) NULL,
  remarks VARCHAR(255) NULL,
  changed_by VARCHAR(50) NULL,
  changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_erp_gidh_header (gi_id),
  CONSTRAINT fk_erp_gidh_header FOREIGN KEY (gi_id)
    REFERENCES erp_goods_issue_delivery(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO rekening (no_rek, induk, level, nama_rek, mapping_coa, kat_coa, jenis)
SELECT '51100', '51', 3, 'Harga Pokok Penjualan - Goods Issue Delivery', NULL, 17, 5
WHERE NOT EXISTS (SELECT 1 FROM rekening WHERE no_rek='51100');

UPDATE sys_menu
SET nav_act='goods_issue_delivery',
    main_table='erp_goods_issue_delivery',
    page_name='Goods Issue for Delivery',
    tampil='Y'
WHERE url='pengeluaran-hamparan';

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id, g.group_level, 'Y', 'Y', 'Y', 'N', 'N'
FROM sys_menu m
JOIN (
  SELECT DISTINCT group_level FROM sys_menu_role WHERE COALESCE(group_level,'') <> ''
) g
WHERE m.url='pengeluaran-hamparan'
  AND NOT EXISTS (
    SELECT 1 FROM sys_menu_role r WHERE r.id_menu=m.id AND r.group_level=g.group_level
  );
