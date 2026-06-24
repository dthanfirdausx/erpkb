CREATE TABLE IF NOT EXISTS erp_vendor_evaluation (
  id BIGINT NOT NULL AUTO_INCREMENT,
  evaluation_no VARCHAR(30) NOT NULL,
  vendor_code VARCHAR(50) NOT NULL,
  vendor_name VARCHAR(150) NOT NULL,
  period_from DATE NOT NULL,
  period_to DATE NOT NULL,
  purchasing_org VARCHAR(20) NULL,
  plant VARCHAR(20) NULL,
  po_count INT NOT NULL DEFAULT 0,
  gr_count INT NOT NULL DEFAULT 0,
  total_po_value DECIMAL(20,2) NOT NULL DEFAULT 0,
  ordered_qty DECIMAL(20,5) NOT NULL DEFAULT 0,
  received_qty DECIMAL(20,5) NOT NULL DEFAULT 0,
  on_time_delivery_pct DECIMAL(8,2) NOT NULL DEFAULT 0,
  qty_accuracy_pct DECIMAL(8,2) NOT NULL DEFAULT 0,
  price_variance_pct DECIMAL(8,2) NOT NULL DEFAULT 0,
  defect_rate_pct DECIMAL(8,2) NOT NULL DEFAULT 0,
  price_score DECIMAL(8,2) NOT NULL DEFAULT 0,
  delivery_score DECIMAL(8,2) NOT NULL DEFAULT 0,
  quality_score DECIMAL(8,2) NOT NULL DEFAULT 0,
  service_score DECIMAL(8,2) NOT NULL DEFAULT 80,
  compliance_score DECIMAL(8,2) NOT NULL DEFAULT 80,
  total_score DECIMAL(8,2) NOT NULL DEFAULT 0,
  rating CHAR(1) NOT NULL DEFAULT 'D',
  status ENUM('DRAFT','FINALIZED','CANCELLED') NOT NULL DEFAULT 'DRAFT',
  evaluator VARCHAR(50) NULL,
  remarks TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  finalized_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_erp_vendor_evaluation_no (evaluation_no),
  KEY idx_erp_vendor_evaluation_vendor (vendor_code),
  KEY idx_erp_vendor_evaluation_period (period_from,period_to),
  KEY idx_erp_vendor_evaluation_status (status)
);

CREATE TABLE IF NOT EXISTS erp_vendor_evaluation_detail (
  id BIGINT NOT NULL AUTO_INCREMENT,
  evaluation_id BIGINT NOT NULL,
  criterion_code VARCHAR(30) NOT NULL,
  criterion_name VARCHAR(100) NOT NULL,
  weight_pct DECIMAL(8,2) NOT NULL DEFAULT 0,
  score DECIMAL(8,2) NOT NULL DEFAULT 0,
  weighted_score DECIMAL(8,2) NOT NULL DEFAULT 0,
  source_type ENUM('AUTO','MANUAL') NOT NULL DEFAULT 'AUTO',
  notes TEXT NULL,
  PRIMARY KEY (id),
  KEY idx_erp_vendor_eval_detail_eval (evaluation_id),
  CONSTRAINT fk_erp_vendor_eval_detail_header FOREIGN KEY (evaluation_id) REFERENCES erp_vendor_evaluation(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS erp_vendor_evaluation_history (
  id BIGINT NOT NULL AUTO_INCREMENT,
  evaluation_id BIGINT NOT NULL,
  status_lama VARCHAR(20) NULL,
  status_baru VARCHAR(20) NOT NULL,
  remarks TEXT NULL,
  changed_by VARCHAR(50) NULL,
  changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_erp_vendor_eval_history_eval (evaluation_id),
  CONSTRAINT fk_erp_vendor_eval_history_header FOREIGN KEY (evaluation_id) REFERENCES erp_vendor_evaluation(id) ON DELETE CASCADE
);

UPDATE sys_menu
SET nav_act='vendor_evaluation',
    page_name='Vendor Evaluation',
    url='vendor-evaluation',
    main_table='erp_vendor_evaluation',
    icon='fa-star-half-o',
    parent=416,
    parent_name='procurement',
    urutan_menu=4,
    dt_table='Y',
    tampil='Y',
    type_menu='page'
WHERE url='vendor-evaluation';

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'vendor_evaluation','Vendor Evaluation','vendor-evaluation','erp_vendor_evaluation','fa-star-half-o',4,416,'procurement','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='vendor-evaluation');

INSERT INTO sys_menu_role (id_menu,group_level,read_act,insert_act,update_act,delete_act,import_act)
SELECT m.id,g.group_level,'Y',
       CASE WHEN g.group_level IN ('admin','system_administrator','purchasing','manager_approver') THEN 'Y' ELSE 'N' END,
       CASE WHEN g.group_level IN ('admin','system_administrator','purchasing','manager_approver','quality_control') THEN 'Y' ELSE 'N' END,
       CASE WHEN g.group_level IN ('admin','system_administrator') THEN 'Y' ELSE 'N' END,
       'N'
FROM sys_menu m
JOIN (
  SELECT 'admin' group_level UNION ALL
  SELECT 'system_administrator' UNION ALL
  SELECT 'purchasing' UNION ALL
  SELECT 'manager_approver' UNION ALL
  SELECT 'quality_control' UNION ALL
  SELECT 'finance_akunting' UNION ALL
  SELECT 'auditor'
) g
LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.group_level
WHERE m.url='vendor-evaluation' AND r.id IS NULL;

UPDATE sys_menu_role r
JOIN sys_menu m ON m.id=r.id_menu
SET r.read_act='Y',
    r.insert_act=CASE WHEN r.group_level IN ('admin','system_administrator','purchasing','manager_approver') THEN 'Y' ELSE r.insert_act END,
    r.update_act=CASE WHEN r.group_level IN ('admin','system_administrator','purchasing','manager_approver','quality_control') THEN 'Y' ELSE r.update_act END
WHERE m.url='vendor-evaluation'
  AND r.group_level IN ('admin','system_administrator','purchasing','manager_approver','quality_control','finance_akunting','auditor');
