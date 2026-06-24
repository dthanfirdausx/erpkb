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
require_once __DIR__ . "/bank_receipt_lib.php";
session_check_json();

function br_json($status, $message = '', $extra = array())
{
    global $initialOutputBufferLevel;
    while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(array_merge(array('status'=>$status,'message'=>$message), $extra));
    exit;
}

function br_params()
{
    $start = isset($_REQUEST['start_date']) ? $_REQUEST['start_date'] : date('Y-m-01');
    $end = isset($_REQUEST['end_date']) ? $_REQUEST['end_date'] : date('Y-m-d');
    if (!br_valid_date($start) || !br_valid_date($end) || $start > $end) throw new Exception('Periode tanggal tidak valid.');
    return array(
        $start,
        $end,
        isset($_REQUEST['status']) ? trim($_REQUEST['status']) : '',
        isset($_REQUEST['receipt_category']) ? trim($_REQUEST['receipt_category']) : '',
        isset($_REQUEST['bank_account']) ? trim($_REQUEST['bank_account']) : ''
    );
}

function br_list_query($db, $start, $end, $status, $category, $bankAccount)
{
    $where = "br.posting_date BETWEEN ? AND ?";
    $params = array($start, $end);
    if ($status !== '') { $where .= " AND br.status=?"; $params[] = $status; }
    if ($category !== '') { $where .= " AND br.receipt_category=?"; $params[] = $category; }
    if ($bankAccount !== '') { $where .= " AND br.bank_account=?"; $params[] = $bankAccount; }

    return $db->query(
        "SELECT br.*,ba.nama_rek bank_account_name,oa.nama_rek offset_account_name,cc.cost_center_code,pc.profit_center_code,tc.tax_code,jh.no_jurnal
         FROM erp_bank_receipt br
         LEFT JOIN rekening ba ON ba.no_rek=br.bank_account
         LEFT JOIN rekening oa ON oa.no_rek=br.offset_account
         LEFT JOIN erp_cost_center cc ON cc.id=br.cost_center_id
         LEFT JOIN erp_profit_center pc ON pc.id=br.profit_center_id
         LEFT JOIN erp_tax_code tc ON tc.id=br.tax_code_id
         LEFT JOIN jurnal_header jh ON jh.id=br.journal_header_id
         WHERE $where
         ORDER BY br.posting_date DESC,br.id DESC",
        $params
    );
}

$act = isset($_GET['act']) ? $_GET['act'] : '';

try {
    switch ($act) {
        case 'get_no':
            br_json('success', 'OK', array('no'=>br_next_no($db)));
            break;

        case 'save':
        case 'post':
            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if ($act === 'post' && $id && empty($_POST['bank_receipt_no'])) {
                $receipt = $db->fetch("SELECT * FROM erp_bank_receipt WHERE id=? LIMIT 1", array($id));
                if (!$receipt) throw new Exception('Bank receipt tidak ditemukan.');
                if ($receipt->status !== 'DRAFT') throw new Exception('Hanya draft yang bisa diposting.');
                br_post_to_gl($db, $receipt);
                br_json('success', 'Bank receipt berhasil diposting.', array('id'=>$id));
            }

            $receiptNo = trim(isset($_POST['bank_receipt_no']) ? $_POST['bank_receipt_no'] : '');
            if ($receiptNo === '') $receiptNo = br_next_no($db);
            $category = isset($_POST['receipt_category']) ? $_POST['receipt_category'] : 'CUSTOMER';
            if (!in_array($category, array('CUSTOMER','OTHER','INTERCOMPANY','ADVANCE'))) $category = 'CUSTOMER';
            $docDate = trim(isset($_POST['document_date']) ? $_POST['document_date'] : date('Y-m-d'));
            $postingDate = trim(isset($_POST['posting_date']) ? $_POST['posting_date'] : date('Y-m-d'));
            $valueDate = trim(isset($_POST['value_date']) ? $_POST['value_date'] : '');
            if (!br_valid_date($docDate) || !br_valid_date($postingDate) || ($valueDate !== '' && !br_valid_date($valueDate))) throw new Exception('Tanggal dokumen/posting/value tidak valid.');
            if ($act === 'post') {
                $period = br_period_open($db, $postingDate);
                if ($period !== true) throw new Exception($period);
            }

            $bankAccount = trim($_POST['bank_account']);
            $offsetAccount = trim($_POST['offset_account']);
            if ($bankAccount === $offsetAccount) throw new Exception('Bank account dan offset account tidak boleh sama.');
            br_account_leaf($db, $bankAccount, 'Bank account');
            br_account_leaf($db, $offsetAccount, 'Offset account');
            $amount = br_amount($_POST['amount']);
            if ($amount <= 0) throw new Exception('Amount wajib lebih dari nol.');

            $data = array(
                'bank_receipt_no'=>$receiptNo,
                'receipt_category'=>$category,
                'document_date'=>$docDate,
                'posting_date'=>$postingDate,
                'value_date'=>$valueDate !== '' ? $valueDate : null,
                'bank_account'=>$bankAccount,
                'offset_account'=>$offsetAccount,
                'amount'=>$amount,
                'currency'=>strtoupper(trim($_POST['currency'] ?: 'IDR')),
                'kurs'=>br_amount($_POST['kurs'] ?: 1),
                'payer_name'=>trim($_POST['payer_name']),
                'bank_reference'=>trim($_POST['bank_reference']),
                'external_reference'=>trim($_POST['external_reference']),
                'cost_center_id'=>$_POST['cost_center_id'] !== '' ? (int) $_POST['cost_center_id'] : null,
                'profit_center_id'=>$_POST['profit_center_id'] !== '' ? (int) $_POST['profit_center_id'] : null,
                'tax_code_id'=>$_POST['tax_code_id'] !== '' ? (int) $_POST['tax_code_id'] : null,
                'description'=>trim($_POST['description']),
                'updated_by'=>br_user(),
                'updated_at'=>date('Y-m-d H:i:s')
            );

            if ($id) {
                $existing = $db->fetch("SELECT * FROM erp_bank_receipt WHERE id=? LIMIT 1", array($id));
                if (!$existing) throw new Exception('Bank receipt tidak ditemukan.');
                if ($existing->status !== 'DRAFT') throw new Exception('Hanya draft yang boleh diedit.');
                $db->update('erp_bank_receipt', $data, 'id', $id);
            } else {
                if ($db->fetch("SELECT id FROM erp_bank_receipt WHERE bank_receipt_no=? LIMIT 1", array($receiptNo))) throw new Exception('Nomor bank receipt sudah digunakan.');
                $data['status'] = 'DRAFT';
                $data['created_by'] = br_user();
                $data['created_at'] = date('Y-m-d H:i:s');
                $db->insert('erp_bank_receipt', $data);
                $id = $db->last_insert_id();
            }

            if ($act === 'post') {
                $receipt = $db->fetch("SELECT * FROM erp_bank_receipt WHERE id=? LIMIT 1", array($id));
                br_post_to_gl($db, $receipt);
                br_json('success', 'Bank receipt berhasil disimpan dan diposting.', array('id'=>$id));
            }
            br_json('success', 'Draft bank receipt berhasil disimpan.', array('id'=>$id));
            break;

        case 'get':
            $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
            $row = $db->fetch("SELECT * FROM erp_bank_receipt WHERE id=? LIMIT 1", array($id));
            if (!$row) throw new Exception('Bank receipt tidak ditemukan.');
            br_json('success', 'OK', array('data'=>$row));
            break;

        case 'filter':
            list($start,$end,$status,$category,$bankAccount) = br_params();
            $rows = br_list_query($db, $start, $end, $status, $category, $bankAccount);
            $html = '';
            $total = 0; $posted = 0; $draft = 0; $count = 0;
            foreach ($rows as $r) {
                $count++;
                $total += (float) $r->amount;
                if ($r->status === 'POSTED') $posted += (float) $r->amount;
                if ($r->status === 'DRAFT') $draft += (float) $r->amount;
                $statusClass = $r->status === 'POSTED' ? 'success' : ($r->status === 'REVERSED' ? 'danger' : 'warning');
                $actions = '<div class="btn-group btn-group-xs">';
                $actions .= '<button class="btn btn-info br-detail" data-id="'.$r->id.'"><i class="fa fa-search"></i></button>';
                if ($r->status === 'DRAFT') {
                    $actions .= '<button class="btn btn-primary br-edit" data-id="'.$r->id.'"><i class="fa fa-pencil"></i></button>';
                    $actions .= '<button class="btn btn-success br-post" data-id="'.$r->id.'"><i class="fa fa-check"></i></button>';
                }
                if ($r->status === 'POSTED') {
                    $actions .= '<button class="btn btn-warning br-reverse" data-id="'.$r->id.'"><i class="fa fa-undo"></i></button>';
                }
                $actions .= '</div>';
                $html .= '<tr>'.
                    '<td>'.$count.'</td>'.
                    '<td>'.br_h($r->posting_date).'</td>'.
                    '<td>'.br_h($r->bank_receipt_no).'</td>'.
                    '<td>'.br_h($r->receipt_category).'</td>'.
                    '<td><span class="label label-'.$statusClass.'">'.br_h($r->status).'</span></td>'.
                    '<td>'.br_h($r->bank_account.' - '.$r->bank_account_name).'</td>'.
                    '<td>'.br_h($r->payer_name).'</td>'.
                    '<td>'.br_h($r->bank_reference).'</td>'.
                    '<td>'.br_h($r->description).'</td>'.
                    '<td class="text-right">'.br_num($r->amount).'</td>'.
                    '<td>'.br_h($r->currency).'</td>'.
                    '<td>'.$actions.'</td>'.
                    '</tr>';
            }
            if ($count === 0) $html = '<tr><td colspan="12" class="text-center text-muted">Tidak ada data.</td></tr>';
            br_json('success', 'OK', array('html'=>$html,'total'=>br_num($total),'posted'=>br_num($posted),'draft'=>br_num($draft),'count'=>$count));
            break;

        case 'detail':
            $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
            $r = $db->fetch("SELECT br.*,ba.nama_rek bank_account_name,oa.nama_rek offset_account_name,cc.cost_center_code,pc.profit_center_code,tc.tax_code,jh.no_jurnal FROM erp_bank_receipt br LEFT JOIN rekening ba ON ba.no_rek=br.bank_account LEFT JOIN rekening oa ON oa.no_rek=br.offset_account LEFT JOIN erp_cost_center cc ON cc.id=br.cost_center_id LEFT JOIN erp_profit_center pc ON pc.id=br.profit_center_id LEFT JOIN erp_tax_code tc ON tc.id=br.tax_code_id LEFT JOIN jurnal_header jh ON jh.id=br.journal_header_id WHERE br.id=? LIMIT 1", array($id));
            if (!$r) throw new Exception('Bank receipt tidak ditemukan.');
            $html = '<div class="row"><div class="col-md-3"><b>'.fin_h('common_no', 'No').'</b><br>'.br_h($r->bank_receipt_no).'</div><div class="col-md-3"><b>Category</b><br>'.br_h($r->receipt_category).'</div><div class="col-md-3"><b>'.fin_h('common_status', 'Status').'</b><br>'.br_h($r->status).'</div><div class="col-md-3"><b>Journal</b><br>'.br_h($r->no_jurnal).'</div></div><hr>';
            $html .= '<table class="table table-bordered table-condensed"><tr><th>'.fin_h('finance_posting_date', 'Posting Date').'</th><td>'.br_h($r->posting_date).'</td><th>'.fin_h('finance_document_date', 'Document Date').'</th><td>'.br_h($r->document_date).'</td></tr><tr><th>Value Date</th><td>'.br_h($r->value_date).'</td><th>Payer</th><td>'.br_h($r->payer_name).'</td></tr><tr><th>Bank Account</th><td>'.br_h($r->bank_account.' - '.$r->bank_account_name).'</td><th>Offset Account</th><td>'.br_h($r->offset_account.' - '.$r->offset_account_name).'</td></tr><tr><th>'.fin_h('finance_amount', 'Amount').'</th><td class="text-right">'.br_num($r->amount).' '.br_h($r->currency).'</td><th>Kurs</th><td>'.br_num($r->kurs).'</td></tr><tr><th>Bank Ref</th><td>'.br_h($r->bank_reference).'</td><th>External Ref</th><td>'.br_h($r->external_reference).'</td></tr><tr><th>Cost Center</th><td>'.br_h($r->cost_center_code).'</td><th>Profit Center</th><td>'.br_h($r->profit_center_code).'</td></tr><tr><th>'.fin_h('finance_tax_code', 'Tax Code').'</th><td>'.br_h($r->tax_code).'</td><th>'.fin_h('finance_description', 'Description').'</th><td>'.br_h($r->description).'</td></tr></table>';
            br_json('success', 'OK', array('html'=>$html));
            break;

        case 'reverse':
            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            $receipt = $db->fetch("SELECT * FROM erp_bank_receipt WHERE id=? LIMIT 1", array($id));
            if (!$receipt || $receipt->status !== 'POSTED' || !$receipt->journal_header_id) throw new Exception('Hanya bank receipt POSTED yang bisa direversal.');
            $period = br_period_open($db, date('Y-m-d'));
            if ($period !== true) throw new Exception($period);
            $originalLines = $db->query("SELECT * FROM jurnal_detail WHERE id_header=? ORDER BY line_no,id", array($receipt->journal_header_id));
            $db->query("START TRANSACTION");
            $db->insert('jurnal_header', array(
                'no_jurnal'=>generate_no_jurnal(),
                'document_type'=>'RV',
                'posting_status'=>'POSTED',
                'tgl_jurnal'=>date('Y-m-d'),
                'ket'=>'REVERSAL BANK RECEIPT: '.$receipt->bank_receipt_no,
                'no_bukti'=>'RV-'.$receipt->bank_receipt_no,
                'source_module'=>'BANK_RECEIPT_REVERSAL',
                'source_document_no'=>$receipt->bank_receipt_no,
                'reversal_of'=>$receipt->journal_header_id,
                'username'=>br_user(),
                'posted_by'=>br_user(),
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
                    'line_text'=>'Reversal '.$receipt->bank_receipt_no,
                    'cost_center_id'=>$line->cost_center_id,
                    'profit_center_id'=>$line->profit_center_id,
                    'tax_code_id'=>$line->tax_code_id,
                    'debet'=>$line->kredit,
                    'kredit'=>$line->debet,
                    'valuta'=>$line->valuta,
                    'kurs'=>$line->kurs
                ));
            }
            $db->update('jurnal_header', array('posting_status'=>'REVERSED','updated_by'=>br_user(),'updated_at'=>date('Y-m-d H:i:s')), 'id', $receipt->journal_header_id);
            $db->update('erp_bank_receipt', array('status'=>'REVERSED','reversal_journal_header_id'=>$revId,'reversed_by'=>br_user(),'reversed_at'=>date('Y-m-d H:i:s'),'updated_by'=>br_user(),'updated_at'=>date('Y-m-d H:i:s')), 'id', $receipt->id);
            $db->query("COMMIT");
            br_json('success', 'Bank receipt berhasil direversal.');
            break;

        case 'excel':
            require_once __DIR__ . "/../../inc/lib/PHPExcel.php";
            require_once __DIR__ . "/../../inc/excel_style_helper.php";
            list($start,$end,$status,$category,$bankAccount) = br_params();
            $excel = new PHPExcel();
            $sheet = $excel->setActiveSheetIndex(0);
            $sheet->setTitle(erp_export_sheet_title('Bank Receipt'));
            $headers = array(erp_export_label("No"),erp_export_label("Posting Date"),erp_export_label("Bank Receipt No"),erp_export_label("Category"),erp_export_label("Status"),erp_export_label("Bank Account"),erp_export_label("Offset Account"),erp_export_label("Payer"),erp_export_label("Bank Ref"),erp_export_label("External Ref"),erp_export_label("Description"),erp_export_label("Amount"),erp_export_label("Currency"),erp_export_label("Journal No"));
            foreach ($headers as $i=>$label) $sheet->setCellValueByColumnAndRow($i,4,$label);
            $rows = br_list_query($db,$start,$end,$status,$category,$bankAccount);
            $rowNo = 5; $no = 1;
            foreach ($rows as $r) {
                $values = array($no++,$r->posting_date,$r->bank_receipt_no,$r->receipt_category,$r->status,$r->bank_account.' - '.$r->bank_account_name,$r->offset_account.' - '.$r->offset_account_name,$r->payer_name,$r->bank_reference,$r->external_reference,$r->description,$r->amount,$r->currency,$r->no_jurnal);
                foreach ($values as $i=>$value) $sheet->setCellValueByColumnAndRow($i,$rowNo,$value);
                $rowNo++;
            }
            erpkb_excel_apply_standard_style($excel, array('sheet'=>$sheet,'title'=>erp_export_title('BANK RECEIPT'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$rowNo-1),'column_count'=>count($headers),'money_columns'=>array('L'),'filters'=>array('Periode'=>$start.' s/d '.$end,'Status'=>$status,'Category'=>$category,'Bank Account'=>$bankAccount)));
            while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="bank_receipt_'.date('YmdHis').'.xlsx"');
            header('Cache-Control: max-age=0');
            PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save('php://output');
            exit;

        default:
            br_json('error', 'Action tidak dikenal.');
    }
} catch (Exception $e) {
    if (isset($db)) $db->query("ROLLBACK");
    br_json('error', $e->getMessage());
}
