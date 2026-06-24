<?php
if (!function_exists('sd_t')) {
  function sd_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('sd_h')) {
  function sd_h($key, $fallback = '') { return htmlspecialchars((string) sd_t($key, $fallback), ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('sd_js')) {
  function sd_js($key, $fallback = '') { return json_encode(sd_t($key, $fallback), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); }
}
function br_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function br_num($v,$d=2){return number_format((float)$v,$d,',','.');}
function br_input($k,$d=''){if(isset($_POST[$k]))return trim((string)$_POST[$k]);if(isset($_GET[$k]))return trim((string)$_GET[$k]);return $d;}
function br_date($v,$d){$v=trim((string)$v);return preg_match('/^\d{4}-\d{2}-\d{2}$/',$v)?$v:$d;}
function br_badge($s,$c='default'){return '<span class="label label-'.$c.'">'.br_h($s).'</span>';}
function br_tax_badge($tax){return ($tax==='1')?br_badge('TAXED','primary'):br_badge('NO TAX','default');}
function br_due_badge($r){
  if((int)$r->is_overdue===1)return br_badge('OVERDUE','danger');
  if($r->due_date)return br_badge('OPEN','info');
  return br_badge('NO TERM','default');
}
function br_term_days($term){
  if(preg_match('/(\d+)/',(string)$term,$m))return (int)$m[1];
  return 0;
}
function br_filters(){
  return array(
    'tgl_awal'=>br_date(br_input('tgl_awal',date('Y-m-01')),date('Y-m-01')),
    'tgl_akhir'=>br_date(br_input('tgl_akhir',date('Y-m-d')),date('Y-m-d')),
    'customer'=>br_input('customer'),
    'currency'=>br_input('currency'),
    'tax_status'=>br_input('tax_status'),
    'due_status'=>br_input('due_status'),
    'sales_person'=>br_input('sales_person'),
    'keyword'=>br_input('keyword')
  );
}
function br_base_sql(){
  return " FROM sales_invoice si
    LEFT JOIN penerima bill ON bill.kode_penerima=si.bill_to
    LEFT JOIN penerima ship ON ship.kode_penerima=si.ship_to
    LEFT JOIN sales_order so ON so.no_sales_order=si.no_sales_order
    LEFT JOIN surat_jalan sj ON BINARY sj.no_surat_jalan=BINARY si.no_do OR BINARY sj.no_invoice=BINARY si.no_sales_invoice
    LEFT JOIN (
      SELECT id_sales,COUNT(*) item_count,COALESCE(SUM(qty),0) total_qty,COALESCE(SUM(nilai),0) subtotal
      FROM sales_invoice_detail GROUP BY id_sales
    ) d ON d.id_sales=si.id_sales ";
}
function br_select_sql(){
  return "SELECT si.*,COALESCE(NULLIF(bill.nama,''),si.bill_to) bill_name,bill.alamat bill_address,
    COALESCE(NULLIF(ship.nama,''),si.ship_to) ship_name,ship.alamat ship_address,
    so.so_date,so.delivery_date,so.sales_id,so.approval_status,COALESCE(sj.no_surat_jalan,si.no_do) surat_jalan_no,sj.posting_date sj_posting_date,sj.status sj_status,
    COALESCE(d.item_count,0) item_count,COALESCE(d.total_qty,0) total_qty,COALESCE(d.subtotal,0) subtotal,
    CASE WHEN si.tax='1' THEN COALESCE(d.subtotal,0)*0.11 ELSE 0 END tax_amount,
    COALESCE(d.subtotal,0) + CASE WHEN si.tax='1' THEN COALESCE(d.subtotal,0)*0.11 ELSE 0 END grand_total,
    CASE WHEN ".br_term_expr().">0 AND si.invoice_date IS NOT NULL THEN DATE_ADD(si.invoice_date, INTERVAL ".br_term_expr()." DAY) ELSE NULL END due_date,
    CASE WHEN ".br_term_expr().">0 AND DATE_ADD(si.invoice_date, INTERVAL ".br_term_expr()." DAY)<CURDATE() THEN DATEDIFF(CURDATE(),DATE_ADD(si.invoice_date, INTERVAL ".br_term_expr()." DAY)) ELSE 0 END overdue_days,
    CASE WHEN ".br_term_expr().">0 AND DATE_ADD(si.invoice_date, INTERVAL ".br_term_expr()." DAY)<CURDATE() THEN 1 ELSE 0 END is_overdue ";
}
function br_term_expr(){
  return "CAST(NULLIF(REGEXP_REPLACE(COALESCE(si.term,''),'[^0-9]',''),'') AS UNSIGNED)";
}
function br_filter_sql($f,&$p){
  $w=" WHERE si.invoice_date BETWEEN ? AND ? ";$p[]=$f['tgl_awal'];$p[]=$f['tgl_akhir'];
  if($f['customer']!==''){$w.=" AND si.bill_to=? ";$p[]=$f['customer'];}
  if($f['currency']!==''){$w.=" AND si.valuta=? ";$p[]=$f['currency'];}
  if($f['tax_status']==='TAXED')$w.=" AND si.tax='1' ";
  if($f['tax_status']==='NO_TAX')$w.=" AND (si.tax='0' OR si.tax IS NULL OR si.tax='') ";
  if($f['sales_person']!==''){$w.=" AND so.sales_id=? ";$p[]=$f['sales_person'];}
  if($f['due_status']==='OVERDUE')$w.=" AND ".br_term_expr().">0 AND DATE_ADD(si.invoice_date, INTERVAL ".br_term_expr()." DAY)<CURDATE() ";
  if($f['due_status']==='NOT_DUE')$w.=" AND (".br_term_expr()."=0 OR DATE_ADD(si.invoice_date, INTERVAL ".br_term_expr()." DAY)>=CURDATE()) ";
  if($f['keyword']!==''){$kw='%'.$f['keyword'].'%';$w.=" AND (si.no_sales_invoice LIKE ? OR si.invoice_no LIKE ? OR si.no_sales_order LIKE ? OR si.no_do LIKE ? OR si.nopo LIKE ? OR bill.nama LIKE ? OR ship.nama LIKE ? OR d.subtotal LIKE ?) ";for($i=0;$i<8;$i++)$p[]=$kw;}
  return $w;
}
function br_load_rows($db,$f,$limit=0,$offset=0){
  $p=array();$sql=br_select_sql().br_base_sql().br_filter_sql($f,$p)." ORDER BY si.invoice_date DESC,si.id_sales DESC";
  if($limit>0)$sql.=" LIMIT ".(int)$offset.",".(int)$limit;
  return $db->query($sql,$p);
}
function br_count_rows($db,$f){$p=array();$r=$db->fetch("SELECT COUNT(*) total ".br_base_sql().br_filter_sql($f,$p),$p);return $r?(int)$r->total:0;}
function br_summary($db,$f){$p=array();return $db->fetch("SELECT COUNT(*) invoice_count,COALESCE(SUM(d.subtotal),0) subtotal,COALESCE(SUM(CASE WHEN si.tax='1' THEN d.subtotal*0.11 ELSE 0 END),0) tax_amount,COALESCE(SUM(d.subtotal + CASE WHEN si.tax='1' THEN d.subtotal*0.11 ELSE 0 END),0) grand_total,SUM(CASE WHEN ".br_term_expr().">0 AND DATE_ADD(si.invoice_date, INTERVAL ".br_term_expr()." DAY)<CURDATE() THEN 1 ELSE 0 END) overdue_count ".br_base_sql().br_filter_sql($f,$p),$p);}
?>
