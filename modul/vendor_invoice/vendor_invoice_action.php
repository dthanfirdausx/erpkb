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
require_once "../../inc/lib/PHPExcel.php";
require_once "../../inc/excel_style_helper.php";
require_once "vendor_invoice_lib.php";
session_check_json();

function vi_json($status, $message = '', $extra = array())
{
    header('Content-Type: application/json');
    echo json_encode(array_merge(array('status'=>$status,'message'=>$message), $extra));
    exit;
}

function vi_params()
{
    $start = isset($_REQUEST['start_date']) ? $_REQUEST['start_date'] : date('Y-m-01');
    $end = isset($_REQUEST['end_date']) ? $_REQUEST['end_date'] : date('Y-m-d');
    if (!vi_valid_date($start) || !vi_valid_date($end) || $start > $end) throw new Exception('Periode tanggal tidak valid.');
    return array($start,$end,isset($_REQUEST['status'])?trim($_REQUEST['status']):'',isset($_REQUEST['payment_status'])?trim($_REQUEST['payment_status']):'',isset($_REQUEST['vendor_code'])?trim($_REQUEST['vendor_code']):'');
}

function vi_list_query($db,$start,$end,$status,$paymentStatus,$vendorCode)
{
    $where = "vi.posting_date BETWEEN ? AND ?";
    $params = array($start,$end);
    if ($status !== '') { $where .= " AND vi.status=?"; $params[] = $status; }
    if ($paymentStatus !== '') { $where .= " AND vi.payment_status=?"; $params[] = $paymentStatus; }
    if ($vendorCode !== '') { $where .= " AND vi.vendor_code=?"; $params[] = $vendorCode; }
    return $db->query(
        "SELECT vi.*,v.nama vendor_name,ea.nama_rek expense_account_name,aa.nama_rek ap_account_name,ta.nama_rek tax_account_name,tc.tax_code,jh.no_jurnal
         FROM erp_vendor_invoice vi
         LEFT JOIN pemasok v ON v.kode_pemasok=vi.vendor_code
         LEFT JOIN rekening ea ON ea.no_rek=vi.expense_account
         LEFT JOIN rekening aa ON aa.no_rek=vi.ap_account
         LEFT JOIN rekening ta ON ta.no_rek=vi.tax_account
         LEFT JOIN erp_tax_code tc ON tc.id=vi.tax_code_id
         LEFT JOIN jurnal_header jh ON jh.id=vi.journal_header_id
         WHERE $where
         ORDER BY vi.posting_date DESC,vi.id DESC",
        $params
    );
}

$act = isset($_GET['act']) ? $_GET['act'] : '';

try {
    switch ($act) {
        case 'get_no':
            vi_json('success','OK',array('no'=>vi_next_no($db)));
            break;

        case 'save':
        case 'post':
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if ($act === 'post' && $id && empty($_POST['vendor_invoice_no'])) {
                $invoice = $db->fetch("SELECT * FROM erp_vendor_invoice WHERE id=? LIMIT 1", array($id));
                if (!$invoice) throw new Exception('Vendor invoice tidak ditemukan.');
                if ($invoice->status !== 'DRAFT') throw new Exception('Hanya draft yang bisa diposting.');
                vi_post_to_gl($db,$invoice);
                vi_json('success','Vendor invoice berhasil diposting.',array('id'=>$id));
            }
            $invoiceNo = trim(isset($_POST['vendor_invoice_no']) ? $_POST['vendor_invoice_no'] : '');
            if ($invoiceNo === '') $invoiceNo = vi_next_no($db);
            $vendorCode = trim($_POST['vendor_code']);
            if (!$db->fetch("SELECT kode_pemasok FROM pemasok WHERE kode_pemasok=? LIMIT 1", array($vendorCode))) throw new Exception('Vendor tidak valid.');
            $invoiceType = isset($_POST['invoice_type']) ? $_POST['invoice_type'] : 'STANDARD';
            if (!in_array($invoiceType,array('STANDARD','DOWN_PAYMENT','CREDIT_MEMO','DEBIT_MEMO','OTHER'))) $invoiceType = 'STANDARD';
            $docDate = trim($_POST['document_date']);
            $postingDate = trim($_POST['posting_date']);
            $dueDate = trim(isset($_POST['due_date']) ? $_POST['due_date'] : '');
            if (!vi_valid_date($docDate) || !vi_valid_date($postingDate) || ($dueDate !== '' && !vi_valid_date($dueDate))) throw new Exception('Tanggal dokumen/posting/due date tidak valid.');
            if ($act === 'post') {
                $period = vi_period_open($db,$postingDate);
                if ($period !== true) throw new Exception($period);
            }
            $expenseAccount = trim($_POST['expense_account']);
            $apAccount = trim($_POST['ap_account']);
            $taxAccount = trim(isset($_POST['tax_account']) ? $_POST['tax_account'] : '');
            $net = vi_amount($_POST['net_amount']);
            $tax = vi_amount($_POST['tax_amount']);
            $gross = vi_amount($_POST['gross_amount']);
            if ($net <= 0) throw new Exception('Net amount wajib lebih dari nol.');
            if ($gross <= 0) throw new Exception('Gross amount wajib lebih dari nol.');
            if (round($net + $tax, 2) !== round($gross, 2)) throw new Exception('Gross amount harus sama dengan net + tax.');
            vi_account_leaf($db,$expenseAccount,'Expense/clearing account');
            vi_account_leaf($db,$apAccount,'AP account');
            vi_account_leaf($db,$taxAccount,'Tax account',$tax > 0);

            $data = array(
                'vendor_invoice_no'=>$invoiceNo,
                'vendor_code'=>$vendorCode,
                'vendor_reference_no'=>trim($_POST['vendor_reference_no']),
                'invoice_type'=>$invoiceType,
                'document_date'=>$docDate,
                'posting_date'=>$postingDate,
                'due_date'=>$dueDate !== '' ? $dueDate : null,
                'payment_term'=>trim($_POST['payment_term']),
                'reference_po'=>trim($_POST['reference_po']),
                'reference_gr'=>trim($_POST['reference_gr']),
                'expense_account'=>$expenseAccount,
                'ap_account'=>$apAccount,
                'tax_account'=>$taxAccount !== '' ? $taxAccount : null,
                'tax_code_id'=>$_POST['tax_code_id'] !== '' ? (int)$_POST['tax_code_id'] : null,
                'net_amount'=>$net,
                'tax_amount'=>$tax,
                'gross_amount'=>$gross,
                'currency'=>strtoupper(trim($_POST['currency'] ?: 'IDR')),
                'kurs'=>vi_amount($_POST['kurs'] ?: 1),
                'cost_center_id'=>$_POST['cost_center_id'] !== '' ? (int)$_POST['cost_center_id'] : null,
                'profit_center_id'=>$_POST['profit_center_id'] !== '' ? (int)$_POST['profit_center_id'] : null,
                'description'=>trim($_POST['description']),
                'updated_by'=>vi_user(),
                'updated_at'=>date('Y-m-d H:i:s')
            );
            if ($data['vendor_reference_no'] === '') throw new Exception('Vendor invoice reference wajib diisi.');
            if ($id) {
                $existing = $db->fetch("SELECT * FROM erp_vendor_invoice WHERE id=? LIMIT 1", array($id));
                if (!$existing) throw new Exception('Vendor invoice tidak ditemukan.');
                if ($existing->status !== 'DRAFT') throw new Exception('Hanya draft yang boleh diedit.');
                $db->update('erp_vendor_invoice',$data,'id',$id);
            } else {
                if ($db->fetch("SELECT id FROM erp_vendor_invoice WHERE vendor_invoice_no=? LIMIT 1", array($invoiceNo))) throw new Exception('Nomor vendor invoice sudah digunakan.');
                $data['status'] = 'DRAFT';
                $data['payment_status'] = 'OPEN';
                $data['created_by'] = vi_user();
                $data['created_at'] = date('Y-m-d H:i:s');
                $db->insert('erp_vendor_invoice',$data);
                $id = $db->last_insert_id();
            }
            if ($act === 'post') {
                $invoice = $db->fetch("SELECT * FROM erp_vendor_invoice WHERE id=? LIMIT 1", array($id));
                vi_post_to_gl($db,$invoice);
                vi_json('success','Vendor invoice berhasil disimpan dan diposting.',array('id'=>$id));
            }
            vi_json('success','Draft vendor invoice berhasil disimpan.',array('id'=>$id));
            break;

        case 'get':
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            $row = $db->fetch("SELECT * FROM erp_vendor_invoice WHERE id=? LIMIT 1", array($id));
            if (!$row) throw new Exception('Vendor invoice tidak ditemukan.');
            vi_json('success','OK',array('data'=>$row));
            break;

        case 'filter':
            list($start,$end,$status,$paymentStatus,$vendorCode)=vi_params();
            $rows = vi_list_query($db,$start,$end,$status,$paymentStatus,$vendorCode);
            $html=''; $total=0; $open=0; $posted=0; $count=0;
            foreach($rows as $r){
                $count++; $total+=(float)$r->gross_amount; if($r->payment_status==='OPEN')$open+=(float)$r->gross_amount; if($r->status==='POSTED')$posted+=(float)$r->gross_amount;
                $statusClass=$r->status==='POSTED'?'success':($r->status==='REVERSED'?'danger':'warning');
                $payClass=$r->payment_status==='PAID'?'success':($r->payment_status==='OPEN'?'warning':'info');
                $actions='<div class="btn-group btn-group-xs"><button class="btn btn-info vi-detail" data-id="'.$r->id.'"><i class="fa fa-search"></i></button>';
                if($r->status==='DRAFT'){$actions.='<button class="btn btn-primary vi-edit" data-id="'.$r->id.'"><i class="fa fa-pencil"></i></button><button class="btn btn-success vi-post" data-id="'.$r->id.'"><i class="fa fa-check"></i></button>';}
                if($r->status==='POSTED'){$actions.='<button class="btn btn-warning vi-reverse" data-id="'.$r->id.'"><i class="fa fa-undo"></i></button>';}
                $actions.='</div>';
                $html.='<tr><td>'.$count.'</td><td>'.vi_h($r->posting_date).'</td><td>'.vi_h($r->vendor_invoice_no).'</td><td>'.vi_h($r->vendor_reference_no).'</td><td>'.vi_h($r->vendor_code.' - '.$r->vendor_name).'</td><td><span class="label label-'.$statusClass.'">'.vi_h($r->status).'</span></td><td><span class="label label-'.$payClass.'">'.vi_h($r->payment_status).'</span></td><td>'.vi_h($r->reference_po).'</td><td class="text-right">'.vi_num($r->net_amount).'</td><td class="text-right">'.vi_num($r->tax_amount).'</td><td class="text-right">'.vi_num($r->gross_amount).'</td><td>'.vi_h($r->currency).'</td><td>'.$actions.'</td></tr>';
            }
            if($count===0)$html='<tr><td colspan="13" class="text-center text-muted">Tidak ada data.</td></tr>';
            vi_json('success','OK',array('html'=>$html,'total'=>vi_num($total),'open'=>vi_num($open),'posted'=>vi_num($posted),'count'=>$count));
            break;

        case 'detail':
            $id=isset($_GET['id'])?(int)$_GET['id']:0;
            $r=$db->fetch("SELECT vi.*,v.nama vendor_name,ea.nama_rek expense_account_name,aa.nama_rek ap_account_name,ta.nama_rek tax_account_name,tc.tax_code,jh.no_jurnal FROM erp_vendor_invoice vi LEFT JOIN pemasok v ON v.kode_pemasok=vi.vendor_code LEFT JOIN rekening ea ON ea.no_rek=vi.expense_account LEFT JOIN rekening aa ON aa.no_rek=vi.ap_account LEFT JOIN rekening ta ON ta.no_rek=vi.tax_account LEFT JOIN erp_tax_code tc ON tc.id=vi.tax_code_id LEFT JOIN jurnal_header jh ON jh.id=vi.journal_header_id WHERE vi.id=? LIMIT 1",array($id));
            if(!$r)throw new Exception('Vendor invoice tidak ditemukan.');
            $html='<div class="row"><div class="col-md-3"><b>'.fin_h('common_no', 'No').'</b><br>'.vi_h($r->vendor_invoice_no).'</div><div class="col-md-3"><b>Vendor Ref</b><br>'.vi_h($r->vendor_reference_no).'</div><div class="col-md-3"><b>'.fin_h('common_status', 'Status').'</b><br>'.vi_h($r->status).'</div><div class="col-md-3"><b>Journal</b><br>'.vi_h($r->no_jurnal).'</div></div><hr>';
            $html.='<table class="table table-bordered table-condensed"><tr><th>'.fin_h('finance_vendor', 'Vendor').'</th><td>'.vi_h($r->vendor_code.' - '.$r->vendor_name).'</td><th>Type</th><td>'.vi_h($r->invoice_type).'</td></tr><tr><th>'.fin_h('finance_posting_date', 'Posting Date').'</th><td>'.vi_h($r->posting_date).'</td><th>'.fin_h('finance_due_date', 'Due Date').'</th><td>'.vi_h($r->due_date).'</td></tr><tr><th>Expense/Clearing</th><td>'.vi_h($r->expense_account.' - '.$r->expense_account_name).'</td><th>AP Account</th><td>'.vi_h($r->ap_account.' - '.$r->ap_account_name).'</td></tr><tr><th>Tax Account</th><td>'.vi_h(trim($r->tax_account.' - '.$r->tax_account_name,' -')).'</td><th>'.fin_h('finance_tax_code', 'Tax Code').'</th><td>'.vi_h($r->tax_code).'</td></tr><tr><th>Net</th><td class="text-right">'.vi_num($r->net_amount).'</td><th>'.fin_h('finance_tax', 'Tax').'</th><td class="text-right">'.vi_num($r->tax_amount).'</td></tr><tr><th>Gross</th><td class="text-right">'.vi_num($r->gross_amount).' '.vi_h($r->currency).'</td><th>Kurs</th><td>'.vi_num($r->kurs).'</td></tr><tr><th>PO / GR</th><td>'.vi_h($r->reference_po.' / '.$r->reference_gr).'</td><th>'.fin_h('finance_payment', 'Payment').'</th><td>'.vi_h($r->payment_status).'</td></tr><tr><th>'.fin_h('finance_description', 'Description').'</th><td colspan="3">'.vi_h($r->description).'</td></tr></table>';
            vi_json('success','OK',array('html'=>$html));
            break;

        case 'reverse':
            $id=isset($_POST['id'])?(int)$_POST['id']:0;
            $invoice=$db->fetch("SELECT * FROM erp_vendor_invoice WHERE id=? LIMIT 1",array($id));
            if(!$invoice || $invoice->status!=='POSTED' || !$invoice->journal_header_id)throw new Exception('Hanya vendor invoice POSTED yang bisa direversal.');
            if($invoice->payment_status!=='OPEN')throw new Exception('Invoice yang sudah dibayar/partial tidak boleh reversal dari menu ini.');
            $period=vi_period_open($db,date('Y-m-d')); if($period!==true)throw new Exception($period);
            $lines=$db->query("SELECT * FROM jurnal_detail WHERE id_header=? ORDER BY line_no,id",array($invoice->journal_header_id));
            $db->query("START TRANSACTION");
            $db->insert('jurnal_header',array('no_jurnal'=>generate_no_jurnal(),'document_type'=>'RV','posting_status'=>'POSTED','tgl_jurnal'=>date('Y-m-d'),'ket'=>'REVERSAL VENDOR INVOICE: '.$invoice->vendor_invoice_no,'no_bukti'=>'RV-'.$invoice->vendor_invoice_no,'source_module'=>'VENDOR_INVOICE_REVERSAL','source_document_no'=>$invoice->vendor_invoice_no,'reversal_of'=>$invoice->journal_header_id,'username'=>vi_user(),'posted_by'=>vi_user(),'posted_at'=>date('Y-m-d H:i:s'),'tgl_insert'=>date('Y-m-d H:i:s')));
            $revId=$db->last_insert_id(); $lineNo=1;
            foreach($lines as $line){$db->insert('jurnal_detail',array('id_header'=>$revId,'line_no'=>$lineNo++,'no_rek'=>$line->no_rek,'line_text'=>'Reversal '.$invoice->vendor_invoice_no,'cost_center_id'=>$line->cost_center_id,'profit_center_id'=>$line->profit_center_id,'tax_code_id'=>$line->tax_code_id,'debet'=>$line->kredit,'kredit'=>$line->debet,'valuta'=>$line->valuta,'kurs'=>$line->kurs));}
            $db->update('jurnal_header',array('posting_status'=>'REVERSED','updated_by'=>vi_user(),'updated_at'=>date('Y-m-d H:i:s')),'id',$invoice->journal_header_id);
            $db->update('erp_vendor_invoice',array('status'=>'REVERSED','payment_status'=>'CANCELLED','reversal_journal_header_id'=>$revId,'reversed_by'=>vi_user(),'reversed_at'=>date('Y-m-d H:i:s'),'updated_by'=>vi_user(),'updated_at'=>date('Y-m-d H:i:s')),'id',$invoice->id);
            $db->query("COMMIT");
            vi_json('success','Vendor invoice berhasil direversal.');
            break;

        case 'excel':
            list($start,$end,$status,$paymentStatus,$vendorCode)=vi_params();
            $excel=new PHPExcel(); $sheet=$excel->setActiveSheetIndex(0); $sheet->setTitle(erp_export_sheet_title('Vendor Invoice'));
            $headers=array(erp_export_label("No"),erp_export_label("Posting Date"),erp_export_label("Invoice No"),erp_export_label("Vendor Ref"),erp_export_label("Vendor"),erp_export_label("Status"),erp_export_label("Payment Status"),erp_export_label("PO"),erp_export_label("GR"),erp_export_label("Net"),erp_export_label("Tax"),erp_export_label("Gross"),erp_export_label("Currency"),erp_export_label("Journal No"));
            foreach($headers as $i=>$label)$sheet->setCellValueByColumnAndRow($i,4,$label);
            $rows=vi_list_query($db,$start,$end,$status,$paymentStatus,$vendorCode); $rowNo=5; $no=1;
            foreach($rows as $r){$values=array($no++,$r->posting_date,$r->vendor_invoice_no,$r->vendor_reference_no,$r->vendor_code.' - '.$r->vendor_name,$r->status,$r->payment_status,$r->reference_po,$r->reference_gr,$r->net_amount,$r->tax_amount,$r->gross_amount,$r->currency,$r->no_jurnal);foreach($values as $i=>$value)$sheet->setCellValueByColumnAndRow($i,$rowNo,$value);$rowNo++;}
            erpkb_excel_apply_standard_style($excel,array('sheet'=>$sheet,'title'=>erp_export_title('VENDOR INVOICE'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$rowNo-1),'column_count'=>count($headers),'money_columns'=>array('J','K','L'),'filters'=>array('Periode'=>$start.' s/d '.$end,'Status'=>$status,'Payment Status'=>$paymentStatus,'Vendor'=>$vendorCode)));
            while(ob_get_level()>$initialOutputBufferLevel)ob_end_clean();
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="vendor_invoice_'.date('YmdHis').'.xlsx"');
            header('Cache-Control: max-age=0');
            PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save('php://output');
            exit;

        default:
            vi_json('error','Action tidak dikenal.');
    }
} catch (Exception $e) {
    if (isset($db)) $db->query("ROLLBACK");
    vi_json('error',$e->getMessage());
}
