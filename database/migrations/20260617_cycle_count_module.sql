CREATE TABLE IF NOT EXISTS cycle_count_documents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  doc_no VARCHAR(30) NOT NULL UNIQUE,
  count_date DATE NOT NULL,
  count_type VARCHAR(30) NOT NULL DEFAULT 'CYCLE_COUNT',
  status ENUM('OPEN','COUNTED','POSTED','CANCELLED') NOT NULL DEFAULT 'OPEN',
  plant_id INT NULL,
  storage_location_id INT NULL,
  storage_bin_id INT NULL,
  stock_type VARCHAR(20) NULL,
  created_by VARCHAR(50) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  remarks VARCHAR(255) NULL,
  INDEX idx_cc_doc_date (count_date),
  INDEX idx_cc_doc_status (status),
  INDEX idx_cc_doc_location (plant_id,storage_location_id,storage_bin_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS cycle_count_document_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  document_id INT NOT NULL,
  line_no INT NOT NULL,
  material_code VARCHAR(100) NOT NULL,
  material_name VARCHAR(150) NULL,
  plant_id INT NULL,
  storage_location_id INT NULL,
  storage_bin_id INT NULL,
  stock_type VARCHAR(20) NOT NULL DEFAULT 'UNRESTRICTED',
  cycle_class CHAR(1) NULL,
  system_qty DECIMAL(18,5) NOT NULL DEFAULT 0,
  counted_qty DECIMAL(18,5) NULL,
  difference_qty DECIMAL(18,5) NULL,
  uom VARCHAR(20) NULL,
  layer_count INT NOT NULL DEFAULT 0,
  customs_doc_count INT NOT NULL DEFAULT 0,
  status ENUM('OPEN','COUNTED','POSTED','CANCELLED') NOT NULL DEFAULT 'OPEN',
  counted_by VARCHAR(50) NULL,
  counted_at DATETIME NULL,
  remarks VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_cc_item (document_id,line_no),
  INDEX idx_cc_item_material (material_code),
  INDEX idx_cc_item_location (plant_id,storage_location_id,storage_bin_id,stock_type),
  CONSTRAINT fk_cc_item_doc FOREIGN KEY (document_id) REFERENCES cycle_count_documents(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE sys_menu
SET nav_act='cycle_count',
    page_name='Cycle Count',
    url='cycle-count',
    main_table='stock_layer',
    icon='fa-check-square-o',
    dt_table='Y',
    tampil='Y',
    type_menu='page'
WHERE url='cycle-count' OR page_name='Cycle Count';

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'cycle_count','Cycle Count','cycle-count','stock_layer','fa-check-square-o',1,573,'Physical Inventory','Y','Y','page'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='cycle-count');

INSERT INTO sys_menu_role (id_menu,group_level,read_act,insert_act,update_act,delete_act,import_act)
SELECT m.id,g.group_level,'Y','Y','N','N','N'
FROM sys_menu m
JOIN (SELECT DISTINCT group_level FROM sys_menu_role WHERE group_level IS NOT NULL AND group_level<>'') g
LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.group_level
WHERE m.url='cycle-count' AND r.id IS NULL;
