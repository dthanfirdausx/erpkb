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

function lr_json($status, $message = '', $extra = array())
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(array('status'=>$status,'message'=>$message), $extra));
    exit;
}

function lr_h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function lr_num($value)
{
    return number_format((float) $value, 2, '.', ',');
}

function lr_valid_date($date)
{
    $dt = DateTime::createFromFormat('Y-m-d', $date);
    return $dt && $dt->format('Y-m-d') === $date;
}

function lr_req($key, $default = '')
{
    return isset($_REQUEST[$key]) ? trim((string) $_REQUEST[$key]) : $default;
}

function lr_params()
{
    $start = lr_req('start_date', date('Y-m-01'));
    $end = lr_req('end_date', date('Y-m-d'));
    $docType = strtoupper(lr_req('document_type'));
    $source = lr_req('source_module');
    $costCenter = lr_req('cost_center');
    $profitCenter = lr_req('profit_center');

    if (!lr_valid_date($start) || !lr_valid_date($end) || $start > $end) {
        throw new Exception('Periode tanggal tidak valid.');
    }
    if ($costCenter !== '' && !ctype_digit($costCenter)) {
        throw new Exception('Cost center tidak valid.');
    }
    if ($profitCenter !== '' && !ctype_digit($profitCenter)) {
        throw new Exception('Profit center tidak valid.');
    }
    return array(
        'start_date'=>$start,
        'end_date'=>$end,
        'document_type'=>$docType,
        'source_module'=>$source,
        'cost_center'=>$costCenter,
        'profit_center'=>$profitCenter
    );
}

function lr_filter_where($filters, &$params)
{
    $params = array($filters['start_date'], $filters['end_date']);
    $where = "h.tgl_jurnal BETWEEN ? AND ? AND h.posting_status='POSTED'";
    if ($filters['document_type'] !== '') {
        $where .= " AND h.document_type=?";
        $params[] = $filters['document_type'];
    }
    if ($filters['source_module'] !== '') {
        $where .= " AND h.source_module LIKE ?";
        $params[] = '%'.$filters['source_module'].'%';
    }
    if ($filters['cost_center'] !== '') {
        $where .= " AND d.cost_center_id=?";
        $params[] = (int) $filters['cost_center'];
    }
    if ($filters['profit_center'] !== '') {
        $where .= " AND d.profit_center_id=?";
        $params[] = (int) $filters['profit_center'];
    }
    return $where;
}

function lr_section_key($kategoriAkun, $kategori)
{
    $kategoriAkun = strtolower(trim((string) $kategoriAkun));
    $kategori = strtolower(trim((string) $kategori));
    if ($kategoriAkun === 'pendapatan') {
        return strpos($kategori, 'lain') !== false ? 'pendapatan_lain' : 'pendapatan_usaha';
    }
    if (strpos($kategori, 'pokok') !== false || strpos($kategori, 'persediaan') !== false) {
        return 'hpp';
    }
    return strpos($kategori, 'lain') !== false ? 'beban_lain' : 'beban_usaha';
}

function lr_empty_sections()
{
    return array(
        'pendapatan_usaha'=>array('title'=>'PENDAPATAN', 'categories'=>array(), 'total'=>0),
        'hpp'=>array('title'=>'BEBAN POKOK / PERSEDIAAN', 'categories'=>array(), 'total'=>0),
        'beban_usaha'=>array('title'=>'BEBAN OPERASIONAL', 'categories'=>array(), 'total'=>0),
        'pendapatan_lain'=>array('title'=>'PENDAPATAN LAIN-LAIN', 'categories'=>array(), 'total'=>0),
        'beban_lain'=>array('title'=>'BEBAN LAIN-LAIN', 'categories'=>array(), 'total'=>0)
    );
}

function lr_data($db, $filters)
{
    $sections = lr_empty_sections();
    $params = array();
    $where = lr_filter_where($filters, $params);
    $params[] = 'pendapatan';
    $params[] = 'beban';

    $query = $db->query(
        "SELECT
            k.id kategori_id,
            k.kategori_akun,
            k.kategori,
            k.saldo_normal,
            r.no_rek,
            r.nama_rek,
            r.level,
            r.induk,
            SUM(COALESCE(d.debet,0)) total_debet,
            SUM(COALESCE(d.kredit,0)) total_kredit,
            CASE
              WHEN k.kategori_akun='pendapatan' THEN SUM(COALESCE(d.kredit,0))-SUM(COALESCE(d.debet,0))
              ELSE SUM(COALESCE(d.debet,0))-SUM(COALESCE(d.kredit,0))
            END saldo
         FROM jurnal_detail d
         INNER JOIN jurnal_header h ON h.id=d.id_header
         INNER JOIN rekening r ON r.no_rek=d.no_rek
         INNER JOIN coa_kategori k ON k.id=r.kat_coa
         WHERE $where
           AND k.kategori_akun IN (?,?)
         GROUP BY k.id,k.kategori_akun,k.kategori,k.saldo_normal,r.no_rek,r.nama_rek,r.level,r.induk
         HAVING ABS(saldo) >= 0.005 OR ABS(total_debet) >= 0.005 OR ABS(total_kredit) >= 0.005
         ORDER BY k.id,LENGTH(r.no_rek),r.no_rek",
        $params
    );

    if ($query === false) {
        throw new Exception('Query laporan gagal: '.$db->getErrorMessage());
    }

    foreach ($query as $row) {
        $sectionKey = lr_section_key($row->kategori_akun, $row->kategori);
        $categoryKey = (string) $row->kategori_id;
        if (!isset($sections[$sectionKey]['categories'][$categoryKey])) {
            $sections[$sectionKey]['categories'][$categoryKey] = array(
                'label'=>$row->kategori,
                'kategori_akun'=>$row->kategori_akun,
                'saldo_normal'=>$row->saldo_normal,
                'rows'=>array(),
                'total'=>0
            );
        }
        $sections[$sectionKey]['categories'][$categoryKey]['rows'][] = $row;
        $sections[$sectionKey]['categories'][$categoryKey]['total'] += (float) $row->saldo;
        $sections[$sectionKey]['total'] += (float) $row->saldo;
    }
    return $sections;
}

function lr_summary($sections)
{
    $pendapatan = (float) $sections['pendapatan_usaha']['total'];
    $hpp = (float) $sections['hpp']['total'];
    $gross = $pendapatan - $hpp;
    $opex = (float) $sections['beban_usaha']['total'];
    $operating = $gross - $opex;
    $otherIncome = (float) $sections['pendapatan_lain']['total'];
    $otherExpense = (float) $sections['beban_lain']['total'];
    $beforeTax = $operating + $otherIncome - $otherExpense;
    $net = $beforeTax;
    return compact('pendapatan','hpp','gross','opex','operating','otherIncome','otherExpense','beforeTax','net');
}

function lr_has_rows($sections)
{
    foreach ($sections as $section) {
        if (count($section['categories'])) return true;
    }
    return false;
}

function lr_validation($db, $filters)
{
    $params = array();
    $where = lr_filter_where($filters, $params);
    $row = $db->fetch(
        "SELECT COUNT(*) journal_count,
                COALESCE(SUM(x.debet),0) total_debet,
                COALESCE(SUM(x.kredit),0) total_kredit,
                COALESCE(SUM(CASE WHEN ABS(x.debet-x.kredit)>0.01 THEN 1 ELSE 0 END),0) unbalanced_count
         FROM (
           SELECT h.id,SUM(COALESCE(d.debet,0)) debet,SUM(COALESCE(d.kredit,0)) kredit
           FROM jurnal_header h
           INNER JOIN jurnal_detail d ON d.id_header=h.id
           WHERE $where
           GROUP BY h.id
         ) x",
        $params
    );
    if ($row === false) {
        throw new Exception('Validasi balance gagal: '.$db->getErrorMessage());
    }
    $diff = (float) $row->total_debet - (float) $row->total_kredit;
    return array(
        'journal_count'=>(int) $row->journal_count,
        'total_debet'=>(float) $row->total_debet,
        'total_kredit'=>(float) $row->total_kredit,
        'difference'=>$diff,
        'unbalanced_count'=>(int) $row->unbalanced_count,
        'balanced'=>abs($diff) <= 0.01 && (int) $row->unbalanced_count === 0
    );
}

function lr_badges_html($validation, $hasRows)
{
    if (!$hasRows) {
        return '<div class="alert alert-warning"><i class="fa fa-info-circle"></i> Tidak ada transaksi pendapatan atau beban untuk periode/filter ini.</div>';
    }
    $class = $validation['balanced'] ? 'alert-success' : 'alert-danger';
    $label = $validation['balanced'] ? 'Balance' : 'Not Balance';
    return '<div class="alert '.$class.'"><strong>'.$label.'</strong> Jurnal POSTED terfilter: '.
        lr_h($validation['journal_count']).'. Total debit '.lr_num($validation['total_debet']).
        ', kredit '.lr_num($validation['total_kredit']).', selisih '.lr_num($validation['difference']).
        ($validation['unbalanced_count'] ? '. Jurnal tidak balance: '.lr_h($validation['unbalanced_count']).'.' : '.').
        '</div>';
}

function lr_account_rows_html($category)
{
    if (!count($category['rows'])) {
        return '<tr><td colspan="5" class="text-muted"><em>Tidak ada transaksi</em></td></tr>';
    }
    $html = '';
    foreach ($category['rows'] as $row) {
        $level = max(0, min(6, (int) $row->level));
        $html .= '<tr>'.
            '<td width="14%" class="lr-account-code">'.lr_h($row->no_rek).'</td>'.
            '<td class="lr-account-name lr-level-'.$level.'">'.lr_h($row->nama_rek).'</td>'.
            '<td width="16%" class="text-right">'.lr_num($row->total_debet).'</td>'.
            '<td width="16%" class="text-right">'.lr_num($row->total_kredit).'</td>'.
            '<td width="18%" class="text-right">'.lr_num($row->saldo).'</td>'.
            '</tr>';
    }
    return $html;
}

function lr_section_html($section)
{
    $html = '<tr class="lr-section"><th colspan="5">'.lr_h($section['title']).'</th></tr>';
    if (!count($section['categories'])) {
        $html .= '<tr><td colspan="5" class="text-muted"><em>Tidak ada transaksi</em></td></tr>';
    }
    foreach ($section['categories'] as $category) {
        $html .= '<tr class="lr-category"><th colspan="4">'.lr_h($category['label']).' <small>('.lr_h($category['kategori_akun']).' / normal '.lr_h($category['saldo_normal']).')</small></th><th class="text-right">'.lr_num($category['total']).'</th></tr>';
        $html .= lr_account_rows_html($category);
    }
    $html .= '<tr class="active lr-subtotal"><th colspan="4">TOTAL '.lr_h($section['title']).'</th><th class="text-right">'.lr_num($section['total']).'</th></tr>';
    return $html;
}

function lr_total_row($label, $value, $class)
{
    return '<tr class="'.$class.' lr-result"><th colspan="4">'.lr_h($label).'</th><th class="text-right">'.lr_num($value).'</th></tr>';
}

function lr_html($sections, $filters, $validation)
{
    $s = lr_summary($sections);
    $html = lr_badges_html($validation, lr_has_rows($sections));
    $html .= '<div class="table-responsive"><table class="table table-bordered table-condensed lr-table">';
    $html .= '<thead><tr class="bg-primary"><th>'.fin_h('finance_coa', 'COA').'</th><th>Nama Akun</th><th class="text-right">'.fin_h('finance_debit', 'Debit').'</th><th class="text-right">'.fin_h('finance_credit', 'Credit').'</th><th class="text-right">Nilai L/R</th></tr></thead><tbody>';
    $html .= lr_section_html($sections['pendapatan_usaha']);
    $html .= lr_section_html($sections['hpp']);
    $html .= lr_total_row('LABA KOTOR', $s['gross'], $s['gross'] >= 0 ? 'success' : 'danger');
    $html .= lr_section_html($sections['beban_usaha']);
    $html .= lr_total_row('LABA OPERASI', $s['operating'], $s['operating'] >= 0 ? 'success' : 'danger');
    $html .= lr_section_html($sections['pendapatan_lain']);
    $html .= lr_section_html($sections['beban_lain']);
    $html .= lr_total_row('LABA SEBELUM PAJAK', $s['beforeTax'], $s['beforeTax'] >= 0 ? 'success' : 'danger');
    $html .= lr_total_row('LABA (RUGI) BERSIH', $s['net'], $s['net'] >= 0 ? 'success' : 'danger');
    $html .= '</tbody></table></div>';
    $html .= '<p class="text-muted"><small>Periode '.lr_h($filters['start_date']).' s/d '.lr_h($filters['end_date']).'. Sumber: jurnal_header, jurnal_detail, rekening, coa_kategori. Hanya jurnal POSTED; reversal resmi masuk bila dokumen reversal berstatus POSTED.</small></p>';
    return $html;
}

function lr_export_lines($sections)
{
    $s = lr_summary($sections);
    $lines = array();
    foreach (array('pendapatan_usaha','hpp','beban_usaha','pendapatan_lain','beban_lain') as $sectionKey) {
        $section = $sections[$sectionKey];
        $lines[] = array('section', $section['title'], '', '', '', '', $section['total']);
        foreach ($section['categories'] as $category) {
            $lines[] = array('category', $category['label'], $category['kategori_akun'], $category['saldo_normal'], '', '', $category['total']);
            foreach ($category['rows'] as $account) {
                $lines[] = array('account', $account->no_rek, $account->nama_rek, $account->kategori, $account->total_debet, $account->total_kredit, $account->saldo);
            }
        }
        if ($sectionKey === 'hpp') $lines[] = array('result', 'LABA KOTOR', '', '', '', '', $s['gross']);
        if ($sectionKey === 'beban_usaha') $lines[] = array('result', 'LABA OPERASI', '', '', '', '', $s['operating']);
    }
    $lines[] = array('result', 'LABA SEBELUM PAJAK', '', '', '', '', $s['beforeTax']);
    $lines[] = array('result', 'LABA (RUGI) BERSIH', '', '', '', '', $s['net']);
    return $lines;
}

function lr_print_page($sections, $filters, $validation)
{
    $info = function_exists('info_pt') ? info_pt() : null;
    $company = $info && isset($info->nama_pt) ? $info->nama_pt : shortTittle;
    $body = lr_html($sections, $filters, $validation);
    $assetBase = rtrim(base_url(), '/').'/assets/';
    while (ob_get_level() > $GLOBALS['initialOutputBufferLevel']) ob_end_clean();
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html><head><meta charset="utf-8"><title>Laba Rugi</title>'.
        '<link rel="stylesheet" href="'.lr_h($assetBase).'bootstrap/css/bootstrap.min.css">'.
        '<link rel="stylesheet" href="'.lr_h($assetBase).'dist/css/AdminLTE.min.css">'.
        '<style>'.
        'html,body{background:#fff!important;color:#111;font-size:12px}.print-wrap{max-width:1040px;margin:20px auto}.print-title{margin-bottom:14px}.print-title h3{margin:0 0 2px}.print-title h4{margin:0;color:#374151}.lr-table{width:100%;border-collapse:collapse!important}.lr-table th,.lr-table td{font-size:12px;vertical-align:middle!important;border:1px solid #d2d6de!important}.lr-section th{background:#1d4ed8!important;color:#fff!important}.lr-category th{background:#f3f4f6!important;color:#374151!important}.lr-subtotal th{background:#f5f5f5!important}.lr-result th{font-size:13px}.lr-account-name{padding-left:16px!important}.lr-level-2{padding-left:24px!important}.lr-level-3{padding-left:36px!important}.lr-level-4,.lr-level-5,.lr-level-6{padding-left:48px!important}.alert{padding:10px 12px;margin-bottom:12px;border-radius:0}.no-print{margin-bottom:14px}.bg-primary{background:#337ab7!important;color:#fff!important}.success th{background:#dff0d8!important;color:#3c763d!important}.danger th{background:#f2dede!important;color:#a94442!important}'.
        '@media print{*{-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important}.no-print{display:none!important}.print-wrap{margin:0;max-width:none;width:100%}@page{size:A4 portrait;margin:10mm}.alert{border:1px solid #ddd!important}.table-responsive{overflow:visible!important}.lr-table{page-break-inside:auto}.lr-table tr{page-break-inside:avoid;page-break-after:auto}}'.
        '</style>'.
        '</head><body><div class="print-wrap"><div class="no-print"><button onclick="window.print()" class="btn btn-primary btn-sm"><span class="glyphicon glyphicon-print"></span> Print / PDF</button></div>'.
        '<div class="print-title"><h3>'.lr_h($company).'</h3><h4>Laporan Laba/Rugi (Standar)</h4></div>'.
        $body.
        '</div><script>window.onload=function(){setTimeout(function(){window.print();},300);};</script></body></html>';
    exit;
}

$act = isset($_GET['act']) ? $_GET['act'] : '';

try {
    $filters = lr_params();
    $sections = lr_data($db, $filters);
    $summary = lr_summary($sections);
    $validation = lr_validation($db, $filters);

    if ($act === 'filter') {
        lr_json('success', 'OK', array(
            'html'=>lr_html($sections, $filters, $validation),
            'pendapatan'=>lr_num($summary['pendapatan']),
            'gross'=>lr_num($summary['gross']),
            'operating'=>lr_num($summary['operating']),
            'before_tax'=>lr_num($summary['beforeTax']),
            'net'=>lr_num($summary['net']),
            'has_rows'=>lr_has_rows($sections) ? 'Y' : 'N',
            'balanced'=>$validation['balanced'] ? 'Y' : 'N'
        ));
    }

    if ($act === 'print') {
        lr_print_page($sections, $filters, $validation);
    }

    if ($act === 'excel') {
        ini_set('display_errors', '0');
        error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
        require '../../inc/lib/PHPExcel.php';
        require_once '../../inc/excel_style_helper.php';
        PHPExcel_Shared_File::setUseUploadTempDirectory(true);
        $excel = new PHPExcel();
        $sheet = $excel->setActiveSheetIndex(0);
        $sheet->setTitle(erp_export_sheet_title('Laba Rugi'));
        $headers = array('Tipe','COA / Group','Nama / Kategori','Info','Debit','Credit','Nilai L/R');
        foreach ($headers as $i => $label) $sheet->setCellValueByColumnAndRow($i, 4, erp_export_label($label));
        $row = 5;
        foreach (lr_export_lines($sections) as $line) {
            for ($i=0; $i<count($line); $i++) {
                $cell = PHPExcel_Cell::stringFromColumnIndex($i).$row;
                if ($i === 1 && $line[0] === 'account') {
                    $sheet->setCellValueExplicit($cell, $line[$i], PHPExcel_Cell_DataType::TYPE_STRING);
                } else {
                    $sheet->setCellValue($cell, $line[$i]);
                }
            }
            if ($line[0] !== 'account') {
                $sheet->getStyle('A'.$row.':G'.$row)->getFont()->setBold(true);
                $sheet->getStyle('A'.$row.':G'.$row)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB($line[0] === 'section' ? '1D4ED8' : ($line[0] === 'result' ? 'DFF0D8' : 'F3F4F6'));
                if ($line[0] === 'section') $sheet->getStyle('A'.$row.':G'.$row)->getFont()->getColor()->setRGB('FFFFFF');
            }
            $row++;
        }
        if ($row === 5) {
            $sheet->setCellValue('A5', 'Tidak ada transaksi pendapatan atau beban untuk periode/filter ini.');
            $row = 6;
        }
        $lastRow = max(5, $row - 1);
        erpkb_excel_apply_standard_style($excel, array(
            'sheet'=>$sheet,
            'title'=>erp_export_title('LAPORAN LABA RUGI STANDAR'),
            'header_row'=>4,
            'first_data_row'=>5,
            'last_data_row'=>$lastRow,
            'column_count'=>7,
            'money_columns'=>array('E','F','G'),
            'filters'=>array(
                'Periode'=>$filters['start_date'].' s/d '.$filters['end_date'],
                'Status'=>'POSTED',
                'Doc Type'=>$filters['document_type'] ?: 'All',
                'Source'=>$filters['source_module'] ?: 'All',
                'Cost Center'=>$filters['cost_center'] ?: 'All',
                'Profit Center'=>$filters['profit_center'] ?: 'All',
                'Balance'=>$validation['balanced'] ? 'Balance' : 'Not Balance'
            )
        ));
        $tempFile = erpkb_excel_temp_file('laba_rugi_standar_');
        PHPExcel_IOFactory::createWriter($excel, 'Excel2007')->save($tempFile);
        $fileSize = @filesize($tempFile);
        $signature = @file_get_contents($tempFile, false, null, 0, 2);
        if (!$fileSize || $signature !== 'PK') {
            @unlink($tempFile);
            throw new Exception('File Excel gagal dibuat dengan benar.');
        }
        while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="laporan_laba_rugi_standar_'.$filters['start_date'].'_sd_'.$filters['end_date'].'.xlsx"');
        header('Cache-Control: max-age=0');
        header('Pragma: public');
        header('Content-Length: '.$fileSize);
        readfile($tempFile);
        @unlink($tempFile);
        exit;
    }

    lr_json('error', 'Action tidak dikenal.');
} catch (Exception $e) {
    while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
    lr_json('error', $e->getMessage());
}
?>
