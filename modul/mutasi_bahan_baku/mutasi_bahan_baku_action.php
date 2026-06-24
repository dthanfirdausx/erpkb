<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
//session_check_json();
function mbb_action_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function mbb_action_num($value, $dec = 2) { return number_format((float)$value, $dec, ',', '.'); }
function mbb_action_input($key, $default = '') { return isset($_REQUEST[$key]) ? trim((string)$_REQUEST[$key]) : $default; }
function mbb_action_stock_opname_row($db, $material, $tglAkhir, $plantId, $slocId, $binId, $stockType) {
  $sql = "
    SELECT doc_type,doc_no,count_date,counted_qty FROM (
      SELECT 'STOCK_OPNAME' AS doc_type,d.doc_no,d.opname_date AS count_date,i.counted_qty,i.counted_at
      FROM stock_opname_document_items i
      JOIN stock_opname_documents d ON d.id=i.document_id
      WHERE d.status<>'CANCELLED' AND i.status IN ('COUNTED','POSTED') AND i.material_code=? AND d.opname_date=?
        AND (? IS NULL OR COALESCE(i.plant_id,d.plant_id)=?)
        AND (? IS NULL OR COALESCE(i.storage_location_id,d.storage_location_id)=?)
        AND (? IS NULL OR COALESCE(i.storage_bin_id,d.storage_bin_id)=?)
        AND (?='' OR COALESCE(i.stock_type,d.stock_type,'UNRESTRICTED')=?)
      UNION ALL
      SELECT 'CYCLE_COUNT' AS doc_type,d.doc_no,d.count_date AS count_date,i.counted_qty,i.counted_at
      FROM cycle_count_document_items i
      JOIN cycle_count_documents d ON d.id=i.document_id
      WHERE d.status<>'CANCELLED' AND i.status IN ('COUNTED','POSTED') AND i.material_code=? AND d.count_date=?
        AND (? IS NULL OR COALESCE(i.plant_id,d.plant_id)=?)
        AND (? IS NULL OR COALESCE(i.storage_location_id,d.storage_location_id)=?)
        AND (? IS NULL OR COALESCE(i.storage_bin_id,d.storage_bin_id)=?)
        AND (?='' OR COALESCE(i.stock_type,d.stock_type,'UNRESTRICTED')=?)
    ) x
    ORDER BY count_date DESC, counted_at DESC, doc_no DESC
    LIMIT 1";
  $plantParam = $plantId === null || $plantId === '' ? null : (int)$plantId;
  $slocParam = $slocId === null || $slocId === '' ? null : (int)$slocId;
  $binParam = $binId === null || $binId === '' ? null : (int)$binId;
  $stockParam = trim((string)$stockType);
  $params = array(
    $material, $tglAkhir, $plantParam, $plantParam, $slocParam, $slocParam, $binParam, $binParam, $stockParam, $stockParam,
    $material, $tglAkhir, $plantParam, $plantParam, $slocParam, $slocParam, $binParam, $binParam, $stockParam, $stockParam
  );
  return $db->fetch($sql, $params);
}
function mbb_action_rows($db, $limit = 0, $offset = 0) {
  $tglAwal = mbb_action_input('tgl_awal', date('Y-m-01'));
  $tglAkhir = mbb_action_input('tgl_akhir', date('Y-m-d'));
  $material = mbb_action_input('material_code');
  $plantId = mbb_action_input('plant_id');
  $slocId = mbb_action_input('storage_location_id');
  $binId = mbb_action_input('storage_bin_id');
  $stockType = mbb_action_input('stock_type');
  $keyword = mbb_action_input('keyword');

  $where = " WHERE b.kd_kategori='K01' ";
  $filterParams = array();
  if ($material !== '') { $where .= " AND b.kd_barang=? "; $filterParams[] = $material; }
  if ($plantId !== '') { $where .= " AND COALESCE(dt.plant_id,sl.plant_id)=? "; $filterParams[] = $plantId; }
  if ($slocId !== '') { $where .= " AND COALESCE(dt.storage_location_id,dt.destination_storage_location_id,sl.storage_location_id)=? "; $filterParams[] = $slocId; }
  if ($binId !== '') { $where .= " AND COALESCE(dt.storage_bin_id,dt.destination_storage_bin_id,sl.storage_bin_id)=? "; $filterParams[] = $binId; }
  if ($stockType !== '') { $where .= " AND COALESCE(dt.stock_type,dt.destination_stock_type,sl.stock_type,'UNRESTRICTED')=? "; $filterParams[] = $stockType; }
  if ($keyword !== '') {
    $where .= " AND (b.kd_barang LIKE ? OR b.nm_barang LIKE ? OR dt.no_ref LIKE ? OR dt.no_bpb LIKE ? OR dt.no_aju LIKE ? OR dt.no_dokpab LIKE ? OR dt.remark LIKE ?) ";
    $kw = '%'.$keyword.'%';
    for ($i=0; $i<7; $i++) $filterParams[] = $kw;
  }

  $movementExpr = "CASE WHEN dt.id_detail IS NULL THEN 'IN' WHEN dt.direction='OUT' OR COALESCE(dt.qty,0)<0 OR dt.move_code IN ('102','122','201','221','261','262','551','601','602') THEN 'OUT' ELSE 'IN' END";
  $adjustExpr = "CASE WHEN dt.move_code IN ('701','702','711','712') OR COALESCE(dt.ref_type,'') LIKE '%DIFF%' OR COALESCE(dt.ref_type,'') LIKE '%OPNAME%' OR COALESCE(dt.ref_type,'') LIKE '%ADJUST%' THEN 1 ELSE 0 END";
  $signedQtyExpr = "CASE WHEN ($movementExpr)='OUT' THEN -ABS(COALESCE(dt.qty,0)) ELSE ABS(COALESCE(dt.qty,0)) END";
  $sql = "
    SELECT
      b.id,b.kd_barang,b.nm_barang,b.satuan,
      COALESCE(dt.plant_id,sl.plant_id) AS plant_id,
      COALESCE(dt.storage_location_id,dt.destination_storage_location_id,sl.storage_location_id) AS storage_location_id,
      COALESCE(dt.storage_bin_id,dt.destination_storage_bin_id,sl.storage_bin_id) AS storage_bin_id,
      ep.plant_code,es.storage_code,eb.bin_code,
      COALESCE(dt.stock_type,dt.destination_stock_type,sl.stock_type,'UNRESTRICTED') AS stock_type,
      COALESCE(SUM(CASE WHEN dt.document_date < ? THEN $signedQtyExpr ELSE 0 END),0) AS saldo_awal,
      COALESCE(SUM(CASE WHEN dt.document_date BETWEEN ? AND ? AND ($adjustExpr)=0 AND ($movementExpr)='IN' THEN ABS(COALESCE(dt.qty,0)) ELSE 0 END),0) AS pemasukan,
      COALESCE(SUM(CASE WHEN dt.document_date BETWEEN ? AND ? AND ($adjustExpr)=0 AND ($movementExpr)='OUT' THEN ABS(COALESCE(dt.qty,0)) ELSE 0 END),0) AS pengeluaran,
      COALESCE(SUM(CASE WHEN dt.document_date BETWEEN ? AND ? AND ($adjustExpr)=1 THEN $signedQtyExpr ELSE 0 END),0) AS penyesuaian,
      COALESCE(SUM(CASE WHEN dt.document_date <= ? THEN $signedQtyExpr ELSE 0 END),0) AS saldo_akhir,
      COALESCE(SUM(CASE WHEN dt.document_date BETWEEN ? AND ? THEN 1 ELSE 0 END),0) AS movement_lines
    FROM barang b
    LEFT JOIN detail_transaksi dt ON dt.kd_barang=b.kd_barang
      AND dt.document_date <= ?
      AND (
        dt.posisi='GUDANG'
        OR dt.lokasi LIKE '%GUDANG%'
        OR dt.lokasi LIKE '%WAREHOUSE%'
        OR dt.move_code IN ('201','221','261','262','551','601','602','701','702','711','712')
        OR COALESCE(dt.ref_type,'') IN ('ISSUE_PROD','ISSUE_PRODUCTION','GI_DELIVERY','MANUAL_ADJUSTMENT','PI_DIFF')
        OR COALESCE(dt.ref_type,'') LIKE '%DIFF%'
        OR COALESCE(dt.ref_type,'') LIKE '%ADJUST%'
        OR dt.posisi IS NULL
      )
      AND COALESCE(dt.is_reversal,0)=0
    LEFT JOIN stock_layer sl ON sl.id=dt.ref_id AND sl.kode=b.kd_barang
    LEFT JOIN erp_plant ep ON ep.id=COALESCE(dt.plant_id,sl.plant_id)
    LEFT JOIN erp_storage_location es ON es.id=COALESCE(dt.storage_location_id,dt.destination_storage_location_id,sl.storage_location_id)
    LEFT JOIN erp_storage_bin eb ON eb.id=COALESCE(dt.storage_bin_id,dt.destination_storage_bin_id,sl.storage_bin_id)
    $where
    GROUP BY b.id,b.kd_barang,b.nm_barang,b.satuan,plant_id,storage_location_id,storage_bin_id,ep.plant_code,es.storage_code,eb.bin_code,stock_type
    HAVING saldo_awal<>0 OR pemasukan<>0 OR pengeluaran<>0 OR penyesuaian<>0 OR saldo_akhir<>0
    ORDER BY b.kd_barang ASC
  ";
  if ($limit > 0) $sql .= " LIMIT ".(int)$offset.",".(int)$limit;
  $params = array($tglAwal.' 00:00:00',$tglAwal.' 00:00:00',$tglAkhir.' 23:59:59',$tglAwal.' 00:00:00',$tglAkhir.' 23:59:59',$tglAwal.' 00:00:00',$tglAkhir.' 23:59:59',$tglAkhir.' 23:59:59',$tglAwal.' 00:00:00',$tglAkhir.' 23:59:59',$tglAkhir.' 23:59:59');
  return $db->query($sql, array_merge($params, $filterParams));
}
switch ($_GET["act"]) {

  case "buat_view":
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('status'=>'good','message'=>'Mutasi bahan baku dibaca langsung dari detail_transaksi.'));
    break;

  case "excel":
    $initialOutputBufferLevel = ob_get_level();
    ob_start();
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
    require "../../inc/lib/PHPExcel.php";
    require_once "../../inc/excel_style_helper.php";
    PHPExcel_Shared_File::setUseUploadTempDirectory(true);
    $tglAwal = mbb_action_input('tgl_awal', date('Y-m-01'));
    $tglAkhir = mbb_action_input('tgl_akhir', date('Y-m-d'));
    $companyName = defined('namaPT') ? namaPT : (defined('shortTittle') ? shortTittle : 'NAMA_PT');
    $rows = mbb_action_rows($db);

    $excel = new PHPExcel();
    $sheet = $excel->setActiveSheetIndex(0);
    $sheet->setTitle(erp_export_sheet_title('Mutasi BB'));
    $lastCol = 'L';
    $headerStart = 6;
    $headerEnd = 7;
    $firstDataRow = 8;

    $sheet->mergeCells('A1:L1');
    $sheet->mergeCells('A2:L2');
    $sheet->mergeCells('A3:L3');
    $sheet->mergeCells('A4:L4');
    $sheet->setCellValue('A1','LAPORAN PERTANGGUNGJAWABAN MUTASI BARANG');
    $sheet->setCellValue('A2','LAPORAN PERTANGGUNGJAWABAN MUTASI BAHAN BAKU DAN BAHAN PENOLONG');
    $sheet->setCellValue('A3','KAWASAN BERIKAT '.$companyName);
    $sheet->setCellValue('A4','PERIODE: '.$tglAwal.' SD '.$tglAkhir);

    $headers = array(erp_export_label("No"),erp_export_label("KODE\\nBARANG"),erp_export_label("NAMA\\nBARANG"),erp_export_label("SAT"),erp_export_label("SALDO\\nAWAL\\n....(7)...."),erp_export_label("PEMASUKAN"),erp_export_label("PENGELUARAN"),erp_export_label("PENYESUAIAN\\n(ADJUSTMENT)"),erp_export_label("SALDO\\nAKHIR\\n....(12)...."),erp_export_label("STOCK\\nOPNAME\\n....(14)...."),erp_export_label("SELISIH"),erp_export_label("KETERANGAN"));
    $numbers = array('(3)','(4)','(5)','(6)','(8)','(9)','(10)','(11)','(13)','(15)','(16)','(17)');
    foreach ($headers as $c=>$h) $sheet->setCellValueByColumnAndRow($c,$headerStart,$h);
    foreach ($numbers as $c=>$h) $sheet->setCellValueByColumnAndRow($c,$headerEnd,$h);

    $r = $firstDataRow; $n = 1;
    $totals = array('saldo_awal'=>0,'pemasukan'=>0,'pengeluaran'=>0,'penyesuaian'=>0,'saldo_akhir'=>0,'stock_opname'=>0,'selisih'=>0);
    foreach ($rows as $row) {
      $saldoAkhir = (float)$row->saldo_akhir;
      $stockOpnameRow = mbb_action_stock_opname_row($db, $row->kd_barang, $tglAkhir, $row->plant_id, $row->storage_location_id, $row->storage_bin_id, $row->stock_type);
      $hasStockOpname = $stockOpnameRow && $stockOpnameRow->counted_qty !== null;
      $stockOpname = $hasStockOpname ? (float)$stockOpnameRow->counted_qty : null;
      $selisih = $hasStockOpname ? $stockOpname - $saldoAkhir : null;
      $keterangan = array();
      if ((int)$row->movement_lines > 0) $keterangan[] = (int)$row->movement_lines.' line transaksi';
      if ($hasStockOpname) $keterangan[] = $stockOpnameRow->doc_type.' '.$stockOpnameRow->doc_no.' tgl '.$stockOpnameRow->count_date;
      if ($row->plant_code || $row->storage_code || $row->bin_code) $keterangan[] = trim((string)$row->plant_code.' / '.(string)$row->storage_code.' / '.(string)$row->bin_code, ' /');
      if ($row->stock_type) $keterangan[] = $row->stock_type;
      $values = array($n++,$row->kd_barang,$row->nm_barang,$row->satuan,(float)$row->saldo_awal,(float)$row->pemasukan,(float)$row->pengeluaran,(float)$row->penyesuaian,$saldoAkhir,$stockOpname,$selisih,implode(' | ', $keterangan));
      foreach ($values as $c=>$v) $sheet->setCellValueByColumnAndRow($c,$r,$v);
      $totals['saldo_awal'] += (float)$row->saldo_awal;
      $totals['pemasukan'] += (float)$row->pemasukan;
      $totals['pengeluaran'] += (float)$row->pengeluaran;
      $totals['penyesuaian'] += (float)$row->penyesuaian;
      $totals['saldo_akhir'] += $saldoAkhir;
      if ($hasStockOpname) {
        $totals['stock_opname'] += $stockOpname;
        $totals['selisih'] += $selisih;
      }
      $r++;
    }
    $lastDataRow = max($firstDataRow, $r - 1);
    $summaryRow = $r + 1;
    $sheet->mergeCells('A'.$summaryRow.':D'.$summaryRow);
    $sheet->setCellValue('A'.$summaryRow, erp_export_label('TOTAL'));
    $sheet->setCellValue('E'.$summaryRow,$totals['saldo_awal']);
    $sheet->setCellValue('F'.$summaryRow,$totals['pemasukan']);
    $sheet->setCellValue('G'.$summaryRow,$totals['pengeluaran']);
    $sheet->setCellValue('H'.$summaryRow,$totals['penyesuaian']);
    $sheet->setCellValue('I'.$summaryRow,$totals['saldo_akhir']);
    $sheet->setCellValue('J'.$summaryRow,$totals['stock_opname']);
    $sheet->setCellValue('K'.$summaryRow,$totals['selisih']);

    $sheet->getStyle('A1:L4')->getFont()->setBold(true)->setSize(12);
    $sheet->getStyle('A1:L4')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('A'.$headerStart.':L'.$headerEnd)->getFont()->setBold(true);
    $sheet->getStyle('A'.$headerStart.':L'.$headerEnd)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)->setWrapText(true);
    $sheet->getStyle('A'.$headerStart.':L'.$headerStart)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('FFF7ED');
    $sheet->getStyle('A'.$headerEnd.':L'.$headerEnd)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('FFFFFF');
    $sheet->getStyle('A'.$headerStart.':L'.$lastDataRow)->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');
    $sheet->getStyle('A'.$firstDataRow.':L'.$lastDataRow)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_TOP)->setWrapText(true);
    $sheet->getStyle('A'.$firstDataRow.':A'.$lastDataRow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('E'.$firstDataRow.':K'.$lastDataRow)->getNumberFormat()->setFormatCode('#,##0.00');
    $sheet->getStyle('E'.$firstDataRow.':K'.$lastDataRow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
    $sheet->getStyle('A'.$summaryRow.':L'.$summaryRow)->getFont()->setBold(true);
    $sheet->getStyle('A'.$summaryRow.':L'.$summaryRow)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('ECFDF5');
    $sheet->getStyle('A'.$summaryRow.':L'.$summaryRow)->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');
    $sheet->getStyle('E'.$summaryRow.':K'.$summaryRow)->getNumberFormat()->setFormatCode('#,##0.00');
    $sheet->getStyle('E'.$summaryRow.':K'.$summaryRow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
    foreach (array('A'=>6,'B'=>14,'C'=>30,'D'=>8,'E'=>13,'F'=>14,'G'=>15,'H'=>18,'I'=>13,'J'=>14,'K'=>12,'L'=>34) as $col=>$width) $sheet->getColumnDimension($col)->setWidth($width);
    $sheet->getRowDimension($headerStart)->setRowHeight(42);
    $sheet->getRowDimension($headerEnd)->setRowHeight(22);
    $sheet->freezePane('A'.$firstDataRow);
    $sheet->setAutoFilter('A'.$headerEnd.':L'.$lastDataRow);
    $sheet->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
    $sheet->getPageSetup()->setFitToWidth(1);
    $sheet->getPageSetup()->setFitToHeight(0);
    $sheet->getPageMargins()->setTop(0.5)->setRight(0.35)->setLeft(0.35)->setBottom(0.5);

    $tmp = erpkb_excel_temp_file('mutasi_bahan_baku_');
    PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp);
    $size = @filesize($tmp); $signature = @file_get_contents($tmp,false,null,0,2);
    if (!$size || $signature !== 'PK') { @unlink($tmp); while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean(); header('Content-Type:text/plain; charset=utf-8'); echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.'); exit; }
    while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="laporan_mutasi_bahan_baku_'.$tglAwal.'_sd_'.$tglAkhir.'.xlsx"');
    header('Content-Length: '.$size);
    header('Cache-Control: max-age=0');
    header('Pragma: public');
    readfile($tmp); @unlink($tmp); exit;
   
 case "show_detail_pemasukan":

    $tgl_awal = isset($_POST['tgl_awal']) && $_POST['tgl_awal'] !== '' ? $_POST['tgl_awal'] : date('Y-m-01');
    $tgl_akhir = isset($_POST['tgl_akhir']) && $_POST['tgl_akhir'] !== '' ? $_POST['tgl_akhir'] : date('Y-m-d');
    $kd_barang = isset($_POST['kd_barang']) ? trim($_POST['kd_barang']) : '';
    $type = isset($_POST['type']) ? strtoupper(trim($_POST['type'])) : 'IN';
    if ($type === '' && isset($_POST['tabel'])) $type = $_POST['tabel'] === 'vmutasipemasukanbbdetails' ? 'IN' : 'OUT';

    $movementExpr = "CASE WHEN dt.direction='OUT' OR COALESCE(dt.qty,0)<0 OR dt.move_code IN ('102','122','201','221','261','262','551','601','602') THEN 'OUT' ELSE 'IN' END";
    $adjustExpr = "CASE WHEN dt.move_code IN ('701','702','711','712') OR COALESCE(dt.ref_type,'') LIKE '%DIFF%' OR COALESCE(dt.ref_type,'') LIKE '%OPNAME%' OR COALESCE(dt.ref_type,'') LIKE '%ADJUST%' THEN 1 ELSE 0 END";
    $where = " WHERE dt.document_date BETWEEN ? AND ? AND dt.kd_barang=? AND (
      dt.posisi='GUDANG'
      OR dt.lokasi LIKE '%GUDANG%'
      OR dt.lokasi LIKE '%WAREHOUSE%'
      OR dt.move_code IN ('201','221','261','262','551','601','602','701','702','711','712')
      OR COALESCE(dt.ref_type,'') IN ('ISSUE_PROD','ISSUE_PRODUCTION','GI_DELIVERY','MANUAL_ADJUSTMENT','PI_DIFF')
      OR COALESCE(dt.ref_type,'') LIKE '%DIFF%'
      OR COALESCE(dt.ref_type,'') LIKE '%ADJUST%'
      OR dt.posisi IS NULL
    ) AND COALESCE(dt.is_reversal,0)=0 ";
    $params = array($tgl_awal.' 00:00:00', $tgl_akhir.' 23:59:59', $kd_barang);
    if ($type === 'ADJ') {
      $where .= " AND ($adjustExpr)=1 ";
      $title = 'Detail Penyesuaian';
    } elseif ($type === 'OUT') {
      $where .= " AND ($adjustExpr)=0 AND ($movementExpr)='OUT' ";
      $title = 'Detail Pengeluaran';
    } else {
      $where .= " AND ($adjustExpr)=0 AND ($movementExpr)='IN' ";
      $title = 'Detail Pemasukan';
    }
    $q = $db->query("SELECT dt.*,b.nm_barang,b.satuan,ep.plant_code,es.storage_code,eb.bin_code,p.jenis_dokpab AS header_jenis_dokpab,p.tgl_dokpab
                     FROM detail_transaksi dt
                     LEFT JOIN barang b ON b.kd_barang=dt.kd_barang
                     LEFT JOIN erp_plant ep ON ep.id=dt.plant_id
                     LEFT JOIN erp_storage_location es ON es.id=COALESCE(dt.storage_location_id,dt.destination_storage_location_id)
                     LEFT JOIN erp_storage_bin eb ON eb.id=COALESCE(dt.storage_bin_id,dt.destination_storage_bin_id)
                     LEFT JOIN pemasukan p ON p.no_bpb=COALESCE(NULLIF(dt.no_bpb,''),NULLIF(dt.no_ref,''),NULLIF(dt.ref_pengganti,'')) OR p.no_aju=dt.no_aju
                     $where
                     ORDER BY dt.posting_date ASC,dt.id_detail ASC", $params);
    ?>
    <style>.mbb-detail-table th,.mbb-detail-table td{font-size:12px;vertical-align:middle!important}.mbb-detail-table th{background:#f8fafc}.mbb-detail-head{background:#f8fafc;border:1px solid #e5e7eb;border-radius:12px;padding:12px;margin-bottom:12px}</style>
    <div class="mbb-detail-head"><h4 style="margin:0"><?=mbb_action_h($title);?> - <?=mbb_action_h($kd_barang);?></h4><small>Periode <?=mbb_action_h($tgl_awal);?> s/d <?=mbb_action_h($tgl_akhir);?></small></div>
    <div class="table-responsive"><table class="table table-bordered table-condensed mbb-detail-table">
      <thead><tr><th><?=customs_h('no','No');?></th><th>Material Document</th><th>Posting Date</th><th>Movement</th><th><?=customs_h('material','Material');?></th><th>Location</th><th>Customs</th><th class="text-right">Qty</th><th>UOM</th><th><?=customs_h('remarks','Keterangan');?></th></tr></thead><tbody>
      <?php $no=1; $total=0; foreach ($q as $k) { $movement = ($k->direction === 'OUT' || (float)$k->qty < 0 || in_array($k->move_code, array('102','122','201','221','261','262','551','601','602'))) ? 'OUT' : 'IN'; $qty=abs((float)$k->qty); $total += $qty; $loc=trim((string)$k->plant_code.' / '.(string)$k->storage_code.' / '.(string)$k->bin_code, ' /'); $customs=trim((string)($k->header_jenis_dokpab ?: '').' '.(string)$k->no_dokpab.' / '.(string)$k->no_aju, ' /'); ?>
        <tr>
          <td><?=intval($no++);?></td>
          <td><strong><?=mbb_action_h($k->no_ref ?: $k->no_bpb);?></strong><br><small>#<?=intval($k->id_detail);?></small></td>
          <td><?=mbb_action_h($k->posting_date ?: $k->document_date);?></td>
          <td><span class="label label-<?=$movement==='OUT'?'danger':'success';?>"><?=mbb_action_h($movement);?></span><br><small><?=mbb_action_h($k->move_code.' '.$k->ref_type);?></small></td>
          <td><strong><?=mbb_action_h($k->kd_barang);?></strong><br><small><?=mbb_action_h($k->nm_barang);?></small></td>
          <td><?=mbb_action_h($loc ?: ($k->lokasi ?: '-'));?><br><small><?=mbb_action_h($k->stock_type ?: 'UNRESTRICTED');?></small></td>
          <td><?=mbb_action_h($customs ?: '-');?><br><small><?=mbb_action_h($k->no_bpb ?: '');?></small></td>
          <td class="text-right"><?=mbb_action_num($qty);?></td>
          <td><?=mbb_action_h($k->uom ?: $k->satuan);?></td>
          <td><?=mbb_action_h(trim((string)$k->remark.' '.(string)$k->reason));?></td>
        </tr>
      <?php } if ($no===1) { ?><tr><td colspan="10" class="text-center text-muted">Tidak ada detail transaksi.</td></tr><?php } ?>
      </tbody><tfoot><tr><th colspan="7" class="text-right">Total</th><th class="text-right"><?=mbb_action_num($total);?></th><th colspan="2"></th></tr></tfoot>
    </table></div>
    <?php
    break;

  case "in": 
    
  
  
  
  $data = array(
      "id" => $_POST["id"],
      "kd_barang" => $_POST["kd_barang"],
      "nm_barang" => $_POST["nm_barang"],
      "type" => $_POST["type"],
  );
  
  
  
   
    $in = $db->insert("mutasi_bahanbaku",$data);
    
    
    action_response($db->getErrorMessage());
    break;
  case "delete":
    
    
    
    $db->delete("mutasi_bahanbaku","id",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("mutasi_bahanbaku","id",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    
   $data = array(
      "id" => $_POST["id"],
      "kd_barang" => $_POST["kd_barang"],
      "nm_barang" => $_POST["nm_barang"],
      "type" => $_POST["type"],
   );
   
   
   

    
    
    $up = $db->update("mutasi_bahanbaku",$data,"id",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>
