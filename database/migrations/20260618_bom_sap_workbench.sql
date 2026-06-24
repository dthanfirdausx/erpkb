ALTER TABLE bom
  ADD COLUMN IF NOT EXISTS bom_no varchar(30) NULL AFTER id,
  ADD COLUMN IF NOT EXISTS bom_usage varchar(30) NULL DEFAULT 'PRODUCTION' AFTER satuan,
  ADD COLUMN IF NOT EXISTS plant_id int(11) NULL AFTER bom_usage,
  ADD COLUMN IF NOT EXISTS plant_code varchar(20) NULL AFTER plant_id,
  ADD COLUMN IF NOT EXISTS alternative_bom varchar(10) NULL DEFAULT '01' AFTER plant_code,
  ADD COLUMN IF NOT EXISTS valid_from date NULL AFTER alternative_bom,
  ADD COLUMN IF NOT EXISTS valid_to date NULL AFTER valid_from,
  ADD COLUMN IF NOT EXISTS base_qty decimal(18,5) NULL AFTER valid_to,
  ADD COLUMN IF NOT EXISTS base_uom varchar(30) NULL AFTER base_qty,
  ADD COLUMN IF NOT EXISTS bom_status varchar(20) NULL DEFAULT 'DRAFT' AFTER base_uom,
  ADD COLUMN IF NOT EXISTS revision varchar(30) NULL AFTER bom_status,
  ADD COLUMN IF NOT EXISTS change_number varchar(50) NULL AFTER revision,
  ADD COLUMN IF NOT EXISTS production_version_id int(11) NULL AFTER change_number,
  ADD COLUMN IF NOT EXISTS lot_size_from decimal(18,5) NULL AFTER production_version_id,
  ADD COLUMN IF NOT EXISTS lot_size_to decimal(18,5) NULL AFTER lot_size_from,
  ADD COLUMN IF NOT EXISTS remarks text NULL AFTER lot_size_to,
  ADD COLUMN IF NOT EXISTS created_by varchar(100) NULL AFTER user_id,
  ADD COLUMN IF NOT EXISTS created_at datetime NULL AFTER created_by,
  ADD COLUMN IF NOT EXISTS updated_by varchar(100) NULL AFTER created_at,
  ADD COLUMN IF NOT EXISTS updated_at datetime NULL AFTER updated_by,
  ADD COLUMN IF NOT EXISTS released_by varchar(100) NULL AFTER updated_at,
  ADD COLUMN IF NOT EXISTS released_at datetime NULL AFTER released_by,
  ADD COLUMN IF NOT EXISTS cancel_reason varchar(255) NULL AFTER released_at;

ALTER TABLE bom_detail
  ADD COLUMN IF NOT EXISTS line_no int(11) NULL AFTER id_bom,
  ADD COLUMN IF NOT EXISTS item_category varchar(30) NULL DEFAULT 'STOCK' AFTER nm_barang,
  ADD COLUMN IF NOT EXISTS component_qty decimal(18,5) NULL AFTER item_category,
  ADD COLUMN IF NOT EXISTS component_uom varchar(30) NULL AFTER component_qty,
  ADD COLUMN IF NOT EXISTS scrap_percent decimal(9,4) NULL DEFAULT 0 AFTER component_uom,
  ADD COLUMN IF NOT EXISTS fixed_qty enum('Y','N') NULL DEFAULT 'N' AFTER scrap_percent,
  ADD COLUMN IF NOT EXISTS phantom_item enum('Y','N') NULL DEFAULT 'N' AFTER fixed_qty,
  ADD COLUMN IF NOT EXISTS backflush enum('Y','N') NULL DEFAULT 'N' AFTER phantom_item,
  ADD COLUMN IF NOT EXISTS operation_no varchar(20) NULL AFTER backflush,
  ADD COLUMN IF NOT EXISTS storage_location_id int(11) NULL AFTER operation_no,
  ADD COLUMN IF NOT EXISTS storage_location varchar(30) NULL AFTER storage_location_id,
  ADD COLUMN IF NOT EXISTS alternative_group varchar(30) NULL AFTER storage_location,
  ADD COLUMN IF NOT EXISTS priority int(11) NULL AFTER alternative_group,
  ADD COLUMN IF NOT EXISTS valid_from date NULL AFTER priority,
  ADD COLUMN IF NOT EXISTS valid_to date NULL AFTER valid_from,
  ADD COLUMN IF NOT EXISTS issue_status varchar(20) NULL DEFAULT 'ACTIVE' AFTER valid_to,
  ADD COLUMN IF NOT EXISTS remarks varchar(255) NULL AFTER issue_status;

ALTER TABLE bom DROP INDEX IF EXISTS kodebj;
ALTER TABLE bom ADD INDEX IF NOT EXISTS idx_bom_material_plant_status (kodebj, plant_code, bom_usage, bom_status, valid_from, valid_to);
ALTER TABLE bom ADD UNIQUE INDEX IF NOT EXISTS uk_bom_no (bom_no);
ALTER TABLE bom_detail ADD INDEX IF NOT EXISTS idx_bom_detail_header_line (id_bom, line_no);

UPDATE bom
SET bom_no = CONCAT('BOM', LPAD(id, 6, '0'))
WHERE bom_no IS NULL OR bom_no = '';

UPDATE bom
SET bom_usage = COALESCE(NULLIF(bom_usage,''), 'PRODUCTION'),
    alternative_bom = COALESCE(NULLIF(alternative_bom,''), '01'),
    valid_from = COALESCE(valid_from, DATE(COALESCE(tgl_input, NOW()))),
    base_qty = COALESCE(NULLIF(base_qty,0), NULLIF(jumlah,0), 1),
    base_uom = COALESCE(NULLIF(base_uom,''), satuan),
    bom_status = CASE WHEN COALESCE(status,1)=1 AND (updated_by IS NULL OR updated_by='') THEN 'RELEASED' WHEN COALESCE(status,1)=1 THEN COALESCE(NULLIF(bom_status,''), 'RELEASED') ELSE 'INACTIVE' END,
    created_by = COALESCE(created_by, user_id),
    created_at = COALESCE(created_at, tgl_input, NOW()),
    updated_at = COALESCE(updated_at, NOW())
WHERE id IS NOT NULL;

UPDATE bom_detail
SET line_no = COALESCE(line_no, id * 10),
    item_category = COALESCE(NULLIF(item_category,''), 'STOCK'),
    component_qty = COALESCE(NULLIF(component_qty,0), jumlah),
    component_uom = COALESCE(NULLIF(component_uom,''), satuan),
    issue_status = CASE WHEN COALESCE(status,'1') IN ('0','Nonaktif','INACTIVE') THEN 'INACTIVE' ELSE 'ACTIVE' END
WHERE id IS NOT NULL;
