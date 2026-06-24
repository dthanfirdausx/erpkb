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
function br_user()
{
    return isset($_SESSION['username']) && $_SESSION['username'] !== '' ? $_SESSION['username'] : 'system';
}

function br_h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function br_num($value)
{
    return number_format((float) $value, 2, '.', ',');
}

function br_valid_date($date)
{
    $dt = DateTime::createFromFormat('Y-m-d', $date);
    return $dt && $dt->format('Y-m-d') === $date;
}

function br_amount($value)
{
    return round((float) str_replace(',', '', trim((string) $value)), 2);
}

function br_next_no($db)
{
    $prefix = 'BR/'.date('Y').'/'.date('m').'/';
    $row = $db->fetch("SELECT MAX(bank_receipt_no) max_no FROM erp_bank_receipt WHERE bank_receipt_no LIKE ?", array($prefix.'%'));
    $next = 1;
    if ($row && $row->max_no) {
        $parts = explode('/', $row->max_no);
        $next = ((int) end($parts)) + 1;
    }
    return $prefix.str_pad($next, 4, '0', STR_PAD_LEFT);
}

function br_period_open($db, $date)
{
    $period = $db->fetch("SELECT status FROM erp_financial_period WHERE ? BETWEEN start_date AND end_date LIMIT 1", array($date));
    if (!$period) return 'Fiscal period untuk tanggal '.$date.' belum dibuat.';
    if ($period->status !== 'OPEN') return 'Fiscal period tanggal '.$date.' status '.$period->status.', tidak boleh posting.';
    return true;
}

function br_account_leaf($db, $account, $label)
{
    $row = $db->fetch(
        "SELECT r.no_rek FROM rekening r LEFT JOIN rekening child ON child.induk=r.no_rek WHERE r.no_rek=? AND child.no_rek IS NULL LIMIT 1",
        array($account)
    );
    if (!$row) throw new Exception($label.' tidak valid atau bukan akun detail.');
}

function br_post_to_gl($db, $receipt)
{
    $period = br_period_open($db, $receipt->posting_date);
    if ($period !== true) throw new Exception($period);
    br_account_leaf($db, $receipt->bank_account, 'Bank account');
    br_account_leaf($db, $receipt->offset_account, 'Offset account');
    if ((float) $receipt->amount <= 0) throw new Exception('Amount wajib lebih dari nol.');

    $db->query("START TRANSACTION");
    try {
        if ($receipt->journal_header_id) {
            $db->delete('jurnal_detail', 'id_header', $receipt->journal_header_id);
            $idHeader = $receipt->journal_header_id;
            $db->update('jurnal_header', array(
                'document_type'=>'DZ',
                'posting_status'=>'POSTED',
                'tgl_jurnal'=>$receipt->posting_date,
                'ket'=>'BANK RECEIPT: '.$receipt->description,
                'no_bukti'=>$receipt->bank_receipt_no,
                'source_module'=>'BANK_RECEIPT',
                'source_document_no'=>$receipt->bank_receipt_no,
                'username'=>br_user(),
                'posted_by'=>br_user(),
                'posted_at'=>date('Y-m-d H:i:s'),
                'updated_by'=>br_user(),
                'updated_at'=>date('Y-m-d H:i:s')
            ), 'id', $idHeader);
        } else {
            $db->insert('jurnal_header', array(
                'no_jurnal'=>generate_no_jurnal(),
                'document_type'=>'DZ',
                'posting_status'=>'POSTED',
                'tgl_jurnal'=>$receipt->posting_date,
                'ket'=>'BANK RECEIPT: '.$receipt->description,
                'no_bukti'=>$receipt->bank_receipt_no,
                'source_module'=>'BANK_RECEIPT',
                'source_document_no'=>$receipt->bank_receipt_no,
                'username'=>br_user(),
                'posted_by'=>br_user(),
                'posted_at'=>date('Y-m-d H:i:s'),
                'tgl_insert'=>date('Y-m-d H:i:s')
            ));
            $idHeader = $db->last_insert_id();
        }

        $amount = round((float) $receipt->amount, 2);
        $lines = array(
            array($receipt->bank_account, $amount, 0, 'Incoming bank'),
            array($receipt->offset_account, 0, $amount, 'Offset incoming bank')
        );
        $lineNo = 1;
        foreach ($lines as $line) {
            $db->insert('jurnal_detail', array(
                'id_header'=>$idHeader,
                'line_no'=>$lineNo++,
                'no_rek'=>$line[0],
                'line_text'=>$line[3].' '.$receipt->bank_receipt_no,
                'cost_center_id'=>$receipt->cost_center_id,
                'profit_center_id'=>$receipt->profit_center_id,
                'tax_code_id'=>$receipt->tax_code_id,
                'debet'=>$line[1],
                'kredit'=>$line[2],
                'valuta'=>$receipt->currency ?: 'IDR',
                'kurs'=>$receipt->kurs ?: 1
            ));
        }

        $db->update('erp_bank_receipt', array(
            'status'=>'POSTED',
            'journal_header_id'=>$idHeader,
            'posted_by'=>br_user(),
            'posted_at'=>date('Y-m-d H:i:s'),
            'updated_by'=>br_user(),
            'updated_at'=>date('Y-m-d H:i:s')
        ), 'id', $receipt->id);

        $db->query("COMMIT");
        return $idHeader;
    } catch (Exception $e) {
        $db->query("ROLLBACK");
        throw $e;
    }
}
?>
