-- =====================================================
-- PICKING MODULE
-- SAP-style outbound picking based on Outbound Delivery
-- =====================================================

CREATE TABLE IF NOT EXISTS erp_picking (
  id INT(11) NOT NULL AUTO_INCREMENT,
  picking_no VARCHAR(30) NOT NULL,
  picking_date DATE NOT NULL,
  delivery_id INT(11) NOT NULL,
  delivery_no VARCHAR(30) NULL,
  id_sales_order INT(11) NULL,
  no_sales_order VARCHAR(30) NULL,
  customer_code VARCHAR(30) NULL,
  customer_name VARCHAR(150) NULL,
  warehouse VARCHAR(100) NULL,
  picker VARCHAR(100) NULL,
  status ENUM('CREATED','IN_PROCESS','PICKED','CANCELLED') NOT NULL DEFAULT 'CREATED',
  remarks TEXT NULL,
  created_by VARCHAR(50) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_by VARCHAR(50) NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_erp_picking_no (picking_no),
  KEY idx_erp_picking_date (picking_date),
  KEY idx_erp_picking_delivery (delivery_id),
  KEY idx_erp_picking_status (status),
  CONSTRAINT fk_erp_picking_delivery FOREIGN KEY (delivery_id)
    REFERENCES erp_outbound_delivery(id)
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS erp_picking_detail (
  id INT(11) NOT NULL AUTO_INCREMENT,
  picking_id INT(11) NOT NULL,
  delivery_detail_id INT(11) NOT NULL,
  line_no INT(11) NOT NULL DEFAULT 10,
  material_code VARCHAR(100) NULL,
  material_name VARCHAR(150) NULL,
  store VARCHAR(50) NULL,
  delivery_qty DECIMAL(18,5) NOT NULL DEFAULT 0,
  picked_qty DECIMAL(18,5) NOT NULL DEFAULT 0,
  uom VARCHAR(30) NULL,
  batch_no VARCHAR(100) NULL,
  source_bin VARCHAR(100) NULL,
  remarks VARCHAR(255) NULL,
  PRIMARY KEY (id),
  KEY idx_erp_picking_detail_header (picking_id),
  KEY idx_erp_picking_detail_delivery (delivery_detail_id),
  KEY idx_erp_picking_detail_material (material_code),
  CONSTRAINT fk_erp_picking_detail_header FOREIGN KEY (picking_id)
    REFERENCES erp_picking(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE sys_menu
SET nav_act='picking',
    main_table='erp_picking',
    page_name='Picking',
    tampil='Y'
WHERE url='picking-pengeluaran';

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id, g.group_level, 'Y', 'Y', 'Y', 'N', 'N'
FROM sys_menu m
JOIN (
  SELECT DISTINCT group_level FROM sys_menu_role WHERE COALESCE(group_level,'') <> ''
) g
WHERE m.url='picking-pengeluaran'
  AND NOT EXISTS (
    SELECT 1 FROM sys_menu_role r WHERE r.id_menu=m.id AND r.group_level=g.group_level
  );
