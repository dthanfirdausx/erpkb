CREATE TABLE IF NOT EXISTS cash_flow_mapping (
  id INT(11) NOT NULL AUTO_INCREMENT,
  no_rek VARCHAR(50) NOT NULL,
  cash_flow_group VARCHAR(30) NOT NULL,
  cash_flow_type VARCHAR(30) DEFAULT NULL,
  note VARCHAR(255) DEFAULT NULL,
  is_active CHAR(1) NOT NULL DEFAULT 'Y',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_cash_flow_mapping_account (no_rek),
  KEY idx_cash_flow_mapping_group (cash_flow_group),
  KEY idx_cash_flow_mapping_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

