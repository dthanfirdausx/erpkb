ALTER TABLE surat_jalan
  ADD COLUMN IF NOT EXISTS packing_list_id INT(11) NULL AFTER id_sales_order,
  ADD COLUMN IF NOT EXISTS packing_list_no VARCHAR(100) NULL AFTER packing_list_id,
  ADD COLUMN IF NOT EXISTS delivery_id INT(11) NULL AFTER packing_list_no,
  ADD COLUMN IF NOT EXISTS delivery_no VARCHAR(30) NULL AFTER delivery_id,
  ADD COLUMN IF NOT EXISTS picking_no VARCHAR(30) NULL AFTER delivery_no;

ALTER TABLE surat_jalan
  ADD INDEX IF NOT EXISTS idx_sj_packing_list_id (packing_list_id),
  ADD INDEX IF NOT EXISTS idx_sj_delivery_id (delivery_id);
