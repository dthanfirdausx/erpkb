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
function cj_user()
{
    return isset($_SESSION['username']) && $_SESSION['username'] !== '' ? $_SESSION['username'] : 'system';
}

function cj_h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function cj_num($value)
{
    return number_format((float) $value, 2, '.', ',');
}

function cj_valid_date($date)
{
    $dt = DateTime::createFromFormat('Y-m-d', $date);
    return $dt && $dt->format('Y-m-d') === $date;
}

function cj_amount($value)
{
    return round((float) str_replace(',', '', trim((string) $value)), 2);
}

function cj_next_no($db)
{
    $prefix = 'CJ/'.date('Y').'/'.date('m').'/';
    $row = $db->fetch("SELECT MAX(cash_journal_no) max_no FROM erp_cash_journal WHERE cash_journal_no LIKE ?", array($prefix.'%'));
    $next = 1;
    if ($row && $row->max_no) {
        $parts = explode('/', $row->max_no);
        $next = ((int) end($parts)) + 1;
    }
    return $prefix.str_pad($next, 4, '0', STR_PAD_LEFT);
}

function cj_period_open($db, $date)
{
    $period = $db->fetch("SELECT status FROM erp_financial_period WHERE ? BETWEEN start_date AND end_date LIMIT 1", array($date));
    if (!$period) return 'Fiscal period untuk tanggal '.$date.' belum dibuat.';
    if ($period->status !== 'OPEN') return 'Fiscal period tanggal '.$date.' status '.$period->status.', tidak boleh posting.';
    return true;
}

function cj_account_leaf($db, $account, $label)
{
    $row = $db->fetch(
        "SELECT r.no_rek FROM rekening r LEFT JOIN rekening child ON child.induk=r.no_rek WHERE r.no_rek=? AND child.no_rek IS NULL LIMIT 1",
        array($account)
    );
    if (!$row) throw new Exception($label.' tidak valid atau bukan akun detail.');
}

function cj_post_to_gl($db, $cash)
{
    $period = cj_period_open($db, $cash->posting_date);
    if ($period !== true) throw new Exception($period);

    cj_account_leaf($db, $cash->cash_account, 'Cash account');
    cj_account_leaf($db, $cash->offset_account, 'Offset account');
    if ((float) $cash->amount <= 0) throw new Exception('Amount wajib lebih dari nol.');

    $db->query("START TRANSACTION");
    try {
        if ($cash->journal_header_id) {
            $db->delete('jurnal_detail', 'id_header', $cash->journal_header_id);
            $idHeader = $cash->journal_header_id;
            $db->update('jurnal_header', array(
                'document_type'=>'SA',
                'posting_status'=>'POSTED',
                'tgl_jurnal'=>$cash->posting_date,
                'ket'=>'CASH JOURNAL: '.$cash->description,
                'no_bukti'=>$cash->cash_journal_no,
                'source_module'=>'CASH_JOURNAL',
                'source_document_no'=>$cash->cash_journal_no,
                'username'=>cj_user(),
                'posted_by'=>cj_user(),
                'posted_at'=>date('Y-m-d H:i:s'),
                'updated_by'=>cj_user(),
                'updated_at'=>date('Y-m-d H:i:s')
            ), 'id', $idHeader);
        } else {
            $db->insert('jurnal_header', array(
                'no_jurnal'=>generate_no_jurnal(),
                'document_type'=>'SA',
                'posting_status'=>'POSTED',
                'tgl_jurnal'=>$cash->posting_date,
                'ket'=>'CASH JOURNAL: '.$cash->description,
                'no_bukti'=>$cash->cash_journal_no,
                'source_module'=>'CASH_JOURNAL',
                'source_document_no'=>$cash->cash_journal_no,
                'username'=>cj_user(),
                'posted_by'=>cj_user(),
                'posted_at'=>date('Y-m-d H:i:s'),
                'tgl_insert'=>date('Y-m-d H:i:s')
            ));
            $idHeader = $db->last_insert_id();
        }

        $amount = round((float) $cash->amount, 2);
        if ($cash->transaction_type === 'RECEIPT') {
            $lines = array(
                array($cash->cash_account, $amount, 0, 'Cash receipt'),
                array($cash->offset_account, 0, $amount, 'Offset receipt')
            );
        } else {
            $lines = array(
                array($cash->offset_account, $amount, 0, 'Offset payment'),
                array($cash->cash_account, 0, $amount, 'Cash payment')
            );
        }

        $lineNo = 1;
        foreach ($lines as $line) {
            $db->insert('jurnal_detail', array(
                'id_header'=>$idHeader,
                'line_no'=>$lineNo++,
                'no_rek'=>$line[0],
                'line_text'=>$line[3].' '.$cash->cash_journal_no,
                'cost_center_id'=>$cash->cost_center_id,
                'profit_center_id'=>$cash->profit_center_id,
                'tax_code_id'=>$cash->tax_code_id,
                'debet'=>$line[1],
                'kredit'=>$line[2],
                'valuta'=>$cash->currency ?: 'IDR',
                'kurs'=>$cash->kurs ?: 1
            ));
        }

        $db->update('erp_cash_journal', array(
            'status'=>'POSTED',
            'journal_header_id'=>$idHeader,
            'posted_by'=>cj_user(),
            'posted_at'=>date('Y-m-d H:i:s'),
            'updated_by'=>cj_user(),
            'updated_at'=>date('Y-m-d H:i:s')
        ), 'id', $cash->id);

        $db->query("COMMIT");
        return $idHeader;
    } catch (Exception $e) {
        $db->query("ROLLBACK");
        throw $e;
    }
}
?>
