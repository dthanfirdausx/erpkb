-- =====================================================
-- PACKING LIST LATEST FLOW
-- Link legacy packing_list to Outbound Delivery / Picking
-- =====================================================

ALTER TABLE packing_list
  ADD COLUMN IF NOT EXISTS delivery_id INT(11) NULL AFTER id,
  ADD COLUMN IF NOT EXISTS delivery_no VARCHAR(30) NULL AFTER delivery_id,
  ADD COLUMN IF NOT EXISTS picking_id INT(11) NULL AFTER delivery_no,
  ADD COLUMN IF NOT EXISTS picking_no VARCHAR(30) NULL AFTER picking_id,
  ADD COLUMN IF NOT EXISTS status ENUM('CREATED','PACKED','CANCELLED') NOT NULL DEFAULT 'PACKED' AFTER vehicle_no,
  ADD COLUMN IF NOT EXISTS packed_by VARCHAR(50) NULL AFTER status,
  ADD COLUMN IF NOT EXISTS packed_at DATETIME NULL AFTER packed_by,
  ADD COLUMN IF NOT EXISTS remarks TEXT NULL AFTER packed_at,
  ADD KEY IF NOT EXISTS idx_packing_list_delivery (delivery_id),
  ADD KEY IF NOT EXISTS idx_packing_list_status (status);

ALTER TABLE packing_list_detail
  ADD COLUMN IF NOT EXISTS packing_list_id INT(11) NULL AFTER id,
  ADD COLUMN IF NOT EXISTS delivery_detail_id INT(11) NULL AFTER packing_list_id,
  ADD COLUMN IF NOT EXISTS line_no INT(11) NULL AFTER delivery_detail_id,
  ADD COLUMN IF NOT EXISTS material_name VARCHAR(150) NULL AFTER kode,
  ADD COLUMN IF NOT EXISTS delivery_qty DECIMAL(18,5) NOT NULL DEFAULT 0 AFTER material_name,
  ADD COLUMN IF NOT EXISTS picked_qty DECIMAL(18,5) NOT NULL DEFAULT 0 AFTER delivery_qty,
  ADD COLUMN IF NOT EXISTS packed_qty DECIMAL(18,5) NOT NULL DEFAULT 0 AFTER picked_qty,
  ADD KEY IF NOT EXISTS idx_packing_list_detail_header (packing_list_id),
  ADD KEY IF NOT EXISTS idx_packing_list_detail_delivery (delivery_detail_id);

UPDATE sys_menu
SET nav_act='packing_list',
    main_table='packing_list',
    page_name='Packing List',
    tampil='Y'
WHERE url='packing-list';
