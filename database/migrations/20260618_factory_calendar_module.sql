CREATE TABLE IF NOT EXISTS erp_factory_calendar_header (
  id int(11) NOT NULL AUTO_INCREMENT,
  calendar_code varchar(30) NOT NULL,
  calendar_name varchar(150) NOT NULL,
  plant_id int(11) DEFAULT NULL,
  plant_code varchar(20) DEFAULT NULL,
  valid_from date NOT NULL,
  valid_to date NOT NULL,
  monday enum('Y','N') DEFAULT 'Y',
  tuesday enum('Y','N') DEFAULT 'Y',
  wednesday enum('Y','N') DEFAULT 'Y',
  thursday enum('Y','N') DEFAULT 'Y',
  friday enum('Y','N') DEFAULT 'Y',
  saturday enum('Y','N') DEFAULT 'N',
  sunday enum('Y','N') DEFAULT 'N',
  default_shift_id int(11) DEFAULT NULL,
  default_shift_code varchar(20) DEFAULT NULL,
  working_hours decimal(8,2) DEFAULT 8.00,
  calendar_status varchar(20) DEFAULT 'DRAFT',
  remarks text DEFAULT NULL,
  created_by varchar(100) DEFAULT NULL,
  created_at datetime DEFAULT CURRENT_TIMESTAMP,
  updated_by varchar(100) DEFAULT NULL,
  updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  released_by varchar(100) DEFAULT NULL,
  released_at datetime DEFAULT NULL,
  inactive_reason varchar(255) DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_fc_code (calendar_code),
  KEY idx_fc_plant_status (plant_code,calendar_status,valid_from,valid_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE erp_factory_calendar
  ADD COLUMN IF NOT EXISTS calendar_id int(11) NULL AFTER id,
  ADD COLUMN IF NOT EXISTS calendar_code varchar(30) NULL AFTER calendar_id,
  ADD COLUMN IF NOT EXISTS plant_id int(11) NULL AFTER calendar_code,
  ADD COLUMN IF NOT EXISTS plant_code varchar(20) NULL AFTER plant_id,
  ADD COLUMN IF NOT EXISTS weekday_no tinyint(1) NULL AFTER nama_hari,
  ADD COLUMN IF NOT EXISTS shift_id int(11) NULL AFTER tipe_hari,
  ADD COLUMN IF NOT EXISTS shift_code varchar(20) NULL AFTER shift_id,
  ADD COLUMN IF NOT EXISTS working_hours decimal(8,2) DEFAULT 8.00 AFTER shift_code,
  ADD COLUMN IF NOT EXISTS exception_type varchar(30) NULL AFTER working_hours,
  ADD COLUMN IF NOT EXISTS source_type varchar(30) DEFAULT 'SYSTEM' AFTER exception_type,
  ADD COLUMN IF NOT EXISTS updated_by varchar(100) NULL AFTER keterangan,
  ADD COLUMN IF NOT EXISTS updated_at datetime NULL AFTER updated_by;

ALTER TABLE erp_factory_calendar
  DROP INDEX IF EXISTS tanggal,
  DROP INDEX IF EXISTS uk_erp_factory_calendar_date;

ALTER TABLE erp_factory_calendar
  ADD UNIQUE KEY IF NOT EXISTS uk_fc_day (calendar_id,tanggal),
  ADD KEY IF NOT EXISTS idx_fc_day_plant_date (plant_code,tanggal,tipe_hari);

INSERT INTO erp_factory_calendar_header
(calendar_code,calendar_name,plant_id,plant_code,valid_from,valid_to,default_shift_id,default_shift_code,working_hours,calendar_status,remarks,created_by,updated_by)
SELECT 'FC-BASE-2026','Baseline Factory Calendar 2026-2027',p.id,p.plant_code,'2026-01-01','2027-12-31',s.id,s.kode_shift,8.00,'RELEASED','Migrated from legacy daily calendar','system','system'
FROM erp_plant p
LEFT JOIN erp_shift s ON s.kode_shift='SHIFT-1'
WHERE p.plant_code='PL01'
  AND NOT EXISTS (SELECT 1 FROM erp_factory_calendar_header WHERE calendar_code='FC-BASE-2026');

UPDATE erp_factory_calendar_header
SET calendar_name='Baseline Factory Calendar 2026-2027',
    valid_to='2027-12-31'
WHERE calendar_code='FC-BASE-2026'
  AND valid_to<'2027-12-31';

UPDATE erp_factory_calendar d
JOIN erp_factory_calendar_header h ON h.calendar_code='FC-BASE-2026'
LEFT JOIN erp_shift s ON s.kode_shift='SHIFT-1'
SET d.calendar_id=h.id,
    d.calendar_code=h.calendar_code,
    d.plant_id=h.plant_id,
    d.plant_code=h.plant_code,
    d.weekday_no=DAYOFWEEK(d.tanggal),
    d.shift_id=s.id,
    d.shift_code=s.kode_shift,
    d.working_hours=CASE WHEN d.tipe_hari='Kerja' THEN 8.00 ELSE 0 END,
    d.source_type='MIGRATED'
WHERE d.calendar_id IS NULL;

UPDATE sys_menu
SET nav_act='factory_calendar',
    main_table='erp_factory_calendar_header',
    page_name='Factory Calendar',
    icon='fa-calendar-check-o',
    tampil='Y',
    type_menu='page'
WHERE url='factory-calendar';
