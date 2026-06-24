<?php
include "../../inc/config.php";

$columns = array(
  'kd_barang',
  'nm_barang',
  'stock_qty',
  'satuan',
  'kategori',
  'location_text',
  'stock_type_text',
  'trace_status',
  'raw_material_count',
  'last_gr_no',
  'kd_barang'
);

$datatable->set_numbering_status(1);
$datatable->set_order_by("kd_barang");
$datatable->set_order_type("asc");

$sql = "
  SELECT *
  FROM (
    SELECT
      sl.kode AS kd_barang,
      COALESCE(b.nm_barang, sl.kode) AS nm_barang,
      COALESCE(SUM(sl.qty_sisa),0) AS stock_qty,
      COALESCE(b.satuan,'') AS satuan,
      COALESCE(b.kategori,b.kd_kategori,'') AS kategori,
      GROUP_CONCAT(DISTINCT CONCAT(COALESCE(ep.plant_code,'-'),' / ',COALESCE(es.storage_code,'-'),' / ',COALESCE(eb.bin_code,'-')) ORDER BY ep.plant_code,es.storage_code,eb.bin_code SEPARATOR ', ') AS location_text,
      GROUP_CONCAT(DISTINCT sl.stock_type ORDER BY sl.stock_type SEPARATOR ', ') AS stock_type_text,
      CASE
        WHEN SUM(CASE WHEN vi.trace_status <> 'OK' OR vi.trace_status IS NULL THEN 1 ELSE 0 END) > 0 THEN 'BROKEN'
        ELSE 'OK'
      END AS trace_status,
      COALESCE(MAX(vi.raw_material_count),0) AS raw_material_count,
      MAX(gp.gr_no) AS last_gr_no
    FROM stock_layer sl
    LEFT JOIN barang b ON b.kd_barang=sl.kode
    LEFT JOIN erp_plant ep ON ep.id=sl.plant_id
    LEFT JOIN erp_storage_location es ON es.id=sl.storage_location_id
    LEFT JOIN erp_storage_bin eb ON eb.id=sl.storage_bin_id
    LEFT JOIN erp_gr_production gp ON gp.id=sl.ref_id
    LEFT JOIN vw_production_output_trace_integrity vi ON vi.stock_layer_id=sl.id
    WHERE sl.ref_table='erp_gr_production'
      AND sl.kode LIKE 'BJ%'
    GROUP BY sl.kode,COALESCE(b.nm_barang, sl.kode),COALESCE(b.satuan,''),COALESCE(b.kategori,b.kd_kategori,'')
  ) stock_prod
";

$query = $datatable->get_custom($sql, $columns);
$data = array();
$i = 1;
foreach ($query as $value) {
  $traceLabel = $value->trace_status === 'OK'
    ? '<span class="label label-success">OK</span>'
    : '<span class="label label-danger">BROKEN</span>';
  $result = array();
  $result[] = $datatable->number($i);
  $result[] = $value->kd_barang;
  $result[] = $value->nm_barang;
  $result[] = number_format((float)$value->stock_qty, 5, ',', '.');
  $result[] = $value->satuan;
  $result[] = $value->kategori;
  $result[] = $value->location_text ?: '-';
  $result[] = $value->stock_type_text ?: '-';
  $result[] = $traceLabel;
  $result[] = (int)$value->raw_material_count;
  $result[] = $value->last_gr_no ?: '-';
  $result[] = $value->kd_barang;
  $data[] = $result;
  $i++;
}

$datatable->set_data($data);
$datatable->create_data();
?>
