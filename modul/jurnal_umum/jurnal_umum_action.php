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
include "../../inc/excel/php-excel-reader/excel_reader2.php";
include "../../inc/excel/SpreadsheetReader.php";
require '../../inc/lib/PHPExcel.php';
require_once '../../inc/excel_style_helper.php';

session_check_json();

function ju_user()
{
    return isset($_SESSION['username']) && $_SESSION['username'] !== '' ? $_SESSION['username'] : 'system';
}

function ju_json($status, $message, $extra = array())
{
    header('Content-Type: application/json');
    echo json_encode(array_merge(array('status' => $status, 'message' => $message), $extra));
    exit;
}

function ju_valid_date($date)
{
    $dt = DateTime::createFromFormat('Y-m-d', $date);
    return $dt && $dt->format('Y-m-d') === $date;
}

function ju_period_open($db, $date)
{
    $period = $db->fetch(
        "SELECT id,status FROM erp_financial_period WHERE ? BETWEEN start_date AND end_date LIMIT 1",
        array($date)
    );
    if (!$period) {
        return 'Fiscal period untuk tanggal '.$date.' belum dibuat.';
    }
    if ($period->status !== 'OPEN') {
        return 'Fiscal period tanggal '.$date.' status '.$period->status.', tidak boleh posting.';
    }
    return true;
}

function ju_amount($value)
{
    $value = trim((string) $value);
    if ($value === '') {
        return 0;
    }
    return round((float) str_replace(',', '', $value), 2);
}

function ju_document_type($type)
{
    $type = strtoupper(trim((string) $type));
    $allowed = array('SA','AJE','KR','DR','KZ','DZ','RV','AB','RE','CM','DM');
    return in_array($type, $allowed) ? $type : 'SA';
}

function ju_status($status)
{
    $status = strtoupper(trim((string) $status));
    return in_array($status, array('DRAFT','POSTED')) ? $status : 'DRAFT';
}

function ju_lookup_id($db, $table, $codeField, $code)
{
    $code = trim((string) $code);
    if ($code === '') {
        return null;
    }
    $row = $db->fetch("SELECT id FROM $table WHERE $codeField=? LIMIT 1", array($code));
    return $row ? (int) $row->id : null;
}

function ju_import_date($value)
{
    if ($value instanceof DateTime) {
        return $value->format('Y-m-d');
    }
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }
    if (is_numeric($value) && (float) $value > 20000) {
        return PHPExcel_Shared_Date::ExcelToPHPObject((float) $value)->format('Y-m-d');
    }
    $formats = array('Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y');
    foreach ($formats as $format) {
        $dt = DateTime::createFromFormat($format, $value);
        if ($dt && $dt->format($format) === $value) {
            return $dt->format('Y-m-d');
        }
    }
    $time = strtotime($value);
    return $time ? date('Y-m-d', $time) : $value;
}

function ju_import_header_key($value)
{
    $value = strtolower(trim((string) $value));
    $value = str_replace(array('*', '(', ')', '/', '-', '.', ':'), ' ', $value);
    $value = preg_replace('/\s+/', ' ', $value);
    return trim($value);
}

function ju_import_value($row, $map, $key, $default = '')
{
    return isset($map[$key]) && isset($row[$map[$key]]) ? trim((string) $row[$map[$key]]) : $default;
}

function ju_lookup_import_code($db, $table, $codeField, $code, $label, $excelRow)
{
    $code = trim((string) $code);
    if ($code === '') {
        return null;
    }
    $id = ju_lookup_id($db, $table, $codeField, $code);
    if (!$id) {
        throw new Exception($label.' "'.$code.'" pada baris '.$excelRow.' tidak ditemukan di master.');
    }
    return $id;
}

function ju_collect_lines($db, $post)
{
    $lines = array();
    $totalDebet = 0;
    $totalKredit = 0;
    $accounts = isset($post['no_rek']) ? (array) $post['no_rek'] : array();

    foreach ($accounts as $idx => $account) {
        $account = trim((string) $account);
        $debet = ju_amount(isset($post['debet'][$idx]) ? $post['debet'][$idx] : 0);
        $kredit = ju_amount(isset($post['kredit'][$idx]) ? $post['kredit'][$idx] : 0);
        $valuta = strtoupper(trim((string) (isset($post['valuta'][$idx]) ? $post['valuta'][$idx] : 'IDR')));
        $kurs = ju_amount(isset($post['kurs'][$idx]) ? $post['kurs'][$idx] : 1);
        $lineText = trim((string) (isset($post['line_text'][$idx]) ? $post['line_text'][$idx] : ''));
        $costCenter = isset($post['cost_center_id'][$idx]) && $post['cost_center_id'][$idx] !== '' ? (int) $post['cost_center_id'][$idx] : null;
        $profitCenter = isset($post['profit_center_id'][$idx]) && $post['profit_center_id'][$idx] !== '' ? (int) $post['profit_center_id'][$idx] : null;
        $taxCode = isset($post['tax_code_id'][$idx]) && $post['tax_code_id'][$idx] !== '' ? (int) $post['tax_code_id'][$idx] : null;

        if ($account === '' && $debet == 0 && $kredit == 0) {
            continue;
        }
        if ($account === '') {
            throw new Exception('COA wajib diisi pada setiap baris jurnal.');
        }
        if ($debet > 0 && $kredit > 0) {
            throw new Exception('Satu baris jurnal tidak boleh berisi debet dan kredit sekaligus.');
        }
        if ($debet <= 0 && $kredit <= 0) {
            throw new Exception('Setiap baris jurnal harus memiliki nilai debet atau kredit.');
        }
        if ($kurs <= 0) {
            throw new Exception('Kurs harus lebih besar dari nol.');
        }

        $accountRow = $db->fetch(
            "SELECT r.no_rek
             FROM rekening r
             LEFT JOIN rekening child ON child.induk = r.no_rek
             WHERE r.no_rek=? AND child.no_rek IS NULL
             LIMIT 1",
            array($account)
        );
        if (!$accountRow) {
            throw new Exception('COA '.$account.' tidak valid untuk posting. Gunakan akun detail/leaf account.');
        }

        $totalDebet += $debet;
        $totalKredit += $kredit;

        $lines[] = array(
            'line_no' => count($lines) + 1,
            'no_rek' => $account,
            'line_text' => $lineText,
            'cost_center_id' => $costCenter,
            'profit_center_id' => $profitCenter,
            'tax_code_id' => $taxCode,
            'debet' => $debet,
            'kredit' => $kredit,
            'valuta' => $valuta ?: 'IDR',
            'kurs' => $kurs ?: 1
        );
    }

    if (count($lines) < 2) {
        throw new Exception('Jurnal minimal harus memiliki dua baris.');
    }
    if (abs($totalDebet - $totalKredit) > 0.01) {
        throw new Exception('Total debet dan kredit harus balance.');
    }

    return array($lines, $totalDebet, $totalKredit);
}

function ju_save_journal($db, $post, $id = null)
{
    $tgl = isset($post['tgl_jurnal']) ? trim($post['tgl_jurnal']) : '';
    if (!ju_valid_date($tgl)) {
        throw new Exception('Tanggal jurnal tidak valid.');
    }

    $status = ju_status(isset($post['posting_status']) ? $post['posting_status'] : 'DRAFT');
    if ($status === 'POSTED') {
        $period = ju_period_open($db, $tgl);
        if ($period !== true) {
            throw new Exception($period);
        }
    }

    list($lines) = ju_collect_lines($db, $post);

    $header = array(
        'no_jurnal' => isset($post['no_jurnal']) && trim($post['no_jurnal']) !== '' ? trim($post['no_jurnal']) : generate_no_jurnal(),
        'document_type' => ju_document_type(isset($post['document_type']) ? $post['document_type'] : 'SA'),
        'posting_status' => $status,
        'tgl_jurnal' => $tgl,
        'ket' => trim((string) (isset($post['ket']) ? $post['ket'] : '')),
        'no_bukti' => trim((string) (isset($post['no_bukti']) ? $post['no_bukti'] : '')),
        'source_module' => trim((string) (isset($post['source_module']) && $post['source_module'] !== '' ? $post['source_module'] : 'MANUAL_GL')),
        'source_document_no' => trim((string) (isset($post['source_document_no']) ? $post['source_document_no'] : '')),
        'updated_by' => ju_user(),
        'updated_at' => date('Y-m-d H:i:s')
    );

    if ($status === 'POSTED') {
        $header['posted_by'] = ju_user();
        $header['posted_at'] = date('Y-m-d H:i:s');
    }

    $db->query("START TRANSACTION");
    try {
        if ($id) {
            $existing = $db->fetch("SELECT * FROM jurnal_header WHERE id=? LIMIT 1", array($id));
            if (!$existing) {
                throw new Exception('Jurnal tidak ditemukan.');
            }
            if ($existing->posting_status !== 'DRAFT') {
                throw new Exception('Hanya jurnal DRAFT yang boleh diedit.');
            }
            unset($header['no_jurnal']);
            $db->update('jurnal_header', $header, 'id', $id);
            $db->delete('jurnal_detail', 'id_header', $id);
            $idHeader = $id;
        } else {
            if (!empty($header['no_jurnal'])) {
                $duplicate = $db->fetch("SELECT id FROM jurnal_header WHERE no_jurnal=? LIMIT 1", array($header['no_jurnal']));
                if ($duplicate) {
                    throw new Exception('No jurnal '.$header['no_jurnal'].' sudah digunakan.');
                }
            }
            $header['username'] = ju_user();
            $header['tgl_insert'] = date('Y-m-d H:i:s');
            if (!$db->insert('jurnal_header', $header)) {
                throw new Exception($db->getErrorMessage());
            }
            $idHeader = $db->last_insert_id();
        }

        foreach ($lines as $line) {
            $line['id_header'] = $idHeader;
            if (!$db->insert('jurnal_detail', $line)) {
                throw new Exception($db->getErrorMessage());
            }
        }

        $db->query("COMMIT");
        return $idHeader;
    } catch (Exception $e) {
        $db->query("ROLLBACK");
        throw $e;
    }
}

$act = isset($_GET['act']) ? $_GET['act'] : '';

try {
    switch ($act) {
        case 'get':
            $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
            $header = $db->fetch("SELECT * FROM jurnal_header WHERE id=? LIMIT 1", array($id));
            if (!$header) {
                throw new Exception('Jurnal tidak ditemukan.');
            }
            if ($header->posting_status !== 'DRAFT') {
                throw new Exception('Hanya jurnal DRAFT yang boleh diedit.');
            }
            $lines = array();
            $detail = $db->query("SELECT * FROM jurnal_detail WHERE id_header=? ORDER BY line_no,id", array($id));
            foreach ($detail as $line) {
                $lines[] = array(
                    'no_rek' => $line->no_rek,
                    'line_text' => $line->line_text,
                    'debet' => $line->debet > 0 ? $line->debet : '',
                    'kredit' => $line->kredit > 0 ? $line->kredit : '',
                    'valuta' => $line->valuta,
                    'kurs' => $line->kurs,
                    'cost_center_id' => $line->cost_center_id,
                    'profit_center_id' => $line->profit_center_id,
                    'tax_code_id' => $line->tax_code_id
                );
            }
            ju_json('success', 'OK', array('header' => $header, 'lines' => $lines));
            break;

        case 'in':
            $id = ju_save_journal($db, $_POST);
            ju_json('success', 'Jurnal berhasil disimpan.', array('id' => $id));
            break;

        case 'update':
        case 'up':
            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            ju_save_journal($db, $_POST, $id);
            ju_json('success', 'Jurnal berhasil diupdate.');
            break;

        case 'post':
            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            $header = $db->fetch("SELECT * FROM jurnal_header WHERE id=? LIMIT 1", array($id));
            if (!$header) {
                throw new Exception('Jurnal tidak ditemukan.');
            }
            if ($header->posting_status !== 'DRAFT') {
                throw new Exception('Hanya jurnal DRAFT yang bisa diposting.');
            }
            $period = ju_period_open($db, $header->tgl_jurnal);
            if ($period !== true) {
                throw new Exception($period);
            }
            $sum = $db->fetch("SELECT COALESCE(SUM(debet),0) debet, COALESCE(SUM(kredit),0) kredit, COUNT(*) lines FROM jurnal_detail WHERE id_header=?", array($id));
            if (!$sum || (int) $sum->lines < 2 || abs((float) $sum->debet - (float) $sum->kredit) > 0.01) {
                throw new Exception('Jurnal tidak balance atau detail belum lengkap.');
            }
            $parent = $db->fetch(
                "SELECT d.no_rek
                 FROM jurnal_detail d
                 LEFT JOIN rekening r ON r.no_rek=d.no_rek
                 LEFT JOIN rekening child ON child.induk=d.no_rek
                 WHERE d.id_header=?
                   AND (r.no_rek IS NULL OR child.no_rek IS NOT NULL)
                 LIMIT 1",
                array($id)
            );
            if ($parent) {
                throw new Exception('COA '.$parent->no_rek.' tidak valid untuk posting. Gunakan akun detail/leaf account.');
            }
            $db->update('jurnal_header', array('posting_status'=>'POSTED','posted_by'=>ju_user(),'posted_at'=>date('Y-m-d H:i:s')), 'id', $id);
            ju_json('success', 'Jurnal berhasil diposting.');
            break;

        case 'reverse':
            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            $tanggal = isset($_POST['tgl_reversal']) && $_POST['tgl_reversal'] !== '' ? $_POST['tgl_reversal'] : date('Y-m-d');
            if (!ju_valid_date($tanggal)) {
                throw new Exception('Tanggal reversal tidak valid.');
            }
            $period = ju_period_open($db, $tanggal);
            if ($period !== true) {
                throw new Exception($period);
            }
            $header = $db->fetch("SELECT * FROM jurnal_header WHERE id=? LIMIT 1", array($id));
            if (!$header || $header->posting_status !== 'POSTED') {
                throw new Exception('Hanya jurnal POSTED yang bisa direversal.');
            }
            $details = $db->query("SELECT * FROM jurnal_detail WHERE id_header=? ORDER BY line_no,id", array($id));
            if (!$details || $details->rowCount() == 0) {
                throw new Exception('Detail jurnal original tidak ditemukan.');
            }

            $db->query("START TRANSACTION");
            $newHeader = array(
                'no_jurnal' => generate_no_jurnal(),
                'document_type' => 'RV',
                'posting_status' => 'POSTED',
                'tgl_jurnal' => $tanggal,
                'ket' => 'REVERSAL: '.$header->no_jurnal.' - '.trim((string) $header->ket),
                'no_bukti' => 'RV-'.$header->no_jurnal,
                'source_module' => 'REVERSAL',
                'source_document_no' => $header->no_jurnal,
                'reversal_of' => $header->id,
                'username' => ju_user(),
                'posted_by' => ju_user(),
                'posted_at' => date('Y-m-d H:i:s'),
                'tgl_insert' => date('Y-m-d H:i:s')
            );
            $db->insert('jurnal_header', $newHeader);
            $newId = $db->last_insert_id();
            foreach ($details as $line) {
                $db->insert('jurnal_detail', array(
                    'id_header' => $newId,
                    'line_no' => $line->line_no,
                    'no_rek' => $line->no_rek,
                    'line_text' => 'Reversal '.$header->no_jurnal.' '.$line->line_text,
                    'cost_center_id' => $line->cost_center_id,
                    'profit_center_id' => $line->profit_center_id,
                    'tax_code_id' => $line->tax_code_id,
                    'debet' => round((float) $line->kredit, 2),
                    'kredit' => round((float) $line->debet, 2),
                    'valuta' => $line->valuta,
                    'kurs' => $line->kurs
                ));
            }
            $db->update('jurnal_header', array('posting_status'=>'REVERSED','updated_by'=>ju_user(),'updated_at'=>date('Y-m-d H:i:s')), 'id', $id);
            $db->query("COMMIT");
            ju_json('success', 'Jurnal reversal berhasil dibuat.', array('id' => $newId));
            break;

        case 'delete':
            $id = isset($_POST['id']) ? (int) $_POST['id'] : (isset($_GET['id']) ? (int) $_GET['id'] : 0);
            $header = $db->fetch("SELECT posting_status FROM jurnal_header WHERE id=? LIMIT 1", array($id));
            if (!$header) {
                throw new Exception('Jurnal tidak ditemukan.');
            }
            if ($header->posting_status !== 'DRAFT') {
                throw new Exception('Jurnal POSTED tidak boleh dihapus. Gunakan reversal.');
            }
            $db->delete('jurnal_header', 'id', $id);
            ju_json('success', 'Draft jurnal berhasil dihapus.');
            break;

        case 'excel':
            $where = " WHERE 1=1 ";
            if (!empty($_GET['tgl_awal']) && !empty($_GET['tgl_akhir'])) {
                $where .= " AND a.tgl_jurnal BETWEEN '".addslashes($_GET['tgl_awal'])."' AND '".addslashes($_GET['tgl_akhir'])."' ";
            }
            if (!empty($_GET['posting_status'])) {
                $where .= " AND a.posting_status='".addslashes($_GET['posting_status'])."' ";
            }
            if (!empty($_GET['document_type'])) {
                $where .= " AND a.document_type='".addslashes($_GET['document_type'])."' ";
            }

            $excel = new PHPExcel();
            $sheet = $excel->setActiveSheetIndex(0);
            $sheet->setTitle(erp_export_sheet_title('Jurnal Umum'));
            $headers = array(erp_export_label("No"),erp_export_label("Journal No"),erp_export_label("Doc Type"),erp_export_label("Status"),erp_export_label("Posting Date"),erp_export_label("Reference"),erp_export_label("Header Text"),erp_export_label("Source"),erp_export_label("Line"),erp_export_label("COA"),erp_export_label("Account Name"),erp_export_label("Line Text"),erp_export_label("Cost Center"),erp_export_label("Profit Center"),erp_export_label("Tax Code"),erp_export_label("Debit"),erp_export_label("Credit"),erp_export_label("Currency"),erp_export_label("Kurs"));
            foreach ($headers as $i => $label) {
                $sheet->setCellValueByColumnAndRow($i, 4, $label);
            }
            $sql = "SELECT a.*,d.line_no,d.no_rek,d.line_text,d.debet,d.kredit,d.valuta,d.kurs,r.nama_rek,cc.cost_center_code,pc.profit_center_code,tc.tax_code
                    FROM jurnal_header a
                    INNER JOIN jurnal_detail d ON d.id_header=a.id
                    LEFT JOIN rekening r ON r.no_rek=d.no_rek
                    LEFT JOIN erp_cost_center cc ON cc.id=d.cost_center_id
                    LEFT JOIN erp_profit_center pc ON pc.id=d.profit_center_id
                    LEFT JOIN erp_tax_code tc ON tc.id=d.tax_code_id
                    $where
                    ORDER BY a.tgl_jurnal,a.no_jurnal,d.line_no,d.id";
            $q = $db->query($sql);
            $row = 5;
            $no = 1;
            foreach ($q as $r) {
                $values = array($no,$r->no_jurnal,$r->document_type,$r->posting_status,$r->tgl_jurnal,$r->no_bukti,$r->ket,$r->source_module,$r->line_no,$r->no_rek,$r->nama_rek,$r->line_text,$r->cost_center_code,$r->profit_center_code,$r->tax_code,$r->debet,$r->kredit,$r->valuta,$r->kurs);
                foreach ($values as $i => $value) {
                    $sheet->setCellValueByColumnAndRow($i, $row, $value);
                }
                $row++;
                $no++;
            }
            erpkb_excel_apply_standard_style($excel, array('sheet'=>$sheet,'title'=>erp_export_title('LAPORAN JURNAL UMUM'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$row-1),'column_count'=>count($headers),'money_columns'=>array('P','Q'),'filters'=>array('Periode'=>(isset($_GET['tgl_awal'])?$_GET['tgl_awal']:'').' s/d '.(isset($_GET['tgl_akhir'])?$_GET['tgl_akhir']:''),'Status'=>isset($_GET['posting_status'])?$_GET['posting_status']:'ALL')));
            while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="jurnal_umum_'.date('YmdHis').'.xlsx"');
            header('Cache-Control: max-age=0');
            PHPExcel_IOFactory::createWriter($excel, 'Excel2007')->save('php://output');
            exit;

        case 'template':
            $excel = new PHPExcel();
            $sheet = $excel->setActiveSheetIndex(0);
            $sheet->setTitle(erp_export_sheet_title('Template Import'));
            $headers = array(erp_export_label("Import Group *"),erp_export_label("No Jurnal"),erp_export_label("Document Type *"),erp_export_label("Posting Date *"),erp_export_label("Reference"),erp_export_label("Header Text *"),erp_export_label("Line Text"),erp_export_label("COA *"),erp_export_label("Debit"),erp_export_label("Credit"),erp_export_label("Currency *"),erp_export_label("Kurs *"),erp_export_label("Cost Center Code"),erp_export_label("Profit Center Code"),erp_export_label("Tax Code"),erp_export_label("Posting Status *"));
            foreach ($headers as $i => $label) {
                $sheet->setCellValueByColumnAndRow($i, 1, $label);
            }
            $sample = array('JE-001','', 'SA', date('Y-m-d'), 'REF-001', 'Manual adjustment', 'Baris debet', '11002', 100000, 0, 'IDR', 1, 'CC-0001', 'PC-0001', '', 'DRAFT');
            foreach ($sample as $i => $value) {
                $sheet->setCellValueByColumnAndRow($i, 2, $value);
            }
            $sample[6] = 'Baris kredit'; $sample[7] = '41100'; $sample[8] = 0; $sample[9] = 100000;
            foreach ($sample as $i => $value) {
                $sheet->setCellValueByColumnAndRow($i, 3, $value);
            }
            $sheet->getStyle('A1:P1')->applyFromArray(array('font'=>array('bold'=>true,'color'=>array('rgb'=>'FFFFFF')),'alignment'=>array('horizontal'=>PHPExcel_Style_Alignment::HORIZONTAL_CENTER),'fill'=>array('type'=>PHPExcel_Style_Fill::FILL_SOLID,'color'=>array('rgb'=>'1D4ED8'))));
            foreach (array('A','C','D','F','H','K','L','P') as $mandatoryCol) {
                $sheet->getStyle($mandatoryCol.'1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('C00000');
            }
            $sheet->getStyle('A1:P3')->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
            $sheet->getStyle('I2:J1000')->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('L2:L1000')->getNumberFormat()->setFormatCode('#,##0.0000');
            $sheet->freezePane('A2');
            $sheet->setAutoFilter('A1:P1');
            $widths = array('A'=>16,'B'=>16,'C'=>16,'D'=>14,'E'=>18,'F'=>28,'G'=>28,'H'=>28,'I'=>14,'J'=>14,'K'=>10,'L'=>10,'M'=>18,'N'=>18,'O'=>14,'P'=>16);
            foreach ($widths as $col => $width) $sheet->getColumnDimension($col)->setWidth($width);

            $hint = new PHPExcel_RichText();
            $hint->createTextRun("Catatan:\n")->getFont()->setBold(true);
            $hint->createText("1. Import Group wajib sama untuk semua baris dalam satu jurnal.\n2. No Jurnal boleh kosong; sistem akan membuat nomor otomatis.\n3. Dalam satu baris hanya isi Debit atau Credit, tidak boleh keduanya.\n4. Jika Posting Status = POSTED, fiscal period tanggal posting harus OPEN.\n5. Kolom header merah adalah mandatory.");
            $guide = $excel->createSheet(1);
            $guide->setTitle(erp_export_sheet_title('Petunjuk'));
            $guide->setCellValue('A1', 'PETUNJUK IMPORT JURNAL UMUM');
            $guide->setCellValue('A3', $hint);
            $guide->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $guide->getStyle('A3')->getAlignment()->setWrapText(true)->setVertical(PHPExcel_Style_Alignment::VERTICAL_TOP);
            $guide->getColumnDimension('A')->setWidth(110);
            $guide->getRowDimension(3)->setRowHeight(120);

            $refSheets = array(
                'Ref COA' => array('query'=>"SELECT no_rek code,nama_rek name FROM rekening r LEFT JOIN rekening c ON c.induk=r.no_rek WHERE c.no_rek IS NULL ORDER BY r.no_rek", 'headers'=>array('COA Code','Account Name')),
                'Ref Cost Center' => array('query'=>"SELECT cost_center_code code,cost_center_name name FROM erp_cost_center WHERE status='Aktif' ORDER BY cost_center_code", 'headers'=>array('Cost Center Code','Cost Center Name')),
                'Ref Profit Center' => array('query'=>"SELECT profit_center_code code,profit_center_name name FROM erp_profit_center WHERE status='Aktif' ORDER BY profit_center_code", 'headers'=>array('Profit Center Code','Profit Center Name')),
                'Ref Tax Code' => array('query'=>"SELECT tax_code code,CONCAT(tax_name,' ',rate,'%') name FROM erp_tax_code WHERE status='Aktif' ORDER BY tax_code", 'headers'=>array('Tax Code','Tax Name')),
                'Ref Document Type' => array('rows'=>array(array('SA','General Ledger'),array('AJE','Adjustment Journal'),array('KR','Vendor Invoice'),array('DR','Customer Invoice'),array('CM','Credit Memo'),array('DM','Debit Memo'),array('KZ','Vendor Payment'),array('DZ','Incoming Payment')), 'headers'=>array('Document Type','Description')),
                'Ref Status' => array('rows'=>array(array('DRAFT','Save only, editable'),array('POSTED','Posted to GL, locked')), 'headers'=>array('Posting Status','Description'))
            );
            $sheetIndex = 2;
            foreach ($refSheets as $title => $config) {
                $ref = $excel->createSheet($sheetIndex++);
                $ref->setTitle(substr($title,0,31));
                $ref->setCellValue('A1', $config['headers'][0]);
                $ref->setCellValue('B1', $config['headers'][1]);
                $ref->getStyle('A1:B1')->applyFromArray(array('font'=>array('bold'=>true,'color'=>array('rgb'=>'FFFFFF')),'fill'=>array('type'=>PHPExcel_Style_Fill::FILL_SOLID,'color'=>array('rgb'=>'0F766E'))));
                $r = 2;
                if (isset($config['query'])) {
                    foreach ($db->query($config['query']) as $rowRef) {
                        $ref->setCellValue('A'.$r, $rowRef->code);
                        $ref->setCellValue('B'.$r, $rowRef->name);
                        $r++;
                    }
                } else {
                    foreach ($config['rows'] as $rowRef) {
                        $ref->setCellValue('A'.$r, $rowRef[0]);
                        $ref->setCellValue('B'.$r, $rowRef[1]);
                        $r++;
                    }
                }
                $ref->getColumnDimension('A')->setWidth(24);
                $ref->getColumnDimension('B')->setWidth(48);
                $ref->setAutoFilter('A1:B'.max(1,$r-1));
            }
            $excel->setActiveSheetIndex(0);
            while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="template_import_jurnal_umum.xlsx"');
            header('Cache-Control: max-age=0');
            PHPExcel_IOFactory::createWriter($excel, 'Excel2007')->save('php://output');
            exit;

        case 'import':
            if (empty($_FILES['file_excel']['tmp_name'])) {
                throw new Exception('File Excel belum dipilih.');
            }
            $target = "../../upload/import_data/".date('YmdHis')."_".basename($_FILES['file_excel']['name']);
            move_uploaded_file($_FILES['file_excel']['tmp_name'], $target);
            $reader = new SpreadsheetReader($target);
            $groups = array();
            $headerMap = array();
            $headerRowFound = false;
            $errors = array();
            foreach ($reader as $idx => $row) {
                $excelRow = $idx + 1;
                if (!$headerRowFound) {
                    foreach ($row as $colIndex => $header) {
                        $key = ju_import_header_key($header);
                        if ($key !== '') {
                            $headerMap[$key] = $colIndex;
                        }
                    }
                    if (isset($headerMap['import group']) || isset($headerMap['no jurnal'])) {
                        $headerRowFound = true;
                    }
                    continue;
                }

                $isEmpty = true;
                foreach ($row as $cell) {
                    if (trim((string) $cell) !== '') {
                        $isEmpty = false;
                        break;
                    }
                }
                if ($isEmpty) continue;

                try {
                    if (isset($headerMap['import group'])) {
                        $groupKey = ju_import_value($row, $headerMap, 'import group');
                        $noJurnal = ju_import_value($row, $headerMap, 'no jurnal');
                        $documentType = ju_import_value($row, $headerMap, 'document type', 'SA');
                        $postingDate = ju_import_date(ju_import_value($row, $headerMap, 'posting date'));
                        $reference = ju_import_value($row, $headerMap, 'reference');
                        $headerText = ju_import_value($row, $headerMap, 'header text');
                        $lineText = ju_import_value($row, $headerMap, 'line text');
                        $coa = ju_import_value($row, $headerMap, 'coa');
                        $debit = ju_import_value($row, $headerMap, 'debit', 0);
                        $credit = ju_import_value($row, $headerMap, 'credit', 0);
                        $currency = ju_import_value($row, $headerMap, 'currency', 'IDR');
                        $kurs = ju_import_value($row, $headerMap, 'kurs', 1);
                        $costCode = ju_import_value($row, $headerMap, 'cost center code');
                        $profitCode = ju_import_value($row, $headerMap, 'profit center code');
                        $taxCode = ju_import_value($row, $headerMap, 'tax code');
                        $postingStatus = ju_import_value($row, $headerMap, 'posting status', 'DRAFT');
                    } else {
                        // Backward-compatible layout from the previous template.
                        $groupKey = ju_import_value($row, $headerMap, 'no jurnal');
                        $noJurnal = $groupKey;
                        $documentType = ju_import_value($row, $headerMap, 'document type', 'SA');
                        $postingDate = ju_import_date(ju_import_value($row, $headerMap, 'posting date'));
                        $reference = ju_import_value($row, $headerMap, 'reference');
                        $headerText = ju_import_value($row, $headerMap, 'header text');
                        $lineText = ju_import_value($row, $headerMap, 'line text');
                        $coa = ju_import_value($row, $headerMap, 'coa');
                        $debit = ju_import_value($row, $headerMap, 'debit', 0);
                        $credit = ju_import_value($row, $headerMap, 'credit', 0);
                        $currency = ju_import_value($row, $headerMap, 'currency', 'IDR');
                        $kurs = ju_import_value($row, $headerMap, 'kurs', 1);
                        $costCode = ju_import_value($row, $headerMap, 'cost center code');
                        $profitCode = ju_import_value($row, $headerMap, 'profit center code');
                        $taxCode = ju_import_value($row, $headerMap, 'tax code');
                        $postingStatus = ju_import_value($row, $headerMap, 'posting status', 'DRAFT');
                    }

                    if ($groupKey === '') throw new Exception('Import Group wajib diisi.');
                    if ($documentType === '') throw new Exception('Document Type wajib diisi.');
                    if ($postingDate === '') throw new Exception('Posting Date wajib diisi.');
                    if ($headerText === '') throw new Exception('Header Text wajib diisi.');
                    if ($coa === '') throw new Exception('COA wajib diisi.');
                    if ($currency === '') throw new Exception('Currency wajib diisi.');
                    if ($kurs === '') throw new Exception('Kurs wajib diisi.');
                    if ($postingStatus === '') throw new Exception('Posting Status wajib diisi.');

                    if (!isset($groups[$groupKey])) {
                        $groups[$groupKey] = array(
                            'no_jurnal'=>$noJurnal,
                            'document_type'=>$documentType,
                            'tgl_jurnal'=>$postingDate,
                            'no_bukti'=>$reference,
                            'ket'=>$headerText,
                            'source_module'=>'IMPORT_GL',
                            'source_document_no'=>$reference,
                            'posting_status'=>$postingStatus,
                            'no_rek'=>array(),'line_text'=>array(),'debet'=>array(),'kredit'=>array(),'valuta'=>array(),'kurs'=>array(),'cost_center_id'=>array(),'profit_center_id'=>array(),'tax_code_id'=>array(),
                            '_rows'=>array()
                        );
                    } else {
                        foreach (array('document_type'=>$documentType, 'tgl_jurnal'=>$postingDate, 'posting_status'=>$postingStatus) as $field => $value) {
                            if ((string) $groups[$groupKey][$field] !== (string) $value) {
                                throw new Exception('Header '.$field.' berbeda dalam Import Group '.$groupKey.'.');
                            }
                        }
                    }

                    $groups[$groupKey]['_rows'][] = $excelRow;
                    $groups[$groupKey]['line_text'][] = $lineText;
                    $groups[$groupKey]['no_rek'][] = $coa;
                    $groups[$groupKey]['debet'][] = $debit;
                    $groups[$groupKey]['kredit'][] = $credit;
                    $groups[$groupKey]['valuta'][] = $currency;
                    $groups[$groupKey]['kurs'][] = $kurs;
                    $groups[$groupKey]['cost_center_id'][] = ju_lookup_import_code($db, 'erp_cost_center', 'cost_center_code', $costCode, 'Cost Center', $excelRow);
                    $groups[$groupKey]['profit_center_id'][] = ju_lookup_import_code($db, 'erp_profit_center', 'profit_center_code', $profitCode, 'Profit Center', $excelRow);
                    $groups[$groupKey]['tax_code_id'][] = ju_lookup_import_code($db, 'erp_tax_code', 'tax_code', $taxCode, 'Tax Code', $excelRow);
                } catch (Exception $rowError) {
                    $errors[] = 'Baris '.$excelRow.': '.$rowError->getMessage();
                }
            }
            if (!$headerRowFound) {
                throw new Exception('Header template tidak ditemukan. Gunakan template terbaru dari menu Jurnal Umum.');
            }
            if (count($errors)) {
                throw new Exception("Import gagal validasi:\n".implode("\n", array_slice($errors, 0, 20)));
            }
            if (!count($groups)) {
                throw new Exception('Tidak ada data jurnal yang bisa diimport.');
            }
            $success = 0;
            foreach ($groups as $journal) {
                unset($journal['_rows']);
                ju_save_journal($db, $journal);
                $success++;
            }
            ju_json('success', 'Import selesai. Jurnal berhasil dibuat: '.$success.'.');
            break;

        default:
            ju_json('error', 'Action tidak dikenal.');
    }
} catch (Exception $e) {
    if (isset($db)) {
        $db->query("ROLLBACK");
    }
    ju_json('error', $e->getMessage());
}
?>
