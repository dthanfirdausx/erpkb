CREATE TABLE IF NOT EXISTS erp_production_schedule (
  id bigint(20) NOT NULL AUTO_INCREMENT,
  schedule_no varchar(50) NOT NULL,
  id_production_order bigint(20) NOT NULL,
  no_production_order varchar(30) NOT NULL,
  schedule_date date NOT NULL,
  plant_code varchar(20) DEFAULT NULL,
  material_code varchar(100) NOT NULL,
  material_name varchar(255) DEFAULT NULL,
  order_qty decimal(18,5) DEFAULT 0.00000,
  uom varchar(20) DEFAULT NULL,
  planned_start datetime DEFAULT NULL,
  planned_finish datetime DEFAULT NULL,
  dispatch_status enum('DRAFT','SCHEDULED','DISPATCHED','IN_PROCESS','COMPLETED','CANCELLED') NOT NULL DEFAULT 'DRAFT',
  priority varchar(20) DEFAULT 'NORMAL',
  scheduler varchar(100) DEFAULT NULL,
  remarks text DEFAULT NULL,
  created_by varchar(100) DEFAULT NULL,
  created_at datetime DEFAULT CURRENT_TIMESTAMP,
  updated_by varchar(100) DEFAULT NULL,
  updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  released_by varchar(100) DEFAULT NULL,
  released_at datetime DEFAULT NULL,
  cancelled_by varchar(100) DEFAULT NULL,
  cancelled_at datetime DEFAULT NULL,
  cancel_reason varchar(255) DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_production_schedule_no (schedule_no),
  KEY idx_production_schedule_po (id_production_order,no_production_order),
  KEY idx_production_schedule_date_status (schedule_date,dispatch_status,plant_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_production_schedule_detail (
  id bigint(20) NOT NULL AUTO_INCREMENT,
  schedule_id bigint(20) NOT NULL,
  id_operation bigint(20) DEFAULT NULL,
  operation_no varchar(20) DEFAULT NULL,
  operation_name varchar(150) DEFAULT NULL,
  work_center varchar(100) DEFAULT NULL,
  work_center_name varchar(150) DEFAULT NULL,
  shift_id int(11) DEFAULT NULL,
  shift_code varchar(20) DEFAULT NULL,
  planned_start datetime DEFAULT NULL,
  planned_finish datetime DEFAULT NULL,
  duration_minutes decimal(18,2) DEFAULT 0.00,
  capacity_qty decimal(18,5) DEFAULT 0.00000,
  scheduled_qty decimal(18,5) DEFAULT 0.00000,
  sequence_no int(11) DEFAULT NULL,
  operation_status enum('OPEN','READY','DISPATCHED','STARTED','FINISHED','CANCELLED') DEFAULT 'OPEN',
  remarks varchar(255) DEFAULT NULL,
  created_at datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_ps_detail_header (schedule_id,sequence_no),
  KEY idx_ps_detail_wc_time (work_center,planned_start,planned_finish),
  CONSTRAINT fk_ps_detail_header FOREIGN KEY (schedule_id) REFERENCES erp_production_schedule(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_production_schedule_history (
  id bigint(20) NOT NULL AUTO_INCREMENT,
  schedule_id bigint(20) NOT NULL,
  status_lama varchar(30) DEFAULT NULL,
  status_baru varchar(30) NOT NULL,
  remarks varchar(255) DEFAULT NULL,
  changed_by varchar(100) DEFAULT NULL,
  changed_at datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_ps_history_header (schedule_id,changed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

UPDATE sys_menu
SET nav_act='production_schedule',
    page_name='Production Schedule',
    url='production-schedule',
    main_table='erp_production_schedule',
    icon='fa-calendar',
    tampil='Y',
    type_menu='page'
WHERE url='production-schedule';
