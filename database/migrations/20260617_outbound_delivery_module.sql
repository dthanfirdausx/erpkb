-- =====================================================
-- OUTBOUND DELIVERY MODULE
-- SAP SD Delivery document before picking/packing/GI
-- =====================================================

CREATE TABLE IF NOT EXISTS erp_outbound_delivery (
  id INT(11) NOT NULL AUTO_INCREMENT,
  delivery_no VARCHAR(30) NOT NULL,
  delivery_date DATE NOT NULL,
  planned_gi_date DATE NULL,
  id_sales_order INT(11) NOT NULL,
  no_sales_order VARCHAR(30) NULL,
  customer_code VARCHAR(30) NULL,
  customer_name VARCHAR(150) NULL,
  shipping_point VARCHAR(50) NULL,
  route VARCHAR(100) NULL,
  carrier VARCHAR(100) NULL,
  vehicle_no VARCHAR(50) NULL,
  driver_name VARCHAR(100) NULL,
  ship_to_address TEXT NULL,
  status ENUM('CREATED','PICKING','PICKED','PACKED','PGI','COMPLETED','CANCELLED') NOT NULL DEFAULT 'CREATED',
  picking_status ENUM('NOT_STARTED','PARTIAL','COMPLETE') NOT NULL DEFAULT 'NOT_STARTED',
  packing_status ENUM('NOT_STARTED','PARTIAL','COMPLETE') NOT NULL DEFAULT 'NOT_STARTED',
  gi_status ENUM('NOT_POSTED','PARTIAL','POSTED') NOT NULL DEFAULT 'NOT_POSTED',
  reference_packing_list VARCHAR(50) NULL,
  reference_surat_jalan VARCHAR(50) NULL,
  reference_gi VARCHAR(50) NULL,
  remarks TEXT NULL,
  created_by VARCHAR(50) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_by VARCHAR(50) NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_erp_outbound_delivery_no (delivery_no),
  KEY idx_eod_date (delivery_date),
  KEY idx_eod_so (id_sales_order),
  KEY idx_eod_status (status),
  CONSTRAINT fk_eod_sales_order FOREIGN KEY (id_sales_order)
    REFERENCES sales_order(id_sales_order)
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS erp_outbound_delivery_detail (
  id INT(11) NOT NULL AUTO_INCREMENT,
  delivery_id INT(11) NOT NULL,
  sales_order_detail_id BIGINT(20) NULL,
  line_no INT(11) NOT NULL DEFAULT 10,
  material_code VARCHAR(100) NULL,
  material_name VARCHAR(150) NULL,
  store VARCHAR(50) NULL,
  order_qty DECIMAL(18,5) NOT NULL DEFAULT 0,
  delivery_qty DECIMAL(18,5) NOT NULL DEFAULT 0,
  picked_qty DECIMAL(18,5) NOT NULL DEFAULT 0,
  packed_qty DECIMAL(18,5) NOT NULL DEFAULT 0,
  gi_qty DECIMAL(18,5) NOT NULL DEFAULT 0,
  uom VARCHAR(30) NULL,
  price DECIMAL(18,2) NOT NULL DEFAULT 0,
  amount DECIMAL(18,2) NOT NULL DEFAULT 0,
  batch_no VARCHAR(100) NULL,
  remarks VARCHAR(255) NULL,
  PRIMARY KEY (id),
  KEY idx_eodd_header (delivery_id),
  KEY idx_eodd_material (material_code),
  CONSTRAINT fk_eodd_header FOREIGN KEY (delivery_id)
    REFERENCES erp_outbound_delivery(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE sys_menu
SET nav_act='outbound_delivery',
    main_table='erp_outbound_delivery',
    page_name='Outbound Delivery',
    tampil='Y'
WHERE url='outbound-delivery';

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id, g.group_level, 'Y', 'Y', 'Y', 'N', 'N'
FROM sys_menu m
JOIN (
  SELECT DISTINCT group_level
  FROM sys_menu_role
  WHERE COALESCE(group_level,'') <> ''
) g
WHERE m.url='outbound-delivery'
  AND NOT EXISTS (
    SELECT 1 FROM sys_menu_role r
    WHERE r.id_menu=m.id AND r.group_level=g.group_level
  );
