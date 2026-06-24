<?php
$requests = $db->query("SELECT no_ro,tgl_ro,catatan FROM ro ORDER BY tgl_ro DESC,no_ro DESC");
$storageLocations = $db->query("SELECT s.id,s.storage_code,s.storage_name,s.storage_type,p.plant_code FROM erp_storage_location s LEFT JOIN erp_plant p ON p.id=s.plant_id WHERE s.status='Aktif' ORDER BY p.plant_code,s.storage_code");
$storageBins = $db->query("SELECT b.id,b.bin_code,b.bin_name,b.storage_location_id,s.storage_code FROM erp_storage_bin b LEFT JOIN erp_storage_location s ON s.id=b.storage_location_id WHERE b.status='Aktif' ORDER BY s.storage_code,b.bin_code");
?>
<style>
  .tp-form-hero{background:linear-gradient(135deg,#1e3a8a,#0f766e);color:#fff;border-radius:14px;padding:18px 20px;margin-bottom:18px}
  .tp-form-hero h1{margin:0 0 5px;font-size:24px;font-weight:700}.tp-form-hero p{margin:0;opacity:.9}
  .tp-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:15px;margin-bottom:15px;box-shadow:0 4px 14px rgba(15,23,42,.05)}
  .tp-items td,.tp-items th{font-size:12px;vertical-align:middle!important}.tp-items .form-control{height:30px;padding:4px 6px;font-size:12px}
  .select2-container{width:100%!important}.required-label:after{content:' *';color:#dd4b39}.tp-submit-help{color:#9ca3af;margin-right:10px}
</style>
<section class="content-header">
  <h1>Transfer Posting <small>Create Movement 311</small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
    <li><a href="<?=base_index();?>transfer-produksi">Transfer Posting</a></li>
    <li class="active">Create</li>
  </ol>
</section>
<section class="content">
  <div class="tp-form-hero">
    <h1>Create Transfer Posting</h1>
    <p>Gunakan untuk memindahkan stock dari Gudang ke area tujuan. Sistem memakai FIFO stock layer dan membuat material document movement 311.</p>
  </div>
  <div class="alert alert-danger error_data" style="display:none"><button type="button" class="close" data-dismiss="alert">&times;</button><span class="isi_warning"></span></div>
  <form id="input_transfer_produksi" method="post" action="<?=base_admin();?>modul/transfer_produksi/transfer_produksi_action.php?act=in">
    <div class="tp-card">
      <h4><i class="fa fa-file-text-o"></i> Document Header</h4>
      <div class="row">
        <div class="col-md-2 form-group"><label class="required-label">Document Date</label><input type="text" name="document_date" id="document_date" class="form-control date-field mandatory" value="<?=date('Y-m-d');?>" autocomplete="off" required></div>
        <div class="col-md-2 form-group"><label class="required-label">Posting Date</label><input type="text" name="tgl_spb" id="tgl_spb" class="form-control date-field mandatory" value="<?=date('Y-m-d');?>" autocomplete="off" required></div>
        <div class="col-md-2 form-group"><label>Movement Type</label><input type="text" class="form-control" value="311 - Transfer Posting" readonly></div>
        <div class="col-md-3 form-group"><label>Reference Request</label><select id="no_request" name="no_request" class="form-control"><option value="">Manual / tanpa request</option><?php foreach($requests as $r){ ?><option value="<?=htmlspecialchars($r->no_ro,ENT_QUOTES,'UTF-8');?>" data-date="<?=htmlspecialchars($r->tgl_ro,ENT_QUOTES,'UTF-8');?>"><?=htmlspecialchars($r->no_ro.' - '.$r->catatan,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
        <div class="col-md-3 form-group"><label>Request Date</label><input type="text" id="tgl_request" name="tgl_request" class="form-control" readonly></div>
      </div>
      <div class="row">
        <div class="col-md-3 form-group"><label>Source</label><input type="text" class="form-control" value="Current stock layer location" readonly><input type="hidden" name="source_bagian" value="1"></div>
        <div class="col-md-3 form-group"><label class="required-label">Destination Storage Location</label><select id="destination_storage_location_id" name="destination_storage_location_id" class="form-control mandatory" required><option value="">Pilih Storage Location</option><?php foreach($storageLocations as $s){ ?><option value="<?=intval($s->id);?>"><?=htmlspecialchars($s->plant_code.' / '.$s->storage_code.' - '.$s->storage_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
        <div class="col-md-3 form-group"><label class="required-label">Destination Storage Bin</label><select id="destination_storage_bin_id" name="destination_storage_bin_id" class="form-control mandatory" required><option value="">Pilih Storage Bin</option><?php foreach($storageBins as $b){ ?><option value="<?=intval($b->id);?>" data-storage-location-id="<?=intval($b->storage_location_id);?>"><?=htmlspecialchars($b->storage_code.' / '.$b->bin_code.' - '.$b->bin_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
        <div class="col-md-3 form-group"><label class="required-label">Destination Stock Type</label><select id="destination_stock_type" name="destination_stock_type" class="form-control mandatory" required><option value="UNRESTRICTED">Unrestricted</option><option value="QUALITY">Quality Inspection</option><option value="BLOCKED">Blocked</option></select></div>
      </div>
      <div class="row">
        <div class="col-md-3 form-group"><label>Created By</label><input type="text" readonly class="form-control" value="<?=htmlspecialchars($_SESSION['username'],ENT_QUOTES,'UTF-8');?>" name="name_ppc"></div>
        <div class="col-md-9 form-group"><label class="required-label">Reason / Remark</label><input type="text" name="catatan" id="catatan" class="form-control mandatory" placeholder="Contoh: staging produksi order / bin transfer / stock type transfer ..." required></div>
      </div>
    </div>

    <div class="tp-card">
      <div class="row">
        <div class="col-sm-8"><h4><i class="fa fa-cubes"></i> Material Items</h4></div>
        <div class="col-sm-4 text-right"><button type="button" class="btn btn-success btn-sm" id="btn_add_row"><i class="fa fa-plus"></i> Add Item</button></div>
      </div>
      <div class="alert alert-info">Pilih source material dari stock unrestricted. Destination material boleh dikosongkan bila material tetap sama. Isi destination material jika prosesnya material-to-material transfer.</div>
      <div class="table-responsive">
        <table class="table table-bordered table-striped table-condensed tp-items">
          <thead><tr><th style="width:42px">#</th><th style="min-width:280px">Source Material</th><th style="min-width:260px">Destination Material</th><th style="width:90px">UOM</th><th style="width:120px">Available</th><th style="width:130px">Transfer Qty</th><th>Remark</th><th style="width:50px"></th></tr></thead>
          <tbody id="isi_tabel"></tbody>
        </table>
      </div>
    </div>

    <div class="text-right">
      <span id="tp_submit_help" class="tp-submit-help">Lengkapi semua field mandatory dan minimal satu item valid.</span>
      <a href="<?=base_index();?>transfer-produksi" class="btn btn-default"><i class="fa fa-arrow-left"></i> Kembali</a>
      <button type="submit" id="btn_simpan" class="btn btn-primary" disabled><i class="fa fa-save"></i> Post Transfer 311</button>
    </div>
  </form>
</section>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
var rowNo=0;
function addRow(item){
  rowNo++;
  var selected=item||{};
  var tr='<tr id="baris_'+rowNo+'">'+
    '<td class="text-center">'+rowNo+'</td>'+
    '<td><select class="form-control material-select mandatory-item" name="kode_input[]" id="kode_input_'+rowNo+'" data-row="'+rowNo+'" required></select><input type="hidden" name="id_input[]" id="id_input_'+rowNo+'"></td>'+
    '<td><select class="form-control destination-material-select" name="destination_material_code[]" id="destination_material_code_'+rowNo+'" data-row="'+rowNo+'"></select></td>'+
    '<td><input type="text" name="unit[]" id="form_unit_'+rowNo+'" class="form-control" readonly></td>'+
    '<td><input type="text" name="stock[]" id="form_stock_'+rowNo+'" class="form-control text-right" readonly></td>'+
    '<td><input type="number" step="0.00001" min="0.00001" name="qty[]" id="form_qty_'+rowNo+'" class="form-control text-right qty-input mandatory-item" data-row="'+rowNo+'" required><small id="error_stock_'+rowNo+'" class="text-danger"></small></td>'+
    '<td><input type="text" name="ket[]" id="form_ket_'+rowNo+'" class="form-control"></td>'+
    '<td class="text-center"><button type="button" class="btn btn-danger btn-xs" onclick="hapus_baris('+rowNo+')"><i class="fa fa-trash"></i></button></td>'+
  '</tr>';
  $('#isi_tabel').append(tr);
  initMaterialSelect($('#kode_input_'+rowNo));
  initDestinationMaterialSelect($('#destination_material_code_'+rowNo));
  if(selected.id){
    var opt=new Option(selected.text,selected.id,true,true);
    $('#kode_input_'+rowNo).append(opt).trigger('change');
    $('#id_input_'+rowNo).val(selected.id_barang||'');
    $('#form_unit_'+rowNo).val(selected.uom||'');
    $('#form_stock_'+rowNo).val(selected.stock||0);
    $('#form_qty_'+rowNo).val(selected.qty||'');
  }
  validateForm();
}
function initMaterialSelect(el){
  el.select2({width:'100%',placeholder:'Cari material...',minimumInputLength:1,ajax:{url:'<?=base_admin();?>modul/transfer_produksi/transfer_produksi_action.php?act=material_search',type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||''};},processResults:function(d){return{results:d.results||[]};}}});
  el.on('select2:select',function(e){var r=$(this).data('row'),d=e.params.data;$('#id_input_'+r).val(d.id_barang||'');$('#form_unit_'+r).val(d.uom||'');$('#form_stock_'+r).val(d.stock||0);$('#form_qty_'+r).val('');validateForm();});
}
function initDestinationMaterialSelect(el){
  el.select2({width:'100%',allowClear:true,placeholder:'Sama dengan source',minimumInputLength:1,ajax:{url:'<?=base_admin();?>modul/transfer_produksi/transfer_produksi_action.php?act=material_master_search',type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||''};},processResults:function(d){return{results:d.results||[]};}}});
}
function hapus_baris(id){$('#baris_'+id).remove();validateForm();}
function validateRow(id){
  var qty=parseFloat($('#form_qty_'+id).val())||0, stock=parseFloat($('#form_stock_'+id).val())||0, ok=true;
  if(qty<=0){$('#error_stock_'+id).text('Qty wajib > 0');ok=false;}
  else if(qty>stock){$('#error_stock_'+id).text('Qty melebihi stock '+stock);ok=false;}
  else{$('#error_stock_'+id).text('');}
  return ok;
}
function validateForm(){
  var ok=true,itemCount=0;
  $('.mandatory').each(function(){if(!$(this).val())ok=false;});
  $('.qty-input').each(function(){itemCount++;if(!validateRow($(this).data('row')))ok=false;});
  if(itemCount===0)ok=false;
  $('#btn_simpan').prop('disabled',!ok);
  $('#tp_submit_help').text(ok?'Siap posting movement 311.':'Lengkapi semua field mandatory dan minimal satu item valid.');
}
function loadRequestItems(noRo){
  if(!noRo) return;
  $('#isi_tabel').html('<tr><td colspan="8" class="text-muted"><i class="fa fa-spinner fa-spin"></i> Memuat item request...</td></tr>');
  $.post('<?=base_admin();?>modul/transfer_produksi/transfer_produksi_action.php?act=get_detail_ro',{no_ro:noRo},function(res){
    $('#isi_tabel').empty();rowNo=0;
    if(res.status==='good' && res.items.length){$.each(res.items,function(_,it){addRow(it);});}else{addRow();}
    validateForm();
  },'json').fail(function(){ $('#isi_tabel').empty(); addRow(); validateForm(); });
}
$(function(){
  if($.fn.datepicker){$('.date-field').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  if($.fn.select2){$('#no_request,#destination_storage_location_id,#destination_storage_bin_id,#destination_stock_type').select2({width:'100%'});}
  addRow();
  $('#btn_add_row').on('click',function(){addRow();});
  $('#destination_storage_location_id').on('change',function(){
    var loc=$(this).val();
    $('#destination_storage_bin_id option').each(function(){var optionLoc=$(this).data('storage-location-id');$(this).toggle(!optionLoc||!loc||String(optionLoc)===String(loc));});
    var selectedLoc=$('#destination_storage_bin_id option:selected').data('storage-location-id');
    if(loc && selectedLoc && String(selectedLoc)!==String(loc)) $('#destination_storage_bin_id').val('').trigger('change.select2');
    validateForm();
  });
  $('#no_request').on('change',function(){var opt=$(this).find(':selected');$('#tgl_request').val(opt.data('date')||'');loadRequestItems($(this).val());});
  $(document).on('keyup change','.mandatory,.qty-input,.material-select',validateForm);
  $('#input_transfer_produksi').on('submit',function(e){
    e.preventDefault();validateForm();if($('#btn_simpan').prop('disabled'))return;
    Swal.fire({title:'Post transfer 311?',text:'Stock Gudang akan berkurang sesuai FIFO layer.',icon:'question',showCancelButton:true,confirmButtonText:'Post'}).then(function(result){
      if(!result.isConfirmed)return;
      var btn=$('#btn_simpan');btn.prop('disabled',true).html('<i class="fa fa-spinner fa-spin"></i> Posting...');
      $.ajax({url:$('#input_transfer_produksi').attr('action'),type:'POST',data:$('#input_transfer_produksi').serialize(),dataType:'json',success:function(res){
        var r=$.isArray(res)?res[0]:res;
        if(r.status==='good'){window.location='<?=base_index();?>transfer-produksi';}else{$('.isi_warning').text(r.error_message||'Posting gagal.');$('.error_data').fadeIn();btn.prop('disabled',false).html('<i class="fa fa-save"></i> Post Transfer 311');}
      },error:function(xhr){$('.isi_warning').text(xhr.responseText);$('.error_data').fadeIn();btn.prop('disabled',false).html('<i class="fa fa-save"></i> Post Transfer 311');}});
    });
  });
});
</script>
