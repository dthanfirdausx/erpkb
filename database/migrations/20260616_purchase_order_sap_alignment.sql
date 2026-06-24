ALTER TABLE purchase_order
  ADD COLUMN IF NOT EXISTS po_type VARCHAR(20) NULL AFTER purchase_order_no,
  ADD COLUMN IF NOT EXISTS source_type VARCHAR(20) NULL AFTER po_type,
  ADD COLUMN IF NOT EXISTS source_ref VARCHAR(50) NULL AFTER source_type,
  ADD COLUMN IF NOT EXISTS purchasing_org VARCHAR(20) NULL AFTER customer_id,
  ADD COLUMN IF NOT EXISTS purchasing_group VARCHAR(20) NULL AFTER purchasing_org,
  ADD COLUMN IF NOT EXISTS plant VARCHAR(10) NULL AFTER purchasing_group,
  ADD COLUMN IF NOT EXISTS storage_location VARCHAR(10) NULL AFTER plant;

ALTER TABLE purchase_order_detail
  ADD COLUMN IF NOT EXISTS id_pr BIGINT NULL AFTER id_po,
  ADD COLUMN IF NOT EXISTS id_pr_detail BIGINT NULL AFTER id_pr,
  ADD COLUMN IF NOT EXISTS rfq_id BIGINT NULL AFTER id_pr_detail,
  ADD COLUMN IF NOT EXISTS rfq_item_id BIGINT NULL AFTER rfq_id,
  ADD COLUMN IF NOT EXISTS rfq_quotation_id BIGINT NULL AFTER rfq_item_id;

UPDATE purchase_order_detail d
JOIN purchase_order h ON h.purchase_order_no=d.po_no
SET d.id_po=h.id
WHERE d.id_po IS NULL OR d.id_po=0;

UPDATE sys_menu
SET main_table='purchase_order',
    nav_act='purchase_order',
    page_name='Purchase Order',
    icon='fa-cart-plus',
    tampil='Y'
WHERE url='purchase-order';
