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

function nr_json($status, $message = '', $extra = array())
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(array('status'=>$status,'message'=>$message), $extra));
    exit;
}

function nr_h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function nr_num($value)
{
    return number_format((float) $value, 2, '.', ',');
}

function nr_valid_date($date)
{
    $dt = DateTime::createFromFormat('Y-m-d', $date);
    return $dt && $dt->format('Y-m-d') === $date;
}

function nr_req($key, $default = '')
{
    return isset($_REQUEST[$key]) ? trim((string) $_REQUEST[$key]) : $default;
}

function nr_filters()
{
    $date = nr_req('as_of_date', nr_req('report_date', date('Y-m-d')));
    $costCenter = nr_req('cost_center');
    $profitCenter = nr_req('profit_center');
    if (!nr_valid_date($date)) {
        throw new Exception('Tanggal as-of neraca tidak valid.');
    }
    if ($costCenter !== '' && !ctype_digit($costCenter)) {
        throw new Exception('Cost center tidak valid.');
    }
    if ($profitCenter !== '' && !ctype_digit($profitCenter)) {
        throw new Exception('Profit center tidak valid.');
    }
    return array('as_of_date'=>$date, 'cost_center'=>$costCenter, 'profit_center'=>$profitCenter);
}

function nr_year_start($asOfDate)
{
    return date('Y-01-01', strtotime($asOfDate));
}

function nr_period_status($db, $asOfDate)
{
    $period = $db->fetch(
        "SELECT period_code,status,start_date,end_date
         FROM erp_financial_period
         WHERE ? BETWEEN start_date AND end_date
         LIMIT 1",
        array($asOfDate)
    );
    return $period ?: null;
}

function nr_journal_where($filters, &$params)
{
    $params = array(nr_year_start($filters['as_of_date']), $filters['as_of_date']);
    $where = "h.tgl_jurnal BETWEEN ? AND ? AND h.posting_status='POSTED'";
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

function nr_validate_opening_balance($db, $filters)
{
    $year = (int) date('Y', strtotime($filters['as_of_date']));
    $summary = $db->fetch(
        "SELECT COUNT(*) rows_count,
                COALESCE(SUM(debet),0) total_debet,
                COALESCE(SUM(kredit),0) total_kredit
         FROM saldo_awal
         WHERE periode=?",
        array($year)
    );
    if (!$summary || (int) $summary->rows_count < 1) {
        throw new Exception('Saldo awal periode '.$year.' belum diisi. Isi/import saldo awal sebelum menjalankan neraca.');
    }
    if (abs((float) $summary->total_debet - (float) $summary->total_kredit) > 0.01) {
        throw new Exception('Saldo awal periode '.$year.' tidak balance. Debit '.nr_num($summary->total_debet).' dan kredit '.nr_num($summary->total_kredit).'.');
    }

    $invalid = $db->fetch(
        "SELECT COUNT(*) total
         FROM saldo_awal sa
         LEFT JOIN rekening r ON r.no_rek=sa.no_rek
         WHERE sa.periode=?
           AND r.no_rek IS NULL",
        array($year)
    );
    if ($invalid && (int) $invalid->total > 0) {
        throw new Exception('Saldo awal periode '.$year.' memiliki akun yang tidak ditemukan di rekening.');
    }
}

function nr_empty_sections()
{
    return array(
        'aset'=>array('title'=>'ASET', 'categories'=>array(), 'total'=>0),
        'kewajiban'=>array('title'=>'KEWAJIBAN', 'categories'=>array(), 'total'=>0),
        'modal'=>array('title'=>'MODAL', 'categories'=>array(), 'total'=>0)
    );
}

function nr_account_balances($db, $filters)
{
    $year = (int) date('Y', strtotime($filters['as_of_date']));
    $journalParams = array();
    $journalWhere = nr_journal_where($filters, $journalParams);
    $params = array_merge(array($year), $journalParams);

    $rows = $db->query(
        "SELECT
            r.no_rek,r.nama_rek,r.level,r.induk,
            k.id kategori_id,k.kategori_akun,k.kategori,k.saldo_normal,
            COALESCE(sa.debet,0) saldo_awal_debet,
            COALESCE(sa.kredit,0) saldo_awal_kredit,
            COALESCE(j.debet,0) mutasi_debet,
            COALESCE(j.kredit,0) mutasi_kredit,
            CASE
              WHEN k.saldo_normal='kredit'
              THEN (COALESCE(sa.kredit,0)+COALESCE(j.kredit,0))-(COALESCE(sa.debet,0)+COALESCE(j.debet,0))
              ELSE (COALESCE(sa.debet,0)+COALESCE(j.debet,0))-(COALESCE(sa.kredit,0)+COALESCE(j.kredit,0))
            END saldo
         FROM rekening r
         INNER JOIN coa_kategori k ON k.id=r.kat_coa
         LEFT JOIN (
            SELECT no_rek,SUM(COALESCE(debet,0)) debet,SUM(COALESCE(kredit,0)) kredit
            FROM saldo_awal
            WHERE periode=?
            GROUP BY no_rek
         ) sa ON sa.no_rek=r.no_rek
         LEFT JOIN (
            SELECT d.no_rek,SUM(COALESCE(d.debet,0)) debet,SUM(COALESCE(d.kredit,0)) kredit
            FROM jurnal_detail d
            INNER JOIN jurnal_header h ON h.id=d.id_header
            WHERE $journalWhere
            GROUP BY d.no_rek
         ) j ON j.no_rek=r.no_rek
         WHERE k.kategori_akun IN ('aset','kewajiban','modal')
         ORDER BY k.kategori_akun,k.id,LENGTH(r.no_rek),r.no_rek",
        $params
    );
    if ($rows === false) {
        throw new Exception('Query neraca gagal: '.$db->getErrorMessage());
    }

    $sections = nr_empty_sections();
    foreach ($rows as $row) {
        if (abs((float) $row->saldo) < 0.005 && abs((float)$row->saldo_awal_debet) < 0.005 && abs((float)$row->saldo_awal_kredit) < 0.005 && abs((float)$row->mutasi_debet) < 0.005 && abs((float)$row->mutasi_kredit) < 0.005) {
            continue;
        }
        $sectionKey = $row->kategori_akun;
        $categoryKey = (string) $row->kategori_id;
        if (!isset($sections[$sectionKey]['categories'][$categoryKey])) {
            $sections[$sectionKey]['categories'][$categoryKey] = array(
                'label'=>$row->kategori,
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

function nr_current_profit($db, $filters)
{
    $params = array();
    $where = nr_journal_where($filters, $params);
    $row = $db->fetch(
        "SELECT COALESCE(SUM(CASE
            WHEN k.kategori_akun='pendapatan' THEN COALESCE(d.kredit,0)-COALESCE(d.debet,0)
            WHEN k.kategori_akun='beban' THEN COALESCE(d.kredit,0)-COALESCE(d.debet,0)
            ELSE 0 END),0) amount
         FROM jurnal_detail d
         INNER JOIN jurnal_header h ON h.id=d.id_header
         INNER JOIN rekening r ON r.no_rek=d.no_rek
         INNER JOIN coa_kategori k ON k.id=r.kat_coa
         WHERE $where
           AND k.kategori_akun IN ('pendapatan','beban')",
        $params
    );
    if ($row === false) {
        throw new Exception('Query laba/rugi berjalan gagal: '.$db->getErrorMessage());
    }
    return (float) $row->amount;
}

function nr_journal_validation($db, $filters)
{
    $params = array();
    $where = nr_journal_where($filters, $params);
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
        throw new Exception('Validasi jurnal gagal: '.$db->getErrorMessage());
    }
    return array(
        'journal_count'=>(int) $row->journal_count,
        'total_debet'=>(float) $row->total_debet,
        'total_kredit'=>(float) $row->total_kredit,
        'difference'=>(float) $row->total_debet - (float) $row->total_kredit,
        'unbalanced_count'=>(int) $row->unbalanced_count
    );
}

function nr_add_current_profit(&$sections, $profit, $shouldAdd)
{
    if (!$shouldAdd || abs($profit) < 0.005) {
        return;
    }
    $row = (object) array(
        'no_rek'=>'CY-PROFIT',
        'nama_rek'=>'Laba (Rugi) Tahun Berjalan',
        'level'=>3,
        'induk'=>'',
        'kategori_id'=>'CY-PROFIT',
        'kategori_akun'=>'modal',
        'kategori'=>'Laba Tahun Berjalan',
        'saldo_normal'=>'kredit',
        'saldo_awal_debet'=>0,
        'saldo_awal_kredit'=>0,
        'mutasi_debet'=>0,
        'mutasi_kredit'=>0,
        'saldo'=>$profit
    );
    if (!isset($sections['modal']['categories']['CY-PROFIT'])) {
        $sections['modal']['categories']['CY-PROFIT'] = array(
            'label'=>'Laba Tahun Berjalan',
            'saldo_normal'=>'kredit',
            'rows'=>array(),
            'total'=>0
        );
    }
    $sections['modal']['categories']['CY-PROFIT']['rows'][] = $row;
    $sections['modal']['categories']['CY-PROFIT']['total'] += $profit;
    $sections['modal']['total'] += $profit;
}

function nr_totals($sections)
{
    $totalAset = (float) $sections['aset']['total'];
    $totalKewajiban = (float) $sections['kewajiban']['total'];
    $totalModal = (float) $sections['modal']['total'];
    $totalPassiva = $totalKewajiban + $totalModal;
    $difference = $totalAset - $totalPassiva;
    return array(
        'total_aset'=>$totalAset,
        'total_kewajiban'=>$totalKewajiban,
        'total_modal'=>$totalModal,
        'total_passiva'=>$totalPassiva,
        'difference'=>$difference,
        'balanced'=>abs($difference) <= 0.01
    );
}

function nr_section_html($section)
{
    $html = '<tr class="nr-group"><th colspan="6">'.nr_h($section['title']).'</th></tr>';
    if (!count($section['categories'])) {
        $html .= '<tr><td colspan="6" class="text-muted"><em>Tidak ada saldo</em></td></tr>';
    }
    foreach ($section['categories'] as $category) {
        $html .= '<tr class="nr-category"><th colspan="5">'.nr_h($category['label']).' <small>normal '.nr_h($category['saldo_normal']).'</small></th><th class="text-right">'.nr_num($category['total']).'</th></tr>';
        foreach ($category['rows'] as $row) {
            $level = max(0, min(6, (int) $row->level));
            $html .= '<tr>'.
                '<td width="13%">'.nr_h($row->no_rek).'</td>'.
                '<td class="nr-account-name nr-level-'.$level.'">'.nr_h($row->nama_rek).'</td>'.
                '<td width="14%" class="text-right">'.nr_num((float)$row->saldo_awal_debet - (float)$row->saldo_awal_kredit).'</td>'.
                '<td width="14%" class="text-right">'.nr_num($row->mutasi_debet).'</td>'.
                '<td width="14%" class="text-right">'.nr_num($row->mutasi_kredit).'</td>'.
                '<td width="16%" class="text-right">'.nr_num($row->saldo).'</td>'.
                '</tr>';
        }
        $html .= '<tr class="active nr-subtotal"><th colspan="5">Subtotal '.nr_h($category['label']).'</th><th class="text-right">'.nr_num($category['total']).'</th></tr>';
    }
    $html .= '<tr class="nr-total"><th colspan="5">TOTAL '.nr_h($section['title']).'</th><th class="text-right">'.nr_num($section['total']).'</th></tr>';
    return $html;
}

function nr_report_html($sections, $filters, $period, $profit, $profitAdded, $validation)
{
    $totals = nr_totals($sections);
    $balanceClass = $totals['balanced'] ? 'alert-success' : 'alert-warning';
    $html = '<div class="alert '.$balanceClass.'"><strong>'.($totals['balanced'] ? 'Balance' : 'Tidak balance').'</strong> per '.nr_h($filters['as_of_date']).'. Selisih: '.nr_num($totals['difference']).'. Jurnal POSTED terfilter: '.nr_h($validation['journal_count']).'.</div>';
    if ((int)$validation['unbalanced_count'] > 0) {
        $html .= '<div class="alert alert-danger"><strong>Warning:</strong> Ada '.nr_h($validation['unbalanced_count']).' jurnal POSTED tidak balance di rentang ini.</div>';
    }
    if ($filters['cost_center'] !== '' || $filters['profit_center'] !== '') {
        $html .= '<div class="alert alert-info"><i class="fa fa-info-circle"></i> Filter cost/profit center diterapkan pada mutasi jurnal dan laba/rugi berjalan. Saldo awal tidak memiliki dimensi cost/profit center.</div>';
    }
    if ($period) {
        $html .= '<p class="text-muted"><small>Fiscal period: '.nr_h($period->period_code).' ('.nr_h($period->status).'). Laba/rugi berjalan '.($profitAdded ? 'ditambahkan ke modal' : 'tidak ditambahkan karena periode CLOSED atau nilainya nol').': '.nr_num($profit).'.</small></p>';
    }
    $html .= '<div class="table-responsive"><table class="table table-bordered table-condensed nr-table">';
    $html .= '<thead><tr class="bg-primary"><th>COA</th><th>Nama Akun</th><th class="text-right">Saldo Awal Net</th><th class="text-right">Mutasi Debit</th><th class="text-right">Mutasi Kredit</th><th class="text-right">Saldo</th></tr></thead><tbody>';
    $html .= nr_section_html($sections['aset']);
    $html .= nr_section_html($sections['kewajiban']);
    $html .= nr_section_html($sections['modal']);
    $html .= '<tr class="nr-grand"><th colspan="5">TOTAL KEWAJIBAN + MODAL</th><th class="text-right">'.nr_num($totals['total_passiva']).'</th></tr>';
    $html .= '<tr class="'.($totals['balanced'] ? 'success' : 'danger').'"><th colspan="5">SELISIH BALANCE</th><th class="text-right">'.nr_num($totals['difference']).'</th></tr>';
    $html .= '</tbody></table></div>';
    return array($html, $totals);
}

function nr_export_lines($sections)
{
    $lines = array();
    foreach (array('aset','kewajiban','modal') as $sectionKey) {
        $section = $sections[$sectionKey];
        $lines[] = array('section', $section['title'], '', '', '', '', $section['total']);
        foreach ($section['categories'] as $category) {
            $lines[] = array('category', $category['label'], '', '', '', '', $category['total']);
            foreach ($category['rows'] as $row) {
                $lines[] = array('account', $row->no_rek, $row->nama_rek, (float)$row->saldo_awal_debet - (float)$row->saldo_awal_kredit, $row->mutasi_debet, $row->mutasi_kredit, $row->saldo);
            }
        }
    }
    return $lines;
}

function nr_print_page($sections, $filters, $period, $profit, $profitAdded, $validation)
{
    $info = function_exists('info_pt') ? info_pt() : null;
    $company = $info && isset($info->nama_pt) ? $info->nama_pt : shortTittle;
    list($body) = nr_report_html($sections, $filters, $period, $profit, $profitAdded, $validation);
    $assetBase = rtrim(base_url(), '/').'/assets/';
    while (ob_get_level() > $GLOBALS['initialOutputBufferLevel']) ob_end_clean();
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html><head><meta charset="utf-8"><title>Neraca</title>'.
        '<link rel="stylesheet" href="'.nr_h($assetBase).'bootstrap/css/bootstrap.min.css">'.
        '<link rel="stylesheet" href="'.nr_h($assetBase).'dist/css/AdminLTE.min.css">'.
        '<style>html,body{background:#fff!important;color:#111;font-size:12px}.print-wrap{max-width:1120px;margin:20px auto}.print-title{margin-bottom:14px}.print-title h3{margin:0 0 2px}.print-title h4{margin:0;color:#374151}.nr-table{width:100%;border-collapse:collapse!important}.nr-table th,.nr-table td{font-size:12px;vertical-align:middle!important;border:1px solid #d2d6de!important}.nr-group th{background:#1d4ed8!important;color:#fff!important}.nr-category th{background:#e0f2fe!important;color:#0f172a!important}.nr-total th{background:#f3f4f6!important}.nr-grand th{background:#0f766e!important;color:#fff!important}.nr-account-name{padding-left:16px!important}.nr-level-2{padding-left:24px!important}.nr-level-3{padding-left:36px!important}.nr-level-4,.nr-level-5,.nr-level-6{padding-left:48px!important}.alert{padding:10px 12px;margin-bottom:12px;border-radius:0}.no-print{margin-bottom:14px}@media print{*{-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important}.no-print{display:none!important}.print-wrap{margin:0;max-width:none;width:100%}@page{size:A4 landscape;margin:8mm}.table-responsive{overflow:visible!important}.nr-table tr{page-break-inside:avoid;page-break-after:auto}}</style>'.
        '</head><body><div class="print-wrap"><div class="no-print"><button onclick="window.print()" class="btn btn-primary btn-sm"><span class="glyphicon glyphicon-print"></span> Print / PDF</button></div>'.
        '<div class="print-title"><h3>'.nr_h($company).'</h3><h4>Neraca (Standar)</h4></div>'.$body.
        '</div><script>window.onload=function(){setTimeout(function(){window.print();},300);};</script></body></html>';
    exit;
}

function nr_prepare_report($db, $filters)
{
    nr_validate_opening_balance($db, $filters);
    $period = nr_period_status($db, $filters['as_of_date']);
    $sections = nr_account_balances($db, $filters);
    $profit = nr_current_profit($db, $filters);
    $profitAdded = !$period || $period->status !== 'CLOSED';
    nr_add_current_profit($sections, $profit, $profitAdded);
    $validation = nr_journal_validation($db, $filters);
    return array($sections, $period, $profit, $profitAdded, $validation);
}

$act = isset($_GET['act']) ? $_GET['act'] : '';

try {
    $filters = nr_filters();
    list($sections, $period, $profit, $profitAdded, $validation) = nr_prepare_report($db, $filters);

    if ($act === 'filter') {
        list($html, $totals) = nr_report_html($sections, $filters, $period, $profit, $profitAdded, $validation);
        nr_json('success', 'OK', array(
            'html'=>$html,
            'total_aset'=>nr_num($totals['total_aset']),
            'total_kewajiban'=>nr_num($totals['total_kewajiban']),
            'total_modal'=>nr_num($totals['total_modal']),
            'total_passiva'=>nr_num($totals['total_passiva']),
            'difference'=>nr_num($totals['difference']),
            'balanced'=>$totals['balanced'] ? 'Y' : 'N',
            'profit'=>nr_num($profit),
            'profit_added'=>$profitAdded ? 'Y' : 'N',
            'period_status'=>$period ? $period->status : ''
        ));
    }

    if ($act === 'print') {
        nr_print_page($sections, $filters, $period, $profit, $profitAdded, $validation);
    }

    if ($act === 'excel') {
        ini_set('display_errors', '0');
        error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
        require '../../inc/lib/PHPExcel.php';
        require_once '../../inc/excel_style_helper.php';
        PHPExcel_Shared_File::setUseUploadTempDirectory(true);
        $totals = nr_totals($sections);
        $excel = new PHPExcel();
        $sheet = $excel->setActiveSheetIndex(0);
        $sheet->setTitle(erp_export_sheet_title('Neraca'));
        $heads = array('Tipe','COA / Group','Nama Akun','Saldo Awal Net','Mutasi Debit','Mutasi Kredit','Saldo');
        foreach ($heads as $i=>$h) $sheet->setCellValueByColumnAndRow($i, 4, erp_export_label($h));
        $row = 5;
        foreach (nr_export_lines($sections) as $line) {
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
                $sheet->getStyle('A'.$row.':G'.$row)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB($line[0] === 'section' ? '1D4ED8' : 'E0F2FE');
                if ($line[0] === 'section') $sheet->getStyle('A'.$row.':G'.$row)->getFont()->getColor()->setRGB('FFFFFF');
            }
            $row++;
        }
        $summaryRows = array(
            array('TOTAL ASET', $totals['total_aset']),
            array('TOTAL KEWAJIBAN', $totals['total_kewajiban']),
            array('TOTAL MODAL', $totals['total_modal']),
            array('TOTAL KEWAJIBAN + MODAL', $totals['total_passiva']),
            array('SELISIH BALANCE', $totals['difference'])
        );
        foreach ($summaryRows as $sr) {
            $sheet->setCellValue('B'.$row, $sr[0]);
            $sheet->setCellValue('G'.$row, $sr[1]);
            $sheet->getStyle('B'.$row.':G'.$row)->getFont()->setBold(true);
            $row++;
        }
        $lastRow = max(5, $row - 1);
        erpkb_excel_apply_standard_style($excel, array(
            'sheet'=>$sheet,
            'title'=>erp_export_title('NERACA STANDAR'),
            'header_row'=>4,
            'first_data_row'=>5,
            'last_data_row'=>$lastRow,
            'column_count'=>7,
            'money_columns'=>array('D','E','F','G'),
            'filters'=>array(
                'As Of Date'=>$filters['as_of_date'],
                'Status Jurnal'=>'POSTED',
                'Cost Center'=>$filters['cost_center'] ?: 'All',
                'Profit Center'=>$filters['profit_center'] ?: 'All',
                'Period Status'=>$period ? $period->status : 'N/A',
                'Balance'=>$totals['balanced'] ? 'Balance' : 'Not Balance'
            )
        ));
        $tmp = erpkb_excel_temp_file('neraca_standar_');
        PHPExcel_IOFactory::createWriter($excel, 'Excel2007')->save($tmp);
        $size = @filesize($tmp);
        $signature = @file_get_contents($tmp, false, null, 0, 2);
        if (!$size || $signature !== 'PK') {
            @unlink($tmp);
            throw new Exception('File Excel gagal dibuat dengan benar.');
        }
        while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="neraca_standar_'.$filters['as_of_date'].'.xlsx"');
        header('Content-Length: '.$size);
        header('Cache-Control: max-age=0');
        header('Pragma: public');
        readfile($tmp);
        @unlink($tmp);
        exit;
    }

    nr_json('error', 'Action tidak dikenal.');
} catch (Exception $e) {
    while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
    nr_json('error', $e->getMessage());
}
?>
