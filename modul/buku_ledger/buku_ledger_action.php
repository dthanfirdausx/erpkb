<?php
if (!function_exists('fin_t')) {
  function fin_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('fin_h')) {
  function fin_h($key, $fallback = '') { return htmlspecialchars((string) fin_t($key, $fallback), ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('fin_js')) {
  function fin_js($key, $fallback = '') { return json_encode(fin_t($key, $fallback), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); }
}
$initialOutputBufferLevel = ob_get_level();
ob_start();

session_start();
include "../../inc/config.php";
session_check_json();

function bl_json($status, $message = '', $extra = array())
{
    header('Content-Type: application/json');
    echo json_encode(array_merge(array('status' => $status, 'message' => $message), $extra));
    exit;
}

function bl_h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function bl_num($value)
{
    return number_format((float) $value, 2, '.', ',');
}

function bl_valid_date($date)
{
    $dt = DateTime::createFromFormat('Y-m-d', $date);
    return $dt && $dt->format('Y-m-d') === $date;
}

function bl_account($db, $noRek)
{
    return $db->fetch(
        "SELECT r.no_rek,r.nama_rek,k.kategori_akun,k.kategori,k.saldo_normal
         FROM rekening r
         LEFT JOIN coa_kategori k ON k.id=r.kat_coa
         WHERE r.no_rek=? LIMIT 1",
        array($noRek)
    );
}

function bl_opening_balance($db, $noRek, $startDate, $saldoNormal)
{
    $year = (int) date('Y', strtotime($startDate));
    $opening = $db->fetch(
        "SELECT COALESCE(SUM(debet),0) debet, COALESCE(SUM(kredit),0) kredit
         FROM saldo_awal
         WHERE no_rek=? AND periode=?",
        array($noRek, $year)
    );
    $before = $db->fetch(
        "SELECT COALESCE(SUM(d.debet),0) debet, COALESCE(SUM(d.kredit),0) kredit
         FROM jurnal_detail d
         INNER JOIN jurnal_header h ON h.id=d.id_header
         WHERE d.no_rek=?
           AND h.tgl_jurnal < ?
           AND h.posting_status IN ('POSTED','REVERSED')",
        array($noRek, $startDate)
    );

    $debet = (float) ($opening ? $opening->debet : 0) + (float) ($before ? $before->debet : 0);
    $kredit = (float) ($opening ? $opening->kredit : 0) + (float) ($before ? $before->kredit : 0);
    return strtolower((string) $saldoNormal) === 'kredit' ? ($kredit - $debet) : ($debet - $kredit);
}

function bl_rows($db, $noRek, $startDate, $endDate, $status, $docType, $source)
{
    $params = array($noRek, $startDate, $endDate);
    $where = "d.no_rek=? AND h.tgl_jurnal BETWEEN ? AND ?";

    if ($status !== '') {
        $where .= " AND h.posting_status=?";
        $params[] = $status;
    } else {
        $where .= " AND h.posting_status IN ('POSTED','REVERSED')";
    }
    if ($docType !== '') {
        $where .= " AND h.document_type=?";
        $params[] = $docType;
    }
    if ($source !== '') {
        $where .= " AND h.source_module LIKE ?";
        $params[] = '%'.$source.'%';
    }

    return $db->query(
        "SELECT h.id header_id,h.no_jurnal,h.document_type,h.posting_status,h.tgl_jurnal,h.no_bukti,h.ket,
                h.source_module,h.source_document_no,d.line_no,d.line_text,d.no_rek,d.debet,d.kredit,d.valuta,d.kurs,
                r.nama_rek,cc.cost_center_code,pc.profit_center_code,tc.tax_code
         FROM jurnal_detail d
         INNER JOIN jurnal_header h ON h.id=d.id_header
         LEFT JOIN rekening r ON r.no_rek=d.no_rek
         LEFT JOIN erp_cost_center cc ON cc.id=d.cost_center_id
         LEFT JOIN erp_profit_center pc ON pc.id=d.profit_center_id
         LEFT JOIN erp_tax_code tc ON tc.id=d.tax_code_id
         WHERE $where
         ORDER BY h.tgl_jurnal ASC,h.id ASC,d.line_no ASC,d.id ASC",
        $params
    );
}

function bl_validate($db)
{
    $startDate = isset($_REQUEST['start_date']) ? trim($_REQUEST['start_date']) : date('Y-m-01');
    $endDate = isset($_REQUEST['end_date']) ? trim($_REQUEST['end_date']) : date('Y-m-d');
    $noRek = isset($_REQUEST['no_rek']) ? trim($_REQUEST['no_rek']) : '';
    $status = isset($_REQUEST['posting_status']) ? strtoupper(trim($_REQUEST['posting_status'])) : '';
    $docType = isset($_REQUEST['document_type']) ? strtoupper(trim($_REQUEST['document_type'])) : '';
    $source = isset($_REQUEST['source_module']) ? trim($_REQUEST['source_module']) : '';

    if (!bl_valid_date($startDate) || !bl_valid_date($endDate)) {
        throw new Exception('Periode tanggal tidak valid.');
    }
    if ($startDate > $endDate) {
        throw new Exception('Tanggal awal tidak boleh lebih besar dari tanggal akhir.');
    }
    if ($noRek === '') {
        throw new Exception('COA wajib dipilih.');
    }
    $account = bl_account($db, $noRek);
    if (!$account) {
        throw new Exception('COA tidak ditemukan.');
    }
    if ($status !== '' && !in_array($status, array('DRAFT','POSTED','REVERSED'))) {
        throw new Exception('Status jurnal tidak valid.');
    }

    return array($startDate, $endDate, $noRek, $status, $docType, $source, $account);
}

$act = isset($_GET['act']) ? $_GET['act'] : '';

try {
    switch ($act) {
        case 'filter':
            list($startDate, $endDate, $noRek, $status, $docType, $source, $account) = bl_validate($db);
            $saldo = bl_opening_balance($db, $noRek, $startDate, $account->saldo_normal);
            $rows = bl_rows($db, $noRek, $startDate, $endDate, $status, $docType, $source);
            $html = '<tr class="info"><td colspan="12"><strong>Opening Balance per '.bl_h($startDate).'</strong></td><td class="text-right"><strong>'.bl_num($saldo).'</strong></td></tr>';
            $no = 1;
            $totalDebet = 0;
            $totalKredit = 0;
            foreach ($rows as $row) {
                $totalDebet += (float) $row->debet;
                $totalKredit += (float) $row->kredit;
                $movement = strtolower((string) $account->saldo_normal) === 'kredit'
                    ? ((float) $row->kredit - (float) $row->debet)
                    : ((float) $row->debet - (float) $row->kredit);
                $saldo += $movement;
                $statusClass = $row->posting_status === 'POSTED' ? 'success' : ($row->posting_status === 'REVERSED' ? 'danger' : 'warning');
                $html .= '<tr>'.
                    '<td>'.($no++).'</td>'.
                    '<td>'.bl_h($row->tgl_jurnal).'</td>'.
                    '<td><a href="javascript:void(0)" class="ledger-detail" data-id="'.(int)$row->header_id.'">'.bl_h($row->no_jurnal).'</a></td>'.
                    '<td><span class="label label-'.$statusClass.'">'.bl_h($row->posting_status).'</span></td>'.
                    '<td>'.bl_h($row->document_type).'</td>'.
                    '<td>'.bl_h($row->no_bukti).'</td>'.
                    '<td>'.bl_h($row->ket).'<br><small class="text-muted">'.bl_h($row->line_text).'</small></td>'.
                    '<td>'.bl_h($row->source_module).'<br><small>'.bl_h($row->source_document_no).'</small></td>'.
                    '<td>'.bl_h($row->cost_center_code).'</td>'.
                    '<td>'.bl_h($row->profit_center_code).'</td>'.
                    '<td class="text-right">'.bl_num($row->debet).'</td>'.
                    '<td class="text-right">'.bl_num($row->kredit).'</td>'.
                    '<td class="text-right"><strong>'.bl_num($saldo).'</strong></td>'.
                    '</tr>';
            }
            if ($no === 1) {
                $html .= '<tr><td colspan="13" class="text-center text-muted">Tidak ada transaksi pada filter ini.</td></tr>';
            }
            $html .= '<tr class="active"><th colspan="10" class="text-right">TOTAL MUTASI</th><th class="text-right">'.bl_num($totalDebet).'</th><th class="text-right">'.bl_num($totalKredit).'</th><th class="text-right">'.bl_num($saldo).'</th></tr>';
            bl_json('success', 'OK', array(
                'html' => $html,
                'account' => array('no_rek'=>$account->no_rek,'nama_rek'=>$account->nama_rek,'saldo_normal'=>$account->saldo_normal),
                'opening_balance' => bl_num(bl_opening_balance($db, $noRek, $startDate, $account->saldo_normal)),
                'ending_balance' => bl_num($saldo),
                'total_debet' => bl_num($totalDebet),
                'total_kredit' => bl_num($totalKredit)
            ));
            break;

        case 'excel':
            list($startDate, $endDate, $noRek, $status, $docType, $source, $account) = bl_validate($db);
            require '../../inc/lib/PHPExcel.php';
            require_once '../../inc/excel_style_helper.php';
            $excel = new PHPExcel();
            $sheet = $excel->setActiveSheetIndex(0);
            $sheet->setTitle(erp_export_sheet_title('Buku Besar'));
            $headers = array(erp_export_label("No"),erp_export_label("Tanggal"),erp_export_label("No Jurnal"),erp_export_label("Status"),erp_export_label("Doc Type"),erp_export_label("No Bukti"),erp_export_label("Header Text"),erp_export_label("Line Text"),erp_export_label("Source Module"),erp_export_label("Source Doc"),erp_export_label("Cost Center"),erp_export_label("Profit Center"),erp_export_label("Debit"),erp_export_label("Credit"),erp_export_label("Saldo"));
            foreach ($headers as $i => $label) $sheet->setCellValueByColumnAndRow($i, 4, $label);
            $saldo = bl_opening_balance($db, $noRek, $startDate, $account->saldo_normal);
            $rowNo = 5;
            $sheet->setCellValue('A'.$rowNo, 'Opening Balance');
            $sheet->mergeCells('A'.$rowNo.':N'.$rowNo);
            $sheet->setCellValue('O'.$rowNo, $saldo);
            $rowNo++;
            $no = 1;
            $totalDebet = 0;
            $totalKredit = 0;
            $rows = bl_rows($db, $noRek, $startDate, $endDate, $status, $docType, $source);
            foreach ($rows as $r) {
                $totalDebet += (float) $r->debet;
                $totalKredit += (float) $r->kredit;
                $movement = strtolower((string) $account->saldo_normal) === 'kredit' ? ((float)$r->kredit - (float)$r->debet) : ((float)$r->debet - (float)$r->kredit);
                $saldo += $movement;
                $values = array($no++,$r->tgl_jurnal,$r->no_jurnal,$r->posting_status,$r->document_type,$r->no_bukti,$r->ket,$r->line_text,$r->source_module,$r->source_document_no,$r->cost_center_code,$r->profit_center_code,$r->debet,$r->kredit,$saldo);
                foreach ($values as $i => $value) $sheet->setCellValueByColumnAndRow($i, $rowNo, $value);
                $rowNo++;
            }
            $sheet->setCellValue('L'.$rowNo, 'TOTAL MUTASI');
            $sheet->setCellValue('M'.$rowNo, $totalDebet);
            $sheet->setCellValue('N'.$rowNo, $totalKredit);
            $sheet->setCellValue('O'.$rowNo, $saldo);
            erpkb_excel_apply_standard_style($excel, array(
                'sheet'=>$sheet,
                'title'=>erp_export_title('LAPORAN BUKU BESAR'),
                'header_row'=>4,
                'first_data_row'=>5,
                'last_data_row'=>$rowNo,
                'column_count'=>count($headers),
                'money_columns'=>array('M','N','O'),
                'filters'=>array('Periode'=>$startDate.' s/d '.$endDate,'COA'=>$account->no_rek.' - '.$account->nama_rek,'Status'=>$status ?: 'POSTED/REVERSED','Doc Type'=>$docType,'Source'=>$source)
            ));
            while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="buku_besar_'.$noRek.'_'.date('YmdHis').'.xlsx"');
            header('Cache-Control: max-age=0');
            PHPExcel_IOFactory::createWriter($excel, 'Excel2007')->save('php://output');
            exit;

        default:
            bl_json('error', 'Action tidak dikenal.');
    }
} catch (Exception $e) {
    bl_json('error', $e->getMessage());
}
?>
