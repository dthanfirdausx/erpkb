ALTER TABLE surat_jalan
  ADD COLUMN IF NOT EXISTS document_date DATE NULL AFTER tgl_surat_jalan,
  ADD COLUMN IF NOT EXISTS posting_date DATE NULL AFTER document_date,
  ADD COLUMN IF NOT EXISTS gi_id INT(11) NULL AFTER picking_no,
  ADD COLUMN IF NOT EXISTS gi_no VARCHAR(30) NULL AFTER gi_id,
  ADD COLUMN IF NOT EXISTS movement_type VARCHAR(5) NULL DEFAULT '601' AFTER gi_no,
  ADD COLUMN IF NOT EXISTS shipping_point VARCHAR(50) NULL AFTER no_sales_order,
  ADD COLUMN IF NOT EXISTS route VARCHAR(100) NULL AFTER shipping_point,
  ADD COLUMN IF NOT EXISTS carrier VARCHAR(100) NULL AFTER route,
  ADD COLUMN IF NOT EXISTS sold_to_party VARCHAR(50) NULL AFTER kode_penerima,
  ADD COLUMN IF NOT EXISTS ship_to_party VARCHAR(50) NULL AFTER sold_to_party,
  ADD COLUMN IF NOT EXISTS bill_to_party VARCHAR(50) NULL AFTER ship_to_party,
  ADD COLUMN IF NOT EXISTS payer VARCHAR(50) NULL AFTER bill_to_party,
  ADD COLUMN IF NOT EXISTS delivery_status VARCHAR(30) NULL AFTER status,
  ADD COLUMN IF NOT EXISTS print_count INT(11) NOT NULL DEFAULT 0 AFTER tanda_tangan_penerima,
  ADD COLUMN IF NOT EXISTS last_printed_at DATETIME NULL AFTER print_count,
  ADD COLUMN IF NOT EXISTS last_printed_by VARCHAR(50) NULL AFTER last_printed_at,
  ADD COLUMN IF NOT EXISTS cancelled_by VARCHAR(50) NULL AFTER updated_date,
  ADD COLUMN IF NOT EXISTS cancelled_at DATETIME NULL AFTER cancelled_by,
  ADD COLUMN IF NOT EXISTS cancel_reason VARCHAR(255) NULL AFTER cancelled_at;

ALTER TABLE surat_jalan
  ADD INDEX IF NOT EXISTS idx_sj_gi_id (gi_id),
  ADD INDEX IF NOT EXISTS idx_sj_gi_no (gi_no),
  ADD INDEX IF NOT EXISTS idx_sj_document_date (document_date),
  ADD INDEX IF NOT EXISTS idx_sj_posting_date (posting_date),
  ADD INDEX IF NOT EXISTS idx_sj_ship_to_party (ship_to_party);

ALTER TABLE surat_jalan_detail
  ADD COLUMN IF NOT EXISTS line_no INT(11) NULL AFTER surat_jalan_id,
  ADD COLUMN IF NOT EXISTS packing_list_detail_id INT(11) NULL AFTER line_no,
  ADD COLUMN IF NOT EXISTS delivery_detail_id INT(11) NULL AFTER packing_list_detail_id,
  ADD COLUMN IF NOT EXISTS gi_detail_id INT(11) NULL AFTER delivery_detail_id,
  ADD COLUMN IF NOT EXISTS material_code VARCHAR(100) NULL AFTER id_sales_order_detail,
  ADD COLUMN IF NOT EXISTS material_name VARCHAR(150) NULL AFTER material_code,
  ADD COLUMN IF NOT EXISTS batch_no VARCHAR(100) NULL AFTER material_name,
  ADD COLUMN IF NOT EXISTS lot_no VARCHAR(100) NULL AFTER batch_no,
  ADD COLUMN IF NOT EXISTS plant_id INT(11) NULL AFTER satuan,
  ADD COLUMN IF NOT EXISTS storage_location_id INT(11) NULL AFTER plant_id,
  ADD COLUMN IF NOT EXISTS storage_bin_id INT(11) NULL AFTER storage_location_id,
  ADD COLUMN IF NOT EXISTS stock_type VARCHAR(20) NULL DEFAULT 'UNRESTRICTED' AFTER storage_bin_id,
  ADD COLUMN IF NOT EXISTS bc_document_type VARCHAR(20) NULL AFTER stock_type,
  ADD COLUMN IF NOT EXISTS bc_document_no VARCHAR(50) NULL AFTER bc_document_type,
  ADD COLUMN IF NOT EXISTS bc_document_date DATE NULL AFTER bc_document_no,
  ADD COLUMN IF NOT EXISTS hs_code VARCHAR(50) NULL AFTER bc_document_date,
  ADD COLUMN IF NOT EXISTS net_weight DECIMAL(18,5) NOT NULL DEFAULT 0 AFTER hs_code,
  ADD COLUMN IF NOT EXISTS gross_weight DECIMAL(18,5) NOT NULL DEFAULT 0 AFTER net_weight;

ALTER TABLE surat_jalan_detail
  ADD INDEX IF NOT EXISTS idx_sjd_line_no (line_no),
  ADD INDEX IF NOT EXISTS idx_sjd_packing_list_detail_id (packing_list_detail_id),
  ADD INDEX IF NOT EXISTS idx_sjd_delivery_detail_id (delivery_detail_id),
  ADD INDEX IF NOT EXISTS idx_sjd_gi_detail_id (gi_detail_id),
  ADD INDEX IF NOT EXISTS idx_sjd_material_code (material_code),
  ADD INDEX IF NOT EXISTS idx_sjd_batch_lot (batch_no, lot_no),
  ADD INDEX IF NOT EXISTS idx_sjd_storage (plant_id, storage_location_id, storage_bin_id),
  ADD INDEX IF NOT EXISTS idx_sjd_bc_doc (bc_document_type, bc_document_no);

UPDATE surat_jalan
SET document_date = COALESCE(document_date, tgl_surat_jalan),
    posting_date = COALESCE(posting_date, tgl_surat_jalan),
    sold_to_party = COALESCE(sold_to_party, kode_penerima),
    ship_to_party = COALESCE(ship_to_party, kode_penerima),
    bill_to_party = COALESCE(bill_to_party, kode_penerima),
    payer = COALESCE(payer, kode_penerima),
    delivery_status = COALESCE(delivery_status, UPPER(status));

UPDATE surat_jalan_detail
SET line_no = COALESCE(line_no, row_no * 10),
    material_code = COALESCE(material_code, kode_barang),
    material_name = COALESCE(material_name, nama_barang),
    stock_type = COALESCE(stock_type, 'UNRESTRICTED');
