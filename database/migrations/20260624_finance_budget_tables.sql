CREATE TABLE IF NOT EXISTS finance_budget_header (
  id INT(11) NOT NULL AUTO_INCREMENT,
  budget_version VARCHAR(50) NOT NULL,
  budget_name VARCHAR(150) NOT NULL,
  fiscal_year INT(4) DEFAULT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  status ENUM('DRAFT','APPROVED','INACTIVE') NOT NULL DEFAULT 'DRAFT',
  description VARCHAR(255) DEFAULT NULL,
  created_by VARCHAR(100) DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_by VARCHAR(100) DEFAULT NULL,
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_finance_budget_version (budget_version),
  KEY idx_finance_budget_period (start_date,end_date,status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS finance_budget_detail (
  id INT(11) NOT NULL AUTO_INCREMENT,
  budget_header_id INT(11) NOT NULL,
  no_rek VARCHAR(50) NOT NULL,
  period_month CHAR(7) NOT NULL,
  cost_center_id INT(11) DEFAULT NULL,
  profit_center_id INT(11) DEFAULT NULL,
  amount DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  note VARCHAR(255) DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_budget_detail_header_month (budget_header_id,period_month),
  KEY idx_budget_detail_account (no_rek),
  KEY idx_budget_detail_cc_pc (cost_center_id,profit_center_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
