CREATE TABLE IF NOT EXISTS erp_auto_journal_mapping (
  id INT(11) NOT NULL AUTO_INCREMENT,
  transaction_code VARCHAR(50) NOT NULL,
  bc_code VARCHAR(20) NOT NULL DEFAULT '',
  item_category VARCHAR(20) NOT NULL DEFAULT '*',
  line_no INT(11) NOT NULL DEFAULT 10,
  account_no VARCHAR(50) NOT NULL,
  dc_position ENUM('1','2') NOT NULL,
  expected_category ENUM('aset','kewajiban','modal','pendapatan','beban') NOT NULL,
  status ENUM('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  remarks VARCHAR(255) DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_auto_journal_mapping (transaction_code,bc_code,item_category,line_no,dc_position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO erp_auto_journal_mapping
  (transaction_code,bc_code,item_category,line_no,account_no,dc_position,expected_category,remarks)
VALUES
  ('pembelian','','*',10,'14101','1','aset','Inventory receipt generic leaf account'),
  ('pembelian','','K02',10,'14300','1','aset','Finished goods inventory receipt'),
  ('pembelian','','K07',10,'14300','1','aset','Finished goods inventory receipt'),
  ('pembelian','','*',20,'21199','2','kewajiban','Generic AP leaf account'),
  ('pembelian','','K02',20,'21199','2','kewajiban','Generic AP leaf account'),
  ('pembelian','','K07',20,'21199','2','kewajiban','Generic AP leaf account'),
  ('penjualan','','*',10,'12199','1','aset','Generic AR leaf account'),
  ('penjualan','','*',20,'41100','2','pendapatan','Domestic sales revenue'),
  ('penjualan','BC 3.0','*',10,'12199','1','aset','Generic AR leaf account'),
  ('penjualan','BC 3.0','*',20,'41200','2','pendapatan','Export sales revenue'),
  ('issue_cost_center','','*',10,'62199','1','beban','Material usage cost center'),
  ('issue_cost_center','','*',20,'14101','2','aset','Inventory leaf account'),
  ('issue_cost_center','','K02',20,'14300','2','aset','Finished goods inventory'),
  ('issue_cost_center','','K07',20,'14300','2','aset','Finished goods inventory'),
  ('issue_asset','','*',10,'15199','1','aset','Asset acquisition clearing'),
  ('issue_asset','','*',20,'14101','2','aset','Inventory leaf account'),
  ('issue_asset','','K02',20,'14300','2','aset','Finished goods inventory'),
  ('issue_asset','','K07',20,'14300','2','aset','Finished goods inventory'),
  ('scrap_issue','','*',10,'62299','1','beban','Scrap expense'),
  ('scrap_issue','','*',20,'14101','2','aset','Inventory leaf account'),
  ('scrap_issue','','K02',20,'14300','2','aset','Finished goods inventory'),
  ('scrap_issue','','K07',20,'14300','2','aset','Finished goods inventory'),
  ('sample_issue','','*',10,'62399','1','beban','Sample expense'),
  ('sample_issue','','*',20,'14101','2','aset','Inventory leaf account'),
  ('sample_issue','','K02',20,'14300','2','aset','Finished goods inventory'),
  ('sample_issue','','K07',20,'14300','2','aset','Finished goods inventory'),
  ('other_goods_issue','','*',10,'62499','1','beban','Other goods issue expense'),
  ('other_goods_issue','','*',20,'14101','2','aset','Inventory leaf account'),
  ('other_goods_issue','','K02',20,'14300','2','aset','Finished goods inventory'),
  ('other_goods_issue','','K07',20,'14300','2','aset','Finished goods inventory'),
  ('goods_issue_delivery','','*',10,'51100','1','beban','COGS delivery'),
  ('goods_issue_delivery','','*',20,'14101','2','aset','Inventory leaf account'),
  ('goods_issue_delivery','','K02',20,'14300','2','aset','Finished goods inventory'),
  ('goods_issue_delivery','','K07',20,'14300','2','aset','Finished goods inventory'),
  ('return_to_vendor','','*',10,'21199','1','kewajiban','Generic AP leaf account'),
  ('return_to_vendor','','*',20,'14101','2','aset','Inventory leaf account'),
  ('return_to_vendor','','K02',20,'14300','2','aset','Finished goods inventory'),
  ('return_to_vendor','','K07',20,'14300','2','aset','Finished goods inventory'),
  ('issue_production','','*',10,'14302','1','aset','Work in process'),
  ('issue_production','','*',20,'14101','2','aset','Inventory leaf account'),
  ('issue_production','','K02',20,'14300','2','aset','Finished goods inventory'),
  ('issue_production','','K07',20,'14300','2','aset','Finished goods inventory'),
  ('gr_production','','*',10,'14101','1','aset','Inventory leaf account'),
  ('gr_production','','K02',10,'14300','1','aset','Finished goods inventory'),
  ('gr_production','','K07',10,'14300','1','aset','Finished goods inventory'),
  ('gr_production','','*',20,'14302','2','aset','Work in process'),
  ('gr_production','','K02',20,'14302','2','aset','Work in process'),
  ('gr_production','','K07',20,'14302','2','aset','Work in process'),
  ('manual_adjust_increase','','*',10,'14101','1','aset','Inventory leaf account'),
  ('manual_adjust_increase','','K02',10,'14300','1','aset','Finished goods inventory'),
  ('manual_adjust_increase','','K07',10,'14300','1','aset','Finished goods inventory'),
  ('manual_adjust_increase','','*',20,'71199','2','pendapatan','Stock difference income'),
  ('manual_adjust_increase','','K02',20,'71199','2','pendapatan','Stock difference income'),
  ('manual_adjust_increase','','K07',20,'71199','2','pendapatan','Stock difference income'),
  ('pi_diff_increase','','*',10,'14101','1','aset','Inventory leaf account'),
  ('pi_diff_increase','','K02',10,'14300','1','aset','Finished goods inventory'),
  ('pi_diff_increase','','K07',10,'14300','1','aset','Finished goods inventory'),
  ('pi_diff_increase','','*',20,'71199','2','pendapatan','Stock difference income'),
  ('pi_diff_increase','','K02',20,'71199','2','pendapatan','Stock difference income'),
  ('pi_diff_increase','','K07',20,'71199','2','pendapatan','Stock difference income'),
  ('manual_adjust_decrease','','*',10,'72199','1','beban','Stock difference expense'),
  ('manual_adjust_decrease','','*',20,'14101','2','aset','Inventory leaf account'),
  ('manual_adjust_decrease','','K02',20,'14300','2','aset','Finished goods inventory'),
  ('manual_adjust_decrease','','K07',20,'14300','2','aset','Finished goods inventory'),
  ('pi_diff_decrease','','*',10,'72199','1','beban','Stock difference expense'),
  ('pi_diff_decrease','','*',20,'14101','2','aset','Inventory leaf account'),
  ('pi_diff_decrease','','K02',20,'14300','2','aset','Finished goods inventory'),
  ('pi_diff_decrease','','K07',20,'14300','2','aset','Finished goods inventory')
ON DUPLICATE KEY UPDATE
  account_no=VALUES(account_no),
  expected_category=VALUES(expected_category),
  remarks=VALUES(remarks),
  status='ACTIVE',
  updated_at=NOW();

UPDATE rekening
SET kat_coa=7
WHERE no_rek='15199'
  AND EXISTS (SELECT 1 FROM coa_kategori WHERE id=7 AND kategori_akun='aset');

UPDATE jurnal_detail
SET no_rek='14101'
WHERE no_rek='140'
  AND EXISTS (SELECT 1 FROM rekening WHERE no_rek='14101');

UPDATE jurnal_detail
SET no_rek='21199'
WHERE no_rek='211'
  AND EXISTS (SELECT 1 FROM rekening WHERE no_rek='21199');

INSERT INTO saldo_awal (periode,no_rek,debet,kredit,tgl_insert,username)
SELECT YEAR(CURDATE()),r.no_rek,0,0,CURDATE(),'migration'
FROM rekening r
INNER JOIN coa_kategori k ON k.id=r.kat_coa
LEFT JOIN rekening child ON child.induk=r.no_rek
LEFT JOIN saldo_awal sa ON sa.periode=YEAR(CURDATE()) AND sa.no_rek=r.no_rek
WHERE child.no_rek IS NULL
  AND k.kategori_akun IN ('aset','kewajiban','modal')
  AND sa.id IS NULL;
