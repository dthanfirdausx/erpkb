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
function vi_user()
{
    return isset($_SESSION['username']) && $_SESSION['username'] !== '' ? $_SESSION['username'] : 'system';
}

function vi_h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function vi_num($value)
{
    return number_format((float) $value, 2, '.', ',');
}

function vi_valid_date($date)
{
    $dt = DateTime::createFromFormat('Y-m-d', $date);
    return $dt && $dt->format('Y-m-d') === $date;
}

function vi_amount($value)
{
    return round((float) str_replace(',', '', trim((string) $value)), 2);
}

function vi_next_no($db)
{
    $prefix = 'VI/'.date('Y').'/'.date('m').'/';
    $row = $db->fetch("SELECT MAX(vendor_invoice_no) max_no FROM erp_vendor_invoice WHERE vendor_invoice_no LIKE ?", array($prefix.'%'));
    $next = 1;
    if ($row && $row->max_no) {
        $parts = explode('/', $row->max_no);
        $next = ((int) end($parts)) + 1;
    }
    return $prefix.str_pad($next, 4, '0', STR_PAD_LEFT);
}

function vi_period_open($db, $date)
{
    $period = $db->fetch("SELECT status FROM erp_financial_period WHERE ? BETWEEN start_date AND end_date LIMIT 1", array($date));
    if (!$period) return 'Fiscal period untuk tanggal '.$date.' belum dibuat.';
    if ($period->status !== 'OPEN') return 'Fiscal period tanggal '.$date.' status '.$period->status.', tidak boleh posting.';
    return true;
}

function vi_account_leaf($db, $account, $label, $required = true)
{
    if (!$required && trim((string)$account) === '') return;
    $row = $db->fetch(
        "SELECT r.no_rek FROM rekening r LEFT JOIN rekening child ON child.induk=r.no_rek WHERE r.no_rek=? AND child.no_rek IS NULL LIMIT 1",
        array($account)
    );
    if (!$row) throw new Exception($label.' tidak valid atau bukan akun detail.');
}

function vi_post_to_gl($db, $invoice)
{
    $period = vi_period_open($db, $invoice->posting_date);
    if ($period !== true) throw new Exception($period);
    vi_account_leaf($db, $invoice->expense_account, 'Expense/clearing account');
    vi_account_leaf($db, $invoice->ap_account, 'AP account');
    vi_account_leaf($db, $invoice->tax_account, 'Tax account', (float)$invoice->tax_amount > 0);
    if ((float)$invoice->gross_amount <= 0) throw new Exception('Gross amount wajib lebih dari nol.');

    $db->query("START TRANSACTION");
    try {
        if ($invoice->journal_header_id) {
            $db->delete('jurnal_detail', 'id_header', $invoice->journal_header_id);
            $idHeader = $invoice->journal_header_id;
            $db->update('jurnal_header', array(
                'document_type'=>'KR',
                'posting_status'=>'POSTED',
                'tgl_jurnal'=>$invoice->posting_date,
                'ket'=>'VENDOR INVOICE: '.$invoice->description,
                'no_bukti'=>$invoice->vendor_invoice_no,
                'source_module'=>'VENDOR_INVOICE',
                'source_document_no'=>$invoice->vendor_invoice_no,
                'username'=>vi_user(),
                'posted_by'=>vi_user(),
                'posted_at'=>date('Y-m-d H:i:s'),
                'updated_by'=>vi_user(),
                'updated_at'=>date('Y-m-d H:i:s')
            ), 'id', $idHeader);
        } else {
            $db->insert('jurnal_header', array(
                'no_jurnal'=>generate_no_jurnal(),
                'document_type'=>'KR',
                'posting_status'=>'POSTED',
                'tgl_jurnal'=>$invoice->posting_date,
                'ket'=>'VENDOR INVOICE: '.$invoice->description,
                'no_bukti'=>$invoice->vendor_invoice_no,
                'source_module'=>'VENDOR_INVOICE',
                'source_document_no'=>$invoice->vendor_invoice_no,
                'username'=>vi_user(),
                'posted_by'=>vi_user(),
                'posted_at'=>date('Y-m-d H:i:s'),
                'tgl_insert'=>date('Y-m-d H:i:s')
            ));
            $idHeader = $db->last_insert_id();
        }

        $lineNo = 1;
        $lines = array(array($invoice->expense_account, (float)$invoice->net_amount, 0, 'Vendor invoice expense/clearing'));
        if ((float)$invoice->tax_amount > 0) {
            $lines[] = array($invoice->tax_account, (float)$invoice->tax_amount, 0, 'Vendor invoice input tax');
        }
        $lines[] = array($invoice->ap_account, 0, (float)$invoice->gross_amount, 'Vendor payable');
        foreach ($lines as $line) {
            $db->insert('jurnal_detail', array(
                'id_header'=>$idHeader,
                'line_no'=>$lineNo++,
                'no_rek'=>$line[0],
                'line_text'=>$line[3].' '.$invoice->vendor_invoice_no,
                'cost_center_id'=>$invoice->cost_center_id,
                'profit_center_id'=>$invoice->profit_center_id,
                'tax_code_id'=>$invoice->tax_code_id,
                'debet'=>round($line[1],2),
                'kredit'=>round($line[2],2),
                'valuta'=>$invoice->currency ?: 'IDR',
                'kurs'=>$invoice->kurs ?: 1
            ));
        }

        $db->update('erp_vendor_invoice', array(
            'status'=>'POSTED',
            'journal_header_id'=>$idHeader,
            'posted_by'=>vi_user(),
            'posted_at'=>date('Y-m-d H:i:s'),
            'updated_by'=>vi_user(),
            'updated_at'=>date('Y-m-d H:i:s')
        ), 'id', $invoice->id);

        $db->query("COMMIT");
        return $idHeader;
    } catch (Exception $e) {
        $db->query("ROLLBACK");
        throw $e;
    }
}
?>
