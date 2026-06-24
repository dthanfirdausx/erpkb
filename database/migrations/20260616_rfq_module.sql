CREATE TABLE IF NOT EXISTS erp_rfq (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  rfq_no VARCHAR(30) NOT NULL UNIQUE,
  rfq_date DATE NOT NULL,
  quotation_deadline DATE NOT NULL,
  purchasing_org VARCHAR(20) NULL,
  purchasing_group VARCHAR(20) NULL,
  plant VARCHAR(10) NULL,
  storage_location VARCHAR(10) NULL,
  currency VARCHAR(10) NOT NULL DEFAULT 'IDR',
  status ENUM('DRAFT','SENT','QUOTED','AWARDED','CLOSED','CANCELLED') NOT NULL DEFAULT 'DRAFT',
  subject VARCHAR(255) NULL,
  note TEXT NULL,
  created_by VARCHAR(100) NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_by VARCHAR(100) NULL,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_rfq_date (rfq_date),
  KEY idx_rfq_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS erp_rfq_item (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  rfq_id BIGINT NOT NULL,
  id_pr BIGINT NULL,
  id_pr_detail BIGINT NULL,
  line_no INT NOT NULL,
  material_code VARCHAR(100) NOT NULL,
  material_name VARCHAR(255) NULL,
  qty DECIMAL(18,5) NOT NULL DEFAULT 0,
  uom VARCHAR(20) NOT NULL,
  required_date DATE NULL,
  plant VARCHAR(10) NULL,
  storage_location VARCHAR(10) NULL,
  target_price DECIMAL(18,5) NOT NULL DEFAULT 0,
  currency VARCHAR(10) NOT NULL DEFAULT 'IDR',
  remarks TEXT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY idx_rfq_item_rfq (rfq_id),
  KEY idx_rfq_item_pr (id_pr,id_pr_detail),
  CONSTRAINT fk_erp_rfq_item_header FOREIGN KEY (rfq_id) REFERENCES erp_rfq(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS erp_rfq_vendor (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  rfq_id BIGINT NOT NULL,
  vendor_code VARCHAR(20) NOT NULL,
  vendor_name VARCHAR(150) NULL,
  email VARCHAR(150) NULL,
  status ENUM('INVITED','RESPONDED','AWARDED','REJECTED','DECLINED') NOT NULL DEFAULT 'INVITED',
  sent_at DATETIME NULL,
  responded_at DATETIME NULL,
  note TEXT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_rfq_vendor (rfq_id,vendor_code),
  KEY idx_rfq_vendor_status (status),
  CONSTRAINT fk_erp_rfq_vendor_header FOREIGN KEY (rfq_id) REFERENCES erp_rfq(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS erp_rfq_quotation (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  rfq_id BIGINT NOT NULL,
  rfq_vendor_id BIGINT NOT NULL,
  rfq_item_id BIGINT NOT NULL,
  vendor_code VARCHAR(20) NOT NULL,
  price DECIMAL(18,5) NOT NULL DEFAULT 0,
  qty DECIMAL(18,5) NOT NULL DEFAULT 0,
  currency VARCHAR(10) NOT NULL DEFAULT 'IDR',
  discount_percent DECIMAL(9,4) NOT NULL DEFAULT 0,
  tax_percent DECIMAL(9,4) NOT NULL DEFAULT 0,
  delivery_days INT NULL,
  payment_terms VARCHAR(100) NULL,
  valid_until DATE NULL,
  rank_no INT NULL,
  is_awarded ENUM('Y','N') NOT NULL DEFAULT 'N',
  remarks TEXT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_rfq_quote (rfq_vendor_id,rfq_item_id),
  KEY idx_rfq_quote_rfq (rfq_id),
  CONSTRAINT fk_erp_rfq_quote_header FOREIGN KEY (rfq_id) REFERENCES erp_rfq(id) ON DELETE CASCADE,
  CONSTRAINT fk_erp_rfq_quote_vendor FOREIGN KEY (rfq_vendor_id) REFERENCES erp_rfq_vendor(id) ON DELETE CASCADE,
  CONSTRAINT fk_erp_rfq_quote_item FOREIGN KEY (rfq_item_id) REFERENCES erp_rfq_item(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS erp_rfq_history (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  rfq_id BIGINT NOT NULL,
  status_lama VARCHAR(50) NULL,
  status_baru VARCHAR(50) NULL,
  remarks TEXT NULL,
  changed_by VARCHAR(100) NULL,
  changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY idx_rfq_history (rfq_id,changed_at),
  CONSTRAINT fk_erp_rfq_history_header FOREIGN KEY (rfq_id) REFERENCES erp_rfq(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE sys_menu
SET nav_act='rfq',
    main_table='erp_rfq',
    page_name='Request for Quotation',
    icon='fa-envelope-o',
    tampil='Y'
WHERE url='rfq';

UPDATE sys_menu_role
SET read_act='Y', insert_act='Y', update_act='Y', delete_act='Y'
WHERE id_menu=(SELECT id FROM sys_menu WHERE url='rfq' LIMIT 1)
  AND group_level IN ('admin','purchasing','system_administrator');

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id, g.level, 'Y', 'Y', 'Y', 'Y', 'N'
FROM sys_menu m
JOIN sys_group_users g ON g.level IN ('admin','purchasing','system_administrator')
LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.level
WHERE m.url='rfq'
  AND r.id IS NULL;
