CREATE TABLE IF NOT EXISTS erp_attendance_device (
  id INT(11) NOT NULL AUTO_INCREMENT,
  device_code VARCHAR(40) NOT NULL,
  serial_no VARCHAR(80) DEFAULT NULL,
  device_name VARCHAR(120) NOT NULL,
  brand VARCHAR(60) DEFAULT 'Solution',
  model VARCHAR(80) DEFAULT NULL,
  ip_address VARCHAR(60) DEFAULT NULL,
  port_number INT(11) DEFAULT NULL,
  location_code VARCHAR(40) DEFAULT NULL,
  timezone VARCHAR(60) NOT NULL DEFAULT 'Asia/Jakarta',
  api_key_hash CHAR(64) DEFAULT NULL,
  sync_mode ENUM('PUSH_JSON','ADMS','PULL_SDK','MANUAL') NOT NULL DEFAULT 'PUSH_JSON',
  device_status ENUM('ACTIVE','INACTIVE','MAINTENANCE') NOT NULL DEFAULT 'ACTIVE',
  last_sync_at DATETIME DEFAULT NULL,
  remarks TEXT DEFAULT NULL,
  created_by VARCHAR(50) DEFAULT 'admin',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_by VARCHAR(50) DEFAULT 'admin',
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_att_device_code (device_code),
  KEY idx_att_device_serial (serial_no),
  KEY idx_att_device_status (device_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS erp_attendance_device_user_map (
  id INT(11) NOT NULL AUTO_INCREMENT,
  device_id INT(11) NOT NULL,
  machine_user_id VARCHAR(50) NOT NULL,
  employee_id INT(11) NOT NULL,
  employee_no VARCHAR(20) NOT NULL,
  valid_from DATE NOT NULL DEFAULT '2026-01-01',
  valid_to DATE NOT NULL DEFAULT '9999-12-31',
  map_status ENUM('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  created_by VARCHAR(50) DEFAULT 'admin',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_by VARCHAR(50) DEFAULT 'admin',
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_att_device_user (device_id,machine_user_id,valid_from),
  KEY idx_att_map_employee (employee_id),
  KEY idx_att_map_employee_no (employee_no),
  CONSTRAINT fk_att_map_device FOREIGN KEY (device_id) REFERENCES erp_attendance_device(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS erp_attendance_machine_batch (
  id INT(11) NOT NULL AUTO_INCREMENT,
  batch_no VARCHAR(40) NOT NULL,
  device_id INT(11) DEFAULT NULL,
  device_code VARCHAR(40) DEFAULT NULL,
  serial_no VARCHAR(80) DEFAULT NULL,
  request_source VARCHAR(40) DEFAULT NULL,
  request_ip VARCHAR(80) DEFAULT NULL,
  payload_type ENUM('JSON','ADMS_ATTLOG','FORM','UNKNOWN') NOT NULL DEFAULT 'UNKNOWN',
  total_logs INT(11) NOT NULL DEFAULT 0,
  accepted_logs INT(11) NOT NULL DEFAULT 0,
  duplicate_logs INT(11) NOT NULL DEFAULT 0,
  error_logs INT(11) NOT NULL DEFAULT 0,
  sync_status ENUM('RECEIVED','PARTIAL','SUCCESS','ERROR') NOT NULL DEFAULT 'RECEIVED',
  raw_payload MEDIUMTEXT DEFAULT NULL,
  response_payload TEXT DEFAULT NULL,
  received_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  processed_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_att_batch_no (batch_no),
  KEY idx_att_batch_device (device_id),
  KEY idx_att_batch_received (received_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS erp_attendance_machine_log (
  id INT(11) NOT NULL AUTO_INCREMENT,
  batch_id INT(11) DEFAULT NULL,
  device_id INT(11) DEFAULT NULL,
  device_code VARCHAR(40) DEFAULT NULL,
  serial_no VARCHAR(80) DEFAULT NULL,
  employee_id INT(11) DEFAULT NULL,
  employee_no VARCHAR(20) DEFAULT NULL,
  machine_user_id VARCHAR(50) DEFAULT NULL,
  punch_time DATETIME NOT NULL,
  punch_date DATE NOT NULL,
  punch_type ENUM('IN','OUT','BREAK_IN','BREAK_OUT','UNKNOWN') NOT NULL DEFAULT 'UNKNOWN',
  verify_type VARCHAR(50) DEFAULT NULL,
  work_code VARCHAR(50) DEFAULT NULL,
  raw_status VARCHAR(50) DEFAULT NULL,
  raw_payload TEXT DEFAULT NULL,
  process_status ENUM('PENDING','PROCESSED','DUPLICATE','ERROR') NOT NULL DEFAULT 'PENDING',
  process_message VARCHAR(255) DEFAULT NULL,
  attendance_id INT(11) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  processed_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_att_machine_log (device_id,machine_user_id,punch_time,punch_type),
  KEY idx_att_log_employee_date (employee_no,punch_date),
  KEY idx_att_log_status (process_status),
  KEY idx_att_log_attendance (attendance_id),
  CONSTRAINT fk_att_log_batch FOREIGN KEY (batch_id) REFERENCES erp_attendance_machine_batch(id) ON DELETE SET NULL,
  CONSTRAINT fk_att_log_device FOREIGN KEY (device_id) REFERENCES erp_attendance_device(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO erp_attendance_device
(device_code,serial_no,device_name,brand,model,ip_address,port_number,location_code,api_key_hash,sync_mode,device_status,remarks,created_by,updated_by,updated_at)
SELECT 'SOL-DEMO','SN-DEMO-001','Demo Solution Attendance Machine','Solution','X-Series','127.0.0.1',80,'HQ',
       SHA2('erpkb-attendance-demo-key',256),'PUSH_JSON','ACTIVE',
       'Demo device untuk pengujian endpoint attendance machine sync. Ganti api key sebelum production.',
       'admin','admin',NOW()
WHERE NOT EXISTS (SELECT 1 FROM erp_attendance_device WHERE device_code='SOL-DEMO');

INSERT INTO erp_attendance_device_user_map
(device_id,machine_user_id,employee_id,employee_no,valid_from,valid_to,map_status,created_by,updated_by,updated_at)
SELECT d.id,e.employee_no,e.id,e.employee_no,'2026-01-01','9999-12-31','ACTIVE','admin','admin',NOW()
  FROM erp_attendance_device d
  JOIN erp_employee_master e ON e.employment_status IN ('ACTIVE','PROBATION','CONTRACT')
 WHERE d.device_code='SOL-DEMO'
   AND NOT EXISTS (
     SELECT 1 FROM erp_attendance_device_user_map m
      WHERE m.device_id=d.id AND m.machine_user_id=e.employee_no
   )
 LIMIT 200;
