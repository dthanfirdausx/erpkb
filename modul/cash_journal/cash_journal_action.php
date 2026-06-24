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
require_once __DIR__ . "/cash_journal_lib.php";
session_check_json();

function cj_json($status, $message = '', $extra = array())
{
    global $initialOutputBufferLevel;
    while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(array_merge(array('status'=>$status,'message'=>$message), $extra));
    exit;
}

function cj_params()
{
    $start = isset($_REQUEST['start_date']) ? $_REQUEST['start_date'] : date('Y-m-01');
    $end = isset($_REQUEST['end_date']) ? $_REQUEST['end_date'] : date('Y-m-d');
    if (!cj_valid_date($start) || !cj_valid_date($end) || $start > $end) throw new Exception('Periode tanggal tidak valid.');
    return array(
        $start,
        $end,
        isset($_REQUEST['status']) ? trim($_REQUEST['status']) : '',
        isset($_REQUEST['transaction_type']) ? trim($_REQUEST['transaction_type']) : '',
        isset($_REQUEST['cash_account']) ? trim($_REQUEST['cash_account']) : ''
    );
}

function cj_list_query($db, $start, $end, $status, $type, $cashAccount)
{
    $where = "posting_date BETWEEN ? AND ?";
    $params = array($start, $end);
    if ($status !== '') { $where .= " AND status=?"; $params[] = $status; }
    if ($type !== '') { $where .= " AND transaction_type=?"; $params[] = $type; }
    if ($cashAccount !== '') { $where .= " AND cash_account=?"; $params[] = $cashAccount; }
    return $db->query(
        "SELECT cj.*,ca.nama_rek cash_account_name,oa.nama_rek offset_account_name,cc.cost_center_code,pc.profit_center_code,tc.tax_code,jh.no_jurnal
         FROM erp_cash_journal cj
         LEFT JOIN rekening ca ON ca.no_rek=cj.cash_account
         LEFT JOIN rekening oa ON oa.no_rek=cj.offset_account
         LEFT JOIN erp_cost_center cc ON cc.id=cj.cost_center_id
         LEFT JOIN erp_profit_center pc ON pc.id=cj.profit_center_id
         LEFT JOIN erp_tax_code tc ON tc.id=cj.tax_code_id
         LEFT JOIN jurnal_header jh ON jh.id=cj.journal_header_id
         WHERE $where
         ORDER BY cj.posting_date DESC,cj.id DESC",
        $params
    );
}

$act = isset($_GET['act']) ? $_GET['act'] : '';

try {
    switch ($act) {
        case 'get_no':
            cj_json('success', 'OK', array('no'=>cj_next_no($db)));
            break;

        case 'save':
        case 'post':
            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if ($act === 'post' && $id && empty($_POST['cash_journal_no'])) {
                $cash = $db->fetch("SELECT * FROM erp_cash_journal WHERE id=? LIMIT 1", array($id));
                if (!$cash) throw new Exception('Cash journal tidak ditemukan.');
                if ($cash->status !== 'DRAFT') throw new Exception('Hanya draft yang bisa diposting.');
                cj_post_to_gl($db, $cash);
                cj_json('success', 'Cash journal berhasil diposting.', array('id'=>$id));
            }
            $cashNo = trim(isset($_POST['cash_journal_no']) ? $_POST['cash_journal_no'] : '');
            if ($cashNo === '') $cashNo = cj_next_no($db);
            $type = isset($_POST['transaction_type']) && $_POST['transaction_type'] === 'PAYMENT' ? 'PAYMENT' : 'RECEIPT';
            $docDate = trim(isset($_POST['document_date']) ? $_POST['document_date'] : date('Y-m-d'));
            $postingDate = trim(isset($_POST['posting_date']) ? $_POST['posting_date'] : date('Y-m-d'));
            if (!cj_valid_date($docDate) || !cj_valid_date($postingDate)) throw new Exception('Tanggal dokumen/posting tidak valid.');
            if ($act === 'post') {
                $period = cj_period_open($db, $postingDate);
                if ($period !== true) throw new Exception($period);
            }
            $cashAccount = trim($_POST['cash_account']);
            $offsetAccount = trim($_POST['offset_account']);
            if ($cashAccount === $offsetAccount) throw new Exception('Cash account dan offset account tidak boleh sama.');
            cj_account_leaf($db, $cashAccount, 'Cash account');
            cj_account_leaf($db, $offsetAccount, 'Offset account');
            $amount = cj_amount($_POST['amount']);
            if ($amount <= 0) throw new Exception('Amount wajib lebih dari nol.');

            $data = array(
                'cash_journal_no'=>$cashNo,
                'transaction_type'=>$type,
                'document_date'=>$docDate,
                'posting_date'=>$postingDate,
                'cash_account'=>$cashAccount,
                'offset_account'=>$offsetAccount,
                'amount'=>$amount,
                'currency'=>strtoupper(trim($_POST['currency'] ?: 'IDR')),
                'kurs'=>cj_amount($_POST['kurs'] ?: 1),
                'cost_center_id'=>$_POST['cost_center_id'] !== '' ? (int) $_POST['cost_center_id'] : null,
                'profit_center_id'=>$_POST['profit_center_id'] !== '' ? (int) $_POST['profit_center_id'] : null,
                'tax_code_id'=>$_POST['tax_code_id'] !== '' ? (int) $_POST['tax_code_id'] : null,
                'reference_no'=>trim($_POST['reference_no']),
                'description'=>trim($_POST['description']),
                'updated_by'=>cj_user(),
                'updated_at'=>date('Y-m-d H:i:s')
            );

            if ($id) {
                $existing = $db->fetch("SELECT * FROM erp_cash_journal WHERE id=? LIMIT 1", array($id));
                if (!$existing) throw new Exception('Cash journal tidak ditemukan.');
                if ($existing->status !== 'DRAFT') throw new Exception('Hanya draft yang boleh diedit.');
                $db->update('erp_cash_journal', $data, 'id', $id);
            } else {
                if ($db->fetch("SELECT id FROM erp_cash_journal WHERE cash_journal_no=? LIMIT 1", array($cashNo))) throw new Exception('Nomor cash journal sudah digunakan.');
                $data['status'] = 'DRAFT';
                $data['created_by'] = cj_user();
                $data['created_at'] = date('Y-m-d H:i:s');
                $db->insert('erp_cash_journal', $data);
                $id = $db->last_insert_id();
            }

            if ($act === 'post') {
                $cash = $db->fetch("SELECT * FROM erp_cash_journal WHERE id=? LIMIT 1", array($id));
                cj_post_to_gl($db, $cash);
                cj_json('success', 'Cash journal berhasil disimpan dan diposting.', array('id'=>$id));
            }
            cj_json('success', 'Draft cash journal berhasil disimpan.', array('id'=>$id));
            break;

        case 'get':
            $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
            $row = $db->fetch("SELECT * FROM erp_cash_journal WHERE id=? LIMIT 1", array($id));
            if (!$row) throw new Exception('Cash journal tidak ditemukan.');
            cj_json('success', 'OK', array('data'=>$row));
            break;

        case 'filter':
            list($start,$end,$status,$type,$cashAccount) = cj_params();
            $rows = cj_list_query($db, $start, $end, $status, $type, $cashAccount);
            $html = '';
            $receipt = 0; $payment = 0; $count = 0;
            foreach ($rows as $r) {
                $count++;
                if ($r->transaction_type === 'RECEIPT') $receipt += (float) $r->amount; else $payment += (float) $r->amount;
                $statusClass = $r->status === 'POSTED' ? 'success' : ($r->status === 'REVERSED' ? 'danger' : 'warning');
                $typeClass = $r->transaction_type === 'RECEIPT' ? 'success' : 'danger';
                $actions = '<div class="btn-group btn-group-xs">';
                $actions .= '<button class="btn btn-info cj-detail" data-id="'.$r->id.'"><i class="fa fa-search"></i></button>';
                if ($r->status === 'DRAFT') {
                    $actions .= '<button class="btn btn-primary cj-edit" data-id="'.$r->id.'"><i class="fa fa-pencil"></i></button>';
                    $actions .= '<button class="btn btn-success cj-post" data-id="'.$r->id.'"><i class="fa fa-check"></i></button>';
                }
                if ($r->status === 'POSTED') {
                    $actions .= '<button class="btn btn-warning cj-reverse" data-id="'.$r->id.'"><i class="fa fa-undo"></i></button>';
                }
                $actions .= '</div>';
                $html .= '<tr>'.
                    '<td>'.$count.'</td>'.
                    '<td>'.cj_h($r->posting_date).'</td>'.
                    '<td>'.cj_h($r->cash_journal_no).'</td>'.
                    '<td><span class="label label-'.$typeClass.'">'.cj_h($r->transaction_type).'</span></td>'.
                    '<td><span class="label label-'.$statusClass.'">'.cj_h($r->status).'</span></td>'.
                    '<td>'.cj_h($r->cash_account.' - '.$r->cash_account_name).'</td>'.
                    '<td>'.cj_h($r->offset_account.' - '.$r->offset_account_name).'</td>'.
                    '<td>'.cj_h($r->reference_no).'</td>'.
                    '<td>'.cj_h($r->description).'</td>'.
                    '<td class="text-right">'.cj_num($r->amount).'</td>'.
                    '<td>'.cj_h($r->currency).'</td>'.
                    '<td>'.$actions.'</td>'.
                    '</tr>';
            }
            if ($count === 0) $html = '<tr><td colspan="12" class="text-center text-muted">Tidak ada data.</td></tr>';
            cj_json('success', 'OK', array('html'=>$html,'receipt'=>cj_num($receipt),'payment'=>cj_num($payment),'balance'=>cj_num($receipt-$payment),'count'=>$count));
            break;

        case 'detail':
            $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
            $r = $db->fetch("SELECT cj.*,ca.nama_rek cash_account_name,oa.nama_rek offset_account_name,cc.cost_center_code,pc.profit_center_code,tc.tax_code,jh.no_jurnal FROM erp_cash_journal cj LEFT JOIN rekening ca ON ca.no_rek=cj.cash_account LEFT JOIN rekening oa ON oa.no_rek=cj.offset_account LEFT JOIN erp_cost_center cc ON cc.id=cj.cost_center_id LEFT JOIN erp_profit_center pc ON pc.id=cj.profit_center_id LEFT JOIN erp_tax_code tc ON tc.id=cj.tax_code_id LEFT JOIN jurnal_header jh ON jh.id=cj.journal_header_id WHERE cj.id=? LIMIT 1", array($id));
            if (!$r) throw new Exception('Cash journal tidak ditemukan.');
            $html = '<div class="row"><div class="col-md-3"><b>'.fin_h('common_no', 'No').'</b><br>'.cj_h($r->cash_journal_no).'</div><div class="col-md-3"><b>Type</b><br>'.cj_h($r->transaction_type).'</div><div class="col-md-3"><b>'.fin_h('common_status', 'Status').'</b><br>'.cj_h($r->status).'</div><div class="col-md-3"><b>Journal</b><br>'.cj_h($r->no_jurnal).'</div></div><hr>';
            $html .= '<table class="table table-bordered table-condensed"><tr><th>'.fin_h('finance_posting_date', 'Posting Date').'</th><td>'.cj_h($r->posting_date).'</td><th>'.fin_h('finance_document_date', 'Document Date').'</th><td>'.cj_h($r->document_date).'</td></tr><tr><th>Cash Account</th><td>'.cj_h($r->cash_account.' - '.$r->cash_account_name).'</td><th>Offset Account</th><td>'.cj_h($r->offset_account.' - '.$r->offset_account_name).'</td></tr><tr><th>'.fin_h('finance_amount', 'Amount').'</th><td class="text-right">'.cj_num($r->amount).' '.cj_h($r->currency).'</td><th>Kurs</th><td>'.cj_num($r->kurs).'</td></tr><tr><th>Cost Center</th><td>'.cj_h($r->cost_center_code).'</td><th>Profit Center</th><td>'.cj_h($r->profit_center_code).'</td></tr><tr><th>'.fin_h('finance_tax_code', 'Tax Code').'</th><td>'.cj_h($r->tax_code).'</td><th>'.fin_h('finance_reference', 'Reference').'</th><td>'.cj_h($r->reference_no).'</td></tr><tr><th>'.fin_h('finance_description', 'Description').'</th><td colspan="3">'.cj_h($r->description).'</td></tr></table>';
            cj_json('success', 'OK', array('html'=>$html));
            break;

        case 'reverse':
            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            $cash = $db->fetch("SELECT * FROM erp_cash_journal WHERE id=? LIMIT 1", array($id));
            if (!$cash || $cash->status !== 'POSTED' || !$cash->journal_header_id) throw new Exception('Hanya cash journal POSTED yang bisa direversal.');
            $period = cj_period_open($db, date('Y-m-d'));
            if ($period !== true) throw new Exception($period);
            $originalLines = $db->query("SELECT * FROM jurnal_detail WHERE id_header=? ORDER BY line_no,id", array($cash->journal_header_id));
            $db->query("START TRANSACTION");
            $db->insert('jurnal_header', array(
                'no_jurnal'=>generate_no_jurnal(),
                'document_type'=>'RV',
                'posting_status'=>'POSTED',
                'tgl_jurnal'=>date('Y-m-d'),
                'ket'=>'REVERSAL CASH JOURNAL: '.$cash->cash_journal_no,
                'no_bukti'=>'RV-'.$cash->cash_journal_no,
                'source_module'=>'CASH_JOURNAL_REVERSAL',
                'source_document_no'=>$cash->cash_journal_no,
                'reversal_of'=>$cash->journal_header_id,
                'username'=>cj_user(),
                'posted_by'=>cj_user(),
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
                    'line_text'=>'Reversal '.$cash->cash_journal_no,
                    'cost_center_id'=>$line->cost_center_id,
                    'profit_center_id'=>$line->profit_center_id,
                    'tax_code_id'=>$line->tax_code_id,
                    'debet'=>$line->kredit,
                    'kredit'=>$line->debet,
                    'valuta'=>$line->valuta,
                    'kurs'=>$line->kurs
                ));
            }
            $db->update('jurnal_header', array('posting_status'=>'REVERSED','updated_by'=>cj_user(),'updated_at'=>date('Y-m-d H:i:s')), 'id', $cash->journal_header_id);
            $db->update('erp_cash_journal', array('status'=>'REVERSED','reversal_journal_header_id'=>$revId,'reversed_by'=>cj_user(),'reversed_at'=>date('Y-m-d H:i:s'),'updated_by'=>cj_user(),'updated_at'=>date('Y-m-d H:i:s')), 'id', $cash->id);
            $db->query("COMMIT");
            cj_json('success', 'Cash journal berhasil direversal.');
            break;

        case 'excel':
            require_once __DIR__ . "/../../inc/lib/PHPExcel.php";
            require_once __DIR__ . "/../../inc/excel_style_helper.php";
            list($start,$end,$status,$type,$cashAccount) = cj_params();
            $excel = new PHPExcel();
            $sheet = $excel->setActiveSheetIndex(0);
            $sheet->setTitle(erp_export_sheet_title('Cash Journal'));
            $headers = array(erp_export_label("No"),erp_export_label("Posting Date"),erp_export_label("Cash Journal No"),erp_export_label("Type"),erp_export_label("Status"),erp_export_label("Cash Account"),erp_export_label("Offset Account"),erp_export_label("Reference"),erp_export_label("Description"),erp_export_label("Amount"),erp_export_label("Currency"),erp_export_label("Journal No"));
            foreach ($headers as $i=>$label) $sheet->setCellValueByColumnAndRow($i,4,$label);
            $rows = cj_list_query($db,$start,$end,$status,$type,$cashAccount);
            $rowNo = 5; $no = 1;
            foreach ($rows as $r) {
                $values = array($no++,$r->posting_date,$r->cash_journal_no,$r->transaction_type,$r->status,$r->cash_account.' - '.$r->cash_account_name,$r->offset_account.' - '.$r->offset_account_name,$r->reference_no,$r->description,$r->amount,$r->currency,$r->no_jurnal);
                foreach ($values as $i=>$value) $sheet->setCellValueByColumnAndRow($i,$rowNo,$value);
                $rowNo++;
            }
            erpkb_excel_apply_standard_style($excel, array('sheet'=>$sheet,'title'=>erp_export_title('CASH JOURNAL'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$rowNo-1),'column_count'=>count($headers),'money_columns'=>array('J'),'filters'=>array('Periode'=>$start.' s/d '.$end,'Status'=>$status,'Type'=>$type,'Cash Account'=>$cashAccount)));
            while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="cash_journal_'.date('YmdHis').'.xlsx"');
            header('Cache-Control: max-age=0');
            PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save('php://output');
            exit;

        default:
            cj_json('error', 'Action tidak dikenal.');
    }
} catch (Exception $e) {
    if (isset($db)) $db->query("ROLLBACK");
    cj_json('error', $e->getMessage());
}
?>
