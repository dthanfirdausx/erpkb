<?php
//print_r(login_ws());
ini_set('serialize_precision','-1');
function home_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function home_t($key,$fallback=''){return lang_text('home_'.$key,$fallback);}
function home_l($key,$fallback=''){return home_h(home_t($key,$fallback));}
function home_msg($key,$fallback='',$replacements=array()){
  $text=home_t($key,$fallback);
  foreach($replacements as $k=>$v)$text=str_replace('{'.$k.'}',(string)$v,$text);
  return $text;
}
function home_num($v,$dec=0){return number_format((float)$v,$dec,'.',',');}
function home_money($v){return number_format((float)$v,2,'.',',');}
function home_group_level(){return isset($_SESSION['group_level'])&&$_SESSION['group_level']!==''?$_SESSION['group_level']:'guest';}
function home_scalar($db,$sql,$params=array(),$default=0){
  try{$r=$db->fetch($sql,$params);if(!$r)return $default;$a=(array)$r;return count($a)?reset($a):$default;}catch(Exception $e){return $default;}
}
function home_rows($db,$sql,$params=array()){
  $out=array();try{$q=$db->query($sql,$params);foreach($q as $r)$out[]=$r;}catch(Exception $e){}return $out;
}
function home_table_exists($db,$table){
  static $cache=array();if(isset($cache[$table]))return $cache[$table];
  $r=home_scalar($db,"SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?",array($table),0);
  return $cache[$table]=((int)$r>0);
}
function home_last_days($days=7){
  $labels=array();
  for($i=$days-1;$i>=0;$i--)$labels[]=date('Y-m-d',strtotime("-$i day"));
  return $labels;
}
function home_pairs_from_rows($rows,$nameField,$valueField,$emptyName=null){
  if($emptyName===null)$emptyName=home_t('no_data','No Data');
  $out=array();
  foreach($rows as $r){
    $name=isset($r->$nameField)&&$r->$nameField!==''?$r->$nameField:$emptyName;
    $out[]=array($name,round((float)(isset($r->$valueField)?$r->$valueField:0),2));
  }
  if(!$out)$out[]=array($emptyName,0);
  return $out;
}
function home_status_pairs($db,$table,$statusField='status',$where='1=1'){
  if(!home_table_exists($db,$table))return array(array(home_t('no_data','No Data'),0));
  $rows=home_rows($db,"SELECT COALESCE($statusField,'N/A') status_name,COUNT(*) total FROM $table WHERE $where GROUP BY COALESCE($statusField,'N/A') ORDER BY total DESC",array());
  return home_pairs_from_rows($rows,'status_name','total');
}
function home_series_by_date($labels,$rows,$dateField,$valueField){
  $map=array();
  foreach($rows as $r)$map[$r->$dateField]=(float)$r->$valueField;
  $out=array();
  foreach($labels as $d)$out[]=isset($map[$d])?round($map[$d],2):0;
  return $out;
}
function home_widget_value($db,$code){
  $today=date('Y-m-d');$monthStart=date('Y-m-01');
  switch($code){
    case 'MGMT_COMPANY_SCORECARD':
      return array('value'=>'ERP','sub'=>home_t('sub_role_based_cockpit','Role based cockpit'),'trend'=>'active','class'=>'bg-navy');
    case 'MGMT_PROFIT_LOSS_MTD':
      $rev=home_scalar($db,"SELECT COALESCE(SUM(d.kredit-d.debet),0) FROM jurnal_header h JOIN jurnal_detail d ON d.id_header=h.id WHERE h.tgl_jurnal BETWEEN ? AND ? AND d.no_rek LIKE '4%'",array($monthStart,$today),0);
      $exp=home_scalar($db,"SELECT COALESCE(SUM(d.debet-d.kredit),0) FROM jurnal_header h JOIN jurnal_detail d ON d.id_header=h.id WHERE h.tgl_jurnal BETWEEN ? AND ? AND d.no_rek LIKE '5%'",array($monthStart,$today),0);
      return array('value'=>home_money((float)$rev-(float)$exp),'sub'=>home_t('sub_mtd_net_result','MTD net result'),'trend'=>(($rev-$exp)>=0?'positive':'negative'),'class'=>'bg-green');
    case 'FIN_CASH_BANK_BALANCE':
      $v=home_scalar($db,"SELECT COALESCE(SUM(d.debet-d.kredit),0) FROM jurnal_detail d WHERE d.no_rek LIKE '11%'",array(),0);
      return array('value'=>home_money($v),'sub'=>home_t('sub_cash_bank_ledger','Cash and bank ledger'),'trend'=>'neutral','class'=>'bg-aqua');
    case 'FIN_AR_OVERDUE':
      $v=home_scalar($db,"SELECT COALESCE(SUM(GREATEST(si.gross_amount-COALESCE((SELECT SUM(ip.amount) FROM erp_incoming_payment ip WHERE ip.sales_invoice_id=si.id_sales AND ip.status='POSTED'),0),0)),0) FROM sales_invoice si WHERE si.billing_status IN ('POSTED','DRAFT') AND COALESCE(si.due_date,si.invoice_date,si.posting_date)<?",array($today),0);
      return array('value'=>home_money($v),'sub'=>home_t('sub_customer_overdue','Customer overdue'),'trend'=>$v>0?'warning':'positive','class'=>'bg-yellow');
    case 'FIN_AP_OVERDUE':
      $v=home_table_exists($db,'erp_vendor_invoice')?home_scalar($db,"SELECT COALESCE(SUM(GREATEST(vi.gross_amount-COALESCE((SELECT SUM(vp.amount) FROM erp_vendor_payment vp WHERE vp.vendor_invoice_id=vi.id AND vp.status='POSTED'),0),0)),0) FROM erp_vendor_invoice vi WHERE vi.status='POSTED' AND COALESCE(vi.due_date,vi.document_date,vi.posting_date)<?",array($today),0):0;
      return array('value'=>home_money($v),'sub'=>home_t('sub_vendor_overdue','Vendor overdue'),'trend'=>$v>0?'warning':'positive','class'=>'bg-red');
    case 'FIN_VENDOR_INVOICE_DUE':
      $v=home_table_exists($db,'erp_vendor_invoice')?home_scalar($db,"SELECT COUNT(*) FROM erp_vendor_invoice WHERE status='POSTED' AND payment_status IN ('OPEN','PARTIAL') AND COALESCE(due_date,document_date,posting_date)<=?",array($today),0):0;
      return array('value'=>home_num($v),'sub'=>home_t('sub_due_vendor_invoices','Due vendor invoices'),'trend'=>$v>0?'warning':'positive','class'=>'bg-orange');
    case 'FIN_CUSTOMER_INVOICE_OPEN':
      $v=home_scalar($db,"SELECT COUNT(*) FROM sales_invoice WHERE billing_status IN ('POSTED','DRAFT') AND gross_amount>COALESCE((SELECT SUM(ip.amount) FROM erp_incoming_payment ip WHERE ip.sales_invoice_id=sales_invoice.id_sales AND ip.status='POSTED'),0)",array(),0);
      return array('value'=>home_num($v),'sub'=>home_t('sub_open_customer_invoices','Open customer invoices'),'trend'=>$v>0?'warning':'positive','class'=>'bg-blue');
    case 'FIN_JOURNAL_DRAFT':
      $v=home_scalar($db,"SELECT COUNT(*) FROM jurnal_header WHERE posting_status='DRAFT'",array(),0);
      return array('value'=>home_num($v),'sub'=>home_t('sub_unposted_journals','Unposted journals'),'trend'=>$v>0?'warning':'positive','class'=>'bg-yellow');
    case 'FIN_CLOSING_STATUS':
      $r=home_scalar($db,"SELECT status FROM erp_financial_period WHERE ? BETWEEN start_date AND end_date LIMIT 1",array($today),'N/A');
      return array('value'=>$r,'sub'=>home_t('sub_current_fiscal_period','Current fiscal period'),'trend'=>$r==='OPEN'?'positive':'warning','class'=>$r==='OPEN'?'bg-green':'bg-red');
    case 'FIN_VAT_POSITION':
      $v=home_table_exists($db,'erp_tax_invoice')?home_scalar($db,"SELECT COALESCE(SUM(CASE WHEN tax_direction='OUT' THEN vat_amount ELSE -vat_amount END),0) FROM erp_tax_invoice WHERE tax_period=?",array(date('Y-m')),0):0;
      return array('value'=>home_money($v),'sub'=>home_t('sub_vat_output_input','VAT output - input'),'trend'=>'neutral','class'=>'bg-teal');
    case 'WH_STOCK_VALUE':
      $v=home_table_exists($db,'stock_layer')?home_scalar($db,"SELECT COALESCE(SUM(qty_sisa),0) FROM stock_layer",array(),0):0;
      return array('value'=>home_num($v,2),'sub'=>home_t('sub_qty_on_hand','Qty on hand'),'trend'=>'neutral','class'=>'bg-green');
    case 'WH_STOCK_CRITICAL':
      $v=home_table_exists($db,'stock_layer')?home_scalar($db,"SELECT COUNT(*) FROM (SELECT kode,SUM(qty_sisa) qty FROM stock_layer GROUP BY kode HAVING qty<=0) x",array(),0):0;
      return array('value'=>home_num($v),'sub'=>home_t('sub_zero_negative_materials','Zero/negative materials'),'trend'=>$v>0?'warning':'positive','class'=>'bg-red');
    case 'WH_GR_TODAY':
      $v=home_table_exists($db,'stock_layer')?home_scalar($db,"SELECT COUNT(*) FROM stock_layer WHERE tgl_masuk=?",array($today),0):0;
      return array('value'=>home_num($v),'sub'=>home_t('sub_gr_layers_today','GR layers today'),'trend'=>'neutral','class'=>'bg-blue');
    case 'WH_GI_TODAY':
      $v=home_table_exists($db,'detail_transaksi')?home_scalar($db,"SELECT COUNT(*) FROM detail_transaksi WHERE qty<0 AND DATE(COALESCE(posting_date,date_created,NOW()))=?",array($today),0):0;
      return array('value'=>home_num($v),'sub'=>home_t('sub_gi_transactions_today','GI transactions today'),'trend'=>'neutral','class'=>'bg-orange');
    case 'WH_SLOW_MOVING':
      $v=home_table_exists($db,'stock_layer')?home_scalar($db,"SELECT COUNT(*) FROM stock_layer WHERE qty_sisa>0 AND tgl_masuk<DATE_SUB(?,INTERVAL 90 DAY)",array($today),0):0;
      return array('value'=>home_num($v),'sub'=>home_t('sub_stock_older_90_days','Stock older than 90 days'),'trend'=>$v>0?'warning':'positive','class'=>'bg-yellow');
    case 'WH_BLOCKED_STOCK':
    case 'QC_BLOCKED_RELEASE':
      $v=home_table_exists($db,'stock_layer')?home_scalar($db,"SELECT COALESCE(SUM(qty_sisa),0) FROM stock_layer WHERE stock_type='BLOCKED'",array(),0):0;
      return array('value'=>home_num($v,2),'sub'=>home_t('sub_blocked_stock_qty','Blocked stock qty'),'trend'=>$v>0?'warning':'positive','class'=>'bg-red');
    case 'WH_TRANSFER_PENDING':
      $v=0;
      foreach(array('erp_storage_location_transfer','erp_storage_bin_transfer','erp_stock_type_transfer') as $transferTable){
        if(home_table_exists($db,$transferTable)){
          $v+=(float)home_scalar($db,"SELECT COUNT(*) FROM $transferTable WHERE status='POSTED' AND posting_date=?",array($today),0);
        }
      }
      return array('value'=>home_num($v),'sub'=>home_t('sub_posted_transfers_today','Posted transfers today'),'trend'=>'neutral','class'=>'bg-aqua');
    case 'PUR_PR_PENDING':
      $v=home_table_exists($db,'purchase_requisition')?home_scalar($db,"SELECT COUNT(*) FROM purchase_requisition WHERE status IN ('DRAFT','SUBMITTED','PENDING','WAITING_APPROVAL')",array(),0):0;
      return array('value'=>home_num($v),'sub'=>home_t('sub_pr_waiting_process','PR waiting process'),'trend'=>$v>0?'warning':'positive','class'=>'bg-yellow');
    case 'PUR_PO_OUTSTANDING':
      $v=home_table_exists($db,'purchase_order')?home_scalar($db,"SELECT COUNT(*) FROM purchase_order",array(),0):0;
      return array('value'=>home_num($v),'sub'=>home_t('sub_po_documents','PO documents'),'trend'=>'neutral','class'=>'bg-blue');
    case 'PUR_VENDOR_EVALUATION':
      $v=home_table_exists($db,'erp_vendor_evaluation')?home_scalar($db,"SELECT COUNT(*) FROM erp_vendor_evaluation",array(),0):0;
      return array('value'=>home_num($v),'sub'=>home_t('sub_vendor_evaluations','Vendor evaluations'),'trend'=>'neutral','class'=>'bg-purple');
    case 'SD_SALES_ORDER_OPEN':
      $v=home_table_exists($db,'sales_order')?home_scalar($db,"SELECT COUNT(*) FROM sales_order",array(),0):0;
      return array('value'=>home_num($v),'sub'=>home_t('sub_sales_orders','Sales orders'),'trend'=>'neutral','class'=>'bg-blue');
    case 'SD_DELIVERY_PENDING':
      $v=home_table_exists($db,'erp_outbound_delivery')?home_scalar($db,"SELECT COUNT(*) FROM erp_outbound_delivery WHERE status NOT IN ('CANCELLED','COMPLETED')",array(),0):0;
      return array('value'=>home_num($v),'sub'=>home_t('sub_open_deliveries','Open deliveries'),'trend'=>$v>0?'warning':'positive','class'=>'bg-orange');
    case 'SD_BILLING_PENDING':
      $v=home_table_exists($db,'sales_invoice')?home_scalar($db,"SELECT COUNT(*) FROM sales_invoice WHERE billing_status='DRAFT'",array(),0):0;
      return array('value'=>home_num($v),'sub'=>home_t('sub_draft_billings','Draft billings'),'trend'=>$v>0?'warning':'positive','class'=>'bg-red');
    case 'PP_MATERIAL_SHORTAGE':
      $v=home_table_exists($db,'erp_material_requirement')?home_scalar($db,"SELECT COUNT(*) FROM erp_material_requirement",array(),0):0;
      return array('value'=>home_num($v),'sub'=>home_t('sub_material_requirements','Material requirements'),'trend'=>'neutral','class'=>'bg-yellow');
    case 'PP_PROD_ORDER_PLAN':
    case 'PRD_MY_ORDERS':
      $v=home_table_exists($db,'production_order')?home_scalar($db,"SELECT COUNT(*) FROM production_order WHERE status NOT IN ('CANCELLED','CLOSED','TECO')",array(),0):0;
      return array('value'=>home_num($v),'sub'=>home_t('sub_open_production_orders','Open production orders'),'trend'=>'neutral','class'=>'bg-blue');
    case 'PRD_OUTPUT_TODAY':
      $v=home_table_exists($db,'production_order_confirmation')?home_scalar($db,"SELECT COALESCE(SUM(yield_qty),0) FROM production_order_confirmation WHERE DATE(confirmation_date)=?",array($today),0):0;
      return array('value'=>home_num($v,2),'sub'=>home_t('sub_yield_today','Yield today'),'trend'=>'positive','class'=>'bg-green');
    case 'PRD_DOWNTIME_TODAY':
      $v=home_table_exists($db,'erp_production_downtime')?home_scalar($db,"SELECT COUNT(*) FROM erp_production_downtime WHERE downtime_date=?",array($today),0):0;
      return array('value'=>home_num($v),'sub'=>home_t('sub_downtime_records','Downtime records'),'trend'=>$v>0?'warning':'positive','class'=>'bg-red');
    case 'SYS_USER_ACTIVITY':
      $v=home_table_exists($db,'log_aktifitas')?home_scalar($db,"SELECT COUNT(*) FROM log_aktifitas WHERE DATE(tgl)=?",array($today),0):0;
      return array('value'=>home_num($v),'sub'=>home_t('sub_user_activities_today','User activities today'),'trend'=>'neutral','class'=>'bg-gray');
    case 'SYS_MENU_ROLE_COVERAGE':
      $v=home_scalar($db,"SELECT COUNT(*) FROM sys_menu m WHERE m.type_menu='page' AND NOT EXISTS (SELECT 1 FROM sys_menu_role r WHERE r.id_menu=m.id)",array(),0);
      return array('value'=>home_num($v),'sub'=>home_t('sub_menus_without_role','Menus without role'),'trend'=>$v>0?'warning':'positive','class'=>'bg-purple');
    default:
      return array('value'=>home_t('open','Open'),'sub'=>home_t('sub_click_for_detail','Click for detail'),'trend'=>'neutral','class'=>'bg-gray');
  }
}

$groupLevel=home_group_level();
if($groupLevel==='employee_self_service'){
  function home_ess_date($v){return (!$v||$v==='0000-00-00')?'-':date('d M Y',strtotime($v));}
  function home_ess_time($v){return (!$v||$v==='0000-00-00 00:00:00')?'-':date('H:i',strtotime($v));}
  function home_ess_workdays($from,$to){$s=strtotime($from);$e=strtotime($to);if(!$s||!$e||$e<$s)return 0;$n=0;for($t=$s;$t<=$e;$t=strtotime('+1 day',$t)){if((int)date('N',$t)<=5)$n++;}return $n;}
  function home_ess_label_class($v,$type='status'){
    if($type==='attendance_type'){
      $m=array('REGULAR'=>'success','OVERTIME'=>'primary','BUSINESS_TRIP'=>'info','TRAINING'=>'info','REMOTE'=>'default','LEAVE'=>'warning','SICK'=>'warning','ABSENT'=>'danger');
      return isset($m[$v])?$m[$v]:'default';
    }
    $m=array('DRAFT'=>'warning','RECORDED'=>'info','APPROVED'=>'success','POSTED'=>'primary','REJECTED'=>'danger','CANCELLED'=>'default');
    return isset($m[$v])?$m[$v]:'default';
  }

  $today=date('Y-m-d');$from=date('Y-m-01');$to=$today;$currentUserId=isset($_SESSION['id_user'])?(int)$_SESSION['id_user']:0;
  $employee=$currentUserId>0?$db->fetch("SELECT e.*,u.username,u.foto_user,d.nm_dept,jt.job_title_name,jt.job_level FROM erp_employee_master e LEFT JOIN sys_users u ON u.id=e.user_id LEFT JOIN dept d ON d.kd_dept=e.department_code LEFT JOIN erp_job_title jt ON jt.id=e.job_title_id WHERE e.user_id=? LIMIT 1",array($currentUserId)):null;
  $summary=array('total'=>0,'posted'=>0,'present_days'=>0,'absence_days'=>0,'leave_days'=>0,'sick_days'=>0,'late_count'=>0,'late_minutes'=>0,'early_count'=>0,'early_minutes'=>0,'planned_hours'=>0,'actual_hours'=>0,'overtime_hours'=>0);
  $chartCategories=array();$chartActual=array();$chartPlanned=array();$chartLate=array();$typeChart=array();$statusChart=array();$recentRows=array();
  if($employee){
    $params=array((int)$employee->id,$from,$to);
    $sumRow=$db->fetch("SELECT COUNT(*) total,SUM(attendance_status='POSTED') posted,SUM(attendance_type IN ('REGULAR','OVERTIME','BUSINESS_TRIP','TRAINING','REMOTE')) present_days,SUM(attendance_type='ABSENT') absence_days,SUM(attendance_type='LEAVE') leave_days,SUM(attendance_type='SICK') sick_days,SUM(late_minutes>0) late_count,COALESCE(SUM(late_minutes),0) late_minutes,SUM(early_leave_minutes>0) early_count,COALESCE(SUM(early_leave_minutes),0) early_minutes,COALESCE(SUM(planned_hours),0) planned_hours,COALESCE(SUM(actual_hours),0) actual_hours,COALESCE(SUM(overtime_hours),0) overtime_hours FROM erp_attendance WHERE employee_id=? AND attendance_date BETWEEN ? AND ?",$params);
    if($sumRow){foreach($summary as $k=>$v)$summary[$k]=isset($sumRow->$k)?(float)$sumRow->$k:0;}
    $trendRows=home_rows($db,"SELECT attendance_date,SUM(actual_hours) actual_hours,SUM(planned_hours) planned_hours,SUM(late_minutes) late_minutes FROM erp_attendance WHERE employee_id=? AND attendance_date BETWEEN ? AND ? GROUP BY attendance_date ORDER BY attendance_date",$params);
    $trendMap=array();foreach($trendRows as $r)$trendMap[$r->attendance_date]=$r;
    for($t=strtotime($from);$t<=strtotime($to);$t=strtotime('+1 day',$t)){$d=date('Y-m-d',$t);$chartCategories[]=date('d M',$t);$chartActual[]=isset($trendMap[$d])?(float)$trendMap[$d]->actual_hours:0;$chartPlanned[]=isset($trendMap[$d])?(float)$trendMap[$d]->planned_hours:0;$chartLate[]=isset($trendMap[$d])?(int)$trendMap[$d]->late_minutes:0;}
    $typeRows=home_rows($db,"SELECT attendance_type label,COUNT(*) total FROM erp_attendance WHERE employee_id=? AND attendance_date BETWEEN ? AND ? GROUP BY attendance_type ORDER BY total DESC",$params);
    foreach($typeRows as $r)$typeChart[]=array($r->label,(int)$r->total);
    if(!$typeChart)$typeChart[]=array(home_t('no_data','No Data'),0);
    $statusRows=home_rows($db,"SELECT attendance_status label,COUNT(*) total FROM erp_attendance WHERE employee_id=? AND attendance_date BETWEEN ? AND ? GROUP BY attendance_status ORDER BY total DESC",$params);
    foreach($statusRows as $r)$statusChart[]=array($r->label,(int)$r->total);
    if(!$statusChart)$statusChart[]=array(home_t('no_data','No Data'),0);
    $recentRows=home_rows($db,"SELECT a.*,s.nama_shift FROM erp_attendance a LEFT JOIN erp_shift s ON s.id=a.shift_id WHERE a.employee_id=? AND a.attendance_date BETWEEN ? AND ? ORDER BY a.attendance_date DESC,a.attendance_no DESC LIMIT 8",$params);
  }
  $expectedDays=home_ess_workdays($from,$to);
  $presenceRate=$expectedDays>0?min(100,round(($summary['present_days']/$expectedDays)*100,1)):0;
  $punctualRate=$summary['total']>0?max(0,round((($summary['total']-$summary['late_count'])/$summary['total'])*100,1)):0;
  $avgHours=$summary['total']>0?round($summary['actual_hours']/$summary['total'],2):0;
  $photoUrl=$employee?erpkb_user_photo_url($employee->foto_user,'back_profil_foto'):base_admin().'assets/dist/img/default-user-neutral.svg';
?>

<section class="content-header">
  <h1><?=home_l('employee_dashboard','Employee Dashboard');?> <small><?=home_l('self_service','Self Service');?></small></h1>
  <ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=home_l('home','Home');?></a></li><li class="active"><?=home_l('employee_dashboard','Employee Dashboard');?></li></ol>
</section>

<section class="content">
  <?php if(!$employee){ ?>
    <div class="alert alert-warning"><i class="fa fa-warning"></i> <?=home_l('employee_missing','Data employee untuk user ini belum ditemukan. Pastikan user login sudah dihubungkan ke erp_employee_master.user_id.');?></div>
  <?php }else{ ?>
  <div class="ess-home-hero">
    <div class="ess-home-profile">
      <img src="<?=home_h($photoUrl);?>" class="ess-home-photo" alt="<?=home_l('my_profile','Profile Photo');?>">
      <div>
        <h2><?=home_l('welcome','Selamat datang');?>, <?=home_h($employee->full_name);?></h2>
        <p><?=home_h($employee->employee_no);?> &bull; <?=home_h($employee->job_title_name?:'-');?> &bull; <?=home_h($employee->nm_dept?:'-');?></p>
        <p class="ess-home-sub"><?=home_h(home_msg('attendance_month_summary','Ringkasan attendance bulan ini: {from} sampai {to}.',array('from'=>home_ess_date($from),'to'=>home_ess_date($to))));?></p>
      </div>
      <div class="ess-home-actions">
        <a href="<?=base_index();?>my-profile" class="btn btn-default"><i class="fa fa-user"></i> <?=home_l('my_profile','My Profile');?></a>
        <a href="<?=base_index();?>my-attendance" class="btn btn-warning"><i class="fa fa-clock-o"></i> <?=home_l('my_attendance','My Attendance');?></a>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-md-3 col-sm-6"><div class="ess-home-kpi"><i class="fa fa-calendar-check-o"></i><span><?=home_l('presence_rate','Presence Rate');?></span><strong><?=home_h($presenceRate);?>%</strong><small><?=home_h(home_msg('present_workdays','{present} hadir dari {days} hari kerja',array('present'=>(int)$summary['present_days'],'days'=>$expectedDays)));?></small><div class="ess-home-progress"><b style="width:<?=home_h($presenceRate);?>%"></b></div></div></div>
    <div class="col-md-3 col-sm-6"><div class="ess-home-kpi"><i class="fa fa-clock-o blue"></i><span><?=home_l('punctual_rate','Punctual Rate');?></span><strong><?=home_h($punctualRate);?>%</strong><small><?=home_h(home_msg('late_minutes_summary','{count} kali terlambat, {minutes} menit',array('count'=>(int)$summary['late_count'],'minutes'=>(int)$summary['late_minutes'])));?></small><div class="ess-home-progress blue"><b style="width:<?=home_h($punctualRate);?>%"></b></div></div></div>
    <div class="col-md-3 col-sm-6"><div class="ess-home-kpi"><i class="fa fa-hourglass-half purple"></i><span><?=home_l('actual_hours','Actual Hours');?></span><strong><?=home_num($summary['actual_hours'],2);?> h</strong><small><?=home_h(home_msg('average_hours_record','Rata-rata {hours} jam / record',array('hours'=>home_num($avgHours,2))));?></small></div></div>
    <div class="col-md-3 col-sm-6"><div class="ess-home-kpi"><i class="fa fa-line-chart orange"></i><span><?=home_l('overtime','Overtime');?></span><strong><?=home_num($summary['overtime_hours'],2);?> h</strong><small><?=home_h(home_msg('early_leave_minutes','Early leave {minutes} menit',array('minutes'=>(int)$summary['early_minutes'])));?></small></div></div>
  </div>

  <div class="ess-home-note"><b><?=home_l('quick_info','Info cepat');?>:</b> <?=home_l('ess_note','dashboard ini hanya menampilkan data pribadi user yang sedang login. Untuk koreksi absen, gunakan alur request/koreksi agar tetap ada approval HR.');?></div>

  <div class="row">
    <div class="col-md-8"><div class="box ess-home-card"><div class="box-header"><h3 class="box-title"><i class="fa fa-area-chart"></i> <?=home_l('actual_vs_planned_hours','Actual vs Planned Hours');?></h3></div><div class="box-body"><div id="ess_home_hours_chart" class="ess-home-chart"></div></div></div></div>
    <div class="col-md-4"><div class="box ess-home-card"><div class="box-header"><h3 class="box-title"><i class="fa fa-pie-chart"></i> <?=home_l('attendance_type','Attendance Type');?></h3></div><div class="box-body"><div id="ess_home_type_chart" class="ess-home-chart-sm"></div></div></div></div>
  </div>

  <div class="row">
    <div class="col-md-7"><div class="box ess-home-card"><div class="box-header"><h3 class="box-title"><i class="fa fa-warning"></i> <?=home_l('late_minutes_trend','Late Minutes Trend');?></h3></div><div class="box-body"><div id="ess_home_late_chart" class="ess-home-chart-sm"></div></div></div></div>
    <div class="col-md-5"><div class="box ess-home-card"><div class="box-header"><h3 class="box-title"><i class="fa fa-check-square-o"></i> <?=home_l('posting_status','Posting Status');?></h3></div><div class="box-body"><div id="ess_home_status_chart" class="ess-home-chart-sm"></div></div></div></div>
  </div>

  <div class="box ess-home-card">
    <div class="box-header"><h3 class="box-title"><i class="fa fa-list"></i> <?=home_l('recent_attendance','Attendance Terbaru');?></h3><a href="<?=base_index();?>my-attendance" class="btn btn-xs btn-primary pull-right"><?=home_l('view_all','Lihat semua');?></a></div>
    <div class="box-body table-responsive">
      <table class="table table-bordered table-hover ess-home-table">
        <thead><tr><th><?=home_l('date','Tanggal');?></th><th><?=home_l('shift','Shift');?></th><th><?=home_l('plan_actual','Plan / Actual');?></th><th><?=home_l('hours','Hours');?></th><th><?=home_l('exception','Exception');?></th><th><?=home_l('type','Type');?></th><th><?=home_l('status','Status');?></th></tr></thead>
        <tbody>
          <?php if(!$recentRows){ ?><tr><td colspan="7" class="text-center text-muted"><?=home_l('no_attendance_this_month','Belum ada data attendance bulan ini.');?></td></tr><?php } ?>
          <?php foreach($recentRows as $r){ ?>
          <tr>
            <td><b><?=home_h(home_ess_date($r->attendance_date));?></b><br><small><?=home_h($r->attendance_no);?></small></td>
            <td><b><?=home_h($r->shift_code?:'-');?></b><br><small><?=home_h($r->nama_shift?:$r->assignment_no?:'-');?></small></td>
            <td><span class="ess-chip"><?=home_l('plan','Plan');?> <?=home_h(home_ess_time($r->planned_start));?>-<?=home_h(home_ess_time($r->planned_end));?></span> <span class="ess-chip"><?=home_l('actual','Actual');?> <?=home_h(home_ess_time($r->actual_clock_in));?>-<?=home_h(home_ess_time($r->actual_clock_out));?></span></td>
            <td><b><?=home_num($r->actual_hours,2);?> h</b><br><small><?=home_l('plan','Plan');?> <?=home_num($r->planned_hours,2);?> h | OT <?=home_num($r->overtime_hours,2);?> h</small></td>
            <td><span class="label label-<?=$r->late_minutes>0?'warning':'success';?>"><?=home_l('late','Late');?> <?=home_h((int)$r->late_minutes);?>m</span> <span class="label label-<?=$r->early_leave_minutes>0?'warning':'success';?>"><?=home_l('early','Early');?> <?=home_h((int)$r->early_leave_minutes);?>m</span></td>
            <td><span class="label label-<?=home_ess_label_class($r->attendance_type,'attendance_type');?>"><?=home_h($r->attendance_type);?></span></td>
            <td><span class="label label-<?=home_ess_label_class($r->attendance_status);?>"><?=home_h($r->attendance_status);?></span></td>
          </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php } ?>
</section>

<style>
.ess-home-hero{position:relative;overflow:hidden;border-radius:22px;background:linear-gradient(135deg,#0f766e,#2563eb);color:#fff;padding:24px;margin-bottom:18px;box-shadow:0 18px 40px rgba(37,99,235,.22)}.ess-home-hero:after{content:"";position:absolute;right:-80px;top:-110px;width:285px;height:285px;border-radius:50%;background:rgba(255,255,255,.13)}.ess-home-profile{position:relative;z-index:1;display:flex;align-items:center;gap:16px;flex-wrap:wrap}.ess-home-photo{width:74px;height:74px;object-fit:cover;border-radius:20px;border:3px solid rgba(255,255,255,.75);background:#fff;box-shadow:0 10px 24px rgba(15,23,42,.2)}.ess-home-profile h2{margin:0 0 6px;font-weight:800}.ess-home-profile p{margin:0;color:rgba(255,255,255,.88)}.ess-home-sub{margin-top:6px!important}.ess-home-actions{margin-left:auto;display:flex;gap:8px;flex-wrap:wrap}.ess-home-kpi{border:1px solid #e5edf5;border-radius:18px;background:#fff;padding:16px;min-height:118px;margin-bottom:16px;box-shadow:0 8px 22px rgba(15,23,42,.045)}.ess-home-kpi i{width:38px;height:38px;border-radius:14px;display:inline-flex;align-items:center;justify-content:center;margin-bottom:10px;color:#fff;background:#0f766e}.ess-home-kpi i.blue{background:#2563eb}.ess-home-kpi i.purple{background:#7c3aed}.ess-home-kpi i.orange{background:#f59e0b}.ess-home-kpi span{display:block;color:#64748b;font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.04em}.ess-home-kpi strong{display:block;color:#0f172a;font-size:25px;line-height:1.25}.ess-home-kpi small{color:#64748b}.ess-home-progress{height:9px;margin:9px 0 0;border-radius:99px;background:#e5e7eb;overflow:hidden}.ess-home-progress b{display:block;height:100%;background:linear-gradient(90deg,#0f766e,#22c55e)}.ess-home-progress.blue b{background:linear-gradient(90deg,#2563eb,#38bdf8)}.ess-home-card{border:1px solid #e5edf5;border-radius:18px;background:#fff;box-shadow:0 10px 26px rgba(15,23,42,.055);margin-bottom:18px}.ess-home-card .box-header{padding:16px 20px 8px;border-bottom:0}.ess-home-card .box-title{font-weight:800;color:#0f172a}.ess-home-card .box-body{padding:14px 20px 20px}.ess-home-chart{height:300px}.ess-home-chart-sm{height:260px}.ess-home-note{padding:13px 14px;border-radius:15px;background:#f8fafc;border:1px solid #e5edf5;color:#475569;margin-bottom:14px}.ess-home-table>thead>tr>th{background:#f8fafc;color:#334155;font-size:12px;text-transform:uppercase;letter-spacing:.03em}.ess-home-table>tbody>tr>td{vertical-align:middle;font-size:12px}.ess-chip{display:inline-block;padding:5px 8px;border-radius:999px;background:#f8fafc;border:1px solid #e5edf5;color:#334155;font-weight:700;margin:2px 0}@media(max-width:767px){.ess-home-actions{margin-left:0}.ess-home-chart,.ess-home-chart-sm{height:250px}}
</style>
<?php if($employee){ ?>
<script src="<?=base_url();?>assets/js/highcharts.js"></script>
<script>
Highcharts.setOptions({lang:{thousandsSep:','},colors:['#0f766e','#2563eb','#f59e0b','#dc2626','#7c3aed','#0891b2','#16a34a','#64748b']});
Highcharts.chart('ess_home_hours_chart',{chart:{type:'areaspline'},title:{text:null},xAxis:{categories:<?=json_encode($chartCategories);?>},yAxis:{title:{text:'<?=home_l('hours','Hours');?>'}},tooltip:{shared:true,valueDecimals:2},plotOptions:{areaspline:{fillOpacity:.15,marker:{enabled:true,radius:3}}},series:[{name:'<?=home_l('actual_hours','Actual Hours');?>',data:<?=json_encode($chartActual);?>},{name:'Planned <?=home_l('hours','Hours');?>',data:<?=json_encode($chartPlanned);?>}],credits:{enabled:false}});
Highcharts.chart('ess_home_type_chart',{chart:{type:'pie'},title:{text:null},tooltip:{pointFormat:'<b>{point.y}</b>'},plotOptions:{pie:{innerSize:'58%',dataLabels:{enabled:true,format:'{point.name}: {point.y}'}}},series:[{name:'<?=home_l('type','Type');?>',data:<?=json_encode($typeChart);?>}],credits:{enabled:false}});
Highcharts.chart('ess_home_late_chart',{chart:{type:'column'},title:{text:null},xAxis:{categories:<?=json_encode($chartCategories);?>},yAxis:{min:0,title:{text:'Minutes'},allowDecimals:false},legend:{enabled:false},tooltip:{valueSuffix:' min'},plotOptions:{column:{borderRadius:4}},series:[{name:'<?=home_l('late_minutes_trend','Late Minutes');?>',data:<?=json_encode($chartLate);?>,color:'#f59e0b'}],credits:{enabled:false}});
Highcharts.chart('ess_home_status_chart',{chart:{type:'bar'},title:{text:null},xAxis:{type:'category'},yAxis:{title:{text:'Record'},allowDecimals:false},legend:{enabled:false},series:[{name:'<?=home_l('status','Status');?>',data:<?=json_encode($statusChart);?>}],credits:{enabled:false}});
</script>
<?php } ?>
<?php return; } ?>
<?php
if($groupLevel==='beacukai'){
  function home_bc_fmt_date($v){return (!$v||$v==='0000-00-00'||$v==='0000-00-00 00:00:00')?'-':date('d M Y',strtotime($v));}
  function home_bc_status_label($v){
    $s=strtoupper(trim((string)$v));
    if($s==='DRAFT')return 'warning';
    if(in_array($s,array('SELESAI','APPROVED','VALID','RESPON','NOMOR PENDAFTARAN'),true))return 'success';
    if(in_array($s,array('REJECT','REJECTED','ERROR','GAGAL'),true))return 'danger';
    return 'info';
  }
  function home_bc_stock_qty($db,$where,$params=array()){
    if(!home_table_exists($db,'stock_layer'))return 0;
    return home_scalar($db,"SELECT COALESCE(SUM(sl.qty_sisa),0) FROM stock_layer sl LEFT JOIN barang b ON b.kd_barang=sl.kode LEFT JOIN erp_material_type mt ON mt.id=b.material_type_id WHERE sl.qty_sisa>0 AND $where",$params,0);
  }
  function home_bc_last_months($months=6){
    $labels=array();
    for($i=$months-1;$i>=0;$i--)$labels[]=date('Y-m',strtotime("first day of -$i month"));
    return $labels;
  }

  $today=date('Y-m-d');$monthStart=date('Y-m-01');
  $hasWsHeader=home_table_exists($db,'ws_header');
  $importDevisaExpr="COALESCE(NULLIF(cifRupiah,0),NULLIF(cif,0)*COALESCE(NULLIF(ndpbm,0),1),NULLIF(nilaiBarang,0)*COALESCE(NULLIF(ndpbm,0),1),0)";
  $exportDevisaExpr="COALESCE(NULLIF(fob,0)*COALESCE(NULLIF(ndpbm,0),1),NULLIF(nilaiBarang,0)*COALESCE(NULLIF(ndpbm,0),1),NULLIF(hargaPenyerahan,0),0)";
  $bc23Total=$hasWsHeader?home_scalar($db,"SELECT COUNT(*) FROM ws_header WHERE kodeDokumen='23'",array(),0):0;
  $bc41Total=$hasWsHeader?home_scalar($db,"SELECT COUNT(*) FROM ws_header WHERE kodeDokumen='41'",array(),0):0;
  $outstandingAju=$hasWsHeader?home_scalar($db,"SELECT COUNT(*) FROM ws_header WHERE nomorAju IS NOT NULL AND nomorAju<>'' AND (nomorDokpab IS NULL OR nomorDokpab='') AND UPPER(COALESCE(statusDokumen,'DRAFT')) IN ('DRAFT','KONSEP','READY','KIRIM','TERKIRIM','PENDING','PROCESS','PROSES')",array(),0):0;
  $importDevisaMtd=$hasWsHeader?home_scalar($db,"SELECT COALESCE(SUM($importDevisaExpr),0) FROM ws_header WHERE kodeDokumen='23' AND DATE(COALESCE(tanggalDokumen,tanggalAju,dateCreated)) BETWEEN ? AND ?",array($monthStart,$today),0):0;
  $exportDevisaMtd=$hasWsHeader?home_scalar($db,"SELECT COALESCE(SUM($exportDevisaExpr),0) FROM ws_header WHERE kodeDokumen='30' AND DATE(COALESCE(tanggalDokumen,tanggalAju,dateCreated)) BETWEEN ? AND ?",array($monthStart,$today),0):0;
  $wipQty=home_table_exists($db,'v_posisi_wip')?home_scalar($db,"SELECT COALESCE(SUM(saldo_akhir),0) FROM v_posisi_wip",array(),0):0;
  $fgQty=home_bc_stock_qty($db,"(mt.type_code='FERT' OR b.material_type_id=3 OR b.kd_kategori='K02' OR UPPER(COALESCE(b.type,'')) LIKE '%FINISHED%')");
  $rmQty=home_bc_stock_qty($db,"(mt.type_code='ROH' OR b.material_type_id=1 OR b.kd_kategori='K01' OR UPPER(COALESCE(b.type,'')) LIKE '%RAW%')");

  $docStatusRows=$hasWsHeader?home_rows($db,"SELECT COALESCE(NULLIF(statusDokumen,''),'DRAFT') status_name,COUNT(*) total FROM ws_header GROUP BY COALESCE(NULLIF(statusDokumen,''),'DRAFT') ORDER BY total DESC",array()):array();
  $docStatusData=home_pairs_from_rows($docStatusRows,'status_name','total',home_t('no_customs_documents','No Document'));
  $docTypeRows=$hasWsHeader?home_rows($db,"SELECT kodeDokumen doc_type,COUNT(*) total FROM ws_header GROUP BY kodeDokumen ORDER BY total DESC",array()):array();
  $docTypeData=home_pairs_from_rows($docTypeRows,'doc_type','total','No BC');
  $docTrendRows=$hasWsHeader?home_rows($db,"SELECT DATE(COALESCE(tanggalDokumen,tanggalAju,dateCreated)) trx_date,SUM(kodeDokumen='23') bc23,SUM(kodeDokumen='41') bc41,COUNT(*) total FROM ws_header WHERE DATE(COALESCE(tanggalDokumen,tanggalAju,dateCreated))>=DATE_SUB(CURDATE(),INTERVAL 6 DAY) GROUP BY DATE(COALESCE(tanggalDokumen,tanggalAju,dateCreated)) ORDER BY trx_date",array()):array();
  $bcDayLabels=home_last_days(7);
  $bc23Trend=home_series_by_date($bcDayLabels,$docTrendRows,'trx_date','bc23');
  $bc41Trend=home_series_by_date($bcDayLabels,$docTrendRows,'trx_date','bc41');
  $stockDocRows=home_table_exists($db,'stock_layer')?home_rows($db,"SELECT COALESCE(NULLIF(jenis_dokpab,''),'NO DOC') dokumen,COALESCE(SUM(qty_sisa),0) qty FROM stock_layer WHERE qty_sisa>0 GROUP BY COALESCE(NULLIF(jenis_dokpab,''),'NO DOC') ORDER BY qty DESC LIMIT 8",array()):array();
  $stockDocData=home_pairs_from_rows($stockDocRows,'dokumen','qty',home_t('no_stock','No Stock'));
  $stockPositionData=array(array(home_t('raw_material_position','Bahan Baku'),round((float)$rmQty,2)),array(home_t('wip_position','WIP'),round((float)$wipQty,2)),array(home_t('finished_goods_position','Barang Jadi'),round((float)$fgQty,2)));
  $devisaMonthLabels=home_bc_last_months(6);
  $devisaRows=$hasWsHeader?home_rows($db,"SELECT DATE_FORMAT(COALESCE(tanggalDokumen,tanggalAju,dateCreated),'%Y-%m') trx_month,COALESCE(SUM(CASE WHEN kodeDokumen='23' THEN $importDevisaExpr ELSE 0 END),0) impor,COALESCE(SUM(CASE WHEN kodeDokumen='30' THEN $exportDevisaExpr ELSE 0 END),0) ekspor FROM ws_header WHERE DATE(COALESCE(tanggalDokumen,tanggalAju,dateCreated))>=DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 5 MONTH),'%Y-%m-01') GROUP BY DATE_FORMAT(COALESCE(tanggalDokumen,tanggalAju,dateCreated),'%Y-%m') ORDER BY trx_month",array()):array();
  $devisaImporSeries=home_series_by_date($devisaMonthLabels,$devisaRows,'trx_month','impor');
  $devisaEksporSeries=home_series_by_date($devisaMonthLabels,$devisaRows,'trx_month','ekspor');
  $recentDocs=$hasWsHeader?home_rows($db,"SELECT kodeDokumen,nomorAju,nomorDokpab,tanggalDokumen,tanggalAju,tanggalTtd,statusDokumen,nilaiBarang,cifRupiah FROM ws_header ORDER BY COALESCE(tanggalDokumen,tanggalAju,dateCreated) DESC LIMIT 8",array()):array();
  $outstandingDocs=$hasWsHeader?home_rows($db,"SELECT kodeDokumen,nomorAju,tanggalAju,tanggalDokumen,statusDokumen,nilaiBarang FROM ws_header WHERE nomorAju IS NOT NULL AND nomorAju<>'' AND (nomorDokpab IS NULL OR nomorDokpab='') ORDER BY COALESCE(tanggalAju,tanggalDokumen,dateCreated) DESC LIMIT 8",array()):array();
?>
<section class="content-header">
  <h1><?=home_l('customs_dashboard','Customs Dashboard');?> <small><?=home_l('customs_officer','Petugas Beacukai');?></small></h1>
  <ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=home_l('home','Home');?></a></li><li class="active"><?=home_l('customs_monitoring','Customs Monitoring');?></li></ol>
</section>
<section class="content">
  <div class="bc-home-hero">
    <div>
      <span class="bc-home-badge"><?=home_l('bonded_zone_monitoring','Kawasan Berikat Monitoring');?></span>
      <h2><?=home_l('customs_supervision_title','Dashboard Pengawasan Dokumen & Posisi Barang');?></h2>
      <p><?=home_l('customs_supervision_intro','Ringkasan dokumen BC, outstanding AJU, WIP, bahan baku, dan barang jadi untuk memudahkan petugas beacukai melihat kondisi terkini.');?></p>
    </div>
    <div class="bc-home-hero-right">
      <strong><?=home_h(date('d M Y'));?></strong>
      <span><?=home_l('local_system_update','Update lokal sistem');?></span>
      <a href="<?=base_index();?>dokumen-pabean" class="btn btn-warning btn-sm"><i class="fa fa-file-text-o"></i> <?=home_l('customs_documents','Dokumen Pabean');?></a>
    </div>
  </div>

  <div class="row">
    <div class="col-lg-2 col-md-4 col-sm-6"><div class="bc-kpi"><i class="fa fa-sign-in green"></i><span><?=home_l('total_bc23','Total Pemasukan BC 2.3');?></span><strong><?=home_num($bc23Total);?></strong><small><?=home_l('total_bc23_hint','Dokumen pemasukan bahan baku/impor');?></small></div></div>
    <div class="col-lg-2 col-md-4 col-sm-6"><div class="bc-kpi"><i class="fa fa-sign-out blue"></i><span><?=home_l('total_bc41','Total Pengeluaran BC 4.1');?></span><strong><?=home_num($bc41Total);?></strong><small><?=home_l('total_bc41_hint','Dokumen pengeluaran sementara');?></small></div></div>
    <div class="col-lg-2 col-md-4 col-sm-6"><div class="bc-kpi warning"><i class="fa fa-hourglass-half orange"></i><span><?=home_l('outstanding_aju','Outstanding Dokumen AJU');?></span><strong><?=home_num($outstandingAju);?></strong><small><?=home_l('outstanding_aju_hint','AJU belum memiliki nomor dokumen');?></small></div></div>
    <div class="col-lg-2 col-md-4 col-sm-6"><div class="bc-kpi"><i class="fa fa-industry purple"></i><span><?=home_l('wip_position','Posisi WIP');?></span><strong><?=home_num($wipQty,2);?></strong><small><?=home_l('wip_position_hint','Saldo akhir work in process');?></small></div></div>
    <div class="col-lg-2 col-md-4 col-sm-6"><div class="bc-kpi"><i class="fa fa-cubes teal"></i><span><?=home_l('finished_goods_position','Posisi Barang Jadi');?></span><strong><?=home_num($fgQty,2);?></strong><small><?=home_l('finished_goods_position_hint','On hand finished product');?></small></div></div>
    <div class="col-lg-2 col-md-4 col-sm-6"><div class="bc-kpi"><i class="fa fa-archive navy"></i><span><?=home_l('raw_material_position','Posisi Bahan Baku');?></span><strong><?=home_num($rmQty,2);?></strong><small><?=home_l('raw_material_position_hint','On hand raw material');?></small></div></div>
  </div>

  <div class="row">
    <div class="col-md-6"><div class="bc-devisa-card impor"><i class="fa fa-sign-in"></i><span><?=home_l('import_forex_realization','Realisasi Devisa Impor MTD');?></span><strong>Rp <?=home_money($importDevisaMtd);?></strong><small><?=home_l('import_forex_basis','Dasar nilai: BC 2.3, CIF Rupiah / CIF x NDPBM');?></small></div></div>
    <div class="col-md-6"><div class="bc-devisa-card ekspor"><i class="fa fa-sign-out"></i><span><?=home_l('export_forex_realization','Realisasi Devisa Ekspor MTD');?></span><strong>Rp <?=home_money($exportDevisaMtd);?></strong><small><?=home_l('export_forex_basis','Dasar nilai: BC 3.0, FOB x NDPBM / nilai barang x NDPBM');?></small></div></div>
  </div>

  <div class="bc-info-strip">
    <i class="fa fa-info-circle"></i>
    <div><b><?=home_l('supervision_focus','Fokus pengawasan');?>:</b> <?=home_l('supervision_focus_text','cek outstanding AJU terlebih dahulu, lalu bandingkan posisi bahan baku, WIP, dan barang jadi. Grafik stok berdasarkan dokumen pabean membantu melihat asal dokumen stock yang masih tersisa.');?></div>
  </div>

  <div class="row">
    <div class="col-md-7"><div class="box bc-card"><div class="box-header"><h3 class="box-title"><i class="fa fa-line-chart"></i> <?=home_l('bc_doc_trend_7_days','Trend Dokumen BC 7 Hari');?></h3></div><div class="box-body"><div id="bc_doc_trend_chart" class="bc-chart"></div></div></div></div>
    <div class="col-md-5"><div class="box bc-card"><div class="box-header"><h3 class="box-title"><i class="fa fa-pie-chart"></i> <?=home_l('goods_position_composition','Komposisi Posisi Barang');?></h3></div><div class="box-body"><div id="bc_stock_position_chart" class="bc-chart"></div></div></div></div>
  </div>

  <div class="row">
    <div class="col-md-12"><div class="box bc-card"><div class="box-header"><h3 class="box-title"><i class="fa fa-money"></i> <?=home_l('forex_import_export_chart','Grafik Nilai Realisasi Devisa Impor vs Ekspor');?></h3><span class="pull-right text-muted"><?=home_l('last_6_months','Periode 6 bulan terakhir');?></span></div><div class="box-body"><div id="bc_devisa_chart" class="bc-chart"></div></div></div></div>
  </div>

  <div class="row">
    <div class="col-md-4"><div class="box bc-card"><div class="box-header"><h3 class="box-title"><i class="fa fa-file-text-o"></i> <?=home_l('document_status','Status Dokumen');?></h3></div><div class="box-body"><div id="bc_doc_status_chart" class="bc-chart-sm"></div></div></div></div>
    <div class="col-md-4"><div class="box bc-card"><div class="box-header"><h3 class="box-title"><i class="fa fa-tags"></i> <?=home_l('bc_document_type','Jenis Dokumen BC');?></h3></div><div class="box-body"><div id="bc_doc_type_chart" class="bc-chart-sm"></div></div></div></div>
    <div class="col-md-4"><div class="box bc-card"><div class="box-header"><h3 class="box-title"><i class="fa fa-balance-scale"></i> <?=home_l('stock_by_customs_doc','Stock by Dokumen Pabean');?></h3></div><div class="box-body"><div id="bc_stock_doc_chart" class="bc-chart-sm"></div></div></div></div>
  </div>

  <div class="row">
    <div class="col-md-7">
      <div class="box bc-card">
        <div class="box-header"><h3 class="box-title"><i class="fa fa-clock-o"></i> <?=home_l('outstanding_aju','Outstanding Dokumen AJU');?></h3><a href="<?=base_index();?>dokumen-pabean" class="btn btn-xs btn-primary pull-right"><?=home_l('open_documents','Buka dokumen');?></a></div>
        <div class="box-body table-responsive">
          <table class="table table-hover table-bordered bc-table">
            <thead><tr><th><?=home_l('bc_type','Jenis BC');?></th><th><?=home_l('aju_number','Nomor AJU');?></th><th><?=home_l('date','Tanggal');?></th><th><?=home_l('status','Status');?></th><th><?=home_l('goods_value','Nilai Barang');?></th></tr></thead>
            <tbody>
              <?php if(!$outstandingDocs){ ?><tr><td colspan="5" class="text-center text-muted"><?=home_l('no_outstanding_aju','Tidak ada outstanding AJU.');?></td></tr><?php } ?>
              <?php foreach($outstandingDocs as $r){ ?><tr>
                <td><span class="bc-chip">BC <?=home_h($r->kodeDokumen);?></span></td>
                <td><b><?=home_h($r->nomorAju?:'-');?></b></td>
                <td><?=home_h(home_bc_fmt_date($r->tanggalAju?:$r->tanggalDokumen));?></td>
                <td><span class="label label-<?=home_bc_status_label($r->statusDokumen);?>"><?=home_h($r->statusDokumen?:'DRAFT');?></span></td>
                <td class="text-right"><?=home_money($r->nilaiBarang);?></td>
              </tr><?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <div class="col-md-5">
      <div class="box bc-card">
        <div class="box-header"><h3 class="box-title"><i class="fa fa-history"></i> <?=home_l('recent_documents','Dokumen Terbaru');?></h3></div>
        <div class="box-body table-responsive">
          <table class="table table-hover table-bordered bc-table">
            <thead><tr><th>BC</th><th><?=home_l('aju_registration','AJU / Daftar');?></th><th><?=home_l('date','Tanggal');?></th><th><?=home_l('status','Status');?></th></tr></thead>
            <tbody>
              <?php if(!$recentDocs){ ?><tr><td colspan="4" class="text-center text-muted"><?=home_l('no_customs_documents','Belum ada dokumen pabean.');?></td></tr><?php } ?>
              <?php foreach($recentDocs as $r){ ?><tr>
                <td><span class="bc-chip">BC <?=home_h($r->kodeDokumen);?></span></td>
                <td><b><?=home_h($r->nomorAju?:'-');?></b><br><small><?=home_h($r->nomorDokpab?:home_t('not_registered','Belum daftar'));?></small></td>
                <td><?=home_h(home_bc_fmt_date($r->tanggalDokumen?:$r->tanggalAju));?></td>
                <td><span class="label label-<?=home_bc_status_label($r->statusDokumen);?>"><?=home_h($r->statusDokumen?:'DRAFT');?></span></td>
              </tr><?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</section>

<style>
.bc-home-hero{position:relative;overflow:hidden;border-radius:22px;background:linear-gradient(135deg,#0f172a,#0f766e);color:#fff;padding:24px;margin-bottom:18px;display:flex;justify-content:space-between;gap:16px;box-shadow:0 18px 40px rgba(15,23,42,.2)}.bc-home-hero:after{content:"";position:absolute;right:-80px;top:-100px;width:290px;height:290px;border-radius:50%;background:rgba(255,255,255,.1)}.bc-home-hero h2{margin:8px 0 7px;font-weight:800}.bc-home-hero p{max-width:760px;margin:0;color:rgba(255,255,255,.88)}.bc-home-badge{display:inline-block;padding:5px 10px;border-radius:999px;background:rgba(255,255,255,.16);border:1px solid rgba(255,255,255,.28);font-weight:700}.bc-home-hero-right{position:relative;z-index:1;text-align:right;min-width:180px}.bc-home-hero-right strong{display:block;font-size:25px}.bc-home-hero-right span{display:block;margin-bottom:12px;color:rgba(255,255,255,.82)}.bc-kpi{border:1px solid #e5edf5;border-radius:18px;background:#fff;padding:15px;min-height:145px;margin-bottom:16px;box-shadow:0 8px 22px rgba(15,23,42,.045)}.bc-kpi.warning{border-color:#fde68a;background:#fffbeb}.bc-kpi i{width:38px;height:38px;border-radius:14px;display:inline-flex;align-items:center;justify-content:center;color:#fff;margin-bottom:10px;background:#0f766e}.bc-kpi i.green{background:#0f766e}.bc-kpi i.blue{background:#2563eb}.bc-kpi i.orange{background:#f59e0b}.bc-kpi i.purple{background:#7c3aed}.bc-kpi i.teal{background:#0891b2}.bc-kpi i.navy{background:#0f172a}.bc-kpi span{display:block;color:#64748b;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.04em}.bc-kpi strong{display:block;color:#0f172a;font-size:24px;line-height:1.2;margin:5px 0}.bc-kpi small{color:#64748b}.bc-devisa-card{position:relative;overflow:hidden;border-radius:18px;color:#fff;padding:18px 20px;margin-bottom:16px;box-shadow:0 12px 28px rgba(15,23,42,.12);min-height:128px}.bc-devisa-card:after{content:"";position:absolute;right:-35px;top:-45px;width:150px;height:150px;border-radius:50%;background:rgba(255,255,255,.12)}.bc-devisa-card.impor{background:linear-gradient(135deg,#0f766e,#0891b2)}.bc-devisa-card.ekspor{background:linear-gradient(135deg,#1d4ed8,#7c3aed)}.bc-devisa-card i{position:absolute;right:24px;bottom:18px;font-size:54px;opacity:.2}.bc-devisa-card span{display:block;font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:rgba(255,255,255,.82)}.bc-devisa-card strong{display:block;font-size:28px;margin:8px 0 4px;line-height:1.2}.bc-devisa-card small{color:rgba(255,255,255,.82)}.bc-info-strip{display:flex;gap:12px;align-items:flex-start;padding:14px 16px;border-radius:16px;background:#f0fdfa;border:1px solid #ccfbf1;color:#334155;margin-bottom:16px}.bc-info-strip i{font-size:22px;color:#0f766e}.bc-card{border:1px solid #e5edf5;border-radius:18px;background:#fff;box-shadow:0 10px 26px rgba(15,23,42,.055);margin-bottom:18px}.bc-card .box-header{padding:16px 20px 8px;border-bottom:0}.bc-card .box-title{font-weight:800;color:#0f172a}.bc-card .box-body{padding:14px 20px 20px}.bc-chart{height:320px}.bc-chart-sm{height:280px}.bc-table>thead>tr>th{background:#f8fafc;color:#334155;font-size:12px;text-transform:uppercase;letter-spacing:.03em}.bc-table>tbody>tr>td{vertical-align:middle;font-size:12px}.bc-chip{display:inline-block;padding:4px 8px;border-radius:999px;background:#ecfeff;color:#0f766e;border:1px solid #ccfbf1;font-weight:800}@media(max-width:767px){.bc-home-hero{display:block}.bc-home-hero-right{text-align:left;margin-top:14px}.bc-chart,.bc-chart-sm{height:250px}}
</style>
<script src="<?=base_url();?>assets/js/highcharts.js"></script>
<script>
Highcharts.setOptions({lang:{thousandsSep:','},colors:['#0f766e','#2563eb','#f59e0b','#dc2626','#7c3aed','#0891b2','#16a34a','#64748b']});
Highcharts.chart('bc_doc_trend_chart',{chart:{type:'column'},title:{text:null},xAxis:{categories:<?=json_encode($bcDayLabels);?>},yAxis:{min:0,title:{text:'<?=home_l('document_count','Jumlah Dokumen');?>'},allowDecimals:false},tooltip:{shared:true},plotOptions:{column:{borderRadius:4}},series:[{name:'BC 2.3',data:<?=json_encode($bc23Trend);?>},{name:'BC 4.1',data:<?=json_encode($bc41Trend);?>}],credits:{enabled:false}});
Highcharts.chart('bc_devisa_chart',{chart:{type:'column'},title:{text:null},xAxis:{categories:<?=json_encode($devisaMonthLabels);?>},yAxis:{min:0,title:{text:'<?=home_l('goods_value','Nilai Rupiah');?>'},labels:{formatter:function(){return 'Rp '+Highcharts.numberFormat(this.value/1000000,0)+' jt';}}},tooltip:{shared:true,valuePrefix:'Rp ',valueDecimals:2},plotOptions:{column:{borderRadius:5}},series:[{name:'<?=home_l('import_forex_realization','Realisasi Devisa Impor');?>',data:<?=json_encode($devisaImporSeries);?>,color:'#0f766e'},{name:'<?=home_l('export_forex_realization','Realisasi Devisa Ekspor');?>',data:<?=json_encode($devisaEksporSeries);?>,color:'#2563eb'}],credits:{enabled:false}});
Highcharts.chart('bc_stock_position_chart',{chart:{type:'pie'},title:{text:null},tooltip:{pointFormat:'<b>{point.y:,.2f}</b> <?=home_l('qty','qty');?>'},plotOptions:{pie:{innerSize:'58%',dataLabels:{enabled:true,format:'{point.name}<br>{point.y:,.2f}'}}},series:[{name:'<?=home_l('qty','Qty');?>',data:<?=json_encode($stockPositionData);?>}],credits:{enabled:false}});
Highcharts.chart('bc_doc_status_chart',{chart:{type:'bar'},title:{text:null},xAxis:{type:'category'},yAxis:{title:{text:'<?=home_l('document','Dokumen');?>'},allowDecimals:false},legend:{enabled:false},series:[{name:'<?=home_l('status','Status');?>',data:<?=json_encode($docStatusData);?>}],credits:{enabled:false}});
Highcharts.chart('bc_doc_type_chart',{chart:{type:'pie'},title:{text:null},plotOptions:{pie:{innerSize:'55%',dataLabels:{enabled:true,format:'BC {point.name}: {point.y}'}}},series:[{name:'<?=home_l('document','Dokumen');?>',data:<?=json_encode($docTypeData);?>}],credits:{enabled:false}});
Highcharts.chart('bc_stock_doc_chart',{chart:{type:'bar'},title:{text:null},xAxis:{type:'category'},yAxis:{title:{text:'<?=home_l('qty','Qty');?>'},allowDecimals:true},legend:{enabled:false},tooltip:{pointFormat:'<b>{point.y:,.2f}</b> <?=home_l('qty','qty');?>'},series:[{name:'<?=home_l('qty','Qty');?>',data:<?=json_encode($stockDocData);?>}],credits:{enabled:false}});
</script>
<?php return; } ?>
<?php
$group=$db->fetch("SELECT level_name,deskripsi FROM sys_group_users WHERE level=? LIMIT 1",array($groupLevel));
$widgets=home_rows($db,"SELECT w.*,r.can_drilldown,r.can_export,r.sequence_no role_sequence FROM dashboard_widget w JOIN dashboard_widget_role r ON r.widget_id=w.id WHERE r.group_level=? AND r.can_view='Y' AND w.is_active='Y' ORDER BY r.sequence_no,w.sequence_no,w.widget_name",array($groupLevel));
if(!$widgets)$widgets=home_rows($db,"SELECT *,'Y' can_drilldown,'N' can_export,sequence_no role_sequence FROM dashboard_widget WHERE widget_code IN ('WH_STOCK_VALUE','WH_GR_TODAY','WH_GI_TODAY') ORDER BY sequence_no");

$categoryCount=array();$typeCount=array();$cards=array();$alerts=array();$links=array();
foreach($widgets as $w){
  $categoryCount[$w->widget_category]=isset($categoryCount[$w->widget_category])?$categoryCount[$w->widget_category]+1:1;
  $typeCount[$w->widget_type]=isset($typeCount[$w->widget_type])?$typeCount[$w->widget_type]+1:1;
  $metric=home_widget_value($db,$w->widget_code);
  $w->metric_value=$metric['value'];$w->metric_sub=$metric['sub'];$w->metric_class=$metric['class'];$w->metric_trend=$metric['trend'];
  if(count($cards)<8&&in_array($w->widget_type,array('KPI','ALERT'))) $cards[]=$w;
  if($w->widget_type==='ALERT') $alerts[]=$w;
  if(count($links)<10) $links[]=$w;
}
$catNames=array_keys($categoryCount);$catValues=array_values($categoryCount);
$typeNames=array_keys($typeCount);$typeValues=array_values($typeCount);

$dayLabels=home_last_days(7);
$movementRows=home_table_exists($db,'detail_transaksi')?home_rows($db,"SELECT DATE(COALESCE(posting_date,document_date,date_created)) trx_date,COALESCE(SUM(CASE WHEN direction='IN' OR qty>0 THEN ABS(qty) ELSE 0 END),0) in_qty,COALESCE(SUM(CASE WHEN direction='OUT' OR qty<0 THEN ABS(qty) ELSE 0 END),0) out_qty FROM detail_transaksi WHERE DATE(COALESCE(posting_date,document_date,date_created))>=DATE_SUB(CURDATE(),INTERVAL 6 DAY) GROUP BY DATE(COALESCE(posting_date,document_date,date_created))",array()):array();
$movementIn=home_series_by_date($dayLabels,$movementRows,'trx_date','in_qty');
$movementOut=home_series_by_date($dayLabels,$movementRows,'trx_date','out_qty');

$financeRows=home_table_exists($db,'jurnal_header')&&home_table_exists($db,'jurnal_detail')?home_rows($db,"SELECT h.tgl_jurnal trx_date,COALESCE(SUM(CASE WHEN d.no_rek LIKE '4%' THEN d.kredit-d.debet ELSE 0 END),0) revenue,ABS(COALESCE(SUM(CASE WHEN d.no_rek LIKE '5%' THEN d.debet-d.kredit ELSE 0 END),0)) expense FROM jurnal_header h JOIN jurnal_detail d ON d.id_header=h.id WHERE h.tgl_jurnal>=DATE_SUB(CURDATE(),INTERVAL 6 DAY) GROUP BY h.tgl_jurnal",array()):array();
$financeRevenue=home_series_by_date($dayLabels,$financeRows,'trx_date','revenue');
$financeExpense=home_series_by_date($dayLabels,$financeRows,'trx_date','expense');

$stockRows=home_table_exists($db,'stock_layer')?home_rows($db,"SELECT COALESCE(stock_type,'UNSPECIFIED') stock_type,COALESCE(SUM(qty_sisa),0) qty FROM stock_layer GROUP BY COALESCE(stock_type,'UNSPECIFIED') ORDER BY qty DESC",array()):array();
$stockTypeData=home_pairs_from_rows($stockRows,'stock_type','qty',home_t('no_stock','No Stock'));
$soStatusData=home_status_pairs($db,'sales_order','approval_status');
$poStatusData=home_status_pairs($db,'purchase_order','status');
$prodStatusData=home_status_pairs($db,'production_order','status');
$invoiceStatusData=home_status_pairs($db,'sales_invoice','billing_status');

$alertTrend=array('warning'=>0,'positive'=>0,'negative'=>0,'neutral'=>0,'active'=>0);
foreach($alerts as $w){$t=isset($w->metric_trend)?$w->metric_trend:'neutral';$alertTrend[$t]=isset($alertTrend[$t])?$alertTrend[$t]+1:1;}
$alertTrendData=array();
foreach($alertTrend as $k=>$v){if($v>0)$alertTrendData[]=array(ucwords($k),$v);}
if(!$alertTrendData)$alertTrendData[]=array(home_t('no_alert','No Alert'),0);
?>

<section class="content-header">
  <h1><?=home_l('dashboard','Home Dashboard');?> <small><?=home_h($group?$group->level_name:$groupLevel);?></small></h1>
  <ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=home_l('home','Home');?></a></li><li class="active"><?=home_l('role_based_dashboard','Role Based Dashboard');?></li></ol>
</section>

<section class="content">
  <div class="home-hero">
    <div class="row">
      <div class="col-md-8">
        <h2><?=home_l('welcome','Selamat datang');?>, <?=home_h(isset($_SESSION['nama'])?$_SESSION['nama']:$_SESSION['username']);?></h2>
        <p><?=home_h(home_msg('role_dashboard_intro','Dashboard ini otomatis mengikuti group user {group}. Widget yang tampil hanya yang diizinkan di dashboard_widget_role.',array('group'=>$groupLevel)));?></p>
      </div>
      <div class="col-md-4 text-right">
        <div class="hero-number"><?=home_num(count($widgets));?></div>
        <span><?=home_l('active_widgets_role','widget aktif untuk role ini');?></span>
      </div>
    </div>
  </div>

  <div class="row">
    <?php foreach($cards as $w){ ?>
    <div class="col-lg-3 col-md-4 col-sm-6">
      <div class="small-box <?=home_h($w->metric_class);?> home-card">
        <div class="inner">
          <h3><?=home_h($w->metric_value);?></h3>
          <p><?=home_h($w->widget_name);?><br><small><?=home_h($w->metric_sub);?></small></p>
        </div>
        <div class="icon"><i class="fa <?=home_h($w->icon?:'fa-dashboard');?>"></i></div>
        <a href="<?=base_index().home_h($w->source_url);?>" class="small-box-footer"><?=home_l('drilldown','Drilldown');?> <i class="fa fa-arrow-circle-right"></i></a>
      </div>
    </div>
    <?php } ?>
  </div>

  <div class="row">
    <div class="col-md-7">
      <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title"><?=home_l('finance_trend_7_days','Finance Trend 7 Hari');?></h3></div>
        <div class="box-body"><div id="home_finance_chart" class="home-chart"></div></div>
      </div>
    </div>
    <div class="col-md-5">
      <div class="box box-success">
        <div class="box-header with-border"><h3 class="box-title"><?=home_l('stock_by_type','Stock by Type');?></h3></div>
        <div class="box-body"><div id="home_stock_type_chart" class="home-chart"></div></div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-md-7">
      <div class="box box-info">
        <div class="box-header with-border"><h3 class="box-title"><?=home_l('material_movement_7_days','Material Movement 7 Hari');?></h3></div>
        <div class="box-body"><div id="home_movement_chart" class="home-chart"></div></div>
      </div>
    </div>
    <div class="col-md-5">
      <div class="box box-warning">
        <div class="box-header with-border"><h3 class="box-title"><?=home_l('alert_health','Alert Health');?></h3></div>
        <div class="box-body"><div id="home_alert_chart" class="home-chart"></div></div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-md-4">
      <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title"><?=home_l('sales_order_status','Sales Order Status');?></h3></div>
        <div class="box-body"><div id="home_so_status_chart" class="home-chart-sm"></div></div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="box box-success">
        <div class="box-header with-border"><h3 class="box-title"><?=home_l('purchase_order_status','Purchase Order Status');?></h3></div>
        <div class="box-body"><div id="home_po_status_chart" class="home-chart-sm"></div></div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="box box-danger">
        <div class="box-header with-border"><h3 class="box-title"><?=home_l('production_order_status','Production Order Status');?></h3></div>
        <div class="box-body"><div id="home_prod_status_chart" class="home-chart-sm"></div></div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-md-7">
      <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title"><?=home_l('widget_coverage_area','Widget Coverage per Area');?></h3></div>
        <div class="box-body"><div id="home_category_chart" class="home-chart-sm"></div></div>
      </div>
    </div>
    <div class="col-md-5">
      <div class="box box-success">
        <div class="box-header with-border"><h3 class="box-title"><?=home_l('widget_type_mix','Widget Type Mix');?></h3></div>
        <div class="box-body"><div id="home_type_chart" class="home-chart-sm"></div></div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-md-7">
      <div class="box box-warning">
        <div class="box-header with-border"><h3 class="box-title"><?=home_l('alert_monitoring','Alert & Monitoring');?></h3></div>
        <div class="box-body table-responsive no-padding">
          <table class="table table-hover home-table">
            <thead><tr><th><?=home_l('area','Area');?></th><th><?=home_l('widget','Widget');?></th><th><?=home_l('value','Nilai');?></th><th><?=home_l('status','Status');?></th><th><?=home_l('action','Action');?></th></tr></thead>
            <tbody>
              <?php if(!$alerts){ ?><tr><td colspan="5" class="text-center text-muted"><?=home_l('no_alert_role','Tidak ada alert untuk role ini.');?></td></tr><?php } ?>
              <?php foreach(array_slice($alerts,0,12) as $w){$label=$w->metric_trend==='positive'?'success':($w->metric_trend==='warning'?'warning':'default'); ?>
              <tr>
                <td><?=home_h($w->widget_category);?></td>
                <td><i class="fa <?=home_h($w->icon?:'fa-bell');?>"></i> <?=home_h($w->widget_name);?><br><small><?=home_h($w->description);?></small></td>
                <td><b><?=home_h($w->metric_value);?></b><br><small><?=home_h($w->metric_sub);?></small></td>
                <td><span class="label label-<?=$label;?>"><?=home_h($w->metric_trend);?></span></td>
                <td><a class="btn btn-xs btn-primary" href="<?=base_index().home_h($w->source_url);?>"><?=home_l('open','Open');?></a></td>
              </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <div class="col-md-5">
      <div class="box box-info">
        <div class="box-header with-border"><h3 class="box-title"><?=home_l('quick_access','Quick Access');?></h3></div>
        <div class="box-body">
          <?php foreach($links as $w){ ?>
          <a class="home-link" href="<?=base_index().home_h($w->source_url);?>">
            <i class="fa <?=home_h($w->icon?:'fa-link');?> text-<?=home_h($w->color?:'blue');?>"></i>
            <span><?=home_h($w->widget_name);?></span>
            <small><?=home_h($w->widget_category);?></small>
          </a>
          <?php } ?>
        </div>
      </div>
    </div>
  </div>
</section>

<style>
.home-hero{background:linear-gradient(135deg,#0f172a,#0f766e);color:#fff;border-radius:14px;padding:22px;margin-bottom:18px;box-shadow:0 10px 24px rgba(15,23,42,.18)}
.home-hero h2{margin:0 0 6px;font-weight:700}.hero-number{font-size:42px;font-weight:800;line-height:1}.home-card small{color:rgba(255,255,255,.86)}.home-table td{vertical-align:middle!important}.home-link{display:block;border:1px solid #e5edf5;border-radius:10px;padding:11px 12px;margin-bottom:9px;color:#1f2937;background:#fff;box-shadow:0 3px 10px rgba(15,23,42,.04)}.home-link:hover{background:#f8fbff;color:#0f766e}.home-link i{font-size:20px;width:28px}.home-link span{font-weight:700}.home-link small{float:right;color:#64748b;margin-top:3px}.home-chart{height:330px}.home-chart-sm{height:285px}.box{border-radius:10px}.box-header .box-title{font-weight:700}.highcharts-background{fill:transparent}
</style>
<script src="<?=base_url();?>assets/js/highcharts.js"></script>
<script>
Highcharts.setOptions({lang:{thousandsSep:','},colors:['#0f766e','#2563eb','#f59e0b','#dc2626','#7c3aed','#0891b2','#16a34a','#64748b']});
Highcharts.chart('home_finance_chart',{chart:{type:'areaspline'},title:{text:null},xAxis:{categories:<?=json_encode($dayLabels);?>},yAxis:{title:{text:'<?=home_l('amount','Amount');?>'}},tooltip:{shared:true,valueDecimals:2},plotOptions:{areaspline:{fillOpacity:.18,marker:{enabled:true,radius:3}}},series:[{name:'<?=home_l('revenue','Revenue');?>',data:<?=json_encode($financeRevenue);?>},{name:'<?=home_l('expense','Expense');?>',data:<?=json_encode($financeExpense);?>}],credits:{enabled:false}});
Highcharts.chart('home_stock_type_chart',{chart:{type:'pie'},title:{text:null},tooltip:{pointFormat:'<b>{point.y:,.2f}</b> <?=home_l('qty','qty');?>'},plotOptions:{pie:{innerSize:'58%',dataLabels:{enabled:true,format:'{point.name}<br>{point.y:,.2f}'}}},series:[{name:'<?=home_l('qty','Qty');?>',data:<?=json_encode($stockTypeData);?>}],credits:{enabled:false}});
Highcharts.chart('home_movement_chart',{chart:{type:'column'},title:{text:null},xAxis:{categories:<?=json_encode($dayLabels);?>},yAxis:{min:0,title:{text:'<?=home_l('qty','Qty');?>'}},tooltip:{shared:true,valueDecimals:2},plotOptions:{column:{borderRadius:4}},series:[{name:'<?=home_l('goods_receipt_in','Goods Receipt / In');?>',data:<?=json_encode($movementIn);?>},{name:'<?=home_l('goods_issue_out','Goods Issue / Out');?>',data:<?=json_encode($movementOut);?>}],credits:{enabled:false}});
Highcharts.chart('home_alert_chart',{chart:{type:'pie'},title:{text:null},plotOptions:{pie:{innerSize:'62%',dataLabels:{enabled:true,format:'{point.name}: {point.y}'}}},series:[{name:'Alert',data:<?=json_encode($alertTrendData);?>}],credits:{enabled:false}});
Highcharts.chart('home_so_status_chart',{chart:{type:'bar'},title:{text:null},xAxis:{type:'category'},yAxis:{title:{text:'<?=home_l('document','Document');?>'},allowDecimals:false},legend:{enabled:false},series:[{name:'SO',data:<?=json_encode($soStatusData);?>}],credits:{enabled:false}});
Highcharts.chart('home_po_status_chart',{chart:{type:'bar'},title:{text:null},xAxis:{type:'category'},yAxis:{title:{text:'<?=home_l('document','Document');?>'},allowDecimals:false},legend:{enabled:false},series:[{name:'PO',data:<?=json_encode($poStatusData);?>}],credits:{enabled:false}});
Highcharts.chart('home_prod_status_chart',{chart:{type:'bar'},title:{text:null},xAxis:{type:'category'},yAxis:{title:{text:'<?=home_l('order','Order');?>'},allowDecimals:false},legend:{enabled:false},series:[{name:'Production',data:<?=json_encode($prodStatusData);?>}],credits:{enabled:false}});
Highcharts.chart('home_category_chart',{chart:{type:'column'},title:{text:null},xAxis:{categories:<?=json_encode($catNames);?>},yAxis:{title:{text:'<?=home_l('widget_count','Jumlah Widget');?>'},allowDecimals:false},legend:{enabled:false},plotOptions:{column:{borderRadius:4}},series:[{name:'<?=home_l('widget','Widget');?>',data:<?=json_encode($catValues);?>,color:'#0f766e'}],credits:{enabled:false}});
Highcharts.chart('home_type_chart',{chart:{type:'pie'},title:{text:null},plotOptions:{pie:{innerSize:'55%',dataLabels:{enabled:true,format:'{point.name}: {point.y}'}}},series:[{name:'<?=home_l('widget','Widget');?>',data:<?=json_encode(array_map(function($n,$v){return array($n,$v);},$typeNames,$typeValues));?>}],credits:{enabled:false}});
</script>
