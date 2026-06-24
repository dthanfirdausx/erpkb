<link rel="stylesheet" href="<?=base_url();?>assets/css/jquery-ui.css">
<style>
.ui-autocomplete{z-index:2147483647}.gr-section{position:relative;margin-bottom:18px}.gr-section .box-title{font-weight:600}.gr-po-section{z-index:40}.gr-customs-section{z-index:30}.gr-po-section .box-body,.gr-po-section .row,.gr-po-section .form-group{overflow:visible}.gr-po-section .chosen-container-active.chosen-with-drop,.gr-po-section .chosen-container .chosen-drop{z-index:2147483646!important}.gr-items{width:100%!important;margin-bottom:0;font-size:11px}.gr-items th{white-space:nowrap;background:#f5f5f5;font-size:11px;padding:6px!important;vertical-align:middle!important}.gr-items td{vertical-align:top;padding:5px!important}.gr-items .form-control{min-width:64px;height:28px;padding:3px 6px;font-size:11px;line-height:1.3}.gr-items .material-col{min-width:260px;font-size:12px}.gr-items .select2-container{width:100%!important;font-size:12px}.gr-items .select2-container .select2-selection--single{height:28px}.gr-items .select2-container .select2-selection__rendered{line-height:26px}.gr-items .select2-container .select2-selection__arrow{height:26px}.gr-detail-row td{background:#fbfcff!important;border-top:0!important}.gr-detail-panel{border:1px solid #d9edf7;border-radius:4px;background:#fff;padding:10px 10px 2px}.gr-detail-title{font-weight:600;color:#31708f;margin-bottom:8px}.gr-detail-grid .form-group{margin-bottom:8px}.gr-detail-grid label{font-size:11px;margin-bottom:3px;color:#555}.gr-detail-grid .form-control{width:100%;min-width:0;font-size:12px}.gr-required-missing{border-color:#dd4b39!important;background:#fff8f8!important}.gr-submit-help{display:inline-block;margin-right:10px;color:#dd4b39;font-size:12px}.required-label:after{content:' *';color:#dd4b39}.help-customs{margin:0 0 15px;color:#666}
</style>
<section class="content-header">
  <h1>GR Without Purchase Order <small>SAP MM Movement Type 501</small></h1>
  <ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li><li><a href="<?=base_index();?>gr-without-po">GR Without PO</a></li><li class="active">Create</li></ol>
</section>
<section class="content">
<form id="input_gr_without_po" method="post" action="<?=base_admin();?>modul/gr_without_po/gr_without_po_action.php?act=in">
  <div class="alert alert-danger error_data" style="display:none"><span class="isi_warning"></span></div>

  <div class="box box-primary gr-section">
    <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-file-text-o"></i> Goods Receipt Document</h3></div>
    <div class="box-body"><div class="row">
      <div class="col-md-3 form-group"><label class="required-label">Document Date</label><input type="text" name="document_date" class="form-control date-field" value="<?=date('Y-m-d');?>" required></div>
      <div class="col-md-3 form-group"><label class="required-label"><?=wh_h(wh_t('warehouse_posting_date', 'Posting Date'));?></label><input type="text" name="posting_date" class="form-control date-field" value="<?=date('Y-m-d');?>" required></div>
      <div class="col-md-3 form-group"><label><?=wh_h(wh_t('warehouse_movement_type', 'Movement Type'));?></label><input class="form-control" value="501 - GR without Purchase Order" readonly><input type="hidden" name="move_code" value="501"></div>
      <div class="col-md-3 form-group"><label class="required-label"><?=wh_h(wh_t('warehouse_stock_type', 'Stock Type'));?></label><select name="stock_type" class="form-control" required><option value="UNRESTRICTED">Unrestricted Use</option><option value="QUALITY"><?=wh_h(wh_t('warehouse_quality_inspection', 'Quality Inspection'));?></option><option value="BLOCKED">Blocked Stock</option></select></div>
      <div class="col-md-3 form-group"><label class="required-label">Reference No</label><input type="text" name="ref_no" class="form-control" placeholder="Surat jalan / memo / approval" required></div>
      <div class="col-md-3 form-group"><label>Reason Code</label><select name="reason_code" class="form-control"><option value="NON_PO_RECEIPT">Non-PO Receipt</option><option value="CUSTOMER_RETURN">Customer Return</option><option value="FREE_SAMPLE">Free Sample</option><option value="ADJUSTMENT">Stock Adjustment</option><option value="OTHER">Other</option></select></div>
      <div class="col-md-6 form-group"><label>Reason Text</label><input type="text" name="reason_text" class="form-control" placeholder="Alasan penerimaan tanpa PO"></div>
    </div></div>
  </div>

  <div class="box box-info gr-section gr-po-section">
    <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-truck"></i> Vendor and Warehouse</h3></div>
    <div class="box-body"><div class="row">
      <div class="col-md-4 form-group"><label class="required-label">Vendor / Source</label><select id="pemasok" name="pemasok" class="form-control chzn-select" required><option value="">Pilih Vendor</option><?php foreach($db->fetch_all('pemasok') as $v){ ?><option value="<?=$v->kode_pemasok;?>"><?=htmlspecialchars($v->nama,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
      <div class="col-md-4 form-group"><label class="required-label"><?=wh_h(wh_t('common_plant', 'Plant'));?></label><select id="plant_id" name="plant_id" class="form-control" required><option value="">Pilih Plant</option><?php foreach($db->query("SELECT id,plant_code,plant_name FROM erp_plant WHERE status='Aktif' ORDER BY plant_code") as $p){ ?><option value="<?=$p->id;?>"><?=$p->plant_code;?> - <?=htmlspecialchars($p->plant_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
      <div class="col-md-4 form-group"><label class="required-label"><?=wh_h(wh_t('warehouse_storage_location', 'Storage Location'));?></label><select id="storage_location_id" name="storage_location_id" class="form-control" required><option value="">Pilih Storage Location</option><?php foreach($db->query("SELECT s.id,s.plant_id,s.storage_code,s.storage_name,p.plant_code FROM erp_storage_location s JOIN erp_plant p ON p.id=s.plant_id WHERE s.status='Aktif' ORDER BY p.plant_code,s.storage_code") as $s){ ?><option value="<?=$s->id;?>" data-plant="<?=$s->plant_id;?>"><?=$s->plant_code;?> / <?=$s->storage_code;?> - <?=htmlspecialchars($s->storage_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
      <div class="col-md-4 form-group"><label class="required-label">Delivery Note / Surat Jalan</label><input type="text" name="no_do" class="form-control" required></div>
      <div class="col-md-4 form-group"><label>Valuta</label><select id="valuta" name="valuta" class="form-control chzn-select"><option value="">Pilih</option><?php foreach($db->fetch_all('matauang') as $m){ ?><option value="<?=$m->jenis_valas;?>"><?=$m->jenis_valas;?></option><?php } ?></select></div>
      <div class="col-md-4 form-group"><label>Kurs Pabean</label><input type="number" step="0.000001" min="0" name="kurs" class="form-control"></div>
    </div></div>
  </div>

  <div class="box box-warning gr-section gr-customs-section">
    <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-file-text"></i> Informasi Kepabeanan - Header</h3></div>
    <div class="box-body"><p class="help-customs">Data berikut berlaku untuk seluruh item dalam satu dokumen pabean.</p><div class="row">
      <div class="col-md-3 form-group"><label class="required-label">Jenis Dokumen BC</label><select name="jenisbcmasuk_jenis_dokumen" id="jenisbcmasuk_jenis_dokumen" class="form-control chzn-select" required><option value="">Pilih</option><?php foreach($db->fetch_all('jenisbcmasuk') as $bc){ ?><option value="<?=htmlspecialchars($bc->jenis,ENT_QUOTES,'UTF-8');?>"><?=htmlspecialchars($bc->jenis,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
      <div class="col-md-3 form-group"><label>Tujuan Detail</label><select name="kd_catdet" id="detail_catatan_kd_catdet" class="form-control chzn-select"><option value="">Pilih</option></select></div>
      <div class="col-md-3 form-group"><label class="required-label">Nomor Aju</label><input type="text" name="no_aju" class="form-control" required></div>
      <div class="col-md-3 form-group"><label class="required-label">Tanggal Aju</label><input type="text" name="tgl_aju" class="form-control date-field" required></div>
      <div class="col-md-3 form-group"><label class="required-label">Nomor Pendaftaran</label><input type="text" name="no_dokpab" class="form-control" required></div>
      <div class="col-md-3 form-group"><label class="required-label">Tanggal Pendaftaran</label><input type="text" name="tgl_dokpab" class="form-control date-field" required></div>
      <div class="col-md-3 form-group"><label>Kantor Pabean</label><select name="kantor_pabean" class="form-control chzn-select"><option value="">Pilih</option><?php foreach($db->query("SELECT id_kantor,nama_kantor FROM ref_kantor WHERE id_kantor IS NOT NULL ORDER BY id_kantor") as $k){ ?><option value="<?=$k->id_kantor;?>"><?=$k->id_kantor;?> - <?=htmlspecialchars($k->nama_kantor,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
      <div class="col-md-3 form-group"><label>Status CEISA</label><select name="customs_status" class="form-control"><option value="DRAFT">Draft</option><option value="SUBMITTED">Submitted</option><option value="REGISTERED" selected>Registered</option><option value="RELEASED">Released</option></select></div>
    </div></div>
  </div>

  <div class="box box-success gr-section">
    <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-cubes"></i> Material and Customs Items</h3><div class="box-tools"><button type="button" class="btn btn-success btn-sm" onclick="addGrRow()"><i class="fa fa-plus"></i> Tambah Item</button></div></div>
    <div class="box-body"><table class="table table-bordered gr-items"><thead><tr>
      <th></th><th><?=wh_h(wh_t('warehouse_material', 'Material'));?></th><th><?=wh_h(wh_t('warehouse_uom', 'UOM'));?></th><th class="required-label">GR Qty</th><th class="required-label">Price</th><th><?=wh_h(wh_t('warehouse_amount', 'Amount'));?></th><th>Batch/Lot</th><th><?=wh_h(wh_t('warehouse_storage_bin', 'Storage Bin'));?></th><th><?=wh_h(wh_t('common_detail', 'Detail'));?></th>
    </tr></thead><tbody id="isi_tabel"></tbody></table><input type="hidden" id="jml" value="0"></div>
  </div>
  <div class="text-right"><span id="gr_submit_help" class="gr-submit-help">Lengkapi semua field mandatory untuk posting GR.</span><a href="<?=base_index();?>gr-without-po" class="btn btn-default"><i class="fa fa-arrow-left"></i> Kembali</a> <button type="submit" id="btn_post_gr" class="btn btn-primary" disabled><i class="fa fa-save"></i> <?=wh_h(wh_t('common_post', 'Post'));?> GR Without PO</button></div>
</form>
</section>
<script src="<?=base_url();?>assets/js/jquery-ui.js"></script>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script>
var grBins = <?=json_encode(array_map(function($b){return array('id'=>$b->id,'location'=>$b->storage_location_id,'label'=>$b->bin_code.' - '.$b->bin_name);},iterator_to_array($db->query("SELECT id,storage_location_id,bin_code,bin_name FROM erp_storage_bin WHERE status='Aktif' ORDER BY bin_code"))));?>;
var packageOptions = <?=json_encode(array_map(function($x){return array('value'=>$x->id_kemasan,'label'=>$x->id_kemasan.' - '.$x->kemasan);},iterator_to_array($db->query("SELECT id_kemasan,kemasan FROM ref_jenis_kemasan ORDER BY id_kemasan"))));?>;
var countryOptions = <?=json_encode(array_map(function($x){return array('value'=>$x->kode_negara,'label'=>$x->kode_negara.' - '.$x->negara);},iterator_to_array($db->query("SELECT kode_negara,negara FROM ref_negara ORDER BY kode_negara"))));?>;
function esc(v){return $('<div>').text(v==null?'':v).html();}
function optionHtml(list,selected){var h='<option value="">Pilih</option>';$.each(list,function(_,o){h+='<option value="'+esc(o.value||o.id)+'" '+(String(selected||'')===String(o.value||o.id)?'selected':'')+'>'+esc(o.label)+'</option>';});return h;}
function binOptions(selected){var loc=$('#storage_location_id').val(),list=[];$.each(grBins,function(_,b){if(!loc||String(b.location)===String(loc))list.push(b);});return optionHtml(list,selected);}
function addGrRow(){var id=parseInt($('#jml').val()||0)+1;$('#jml').val(id);var html='<tr id="baris_'+id+'" class="gr-main-row"><td><button type="button" class="btn btn-danger btn-xs" onclick="hapus_baris('+id+')"><i class="fa fa-trash"></i></button></td>'+
'<td><select class="form-control material-col gr-required material-select" name="kode_input[]" required><option value="">Cari Material</option></select><input type="hidden" name="kode[]" value=""></td>'+
'<td><input class="form-control gr-required unit-field" name="unit[]" required readonly></td>'+
'<td><input type="number" step="0.00001" min="0.00001" class="form-control gr-qty gr-required" name="jumlah[]" required></td>'+
'<td><input type="number" step="0.00001" min="0.00001" class="form-control gr-price gr-required" name="harga[]" required></td>'+
'<td><input class="form-control gr-amount" name="nilai[]" readonly></td>'+
'<td><input class="form-control" name="lot_no[]"></td><td><select class="form-control storage-bin gr-required" name="storage_bin_id[]" required>'+binOptions('')+'</select><input type="hidden" name="lokasi[]" value=""></td>'+
'<td><button type="button" class="btn btn-info btn-xs" onclick="toggleGrDetail('+id+')"><i class="fa fa-list"></i> Detail Pabean</button></td></tr>'+
'<tr id="detail_'+id+'" class="gr-detail-row"><td colspan="9"><div class="gr-detail-panel"><div class="gr-detail-title"><i class="fa fa-file-text-o"></i> Detail Pabean Item '+id+'</div><div class="row gr-detail-grid">'+
'<div class="col-md-2 form-group"><label class="required-label">Item Pabean</label><input type="number" min="1" class="form-control gr-required" name="customs_item_no[]" value="'+id+'" required></div>'+
'<div class="col-md-2 form-group"><label>HS Code</label><input class="form-control" name="hs_code[]"></div>'+
'<div class="col-md-2 form-group"><label class="required-label">Qty Pabean</label><input type="number" step="0.00001" min="0.00001" class="form-control customs-qty gr-required" name="customs_qty[]" required></div>'+
'<div class="col-md-2 form-group"><label class="required-label">Sat. Pabean</label><input class="form-control customs-uom gr-required" name="customs_uom[]" required></div>'+
'<div class="col-md-2 form-group"><label class="required-label">Nilai Pabean</label><input type="number" step="0.00001" min="0.00001" class="form-control customs-value gr-required" name="customs_value[]" required></div>'+
'<div class="col-md-2 form-group"><label>Net Weight</label><input type="number" step="0.00001" min="0" class="form-control" name="net_weight[]"></div>'+
'<div class="col-md-2 form-group"><label>Gross Weight</label><input type="number" step="0.00001" min="0" class="form-control" name="gross_weight[]"></div>'+
'<div class="col-md-3 form-group"><label>Jenis Kemasan</label><select class="form-control" name="package_type[]">'+optionHtml(packageOptions,'')+'</select></div>'+
'<div class="col-md-2 form-group"><label>Jml Kemasan</label><input type="number" step="0.001" min="0" class="form-control" name="package_qty[]"></div>'+
'<div class="col-md-3 form-group"><label>Negara Asal</label><select class="form-control" name="origin_country[]">'+optionHtml(countryOptions,'')+'</select></div>'+
'</div></div></td></tr>';$('#isi_tabel').append(html);initMaterialSelect($('#baris_'+id));updateSubmitState();}
function toggleGrDetail(id){$('#detail_'+id).toggle();}
function hapus_baris(id){$('#baris_'+id+',#detail_'+id).remove();updateSubmitState();}
function isRequiredFilled(el){var $el=$(el),val=$.trim($el.val()||'');if($el.is(':disabled'))return true;if(val==='')return false;if($el.attr('type')==='number'){var num=parseFloat(val),min=$el.attr('min')!==undefined?parseFloat($el.attr('min')):null;if(isNaN(num))return false;if(min!==null&&num<min)return false;}return true;}
function updateSubmitState(){var valid=true;$('#input_gr_without_po [required]').each(function(){var ok=isRequiredFilled(this);$(this).toggleClass('gr-required-missing',!ok);if(!ok)valid=false;});if($('#isi_tabel .gr-main-row').length===0)valid=false;$('#btn_post_gr').prop('disabled',!valid);$('#gr_submit_help').toggle(!valid);return valid;}
function recalcRow(row){var id=(row.attr('id')||'').replace('baris_',''),qty=parseFloat(row.find('.gr-qty').val())||0,price=parseFloat(row.find('.gr-price').val())||0,amount=qty*price,detail=$('#detail_'+id),amountText=amount?amount.toFixed(5).replace(/\.?0+$/,''):'';row.find('.gr-amount').val(amountText);detail.find('.customs-qty').val(qty||'');detail.find('.customs-value').val(amountText);}
function initMaterialSelect(scope){var target=scope?scope.find('.material-select'):$('.material-select');if(!$.fn.select2)return;target.each(function(){var el=$(this);if(el.data('select2'))return;el.select2({placeholder:'Cari Material',allowClear:true,width:'100%',minimumInputLength:1,ajax:{url:'<?=base_admin();?>modul/gr_without_po/gr_without_po_action.php?act=search_material',type:'POST',dataType:'json',delay:250,data:function(params){return {term:params.term||''};},processResults:function(data){return {results:data.results||[]};},cache:true}});});}
function showGrError(message){$('.isi_warning').text(message||'Terjadi error saat posting GR without PO.');$('.error_data').show();$('html,body').animate({scrollTop:$('.error_data').offset().top-90},300);}
function parseGrResponse(response){if(typeof response==='string'){try{return JSON.parse(response);}catch(e){return [{status:'error',error_message:response}];}}return response;}
$(function(){
  if($.fn.chosen){$('.chzn-select').chosen({width:'100%'});}
  if($.fn.datepicker){$('.date-field').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  addGrRow();
  $('#input_gr_without_po').on('input change','input,select,textarea',updateSubmitState);
  $(document).on('select2:select','.material-select',function(e){var row=$(this).closest('tr'),id=(row.attr('id')||'').replace('baris_',''),data=e.params.data||{};row.find('input[name="kode[]"]').val(data.id||$(this).val());row.find('.unit-field').val(data.unit||'');$('#detail_'+id).find('.customs-uom').val(data.unit||'');updateSubmitState();});
  $(document).on('select2:clear','.material-select',function(){var row=$(this).closest('tr'),id=(row.attr('id')||'').replace('baris_','');row.find('input[name="kode[]"],.unit-field').val('');$('#detail_'+id).find('.customs-uom').val('');updateSubmitState();});
  $(document).on('keyup input change','.gr-qty,.gr-price',function(){recalcRow($(this).closest('tr'));updateSubmitState();});
  $('#plant_id').change(function(){var plant=this.value;$('#storage_location_id option').each(function(){var p=$(this).data('plant');$(this).toggle(!p||String(p)===String(plant));});$('#storage_location_id').val('');updateSubmitState();});
  $('#storage_location_id').change(function(){$('.storage-bin').each(function(){var selected=$(this).val();$(this).html(binOptions(selected));});updateSubmitState();});
  $('#jenisbcmasuk_jenis_dokumen').change(function(){$.post('<?=base_admin();?>modul/pemasukan_hamparan/get_kd_catdet.php',{jenis_dokumen:this.value},function(data){$('#detail_catatan_kd_catdet').html(data).trigger('chosen:updated');updateSubmitState();});});
  $('#input_gr_without_po').on('submit',function(e){
    e.preventDefault();
    if(!updateSubmitState()){showGrError('Lengkapi semua field mandatory sebelum posting GR without PO.');return false;}
    var form=this,button=$('#btn_post_gr');
    button.prop('disabled',true).data('original-text',button.html()).html('<i class="fa fa-spinner fa-spin"></i> <?=wh_h(wh_t('common_post', 'Post'));?>ing...');
    $('.error_data').hide();
    $.ajax({
      url:$(form).attr('action'),
      type:'POST',
      data:$(form).serialize(),
      dataType:'json',
      success:function(response){
        response=parseGrResponse(response);
        var result=$.isArray(response)?response[0]:response;
        if(result&&result.status==='good'){window.location='<?=base_index();?>gr-without-po';return;}
        showGrError(result&&result.error_message?result.error_message:'Posting GR without PO gagal.');
        button.prop('disabled',false).html(button.data('original-text'));
      },
      error:function(xhr){
        var response=parseGrResponse(xhr.responseText),result=$.isArray(response)?response[0]:response;
        showGrError(result&&result.error_message?result.error_message:'Server error saat posting GR without PO.');
        button.prop('disabled',false).html(button.data('original-text'));
      }
    });
    return false;
  });
});
</script>
