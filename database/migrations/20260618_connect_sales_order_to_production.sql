ALTER TABLE production_order
  ADD COLUMN IF NOT EXISTS order_strategy ENUM('MTO','MTS') NOT NULL DEFAULT 'MTS' AFTER order_type,
  ADD COLUMN IF NOT EXISTS id_sales_order INT NULL AFTER no_production_order,
  ADD COLUMN IF NOT EXISTS no_sales_order VARCHAR(30) NULL AFTER id_sales_order,
  ADD COLUMN IF NOT EXISTS id_sales_order_detail BIGINT NULL AFTER no_sales_order,
  ADD COLUMN IF NOT EXISTS customer_code VARCHAR(30) NULL AFTER id_sales_order_detail,
  ADD COLUMN IF NOT EXISTS customer_po VARCHAR(100) NULL AFTER customer_code;

CREATE INDEX IF NOT EXISTS idx_production_order_so
  ON production_order(id_sales_order,no_sales_order,id_sales_order_detail);

UPDATE production_order
SET order_strategy = 'MTO'
WHERE no_sales_order IS NOT NULL AND no_sales_order <> '';

CREATE OR REPLACE VIEW v_sales_status AS
SELECT
  so.kode_penerima AS kode_penerima,
  so.alasan AS alasan,
  so.no_po AS no_po,
  so.status AS status,
  so.no_sales_order AS no_sales_order,
  so.id_quotation AS id_quotation,
  so.so_date AS so_date,
  p.nama AS nama,
  so.sales_id AS sales_id,
  so.currency AS currency,
  so.user AS user,
  so.shipping_address AS shipping_address,
  so.id_sales_order AS id_sales_order,
  IFNULL(sod.total_so,0) AS qty_so,
  IFNULL(prod.total_produksi,0) AS qty_produksi,
  IFNULL(kirim.total_kirim,0) AS qty_kirim,
  CASE
    WHEN IFNULL(kirim.total_kirim,0) >= IFNULL(sod.total_so,0) AND IFNULL(sod.total_so,0) > 0 THEN 'SUDAH DIKIRIM'
    WHEN IFNULL(kirim.total_kirim,0) > 0 AND IFNULL(kirim.total_kirim,0) < IFNULL(sod.total_so,0) THEN 'DIKIRIM SEBAGIAN'
    WHEN IFNULL(prod.total_produksi,0) = 0 THEN 'BELUM PRODUKSI'
    WHEN IFNULL(prod.total_produksi,0) < IFNULL(sod.total_so,0) THEN 'PRODUKSI BELUM FULL'
    WHEN IFNULL(prod.total_produksi,0) >= IFNULL(sod.total_so,0) THEN 'PROSES PRODUKSI'
    ELSE 'OPEN'
  END AS status_so
FROM sales_order so
JOIN penerima p ON so.kode_penerima = p.kode_penerima
LEFT JOIN (
  SELECT id_sales_order, SUM(qty) AS total_so
  FROM sales_order_detail
  GROUP BY id_sales_order
) sod ON sod.id_sales_order = so.id_sales_order
LEFT JOIN (
  SELECT no_sales_order, SUM(qty_produksi) AS total_produksi
  FROM (
    SELECT po.no_sales_order, SUM(grd.qty) AS qty_produksi
    FROM production_order po
    JOIN erp_gr_production gr ON gr.id_production_order = po.id_production_order AND gr.status = 'POSTED'
    JOIN erp_gr_production_detail grd ON grd.gr_id = gr.id
    WHERE po.no_sales_order IS NOT NULL AND po.no_sales_order <> ''
    GROUP BY po.no_sales_order
    UNION ALL
    SELECT b.no_sales_order, SUM(d.jumlah) AS qty_produksi
    FROM brgjadi b
    JOIN brgjadi_detail d ON b.id_produksi = d.id_produksi
    WHERE b.no_sales_order IS NOT NULL AND b.no_sales_order <> ''
    GROUP BY b.no_sales_order
  ) x
  GROUP BY no_sales_order
) prod ON prod.no_sales_order = so.no_sales_order
LEFT JOIN (
  SELECT sj.no_sales_order AS no_sales_order, SUM(sjd.qty_kirim) AS total_kirim
  FROM surat_jalan sj
  JOIN surat_jalan_detail sjd ON sj.id = sjd.surat_jalan_id
  WHERE sj.status <> 'dibatalkan'
  GROUP BY sj.no_sales_order
) kirim ON kirim.no_sales_order = so.no_sales_order
ORDER BY so.so_date DESC;
