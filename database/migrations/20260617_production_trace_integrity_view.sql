CREATE OR REPLACE VIEW vw_production_output_trace_integrity AS
SELECT
  sl.id AS stock_layer_id,
  sl.kode AS output_material_code,
  b.nm_barang AS output_material_name,
  sl.qty_masuk AS output_qty,
  sl.qty_sisa AS remaining_qty,
  sl.no_bpb AS gr_no,
  sl.ref_id AS gr_id,
  gp.no_production_order,
  gp.confirmation_no,
  gp.posting_date,
  COUNT(gt.id) AS trace_rows,
  COUNT(DISTINCT gt.raw_material_code) AS raw_material_count,
  SUM(
    CASE
      WHEN gt.id IS NOT NULL
       AND COALESCE(NULLIF(TRIM(gt.no_bpb), ''), NULLIF(TRIM(gt.no_aju), ''), NULLIF(TRIM(gt.jenis_dokpab), ''), NULLIF(TRIM(gt.no_dokpab), '')) IS NULL
      THEN 1 ELSE 0
    END
  ) AS missing_document_rows,
  CASE
    WHEN COUNT(gt.id) = 0 THEN 'BROKEN_NO_TRACE'
    WHEN SUM(
      CASE
        WHEN gt.id IS NOT NULL
         AND COALESCE(NULLIF(TRIM(gt.no_bpb), ''), NULLIF(TRIM(gt.no_aju), ''), NULLIF(TRIM(gt.jenis_dokpab), ''), NULLIF(TRIM(gt.no_dokpab), '')) IS NULL
        THEN 1 ELSE 0
      END
    ) > 0 THEN 'BROKEN_NO_DOCUMENT'
    ELSE 'OK'
  END AS trace_status
FROM stock_layer sl
LEFT JOIN barang b ON b.kd_barang = sl.kode
LEFT JOIN erp_gr_production gp ON gp.id = sl.ref_id
LEFT JOIN erp_gr_production_trace gt ON gt.output_stock_layer_id = sl.id
WHERE sl.ref_table = 'erp_gr_production'
GROUP BY
  sl.id,
  sl.kode,
  b.nm_barang,
  sl.qty_masuk,
  sl.qty_sisa,
  sl.no_bpb,
  sl.ref_id,
  gp.no_production_order,
  gp.confirmation_no,
  gp.posting_date;
