CREATE OR REPLACE VIEW v_stock_transaksi AS
SELECT
  MIN(sl.id) AS id,
  b.id AS id_barang,
  sl.kode AS kd_barang,
  b.nm_barang AS nm_barang,
  b.satuan AS satuan,
  b.kd_kategori AS kd_kategori,
  k.nm_kategori AS nm_kategori,
  SUM(sl.qty_sisa) AS stock,
  sl.plant_id AS plant_id,
  sl.storage_location_id AS storage_location_id,
  sl.storage_bin_id AS storage_bin_id,
  p.plant_code AS plant_code,
  CONCAT(s.storage_code, ' - ', s.storage_name) AS storage_location,
  CONCAT(bin.bin_code, ' - ', bin.bin_name) AS storage_bin
FROM stock_layer sl
LEFT JOIN barang b ON b.kd_barang = sl.kode
LEFT JOIN kategori k ON k.kd_kategori = b.kd_kategori
LEFT JOIN erp_plant p ON p.id = sl.plant_id
LEFT JOIN erp_storage_location s ON s.id = sl.storage_location_id
LEFT JOIN erp_storage_bin bin ON bin.id = sl.storage_bin_id
WHERE sl.lokasi = 'GUDANG'
  AND COALESCE(sl.stock_type, 'UNRESTRICTED') = 'UNRESTRICTED'
GROUP BY sl.kode,b.id,b.nm_barang,b.satuan,b.kd_kategori,k.nm_kategori,sl.plant_id,sl.storage_location_id,sl.storage_bin_id,p.plant_code,s.storage_code,s.storage_name,bin.bin_code,bin.bin_name
HAVING SUM(sl.qty_sisa) > 0;

CREATE OR REPLACE VIEW v_stock_pemasukan AS
SELECT
  b.id AS id_barang,
  b.kd_kategori AS kd_kategori,
  d.no_urut AS no_urut,
  b.kd_barang AS kd_barang,
  b.satuan AS satuan,
  b.nm_barang AS nm_barang,
  d.jumlah AS jumlah,
  d.id AS id_incoming_detail,
  d.lokasi AS lokasi,
  p.plant_id AS plant_id,
  p.storage_location_id AS storage_location_id,
  d.storage_bin_id AS storage_bin_id,
  ep.plant_code AS plant_code,
  es.storage_code AS storage_code,
  es.storage_name AS storage_name,
  eb.bin_code AS bin_code,
  eb.bin_name AS bin_name,
  IFNULL((SELECT SUM(td.jml) FROM transfer_detail td JOIN transfer t ON t.id_transfer = td.id_transfer WHERE td.id_incoming_detail = d.id AND t.dari = '1'),0) AS keluar,
  IFNULL((SELECT SUM(td.jml) FROM transfer_detail td JOIN transfer t ON t.id_transfer = td.id_transfer WHERE td.id_incoming_detail = d.id AND t.ke = '1'),0) AS masuk,
  p.nomor AS nomor,
  p.id AS id,
  p.no_bpb AS no_bpb,
  p.tgl_bpb AS tgl_bpb,
  p.pemasok AS pemasok,
  p.no_invoice AS no_invoice,
  p.tgl_invoice AS tgl_invoice,
  p.no_do AS no_do,
  p.catatan AS catatan,
  p.no_aju AS no_aju,
  p.tgl_aju AS tgl_aju,
  p.jenis_dokpab AS jenis_dokpab,
  p.no_dokpab AS no_dokpab,
  p.tgl_dokpab AS tgl_dokpab,
  p.userid AS userid,
  p.kd_catdet AS kd_catdet,
  p.flag AS flag,
  p.nopo AS nopo,
  p.efaktur AS efaktur,
  p.tgl_efaktur AS tgl_efaktur,
  p.tipe AS tipe,
  p.valuta AS valuta,
  p.kurs AS kurs,
  p.ref_no AS ref_no,
  p.no_kontrak AS no_kontrak,
  p.tgl_kontrak AS tgl_kontrak,
  p.date_created AS date_created,
  p.status AS status
FROM pemasukan_detail d
JOIN barang b ON b.kd_barang = d.kode
JOIN pemasukan p ON p.no_bpb = d.no_bpb
LEFT JOIN erp_plant ep ON ep.id = p.plant_id
LEFT JOIN erp_storage_location es ON es.id = p.storage_location_id
LEFT JOIN erp_storage_bin eb ON eb.id = d.storage_bin_id
WHERE p.is_reversal = 'N';
