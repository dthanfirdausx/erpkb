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
$accounts = $db->query("SELECT r.no_rek,r.nama_rek FROM rekening r LEFT JOIN rekening c ON c.induk=r.no_rek WHERE c.no_rek IS NULL ORDER BY r.no_rek");
$currencies = $db->query("SELECT jenis_valas FROM matauang GROUP BY jenis_valas ORDER BY jenis_valas='IDR' DESC, jenis_valas");
$costCenters = $db->query("SELECT id,cost_center_code,cost_center_name FROM erp_cost_center WHERE status='Aktif' ORDER BY cost_center_code");
$profitCenters = $db->query("SELECT id,profit_center_code,profit_center_name FROM erp_profit_center WHERE status='Aktif' ORDER BY profit_center_code");
$taxCodes = $db->query("SELECT id,tax_code,tax_name,rate FROM erp_tax_code WHERE status='Aktif' ORDER BY tax_code");
function ju_opt($value, $label, $selected = false) {
  return '<option value="'.htmlspecialchars($value, ENT_QUOTES, 'UTF-8').'"'.($selected?' selected':'').'>'.htmlspecialchars($label, ENT_QUOTES, 'UTF-8').'</option>';
}
?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
.ju-filter .form-group{margin-bottom:12px}.ju-total{font-size:18px;font-weight:700}.ju-line-table th,.ju-line-table td{font-size:12px;vertical-align:middle!important}.ju-line-table input,.ju-line-table select{font-size:12px}.ju-section{font-weight:700;color:#3c8dbc;border-bottom:1px solid #d2d6de;margin:12px 0 10px;padding-bottom:6px}
</style>

<section class="content-header">
  <h1><?=fin_h('finance_general_journal', 'General Journal');?> <small>SAP FI Journal Entry</small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=fin_h('common_home', 'Home');?></a></li>
    <li>Akunting</li>
    <li class="active"><?=fin_h('finance_general_journal', 'General Journal');?></li>
  </ol>
</section>

<section class="content">
  <div class="row">
    <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-green"><i class="fa fa-check"></i></span><div class="info-box-content"><span class="info-box-text"><?=fin_h('finance_posted', 'Posted');?></span><span class="info-box-number"><?=$db->query("SELECT COUNT(*) total FROM jurnal_header WHERE posting_status='POSTED'")->fetch()->total;?></span></div></div></div>
    <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-yellow"><i class="fa fa-pencil"></i></span><div class="info-box-content"><span class="info-box-text"><?=fin_h('finance_draft', 'Draft');?></span><span class="info-box-number"><?=$db->query("SELECT COUNT(*) total FROM jurnal_header WHERE posting_status='DRAFT'")->fetch()->total;?></span></div></div></div>
    <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-red"><i class="fa fa-undo"></i></span><div class="info-box-content"><span class="info-box-text">Reversed</span><span class="info-box-number"><?=$db->query("SELECT COUNT(*) total FROM jurnal_header WHERE posting_status='REVERSED'")->fetch()->total;?></span></div></div></div>
    <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-blue"><i class="fa fa-calendar"></i></span><div class="info-box-content"><span class="info-box-text">Open Period</span><span class="info-box-number"><?=$db->query("SELECT COUNT(*) total FROM erp_financial_period WHERE status='OPEN'")->fetch()->total;?></span></div></div></div>
  </div>

  <div class="box box-primary">
    <div class="box-header with-border">
      <h3 class="box-title">Journal Entry Cockpit</h3>
      <div class="box-tools">
        <button class="btn btn-primary btn-sm" id="btn_add_journal"><i class="fa fa-plus"></i> Create Journal</button>
        <button class="btn btn-info btn-sm" id="btn_import"><i class="fa fa-upload"></i> Import Excel</button>
        <a class="btn btn-default btn-sm" href="<?=base_admin();?>modul/jurnal_umum/jurnal_umum_action.php?act=template"><i class="fa fa-download"></i> Template</a>
      </div>
    </div>
    <div class="box-body">
      <form id="form_filter_jurnal" class="form-horizontal ju-filter">
        <div class="form-group">
          <label class="control-label col-md-1">Tanggal</label>
          <div class="col-md-2"><div class="input-group date"><input id="start_date" class="form-control" value="<?=date('Y-m-01');?>"><span class="input-group-addon"><i class="fa fa-calendar"></i></span></div></div>
          <div class="col-md-2"><div class="input-group date"><input id="end_date" class="form-control" value="<?=date('Y-m-d');?>"><span class="input-group-addon"><i class="fa fa-calendar"></i></span></div></div>
          <label class="control-label col-md-1"><?=fin_h('common_status', 'Status');?></label>
          <div class="col-md-2"><select id="filter_status" class="form-control select2-filter"><option value="">All</option><option value="DRAFT"><?=fin_h('finance_draft', 'Draft');?></option><option value="POSTED"><?=fin_h('finance_posted', 'Posted');?></option><option value="REVERSED">Reversed</option></select></div>
          <label class="control-label col-md-1">Doc Type</label>
          <div class="col-md-2"><select id="filter_doc_type" class="form-control select2-filter"><option value="">All</option><option value="SA">SA - General Ledger</option><option value="AJE">AJE - Adjustment</option><option value="KR">KR - Vendor Invoice</option><option value="DR">DR - Customer Invoice</option><option value="CM">CM - Credit Memo</option><option value="DM">DM - Debit Memo</option><option value="KZ">KZ - Vendor Payment</option><option value="DZ">DZ - Incoming Payment</option><option value="RV">RV - Reversal</option></select></div>
        </div>
        <div class="form-group">
          <label class="control-label col-md-1">Source</label>
          <div class="col-md-4"><input id="filter_source" class="form-control" placeholder="MANUAL_GL, SALES_INVOICE, GOODS_RECEIPT..."></div>
          <div class="col-md-7">
            <button type="button" class="btn btn-primary" id="btn_filter"><i class="fa fa-search"></i> <?=fin_h('common_filter', 'Filter');?></button>
            <button type="button" class="btn btn-default" id="btn_reset"><i class="fa fa-refresh"></i> <?=fin_h('common_reset', 'Reset');?></button>
            <button type="button" class="btn btn-success" id="btn_excel"><i class="fa fa-file-excel-o"></i> <?=fin_h('common_export_excel', 'Export Excel');?></button>
          </div>
        </div>
      </form>

      <div class="table-responsive">
        <table id="dtb_jurnal_umum" class="table table-bordered table-striped table-hover">
          <thead>
            <tr>
              <th><?=fin_h('common_no', 'No');?></th><th><?=fin_h('finance_journal_no', 'Journal No');?></th><th><?=fin_h('common_status', 'Status');?></th><th>Doc Type</th><th>Date</th><th><?=fin_h('finance_reference', 'Reference');?></th><th>Source</th><th><?=fin_h('finance_debit', 'Debit');?></th><th><?=fin_h('finance_credit', 'Credit');?></th><th><?=fin_h('common_action', 'Action');?></th>
            </tr>
          </thead>
        </table>
      </div>
    </div>
  </div>
</section>

<div class="modal fade" id="modal_jurnal">
  <div class="modal-dialog modal-lg" style="width:96%">
    <div class="modal-content">
      <form id="journal_form" action="<?=base_admin();?>modul/jurnal_umum/jurnal_umum_action.php?act=in" method="post">
        <input type="hidden" name="id" id="journal_id">
        <input type="hidden" name="posting_status" id="posting_status" value="DRAFT">
        <div class="modal-header bg-primary">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title"><i class="fa fa-book"></i> Journal Entry</h4>
        </div>
        <div class="modal-body">
          <div class="ju-section">Document Header</div>
          <div class="row">
            <div class="col-md-2"><label><?=fin_h('finance_journal_no', 'Journal No');?></label><input name="no_jurnal" id="no_jurnal" class="form-control" value="<?=generate_no_jurnal();?>" readonly></div>
            <div class="col-md-2"><label>Document Type</label><select name="document_type" id="document_type" class="form-control select2-modal"><option value="SA">SA - General Ledger</option><option value="AJE">AJE - Adjustment</option><option value="KR">KR - Vendor Invoice</option><option value="DR">DR - Customer Invoice</option><option value="KZ">KZ - Vendor Payment</option><option value="DZ">DZ - Incoming Payment</option></select></div>
            <div class="col-md-2"><label><?=fin_h('finance_posting_date', 'Posting Date');?></label><div class="input-group date"><input name="tgl_jurnal" id="tgl_jurnal" class="form-control" value="<?=date('Y-m-d');?>" required><span class="input-group-addon"><i class="fa fa-calendar"></i></span></div></div>
            <div class="col-md-3"><label><?=fin_h('finance_reference', 'Reference');?></label><input name="no_bukti" id="no_bukti" class="form-control" placeholder="No bukti/ref transaksi"></div>
            <div class="col-md-3"><label>Source Module</label><input name="source_module" id="source_module" class="form-control" value="MANUAL_GL"></div>
          </div>
          <div class="row" style="margin-top:10px">
            <div class="col-md-3"><label>Source Document</label><input name="source_document_no" id="source_document_no" class="form-control"></div>
            <div class="col-md-9"><label>Header Text</label><input name="ket" id="ket" class="form-control" required placeholder="Deskripsi jurnal"></div>
          </div>

          <div class="ju-section">Line Items</div>
          <div class="table-responsive">
            <table class="table table-bordered ju-line-table" id="table_detail">
              <thead>
                <tr>
                  <th style="width:22%"><?=fin_h('finance_coa', 'COA');?></th><th style="width:16%">Line Text</th><th style="width:10%"><?=fin_h('finance_debit', 'Debit');?></th><th style="width:10%"><?=fin_h('finance_credit', 'Credit');?></th><th style="width:7%">Curr</th><th style="width:7%">Kurs</th><th style="width:10%">Cost Ctr</th><th style="width:10%">Profit Ctr</th><th style="width:8%"><?=fin_h('finance_tax', 'Tax');?></th><th style="width:4%"><button type="button" class="btn btn-success btn-xs" id="add_row"><i class="fa fa-plus"></i></button></th>
                </tr>
              </thead>
              <tbody></tbody>
              <tfoot>
                <tr><th colspan="2" class="text-right">TOTAL</th><th><input id="total_debet" class="form-control text-right ju-total" readonly></th><th><input id="total_kredit" class="form-control text-right ju-total" readonly></th><th colspan="6"><span id="balance_info" class="label label-default">Not calculated</span></th></tr>
              </tfoot>
            </table>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal"><?=fin_h('common_close', 'Close');?></button>
          <button type="button" class="btn btn-warning save_journal" data-status="DRAFT"><i class="fa fa-save"></i> Save Draft</button>
          <button type="button" class="btn btn-success save_journal" data-status="POSTED"><i class="fa fa-check"></i> Post Journal</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modal_detail_jurnal"><div class="modal-dialog modal-lg" style="width:92%"><div class="modal-content"><div class="modal-header bg-primary"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Journal Detail</h4></div><div class="modal-body" id="detail_jurnal_body"></div></div></div></div>
<div class="modal fade" id="modal_import"><div class="modal-dialog"><div class="modal-content"><form id="form_import_excel" enctype="multipart/form-data"><div class="modal-header bg-info"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Import Journal Excel</h4></div><div class="modal-body"><p class="text-muted">Gunakan template resmi terbaru. Kolom merah wajib diisi, dan <strong>Import Group</strong> harus sama untuk semua baris dalam satu jurnal. <strong>No Jurnal</strong> boleh dikosongkan agar sistem membuat nomor otomatis.</p><a href="<?=base_admin();?>modul/jurnal_umum/jurnal_umum_action.php?act=template" class="btn btn-default btn-sm"><i class="fa fa-download"></i> Download Template</a><hr><input type="file" name="file_excel" class="form-control" accept=".xls,.xlsx" required></div><div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal"><?=fin_h('common_close', 'Close');?></button><button class="btn btn-info"><i class="fa fa-upload"></i> Import</button></div></form></div></div></div>

<script>
var accountOptions = `<?php foreach($accounts as $r){ echo ju_opt($r->no_rek, $r->no_rek.' - '.$r->nama_rek); } ?>`;
var currencyOptions = `<?php foreach($currencies as $r){ echo ju_opt($r->jenis_valas, $r->jenis_valas, $r->jenis_valas==='IDR'); } ?>`;
var costOptions = `<option value=""></option><?php foreach($costCenters as $r){ echo ju_opt($r->id, $r->cost_center_code.' - '.$r->cost_center_name); } ?>`;
var profitOptions = `<option value=""></option><?php foreach($profitCenters as $r){ echo ju_opt($r->id, $r->profit_center_code.' - '.$r->profit_center_name); } ?>`;
var taxOptions = `<option value=""></option><?php foreach($taxCodes as $r){ echo ju_opt($r->id, $r->tax_code.' - '.$r->tax_name.' '.$r->rate.'%'); } ?>`;

function lineRow(data){
  data = data || {};
  return `<tr>
    <td><select name="no_rek[]" class="form-control select2-line coa" required><option value=""></option>${accountOptions}</select></td>
    <td><input name="line_text[]" class="form-control" value="${data.line_text||''}"></td>
    <td><input name="debet[]" class="form-control text-right money debet" value="${data.debet||''}"></td>
    <td><input name="kredit[]" class="form-control text-right money kredit" value="${data.kredit||''}"></td>
    <td><select name="valuta[]" class="form-control select2-line valuta">${currencyOptions}</select></td>
    <td><input name="kurs[]" class="form-control text-right" value="${data.kurs||'1'}"></td>
    <td><select name="cost_center_id[]" class="form-control select2-line">${costOptions}</select></td>
    <td><select name="profit_center_id[]" class="form-control select2-line">${profitOptions}</select></td>
    <td><select name="tax_code_id[]" class="form-control select2-line">${taxOptions}</select></td>
    <td class="text-center"><button type="button" class="btn btn-danger btn-xs remove_row"><i class="fa fa-trash"></i></button></td>
  </tr>`;
}
function initSelect(scope){ $(scope).find('.select2-line,.select2-modal').select2({width:'100%',dropdownParent:$('#modal_jurnal'),allowClear:true}); }
function addLine(data){ $('#table_detail tbody').append(lineRow(data)); initSelect('#table_detail tbody tr:last'); if(data){ var tr=$('#table_detail tbody tr:last'); tr.find('.coa').val(data.no_rek).trigger('change'); tr.find('.valuta').val(data.valuta||'IDR').trigger('change'); tr.find('[name="cost_center_id[]"]').val(data.cost_center_id||'').trigger('change'); tr.find('[name="profit_center_id[]"]').val(data.profit_center_id||'').trigger('change'); tr.find('[name="tax_code_id[]"]').val(data.tax_code_id||'').trigger('change'); } calcTotal(); }
function calcTotal(){ var d=0,k=0; $('.debet').each(function(){d+=parseFloat(String($(this).val()).replace(/,/g,''))||0}); $('.kredit').each(function(){k+=parseFloat(String($(this).val()).replace(/,/g,''))||0}); $('#total_debet').val(d.toFixed(2)); $('#total_kredit').val(k.toFixed(2)); var ok=Math.abs(d-k)<=0.01 && d>0; $('#balance_info').removeClass().addClass('label '+(ok?'label-success':'label-danger')).text(ok?'Balanced':'Not Balanced'); }

$(function(){
  $('.input-group.date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});
  $('.select2-filter').select2({width:'100%'});
  var table = $('#dtb_jurnal_umum').DataTable({
    processing:true, serverSide:true, order:[[4,'desc']],
    ajax:{url:'<?=base_admin();?>modul/jurnal_umum/jurnal_umum_data.php',type:'post',data:function(d){d.start_date=$('#start_date').val();d.end_date=$('#end_date').val();d.posting_status=$('#filter_status').val();d.document_type=$('#filter_doc_type').val();d.source_module=$('#filter_source').val();}},
    columnDefs:[{targets:[0,9],orderable:false,searchable:false},{targets:[7,8],className:'text-right'}]
  });
  $('#btn_filter').on('click',function(){table.draw();});
  $('#btn_reset').on('click',function(){$('#form_filter_jurnal')[0].reset();$('.select2-filter').val('').trigger('change');table.draw();});
  $('#btn_excel').on('click',function(){window.open('<?=base_admin();?>modul/jurnal_umum/jurnal_umum_action.php?act=excel&tgl_awal='+encodeURIComponent($('#start_date').val())+'&tgl_akhir='+encodeURIComponent($('#end_date').val())+'&posting_status='+encodeURIComponent($('#filter_status').val())+'&document_type='+encodeURIComponent($('#filter_doc_type').val()));});
  $('#btn_import').on('click',function(){$('#modal_import').modal('show');});
  $('#btn_add_journal').on('click',function(){ $('#journal_form')[0].reset(); $('#journal_id').val(''); $('#journal_form').attr('action','<?=base_admin();?>modul/jurnal_umum/jurnal_umum_action.php?act=in'); $('#no_jurnal').val('<?=generate_no_jurnal();?>'); $('#table_detail tbody').html(''); addLine(); addLine(); $('#modal_jurnal').modal('show'); initSelect('#modal_jurnal'); });
  $('#add_row').on('click',function(){addLine();});
  $(document).on('click','.remove_row',function(){$(this).closest('tr').remove(); calcTotal();});
  $(document).on('keyup change','.debet,.kredit,.money',calcTotal);
  $(document).on('click','.save_journal',function(){ $('#posting_status').val($(this).data('status')); if(Math.abs((parseFloat($('#total_debet').val())||0)-(parseFloat($('#total_kredit').val())||0))>0.01){Swal.fire('Tidak balance','Total debit dan credit harus sama.','warning');return;} $.post($('#journal_form').attr('action'),$('#journal_form').serialize(),function(res){ if(res.status==='success'){Swal.fire('Berhasil',res.message,'success');$('#modal_jurnal').modal('hide');table.ajax.reload(null,false);}else{Swal.fire('Gagal',res.message,'error');}},'json').fail(function(xhr){Swal.fire('Server Error',xhr.responseText,'error');}); });
  $(document).on('click','.detail_jurnal',function(){ $('#modal_detail_jurnal').modal('show'); $('#detail_jurnal_body').html(<?=fin_js('common_loading', 'Loading...');?>); $('#detail_jurnal_body').load('<?=base_admin();?>modul/jurnal_umum/detail_jurnal.php?id='+$(this).data('id')); });
  $(document).on('click','.edit_jurnal',function(){ $.getJSON('<?=base_admin();?>modul/jurnal_umum/jurnal_umum_action.php?act=get&id='+$(this).data('id')).done(function(res){ if(res.status!=='success'){Swal.fire('Gagal',res.message,'error');return;} $('#journal_form')[0].reset(); $('#journal_form').attr('action','<?=base_admin();?>modul/jurnal_umum/jurnal_umum_action.php?act=update'); $('#journal_id').val(res.header.id); $('#no_jurnal').val(res.header.no_jurnal); $('#document_type').val(res.header.document_type); $('#tgl_jurnal').val(res.header.tgl_jurnal); $('#no_bukti').val(res.header.no_bukti); $('#source_module').val(res.header.source_module); $('#source_document_no').val(res.header.source_document_no); $('#ket').val(res.header.ket); $('#table_detail tbody').html(''); $.each(res.lines,function(_,line){addLine(line);}); $('#modal_jurnal').modal('show'); initSelect('#modal_jurnal'); }); });
  $(document).on('click','.post_jurnal',function(){ var id=$(this).data('id'); Swal.fire({title:'Post jurnal?',text:'Setelah posting, jurnal tidak bisa diedit dan harus direversal bila salah.',icon:'question',showCancelButton:true}).then(function(r){if(r.isConfirmed){$.post('<?=base_admin();?>modul/jurnal_umum/jurnal_umum_action.php?act=post',{id:id},function(res){res.status==='success'?Swal.fire('Berhasil',res.message,'success'):Swal.fire('Gagal',res.message,'error');table.ajax.reload(null,false);},'json');}}); });
  $(document).on('click','.reverse_jurnal',function(){ var id=$(this).data('id'); Swal.fire({title:'Tanggal reversal',input:'text',inputValue:'<?=date('Y-m-d');?>',showCancelButton:true,confirmButtonText:'Create Reversal'}).then(function(r){if(r.isConfirmed){$.post('<?=base_admin();?>modul/jurnal_umum/jurnal_umum_action.php?act=reverse',{id:id,tgl_reversal:r.value},function(res){res.status==='success'?Swal.fire('Berhasil',res.message,'success'):Swal.fire('Gagal',res.message,'error');table.ajax.reload(null,false);},'json');}}); });
  $(document).on('click','.delete_jurnal',function(){ var id=$(this).data('id'); Swal.fire({title:'Hapus draft?',icon:'warning',showCancelButton:true}).then(function(r){if(r.isConfirmed){$.post('<?=base_admin();?>modul/jurnal_umum/jurnal_umum_action.php?act=delete',{id:id},function(res){res.status==='success'?Swal.fire('Berhasil',res.message,'success'):Swal.fire('Gagal',res.message,'error');table.ajax.reload(null,false);},'json');}}); });
  $('#form_import_excel').on('submit',function(e){ e.preventDefault(); var fd=new FormData(this); $.ajax({url:'<?=base_admin();?>modul/jurnal_umum/jurnal_umum_action.php?act=import',type:'POST',data:fd,processData:false,contentType:false,dataType:'json',success:function(res){res.status==='success'?Swal.fire('Berhasil',res.message,'success'):Swal.fire('Gagal',res.message,'error'); if(res.status==='success'){$('#modal_import').modal('hide');table.ajax.reload(null,false);}},error:function(xhr){Swal.fire('Server Error',xhr.responseText,'error');}}); });
});
</script>
