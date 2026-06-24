-- =====================================================
-- QUOTATION FOLLOW UP MODULE
-- SAP SD Quotation follow-up activity tracking
-- =====================================================

CREATE TABLE IF NOT EXISTS sales_quotation_followup (
  id INT(11) NOT NULL AUTO_INCREMENT,
  quotation_id INT(11) NOT NULL,
  followup_date DATETIME NOT NULL,
  contact_method ENUM('PHONE','EMAIL','WHATSAPP','MEETING','VISIT','OTHER') NOT NULL DEFAULT 'PHONE',
  contact_person VARCHAR(100) NULL,
  sales_person VARCHAR(100) NULL,
  activity_type ENUM('REMINDER','NEGOTIATION','TECHNICAL_CLARIFICATION','PRICE_REVISION','CLOSING','OTHER') NOT NULL DEFAULT 'REMINDER',
  result_status ENUM('OPEN','WAITING_CUSTOMER','NEED_REVISION','WON','LOST','CANCELLED') NOT NULL DEFAULT 'OPEN',
  probability_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
  discussion_summary TEXT NULL,
  next_action VARCHAR(255) NULL,
  next_followup_date DATETIME NULL,
  lost_reason VARCHAR(255) NULL,
  created_by VARCHAR(50) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_by VARCHAR(50) NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_sqf_quotation (quotation_id),
  KEY idx_sqf_followup_date (followup_date),
  KEY idx_sqf_next_followup_date (next_followup_date),
  KEY idx_sqf_status (result_status),
  CONSTRAINT fk_sqf_quotation
    FOREIGN KEY (quotation_id) REFERENCES sales_quotation(id_quotation)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE sys_menu
SET nav_act='quotation_follow_up',
    main_table='sales_quotation_followup',
    page_name='Quotation Follow Up',
    tampil='Y'
WHERE url='quotation-follow-up';

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id, g.group_level, 'Y', 'Y', 'Y', 'N', 'N'
FROM sys_menu m
JOIN (
  SELECT DISTINCT group_level
  FROM sys_menu_role
  WHERE COALESCE(group_level,'') <> ''
) g
WHERE m.url='quotation-follow-up'
  AND NOT EXISTS (
    SELECT 1
    FROM sys_menu_role r
    WHERE r.id_menu=m.id AND r.group_level=g.group_level
  );
