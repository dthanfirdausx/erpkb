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
function financial_closing_defaults()
{
    return array(
        array('code' => 'JOURNAL_REVIEW', 'name' => 'Review jurnal umum dan adjustment journal', 'sequence' => 10, 'required' => 'Y'),
        array('code' => 'BANK_RECONCILIATION', 'name' => 'Rekonsiliasi kas dan bank', 'sequence' => 20, 'required' => 'Y'),
        array('code' => 'AP_RECONCILIATION', 'name' => 'Rekonsiliasi utang usaha', 'sequence' => 30, 'required' => 'Y'),
        array('code' => 'AR_RECONCILIATION', 'name' => 'Rekonsiliasi piutang usaha', 'sequence' => 40, 'required' => 'Y'),
        array('code' => 'INVENTORY_RECONCILIATION', 'name' => 'Rekonsiliasi persediaan dan mutasi stok', 'sequence' => 50, 'required' => 'Y'),
        array('code' => 'TAX_RECONCILIATION', 'name' => 'Rekonsiliasi pajak masukan dan keluaran', 'sequence' => 60, 'required' => 'Y'),
        array('code' => 'FINANCIAL_REPORT_REVIEW', 'name' => 'Review neraca dan laporan laba rugi', 'sequence' => 70, 'required' => 'Y'),
        array('code' => 'MANAGEMENT_APPROVAL', 'name' => 'Persetujuan manajemen atas penutupan periode', 'sequence' => 80, 'required' => 'Y'),
    );
}

function financial_closing_permission($db, $groupLevel)
{
    $result = $db->query(
        "select r.read_act, r.insert_act, r.update_act, r.delete_act
         from sys_menu_role r inner join sys_menu m on m.id=r.id_menu
         where r.group_level=? and m.url='financial-closing' limit 1",
        array('group_level' => $groupLevel)
    );
    return $result ? $result->fetch() : false;
}

function financial_closing_metrics($db, $startDate, $endDate)
{
    $journal = $db->query(
        "select count(*) journal_count,
                coalesce(sum(x.debet),0) total_debet,
                coalesce(sum(x.kredit),0) total_kredit,
                coalesce(sum(case when abs(x.debet-x.kredit)>0.005 then 1 else 0 end),0) unbalanced_count
         from (
            select h.id, coalesce(sum(d.debet),0) debet, coalesce(sum(d.kredit),0) kredit
            from jurnal_header h left join jurnal_detail d on d.id_header=h.id
            where h.tgl_jurnal between ? and ?
            group by h.id
         ) x",
        array('start_date' => $startDate, 'end_date' => $endDate)
    )->fetch();
    $adjustment = $db->query(
        "select count(distinct h.no_jurnal) adjustment_count,
                coalesce(sum(d.debet),0) adjustment_debet,
                coalesce(sum(d.kredit),0) adjustment_kredit
         from jurnal_header h
         left join jurnal_detail d on d.id_header=h.id
         where h.tgl_jurnal between ? and ?
           and (
                h.no_jurnal like 'AJE%'
                or h.no_bukti like 'AJE%'
                or lower(coalesce(h.ket,'')) like '%penyesuaian%'
                or lower(coalesce(h.ket,'')) like '%adjustment%'
           )",
        array('start_date' => $startDate, 'end_date' => $endDate)
    )->fetch();
    $inventory = $db->query(
        "select count(*) inventory_rows from closing where tgl_closing between ? and ?",
        array('start_date' => $startDate, 'end_date' => $endDate)
    )->fetch();
    $openDocs = 0;
    $checks = array(
        array("select count(*) total from erp_bank_receipt where posting_date between ? and ? and status='DRAFT'"),
        array("select count(*) total from erp_bank_payment where posting_date between ? and ? and status='DRAFT'"),
        array("select count(*) total from erp_vendor_payment where posting_date between ? and ? and status='DRAFT'"),
        array("select count(*) total from erp_incoming_payment where posting_date between ? and ? and status='DRAFT'"),
        array("select count(*) total from erp_vendor_invoice where posting_date between ? and ? and status='DRAFT'"),
        array("select count(*) total from sales_invoice where COALESCE(posting_date, invoice_date) between ? and ? and billing_status='DRAFT'"),
        array("select count(*) total from erp_tax_invoice where tax_invoice_date between ? and ? and status='DRAFT'"),
    );
    foreach ($checks as $check) {
        $row = $db->query($check[0], array('start_date' => $startDate, 'end_date' => $endDate));
        if ($row) {
            $openDocs += intval($row->fetch()->total);
        }
    }

    return array(
        'journal_count' => intval($journal->journal_count),
        'total_debet' => floatval($journal->total_debet),
        'total_kredit' => floatval($journal->total_kredit),
        'unbalanced_count' => intval($journal->unbalanced_count),
        'adjustment_count' => intval($adjustment->adjustment_count),
        'adjustment_debet' => floatval($adjustment->adjustment_debet),
        'adjustment_kredit' => floatval($adjustment->adjustment_kredit),
        'inventory_rows' => intval($inventory->inventory_rows),
        'open_document_count' => $openDocs,
    );
}
?>
