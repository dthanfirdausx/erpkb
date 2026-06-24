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
require_once __DIR__ . "/bank_payment_lib.php";
session_check_json();

function bp_json($status, $message = '', $extra = array())
{
    global $initialOutputBufferLevel;
    while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(array_merge(array('status'=>$status,'message'=>$message), $extra));
    exit;
}

function bp_params()
{
    $start = isset($_REQUEST['start_date']) ? $_REQUEST['start_date'] : date('Y-m-01');
    $end = isset($_REQUEST['end_date']) ? $_REQUEST['end_date'] : date('Y-m-d');
    if (!bp_valid_date($start) || !bp_valid_date($end) || $start > $end) throw new Exception('Periode tanggal tidak valid.');
    return array(
        $start,
        $end,
        isset($_REQUEST['status']) ? trim($_REQUEST['status']) : '',
        isset($_REQUEST['payment_category']) ? trim($_REQUEST['payment_category']) : '',
        isset($_REQUEST['bank_account']) ? trim($_REQUEST['bank_account']) : ''
    );
}

function bp_list_query($db, $start, $end, $status, $category, $bankAccount)
{
    $where = "bp.posting_date BETWEEN ? AND ?";
    $params = array($start, $end);
    if ($status !== '') { $where .= " AND bp.status=?"; $params[] = $status; }
    if ($category !== '') { $where .= " AND bp.payment_category=?"; $params[] = $category; }
    if ($bankAccount !== '') { $where .= " AND bp.bank_account=?"; $params[] = $bankAccount; }

    return $db->query(
        "SELECT bp.*,ba.nama_rek bank_account_name,oa.nama_rek offset_account_name,cc.cost_center_code,pc.profit_center_code,tc.tax_code,jh.no_jurnal
         FROM erp_bank_payment bp
         LEFT JOIN rekening ba ON ba.no_rek=bp.bank_account
         LEFT JOIN rekening oa ON oa.no_rek=bp.offset_account
         LEFT JOIN erp_cost_center cc ON cc.id=bp.cost_center_id
         LEFT JOIN erp_profit_center pc ON pc.id=bp.profit_center_id
         LEFT JOIN erp_tax_code tc ON tc.id=bp.tax_code_id
         LEFT JOIN jurnal_header jh ON jh.id=bp.journal_header_id
         WHERE $where
         ORDER BY bp.posting_date DESC,bp.id DESC",
        $params
    );
}

$act = isset($_GET['act']) ? $_GET['act'] : '';

try {
    switch ($act) {
        case 'get_no':
            bp_json('success', 'OK', array('no'=>bp_next_no($db)));
            break;

        case 'save':
        case 'post':
            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if ($act === 'post' && $id && empty($_POST['bank_payment_no'])) {
                $payment = $db->fetch("SELECT * FROM erp_bank_payment WHERE id=? LIMIT 1", array($id));
                if (!$payment) throw new Exception('Bank payment tidak ditemukan.');
                if ($payment->status !== 'DRAFT') throw new Exception('Hanya draft yang bisa diposting.');
                bp_post_to_gl($db, $payment);
                bp_json('success', 'Bank payment berhasil diposting.', array('id'=>$id));
            }

            $paymentNo = trim(isset($_POST['bank_payment_no']) ? $_POST['bank_payment_no'] : '');
            if ($paymentNo === '') $paymentNo = bp_next_no($db);
            $category = isset($_POST['payment_category']) ? $_POST['payment_category'] : 'VENDOR';
            if (!in_array($category, array('VENDOR','EXPENSE','TAX','INTERCOMPANY','OTHER'))) $category = 'VENDOR';
            $method = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'TRANSFER';
            if (!in_array($method, array('TRANSFER','GIRO','CHEQUE','VIRTUAL_ACCOUNT','OTHER'))) $method = 'TRANSFER';
            $docDate = trim(isset($_POST['document_date']) ? $_POST['document_date'] : date('Y-m-d'));
            $postingDate = trim(isset($_POST['posting_date']) ? $_POST['posting_date'] : date('Y-m-d'));
            $valueDate = trim(isset($_POST['value_date']) ? $_POST['value_date'] : '');
            if (!bp_valid_date($docDate) || !bp_valid_date($postingDate) || ($valueDate !== '' && !bp_valid_date($valueDate))) throw new Exception('Tanggal dokumen/posting/value tidak valid.');
            if ($act === 'post') {
                $period = bp_period_open($db, $postingDate);
                if ($period !== true) throw new Exception($period);
            }

            $bankAccount = trim($_POST['bank_account']);
            $offsetAccount = trim($_POST['offset_account']);
            if ($bankAccount === $offsetAccount) throw new Exception('Bank account dan offset account tidak boleh sama.');
            bp_account_leaf($db, $bankAccount, 'Bank account');
            bp_account_leaf($db, $offsetAccount, 'Offset account');
            $amount = bp_amount($_POST['amount']);
            if ($amount <= 0) throw new Exception('Amount wajib lebih dari nol.');

            $data = array(
                'bank_payment_no'=>$paymentNo,
                'payment_category'=>$category,
                'document_date'=>$docDate,
                'posting_date'=>$postingDate,
                'value_date'=>$valueDate !== '' ? $valueDate : null,
                'bank_account'=>$bankAccount,
                'offset_account'=>$offsetAccount,
                'amount'=>$amount,
                'currency'=>strtoupper(trim($_POST['currency'] ?: 'IDR')),
                'kurs'=>bp_amount($_POST['kurs'] ?: 1),
                'payee_name'=>trim($_POST['payee_name']),
                'bank_reference'=>trim($_POST['bank_reference']),
                'external_reference'=>trim($_POST['external_reference']),
                'payment_method'=>$method,
                'cost_center_id'=>$_POST['cost_center_id'] !== '' ? (int) $_POST['cost_center_id'] : null,
                'profit_center_id'=>$_POST['profit_center_id'] !== '' ? (int) $_POST['profit_center_id'] : null,
                'tax_code_id'=>$_POST['tax_code_id'] !== '' ? (int) $_POST['tax_code_id'] : null,
                'description'=>trim($_POST['description']),
                'updated_by'=>bp_user(),
                'updated_at'=>date('Y-m-d H:i:s')
            );

            if ($id) {
                $existing = $db->fetch("SELECT * FROM erp_bank_payment WHERE id=? LIMIT 1", array($id));
                if (!$existing) throw new Exception('Bank payment tidak ditemukan.');
                if ($existing->status !== 'DRAFT') throw new Exception('Hanya draft yang boleh diedit.');
                $db->update('erp_bank_payment', $data, 'id', $id);
            } else {
                if ($db->fetch("SELECT id FROM erp_bank_payment WHERE bank_payment_no=? LIMIT 1", array($paymentNo))) throw new Exception('Nomor bank payment sudah digunakan.');
                $data['status'] = 'DRAFT';
                $data['created_by'] = bp_user();
                $data['created_at'] = date('Y-m-d H:i:s');
                $db->insert('erp_bank_payment', $data);
                $id = $db->last_insert_id();
            }

            if ($act === 'post') {
                $payment = $db->fetch("SELECT * FROM erp_bank_payment WHERE id=? LIMIT 1", array($id));
                bp_post_to_gl($db, $payment);
                bp_json('success', 'Bank payment berhasil disimpan dan diposting.', array('id'=>$id));
            }
            bp_json('success', 'Draft bank payment berhasil disimpan.', array('id'=>$id));
            break;

        case 'get':
            $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
            $row = $db->fetch("SELECT * FROM erp_bank_payment WHERE id=? LIMIT 1", array($id));
            if (!$row) throw new Exception('Bank payment tidak ditemukan.');
            bp_json('success', 'OK', array('data'=>$row));
            break;

        case 'filter':
            list($start,$end,$status,$category,$bankAccount) = bp_params();
            $rows = bp_list_query($db, $start, $end, $status, $category, $bankAccount);
            $html = '';
            $total = 0; $posted = 0; $draft = 0; $count = 0;
            foreach ($rows as $r) {
                $count++;
                $total += (float) $r->amount;
                if ($r->status === 'POSTED') $posted += (float) $r->amount;
                if ($r->status === 'DRAFT') $draft += (float) $r->amount;
                $statusClass = $r->status === 'POSTED' ? 'success' : ($r->status === 'REVERSED' ? 'danger' : 'warning');
                $actions = '<div class="btn-group btn-group-xs">';
                $actions .= '<button class="btn btn-info bp-detail" data-id="'.$r->id.'"><i class="fa fa-search"></i></button>';
                if ($r->status === 'DRAFT') {
                    $actions .= '<button class="btn btn-primary bp-edit" data-id="'.$r->id.'"><i class="fa fa-pencil"></i></button>';
                    $actions .= '<button class="btn btn-success bp-post" data-id="'.$r->id.'"><i class="fa fa-check"></i></button>';
                }
                if ($r->status === 'POSTED') {
                    $actions .= '<button class="btn btn-warning bp-reverse" data-id="'.$r->id.'"><i class="fa fa-undo"></i></button>';
                }
                $actions .= '</div>';
                $html .= '<tr>'.
                    '<td>'.$count.'</td>'.
                    '<td>'.bp_h($r->posting_date).'</td>'.
                    '<td>'.bp_h($r->bank_payment_no).'</td>'.
                    '<td>'.bp_h($r->payment_category).'</td>'.
                    '<td><span class="label label-'.$statusClass.'">'.bp_h($r->status).'</span></td>'.
                    '<td>'.bp_h($r->bank_account.' - '.$r->bank_account_name).'</td>'.
                    '<td>'.bp_h($r->payee_name).'</td>'.
                    '<td>'.bp_h($r->payment_method).'</td>'.
                    '<td>'.bp_h($r->bank_reference).'</td>'.
                    '<td class="text-right">'.bp_num($r->amount).'</td>'.
                    '<td>'.bp_h($r->currency).'</td>'.
                    '<td>'.$actions.'</td>'.
                    '</tr>';
            }
            if ($count === 0) $html = '<tr><td colspan="12" class="text-center text-muted">Tidak ada data.</td></tr>';
            bp_json('success', 'OK', array('html'=>$html,'total'=>bp_num($total),'posted'=>bp_num($posted),'draft'=>bp_num($draft),'count'=>$count));
            break;

        case 'detail':
            $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
            $r = $db->fetch("SELECT bp.*,ba.nama_rek bank_account_name,oa.nama_rek offset_account_name,cc.cost_center_code,pc.profit_center_code,tc.tax_code,jh.no_jurnal FROM erp_bank_payment bp LEFT JOIN rekening ba ON ba.no_rek=bp.bank_account LEFT JOIN rekening oa ON oa.no_rek=bp.offset_account LEFT JOIN erp_cost_center cc ON cc.id=bp.cost_center_id LEFT JOIN erp_profit_center pc ON pc.id=bp.profit_center_id LEFT JOIN erp_tax_code tc ON tc.id=bp.tax_code_id LEFT JOIN jurnal_header jh ON jh.id=bp.journal_header_id WHERE bp.id=? LIMIT 1", array($id));
            if (!$r) throw new Exception('Bank payment tidak ditemukan.');
            $html = '<div class="row"><div class="col-md-3"><b>'.fin_h('common_no', 'No').'</b><br>'.bp_h($r->bank_payment_no).'</div><div class="col-md-3"><b>Category</b><br>'.bp_h($r->payment_category).'</div><div class="col-md-3"><b>'.fin_h('common_status', 'Status').'</b><br>'.bp_h($r->status).'</div><div class="col-md-3"><b>Journal</b><br>'.bp_h($r->no_jurnal).'</div></div><hr>';
            $html .= '<table class="table table-bordered table-condensed"><tr><th>'.fin_h('finance_posting_date', 'Posting Date').'</th><td>'.bp_h($r->posting_date).'</td><th>'.fin_h('finance_document_date', 'Document Date').'</th><td>'.bp_h($r->document_date).'</td></tr><tr><th>Value Date</th><td>'.bp_h($r->value_date).'</td><th>Payee</th><td>'.bp_h($r->payee_name).'</td></tr><tr><th>Bank Account</th><td>'.bp_h($r->bank_account.' - '.$r->bank_account_name).'</td><th>Offset Account</th><td>'.bp_h($r->offset_account.' - '.$r->offset_account_name).'</td></tr><tr><th>'.fin_h('finance_amount', 'Amount').'</th><td class="text-right">'.bp_num($r->amount).' '.bp_h($r->currency).'</td><th>Kurs</th><td>'.bp_num($r->kurs).'</td></tr><tr><th>Payment Method</th><td>'.bp_h($r->payment_method).'</td><th>Bank Ref</th><td>'.bp_h($r->bank_reference).'</td></tr><tr><th>External Ref</th><td>'.bp_h($r->external_reference).'</td><th>'.fin_h('finance_tax_code', 'Tax Code').'</th><td>'.bp_h($r->tax_code).'</td></tr><tr><th>Cost Center</th><td>'.bp_h($r->cost_center_code).'</td><th>Profit Center</th><td>'.bp_h($r->profit_center_code).'</td></tr><tr><th>'.fin_h('finance_description', 'Description').'</th><td colspan="3">'.bp_h($r->description).'</td></tr></table>';
            bp_json('success', 'OK', array('html'=>$html));
            break;

        case 'reverse':
            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            $payment = $db->fetch("SELECT * FROM erp_bank_payment WHERE id=? LIMIT 1", array($id));
            if (!$payment || $payment->status !== 'POSTED' || !$payment->journal_header_id) throw new Exception('Hanya bank payment POSTED yang bisa direversal.');
            $period = bp_period_open($db, date('Y-m-d'));
            if ($period !== true) throw new Exception($period);
            $originalLines = $db->query("SELECT * FROM jurnal_detail WHERE id_header=? ORDER BY line_no,id", array($payment->journal_header_id));
            $db->query("START TRANSACTION");
            $db->insert('jurnal_header', array(
                'no_jurnal'=>generate_no_jurnal(),
                'document_type'=>'RV',
                'posting_status'=>'POSTED',
                'tgl_jurnal'=>date('Y-m-d'),
                'ket'=>'REVERSAL BANK PAYMENT: '.$payment->bank_payment_no,
                'no_bukti'=>'RV-'.$payment->bank_payment_no,
                'source_module'=>'BANK_PAYMENT_REVERSAL',
                'source_document_no'=>$payment->bank_payment_no,
                'reversal_of'=>$payment->journal_header_id,
                'username'=>bp_user(),
                'posted_by'=>bp_user(),
                'posted_at'=>date('Y-m-d H:i:s'),
                'tgl_insert'=>date('Y-m-d H:i:s')
            ));
            $revId = $db->last_insert_id();
            $lineNo = 1;
            foreach ($originalLines as $line) {
                $db->insert('jurnal_detail', array(
                    'id_header'=>$revId,
                    'line_no'=>$lineNo++,
                    'no_rek'=>$line->no_rek,
                    'line_text'=>'Reversal '.$payment->bank_payment_no,
                    'cost_center_id'=>$line->cost_center_id,
                    'profit_center_id'=>$line->profit_center_id,
                    'tax_code_id'=>$line->tax_code_id,
                    'debet'=>$line->kredit,
                    'kredit'=>$line->debet,
                    'valuta'=>$line->valuta,
                    'kurs'=>$line->kurs
                ));
            }
            $db->update('jurnal_header', array('posting_status'=>'REVERSED','updated_by'=>bp_user(),'updated_at'=>date('Y-m-d H:i:s')), 'id', $payment->journal_header_id);
            $db->update('erp_bank_payment', array('status'=>'REVERSED','reversal_journal_header_id'=>$revId,'reversed_by'=>bp_user(),'reversed_at'=>date('Y-m-d H:i:s'),'updated_by'=>bp_user(),'updated_at'=>date('Y-m-d H:i:s')), 'id', $payment->id);
            $db->query("COMMIT");
            bp_json('success', 'Bank payment berhasil direversal.');
            break;

        case 'excel':
            require_once __DIR__ . "/../../inc/lib/PHPExcel.php";
            require_once __DIR__ . "/../../inc/excel_style_helper.php";
            list($start,$end,$status,$category,$bankAccount) = bp_params();
            $excel = new PHPExcel();
            $sheet = $excel->setActiveSheetIndex(0);
            $sheet->setTitle(erp_export_sheet_title('Bank Payment'));
            $headers = array(erp_export_label("No"),erp_export_label("Posting Date"),erp_export_label("Bank Payment No"),erp_export_label("Category"),erp_export_label("Status"),erp_export_label("Bank Account"),erp_export_label("Offset Account"),erp_export_label("Payee"),erp_export_label("Method"),erp_export_label("Bank Ref"),erp_export_label("External Ref"),erp_export_label("Description"),erp_export_label("Amount"),erp_export_label("Currency"),erp_export_label("Journal No"));
            foreach ($headers as $i=>$label) $sheet->setCellValueByColumnAndRow($i,4,$label);
            $rows = bp_list_query($db,$start,$end,$status,$category,$bankAccount);
            $rowNo = 5; $no = 1;
            foreach ($rows as $r) {
                $values = array($no++,$r->posting_date,$r->bank_payment_no,$r->payment_category,$r->status,$r->bank_account.' - '.$r->bank_account_name,$r->offset_account.' - '.$r->offset_account_name,$r->payee_name,$r->payment_method,$r->bank_reference,$r->external_reference,$r->description,$r->amount,$r->currency,$r->no_jurnal);
                foreach ($values as $i=>$value) $sheet->setCellValueByColumnAndRow($i,$rowNo,$value);
                $rowNo++;
            }
            erpkb_excel_apply_standard_style($excel, array('sheet'=>$sheet,'title'=>erp_export_title('BANK PAYMENT'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$rowNo-1),'column_count'=>count($headers),'money_columns'=>array('M'),'filters'=>array('Periode'=>$start.' s/d '.$end,'Status'=>$status,'Category'=>$category,'Bank Account'=>$bankAccount)));
            while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="bank_payment_'.date('YmdHis').'.xlsx"');
            header('Cache-Control: max-age=0');
            PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save('php://output');
            exit;

        default:
            bp_json('error', 'Action tidak dikenal.');
    }
} catch (Exception $e) {
    if (isset($db)) $db->query("ROLLBACK");
    bp_json('error', $e->getMessage());
}
