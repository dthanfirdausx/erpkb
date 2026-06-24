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
function so_form_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function so_form_v($record, $field, $default = '') {
  return $record && isset($record->$field) && $record->$field !== null ? $record->$field : $default;
}
function so_form_selected($a, $b) { return (string)$a === (string)$b ? ' selected' : ''; }

$isEdit = isset($soMode) && $soMode === 'edit';
$action = base_admin().'modul/sales_order/sales_order_action.php?act='.($isEdit ? 'up' : 'in');
$title = $isEdit ? 'Edit Sales Order' : 'Create Sales Order';
$subtitle = $isEdit ? 'Update dokumen SD dengan kontrol field SAP.' : 'Buat Sales Order standar SAP SD dari quotation atau manual.';
$soNo = $isEdit ? so_form_v($soRecord, 'no_sales_order') : get_nomor_transaksi('so');
$today = date('Y-m-d');

$salesOrgs = $db->query("SELECT id,org_code,org_name FROM erp_sales_organization WHERE status='Aktif' OR status='ACTIVE' ORDER BY org_code");
$channels = $db->query("SELECT dc.id,dc.channel_code,dc.channel_name,so.org_code FROM erp_distribution_channel dc LEFT JOIN erp_sales_organization so ON so.id=dc.sales_org_id WHERE dc.status='Aktif' OR dc.status='ACTIVE' ORDER BY so.org_code,dc.channel_code");
$plants = $db->query("SELECT id,plant_code,plant_name FROM erp_plant WHERE status='Aktif' OR status='ACTIVE' ORDER BY plant_code");
$slocs = $db->query("SELECT id,storage_code,storage_name,plant_id FROM erp_storage_location WHERE status='Aktif' OR status='ACTIVE' ORDER BY storage_code");
$customers = $db->query("SELECT kode_penerima,nama,alamat,npwp FROM penerima ORDER BY nama");
$currencies = $db->query("SELECT jenis_valas FROM matauang GROUP BY jenis_valas ORDER BY jenis_valas");
$terms = $db->query("SELECT jenis_term,net_day FROM term_payment ORDER BY net_day,jenis_term");
$quotations = $db->query("SELECT id_quotation,no_sales_quotation,kode_penerima,customer_name,status FROM sales_quotation WHERE status IN ('OPEN','SENT','ACCEPTED') ORDER BY tgl DESC,id_quotation DESC");
$salesOrgRows = $salesOrgs ? iterator_to_array($salesOrgs) : array();
$channelRows = $channels ? iterator_to_array($channels) : array();
$plantRows = $plants ? iterator_to_array($plants) : array();
$slocRows = $slocs ? iterator_to_array($slocs) : array();
$customerRows = $customers ? iterator_to_array($customers) : array();
$currencyRows = $currencies ? iterator_to_array($currencies) : array();
$termRows = $terms ? iterator_to_array($terms) : array();
$quotationRows = $quotations ? iterator_to_array($quotations) : array();

$rows = array();
if ($isEdit && $soItems) {
  foreach ($soItems as $item) $rows[] = $item;
}
if (!$rows) {
  $rows[] = (object) array(
    'line_no'=>10,'item_category'=>'TAN','kd_barang'=>'','nm_barang'=>'','satuan'=>'','store'=>'',
    'plant_id'=>'','storage_location_id'=>'','requested_delivery_date'=>so_form_v($soRecord,'delivery_date',$today),
    'qty'=>'','price'=>'','discount_percent'=>0,'tax_percent'=>0,'nilai'=>'','ket'=>''
  );
}
?>
<style>
.so-form-hero{background:linear-gradient(135deg,#0f766e,#1d4ed8);color:#fff;border-radius:16px;padding:22px;margin-bottom:18px;box-shadow:0 12px 28px rgba(15,23,42,.18)}
.so-form-hero h1{margin:0 0 6px;font-size:26px;font-weight:800}.so-form-hero p{margin:0;opacity:.92}.so-card{border:1px solid #e5edf5;border-radius:16px;background:#fff;margin-bottom:16px;box-shadow:0 8px 24px rgba(15,23,42,.055)}
.so-card .box-header{border-bottom:1px solid #edf2f7;padding:14px 18px}.so-card .box-title{font-weight:800;color:#0f172a}.so-card .box-body{padding:18px}.so-card label{font-size:12px;color:#475569;text-transform:uppercase;letter-spacing:.025em}
.required-label:after{content:" *";color:#dc2626}.select2-container{width:100%!important}.so-total-box{background:#f8fafc;border:1px solid #e5edf5;border-radius:14px;padding:14px}.so-total-box span{display:block;color:#64748b;font-size:12px}.so-total-box strong{font-size:22px;color:#0f172a}.so-action-bar .btn{margin-left:5px;margin-bottom:5px}
.so-item-card{border:1px solid #dbe7f3;border-radius:14px;background:#fff;margin-bottom:14px;box-shadow:0 5px 18px rgba(15,23,42,.05);overflow:hidden}
.so-item-card .item-card-head{display:flex;align-items:center;justify-content:space-between;gap:10px;background:#f8fafc;border-bottom:1px solid #e5edf5;padding:10px 12px}
.so-item-card .item-title{display:flex;align-items:center;gap:8px;font-weight:800;color:#0f172a}.so-item-card .item-title .badge{background:#1d4ed8}
.so-item-card .item-card-body{padding:14px}.so-item-card .item-section-title{font-size:11px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.055em;margin:4px 0 10px;border-bottom:1px dashed #dbe7f3;padding-bottom:5px}
.so-item-card label{font-size:11px}.so-item-card .form-control{height:32px;font-size:12px}.so-item-card textarea.form-control{height:auto}.so-item-card .amount-field{font-weight:800;background:#f8fafc}
.so-floating-save{position:fixed;right:24px;top:24px;z-index:1040;display:flex;align-items:center;gap:12px;background:#0f172a;color:#fff;border:1px solid rgba(255,255,255,.18);border-radius:18px;padding:12px 14px;box-shadow:0 16px 38px rgba(15,23,42,.34);opacity:0;visibility:hidden;transform:translateY(-12px);pointer-events:none;transition:opacity .2s ease,transform .2s ease,visibility .2s ease}
.so-floating-save.is-visible{opacity:1;visibility:visible;transform:translateY(0);pointer-events:auto}
.so-floating-save .float-caption{font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:#cbd5e1}.so-floating-save .float-total{font-weight:800;font-size:15px;line-height:1.1}.so-floating-save .btn{border-radius:12px;font-weight:800;padding:9px 16px;box-shadow:0 7px 18px rgba(245,158,11,.28)}
@media(max-width:767px){.so-floating-save{left:12px;right:12px;top:12px;justify-content:space-between;border-radius:14px}.so-floating-save .float-total{font-size:13px}.so-floating-save .btn{padding:8px 12px}}
</style>

<section class="content-header">
  <h1><?=sd_h('sales_order', 'Sales Order');?> <small>SAP SD Order Entry</small></h1>
  <ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=sd_h('common_home', 'Home');?></a></li><li><a href="<?=base_index();?>sales-order"><?=sd_h('sales_order', 'Sales Order');?></a></li><li class="active"><?=$isEdit?'Edit':'Add';?></li></ol>
</section>

<section class="content">
  <div class="so-form-hero">
    <div class="row">
      <div class="col-md-8"><h1><?=so_form_h($title);?></h1><p><?=so_form_h($subtitle);?></p></div>
      <div class="col-md-4 text-right so-action-bar">
        <a href="<?=base_index();?>sales-order" class="btn btn-default"><i class="fa fa-arrow-left"></i> Kembali</a>
        <button type="submit" form="form_sales_order" class="btn btn-warning"><i class="fa fa-save"></i> Simpan Sales Order</button>
      </div>
    </div>
  </div>

  <div class="alert alert-danger error_data" style="display:none"><button type="button" class="close" data-dismiss="alert">&times;</button><span class="isi_warning"></span></div>

  <form id="form_sales_order" method="post" action="<?=$action;?>">
    <?php if ($isEdit) { ?><input type="hidden" name="id" value="<?=intval($soRecord->id_sales_order);?>"><?php } ?>
    <div class="row">
      <div class="col-md-6">
        <div class="box so-card">
          <div class="box-header"><h3 class="box-title"><i class="fa fa-file-text-o"></i> Order Header</h3></div>
          <div class="box-body">
            <div class="row">
              <div class="col-md-4 form-group"><label>SO Number</label><input name="no_sales_order_display" class="form-control" value="<?=so_form_h($soNo);?>" readonly></div>
              <div class="col-md-4 form-group"><label class="required-label">Order Type</label><select name="order_type" class="form-control so-select2" required>
                <?php foreach(array('OR'=>'Standard Order','ZEXP'=>'Export Order','ZKB'=>'Kawasan Berikat','ZSAM'=>'Sample Order','ZRET'=>'Return Order') as $code=>$label){ ?><option value="<?=$code;?>"<?=so_form_selected(so_form_v($soRecord,'order_type','OR'),$code);?>><?=$code.' - '.$label;?></option><?php } ?>
              </select></div>
              <div class="col-md-4 form-group"><label class="required-label">SO Date</label><div class="input-group date so-date"><input name="so_date" class="form-control" value="<?=so_form_h(so_form_v($soRecord,'so_date',$today));?>" required autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
            </div>
            <div class="row">
              <div class="col-md-5 form-group"><label>Reference Quotation</label><select name="id_quotation" id="id_quotation" class="form-control so-select2"><option value="">Manual / tanpa quotation</option><?php foreach($quotationRows as $q){ ?><option value="<?=intval($q->id_quotation);?>"<?=so_form_selected(so_form_v($soRecord,'id_quotation'),$q->id_quotation);?>><?=so_form_h($q->no_sales_quotation.' - '.($q->customer_name ?: $q->kode_penerima));?></option><?php } ?></select></div>
              <div class="col-md-4 form-group"><label><?=sd_h('sales_customer_po', 'Customer PO');?></label><input name="no_po" class="form-control" value="<?=so_form_h(so_form_v($soRecord,'no_po'));?>" placeholder="PO customer"></div>
              <div class="col-md-3 form-group"><label>Purchase Ref</label><input name="purchase_ref" class="form-control" value="<?=so_form_h(so_form_v($soRecord,'purchase_ref'));?>"></div>
            </div>
            <div class="row">
              <div class="col-md-4 form-group"><label class="required-label"><?=sd_h('sales_currency', 'Currency');?></label><select name="currency" id="currency" class="form-control so-select2" required><option value="">Pilih</option><?php foreach($currencyRows as $c){ ?><option value="<?=so_form_h($c->jenis_valas);?>"<?=so_form_selected(so_form_v($soRecord,'currency','IDR'),$c->jenis_valas);?>><?=so_form_h($c->jenis_valas);?></option><?php } ?></select></div>
              <div class="col-md-4 form-group"><label>Exchange Rate</label><input type="number" step="0.0001" name="rupiah_rate" id="rupiah_rate" class="form-control" value="<?=so_form_h(so_form_v($soRecord,'rupiah_rate',1));?>"></div>
              <div class="col-md-4 form-group"><label>Sales PIC</label><input name="sales_id" class="form-control" value="<?=so_form_h(so_form_v($soRecord,'sales_id'));?>" placeholder="Sales / PIC"></div>
            </div>
            <div class="row">
              <div class="col-md-4 form-group"><label>Approval Status</label><input class="form-control" value="<?=so_form_h(so_form_v($soRecord,'approval_status','SUBMITTED'));?>" readonly></div>
              <div class="col-md-4 form-group"><label>Delivery Block</label><select name="delivery_block" class="form-control so-select2"><option value="">No Block</option><option value="CREDIT"<?=so_form_selected(so_form_v($soRecord,'delivery_block'),'CREDIT');?>>Credit Block</option><option value="MANUAL"<?=so_form_selected(so_form_v($soRecord,'delivery_block'),'MANUAL');?>>Manual Review</option></select></div>
              <div class="col-md-4 form-group"><label>Billing Block</label><select name="billing_block" class="form-control so-select2"><option value="">No Block</option><option value="PRICE"<?=so_form_selected(so_form_v($soRecord,'billing_block'),'PRICE');?>>Pricing Check</option><option value="MANUAL"<?=so_form_selected(so_form_v($soRecord,'billing_block'),'MANUAL');?>>Manual Review</option></select></div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-6">
        <div class="box so-card">
          <div class="box-header"><h3 class="box-title"><i class="fa fa-sitemap"></i> Sales Area & Partner</h3></div>
          <div class="box-body">
            <div class="row">
              <div class="col-md-4 form-group"><label class="required-label">Sales Org</label><select name="sales_org_id" id="sales_org_id" class="form-control so-select2" required><option value="">Pilih</option><?php foreach($salesOrgRows as $o){ ?><option value="<?=intval($o->id);?>"<?=so_form_selected(so_form_v($soRecord,'sales_org_id'),$o->id);?>><?=so_form_h($o->org_code.' - '.$o->org_name);?></option><?php } ?></select></div>
              <div class="col-md-4 form-group"><label class="required-label">Distribution Channel</label><select name="distribution_channel_id" class="form-control so-select2" required><option value="">Pilih</option><?php foreach($channelRows as $ch){ ?><option value="<?=intval($ch->id);?>"<?=so_form_selected(so_form_v($soRecord,'distribution_channel_id'),$ch->id);?>><?=so_form_h($ch->channel_code.' - '.$ch->channel_name);?></option><?php } ?></select></div>
              <div class="col-md-4 form-group"><label>Division</label><input name="division_code" class="form-control" value="<?=so_form_h(so_form_v($soRecord,'division_code','00'));?>" placeholder="00"></div>
            </div>
            <div class="row">
              <?php $soldTo = so_form_v($soRecord,'sold_to_party',so_form_v($soRecord,'kode_penerima')); $shipTo = so_form_v($soRecord,'ship_to_party',$soldTo); $billTo = so_form_v($soRecord,'bill_to_party',$soldTo); $payer = so_form_v($soRecord,'payer',$soldTo); ?>
              <div class="col-md-6 form-group"><label class="required-label">Sold-to Party</label><select name="sold_to_party" id="sold_to_party" class="form-control partner-select" required><option value="">Pilih customer</option><?php foreach($customerRows as $c){ ?><option value="<?=so_form_h($c->kode_penerima);?>"<?=so_form_selected($soldTo,$c->kode_penerima);?> data-address="<?=so_form_h($c->alamat);?>"><?=so_form_h($c->kode_penerima.' - '.$c->nama);?></option><?php } ?></select></div>
              <div class="col-md-6 form-group"><label class="required-label">Ship-to Party</label><select name="ship_to_party" id="ship_to_party" class="form-control partner-select" required><option value="">Pilih customer</option><?php foreach($customerRows as $c){ ?><option value="<?=so_form_h($c->kode_penerima);?>"<?=so_form_selected($shipTo,$c->kode_penerima);?> data-address="<?=so_form_h($c->alamat);?>"><?=so_form_h($c->kode_penerima.' - '.$c->nama);?></option><?php } ?></select></div>
            </div>
            <div class="row">
              <div class="col-md-6 form-group"><label>Bill-to Party</label><select name="bill_to_party" id="bill_to_party" class="form-control partner-select"><option value="">Ikuti Sold-to</option><?php foreach($customerRows as $c){ ?><option value="<?=so_form_h($c->kode_penerima);?>"<?=so_form_selected($billTo,$c->kode_penerima);?>><?=so_form_h($c->kode_penerima.' - '.$c->nama);?></option><?php } ?></select></div>
              <div class="col-md-6 form-group"><label>Payer</label><select name="payer" id="payer" class="form-control partner-select"><option value="">Ikuti Sold-to</option><?php foreach($customerRows as $c){ ?><option value="<?=so_form_h($c->kode_penerima);?>"<?=so_form_selected($payer,$c->kode_penerima);?>><?=so_form_h($c->kode_penerima.' - '.$c->nama);?></option><?php } ?></select></div>
            </div>
            <div class="row">
              <div class="col-md-6 form-group"><label>Consignee</label><input name="consignee" id="consignee" class="form-control" value="<?=so_form_h(so_form_v($soRecord,'consignee'));?>"></div>
              <div class="col-md-6 form-group"><label>Notify Party</label><input name="notify_party" class="form-control" value="<?=so_form_h(so_form_v($soRecord,'notify_party'));?>"></div>
            </div>
            <input type="hidden" name="kode_penerima" id="kode_penerima" value="<?=so_form_h($soldTo);?>">
          </div>
        </div>
      </div>
    </div>

    <div class="box so-card">
      <div class="box-header"><h3 class="box-title"><i class="fa fa-truck"></i> Delivery, Billing & Terms</h3></div>
      <div class="box-body">
        <div class="row">
          <div class="col-md-2 form-group"><label class="required-label">Req. Delivery Date</label><div class="input-group date so-date"><input name="delivery_date" id="delivery_date" class="form-control" value="<?=so_form_h(so_form_v($soRecord,'delivery_date',$today));?>" required autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
          <div class="col-md-2 form-group"><label>Incoterm</label><input name="incoterm" class="form-control text-uppercase" value="<?=so_form_h(so_form_v($soRecord,'incoterm','EXW'));?>" placeholder="EXW/FOB/CIF"></div>
          <div class="col-md-2 form-group"><label>Delivery Term</label><input name="delivery_term" class="form-control" value="<?=so_form_h(so_form_v($soRecord,'delivery_term'));?>" placeholder="FRANCO / FOB"></div>
          <div class="col-md-3 form-group"><label><?=sd_h('sales_payment_term', 'Payment Term');?></label><select name="payment_term" id="payment_term" class="form-control so-select2"><option value="">Pilih</option><?php foreach($termRows as $t){ ?><option value="<?=so_form_h($t->jenis_term);?>" data-day="<?=intval($t->net_day);?>"<?=so_form_selected(so_form_v($soRecord,'payment_term',so_form_v($soRecord,'term')),$t->jenis_term);?>><?=so_form_h($t->jenis_term.' - Net '.$t->net_day.' hari');?></option><?php } ?></select><input type="hidden" name="term" id="term" value="<?=so_form_h(so_form_v($soRecord,'term'));?>"></div>
          <div class="col-md-3 form-group"><label><?=sd_h('sales_tax', 'Tax');?></label><select name="tax" id="tax" class="form-control so-select2"><option value="include"<?=so_form_selected(so_form_v($soRecord,'tax','include'),'include');?>>Include</option><option value="exclude"<?=so_form_selected(so_form_v($soRecord,'tax'),'exclude');?>>Exclude</option><option value="none"<?=so_form_selected(so_form_v($soRecord,'tax'),'none');?>>Non Tax</option></select></div>
        </div>
        <div class="row">
          <div class="col-md-4 form-group"><label>Shipping Address</label><textarea name="shipping_address" id="shipping_address" class="form-control" rows="3"><?=so_form_h(so_form_v($soRecord,'shipping_address'));?></textarea></div>
          <div class="col-md-2 form-group"><label>Transport / Vessel</label><input name="vessel" class="form-control" value="<?=so_form_h(so_form_v($soRecord,'vessel'));?>"></div>
          <div class="col-md-3 form-group"><label>From</label><input name="dari" class="form-control" value="<?=so_form_h(so_form_v($soRecord,'dari',infokb()->nama.', '.infokb()->kota.', INDONESIA'));?>"></div>
          <div class="col-md-3 form-group"><label>To</label><input name="ke" class="form-control" value="<?=so_form_h(so_form_v($soRecord,'ke'));?>"></div>
        </div>
        <div class="row">
          <div class="col-md-4 form-group"><label>Other Reference</label><input name="other_reference" class="form-control" value="<?=so_form_h(so_form_v($soRecord,'other_reference'));?>"></div>
          <div class="col-md-4 form-group"><label>Discount Header</label><input type="number" step="0.01" name="discount" class="form-control" value="<?=so_form_h(so_form_v($soRecord,'discount',0));?>"></div>
          <div class="col-md-4 form-group"><label>Note</label><input name="catatan" class="form-control" value="<?=so_form_h(so_form_v($soRecord,'catatan'));?>"></div>
        </div>
      </div>
    </div>

    <div class="box so-card">
      <div class="box-header">
        <h3 class="box-title"><i class="fa fa-list"></i> Item Overview</h3>
        <div class="box-tools"><button type="button" class="btn btn-primary btn-sm" id="btn_add_item"><i class="fa fa-plus"></i> Add Item</button></div>
      </div>
      <div class="box-body">
        <div id="so_items">
          <?php $idx=0; foreach($rows as $r){ $idx++; $materialText = trim((string)$r->kd_barang.' - '.(string)$r->nm_barang, ' -'); ?>
          <div class="so-item-card">
            <div class="item-card-head">
              <div class="item-title"><span class="badge item-line-badge"><?=intval($r->line_no ?: ($idx*10));?></span><span>Sales Order Item</span></div>
              <button type="button" class="btn btn-danger btn-xs btn-remove-item"><i class="fa fa-trash"></i> Remove</button>
            </div>
            <div class="item-card-body">
              <div class="item-section-title">Basic Data</div>
              <div class="row">
                <div class="col-md-2 col-sm-4 form-group"><label>Line</label><input name="line_no[]" class="form-control line-no" value="<?=so_form_h($r->line_no ?: ($idx*10));?>" readonly></div>
                <div class="col-md-2 col-sm-4 form-group"><label>Item Cat.</label><select name="item_category[]" class="form-control"><option value="TAN"<?=so_form_selected($r->item_category,'TAN');?>>TAN</option><option value="TANN"<?=so_form_selected($r->item_category,'TANN');?>>TANN</option><option value="TAS"<?=so_form_selected($r->item_category,'TAS');?>>TAS</option><option value="ZFG"<?=so_form_selected($r->item_category,'ZFG');?>>ZFG</option></select></div>
                <div class="col-md-6 col-sm-8 form-group"><label class="required-label"><?=sd_h('sales_material', 'Material');?></label><select name="kode_input[]" class="form-control material-select" required><?php if($r->kd_barang){ ?><option value="<?=so_form_h($r->kd_barang);?>" selected><?=so_form_h($materialText);?></option><?php } ?></select></div>
                <div class="col-md-2 col-sm-4 form-group"><label><?=sd_h('sales_uom', 'UOM');?></label><input name="unit[]" class="form-control uom-field" value="<?=so_form_h($r->satuan);?>" readonly></div>
              </div>

              <div class="item-section-title">Location & Delivery</div>
              <div class="row">
                <div class="col-md-3 col-sm-4 form-group"><label><?=sd_h('sales_plant', 'Plant');?></label><select name="plant_id[]" class="form-control plant-field"><?php foreach($plantRows as $p){ ?><option value="<?=intval($p->id);?>"<?=so_form_selected($r->plant_id,$p->id);?>><?=so_form_h($p->plant_code.' - '.$p->plant_name);?></option><?php } ?></select></div>
                <div class="col-md-3 col-sm-4 form-group"><label>SLoc</label><select name="storage_location_id[]" class="form-control sloc-field"><option value="">-</option><?php foreach($slocRows as $s){ ?><option value="<?=intval($s->id);?>" data-plant="<?=intval($s->plant_id);?>"<?=so_form_selected($r->storage_location_id,$s->id);?>><?=so_form_h($s->storage_code.' - '.$s->storage_name);?></option><?php } ?></select></div>
                <div class="col-md-3 col-sm-4 form-group"><label>Req. Delivery Date</label><input name="requested_delivery_date[]" class="form-control date-line" value="<?=so_form_h($r->requested_delivery_date ?: so_form_v($soRecord,'delivery_date',$today));?>"></div>
                <div class="col-md-3 col-sm-12 form-group"><label>Remark</label><input name="ket[]" class="form-control" value="<?=so_form_h($r->ket);?>"></div>
              </div>

              <div class="item-section-title">Quantity & Pricing</div>
              <div class="row">
                <div class="col-md-2 col-sm-4 form-group"><label class="required-label"><?=sd_h('sales_qty', 'Qty');?></label><input type="number" step="0.0001" name="qty[]" class="form-control qty-field text-right" value="<?=so_form_h($r->qty);?>" required></div>
                <div class="col-md-2 col-sm-4 form-group"><label class="required-label"><?=sd_h('sales_price', 'Price');?></label><input type="number" step="0.01" name="harga[]" class="form-control price-field text-right" value="<?=so_form_h($r->price);?>" required></div>
                <div class="col-md-2 col-sm-4 form-group"><label>Disc %</label><input type="number" step="0.01" name="discount_percent[]" class="form-control disc-field text-right" value="<?=so_form_h($r->discount_percent ?: 0);?>"></div>
                <div class="col-md-2 col-sm-4 form-group"><label>Tax %</label><input type="number" step="0.01" name="tax_percent[]" class="form-control tax-field text-right" value="<?=so_form_h($r->tax_percent ?: 0);?>"></div>
                <div class="col-md-4 col-sm-8 form-group"><label><?=sd_h('sales_amount', 'Amount');?></label><input name="nilai[]" class="form-control amount-field text-right" value="<?=so_form_h($r->nilai);?>" readonly></div>
              </div>
            </div>
          </div>
          <?php } ?>
        </div>
        <div class="row">
          <div class="col-md-8 text-muted">Field mandatory: Sales Area, Sold-to, Ship-to, SO Date, Delivery Date, Currency, minimal 1 item material dengan Qty & Price.</div>
          <div class="col-md-4"><div class="so-total-box text-right"><span>Grand Total</span><strong id="grand_total_label">0,00</strong><input type="hidden" id="grand_total" value="0"></div></div>
        </div>
      </div>
    </div>
  </form>
  <div class="so-floating-save">
    <div>
      <div class="float-caption">Sales Order Total</div>
      <div class="float-total" id="floating_total_label">0,00</div>
    </div>
    <button type="submit" form="form_sales_order" class="btn btn-warning"><i class="fa fa-save"></i> Simpan</button>
  </div>
</section>

<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script>
var soPlantsHtml = <?=json_encode('');?>;
var soSlocOptions = <?=json_encode(array_map(function($s){return array('id'=>$s->id,'plant_id'=>$s->plant_id,'text'=>$s->storage_code.' - '.$s->storage_name);}, $slocRows));?>;
function escHtml(v){return $('<div>').text(v==null?'':v).html();}
function fmtNum(v){return (parseFloat(v)||0).toLocaleString('id-ID',{minimumFractionDigits:2,maximumFractionDigits:2});}
function initSelects(ctx){ctx=ctx||$(document);ctx.find('.so-select2,.partner-select,.plant-field,.sloc-field').select2({width:'100%',allowClear:true});ctx.find('.material-select').each(function(){var el=$(this);if(el.data('select2'))return;el.select2({width:'100%',placeholder:<?=sd_js('sales_search_material', 'Search material...');?>,allowClear:true,minimumInputLength:1,ajax:{url:'<?=base_admin();?>modul/sales_order/sales_order_action.php?act=material_search',type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||''};},processResults:function(d){return{results:d.results||[]};}}});});}
function initDates(ctx){if($.fn.datepicker){(ctx||$(document)).find('.so-date,.date-line').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}}
function calcRow(row){var qty=parseFloat(row.find('.qty-field').val())||0,price=parseFloat(row.find('.price-field').val())||0,disc=parseFloat(row.find('.disc-field').val())||0,tax=parseFloat(row.find('.tax-field').val())||0;var net=qty*price;net=net-(net*disc/100);var amount=net+(net*tax/100);row.find('.amount-field').val(amount.toFixed(2));calcTotal();}
function calcTotal(){var total=0;$('.amount-field').each(function(){total+=parseFloat(this.value)||0;});$('#grand_total').val(total.toFixed(2));$('#grand_total_label,#floating_total_label').text(fmtNum(total));}
function applyMaterialMeta(el,data){
  var row=$(el).closest('.so-item-card'), kode=$(el).val();
  data=data||{};
  if(data.uom){row.find('.uom-field').val(data.uom);}
  if(data.price){row.find('.price-field').val(data.price);}
  if(!row.find('.uom-field').val()&&kode){
    $.post('<?=base_admin();?>modul/sales_order/sales_order_action.php?act=material_get',{kode_barang:kode},function(res){
      if(res&&res.status==='success'){
        row.find('.uom-field').val(res.uom||'');
        if(res.price)row.find('.price-field').val(res.price);
        calcRow(row);
      }
    },'json');
  }
  calcRow(row);
}
function setFloatingSaveVisible(show){$('.so-floating-save').toggleClass('is-visible',!!show);}
function initFloatingSave(){
  var hero=document.querySelector('.so-form-hero');
  if(!hero)return;
  if('IntersectionObserver' in window){
    var observer=new IntersectionObserver(function(entries){
      setFloatingSaveVisible(!entries[0].isIntersecting);
    },{threshold:0.05});
    observer.observe(hero);
    return;
  }
  var fallback=function(){setFloatingSaveVisible(hero.getBoundingClientRect().bottom<=0);};
  $(window).on('scroll resize',fallback);
  fallback();
}
function refreshLines(){$('#so_items .so-item-card').each(function(i){var line=(i+1)*10;$(this).find('.line-no').val(line);$(this).find('.item-line-badge').text(line);});}
function addItem(data){data=data||{};var line=($('#so_items .so-item-card').length+1)*10;var plantOptions='';$('.plant-field:first option').each(function(){plantOptions+='<option value="'+escHtml($(this).val())+'">'+escHtml($(this).text())+'</option>';});var slocOptions='<option value="">-</option>';$('.sloc-field:first option').each(function(){slocOptions+='<option value="'+escHtml($(this).val())+'" data-plant="'+escHtml($(this).data('plant')||'')+'">'+escHtml($(this).text())+'</option>';});var mat=data.material_code?'<option value="'+escHtml(data.material_code)+'" selected>'+escHtml(data.material_code+' - '+(data.material_name||''))+'</option>':'';var card=$('<div class="so-item-card">\
<div class="item-card-head">\
<div class="item-title"><span class="badge item-line-badge">'+line+'</span><span>Sales Order Item</span></div>\
<button type="button" class="btn btn-danger btn-xs btn-remove-item"><i class="fa fa-trash"></i> Remove</button>\
</div>\
<div class="item-card-body">\
<div class="item-section-title">Basic Data</div>\
<div class="row">\
<div class="col-md-2 col-sm-4 form-group"><label>Line</label><input name="line_no[]" class="form-control line-no" value="'+line+'" readonly></div>\
<div class="col-md-2 col-sm-4 form-group"><label>Item Cat.</label><select name="item_category[]" class="form-control"><option value="TAN">TAN</option><option value="TANN">TANN</option><option value="TAS">TAS</option><option value="ZFG">ZFG</option></select></div>\
<div class="col-md-6 col-sm-8 form-group"><label class="required-label"><?=sd_h('sales_material', 'Material');?></label><select name="kode_input[]" class="form-control material-select" required>'+mat+'</select></div>\
<div class="col-md-2 col-sm-4 form-group"><label><?=sd_h('sales_uom', 'UOM');?></label><input name="unit[]" class="form-control uom-field" value="'+escHtml(data.uom||'')+'" readonly></div>\
</div>\
<div class="item-section-title">Location & Delivery</div>\
<div class="row">\
<div class="col-md-3 col-sm-4 form-group"><label><?=sd_h('sales_plant', 'Plant');?></label><select name="plant_id[]" class="form-control plant-field">'+plantOptions+'</select></div>\
<div class="col-md-3 col-sm-4 form-group"><label>SLoc</label><select name="storage_location_id[]" class="form-control sloc-field">'+slocOptions+'</select></div>\
<div class="col-md-3 col-sm-4 form-group"><label>Req. Delivery Date</label><input name="requested_delivery_date[]" class="form-control date-line" value="'+escHtml(data.required_date||$('#delivery_date').val()||'<?=date('Y-m-d');?>')+'"></div>\
<div class="col-md-3 col-sm-12 form-group"><label>Remark</label><input name="ket[]" class="form-control" value="'+escHtml(data.remark||'')+'"></div>\
</div>\
<div class="item-section-title">Quantity & Pricing</div>\
<div class="row">\
<div class="col-md-2 col-sm-4 form-group"><label class="required-label"><?=sd_h('sales_qty', 'Qty');?></label><input type="number" step="0.0001" name="qty[]" class="form-control qty-field text-right" value="'+escHtml(data.qty||'')+'" required></div>\
<div class="col-md-2 col-sm-4 form-group"><label class="required-label"><?=sd_h('sales_price', 'Price');?></label><input type="number" step="0.01" name="harga[]" class="form-control price-field text-right" value="'+escHtml(data.price||'')+'" required></div>\
<div class="col-md-2 col-sm-4 form-group"><label>Disc %</label><input type="number" step="0.01" name="discount_percent[]" class="form-control disc-field text-right" value="'+escHtml(data.discount_percent||0)+'"></div>\
<div class="col-md-2 col-sm-4 form-group"><label>Tax %</label><input type="number" step="0.01" name="tax_percent[]" class="form-control tax-field text-right" value="'+escHtml(data.tax_percent||0)+'"></div>\
<div class="col-md-4 col-sm-8 form-group"><label><?=sd_h('sales_amount', 'Amount');?></label><input name="nilai[]" class="form-control amount-field text-right" readonly></div>\
</div>\
</div>\
</div>');$('#so_items').append(card);initSelects(card);initDates(card);calcRow(card);}
$(function(){
  initSelects($(document));initDates($(document));$('.qty-field,.price-field,.disc-field,.tax-field').each(function(){calcRow($(this).closest('.so-item-card'));});
  initFloatingSave();
  $('#payment_term').on('change',function(){var day=$(this).find(':selected').data('day')||'';$('#term').val(day);});
  $('#sold_to_party').on('change',function(){var v=this.value;$('#kode_penerima').val(v);if(!$('#ship_to_party').val())$('#ship_to_party').val(v).trigger('change');if(!$('#bill_to_party').val())$('#bill_to_party').val(v).trigger('change');if(!$('#payer').val())$('#payer').val(v).trigger('change');});
  $('#ship_to_party').on('change',function(){var addr=$(this).find(':selected').data('address')||'';if(addr)$('#shipping_address').val(addr);});
  $(document).on('select2:select','.material-select',function(e){applyMaterialMeta(this,e.params.data||{});});
  $(document).on('change','.material-select',function(){applyMaterialMeta(this,{});});
  $(document).on('select2:clear','.material-select',function(){var row=$(this).closest('.so-item-card');row.find('.uom-field').val('');calcRow(row);});
  $(document).on('input','.qty-field,.price-field,.disc-field,.tax-field',function(){calcRow($(this).closest('.so-item-card'));});
  $('#btn_add_item').on('click',function(){addItem({});});
  $(document).on('click','.btn-remove-item',function(){if($('#so_items .so-item-card').length<=1){alert(<?=sd_js('sales_item_required', 'At least one item is required.');?>);return;}$(this).closest('.so-item-card').remove();refreshLines();calcTotal();});
  $('#id_quotation').on('change',function(){var id=this.value;if(!id)return;$.post('<?=base_admin();?>modul/sales_order/sales_order_action.php?act=quotation_load',{id_quotation:id},function(res){if(!res||res.status!=='success')return;var h=res.header||{};$('#sold_to_party').val(h.kode_penerima).trigger('change');$('#ship_to_party').val(h.kode_penerima).trigger('change');$('#currency').val(h.currency).trigger('change');$('#rupiah_rate').val(h.rupiah_rate||1);$('#payment_term').val(h.payment_term||h.term).trigger('change');$('#delivery_date').val(h.requested_delivery_date||$('#delivery_date').val());$('#tax').val(h.tax||'include').trigger('change');$('#sales_id').val(h.sales_id||'');$('#so_items').empty();$.each(res.items||[],function(_,it){addItem(it);});},'json');});
  $('#form_sales_order').on('submit',function(e){e.preventDefault();if($('#so_items .so-item-card').length===0){$('.isi_warning').text(<?=sd_js('sales_item_required', 'At least one item is required.');?>);$('.error_data').show();return;}$.ajax({url:this.action,type:'POST',data:$(this).serialize(),dataType:'json',success:function(res){var r=$.isArray(res)?res[0]:res;if(r.status==='good'){window.location='<?=base_index();?>sales-order';return;}$('.isi_warning').text(r.error_message||<?=sd_js('sales_order_save_failed', 'Sales Order failed to save.');?>);$('.error_data').show();},error:function(xhr){$('.isi_warning').text(xhr.responseText||<?=sd_js('sales_order_save_failed', 'Sales Order failed to save.');?>);$('.error_data').show();}});});
});
</script>
