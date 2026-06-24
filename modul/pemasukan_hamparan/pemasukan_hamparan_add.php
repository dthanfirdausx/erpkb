<link rel="stylesheet" href="<?=base_url();?>assets/css/jquery-ui.css">
<style>
.ui-autocomplete{z-index:2147483647}.gr-section{position:relative;margin-bottom:18px}.gr-section .box-title{font-weight:600}.gr-po-section{z-index:40}.gr-customs-section{z-index:30}.gr-po-section .box-body,.gr-po-section .row,.gr-po-section .form-group{overflow:visible}.gr-po-section .chosen-container-active.chosen-with-drop,.gr-po-section .chosen-container .chosen-drop,.gr-po-section .select2-container--open,.select2-dropdown{z-index:2147483646!important}.gr-items-scroll{display:block;width:100%;max-width:100%;overflow-x:auto!important;overflow-y:hidden!important;padding-bottom:10px;-webkit-overflow-scrolling:touch}.gr-items-scroll::-webkit-scrollbar{height:10px}.gr-items-scroll::-webkit-scrollbar-track{background:#f1f1f1;border-radius:6px}.gr-items-scroll::-webkit-scrollbar-thumb{background:#3c8dbc;border-radius:6px}.gr-scroll-hint{margin:0 0 8px;color:#777}.gr-items{width:100%!important;min-width:0;max-width:none!important;margin-bottom:0;font-size:11px}.gr-items th{white-space:nowrap;background:#f5f5f5;font-size:11px;padding:6px!important;vertical-align:middle!important}.gr-items td{vertical-align:top;padding:5px!important}.gr-items .form-control{min-width:64px;height:28px;padding:3px 6px;font-size:11px;line-height:1.3}.gr-items select.form-control{padding:3px 4px}.gr-items .btn-xs{padding:2px 5px;font-size:10px}.gr-items .material-col{min-width:260px;font-size:13px}.gr-items input[name="po_item_no[]"],.gr-items input[name="unit[]"]{min-width:56px}.gr-items input[name="open_qty[]"],.gr-items input[name="jumlah[]"],.gr-items input[name="nilai[]"]{min-width:78px}.gr-detail-row td{background:#fbfcff!important;border-top:0!important}.gr-detail-panel{border:1px solid #d9edf7;border-radius:4px;background:#fff;padding:10px 10px 2px}.gr-detail-title{font-weight:600;color:#31708f;margin-bottom:8px}.gr-detail-grid .form-group{margin-bottom:8px}.gr-detail-grid label{font-size:11px;margin-bottom:3px;color:#555}.gr-detail-grid .form-control{width:100%;min-width:0;font-size:12px}.gr-required-missing{border-color:#dd4b39!important;background:#fff8f8!important}.gr-submit-help{display:inline-block;margin-right:10px;color:#dd4b39;font-size:12px}.required-label:after{content:' *';color:#dd4b39}.help-customs{margin:0 0 15px;color:#666}
</style>
<section class="content-header">
  <h1>GR for Purchase Order <small>SAP MM Movement Type 101</small></h1>
  <ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li><li><a href="<?=base_index();?>pemasukan-hamparan">Goods Receipt</a></li><li class="active">Create</li></ol>
</section>
<section class="content">
<form id="input_pemasukan_hamparan" method="post" action="<?=base_admin();?>modul/pemasukan_hamparan/pemasukan_hamparan_action.php?act=in">
  <div class="alert alert-danger error_data" style="display:none"><span class="isi_warning"></span></div>

  <div class="box box-primary gr-section">
    <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-file-text-o"></i> Goods Receipt Document</h3></div>
    <div class="box-body"><div class="row">
      <div class="col-md-3 form-group"><label class="required-label">Document Date</label><input type="text" name="document_date" class="form-control date-field" value="<?=date('Y-m-d');?>" required></div>
      <div class="col-md-3 form-group"><label class="required-label">Posting Date</label><input type="text" name="posting_date" class="form-control date-field" value="<?=date('Y-m-d');?>" required></div>
      <div class="col-md-3 form-group"><label>Movement Type</label><input class="form-control" value="101 - GR for Purchase Order" readonly><input type="hidden" name="move_code" value="101"></div>
      <div class="col-md-3 form-group"><label class="required-label">Stock Type</label><select name="stock_type" class="form-control" required><option value="UNRESTRICTED">Unrestricted Use</option><option value="QUALITY">Quality Inspection</option><option value="BLOCKED">Blocked Stock</option></select></div>
    </div></div>
  </div>

  <div class="box box-info gr-section gr-po-section">
    <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-shopping-cart"></i> Purchase Order and Warehouse</h3></div>
    <div class="box-body"><div class="row">
      <div class="col-md-4 form-group"><label class="required-label">Purchase Order</label><select id="nopo" name="nopo" class="form-control chzn-select" required><option value="">Pilih PO</option><?php foreach($db->query("SELECT po.purchase_order_no, SUM(GREATEST(COALESCE(d.qty,0)-COALESCE(d.received_qty,0),0)) AS open_qty FROM purchase_order po JOIN purchase_order_detail d ON (d.id_po=po.id OR d.po_no=po.purchase_order_no) WHERE UPPER(COALESCE(po.status,'')) NOT IN ('CLOSE','CLOSED','CANCEL','CANCELED','CANCELLED','VOID') GROUP BY po.id,po.purchase_order_no HAVING open_qty>0 ORDER BY po.purchase_order_no DESC") as $po){ ?><option value="<?=htmlspecialchars($po->purchase_order_no,ENT_QUOTES,'UTF-8');?>"><?=htmlspecialchars($po->purchase_order_no,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
      <div class="col-md-4 form-group"><label class="required-label">Vendor</label><select id="pemasok" name="pemasok" class="form-control chzn-select" required><option value="">Pilih Vendor</option><?php foreach($db->fetch_all('pemasok') as $v){ ?><option value="<?=$v->kode_pemasok;?>"><?=htmlspecialchars($v->nama,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
      <div class="col-md-4 form-group"><label class="required-label">Plant</label><select id="plant_id" name="plant_id" class="form-control" required><option value="">Pilih Plant</option><?php foreach($db->query("SELECT id,plant_code,plant_name FROM erp_plant WHERE status='Aktif' ORDER BY plant_code") as $p){ ?><option value="<?=$p->id;?>"><?=$p->plant_code;?> - <?=htmlspecialchars($p->plant_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
      <div class="col-md-4 form-group"><label class="required-label">Storage Location</label><select id="storage_location_id" name="storage_location_id" class="form-control" required><option value="">Pilih Storage Location</option><?php foreach($db->query("SELECT s.id,s.plant_id,s.storage_code,s.storage_name,p.plant_code FROM erp_storage_location s JOIN erp_plant p ON p.id=s.plant_id WHERE s.status='Aktif' ORDER BY p.plant_code,s.storage_code") as $s){ ?><option value="<?=$s->id;?>" data-plant="<?=$s->plant_id;?>"><?=$s->plant_code;?> / <?=$s->storage_code;?> - <?=htmlspecialchars($s->storage_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
      <div class="col-md-4 form-group"><label class="required-label">Delivery Note / Surat Jalan</label><input type="text" name="no_do" class="form-control" required></div>
      <div class="col-md-4 form-group"><label>Header Text</label><select id="catatan" name="catatan" class="form-control chzn-select"><option value="">Pilih</option><?php foreach($db->fetch_all('catatan') as $c){ ?><option value="<?=htmlspecialchars($c->nm_catatan,ENT_QUOTES,'UTF-8');?>"><?=htmlspecialchars($c->nm_catatan,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
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
      <div class="col-md-3 form-group"><label>Negara Asal Umum</label><select name="negara_asal" class="form-control chzn-select"><option value="">Pilih</option><?php foreach($db->fetch_all('ref_negara') as $n){ ?><option value="<?=$n->kode_negara;?>"><?=$n->kode_negara;?> - <?=htmlspecialchars($n->negara,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
      <div class="col-md-3 form-group"><label>Status CEISA</label><select name="customs_status" class="form-control"><option value="DRAFT">Draft</option><option value="SUBMITTED">Submitted</option><option value="REGISTERED" selected>Registered</option><option value="RELEASED">Released</option></select></div>
      <div class="col-md-3 form-group"><label>Valuta</label><select id="valuta" name="valuta" class="form-control chzn-select"><option value="">Pilih</option><?php foreach($db->fetch_all('matauang') as $m){ ?><option value="<?=$m->jenis_valas;?>"><?=$m->jenis_valas;?></option><?php } ?></select></div>
      <div class="col-md-3 form-group"><label>Kurs Pabean</label><input type="number" step="0.000001" min="0" name="kurs" class="form-control"></div>
      <div class="col-md-3 form-group"><label>Nomor Invoice</label><input type="text" name="no_invoice" class="form-control"></div>
      <div class="col-md-3 form-group"><label>Tanggal Invoice</label><input type="text" name="tgl_invoice" class="form-control date-field"></div>
      <div class="col-md-3 form-group"><label>Nomor Kontrak</label><input type="text" name="no_kontrak" class="form-control"></div>
      <div class="col-md-3 form-group"><label>Tanggal Kontrak</label><input type="text" name="tgl_kontrak" class="form-control date-field"></div>
      <div class="col-md-3 form-group"><label>Nomor E-Faktur</label><input type="text" name="efaktur" class="form-control"></div>
      <div class="col-md-3 form-group"><label>Tanggal E-Faktur</label><input type="text" name="tgl_efaktur" class="form-control date-field"></div>
    </div></div>
  </div>

  <div class="box box-success gr-section">
    <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-cubes"></i> Material and Customs Items</h3><div class="box-tools"><span class="label label-info">Item otomatis dari PO outstanding</span></div></div>
    <div class="box-body"><p class="help-customs">Untuk GR for Purchase Order, material tidak ditambah manual di form ini. Tambahkan atau revisi item di Purchase Order terlebih dahulu, lalu pilih ulang PO di form GR.</p><div class="gr-items-scroll"><table class="table table-bordered gr-items"><thead><tr>
      <th></th><th>PO Item</th><th>Material</th><th>UOM</th><th>Open Qty</th><th class="required-label">GR Qty</th><th>Batch/Lot</th><th>Storage Bin</th><th>Amount</th><th>Detail</th>
    </tr></thead><tbody id="isi_tabel"></tbody></table></div><input type="hidden" id="jml" value="0"></div>
  </div>
  <div class="text-right"><span id="gr_submit_help" class="gr-submit-help">Lengkapi semua field mandatory untuk posting GR.</span><a href="<?=base_index();?>pemasukan-hamparan" class="btn btn-default"><i class="fa fa-arrow-left"></i> Kembali</a> <button type="submit" id="btn_post_gr" class="btn btn-primary" disabled><i class="fa fa-save"></i> Post Goods Receipt</button></div>
</form>
</section>
<script src="<?=base_url();?>assets/js/jquery-ui.js"></script>
<script>
var grBins = <?=json_encode(array_map(function($b){return array('id'=>$b->id,'location'=>$b->storage_location_id,'label'=>$b->bin_code.' - '.$b->bin_name);},iterator_to_array($db->query("SELECT id,storage_location_id,bin_code,bin_name FROM erp_storage_bin WHERE status='Aktif' ORDER BY bin_code"))));?>;
var packageOptions = <?=json_encode(array_map(function($x){return array('value'=>$x->id_kemasan,'label'=>$x->id_kemasan.' - '.$x->kemasan);},iterator_to_array($db->query("SELECT id_kemasan,kemasan FROM ref_jenis_kemasan ORDER BY id_kemasan"))));?>;
var countryOptions = <?=json_encode(array_map(function($x){return array('value'=>$x->kode_negara,'label'=>$x->kode_negara.' - '.$x->negara);},iterator_to_array($db->query("SELECT kode_negara,negara FROM ref_negara ORDER BY kode_negara"))));?>;
function esc(v){return $('<div>').text(v==null?'':v).html();}
function optionHtml(list,selected){var h='<option value="">Pilih</option>';$.each(list,function(_,o){h+='<option value="'+esc(o.value||o.id)+'" '+(String(selected||'')===String(o.value||o.id)?'selected':'')+'>'+esc(o.label)+'</option>';});return h;}
function binOptions(selected){var loc=$('#storage_location_id').val(),list=[];$.each(grBins,function(_,b){if(!loc||String(b.location)===String(loc))list.push(b);});return optionHtml(list,selected);}
function addGrRow(item){item=item||{};var id=parseInt($('#jml').val()||0)+1;$('#jml').val(id);var open=parseFloat(item.open_qty||0),qty=item.gr_qty!==undefined?item.gr_qty:open,price=parseFloat(item.harga)||0,amount=(parseFloat(qty)||0)*price;
var html='<tr id="baris_'+id+'" class="gr-main-row"><td><button type="button" class="btn btn-danger btn-xs" onclick="hapus_baris('+id+')"><i class="fa fa-trash"></i></button></td>'+
'<td><input class="form-control" name="po_item_no[]" value="'+esc(item.po_item_no||id)+'" readonly><input type="hidden" name="id_po_detail[]" value="'+esc(item.id_po_detail||'')+'"></td>'+
'<td><input class="form-control material-col" name="kode[]" value="'+esc((item.kode_barang||'')+(item.nama_barang?' - '+item.nama_barang:''))+'" readonly><input type="hidden" name="kode_input[]" value="'+esc(item.kode_barang||'')+'"></td>'+
'<td><input class="form-control" name="unit[]" value="'+esc(item.unit||'')+'" readonly></td><td><input class="form-control" name="open_qty[]" value="'+open+'" readonly></td>'+
'<td><input type="number" step="0.00001" min="0.00001" max="'+open+'" class="form-control gr-qty gr-required" name="jumlah[]" value="'+qty+'" required></td>'+
'<td><input class="form-control" name="lot_no[]"></td><td><select class="form-control storage-bin gr-required" name="storage_bin_id[]" required>'+binOptions('')+'</select><input type="hidden" name="lokasi[]" value=""></td>'+
'<td><input type="hidden" class="gr-price" name="harga[]" value="'+esc(item.harga||0)+'"><input class="form-control gr-amount" name="nilai[]" value="'+amount+'" readonly></td>'+
'<td><button type="button" class="btn btn-info btn-xs" onclick="toggleGrDetail('+id+')"><i class="fa fa-list"></i> Detail Pabean</button></td></tr>'+
'<tr id="detail_'+id+'" class="gr-detail-row"><td colspan="10"><div class="gr-detail-panel"><div class="gr-detail-title"><i class="fa fa-file-text-o"></i> Detail Pabean Item '+id+'</div><div class="row gr-detail-grid">'+
'<div class="col-md-2 form-group"><label class="required-label">Item Pabean</label><input type="number" min="1" class="form-control gr-required" name="customs_item_no[]" value="'+id+'" required></div>'+
'<div class="col-md-2 form-group"><label>HS Code</label><input class="form-control" name="hs_code[]"></div>'+
'<div class="col-md-2 form-group"><label class="required-label">Qty Pabean</label><input type="number" step="0.00001" min="0.00001" class="form-control customs-qty gr-required" name="customs_qty[]" value="'+qty+'" required></div>'+
'<div class="col-md-2 form-group"><label class="required-label">Sat. Pabean</label><input class="form-control gr-required" name="customs_uom[]" value="'+esc(item.unit||'')+'" required></div>'+
'<div class="col-md-2 form-group"><label class="required-label">Nilai Pabean</label><input type="number" step="0.00001" min="0.00001" class="form-control customs-value gr-required" name="customs_value[]" value="'+amount+'" required></div>'+
'<div class="col-md-2 form-group"><label>Net Weight</label><input type="number" step="0.00001" min="0" class="form-control" name="net_weight[]"></div>'+
'<div class="col-md-2 form-group"><label>Gross Weight</label><input type="number" step="0.00001" min="0" class="form-control" name="gross_weight[]"></div>'+
'<div class="col-md-3 form-group"><label>Jenis Kemasan</label><select class="form-control" name="package_type[]">'+optionHtml(packageOptions,'')+'</select></div>'+
'<div class="col-md-2 form-group"><label>Jml Kemasan</label><input type="number" step="0.001" min="0" class="form-control" name="package_qty[]"></div>'+
'<div class="col-md-3 form-group"><label>Negara Asal</label><select class="form-control" name="origin_country[]">'+optionHtml(countryOptions,'')+'</select></div>'+
'</div></div></td></tr>';$('#isi_tabel').append(html);updateSubmitState();}
function toggleGrDetail(id){$('#detail_'+id).toggle();}
function hapus_baris(id){$('#baris_'+id+',#detail_'+id).remove();}
function isRequiredFilled(el){var $el=$(el),val=$.trim($el.val()||'');if($el.is(':disabled'))return true;if(val==='')return false;if($el.attr('type')==='number'){var num=parseFloat(val),min=$el.attr('min')!==undefined?parseFloat($el.attr('min')):null,max=$el.attr('max')!==undefined?parseFloat($el.attr('max')):null;if(isNaN(num))return false;if(min!==null&&num<min)return false;if(max!==null&&num>max)return false;}return true;}
function updateSubmitState(){var valid=true;$('#input_pemasukan_hamparan [required]').each(function(){var ok=isRequiredFilled(this);$(this).toggleClass('gr-required-missing',!ok);if(!ok)valid=false;});if($('#isi_tabel .gr-main-row').length===0)valid=false;$('#btn_post_gr').prop('disabled',!valid);$('#gr_submit_help').toggle(!valid);return valid;}
function openFirstInvalidDetail(){var first=$('#input_pemasukan_hamparan .gr-required-missing').first();if(first.length){var detail=first.closest('.gr-detail-row');if(detail.length)detail.show();$('html,body').animate({scrollTop:first.offset().top-120},300);}}
function loadPo(noPo){if(!noPo){$('#isi_tabel').empty();updateSubmitState();return;}$.post('<?=base_url();?>modul/pemasukan_hamparan/pemasukan_hamparan_action.php?act=get_po_form',{no_po:noPo},function(res){if(!res||res.status!=='good')return;$('#pemasok').val(res.header.seller_code).trigger('chosen:updated');$('#valuta').val(res.header.currency).trigger('chosen:updated');$('#isi_tabel').empty();$('#jml').val(0);$.each(res.items,function(_,item){addGrRow(item);});updateSubmitState();},'json');}
function showGrError(message){$('.isi_warning').text(message||'Terjadi error saat posting GR for PO.');$('.error_data').show();$('html,body').animate({scrollTop:$('.error_data').offset().top-90},300);}
function parseGrResponse(response){if(typeof response==='string'){try{return JSON.parse(response);}catch(e){return [{status:'error',error_message:response}];}}return response;}
$(function(){
  if($.fn.chosen){$('.chzn-select').chosen({width:'100%'});}
  if($.fn.datepicker){$('.date-field').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  $('#nopo').change(function(){loadPo(this.value);});
  $('#input_pemasukan_hamparan').on('input change','input,select,textarea',updateSubmitState);
  $('#plant_id').change(function(){var plant=this.value;$('#storage_location_id option').each(function(){var p=$(this).data('plant');$(this).toggle(!p||String(p)===String(plant));});$('#storage_location_id').val('');updateSubmitState();});
  $('#storage_location_id').change(function(){$('.storage-bin').each(function(){var selected=$(this).val();$(this).html(binOptions(selected));});updateSubmitState();});
  $(document).on('input','.gr-qty',function(){var row=$(this).closest('tr'),id=(row.attr('id')||'').replace('baris_',''),detail=$('#detail_'+id),qty=parseFloat(this.value)||0,price=parseFloat(row.find('.gr-price').val())||0,amount=qty*price;row.find('.gr-amount').val(amount);detail.find('.customs-value').val(amount);detail.find('.customs-qty').val(qty);updateSubmitState();});
  $('#jenisbcmasuk_jenis_dokumen').change(function(){$.post('<?=base_admin();?>modul/pemasukan_hamparan/get_kd_catdet.php',{jenis_dokumen:this.value},function(data){$('#detail_catatan_kd_catdet').html(data).trigger('chosen:updated');updateSubmitState();});});
  $('#input_pemasukan_hamparan').on('submit',function(e){
    e.preventDefault();
    if(!updateSubmitState()){showGrError('Lengkapi semua field mandatory sebelum posting GR.');openFirstInvalidDetail();return false;}
    var invalid=false;
    $('.gr-qty').each(function(){var qty=parseFloat(this.value)||0,max=parseFloat($(this).attr('max'))||0;if(qty<=0||qty>max)invalid=true;});
    if(invalid){showGrError('GR Qty harus lebih dari nol dan tidak boleh melebihi Open Qty.');openFirstInvalidDetail();return false;}
    var form=this,button=$('#btn_post_gr');
    button.prop('disabled',true).data('original-text',button.html()).html('<i class="fa fa-spinner fa-spin"></i> Posting...');
    $('.error_data').hide();
    $.ajax({
      url:$(form).attr('action'),
      type:'POST',
      data:$(form).serialize(),
      dataType:'json',
      success:function(response){
        response=parseGrResponse(response);
        var result=$.isArray(response)?response[0]:response;
        if(result&&result.status==='good'){window.location='<?=base_index();?>pemasukan-hamparan';return;}
        showGrError(result&&result.error_message?result.error_message:'Posting GR for PO gagal.');
        button.prop('disabled',false).html(button.data('original-text'));
      },
      error:function(xhr){
        var response=parseGrResponse(xhr.responseText),result=$.isArray(response)?response[0]:response;
        showGrError(result&&result.error_message?result.error_message:'Server error saat posting GR for PO.');
        button.prop('disabled',false).html(button.data('original-text'));
      }
    });
    return false;
  });
  updateSubmitState();
});
</script>
