<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
$selectedLayerId = intval(uri_segment(3));
$sourceRows = $db->query("SELECT sl.id AS layer_id,sl.qty_sisa,sl.no_bpb,sl.plant_id,sl.storage_location_id,sl.storage_bin_id,
                                p.posting_date,p.tgl_bpb,p.nopo,p.pemasok,COALESCE(v.nama,p.pemasok) AS vendor_name,
                                p.no_aju,p.no_dokpab,p.jenis_dokpab,
                                d.no_urut,d.kode,d.unit,d.harga,COALESCE(b.nm_barang,'') AS nm_barang,
                                ep.plant_code,es.storage_code,es.storage_name,eb.bin_code,eb.bin_name
                         FROM stock_layer sl
                         JOIN pemasukan_detail d ON d.id=sl.ref_id
                         JOIN pemasukan p ON p.no_bpb=sl.no_bpb
                         LEFT JOIN pemasok v ON v.kode_pemasok=p.pemasok
                         LEFT JOIN barang b ON b.kd_barang=d.kode
                         LEFT JOIN erp_plant ep ON ep.id=sl.plant_id
                         LEFT JOIN erp_storage_location es ON es.id=sl.storage_location_id
                         LEFT JOIN erp_storage_bin eb ON eb.id=sl.storage_bin_id
                         WHERE sl.stock_type='BLOCKED' AND sl.qty_sisa>0
                         ORDER BY p.posting_date DESC,sl.id DESC");
$sources = array();
foreach ($sourceRows as $row) {
  $sources[] = array(
    'layer_id' => $row->layer_id,
    'label' => $row->no_bpb.' / '.$row->kode.' / qty '.$row->qty_sisa,
    'no_bpb' => $row->no_bpb,
    'posting_date' => $row->posting_date ?: $row->tgl_bpb,
    'nopo' => $row->nopo,
    'vendor' => $row->vendor_name,
    'kode' => $row->kode,
    'material' => $row->nm_barang,
    'unit' => $row->unit,
    'harga' => $row->harga,
    'qty_sisa' => $row->qty_sisa,
    'plant' => $row->plant_code,
    'storage_location' => trim($row->storage_code.' - '.$row->storage_name),
    'storage_bin' => trim($row->bin_code.' - '.$row->bin_name),
    'no_aju' => $row->no_aju,
    'jenis_dokpab' => $row->jenis_dokpab,
    'no_dokpab' => $row->no_dokpab
  );
}
?>

<link rel="stylesheet" href="<?=base_url();?>assets/css/jquery-ui.css">
<style>
  .release-section { margin-bottom: 18px; }
  .release-required:after { content:' *'; color:#dd4b39; }
  .release-source-info .dl-horizontal dt { width: 145px; }
  .release-source-info .dl-horizontal dd { margin-left: 165px; }
  .release-required-missing { border-color:#dd4b39!important; background:#fff8f8!important; }
  .release-help { display:inline-block; margin-right:10px; color:#dd4b39; font-size:12px; }
</style>

<section class="content-header">
  <h1>Release GR Blocked Stock <small>SAP MM Movement Type 105</small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
    <li><a href="<?=base_index();?>release-gr-blocked-stock">Release GR Blocked Stock</a></li>
    <li class="active">Create Release</li>
  </ol>
</section>

<section class="content">
  <form id="input_release_gr_blocked_stock" method="post" action="<?=base_admin();?>modul/release_gr_blocked_stock/release_gr_blocked_stock_action.php?act=in">
    <div class="alert alert-danger error_data" style="display:none"><span class="isi_warning"></span></div>

    <div class="box box-primary release-section">
      <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-file-text-o"></i> Release Document</h3>
      </div>
      <div class="box-body">
        <div class="row">
          <div class="col-md-3 form-group">
            <label class="release-required">Document Date</label>
            <input type="text" name="document_date" class="form-control date-field" value="<?=date('Y-m-d');?>" required>
          </div>
          <div class="col-md-3 form-group">
            <label class="release-required"><?=wh_h(wh_t('warehouse_posting_date', 'Posting Date'));?></label>
            <input type="text" name="posting_date" class="form-control date-field" value="<?=date('Y-m-d');?>" required>
          </div>
          <div class="col-md-3 form-group">
            <label><?=wh_h(wh_t('warehouse_movement_type', 'Movement Type'));?></label>
            <input class="form-control" value="105 - Release GR Blocked Stock" readonly>
            <input type="hidden" name="move_code" value="105">
          </div>
          <div class="col-md-3 form-group">
            <label>Target Stock Type</label>
            <input class="form-control" value="Unrestricted Use" readonly>
            <input type="hidden" name="stock_type" value="UNRESTRICTED">
          </div>
        </div>
      </div>
    </div>

    <div class="box box-warning release-section">
      <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-lock"></i> Source GR Blocked Stock</h3>
      </div>
      <div class="box-body">
        <div class="row">
          <div class="col-md-8 form-group">
            <label class="release-required">Original GR Blocked Item</label>
            <select id="source_layer_id" name="source_layer_id" class="form-control" required>
              <option value="">Pilih GR blocked item</option>
              <?php foreach ($sources as $source) { ?>
                <option value="<?=htmlspecialchars($source['layer_id'],ENT_QUOTES,'UTF-8');?>" <?=$selectedLayerId == $source['layer_id'] ? 'selected' : '';?>><?=htmlspecialchars($source['label'],ENT_QUOTES,'UTF-8');?></option>
              <?php } ?>
            </select>
          </div>
          <div class="col-md-4 form-group">
            <label class="release-required">Release Qty</label>
            <input type="number" step="0.00001" min="0.00001" name="release_qty" id="release_qty" class="form-control" required>
          </div>
          <div class="col-md-12 form-group">
            <label>Reason / Text</label>
            <input type="text" name="reason" class="form-control" placeholder="Contoh: Dokumen dan pemeriksaan penerimaan sudah lengkap">
          </div>
        </div>

        <div class="well release-source-info" id="source_info" style="display:none">
          <dl class="dl-horizontal">
            <dt>Original BPB</dt><dd id="info_no_bpb">-</dd>
            <dt>PO</dt><dd id="info_po">-</dd>
            <dt>Vendor</dt><dd id="info_vendor">-</dd>
            <dt><?=wh_h(wh_t('warehouse_material', 'Material'));?></dt><dd id="info_material">-</dd>
            <dt><?=wh_h(wh_t('common_plant', 'Plant'));?></dt><dd id="info_plant">-</dd>
            <dt><?=wh_h(wh_t('warehouse_storage_location', 'Storage Location'));?></dt><dd id="info_sloc">-</dd>
            <dt><?=wh_h(wh_t('warehouse_storage_bin', 'Storage Bin'));?></dt><dd id="info_bin">-</dd>
            <dt>Blocked Qty</dt><dd id="info_qty">-</dd>
            <dt><?=wh_h(wh_t('warehouse_customs', 'Customs'));?></dt><dd id="info_customs">-</dd>
          </dl>
        </div>
      </div>
    </div>

    <div class="text-right">
      <span id="release_submit_help" class="release-help">Lengkapi field mandatory untuk posting release 105.</span>
      <a href="<?=base_index();?>release-gr-blocked-stock" class="btn btn-default"><i class="fa fa-arrow-left"></i> Kembali</a>
      <button type="submit" id="btn_post_release" class="btn btn-success" disabled><i class="fa fa-unlock"></i> <?=wh_h(wh_t('common_post', 'Post'));?> Release 105</button>
    </div>
  </form>
</section>

<script src="<?=base_url();?>assets/js/jquery-ui.js"></script>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script>
var releaseSources = <?=json_encode($sources);?>;
function getReleaseSource(id){var found=null;$.each(releaseSources,function(_,source){if(String(source.layer_id)===String(id)){found=source;return false;}});return found;}
function escapeRelease(value){return $('<div>').text(value==null?'':value).html();}
function showReleaseError(message){$('.isi_warning').text(message||'Terjadi error saat posting release GR blocked stock.');$('.error_data').show();$('html,body').animate({scrollTop:$('.error_data').offset().top-90},300);}
function parseReleaseResponse(response){if(typeof response==='string'){try{return JSON.parse(response);}catch(e){return [{status:'error',error_message:response}];}}return response;}
function isReleaseRequiredFilled(el){var $el=$(el),val=$.trim($el.val()||'');if(val==='')return false;if($el.attr('type')==='number'){var num=parseFloat(val),min=$el.attr('min')!==undefined?parseFloat($el.attr('min')):null,max=$el.attr('max')!==undefined?parseFloat($el.attr('max')):null;if(isNaN(num))return false;if(min!==null&&num<min)return false;if(max!==null&&num>max)return false;}return true;}
function updateReleaseSubmitState(){var valid=true;$('#input_release_gr_blocked_stock [required]').each(function(){var ok=isReleaseRequiredFilled(this);$(this).toggleClass('release-required-missing',!ok);if(!ok)valid=false;});$('#btn_post_release').prop('disabled',!valid);$('#release_submit_help').toggle(!valid);return valid;}
function fillSourceInfo(){
  var source=getReleaseSource($('#source_layer_id').val());
  if(!source){$('#source_info').hide();$('#release_qty').val('').removeAttr('max');updateReleaseSubmitState();return;}
  $('#info_no_bpb').text(source.no_bpb||'-');
  $('#info_po').text(source.nopo||'-');
  $('#info_vendor').text(source.vendor||'-');
  $('#info_material').html('<strong>'+escapeRelease(source.kode)+'</strong> - '+escapeRelease(source.material)+' / '+escapeRelease(source.unit));
  $('#info_plant').text(source.plant||'-');
  $('#info_sloc').text(source.storage_location||'-');
  $('#info_bin').text(source.storage_bin||'-');
  $('#info_qty').text(source.qty_sisa+' '+(source.unit||''));
  $('#info_customs').text((source.jenis_dokpab||'')+' '+(source.no_dokpab||'')+' / No Aju '+(source.no_aju||'-'));
  $('#release_qty').attr('max',source.qty_sisa).val(source.qty_sisa);
  $('#source_info').show();
  updateReleaseSubmitState();
}
$(function(){
  if($.fn.select2){$('#source_layer_id').select2({width:'100%',placeholder:'Pilih GR blocked item'});}
  if($.fn.datepicker){$('.date-field').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  $('#source_layer_id').on('change',fillSourceInfo);
  $('#input_release_gr_blocked_stock').on('input change','input,select,textarea',updateReleaseSubmitState);
  $('#input_release_gr_blocked_stock').on('submit',function(e){
    e.preventDefault();
    if(!updateReleaseSubmitState()){showReleaseError('Lengkapi field mandatory sebelum posting release 105.');return false;}
    var source=getReleaseSource($('#source_layer_id').val()),qty=parseFloat($('#release_qty').val())||0,max=source?parseFloat(source.qty_sisa):0;
    if(!source||qty<=0||qty>max){showReleaseError('Release Qty harus lebih dari nol dan tidak boleh melebihi Blocked Qty.');return false;}
    var form=this,button=$('#btn_post_release');
    button.prop('disabled',true).data('original-text',button.html()).html('<i class="fa fa-spinner fa-spin"></i> <?=wh_h(wh_t('common_post', 'Post'));?>ing...');
    $('.error_data').hide();
    $.ajax({
      url:$(form).attr('action'),
      type:'POST',
      data:$(form).serialize(),
      dataType:'json',
      success:function(response){
        response=parseReleaseResponse(response);
        var result=$.isArray(response)?response[0]:response;
        if(result&&result.status==='good'){window.location='<?=base_index();?>release-gr-blocked-stock';return;}
        showReleaseError(result&&result.error_message?result.error_message:'Posting release 105 gagal.');
        button.prop('disabled',false).html(button.data('original-text'));
      },
      error:function(xhr){
        var response=parseReleaseResponse(xhr.responseText),result=$.isArray(response)?response[0]:response;
        showReleaseError(result&&result.error_message?result.error_message:'Server error saat posting release 105.');
        button.prop('disabled',false).html(button.data('original-text'));
      }
    });
    return false;
  });
  fillSourceInfo();
});
</script>
