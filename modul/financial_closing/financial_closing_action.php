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
session_start();
include '../../inc/config.php';
require_once __DIR__.'/financial_closing_helper.php';

function financial_closing_response($status, $message, $extra = array())
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array(array_merge(array('status' => $status, 'message' => $message, 'error_message' => $message), $extra)));
    exit();
}

if (empty($_SESSION['login'])) {
    financial_closing_response('die', 'Sesi login telah berakhir.');
}
$permission = financial_closing_permission($db, $_SESSION['group_level']);
if (!$permission || $permission->read_act !== 'Y') {
    financial_closing_response('error', 'Anda tidak memiliki akses Financial Closing.');
}
$act = isset($_POST['act']) ? $_POST['act'] : (isset($_GET['act']) ? $_GET['act'] : '');

if ($act === 'create') {
    if ($permission->insert_act !== 'Y') {
        financial_closing_response('error', 'Anda tidak memiliki izin membuat periode.');
    }
    $periodMonth = isset($_POST['period_month']) ? trim($_POST['period_month']) : '';
    if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $periodMonth)) {
        financial_closing_response('error', 'Format periode tidak valid.');
    }
    $startDate = $periodMonth.'-01';
    $endDate = date('Y-m-t', strtotime($startDate));
    $exists = $db->query('select id from erp_financial_period where period_code=? limit 1', array('period_code' => $periodMonth));
    if ($exists->rowCount() > 0) {
        financial_closing_response('error', 'Periode '.$periodMonth.' sudah tersedia.');
    }
    if (!$db->insert('erp_financial_period', array('period_code'=>$periodMonth,'start_date'=>$startDate,'end_date'=>$endDate,'status'=>'OPEN','notes'=>trim($_POST['notes']),'created_by'=>$_SESSION['username']))) {
        financial_closing_response('error', $db->getErrorMessage());
    }
    $periodId = $db->last_insert_id();
    foreach (financial_closing_defaults() as $item) {
        $db->insert('erp_financial_closing_checklist', array('period_id'=>$periodId,'checklist_code'=>$item['code'],'checklist_name'=>$item['name'],'sequence_no'=>$item['sequence'],'is_required'=>$item['required'],'is_completed'=>'N'));
    }
    financial_closing_response('good', 'Periode berhasil dibuat.', array('id'=>$periodId));
}

if ($permission->update_act !== 'Y') {
    financial_closing_response('error', 'Anda tidak memiliki izin memproses financial closing.');
}

if ($act === 'checklist') {
    $id = intval($_POST['id']);
    $item = $db->query('select c.*,p.status from erp_financial_closing_checklist c inner join erp_financial_period p on p.id=c.period_id where c.id=? limit 1', array('id'=>$id))->fetch();
    if (!$item || $item->status === 'CLOSED') {
        financial_closing_response('error', 'Checklist tidak dapat diubah.');
    }
    $completed = isset($_POST['completed']) && $_POST['completed']==='Y' ? 'Y' : 'N';
    $db->update('erp_financial_closing_checklist', array('is_completed'=>$completed,'completed_by'=>$completed==='Y'?$_SESSION['username']:null,'completed_at'=>$completed==='Y'?date('Y-m-d H:i:s'):null,'notes'=>trim($_POST['notes'])), 'id', $id);
    financial_closing_response('good', 'Checklist diperbarui.');
}

$periodId = intval(isset($_POST['period_id']) ? $_POST['period_id'] : 0);
$period = $db->query('select * from erp_financial_period where id=? limit 1', array('id'=>$periodId))->fetch();
if (!$period) {
    financial_closing_response('error', 'Periode tidak ditemukan.');
}

if ($act === 'start_closing') {
    if ($period->status === 'CLOSED') financial_closing_response('error', 'Periode sudah ditutup.');
    $db->update('erp_financial_period', array('status'=>'CLOSING'), 'id', $periodId);
    financial_closing_response('good', 'Periode masuk tahap closing.');
}

if ($act === 'close') {
    if ($period->status === 'CLOSED') financial_closing_response('error', 'Periode sudah ditutup.');
    if ($period->status !== 'CLOSING') financial_closing_response('error', 'Periode harus masuk tahap CLOSING sebelum ditutup.');
    $pending = $db->query("select count(*) total from erp_financial_closing_checklist where period_id=? and is_required='Y' and is_completed='N'", array('period_id'=>$periodId))->fetch();
    if (intval($pending->total) > 0) financial_closing_response('error', 'Masih ada '.$pending->total.' checklist wajib yang belum selesai.');
    $metrics = financial_closing_metrics($db, $period->start_date, $period->end_date);
    if ($metrics['unbalanced_count'] > 0) financial_closing_response('error', 'Terdapat jurnal tidak seimbang pada periode ini.');
    if ($metrics['open_document_count'] > 0) financial_closing_response('error', 'Masih ada '.$metrics['open_document_count'].' dokumen finance/tax berstatus draft pada periode ini.');
    $db->update('erp_financial_period', array('status'=>'CLOSED','closed_by'=>$_SESSION['username'],'closed_at'=>date('Y-m-d H:i:s')), 'id', $periodId);
    financial_closing_response('good', 'Periode berhasil ditutup.');
}

if ($act === 'reopen') {
    $reason = trim(isset($_POST['reason']) ? $_POST['reason'] : '');
    if ($period->status !== 'CLOSED') financial_closing_response('error', 'Hanya periode CLOSED yang dapat dibuka kembali.');
    if ($reason === '') financial_closing_response('error', 'Alasan reopen wajib diisi.');
    $notes = trim($period->notes."\nReopen: ".$reason);
    $db->update('erp_financial_period', array('status'=>'OPEN','notes'=>$notes,'reopened_by'=>$_SESSION['username'],'reopened_at'=>date('Y-m-d H:i:s')), 'id', $periodId);
    financial_closing_response('good', 'Periode berhasil dibuka kembali.');
}

financial_closing_response('error', 'Aksi tidak dikenali.');
?>
