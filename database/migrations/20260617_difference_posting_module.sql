CREATE TABLE IF NOT EXISTS physical_inventory_postings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  posting_no VARCHAR(30) NOT NULL UNIQUE,
  doc_type ENUM('CYCLE_COUNT','STOCK_OPNAME') NOT NULL,
  document_id INT NOT NULL,
  item_id INT NOT NULL,
  material_doc_id BIGINT NULL,
  movement_type VARCHAR(5) NOT NULL,
  difference_qty DECIMAL(18,5) NOT NULL,
  posted_by VARCHAR(50) NULL,
  posted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  remarks VARCHAR(255) NULL,
  UNIQUE KEY uq_pi_posting_item (doc_type,item_id),
  INDEX idx_pi_posting_doc (doc_type,document_id),
  INDEX idx_pi_posting_matdoc (material_doc_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE sys_menu
SET nav_act='difference_posting',
    page_name='Difference Posting',
    url='difference-posting',
    main_table='detail_transaksi',
    icon='fa-balance-scale',
    dt_table='Y',
    tampil='Y',
    type_menu='page'
WHERE url='difference-posting' OR page_name='Difference Posting';

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'difference_posting','Difference Posting','difference-posting','detail_transaksi','fa-balance-scale',4,573,'Physical Inventory','Y','Y','page'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='difference-posting');

INSERT INTO sys_menu_role (id_menu,group_level,read_act,insert_act,update_act,delete_act,import_act)
SELECT m.id,g.group_level,'Y','Y','Y','N','N'
FROM sys_menu m
JOIN (SELECT DISTINCT group_level FROM sys_menu_role WHERE group_level IS NOT NULL AND group_level<>'') g
LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.group_level
WHERE m.url='difference-posting' AND r.id IS NULL;
