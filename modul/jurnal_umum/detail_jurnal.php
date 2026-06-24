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
include "../../inc/config.php";
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$header = $db->fetch("SELECT h.*, r.no_jurnal reversal_ref FROM jurnal_header h LEFT JOIN jurnal_header r ON r.id=h.reversal_of WHERE h.id=? LIMIT 1", array($id));
if (!$header) {
    echo '<div class="alert alert-danger">Jurnal tidak ditemukan.</div>';
    exit;
}
$badge = array('DRAFT'=>'warning','POSTED'=>'success','REVERSED'=>'danger');
$statusClass = isset($badge[$header->posting_status]) ? $badge[$header->posting_status] : 'default';
?>
<div class="row">
  <div class="col-md-3"><b><?=fin_h('finance_journal_no', 'Journal No');?></b><br><?=htmlspecialchars($header->no_jurnal,ENT_QUOTES,'UTF-8');?></div>
  <div class="col-md-2"><b>Doc Type</b><br><?=htmlspecialchars($header->document_type,ENT_QUOTES,'UTF-8');?></div>
  <div class="col-md-2"><b><?=fin_h('common_status', 'Status');?></b><br><span class="label label-<?=$statusClass;?>"><?=htmlspecialchars($header->posting_status,ENT_QUOTES,'UTF-8');?></span></div>
  <div class="col-md-2"><b><?=fin_h('finance_posting_date', 'Posting Date');?></b><br><?=htmlspecialchars($header->tgl_jurnal,ENT_QUOTES,'UTF-8');?></div>
  <div class="col-md-3"><b><?=fin_h('finance_reference', 'Reference');?></b><br><?=htmlspecialchars($header->no_bukti,ENT_QUOTES,'UTF-8');?></div>
</div>
<div class="row" style="margin-top:12px">
  <div class="col-md-3"><b>Source Module</b><br><?=htmlspecialchars($header->source_module,ENT_QUOTES,'UTF-8');?></div>
  <div class="col-md-3"><b>Source Document</b><br><?=htmlspecialchars($header->source_document_no,ENT_QUOTES,'UTF-8');?></div>
  <div class="col-md-3"><b>Posted By</b><br><?=htmlspecialchars($header->posted_by,ENT_QUOTES,'UTF-8');?> <?=htmlspecialchars($header->posted_at,ENT_QUOTES,'UTF-8');?></div>
  <div class="col-md-3"><b>Reversal Of</b><br><?=htmlspecialchars($header->reversal_ref,ENT_QUOTES,'UTF-8');?></div>
</div>
<div class="row" style="margin-top:12px">
  <div class="col-md-12"><b>Header Text</b><br><?=htmlspecialchars($header->ket,ENT_QUOTES,'UTF-8');?></div>
</div>
<hr>
<div class="table-responsive">
<table class="table table-bordered table-striped table-condensed">
  <thead>
    <tr class="bg-primary">
      <th>Line</th><th><?=fin_h('finance_coa', 'COA');?></th><th><?=fin_h('finance_account_name', 'Account Name');?></th><th>Line Text</th><th>Cost Center</th><th>Profit Center</th><th><?=fin_h('finance_tax', 'Tax');?></th><th class="text-right"><?=fin_h('finance_debit', 'Debit');?></th><th class="text-right"><?=fin_h('finance_credit', 'Credit');?></th><th>Curr</th><th class="text-right">Kurs</th>
    </tr>
  </thead>
  <tbody>
<?php
$totalDebet = 0;
$totalKredit = 0;
$detail = $db->query("
SELECT d.*,r.nama_rek,cc.cost_center_code,pc.profit_center_code,tc.tax_code
FROM jurnal_detail d
LEFT JOIN rekening r ON r.no_rek=d.no_rek
LEFT JOIN erp_cost_center cc ON cc.id=d.cost_center_id
LEFT JOIN erp_profit_center pc ON pc.id=d.profit_center_id
LEFT JOIN erp_tax_code tc ON tc.id=d.tax_code_id
WHERE d.id_header=?
ORDER BY d.line_no,d.id", array($id));
foreach ($detail as $row) {
    $totalDebet += (float) $row->debet;
    $totalKredit += (float) $row->kredit;
?>
    <tr>
      <td><?=htmlspecialchars($row->line_no,ENT_QUOTES,'UTF-8');?></td>
      <td><?=htmlspecialchars($row->no_rek,ENT_QUOTES,'UTF-8');?></td>
      <td><?=htmlspecialchars($row->nama_rek,ENT_QUOTES,'UTF-8');?></td>
      <td><?=htmlspecialchars($row->line_text,ENT_QUOTES,'UTF-8');?></td>
      <td><?=htmlspecialchars($row->cost_center_code,ENT_QUOTES,'UTF-8');?></td>
      <td><?=htmlspecialchars($row->profit_center_code,ENT_QUOTES,'UTF-8');?></td>
      <td><?=htmlspecialchars($row->tax_code,ENT_QUOTES,'UTF-8');?></td>
      <td class="text-right"><?=number_format($row->debet,2);?></td>
      <td class="text-right"><?=number_format($row->kredit,2);?></td>
      <td><?=htmlspecialchars($row->valuta,ENT_QUOTES,'UTF-8');?></td>
      <td class="text-right"><?=number_format($row->kurs,2);?></td>
    </tr>
<?php } ?>
  </tbody>
  <tfoot>
    <tr>
      <th colspan="7" class="text-right">TOTAL</th>
      <th class="text-right"><?=number_format($totalDebet,2);?></th>
      <th class="text-right"><?=number_format($totalKredit,2);?></th>
      <th colspan="2"></th>
    </tr>
  </tfoot>
</table>
</div>
