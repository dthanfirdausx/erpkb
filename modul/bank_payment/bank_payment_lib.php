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
function bp_user()
{
    return isset($_SESSION['username']) && $_SESSION['username'] !== '' ? $_SESSION['username'] : 'system';
}

function bp_h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function bp_num($value)
{
    return number_format((float) $value, 2, '.', ',');
}

function bp_valid_date($date)
{
    $dt = DateTime::createFromFormat('Y-m-d', $date);
    return $dt && $dt->format('Y-m-d') === $date;
}

function bp_amount($value)
{
    return round((float) str_replace(',', '', trim((string) $value)), 2);
}

function bp_next_no($db)
{
    $prefix = 'BP/'.date('Y').'/'.date('m').'/';
    $row = $db->fetch("SELECT MAX(bank_payment_no) max_no FROM erp_bank_payment WHERE bank_payment_no LIKE ?", array($prefix.'%'));
    $next = 1;
    if ($row && $row->max_no) {
        $parts = explode('/', $row->max_no);
        $next = ((int) end($parts)) + 1;
    }
    return $prefix.str_pad($next, 4, '0', STR_PAD_LEFT);
}

function bp_period_open($db, $date)
{
    $period = $db->fetch("SELECT status FROM erp_financial_period WHERE ? BETWEEN start_date AND end_date LIMIT 1", array($date));
    if (!$period) return 'Fiscal period untuk tanggal '.$date.' belum dibuat.';
    if ($period->status !== 'OPEN') return 'Fiscal period tanggal '.$date.' status '.$period->status.', tidak boleh posting.';
    return true;
}

function bp_account_leaf($db, $account, $label)
{
    $row = $db->fetch(
        "SELECT r.no_rek FROM rekening r LEFT JOIN rekening child ON child.induk=r.no_rek WHERE r.no_rek=? AND child.no_rek IS NULL LIMIT 1",
        array($account)
    );
    if (!$row) throw new Exception($label.' tidak valid atau bukan akun detail.');
}

function bp_post_to_gl($db, $payment)
{
    $period = bp_period_open($db, $payment->posting_date);
    if ($period !== true) throw new Exception($period);
    bp_account_leaf($db, $payment->bank_account, 'Bank account');
    bp_account_leaf($db, $payment->offset_account, 'Offset account');
    if ((float) $payment->amount <= 0) throw new Exception('Amount wajib lebih dari nol.');

    $db->query("START TRANSACTION");
    try {
        if ($payment->journal_header_id) {
            $db->delete('jurnal_detail', 'id_header', $payment->journal_header_id);
            $idHeader = $payment->journal_header_id;
            $db->update('jurnal_header', array(
                'document_type'=>'KZ',
                'posting_status'=>'POSTED',
                'tgl_jurnal'=>$payment->posting_date,
                'ket'=>'BANK PAYMENT: '.$payment->description,
                'no_bukti'=>$payment->bank_payment_no,
                'source_module'=>'BANK_PAYMENT',
                'source_document_no'=>$payment->bank_payment_no,
                'username'=>bp_user(),
                'posted_by'=>bp_user(),
                'posted_at'=>date('Y-m-d H:i:s'),
                'updated_by'=>bp_user(),
                'updated_at'=>date('Y-m-d H:i:s')
            ), 'id', $idHeader);
        } else {
            $db->insert('jurnal_header', array(
                'no_jurnal'=>generate_no_jurnal(),
                'document_type'=>'KZ',
                'posting_status'=>'POSTED',
                'tgl_jurnal'=>$payment->posting_date,
                'ket'=>'BANK PAYMENT: '.$payment->description,
                'no_bukti'=>$payment->bank_payment_no,
                'source_module'=>'BANK_PAYMENT',
                'source_document_no'=>$payment->bank_payment_no,
                'username'=>bp_user(),
                'posted_by'=>bp_user(),
                'posted_at'=>date('Y-m-d H:i:s'),
                'tgl_insert'=>date('Y-m-d H:i:s')
            ));
            $idHeader = $db->last_insert_id();
        }

        $amount = round((float) $payment->amount, 2);
        $lines = array(
            array($payment->offset_account, $amount, 0, 'Offset bank payment'),
            array($payment->bank_account, 0, $amount, 'Outgoing bank')
        );
        $lineNo = 1;
        foreach ($lines as $line) {
            $db->insert('jurnal_detail', array(
                'id_header'=>$idHeader,
                'line_no'=>$lineNo++,
                'no_rek'=>$line[0],
                'line_text'=>$line[3].' '.$payment->bank_payment_no,
                'cost_center_id'=>$payment->cost_center_id,
                'profit_center_id'=>$payment->profit_center_id,
                'tax_code_id'=>$payment->tax_code_id,
                'debet'=>$line[1],
                'kredit'=>$line[2],
                'valuta'=>$payment->currency ?: 'IDR',
                'kurs'=>$payment->kurs ?: 1
            ));
        }

        $db->update('erp_bank_payment', array(
            'status'=>'POSTED',
            'journal_header_id'=>$idHeader,
            'posted_by'=>bp_user(),
            'posted_at'=>date('Y-m-d H:i:s'),
            'updated_by'=>bp_user(),
            'updated_at'=>date('Y-m-d H:i:s')
        ), 'id', $payment->id);

        $db->query("COMMIT");
        return $idHeader;
    } catch (Exception $e) {
        $db->query("ROLLBACK");
        throw $e;
    }
}
?>
