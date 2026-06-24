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
function brec_user()
{
    return isset($_SESSION['username']) && $_SESSION['username'] !== '' ? $_SESSION['username'] : 'system';
}

function brec_h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function brec_num($value)
{
    return number_format((float) $value, 2, '.', ',');
}

function brec_amount($value)
{
    return round((float) str_replace(',', '', trim((string) $value)), 2);
}

function brec_valid_date($date)
{
    $dt = DateTime::createFromFormat('Y-m-d', $date);
    return $dt && $dt->format('Y-m-d') === $date;
}

function brec_next_statement_no($db)
{
    $prefix = 'BS/'.date('Y').'/'.date('m').'/';
    $row = $db->fetch("SELECT MAX(statement_no) max_no FROM erp_bank_statement_line WHERE statement_no LIKE ?", array($prefix.'%'));
    $next = 1;
    if ($row && $row->max_no) {
        $parts = explode('/', $row->max_no);
        $next = ((int) end($parts)) + 1;
    }
    return $prefix.str_pad($next, 4, '0', STR_PAD_LEFT);
}

function brec_next_match_no($db)
{
    $prefix = 'BRM/'.date('Y').'/'.date('m').'/';
    $row = $db->fetch("SELECT MAX(match_no) max_no FROM erp_bank_reconciliation_match WHERE match_no LIKE ?", array($prefix.'%'));
    $next = 1;
    if ($row && $row->max_no) {
        $parts = explode('/', $row->max_no);
        $next = ((int) end($parts)) + 1;
    }
    return $prefix.str_pad($next, 4, '0', STR_PAD_LEFT);
}

function brec_account_leaf($db, $account, $label)
{
    $row = $db->fetch(
        "SELECT r.no_rek FROM rekening r LEFT JOIN rekening child ON child.induk=r.no_rek WHERE r.no_rek=? AND child.no_rek IS NULL LIMIT 1",
        array($account)
    );
    if (!$row) throw new Exception($label.' tidak valid atau bukan akun detail.');
}

function brec_erp_source_sql()
{
    return "
        SELECT 'BANK_RECEIPT' source_module, br.id source_id, br.bank_receipt_no document_no,
               br.posting_date, br.value_date, br.bank_account, br.bank_reference reference_no,
               br.payer_name partner_name, br.description, br.amount, br.currency, 'IN' direction, jh.no_jurnal
        FROM erp_bank_receipt br
        LEFT JOIN jurnal_header jh ON jh.id=br.journal_header_id
        WHERE br.status='POSTED'
        UNION ALL
        SELECT 'BANK_PAYMENT' source_module, bp.id source_id, bp.bank_payment_no document_no,
               bp.posting_date, bp.value_date, bp.bank_account, bp.bank_reference reference_no,
               bp.payee_name partner_name, bp.description, bp.amount, bp.currency, 'OUT' direction, jh.no_jurnal
        FROM erp_bank_payment bp
        LEFT JOIN jurnal_header jh ON jh.id=bp.journal_header_id
        WHERE bp.status='POSTED'
        UNION ALL
        SELECT 'VENDOR_PAYMENT' source_module, vp.id source_id, vp.vendor_payment_no document_no,
               vp.posting_date, vp.value_date, vp.bank_account, vp.bank_reference reference_no,
               COALESCE(v.nama, vp.vendor_code) partner_name, vp.description, vp.amount, vp.currency, 'OUT' direction, jh.no_jurnal
        FROM erp_vendor_payment vp
        LEFT JOIN pemasok v ON v.kode_pemasok=vp.vendor_code
        LEFT JOIN jurnal_header jh ON jh.id=vp.journal_header_id
        WHERE vp.status='POSTED'
        UNION ALL
        SELECT 'CASH_JOURNAL' source_module, cj.id source_id, cj.cash_journal_no document_no,
               cj.posting_date, cj.posting_date value_date, cj.cash_account bank_account, cj.reference_no reference_no,
               '' partner_name, cj.description, cj.amount, cj.currency,
               CASE WHEN cj.transaction_type='RECEIPT' THEN 'IN' ELSE 'OUT' END direction, jh.no_jurnal
        FROM erp_cash_journal cj
        LEFT JOIN jurnal_header jh ON jh.id=cj.journal_header_id
        WHERE cj.status='POSTED' AND (cj.cash_account LIKE '112%' OR cj.cash_account IN (SELECT no_rek FROM rekening WHERE nama_rek LIKE '%Bank%' OR nama_rek LIKE '%Giro%'))
    ";
}
?>
