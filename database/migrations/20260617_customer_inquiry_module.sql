-- =====================================================
-- CUSTOMER INQUIRY MODULE
-- SAP SD Pre-Sales Inquiry
-- =====================================================

CREATE TABLE IF NOT EXISTS sales_inquiry (
  id INT(11) NOT NULL AUTO_INCREMENT,
  inquiry_no VARCHAR(30) NOT NULL,
  inquiry_date DATE NOT NULL,
  valid_until DATE NULL,
  requested_delivery_date DATE NULL,
  customer_id INT(11) NULL,
  customer_code VARCHAR(30) NULL,
  customer_name VARCHAR(150) NULL,
  contact_person VARCHAR(100) NULL,
  phone VARCHAR(50) NULL,
  email VARCHAR(100) NULL,
  sales_person VARCHAR(100) NULL,
  sales_org_id INT(11) NULL,
  distribution_channel_id INT(11) NULL,
  priority ENUM('LOW','NORMAL','HIGH','URGENT') NOT NULL DEFAULT 'NORMAL',
  status ENUM('OPEN','QUOTED','WON','LOST','CANCELLED') NOT NULL DEFAULT 'OPEN',
  source VARCHAR(50) NULL,
  currency VARCHAR(10) NOT NULL DEFAULT 'IDR',
  incoterm VARCHAR(30) NULL,
  payment_term VARCHAR(100) NULL,
  subject VARCHAR(200) NULL,
  remarks TEXT NULL,
  lost_reason VARCHAR(255) NULL,
  quotation_id INT(11) NULL,
  quotation_no VARCHAR(50) NULL,
  created_by VARCHAR(50) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_by VARCHAR(50) NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_sales_inquiry_no (inquiry_no),
  KEY idx_sales_inquiry_date (inquiry_date),
  KEY idx_sales_inquiry_customer (customer_id, customer_code),
  KEY idx_sales_inquiry_status (status),
  KEY idx_sales_inquiry_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS sales_inquiry_detail (
  id INT(11) NOT NULL AUTO_INCREMENT,
  inquiry_id INT(11) NOT NULL,
  line_no INT(11) NOT NULL DEFAULT 10,
  material_code VARCHAR(100) NULL,
  material_name VARCHAR(150) NULL,
  description VARCHAR(255) NULL,
  qty DECIMAL(18,5) NOT NULL DEFAULT 0,
  uom VARCHAR(30) NULL,
  target_price DECIMAL(18,2) NOT NULL DEFAULT 0,
  estimated_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
  requested_delivery_date DATE NULL,
  remarks VARCHAR(255) NULL,
  PRIMARY KEY (id),
  KEY idx_sales_inquiry_detail_header (inquiry_id),
  KEY idx_sales_inquiry_detail_material (material_code),
  CONSTRAINT fk_sales_inquiry_detail_header
    FOREIGN KEY (inquiry_id) REFERENCES sales_inquiry(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE sys_menu
SET nav_act='customer_inquiry',
    main_table='sales_inquiry',
    page_name='Customer Inquiry',
    tampil='Y'
WHERE url='customer-inquiry';

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id, g.group_level, 'Y', 'Y', 'Y', 'N', 'N'
FROM sys_menu m
JOIN (
  SELECT DISTINCT group_level
  FROM sys_menu_role
  WHERE COALESCE(group_level,'') <> ''
) g
WHERE m.url='customer-inquiry'
  AND NOT EXISTS (
    SELECT 1
    FROM sys_menu_role r
    WHERE r.id_menu=m.id AND r.group_level=g.group_level
  );
