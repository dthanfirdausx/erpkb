DROP TRIGGER IF EXISTS trg_stock_layer_no_negative_insert;
DROP TRIGGER IF EXISTS trg_stock_layer_no_negative_update;
DROP TRIGGER IF EXISTS trg_detail_transaksi_no_negative_insert;
DROP TRIGGER IF EXISTS trg_detail_transaksi_no_negative_update;

DELIMITER $$

CREATE TRIGGER trg_stock_layer_no_negative_insert
BEFORE INSERT ON stock_layer
FOR EACH ROW
BEGIN
  IF COALESCE(NEW.qty_sisa,0) < -0.00001 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Validasi stok gagal: qty_sisa stock layer tidak boleh minus.';
  END IF;
END$$

CREATE TRIGGER trg_stock_layer_no_negative_update
BEFORE UPDATE ON stock_layer
FOR EACH ROW
BEGIN
  IF COALESCE(NEW.qty_sisa,0) < -0.00001 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Validasi stok gagal: qty_sisa stock layer tidak boleh minus.';
  END IF;
END$$

CREATE TRIGGER trg_detail_transaksi_no_negative_insert
BEFORE INSERT ON detail_transaksi
FOR EACH ROW
BEGIN
  DECLARE v_delta DECIMAL(24,5) DEFAULT 0;
  DECLARE v_balance DECIMAL(24,5) DEFAULT 0;
  DECLARE v_plant_id INT DEFAULT NULL;
  DECLARE v_sloc_id INT DEFAULT NULL;
  DECLARE v_bin_id INT DEFAULT NULL;
  DECLARE v_stock_type VARCHAR(20) DEFAULT 'UNRESTRICTED';

  IF COALESCE(NEW.is_reversal,0)=0 THEN
    SET v_delta = CASE
      WHEN NEW.direction='OUT' OR COALESCE(NEW.qty,0)<0 OR NEW.move_code IN ('102','122','201','221','261','262','551','601','602','702','712') THEN -ABS(COALESCE(NEW.qty,0))
      ELSE ABS(COALESCE(NEW.qty,0))
    END;

    IF v_delta < 0 THEN
      SELECT COALESCE(NEW.plant_id,sl.plant_id),
             COALESCE(NEW.storage_location_id,NEW.destination_storage_location_id,sl.storage_location_id),
             COALESCE(NEW.storage_bin_id,NEW.destination_storage_bin_id,sl.storage_bin_id),
             COALESCE(NEW.stock_type,NEW.destination_stock_type,sl.stock_type,'UNRESTRICTED')
        INTO v_plant_id,v_sloc_id,v_bin_id,v_stock_type
      FROM (SELECT 1) x
      LEFT JOIN stock_layer sl ON sl.id=NEW.ref_id AND sl.kode=NEW.kd_barang
      LIMIT 1;

      SELECT COALESCE(SUM(CASE
               WHEN dt.direction='OUT' OR COALESCE(dt.qty,0)<0 OR dt.move_code IN ('102','122','201','221','261','262','551','601','602','702','712') THEN -ABS(COALESCE(dt.qty,0))
               ELSE ABS(COALESCE(dt.qty,0))
             END),0)
        INTO v_balance
      FROM detail_transaksi dt
      LEFT JOIN stock_layer sl ON sl.id=dt.ref_id AND sl.kode=dt.kd_barang
      WHERE dt.kd_barang=NEW.kd_barang
        AND COALESCE(dt.is_reversal,0)=0
        AND COALESCE(dt.plant_id,sl.plant_id) <=> v_plant_id
        AND COALESCE(dt.storage_location_id,dt.destination_storage_location_id,sl.storage_location_id) <=> v_sloc_id
        AND COALESCE(dt.storage_bin_id,dt.destination_storage_bin_id,sl.storage_bin_id) <=> v_bin_id
        AND COALESCE(dt.stock_type,dt.destination_stock_type,sl.stock_type,'UNRESTRICTED') <=> v_stock_type;

      IF v_balance + v_delta < -0.00001 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Validasi stok gagal: transaksi ini membuat saldo akhir material minus pada lokasi/stock type tersebut.';
      END IF;
    END IF;
  END IF;
END$$

CREATE TRIGGER trg_detail_transaksi_no_negative_update
BEFORE UPDATE ON detail_transaksi
FOR EACH ROW
BEGIN
  DECLARE v_delta DECIMAL(24,5) DEFAULT 0;
  DECLARE v_balance DECIMAL(24,5) DEFAULT 0;
  DECLARE v_plant_id INT DEFAULT NULL;
  DECLARE v_sloc_id INT DEFAULT NULL;
  DECLARE v_bin_id INT DEFAULT NULL;
  DECLARE v_stock_type VARCHAR(20) DEFAULT 'UNRESTRICTED';

  IF COALESCE(NEW.is_reversal,0)=0 THEN
    SET v_delta = CASE
      WHEN NEW.direction='OUT' OR COALESCE(NEW.qty,0)<0 OR NEW.move_code IN ('102','122','201','221','261','262','551','601','602','702','712') THEN -ABS(COALESCE(NEW.qty,0))
      ELSE ABS(COALESCE(NEW.qty,0))
    END;

    IF v_delta < 0 THEN
      SELECT COALESCE(NEW.plant_id,sl.plant_id),
             COALESCE(NEW.storage_location_id,NEW.destination_storage_location_id,sl.storage_location_id),
             COALESCE(NEW.storage_bin_id,NEW.destination_storage_bin_id,sl.storage_bin_id),
             COALESCE(NEW.stock_type,NEW.destination_stock_type,sl.stock_type,'UNRESTRICTED')
        INTO v_plant_id,v_sloc_id,v_bin_id,v_stock_type
      FROM (SELECT 1) x
      LEFT JOIN stock_layer sl ON sl.id=NEW.ref_id AND sl.kode=NEW.kd_barang
      LIMIT 1;

      SELECT COALESCE(SUM(CASE
               WHEN dt.direction='OUT' OR COALESCE(dt.qty,0)<0 OR dt.move_code IN ('102','122','201','221','261','262','551','601','602','702','712') THEN -ABS(COALESCE(dt.qty,0))
               ELSE ABS(COALESCE(dt.qty,0))
             END),0)
        INTO v_balance
      FROM detail_transaksi dt
      LEFT JOIN stock_layer sl ON sl.id=dt.ref_id AND sl.kode=dt.kd_barang
      WHERE dt.id_detail<>OLD.id_detail
        AND dt.kd_barang=NEW.kd_barang
        AND COALESCE(dt.is_reversal,0)=0
        AND COALESCE(dt.plant_id,sl.plant_id) <=> v_plant_id
        AND COALESCE(dt.storage_location_id,dt.destination_storage_location_id,sl.storage_location_id) <=> v_sloc_id
        AND COALESCE(dt.storage_bin_id,dt.destination_storage_bin_id,sl.storage_bin_id) <=> v_bin_id
        AND COALESCE(dt.stock_type,dt.destination_stock_type,sl.stock_type,'UNRESTRICTED') <=> v_stock_type;

      IF v_balance + v_delta < -0.00001 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Validasi stok gagal: perubahan ini membuat saldo akhir material minus pada lokasi/stock type tersebut.';
      END IF;
    END IF;
  END IF;
END$$

DELIMITER ;
