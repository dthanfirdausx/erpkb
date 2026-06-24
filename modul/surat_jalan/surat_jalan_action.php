<?php
if (!function_exists('sd_t')) {
  function sd_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('sd_h')) {
  function sd_h($key, $fallback = '') { return htmlspecialchars((string) sd_t($key, $fallback), ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('sd_js')) {
  function sd_js($key, $fallback = '') { return json_encode(sd_t($key, $fallback), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); }
}
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
session_check_json();

function sj_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function sj_in($key, $default = '') {
    if (isset($_POST[$key])) return trim((string)$_POST[$key]);
    if (isset($_GET[$key])) return trim((string)$_GET[$key]);
    if (isset($_REQUEST[$key])) return trim((string)$_REQUEST[$key]);
    return $default;
}
function sj_date($value, $default) {
    $value = trim((string)$value);
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : $default;
}
function sj_num($value) { return number_format((float)$value, 4, ',', '.'); }
function sj_prop($row, $key, $default = '') {
    return isset($row->$key) ? $row->$key : $default;
}
function sj_int_or_null($value) {
    return trim((string)$value) === '' || (int)$value <= 0 ? null : (int)$value;
}
function sj_user() {
    if (!empty($_SESSION['username'])) return $_SESSION['username'];
    if (!empty($_SESSION['nama_lengkap'])) return $_SESSION['nama_lengkap'];
    return 'system';
}
function sj_next_no() {
    return "SJ-" . date('Ym') . "-" . str_pad(get_nomor('surat_jalan','id'), 4, '0', STR_PAD_LEFT);
}
function sj_filter_sql(&$params) {
    $tglAwal = sj_date(sj_in('tgl_awal'), date('Y-01-01'));
    $tglAkhir = sj_date(sj_in('tgl_akhir'), date('Y-m-d'));
    $customer = sj_in('customer', 'all');
    $status = sj_in('status', 'all');
    $keyword = sj_in('keyword');

    $params = array($tglAwal, $tglAkhir);
    $where = " WHERE sj.tgl_surat_jalan BETWEEN ? AND ? ";
    if ($customer !== '' && $customer !== 'all') { $where .= " AND sj.kode_penerima = ? "; $params[] = $customer; }
    if ($status !== '' && $status !== 'all') { $where .= " AND sj.status = ? "; $params[] = $status; }
    if ($keyword !== '') {
        $kw = "%".$keyword."%";
        $where .= " AND (
            sj.no_surat_jalan LIKE ? OR sj.packing_list_no LIKE ? OR sj.delivery_no LIKE ?
            OR sj.gi_no LIKE ? OR sj.no_sales_order LIKE ? OR sj.no_po LIKE ?
            OR sj.sopir LIKE ? OR sj.no_kendaraan LIKE ? OR sj.shipping_point LIKE ?
            OR sj.route LIKE ? OR sj.carrier LIKE ? OR p.nama LIKE ?
        ) ";
        for ($i = 0; $i < 12; $i++) $params[] = $kw;
    }
    return $where;
}
function sj_load_packing($db, $packingListId) {
    return $db->fetch(
        "SELECT pl.*,p.nama AS customer_name,p.alamat AS customer_address,
                od.id_sales_order,od.no_sales_order,od.ship_to_address,od.driver_name,
                od.vehicle_no AS delivery_vehicle_no,od.customer_name AS delivery_customer_name,
                od.shipping_point,od.route,od.carrier,od.status AS outbound_status,
                gi.id AS gi_id,gi.gi_no,gi.document_date AS gi_document_date,gi.posting_date AS gi_posting_date,
                gi.movement_type,gi.status AS gi_status
         FROM packing_list pl
         LEFT JOIN penerima p ON p.kode_penerima=pl.penerima
         LEFT JOIN erp_outbound_delivery od ON od.id=pl.delivery_id
         LEFT JOIN erp_goods_issue_delivery gi ON gi.delivery_id=od.id AND gi.status='POSTED'
         WHERE pl.id=? LIMIT 1",
        array((int)$packingListId)
    );
}
function sj_load_packing_details($db, $packing) {
    $rows = $db->query(
        "SELECT pld.*,pld.id AS packing_list_detail_id,
                odd.sales_order_detail_id,odd.material_code,odd.material_name AS od_material_name,
                odd.delivery_qty AS od_delivery_qty,odd.uom AS od_uom,odd.batch_no,
                gid.id AS gi_detail_id,gid.stock_type AS gi_stock_type,
                tr.plant_id,tr.storage_location_id,tr.storage_bin_id,tr.jenis_dokpab,tr.no_dokpab,tr.hs_code AS trace_hs_code,tr.lot_no AS trace_lot_no
         FROM packing_list_detail pld
         LEFT JOIN erp_outbound_delivery_detail odd ON odd.id=pld.delivery_detail_id
         LEFT JOIN erp_goods_issue_delivery_detail gid ON gid.delivery_detail_id=pld.delivery_detail_id AND gid.material_code=COALESCE(odd.material_code,pld.kode)
         LEFT JOIN (
             SELECT gi_detail_id,MAX(plant_id) plant_id,MAX(storage_location_id) storage_location_id,MAX(storage_bin_id) storage_bin_id,
                    MAX(jenis_dokpab) jenis_dokpab,MAX(no_dokpab) no_dokpab,MAX(hs_code) hs_code,MAX(lot_no) lot_no
             FROM erp_goods_issue_delivery_trace
             GROUP BY gi_detail_id
         ) tr ON tr.gi_detail_id=gid.id
         WHERE pld.packing_list_id=?
         ORDER BY pld.line_no,pld.row_no,pld.id",
        array((int)$packing->id)
    );
    $data = array();
    foreach ($rows as $row) $data[] = $row;
    if (!empty($data)) return $data;
    return $db->query(
        "SELECT pld.*,pld.id AS packing_list_detail_id,NULL AS sales_order_detail_id,NULL AS material_code,b.nm_barang AS od_material_name,
                pld.jumlah AS od_delivery_qty,pld.unit AS od_uom,NULL AS batch_no,NULL AS gi_detail_id,NULL AS gi_stock_type,
                NULL AS plant_id,NULL AS storage_location_id,NULL AS storage_bin_id,
                pld.jenis_dokpab,pld.no_dokpab,pld.hs_code AS trace_hs_code,pld.lot_no AS trace_lot_no
         FROM packing_list_detail pld
         LEFT JOIN barang b ON b.kd_barang=pld.kode
         WHERE pld.no_sj=?
         ORDER BY pld.row_no,pld.id",
        array($packing->no_sj)
    );
}
function sj_save_details_from_post($db, $suratJalanId) {
    if (empty($_POST['kode_barang']) || !is_array($_POST['kode_barang'])) return 0;
    $totalQty = 0;
    $no = 1;
    foreach ($_POST['kode_barang'] as $i => $kodeBarang) {
        $qtyKirim = isset($_POST['qty_kirim'][$i]) ? (float)str_replace(',', '.', $_POST['qty_kirim'][$i]) : 0;
        if ($qtyKirim <= 0) continue;
        $db->insert("surat_jalan_detail", array(
            'surat_jalan_id' => $suratJalanId,
            'line_no' => isset($_POST['line_no'][$i]) ? (int)$_POST['line_no'][$i] : ($no * 10),
            'packing_list_detail_id' => isset($_POST['packing_list_detail_id'][$i]) ? sj_int_or_null($_POST['packing_list_detail_id'][$i]) : null,
            'delivery_detail_id' => isset($_POST['delivery_detail_id'][$i]) ? sj_int_or_null($_POST['delivery_detail_id'][$i]) : null,
            'gi_detail_id' => isset($_POST['gi_detail_id'][$i]) ? sj_int_or_null($_POST['gi_detail_id'][$i]) : null,
            'id_sales_order_detail' => isset($_POST['id_detail'][$i]) ? sj_int_or_null($_POST['id_detail'][$i]) : null,
            'material_code' => isset($_POST['material_code'][$i]) ? $_POST['material_code'][$i] : $kodeBarang,
            'material_name' => isset($_POST['material_name'][$i]) ? $_POST['material_name'][$i] : (isset($_POST['nama_barang'][$i]) ? $_POST['nama_barang'][$i] : ''),
            'batch_no' => isset($_POST['batch_no'][$i]) ? $_POST['batch_no'][$i] : '',
            'lot_no' => isset($_POST['lot_no'][$i]) ? $_POST['lot_no'][$i] : '',
            'kode_barang' => $kodeBarang,
            'nama_barang' => isset($_POST['nama_barang'][$i]) ? $_POST['nama_barang'][$i] : '',
            'packing' => isset($_POST['packing'][$i]) ? $_POST['packing'][$i] : '',
            'satuan_packing' => isset($_POST['satuan_packing'][$i]) ? $_POST['satuan_packing'][$i] : '',
            'qty_order' => isset($_POST['qty_order'][$i]) ? (float)str_replace(',', '.', $_POST['qty_order'][$i]) : $qtyKirim,
            'qty_kirim' => $qtyKirim,
            'satuan' => isset($_POST['satuan'][$i]) ? $_POST['satuan'][$i] : '',
            'plant_id' => isset($_POST['plant_id'][$i]) ? sj_int_or_null($_POST['plant_id'][$i]) : null,
            'storage_location_id' => isset($_POST['storage_location_id'][$i]) ? sj_int_or_null($_POST['storage_location_id'][$i]) : null,
            'storage_bin_id' => isset($_POST['storage_bin_id'][$i]) ? sj_int_or_null($_POST['storage_bin_id'][$i]) : null,
            'stock_type' => isset($_POST['stock_type'][$i]) && $_POST['stock_type'][$i] !== '' ? $_POST['stock_type'][$i] : 'UNRESTRICTED',
            'bc_document_type' => isset($_POST['bc_document_type'][$i]) ? $_POST['bc_document_type'][$i] : '',
            'bc_document_no' => isset($_POST['bc_document_no'][$i]) ? $_POST['bc_document_no'][$i] : '',
            'bc_document_date' => isset($_POST['bc_document_date'][$i]) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['bc_document_date'][$i]) ? $_POST['bc_document_date'][$i] : null,
            'hs_code' => isset($_POST['hs_code'][$i]) ? $_POST['hs_code'][$i] : '',
            'net_weight' => isset($_POST['net_weight'][$i]) ? (float)str_replace(',', '.', $_POST['net_weight'][$i]) : 0,
            'gross_weight' => isset($_POST['gross_weight'][$i]) ? (float)str_replace(',', '.', $_POST['gross_weight'][$i]) : 0,
            'keterangan' => isset($_POST['keterangan_barang'][$i]) ? $_POST['keterangan_barang'][$i] : '',
            'row_no' => $no
        ));
        $totalQty += $qtyKirim;
        $no++;
    }
    return $totalQty;
}

switch ($_GET["act"]) {
    case "excel":
        $initialOutputBufferLevel = ob_get_level();
        ob_start();
        ini_set('display_errors', '0');
        error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
        require "../../inc/lib/PHPExcel.php";
        require_once "../../inc/excel_style_helper.php";
        PHPExcel_Shared_File::setUseUploadTempDirectory(true);

        $params = array();
        $where = sj_filter_sql($params);
        $tglAwal = sj_date(sj_in('tgl_awal'), date('Y-01-01'));
        $tglAkhir = sj_date(sj_in('tgl_akhir'), date('Y-m-d'));
        $customer = sj_in('customer', 'all');
        $status = sj_in('status', 'all');
        $keyword = sj_in('keyword');

        $rows = $db->query("
            SELECT sj.*,p.nama AS customer_name,p.alamat AS customer_address
            FROM surat_jalan sj
            LEFT JOIN penerima p ON p.kode_penerima = sj.kode_penerima
            $where
            ORDER BY sj.tgl_surat_jalan DESC, sj.no_surat_jalan DESC
        ", $params);

        $excel = new PHPExcel();
        $sheet = $excel->setActiveSheetIndex(0);
        $sheet->setTitle(erp_export_sheet_title('Surat Jalan'));
        $headers = array(
            erp_export_label("No"),erp_export_label("No Surat Jalan"),erp_export_label("Document Date"),erp_export_label("Posting Date"),erp_export_label("Tanggal SJ"),erp_export_label("Packing List"),erp_export_label("Outbound Delivery"),erp_export_label("Goods Issue"),
            erp_export_label("Movement Type"),erp_export_label("Sales Order"),erp_export_label("No PO"),erp_export_label("Invoice"),erp_export_label("Customer"),erp_export_label("Ship To Party"),erp_export_label("Alamat Kirim"),erp_export_label("Shipping Point"),
            erp_export_label("Route"),erp_export_label("Carrier"),erp_export_label("Sopir"),erp_export_label("No Kendaraan"),erp_export_label("Status"),erp_export_label("Delivery Status"),erp_export_label("Total Qty"),erp_export_label("Print Count"),
            erp_export_label("Last Printed At"),erp_export_label("Tanggal Kirim"),erp_export_label("Tanggal Terima"),erp_export_label("Penerima"),erp_export_label("Created By"),erp_export_label("Created Date"),erp_export_label("Keterangan")
        );
        foreach ($headers as $col => $header) $sheet->setCellValueByColumnAndRow($col, 4, $header);

        $rowNo = 5; $no = 1;
        foreach ($rows as $row) {
            $values = array(
                $no++,
                $row->no_surat_jalan,
                $row->document_date ?: $row->tgl_surat_jalan,
                $row->posting_date ?: $row->tgl_surat_jalan,
                $row->tgl_surat_jalan,
                $row->packing_list_no,
                $row->delivery_no,
                $row->gi_no,
                $row->movement_type ?: '601',
                $row->no_sales_order,
                $row->no_po,
                $row->no_invoice,
                $row->customer_name,
                $row->ship_to_party,
                $row->alamat_pengiriman ?: $row->customer_address,
                $row->shipping_point,
                $row->route,
                $row->carrier,
                $row->sopir,
                $row->no_kendaraan,
                strtoupper($row->status),
                $row->delivery_status,
                (float)$row->total_qty,
                (int)$row->print_count,
                $row->last_printed_at,
                $row->tgl_kirim,
                $row->tgl_terima,
                $row->nama_penerima,
                $row->created_by,
                $row->created_date,
                $row->keterangan
            );
            foreach ($values as $col => $value) $sheet->setCellValueByColumnAndRow($col, $rowNo, $value);
            $rowNo++;
        }

        erpkb_excel_apply_standard_style($excel, array(
            'sheet' => $sheet,
            'title' => erp_export_title('SURAT JALAN REPORT - SAP SD'),
            'header_row' => 4,
            'first_data_row' => 5,
            'last_data_row' => max(5, $rowNo - 1),
            'column_count' => 31,
            'numeric_columns' => array('W'),
            'filters' => array(
                'Periode' => $tglAwal.' s/d '.$tglAkhir,
                'Customer' => $customer !== 'all' ? $customer : erp_export_all_text(),
                'Status' => $status !== 'all' ? $status : erp_export_all_text(),
                'Keyword' => $keyword
            ),
            'widths' => array(
                'A'=>6,'B'=>20,'C'=>14,'D'=>14,'E'=>14,'F'=>22,'G'=>22,'H'=>18,'I'=>12,'J'=>20,
                'K'=>18,'L'=>18,'M'=>28,'N'=>16,'O'=>34,'P'=>18,'Q'=>18,'R'=>18,'S'=>18,'T'=>18,
                'U'=>14,'V'=>16,'W'=>14,'X'=>12,'Y'=>20,'Z'=>18,'AA'=>18,'AB'=>22,'AC'=>16,'AD'=>20,'AE'=>40
            )
        ));

        $tmp = erpkb_excel_temp_file('surat_jalan_');
        PHPExcel_IOFactory::createWriter($excel, 'Excel2007')->save($tmp);
        $size = @filesize($tmp);
        $signature = @file_get_contents($tmp, false, null, 0, 2);
        if (!$size || $signature !== 'PK') {
            @unlink($tmp);
            while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
            header('Content-Type:text/plain; charset=utf-8');
            echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.');
            exit;
        }
        while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="surat_jalan_'.$tglAwal.'_sd_'.$tglAkhir.'.xlsx"');
        header('Content-Length: '.$size);
        header('Cache-Control: max-age=0');
        header('Pragma: public');
        readfile($tmp);
        @unlink($tmp);
        exit;

    case "packing_search":
        $term = sj_in('term');
        $kw = '%'.$term.'%';
        $rows = $db->query(
             "SELECT pl.*,p.nama AS customer_name,p.alamat AS customer_address,od.no_sales_order,od.ship_to_address,od.driver_name,
                    od.vehicle_no AS od_vehicle_no,od.customer_name AS od_customer_name,
                    od.shipping_point,od.route,od.carrier,
                    gi.id AS gi_id,gi.gi_no,gi.document_date AS gi_document_date,gi.posting_date AS gi_posting_date,
                    gi.movement_type,gi.status AS gi_status,
                    COALESCE(SUM(pld.jumlah),0) total_qty
             FROM packing_list pl
             LEFT JOIN packing_list_detail pld ON (pld.packing_list_id=pl.id OR (pld.packing_list_id IS NULL AND pld.no_sj=pl.no_sj))
             LEFT JOIN penerima p ON p.kode_penerima=pl.penerima
             LEFT JOIN erp_outbound_delivery od ON od.id=pl.delivery_id
             LEFT JOIN erp_goods_issue_delivery gi ON gi.delivery_id=od.id AND gi.status='POSTED'
             WHERE pl.status='PACKED'
               AND NOT EXISTS (
                 SELECT 1 FROM surat_jalan sj
                 WHERE sj.status<>'dibatalkan'
                   AND (sj.packing_list_id=pl.id OR (pl.no_sj IS NOT NULL AND pl.no_sj<>'' AND sj.no_surat_jalan=pl.no_sj))
               )
               AND (?='' OR pl.no_packing_list LIKE ? OR pl.delivery_no LIKE ? OR pl.picking_no LIKE ? OR od.no_sales_order LIKE ? OR p.nama LIKE ? OR od.customer_name LIKE ?)
             GROUP BY pl.id
             HAVING total_qty>0
             ORDER BY pl.packed_at DESC,pl.id DESC
             LIMIT 30",
            array($term,$kw,$kw,$kw,$kw,$kw,$kw)
        );
        $results = array();
        foreach ($rows as $row) {
            $customerName = $row->customer_name ?: $row->od_customer_name;
            $results[] = array(
                'id' => $row->id,
                'text' => $row->no_packing_list.' - '.$customerName.' - Qty '.sj_num($row->total_qty),
                'no_packing_list' => $row->no_packing_list,
                'delivery_id' => $row->delivery_id,
                'delivery_no' => $row->delivery_no,
                'picking_no' => $row->picking_no,
                'gi_id' => $row->gi_id,
                'gi_no' => $row->gi_no,
                'movement_type' => $row->movement_type,
                'gi_status' => $row->gi_status,
                'posting_date' => $row->gi_posting_date,
                'shipping_point' => $row->shipping_point,
                'route' => $row->route,
                'carrier' => $row->carrier,
                'customer_code' => $row->penerima,
                'customer_name' => $customerName,
                'customer_address' => $row->customer_address,
                'no_sales_order' => $row->no_sales_order,
                'no_invoice' => $row->no_invoice,
                'no_po' => $row->no_po,
                'vehicle_no' => $row->vehicle_no ?: $row->od_vehicle_no,
                'driver_name' => $row->driver_name,
                'ship_to_address' => $row->ship_to_address,
                'remarks' => $row->remarks
            );
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array('results'=>$results));
        break;

    case "packing_items":
        $packing = sj_load_packing($db, (int)sj_in('packing_list_id'));
        if (!$packing) {
            echo '<tr><td colspan="10" class="text-center text-danger">Packing List tidak ditemukan.</td></tr>';
            exit;
        }
        $details = sj_load_packing_details($db, $packing);
        $no = 1; $total = 0;
        foreach ($details as $row) {
            $kode = sj_prop($row, 'material_code') ?: sj_prop($row, 'kode');
            $nama = sj_prop($row, 'od_material_name') ?: sj_prop($row, 'material_name');
            $qtyOrder = (float)(sj_prop($row, 'od_delivery_qty') ?: sj_prop($row, 'delivery_qty') ?: sj_prop($row, 'jumlah'));
            $qtyKirim = (float)$row->jumlah;
            if ($qtyKirim <= 0) continue;
            $unit = sj_prop($row, 'od_uom') ?: sj_prop($row, 'unit');
            $packingText = trim(sj_prop($row, 'packing'));
            $qtyPacking = trim((string)sj_prop($row, 'qty_packing'));
            $lineNo = (int)(sj_prop($row, 'line_no') ?: ($no * 10));
            $batchNo = sj_prop($row, 'batch_no');
            $lotNo = sj_prop($row, 'trace_lot_no') ?: sj_prop($row, 'lot_no');
            $stockType = sj_prop($row, 'gi_stock_type') ?: 'UNRESTRICTED';
            $bcType = sj_prop($row, 'jenis_dokpab');
            $bcNo = sj_prop($row, 'no_dokpab');
            $hsCode = sj_prop($row, 'trace_hs_code') ?: sj_prop($row, 'hs_code');
            $netWeight = (float)sj_prop($row, 'berat2', 0);
            $grossWeight = (float)sj_prop($row, 'bruto', 0);
            $total += $qtyKirim;
            ?>
            <tr>
                <td class="text-center"><?=intval($no);?></td>
                <td><strong><?=sj_h($kode);?></strong>
                    <input type="hidden" name="line_no[]" value="<?=intval($lineNo);?>">
                    <input type="hidden" name="packing_list_detail_id[]" value="<?=intval(sj_prop($row, 'packing_list_detail_id'));?>">
                    <input type="hidden" name="delivery_detail_id[]" value="<?=intval(sj_prop($row, 'delivery_detail_id'));?>">
                    <input type="hidden" name="gi_detail_id[]" value="<?=intval(sj_prop($row, 'gi_detail_id'));?>">
                    <input type="hidden" name="id_detail[]" value="<?=intval(sj_prop($row, 'sales_order_detail_id'));?>">
                    <input type="hidden" name="kode_barang[]" value="<?=sj_h($kode);?>">
                    <input type="hidden" name="material_code[]" value="<?=sj_h($kode);?>">
                    <input type="hidden" name="batch_no[]" value="<?=sj_h($batchNo);?>">
                    <input type="hidden" name="lot_no[]" value="<?=sj_h($lotNo);?>">
                    <input type="hidden" name="plant_id[]" value="<?=intval(sj_prop($row, 'plant_id'));?>">
                    <input type="hidden" name="storage_location_id[]" value="<?=intval(sj_prop($row, 'storage_location_id'));?>">
                    <input type="hidden" name="storage_bin_id[]" value="<?=intval(sj_prop($row, 'storage_bin_id'));?>">
                    <input type="hidden" name="stock_type[]" value="<?=sj_h($stockType);?>">
                    <input type="hidden" name="bc_document_type[]" value="<?=sj_h($bcType);?>">
                    <input type="hidden" name="bc_document_no[]" value="<?=sj_h($bcNo);?>">
                    <input type="hidden" name="bc_document_date[]" value="">
                    <input type="hidden" name="hs_code[]" value="<?=sj_h($hsCode);?>">
                    <input type="hidden" name="net_weight[]" value="<?=sj_h($netWeight);?>">
                    <input type="hidden" name="gross_weight[]" value="<?=sj_h($grossWeight);?>">
                </td>
                <td><?=sj_h($nama);?><input type="hidden" name="nama_barang[]" value="<?=sj_h($nama);?>"><input type="hidden" name="material_name[]" value="<?=sj_h($nama);?>"></td>
                <td class="text-right"><?=sj_num($qtyOrder);?><input type="hidden" name="qty_order[]" value="<?=sj_h($qtyOrder);?>"></td>
                <td><input type="text" name="packing[]" class="form-control input-sm" value="<?=sj_h($packingText);?>"></td>
                <td><input type="text" name="satuan_packing[]" class="form-control input-sm" value="<?=sj_h($qtyPacking);?>"></td>
                <td><input type="text" name="qty_kirim[]" class="form-control input-sm text-right sj-qty" value="<?=sj_h(number_format($qtyKirim,4,'.',''));?>" data-max="<?=sj_h($qtyKirim);?>"></td>
                <td><?=sj_h($unit);?><input type="hidden" name="satuan[]" value="<?=sj_h($unit);?>"></td>
                <td><small>Lot: <?=sj_h($lotNo ?: '-');?><br>BC: <?=sj_h(trim($bcType.' '.$bcNo) ?: '-');?><br>Stock: <?=sj_h($stockType ?: '-');?></small></td>
                <td><input type="text" name="keterangan_barang[]" class="form-control input-sm" value="<?=sj_h($row->remark);?>"></td>
            </tr>
            <?php
            $no++;
        }
        if ($total <= 0) echo '<tr><td colspan="10" class="text-center text-muted">Tidak ada item Packing List.</td></tr>';
        exit;

    case "in":
        $packingId = (int)sj_in('packing_list_id');
        $packing = sj_load_packing($db, $packingId);
        if (!$packing) { action_response('Packing List tidak ditemukan.'); break; }
        if ($packing->status !== 'PACKED') { action_response('Surat Jalan hanya bisa dibuat dari Packing List status PACKED.'); break; }
        $exists = $db->fetch("SELECT id FROM surat_jalan WHERE packing_list_id=? AND status<>'dibatalkan' LIMIT 1", array($packingId));
        if ($exists) { action_response('Packing List ini sudah dibuatkan Surat Jalan.'); break; }
        if (empty($_POST['kode_barang']) || !is_array($_POST['kode_barang'])) { action_response('Detail item Surat Jalan wajib diisi.'); break; }

        $noSuratJalan = sj_in('no_surat_jalan') ?: sj_next_no();
        $tglSuratJalan = sj_date(sj_in('tgl_surat_jalan'), date('Y-m-d'));
        $customerName = $packing->customer_name ?: $packing->delivery_customer_name;
        $postingDate = sj_date(sj_prop($packing, 'gi_posting_date'), $tglSuratJalan);
        $db->query('START TRANSACTION');
        $db->insert("surat_jalan", array(
            'no_surat_jalan' => $noSuratJalan,
            'packing_list_id' => $packing->id,
            'packing_list_no' => $packing->no_packing_list,
            'delivery_id' => $packing->delivery_id,
            'delivery_no' => $packing->delivery_no,
            'picking_no' => $packing->picking_no,
            'gi_id' => sj_prop($packing, 'gi_id') ? (int)sj_prop($packing, 'gi_id') : null,
            'gi_no' => sj_prop($packing, 'gi_no'),
            'movement_type' => sj_prop($packing, 'movement_type') ?: '601',
            'id_sales_order' => $packing->id_sales_order,
            'no_sales_order' => $packing->no_sales_order,
            'shipping_point' => sj_prop($packing, 'shipping_point'),
            'route' => sj_prop($packing, 'route'),
            'carrier' => sj_prop($packing, 'carrier'),
            'tgl_surat_jalan' => $tglSuratJalan,
            'document_date' => $tglSuratJalan,
            'posting_date' => $postingDate,
            'kode_penerima' => $packing->penerima,
            'sold_to_party' => $packing->penerima,
            'ship_to_party' => $packing->penerima,
            'bill_to_party' => $packing->penerima,
            'payer' => $packing->penerima,
            'no_invoice' => sj_in('no_invoice') ?: $packing->no_invoice,
            'no_po' => sj_in('no_po') ?: $packing->no_po,
            'alamat_pengiriman' => sj_in('alamat_pengiriman') ?: ($packing->customer_address ?: $packing->ship_to_address),
            'sopir' => sj_in('sopir') ?: $packing->driver_name,
            'attn' => sj_in('attn') ?: $customerName,
            'no_kendaraan' => sj_in('no_kendaraan') ?: ($packing->delivery_vehicle_no ?: $packing->vehicle_no),
            'no_polisi' => sj_in('no_kendaraan') ?: ($packing->delivery_vehicle_no ?: $packing->vehicle_no),
            'keterangan' => sj_in('keterangan'),
            'status' => 'draft',
            'delivery_status' => 'DRAFT',
            'nama_penerima' => $customerName,
            'created_by' => sj_user(),
            'created_date' => date('Y-m-d H:i:s')
        ));
        $suratJalanId = $db->last_insert_id();
        $totalQty = sj_save_details_from_post($db, $suratJalanId);
        if ($totalQty <= 0) {
            $db->query('ROLLBACK');
            action_response('Minimal satu qty kirim harus lebih dari nol.');
            break;
        }
        $db->update("surat_jalan", array('total_qty' => $totalQty), "id", $suratJalanId);
        $db->update("packing_list", array('no_sj' => $noSuratJalan), "id", $packingId);
        if (!empty($packing->delivery_id)) {
            $db->query("UPDATE erp_outbound_delivery SET reference_surat_jalan=?,updated_by=?,updated_at=? WHERE id=?", array($noSuratJalan,sj_user(),date('Y-m-d H:i:s'),(int)$packing->delivery_id));
        }
        if (function_exists('simpan_log')) simpan_log('User '.sj_user().' membuat Surat Jalan '.$noSuratJalan.' dari Packing List '.$packing->no_packing_list.' pada '.date('Y-m-d H:i:s'), sj_user());
        $err = $db->getErrorMessage();
        if ($err !== '') $db->query('ROLLBACK'); else $db->query('COMMIT');
        action_response($err);
        break;

    case "up":
        $id = (int)sj_in('id');
        $header = $db->fetch("SELECT * FROM surat_jalan WHERE id=? LIMIT 1", array($id));
        if (!$header) { action_response('Surat Jalan tidak ditemukan.'); break; }
        if ($header->status !== 'draft') { action_response('Surat Jalan hanya bisa diedit saat status DRAFT.'); break; }
        $db->query('START TRANSACTION');
        $db->update("surat_jalan", array(
            'tgl_surat_jalan' => sj_date(sj_in('tgl_surat_jalan'), $header->tgl_surat_jalan),
            'document_date' => sj_date(sj_in('tgl_surat_jalan'), $header->tgl_surat_jalan),
            'posting_date' => sj_date(sj_in('tgl_surat_jalan'), $header->tgl_surat_jalan),
            'no_invoice' => sj_in('no_invoice'),
            'no_po' => sj_in('no_po'),
            'alamat_pengiriman' => sj_in('alamat_pengiriman'),
            'sopir' => sj_in('sopir'),
            'attn' => sj_in('attn'),
            'no_kendaraan' => sj_in('no_kendaraan'),
            'no_polisi' => sj_in('no_kendaraan'),
            'keterangan' => sj_in('keterangan'),
            'updated_by' => sj_user(),
            'updated_date' => date('Y-m-d H:i:s')
        ), "id", $id);
        $db->query("DELETE FROM surat_jalan_detail WHERE surat_jalan_id=?", array($id));
        $totalQty = sj_save_details_from_post($db, $id);
        if ($totalQty <= 0) {
            $db->query('ROLLBACK');
            action_response('Minimal satu qty kirim harus lebih dari nol.');
            break;
        }
        $db->update("surat_jalan", array('total_qty' => $totalQty), "id", $id);
        if (function_exists('simpan_log')) simpan_log('User '.sj_user().' mengubah Surat Jalan '.$header->no_surat_jalan.' pada '.date('Y-m-d H:i:s'), sj_user());
        $err = $db->getErrorMessage();
        if ($err !== '') $db->query('ROLLBACK'); else $db->query('COMMIT');
        action_response($err);
        break;

    case "update_status":
        $id = (int)sj_in('id');
        $status = sj_in('status');
        if (!in_array($status, array('draft','dikirim','diterima','dibatalkan'))) {
            echo json_encode(array('status'=>'error','message'=>'Status tidak valid.'));
            break;
        }
        $dataUpdate = array(
            'status' => $status,
            'delivery_status' => strtoupper($status),
            'updated_by' => sj_user(),
            'updated_date' => date('Y-m-d H:i:s')
        );
        if ($status === 'dikirim') $dataUpdate['tgl_kirim'] = date('Y-m-d H:i:s');
        if ($status === 'diterima') {
            $dataUpdate['tgl_terima'] = date('Y-m-d H:i:s');
            $dataUpdate['nama_penerima'] = sj_in('nama_penerima');
            if (!empty($_FILES['tanda_tangan']['name'])) {
                $targetDir = "../../upload/tanda_tangan/";
                if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
                $fileName = time().'_'.basename($_FILES["tanda_tangan"]["name"]);
                if (move_uploaded_file($_FILES["tanda_tangan"]["tmp_name"], $targetDir.$fileName)) {
                    $dataUpdate['tanda_tangan_penerima'] = "upload/tanda_tangan/".$fileName;
                }
            }
        }
        if ($status === 'dibatalkan') {
            $dataUpdate['cancelled_by'] = sj_user();
            $dataUpdate['cancelled_at'] = date('Y-m-d H:i:s');
            $dataUpdate['cancel_reason'] = sj_in('cancel_reason');
        }
        $db->update("surat_jalan", $dataUpdate, "id", $id);
        echo json_encode(array('status'=>$db->getErrorMessage() === '' ? 'success' : 'error','message'=>$db->getErrorMessage()));
        break;

    case "show_detail":
        $id = (int)sj_in('id');
        $header = $db->fetch("SELECT sj.*,p.nama AS customer_name FROM surat_jalan sj LEFT JOIN penerima p ON p.kode_penerima=sj.kode_penerima WHERE sj.id=? LIMIT 1", array($id));
        if ($header) {
            echo '<div class="row">';
            echo '<div class="col-sm-3"><strong>'.sd_h('sales_surat_jalan', 'Surat Jalan').'</strong><br>'.sj_h($header->no_surat_jalan).'</div>';
            echo '<div class="col-sm-3"><strong>'.sd_h('sales_packing_list', 'Packing List').'</strong><br>'.sj_h($header->packing_list_no ?: '-').'</div>';
            echo '<div class="col-sm-3"><strong>'.sd_h('sales_outbound_delivery', 'Outbound Delivery').'</strong><br>'.sj_h($header->delivery_no ?: '-').'</div>';
            echo '<div class="col-sm-3"><strong>Goods Issue</strong><br>'.sj_h($header->gi_no ?: '-').'</div>';
            echo '</div><br><div class="row">';
            echo '<div class="col-sm-3"><strong>Document / Posting</strong><br>'.sj_h(($header->document_date ?: '-').' / '.($header->posting_date ?: '-')).'</div>';
            echo '<div class="col-sm-3"><strong>Movement Type</strong><br>'.sj_h($header->movement_type ?: '601').'</div>';
            echo '<div class="col-sm-3"><strong>'.sd_h('sales_customer', 'Customer').'</strong><br>'.sj_h($header->customer_name ?: $header->nama_penerima).'</div>';
            echo '<div class="col-sm-3"><strong>'.sd_h('common_print', 'Print').'</strong><br>'.sj_h((int)$header->print_count.'x').'</div>';
            echo '</div><br>';
        }
        ?>
        <table class="table table-bordered sj-detail-table">
            <thead>
                <tr>
                    <th>'.sd_h('common_no', 'No').'</th><th>Kode Barang</th><th>Nama Barang</th><th>Qty Delivery</th>
                    <th>Qty Kirim</th><th>Satuan</th><th>Packing</th><th>Lot/Batch</th><th>BC</th><th>Storage</th><th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $rows = $db->query("SELECT * FROM surat_jalan_detail WHERE surat_jalan_id=? ORDER BY row_no", array($id));
                $no = 1; $totalOrder = 0; $totalKirim = 0;
                foreach ($rows as $row) {
                    $totalOrder += (float)$row->qty_order;
                    $totalKirim += (float)$row->qty_kirim;
                    echo "<tr>
                        <td class='text-center'>".$no."</td>
                        <td>".sj_h($row->kode_barang)."</td>
                        <td>".sj_h($row->nama_barang)."</td>
                        <td class='text-right'>".sj_num($row->qty_order)."</td>
                        <td class='text-right'>".sj_num($row->qty_kirim)."</td>
                        <td>".sj_h($row->satuan)."</td>
                        <td>".sj_h(trim($row->packing.' '.$row->satuan_packing))."</td>
                        <td>".sj_h(trim($row->batch_no.' '.$row->lot_no) ?: '-')."</td>
                        <td>".sj_h(trim($row->bc_document_type.' '.$row->bc_document_no) ?: '-')."</td>
                        <td>".sj_h(trim($row->plant_id.'/'.$row->storage_location_id.'/'.$row->storage_bin_id, '/') ?: '-')."</td>
                        <td>".sj_h($row->keterangan)."</td>
                    </tr>";
                    $no++;
                }
                ?>
                <tr>
                    <td colspan="3" class="text-center"><strong>TOTAL</strong></td>
                    <td class="text-right"><strong><?=sj_num($totalOrder);?></strong></td>
                    <td class="text-right"><strong><?=sj_num($totalKirim);?></strong></td>
                    <td colspan="6"></td>
                </tr>
            </tbody>
        </table>
        <?php
        break;

    case "delete":
        $id = (int)$_GET["id"];
        $header = $db->fetch("SELECT * FROM surat_jalan WHERE id=? LIMIT 1", array($id));
        if ($header && $header->status !== 'draft') { action_response('Hanya Surat Jalan status DRAFT yang bisa dihapus.'); break; }
        $db->delete("surat_jalan_detail", "surat_jalan_id", $id);
        $db->delete("surat_jalan", "id", $id);
        action_response($db->getErrorMessage());
        break;

    case "del_massal":
        $ids = explode(",", sj_in('data_ids'));
        foreach ($ids as $id) {
            $id = (int)$id;
            $header = $db->fetch("SELECT status FROM surat_jalan WHERE id=? LIMIT 1", array($id));
            if ($header && $header->status === 'draft') {
                $db->delete("surat_jalan_detail", "surat_jalan_id", $id);
                $db->delete("surat_jalan", "id", $id);
            }
        }
        action_response($db->getErrorMessage());
        break;

    default:
        break;
}
?>
