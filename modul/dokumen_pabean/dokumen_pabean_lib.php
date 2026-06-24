<?php
function dpb_h($value){ return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function dpb_input($key,$default=''){
  if(isset($_POST[$key])) return trim((string)$_POST[$key]);
  if(isset($_GET[$key])) return trim((string)$_GET[$key]);
  return $default;
}
function dpb_valid_date($date,$default){ return preg_match('/^\d{4}-\d{2}-\d{2}$/', trim((string)$date)) ? trim((string)$date) : $default; }
function dpb_status_badge($status){
  $status = trim((string)$status);
  $upper = strtoupper($status);
  $class = 'default';
  if(in_array($upper,array('DRAFT','READY'))) $class = 'primary';
  elseif(in_array($upper,array('SENT','SUBMITTED','PROCESS'))) $class = 'info';
  elseif(in_array($upper,array('APPROVED','ACCEPTED','SUCCESS','SELESAI'))) $class = 'success';
  elseif(in_array($upper,array('REJECTED','FAILED','ERROR','CANCELLED'))) $class = 'danger';
  elseif($upper==='POSTED') $class = 'success';
  return '<span class="label label-'.$class.'">'.dpb_h($status ?: 'Draft').'</span>';
}
function dpb_filter_input(){
  return array(
    'tgl_awal'=>dpb_input('tgl_awal',date('Y-m-01')),
    'tgl_akhir'=>dpb_input('tgl_akhir',date('Y-m-d')),
    'jenis_bc'=>dpb_input('jenis_bc'),
    'status_dokumen'=>dpb_input('status_dokumen'),
    'keyword'=>dpb_input('keyword')
  );
}
function dpb_where($input,&$params){
  $where = " WHERE 1=1 ";
  $from = dpb_valid_date(isset($input['tgl_awal'])?$input['tgl_awal']:'',date('Y-m-01'));
  $to = dpb_valid_date(isset($input['tgl_akhir'])?$input['tgl_akhir']:'',date('Y-m-d'));
  $where .= " AND DATE(h.tanggalDokumen) BETWEEN ? AND ? ";
  $params[] = $from; $params[] = $to;
  if(!empty($input['jenis_bc'])){ $where .= " AND h.kodeDokumen=? "; $params[] = $input['jenis_bc']; }
  if(!empty($input['status_dokumen'])){ $where .= " AND h.statusDokumen=? "; $params[] = $input['status_dokumen']; }
  if(!empty($input['keyword'])){
    $kw = '%'.trim($input['keyword']).'%';
    $where .= " AND (h.nomorAju LIKE ? OR h.nomorDokpab LIKE ? OR h.statusDokumen LIKE ? OR d.nama_pendek LIKE ? OR d.nama_dokumen LIKE ?) ";
    for($i=0;$i<5;$i++) $params[] = $kw;
  }
  return $where;
}
function dpb_base_select(){
  return "SELECT h.id_header,h.uuid,h.kodeDokumen,h.nomorAju,h.nomorDokpab,h.tanggalDokumen,h.tanggalTtd,h.statusDokumen,
                 d.nama_pendek,d.nama_dokumen,
                 COALESCE(b.total_barang,0) total_barang,
                 COALESCE(b.total_qty,0) total_qty
          FROM ws_header h
          LEFT JOIN ref_dokumen d ON d.id_dokumen=h.kodeDokumen
          LEFT JOIN (
            SELECT id_header,COUNT(*) total_barang,COALESCE(SUM(jumlahSatuan),0) total_qty
            FROM ws_barang
            GROUP BY id_header
          ) b ON b.id_header=h.id_header";
}
function dpb_load_rows($db,$input){
  $params = array();
  $where = dpb_where($input,$params);
  return $db->query(dpb_base_select()." $where ORDER BY h.tanggalDokumen DESC,h.id_header DESC", $params);
}
function dpb_ref_dokumen($db){
  return $db->query("SELECT id_dokumen,nama_pendek,nama_dokumen FROM ref_dokumen WHERE id_dokumen IN ('23','25','261','262','27','30','40','41') ORDER BY FIELD(id_dokumen,'23','25','27','30','40','41','261','262')");
}
?>
