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

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include __DIR__ . "/../../inc/config.php";
require_once __DIR__ . "/bank_reconciliation_lib.php";
session_check_json();

function brec_json($status, $message = '', $extra = array())
{
    global $initialOutputBufferLevel;
    while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(array_merge(array('status'=>$status,'message'=>$message), $extra));
    exit;
}

function brec_params()
{
    $start = isset($_REQUEST['start_date']) ? $_REQUEST['start_date'] : date('Y-m-01');
    $end = isset($_REQUEST['end_date']) ? $_REQUEST['end_date'] : date('Y-m-d');
    if (!brec_valid_date($start) || !brec_valid_date($end) || $start > $end) throw new Exception('Periode tanggal tidak valid.');
    return array(
        $start,
        $end,
        isset($_REQUEST['bank_account']) ? trim($_REQUEST['bank_account']) : '',
        isset($_REQUEST['status']) ? trim($_REQUEST['status']) : ''
    );
}

function brec_statement_rows($db, $start, $end, $bankAccount, $status)
{
    $where = "bs.statement_date BETWEEN ? AND ?";
    $params = array($start, $end);
    if ($bankAccount !== '') { $where .= " AND bs.bank_account=?"; $params[] = $bankAccount; }
    if ($status !== '') { $where .= " AND bs.status=?"; $params[] = $status; }
    return $db->query(
        "SELECT bs.*,r.nama_rek bank_account_name
         FROM erp_bank_statement_line bs
         LEFT JOIN rekening r ON r.no_rek=bs.bank_account
         WHERE $where
         ORDER BY bs.statement_date DESC,bs.id DESC",
        $params
    );
}

function brec_erp_rows($db, $start, $end, $bankAccount, $openOnly)
{
    $sql = "SELECT src.* FROM (".brec_erp_source_sql().") src
            WHERE src.posting_date BETWEEN ? AND ?";
    $params = array($start, $end);
    if ($bankAccount !== '') { $sql .= " AND src.bank_account=?"; $params[] = $bankAccount; }
    if ($openOnly) {
        $sql .= " AND NOT EXISTS (
            SELECT 1 FROM erp_bank_reconciliation_match m
            WHERE m.source_module=src.source_module AND m.source_id=src.source_id AND m.status='MATCHED'
        )";
    }
    $sql .= " ORDER BY src.posting_date DESC,src.document_no DESC";
    return $db->query($sql, $params);
}

function brec_source_row($db, $sourceModule, $sourceId)
{
    $row = $db->fetch(
        "SELECT src.* FROM (".brec_erp_source_sql().") src WHERE src.source_module=? AND src.source_id=? LIMIT 1",
        array($sourceModule, $sourceId)
    );
    if (!$row) throw new Exception('Transaksi ERP tidak ditemukan atau belum POSTED.');
    return $row;
}

$act = isset($_GET['act']) ? $_GET['act'] : '';

try {
    switch ($act) {
        case 'get_statement_no':
            brec_json('success', 'OK', array('no'=>brec_next_statement_no($db)));
            break;

        case 'save_statement':
            $statementNo = trim(isset($_POST['statement_no']) ? $_POST['statement_no'] : '');
            if ($statementNo === '') $statementNo = brec_next_statement_no($db);
            $bankAccount = trim($_POST['bank_account']);
            brec_account_leaf($db, $bankAccount, 'Bank account');
            $statementDate = trim($_POST['statement_date']);
            $valueDate = trim(isset($_POST['value_date']) ? $_POST['value_date'] : '');
            if (!brec_valid_date($statementDate) || ($valueDate !== '' && !brec_valid_date($valueDate))) throw new Exception('Tanggal statement/value tidak valid.');
            $debit = brec_amount($_POST['debit_amount']);
            $credit = brec_amount($_POST['credit_amount']);
            if ($debit <= 0 && $credit <= 0) throw new Exception('Debit atau credit statement wajib diisi.');
            if ($debit > 0 && $credit > 0) throw new Exception('Statement line hanya boleh debit atau credit, tidak boleh keduanya.');
            $db->insert('erp_bank_statement_line', array(
                'statement_no'=>$statementNo,
                'bank_account'=>$bankAccount,
                'statement_date'=>$statementDate,
                'value_date'=>$valueDate !== '' ? $valueDate : null,
                'bank_reference'=>trim($_POST['bank_reference']),
                'description'=>trim($_POST['description']),
                'debit_amount'=>$debit,
                'credit_amount'=>$credit,
                'currency'=>strtoupper(trim($_POST['currency'] ?: 'IDR')),
                'status'=>'OPEN',
                'created_by'=>brec_user(),
                'created_at'=>date('Y-m-d H:i:s')
            ));
            brec_json('success', 'Bank statement line berhasil disimpan.');
            break;

        case 'filter':
            list($start,$end,$bankAccount,$status) = brec_params();
            $statementRows = brec_statement_rows($db,$start,$end,$bankAccount,$status);
            $erpRows = brec_erp_rows($db,$start,$end,$bankAccount,true);
            $stmtHtml = ''; $erpHtml = '';
            $stmtOpen = 0; $stmtMatched = 0; $erpOpen = 0; $stmtCount = 0; $erpCount = 0;
            foreach ($statementRows as $r) {
                $stmtCount++;
                $amount = (float)$r->credit_amount > 0 ? (float)$r->credit_amount : (float)$r->debit_amount;
                if ($r->status === 'OPEN') $stmtOpen += $amount;
                if ($r->status === 'MATCHED') $stmtMatched += $amount;
                $statusClass = $r->status === 'MATCHED' ? 'success' : ($r->status === 'CANCELLED' ? 'danger' : 'warning');
                $stmtHtml .= '<tr data-statement-id="'.$r->id.'" data-amount="'.$amount.'" data-direction="'.((float)$r->credit_amount > 0 ? 'IN' : 'OUT').'">'.
                    '<td><input type="radio" name="statement_pick" value="'.$r->id.'" '.($r->status !== 'OPEN' ? 'disabled' : '').'></td>'.
                    '<td>'.brec_h($r->statement_date).'</td>'.
                    '<td>'.brec_h($r->statement_no).'</td>'.
                    '<td>'.brec_h($r->bank_reference).'</td>'.
                    '<td>'.brec_h($r->description).'</td>'.
                    '<td class="text-right">'.brec_num($r->debit_amount).'</td>'.
                    '<td class="text-right">'.brec_num($r->credit_amount).'</td>'.
                    '<td><span class="label label-'.$statusClass.'">'.brec_h($r->status).'</span></td>'.
                    '<td><button class="btn btn-info btn-xs brec-stmt-detail" data-id="'.$r->id.'"><i class="fa fa-search"></i></button></td>'.
                    '</tr>';
            }
            if ($stmtCount === 0) $stmtHtml = '<tr><td colspan="9" class="text-center text-muted">Tidak ada statement.</td></tr>';
            foreach ($erpRows as $r) {
                $erpCount++;
                $erpOpen += (float)$r->amount;
                $erpHtml .= '<tr data-source-module="'.$r->source_module.'" data-source-id="'.$r->source_id.'" data-amount="'.$r->amount.'" data-direction="'.$r->direction.'">'.
                    '<td><input type="radio" name="erp_pick" value="'.$r->source_module.'|'.$r->source_id.'"></td>'.
                    '<td>'.brec_h($r->posting_date).'</td>'.
                    '<td>'.brec_h($r->source_module).'</td>'.
                    '<td>'.brec_h($r->document_no).'</td>'.
                    '<td>'.brec_h($r->reference_no).'</td>'.
                    '<td>'.brec_h($r->partner_name).'</td>'.
                    '<td>'.brec_h($r->direction).'</td>'.
                    '<td class="text-right">'.brec_num($r->amount).'</td>'.
                    '<td>'.brec_h($r->currency).'</td>'.
                    '</tr>';
            }
            if ($erpCount === 0) $erpHtml = '<tr><td colspan="9" class="text-center text-muted">Tidak ada transaksi ERP open.</td></tr>';
            brec_json('success','OK',array('statement_html'=>$stmtHtml,'erp_html'=>$erpHtml,'stmt_open'=>brec_num($stmtOpen),'stmt_matched'=>brec_num($stmtMatched),'erp_open'=>brec_num($erpOpen),'stmt_count'=>$stmtCount,'erp_count'=>$erpCount));
            break;

        case 'match':
            $statementId = isset($_POST['statement_id']) ? (int)$_POST['statement_id'] : 0;
            $sourceModule = trim($_POST['source_module']);
            $sourceId = isset($_POST['source_id']) ? (int)$_POST['source_id'] : 0;
            if (!in_array($sourceModule, array('BANK_RECEIPT','BANK_PAYMENT','CASH_JOURNAL','VENDOR_PAYMENT'))) throw new Exception('Source module tidak valid.');
            $stmt = $db->fetch("SELECT * FROM erp_bank_statement_line WHERE id=? LIMIT 1", array($statementId));
            if (!$stmt || $stmt->status !== 'OPEN') throw new Exception('Statement line tidak ditemukan atau tidak OPEN.');
            if ($db->fetch("SELECT id FROM erp_bank_reconciliation_match WHERE bank_statement_line_id=? AND status='MATCHED' LIMIT 1", array($statementId))) throw new Exception('Statement line sudah matched.');
            if ($db->fetch("SELECT id FROM erp_bank_reconciliation_match WHERE source_module=? AND source_id=? AND status='MATCHED' LIMIT 1", array($sourceModule,$sourceId))) throw new Exception('Transaksi ERP sudah matched.');
            $src = brec_source_row($db,$sourceModule,$sourceId);
            if ($src->bank_account !== $stmt->bank_account) throw new Exception('Bank account statement dan ERP tidak sama.');
            $stmtDirection = (float)$stmt->credit_amount > 0 ? 'IN' : 'OUT';
            if ($stmtDirection !== $src->direction) throw new Exception('Arah transaksi tidak sama. Credit statement cocok dengan receipt, debit statement cocok dengan payment.');
            $stmtAmount = $stmtDirection === 'IN' ? (float)$stmt->credit_amount : (float)$stmt->debit_amount;
            $erpAmount = (float)$src->amount;
            $diff = round($stmtAmount - $erpAmount, 2);
            $db->query("START TRANSACTION");
            $db->insert('erp_bank_reconciliation_match', array(
                'match_no'=>brec_next_match_no($db),
                'bank_statement_line_id'=>$stmt->id,
                'source_module'=>$src->source_module,
                'source_id'=>$src->source_id,
                'source_document_no'=>$src->document_no,
                'bank_account'=>$src->bank_account,
                'match_date'=>date('Y-m-d'),
                'statement_amount'=>$stmtAmount,
                'erp_amount'=>$erpAmount,
                'difference_amount'=>$diff,
                'status'=>'MATCHED',
                'notes'=>trim(isset($_POST['notes']) ? $_POST['notes'] : ''),
                'created_by'=>brec_user(),
                'created_at'=>date('Y-m-d H:i:s')
            ));
            $db->update('erp_bank_statement_line', array('status'=>'MATCHED','matched_by'=>brec_user(),'matched_at'=>date('Y-m-d H:i:s'),'updated_by'=>brec_user(),'updated_at'=>date('Y-m-d H:i:s')), 'id', $stmt->id);
            $db->query("COMMIT");
            brec_json('success', 'Rekonsiliasi berhasil disimpan.');
            break;

        case 'history':
            list($start,$end,$bankAccount,$status) = brec_params();
            $where = "m.match_date BETWEEN ? AND ?";
            $params = array($start,$end);
            if ($bankAccount !== '') { $where .= " AND m.bank_account=?"; $params[] = $bankAccount; }
            if ($status !== '') { $where .= " AND m.status=?"; $params[] = $status; }
            $rows = $db->query("SELECT m.*,r.nama_rek bank_account_name,bs.statement_no,bs.statement_date,bs.bank_reference FROM erp_bank_reconciliation_match m LEFT JOIN erp_bank_statement_line bs ON bs.id=m.bank_statement_line_id LEFT JOIN rekening r ON r.no_rek=m.bank_account WHERE $where ORDER BY m.match_date DESC,m.id DESC", $params);
            $html = ''; $no = 1; $count = 0;
            foreach ($rows as $r) {
                $count++;
                $cls = $r->status === 'MATCHED' ? 'success' : 'default';
                $action = $r->status === 'MATCHED' ? '<button class="btn btn-warning btn-xs brec-unmatch" data-id="'.$r->id.'"><i class="fa fa-undo"></i></button>' : '';
                $html .= '<tr><td>'.$no++.'</td><td>'.brec_h($r->match_date).'</td><td>'.brec_h($r->match_no).'</td><td>'.brec_h($r->statement_no).'</td><td>'.brec_h($r->source_module).'</td><td>'.brec_h($r->source_document_no).'</td><td>'.brec_h($r->bank_account.' - '.$r->bank_account_name).'</td><td class="text-right">'.brec_num($r->statement_amount).'</td><td class="text-right">'.brec_num($r->erp_amount).'</td><td class="text-right">'.brec_num($r->difference_amount).'</td><td><span class="label label-'.$cls.'">'.brec_h($r->status).'</span></td><td>'.$action.'</td></tr>';
            }
            if ($no === 1) $html = '<tr><td colspan="12" class="text-center text-muted">Tidak ada history.</td></tr>';
            brec_json('success','OK',array('html'=>$html,'count'=>$count));
            break;

        case 'statement_detail':
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            $r = $db->fetch("SELECT bs.*,ra.nama_rek bank_account_name,m.match_no,m.source_module,m.source_document_no,m.statement_amount,m.erp_amount,m.difference_amount FROM erp_bank_statement_line bs LEFT JOIN rekening ra ON ra.no_rek=bs.bank_account LEFT JOIN erp_bank_reconciliation_match m ON m.bank_statement_line_id=bs.id AND m.status='MATCHED' WHERE bs.id=? LIMIT 1", array($id));
            if (!$r) throw new Exception('Statement line tidak ditemukan.');
            $html = '<table class="table table-bordered table-condensed">'.
                '<tr><th>Statement No</th><td>'.brec_h($r->statement_no).'</td><th>'.fin_h('common_status', 'Status').'</th><td>'.brec_h($r->status).'</td></tr>'.
                '<tr><th>'.fin_h('finance_bank', 'Bank').'</th><td>'.brec_h($r->bank_account.' - '.$r->bank_account_name).'</td><th>Date</th><td>'.brec_h($r->statement_date).'</td></tr>'.
                '<tr><th>Value Date</th><td>'.brec_h($r->value_date).'</td><th>Bank Ref</th><td>'.brec_h($r->bank_reference).'</td></tr>'.
                '<tr><th>'.fin_h('finance_debit', 'Debit').'</th><td class="text-right">'.brec_num($r->debit_amount).'</td><th>'.fin_h('finance_credit', 'Credit').'</th><td class="text-right">'.brec_num($r->credit_amount).'</td></tr>'.
                '<tr><th>'.fin_h('finance_description', 'Description').'</th><td colspan="3">'.brec_h($r->description).'</td></tr>'.
                '<tr><th>Match No</th><td>'.brec_h($r->match_no).'</td><th>ERP Doc</th><td>'.brec_h(trim($r->source_module.' '.$r->source_document_no)).'</td></tr>'.
                '<tr><th>Statement Amt</th><td class="text-right">'.brec_num($r->statement_amount).'</td><th>Diff</th><td class="text-right">'.brec_num($r->difference_amount).'</td></tr>'.
                '</table>';
            brec_json('success','OK',array('html'=>$html));
            break;

        case 'unmatch':
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $m = $db->fetch("SELECT * FROM erp_bank_reconciliation_match WHERE id=? LIMIT 1", array($id));
            if (!$m || $m->status !== 'MATCHED') throw new Exception('Data match tidak ditemukan atau sudah unmatch.');
            $db->query("START TRANSACTION");
            $db->update('erp_bank_reconciliation_match', array('status'=>'UNMATCHED','unmatched_by'=>brec_user(),'unmatched_at'=>date('Y-m-d H:i:s')), 'id', $id);
            $db->update('erp_bank_statement_line', array('status'=>'OPEN','matched_by'=>null,'matched_at'=>null,'updated_by'=>brec_user(),'updated_at'=>date('Y-m-d H:i:s')), 'id', $m->bank_statement_line_id);
            $db->query("COMMIT");
            brec_json('success','Rekonsiliasi berhasil dibuka kembali.');
            break;

        case 'excel':
            require_once __DIR__ . "/../../inc/lib/PHPExcel.php";
            require_once __DIR__ . "/../../inc/excel_style_helper.php";
            list($start,$end,$bankAccount,$status) = brec_params();
            $excel = new PHPExcel();
            $sheet = $excel->setActiveSheetIndex(0);
            $sheet->setTitle(erp_export_sheet_title('Bank Reconciliation'));
            $headers = array(erp_export_label("No"),erp_export_label("Match Date"),erp_export_label("Match No"),erp_export_label("Statement No"),erp_export_label("Statement Date"),erp_export_label("Bank Account"),erp_export_label("Source Module"),erp_export_label("Source Document"),erp_export_label("Statement Amount"),erp_export_label("ERP Amount"),erp_export_label("Difference"),erp_export_label("Status"),erp_export_label("Notes"));
            foreach ($headers as $i=>$label) $sheet->setCellValueByColumnAndRow($i,4,$label);
            $where = "m.match_date BETWEEN ? AND ?";
            $params = array($start,$end);
            if ($bankAccount !== '') { $where .= " AND m.bank_account=?"; $params[] = $bankAccount; }
            if ($status !== '') { $where .= " AND m.status=?"; $params[] = $status; }
            $rows = $db->query("SELECT m.*,bs.statement_no,bs.statement_date,r.nama_rek bank_account_name FROM erp_bank_reconciliation_match m LEFT JOIN erp_bank_statement_line bs ON bs.id=m.bank_statement_line_id LEFT JOIN rekening r ON r.no_rek=m.bank_account WHERE $where ORDER BY m.match_date DESC,m.id DESC", $params);
            $rowNo = 5; $no = 1;
            foreach ($rows as $r) {
                $values = array($no++,$r->match_date,$r->match_no,$r->statement_no,$r->statement_date,$r->bank_account.' - '.$r->bank_account_name,$r->source_module,$r->source_document_no,$r->statement_amount,$r->erp_amount,$r->difference_amount,$r->status,$r->notes);
                foreach ($values as $i=>$value) $sheet->setCellValueByColumnAndRow($i,$rowNo,$value);
                $rowNo++;
            }
            erpkb_excel_apply_standard_style($excel, array('sheet'=>$sheet,'title'=>erp_export_title('BANK RECONCILIATION'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$rowNo-1),'column_count'=>count($headers),'money_columns'=>array('I','J','K'),'filters'=>array('Periode'=>$start.' s/d '.$end,'Bank Account'=>$bankAccount,'Status'=>$status)));
            while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="bank_reconciliation_'.date('YmdHis').'.xlsx"');
            header('Cache-Control: max-age=0');
            PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save('php://output');
            exit;

        default:
            brec_json('error','Action tidak dikenal.');
    }
} catch (Exception $e) {
    if (isset($db)) $db->query("ROLLBACK");
    brec_json('error', $e->getMessage());
}
