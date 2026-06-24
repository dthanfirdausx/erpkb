-- Goods Receipt for PO: SAP-oriented warehouse and customs fields.

ALTER TABLE pemasukan ADD COLUMN IF NOT EXISTS document_date DATE DEFAULT NULL AFTER tgl_bpb;
ALTER TABLE pemasukan ADD COLUMN IF NOT EXISTS posting_date DATE DEFAULT NULL AFTER document_date;
ALTER TABLE pemasukan ADD COLUMN IF NOT EXISTS plant_id INT DEFAULT NULL AFTER nopo;
ALTER TABLE pemasukan ADD COLUMN IF NOT EXISTS storage_location_id INT DEFAULT NULL AFTER plant_id;
ALTER TABLE pemasukan ADD COLUMN IF NOT EXISTS stock_type ENUM('UNRESTRICTED','QUALITY','BLOCKED') NOT NULL DEFAULT 'UNRESTRICTED' AFTER storage_location_id;
ALTER TABLE pemasukan ADD COLUMN IF NOT EXISTS kantor_pabean VARCHAR(20) DEFAULT NULL AFTER tgl_dokpab;
ALTER TABLE pemasukan ADD COLUMN IF NOT EXISTS negara_asal VARCHAR(5) DEFAULT NULL AFTER kantor_pabean;
ALTER TABLE pemasukan ADD COLUMN IF NOT EXISTS customs_status ENUM('DRAFT','SUBMITTED','REGISTERED','RELEASED') DEFAULT 'REGISTERED' AFTER negara_asal;
ALTER TABLE pemasukan MODIFY COLUMN no_dokpab VARCHAR(50) DEFAULT '';

ALTER TABLE pemasukan_detail ADD COLUMN IF NOT EXISTS id_po_detail INT DEFAULT NULL AFTER id;
ALTER TABLE pemasukan_detail ADD COLUMN IF NOT EXISTS storage_bin_id INT DEFAULT NULL AFTER lokasi;
ALTER TABLE pemasukan_detail ADD COLUMN IF NOT EXISTS customs_item_no INT DEFAULT NULL AFTER no_urut;
ALTER TABLE pemasukan_detail ADD COLUMN IF NOT EXISTS hs_code VARCHAR(20) DEFAULT NULL AFTER customs_item_no;
ALTER TABLE pemasukan_detail ADD COLUMN IF NOT EXISTS customs_qty DECIMAL(15,5) DEFAULT NULL AFTER hs_code;
ALTER TABLE pemasukan_detail ADD COLUMN IF NOT EXISTS customs_uom VARCHAR(10) DEFAULT NULL AFTER customs_qty;
ALTER TABLE pemasukan_detail ADD COLUMN IF NOT EXISTS customs_value DECIMAL(20,5) DEFAULT NULL AFTER customs_uom;
ALTER TABLE pemasukan_detail ADD COLUMN IF NOT EXISTS net_weight DECIMAL(15,5) DEFAULT NULL AFTER customs_value;
ALTER TABLE pemasukan_detail ADD COLUMN IF NOT EXISTS gross_weight DECIMAL(15,5) DEFAULT NULL AFTER net_weight;
ALTER TABLE pemasukan_detail ADD COLUMN IF NOT EXISTS package_type VARCHAR(10) DEFAULT NULL AFTER gross_weight;
ALTER TABLE pemasukan_detail ADD COLUMN IF NOT EXISTS package_qty DECIMAL(15,3) DEFAULT NULL AFTER package_type;
ALTER TABLE pemasukan_detail ADD COLUMN IF NOT EXISTS origin_country VARCHAR(5) DEFAULT NULL AFTER package_qty;
ALTER TABLE pemasukan_detail ADD INDEX IF NOT EXISTS idx_pemasukan_detail_po_item (id_po_detail);
ALTER TABLE pemasukan_detail ADD INDEX IF NOT EXISTS idx_pemasukan_detail_storage_bin (storage_bin_id);

-- Repair legacy PO item links where the PO number still matches an existing header.
UPDATE purchase_order_detail d
JOIN purchase_order p ON p.purchase_order_no=d.po_no
SET d.id_po=p.id
WHERE d.id_po IS NULL;
