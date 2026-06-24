CREATE TABLE IF NOT EXISTS production_order_release_log (
  id int(11) NOT NULL AUTO_INCREMENT,
  id_production_order bigint(20) NOT NULL,
  no_production_order varchar(30) NOT NULL,
  release_status varchar(20) NOT NULL DEFAULT 'RELEASED',
  readiness_score decimal(5,2) DEFAULT 0.00,
  error_count int(11) DEFAULT 0,
  warning_count int(11) DEFAULT 0,
  readiness_json text DEFAULT NULL,
  remarks text DEFAULT NULL,
  released_by varchar(100) DEFAULT NULL,
  released_at datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_release_po (id_production_order,no_production_order),
  KEY idx_release_at (released_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

UPDATE sys_menu
SET nav_act='production_order_release',
    page_name='Production Order Release',
    url='production-order-release',
    main_table='production_order',
    icon='fa-unlock',
    tampil='Y',
    type_menu='page'
WHERE url='production-order-release';
