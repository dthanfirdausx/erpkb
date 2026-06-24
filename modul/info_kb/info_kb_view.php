<?php
if (!function_exists('info_kb_logo_url')) {
    function info_kb_logo_url($logo)
    {
        $logo = trim((string) $logo);
        if ($logo === '') {
            return '';
        }
        if (strpos($logo, '/') !== false) {
            return base_admin() . $logo;
        }
        return base_admin() . 'assets/' . $logo;
    }
}
if (!function_exists('info_kb_h')) {
    function info_kb_h($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('info_kb_t')) {
    function info_kb_t($key, $fallback = '')
    {
        return lang_text('info_kb_' . $key, $fallback);
    }
}
$infoKbLang = array(
    'general_data' => info_kb_t('general_data', 'General Data'),
    'legal_tax' => info_kb_t('legal_tax', 'Legal & Tax'),
    'customs_ceisa' => info_kb_t('customs_ceisa', 'Customs / CEISA'),
    'sap_org' => info_kb_t('sap_org', 'SAP Organization'),
    'bank' => info_kb_t('bank', 'Bank'),
    'general_company_data' => info_kb_t('general_company_data', 'General Company Data'),
    'legal_tax_registration' => info_kb_t('legal_tax_registration', 'Legal & Tax Registration'),
    'customs_ceisa_profile' => info_kb_t('customs_ceisa_profile', 'Customs / CEISA 4.0 Profile'),
    'sap_org_assignment' => info_kb_t('sap_org_assignment', 'SAP Organization Assignment'),
    'bank_account' => info_kb_t('bank_account', 'Bank Account'),
    'internal_code' => info_kb_t('internal_code', 'Internal Code'),
    'company_name' => info_kb_t('company_name', 'Company Name'),
    'legal_address' => info_kb_t('legal_address', 'Legal Address'),
    'shipping_address' => info_kb_t('shipping_address', 'Shipping Address'),
    'province' => info_kb_t('province', 'Province'),
    'city' => info_kb_t('city', 'City'),
    'postal_code' => info_kb_t('postal_code', 'Postal Code'),
    'country' => info_kb_t('country', 'Country'),
    'phone' => info_kb_t('phone', 'Phone'),
    'fax' => info_kb_t('fax', 'Fax'),
    'email' => info_kb_t('email', 'Email'),
    'website' => info_kb_t('website', 'Website'),
    'logo' => info_kb_t('logo', 'Company Logo'),
    'no_logo' => info_kb_t('no_logo', 'No logo yet'),
    'npwp' => info_kb_t('npwp', 'Tax ID'),
    'tax_registration_no' => info_kb_t('tax_registration_no', 'Tax Registration No'),
    'nib' => info_kb_t('nib', 'Business ID'),
    'api' => info_kb_t('api', 'API-P / API-U'),
    'skep_kb' => info_kb_t('skep_kb', 'SKEP KB'),
    'skep_date' => info_kb_t('skep_date', 'SKEP Date'),
    'ceisa_code' => info_kb_t('ceisa_code', 'CEISA Code'),
    'facility_type' => info_kb_t('facility_type', 'Facility Type'),
    'customs_office' => info_kb_t('customs_office', 'Supervising Customs Office'),
    'company_code' => info_kb_t('company_code', 'Company Code'),
    'business_area' => info_kb_t('business_area', 'Business Area'),
    'default_plant' => info_kb_t('default_plant', 'Default Plant'),
    'purchasing_org' => info_kb_t('purchasing_org', 'Purchasing Org'),
    'sales_org' => info_kb_t('sales_org', 'Sales Org'),
    'fiscal_year_variant' => info_kb_t('fiscal_year_variant', 'Fiscal Year Variant'),
    'currency' => info_kb_t('currency', 'Currency'),
    'bank_name' => info_kb_t('bank_name', 'Bank Name'),
    'account_holder' => info_kb_t('account_holder', 'Account Holder'),
    'account_1' => info_kb_t('account_1', 'Account 1'),
    'account_2' => info_kb_t('account_2', 'Account 2'),
    'swift_code' => info_kb_t('swift_code', 'SWIFT Code'),
    'bank_currency' => info_kb_t('bank_currency', 'Bank Currency'),
    'bank_detail' => info_kb_t('bank_detail', 'Bank Detail'),
    'export_data' => info_kb_t('export_data', 'Export Data'),
);
?>
<style>
.kb-hero{background:linear-gradient(135deg,#0f766e,#1d4ed8);color:#fff;border-radius:14px;padding:20px 22px;margin-bottom:18px;box-shadow:0 10px 24px rgba(15,23,42,.16)}
.kb-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.kb-hero p{margin:0;opacity:.92}
.kb-card{border-radius:12px;background:#fff;border:1px solid #e5edf5;box-shadow:0 5px 16px rgba(15,23,42,.05);margin-bottom:14px}
#dtb_manual th,#dtb_manual td{font-size:12px;vertical-align:middle}.kb-logo-thumb{height:42px;max-width:110px;object-fit:contain;background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:5px}
.kb-action{white-space:nowrap}.kb-action .btn{border-radius:8px;margin-right:4px}.kb-muted{color:#64748b}
.kb-filter label{font-size:12px;color:#475569;text-transform:uppercase;letter-spacing:.03em}.select2-container{width:100%!important}
.kb-detail-tabs{border-bottom:1px solid #e5e7eb;margin-bottom:18px}.kb-detail-tabs>li>a{border-radius:10px 10px 0 0;color:#334155;font-weight:600}
.kb-detail-tabs>li.active>a,.kb-detail-tabs>li.active>a:focus,.kb-detail-tabs>li.active>a:hover{color:#0f766e;border-top:3px solid #0f766e}
.kb-detail-title{font-weight:700;color:#334155;margin:8px 0 14px;border-bottom:1px solid #e5e7eb;padding-bottom:8px}
.kb-detail-grid{border:1px solid #e5edf5;border-radius:12px;overflow:hidden;margin-bottom:12px}.kb-detail-row{display:flex;border-bottom:1px solid #eef2f7}.kb-detail-row:last-child{border-bottom:0}
.kb-detail-label{width:185px;background:#f8fafc;color:#475569;font-weight:700;padding:10px 12px}.kb-detail-value{flex:1;padding:10px 12px;color:#0f172a;word-break:break-word}
.kb-detail-logo{padding:14px;border:1px solid #e5e7eb;border-radius:12px;background:#f8fafc;display:inline-block}.kb-detail-logo img{max-height:96px;max-width:240px;object-fit:contain}
.kb-detail-empty{color:#94a3b8;font-style:italic}@media(max-width:767px){.kb-detail-row{display:block}.kb-detail-label{width:auto}}
</style>
<!-- Content Header (Page header) -->
<section class="content-header">
  <h1><?=info_kb_h(info_kb_t('title', 'KB Profile'));?> <small><?=info_kb_h(info_kb_t('company_profile', 'Company Profile'));?></small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
    <li><a href="<?=base_index();?>info-kb"><?=info_kb_h(info_kb_t('company', 'Company'));?></a></li>
    <li class="active"><?=info_kb_h(info_kb_t('title', 'KB Profile'));?></li>
  </ol>
</section>
<!-- Main content -->
<section class="content">
  <div class="kb-hero">
    <div class="row">
      <div class="col-md-8">
        <h1><?=info_kb_h(info_kb_t('title', 'KB Profile'));?></h1>
        <p><?=info_kb_h(info_kb_t('view_intro', 'Manage company identity, bonded zone legality, bank accounts, and company logo used in documents and reports.'));?></p>
      </div>
      <div class="col-md-4 text-right">
        <?php if($role_act["up_act"]=="Y") { ?>
          <span class="btn btn-warning disabled"><i class="fa fa-building"></i> <?=info_kb_h(info_kb_t('company_master', 'Company Master'));?></span>
        <?php } ?>
      </div>
    </div>
  </div>
  <div class="row">
    <div class="col-xs-12">
      <div class="box kb-card kb-filter">
        <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-filter"></i> <?=info_kb_h(info_kb_t('filter_title', 'KB Profile Filter'));?></h3></div>
        <div class="box-body">
          <form class="form-horizontal" onsubmit="return false;">
            <div class="form-group">
              <label class="control-label col-md-2"><?=info_kb_h(info_kb_t('plant', 'Plant'));?></label>
              <div class="col-md-3">
                <select id="filter_plant" class="form-control select2">
                  <option value=""><?=info_kb_h(info_kb_t('all_plant', 'All Plants'));?></option>
                  <?php foreach($db->query("SELECT id,plant_code,plant_name FROM erp_plant ORDER BY plant_code") as $p) { ?>
                    <option value="<?=htmlspecialchars($p->plant_code.' - '.$p->plant_name, ENT_QUOTES, 'UTF-8');?>"><?=htmlspecialchars($p->plant_code.' - '.$p->plant_name, ENT_QUOTES, 'UTF-8');?></option>
                  <?php } ?>
                </select>
              </div>
              <label class="control-label col-md-2"><?=info_kb_h(info_kb_t('legal_status', 'Legal Status'));?></label>
              <div class="col-md-3">
                <select id="filter_legal" class="form-control select2">
                  <option value=""><?=info_kb_h(info_kb_t('all', 'All'));?></option>
                  <option value="Ada SKEP"><?=info_kb_h(info_kb_t('has_skep', 'Has SKEP'));?></option>
                  <option value="Belum Lengkap"><?=info_kb_h(info_kb_t('incomplete', 'Incomplete'));?></option>
                </select>
              </div>
              <div class="col-md-2"><button id="btn_reset_kb" class="btn btn-default"><i class="fa fa-refresh"></i> <?=info_kb_h(info_kb_t('reset', 'Reset'));?></button></div>
            </div>
          </form>
        </div>
      </div>
      <div class="box kb-card">
        <div class="box-header">
          <h3 class="box-title"><i class="fa fa-id-card-o"></i> <?=info_kb_h(info_kb_t('data_title', 'KB Profile Data'));?></h3>
          </div><!-- /.box-header -->
          <div class="box-body table-responsive">
 <div class="alert alert-warning fade in error_data_delete" style="display:none">
          <button type="button" class="close hide_alert_notif">&times;</button>
          <i class="icon fa fa-warning"></i> <span class="isi_warning_delete"></span>
        </div>
            <table id="dtb_manual" class="table table-bordered table-striped" style="width:100%">
              <thead>
                <tr>
                  <th style="width:25px" align="center">No</th>
                  
                                  <th><?=info_kb_h(info_kb_t('internal_code', 'Internal Code'));?></th>
                                  <th><?=info_kb_h(info_kb_t('company_code', 'Company Code'));?></th>
                                  <th><?=info_kb_h(info_kb_t('logo', 'Company Logo'));?></th>
                                  <th><?=info_kb_h(info_kb_t('company_name', 'Company Name'));?></th>
                                  <th><?=info_kb_h(info_kb_t('plant', 'Plant'));?></th>
                                  <th><?=info_kb_h(info_kb_t('address', 'Address'));?></th>
                                  <th><?=info_kb_h(info_kb_t('province', 'Province'));?></th>
                                  <th><?=info_kb_h(info_kb_t('city', 'City'));?></th>
                                  <th><?=info_kb_h(info_kb_t('npwp', 'Tax ID'));?></th>
                                  <th><?=info_kb_h(info_kb_t('ceisa_code', 'CEISA Code'));?></th>
                                  <th><?=info_kb_h(info_kb_t('phone', 'Phone'));?></th>
                                  <th><?=info_kb_h(info_kb_t('email', 'Email'));?></th>
                                  <th><?=info_kb_h(info_kb_t('fax', 'Fax'));?></th>
                                  <th><?=info_kb_h(info_kb_t('skep_kb', 'SKEP KB'));?></th>
                                  <th><?=info_kb_h(info_kb_t('skep_date', 'SKEP Date'));?></th>
                  <th><?=info_kb_h(lang_text('action', 'Action'));?></th>
                </tr>
              </thead>
              <tbody>
                
      <?php
      $dtb=$db->query("select infokb.*, p.plant_code, p.plant_name, po.org_code purchasing_org_code, po.org_name purchasing_org_name, so.org_code sales_org_code, so.org_name sales_org_name from infokb left join erp_plant p on p.id=infokb.default_plant_id left join erp_purchasing_organization po on po.id=infokb.purchasing_org_id left join erp_sales_organization so on so.id=infokb.sales_org_id");
      $i=1;
      foreach ($dtb as $isi) {
        $logo_url = info_kb_logo_url(isset($isi->logo) ? $isi->logo : '');
        $detail = array(
          'kode' => $isi->kode,
          'nama' => $isi->nama,
          'logo_url' => $logo_url,
          'alamat' => $isi->alamat,
          'alamat_kirim' => isset($isi->alamat_kirim) ? $isi->alamat_kirim : '',
          'prop' => $isi->prop,
          'kota' => $isi->kota,
          'postal_code' => isset($isi->postal_code) ? $isi->postal_code : '',
          'country' => isset($isi->country) ? $isi->country : '',
          'telp' => $isi->telp,
          'fax' => $isi->fax,
          'email' => isset($isi->email) ? $isi->email : '',
          'website' => isset($isi->website) ? $isi->website : '',
          'npwp' => $isi->npwp,
          'tax_registration_no' => isset($isi->tax_registration_no) ? $isi->tax_registration_no : '',
          'nomor_nib' => isset($isi->nomor_nib) ? $isi->nomor_nib : '',
          'nomor_api' => isset($isi->nomor_api) ? $isi->nomor_api : '',
          'skepkb' => $isi->skepkb,
          'tglskep' => $isi->tglskep,
          'pbob' => isset($isi->pbob) ? $isi->pbob : '',
          'cdob' => isset($isi->cdob) ? $isi->cdob : '',
          'kode_ceisa' => isset($isi->kode_ceisa) ? $isi->kode_ceisa : '',
          'jenis_fasilitas' => isset($isi->jenis_fasilitas) ? $isi->jenis_fasilitas : '',
          'kantor_pengawas' => isset($isi->kantor_pengawas) ? $isi->kantor_pengawas : '',
          'company_code' => isset($isi->company_code) ? $isi->company_code : '',
          'business_area' => isset($isi->business_area) ? $isi->business_area : '',
          'default_plant' => isset($isi->plant_code) && $isi->plant_code ? $isi->plant_code.' - '.$isi->plant_name : '',
          'purchasing_org' => isset($isi->purchasing_org_code) && $isi->purchasing_org_code ? $isi->purchasing_org_code.' - '.$isi->purchasing_org_name : '',
          'sales_org' => isset($isi->sales_org_code) && $isi->sales_org_code ? $isi->sales_org_code.' - '.$isi->sales_org_name : '',
          'fiscal_year_variant' => isset($isi->fiscal_year_variant) ? $isi->fiscal_year_variant : '',
          'local_currency' => isset($isi->local_currency) ? $isi->local_currency : '',
          'bank_name' => isset($isi->bank_name) ? $isi->bank_name : '',
          'bank_account_name' => isset($isi->bank_account_name) ? $isi->bank_account_name : '',
          'rek1' => isset($isi->rek1) ? $isi->rek1 : '',
          'rek2' => isset($isi->rek2) ? $isi->rek2 : '',
          'swift_code' => isset($isi->swift_code) ? $isi->swift_code : '',
          'bank_currency' => isset($isi->bank_currency) ? $isi->bank_currency : '',
          'bank' => isset($isi->bank) ? trim(strip_tags($isi->bank)) : '',
        );
        ?><tr id="line_<?=$isi->id;?>">
          <td align="center"><?=$i;?></td>
          <td><?=$isi->kode;?></td>
          <td><?=isset($isi->company_code) && $isi->company_code ? $isi->company_code : '-';?></td>
          <td>
            <?php if ($logo_url !== '') { ?>
              <img src="<?=htmlspecialchars($logo_url, ENT_QUOTES, 'UTF-8');?>" alt="Logo PT" class="kb-logo-thumb">
            <?php } else { ?>
              <span class="kb-muted"><?=info_kb_h(info_kb_t('no_logo', 'No logo yet'));?></span>
            <?php } ?>
          </td>
          <td><?=$isi->nama;?></td>
          <td><?=isset($isi->plant_code) && $isi->plant_code ? $isi->plant_code.' - '.$isi->plant_name : '-';?></td>
          <td><?=$isi->alamat;?></td>
          <td><?=$isi->prop;?></td>
          <td><?=$isi->kota;?></td>
          <td><?=$isi->npwp;?></td>
          <td><?=isset($isi->kode_ceisa) && $isi->kode_ceisa ? $isi->kode_ceisa : '-';?></td>
          <td><?=$isi->telp;?></td>
          <td><?=isset($isi->email) && $isi->email ? $isi->email : '-';?></td>
          <td><?=$isi->fax;?></td>
          <td><?=$isi->skepkb;?></td>
          <td><?=$isi->tglskep;?></td>
        <td class="kb-action">
            <button type="button" class="btn btn-info btn-sm btn-kb-detail" data-detail="<?=info_kb_h(json_encode($detail));?>">
              <i class="fa fa-eye"></i> <?=info_kb_h(info_kb_t('detail_title', 'Detail'));?>
            </button>
            <?php
            // echo '<a href="'.base_index().'info-kb/detail/'.$isi->id.'" class="btn btn-success "><i class="fa fa-eye"></i></a> ';
            if($role_act["up_act"]=="Y") {
              echo '<a href="'.base_index().'info-kb/edit/'.$isi->id.'" data-id="'.$isi->id.'" class="btn edit_data btn-primary btn-sm"><i class="fa fa-pencil"></i> '.info_kb_h(lang_text('edit', 'Edit')).'</a> ';
            }
            // if($role_act["del_act"]=="Y") {
            //   echo '<button class="btn btn-danger hapus " data-uri="'.base_admin().'modul/info_kb/info_kb_action.php" data-id="'.$isi->id.'"><i class="fa fa-trash-o"></i></button>';
            // }
          ?>
        </td>
        </tr>
        <?php
      $i++;
      }
      ?>
              </tbody>
            </table>
            </div><!-- /.box-body -->
            </div><!-- /.box -->
          </div>
        </div>
        </section><!-- /.content -->
<div class="modal fade" id="modal_kb_detail">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title"><i class="fa fa-building"></i> <?=info_kb_h(info_kb_t('detail_title', 'KB Profile Detail'));?></h4></div>
      <div class="modal-body" id="kb_detail_body"></div>
    </div>
  </div>
</div>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script>
$(function(){
  var kbLang = <?=json_encode($infoKbLang, JSON_UNESCAPED_UNICODE);?>;
  if($.fn.select2){$('.select2').select2({width:'100%',allowClear:true});}
  function kbEsc(v){
    if(v===null || typeof v==='undefined' || v==='') return '<span class="kb-detail-empty">-</span>';
    return $('<div>').text(v).html();
  }
  function kbRow(label,value){
    return '<div class="kb-detail-row"><div class="kb-detail-label">'+kbEsc(label)+'</div><div class="kb-detail-value">'+kbEsc(value)+'</div></div>';
  }
  function kbGrid(rows){
    return '<div class="kb-detail-grid">'+rows.join('')+'</div>';
  }
  function kbLogo(url){
    if(!url) return '<span class="kb-detail-empty">'+kbEsc(kbLang.no_logo)+'</span>';
    return '<div class="kb-detail-logo"><img src="'+kbEsc(url)+'" alt="'+kbEsc(kbLang.logo)+'"></div>';
  }
  function kbDetailTabs(d){
    return ''+
      '<ul class="nav nav-tabs kb-detail-tabs">'+
        '<li class="active"><a href="#kb_detail_general" data-toggle="tab"><i class="fa fa-id-card-o"></i> '+kbEsc(kbLang.general_data)+'</a></li>'+
        '<li><a href="#kb_detail_legal" data-toggle="tab"><i class="fa fa-balance-scale"></i> '+kbEsc(kbLang.legal_tax)+'</a></li>'+
        '<li><a href="#kb_detail_customs" data-toggle="tab"><i class="fa fa-exchange"></i> '+kbEsc(kbLang.customs_ceisa)+'</a></li>'+
        '<li><a href="#kb_detail_sap" data-toggle="tab"><i class="fa fa-sitemap"></i> '+kbEsc(kbLang.sap_org)+'</a></li>'+
        '<li><a href="#kb_detail_bank" data-toggle="tab"><i class="fa fa-bank"></i> '+kbEsc(kbLang.bank)+'</a></li>'+
      '</ul>'+
      '<div class="tab-content">'+
        '<div class="tab-pane active" id="kb_detail_general">'+
          '<div class="kb-detail-title">'+kbEsc(kbLang.general_company_data)+'</div>'+
          '<div class="row"><div class="col-md-8">'+
            kbGrid([
              kbRow(kbLang.internal_code,d.kode),
              kbRow(kbLang.company_name,d.nama),
              kbRow(kbLang.legal_address,d.alamat),
              kbRow(kbLang.shipping_address,d.alamat_kirim),
              kbRow(kbLang.province,d.prop),
              kbRow(kbLang.city,d.kota),
              kbRow(kbLang.postal_code,d.postal_code),
              kbRow(kbLang.country,d.country),
              kbRow(kbLang.phone,d.telp),
              kbRow(kbLang.fax,d.fax),
              kbRow(kbLang.email,d.email),
              kbRow(kbLang.website,d.website)
            ])+
          '</div><div class="col-md-4"><div class="kb-detail-title">'+kbEsc(kbLang.logo)+'</div>'+kbLogo(d.logo_url)+'</div></div>'+
        '</div>'+
        '<div class="tab-pane" id="kb_detail_legal">'+
          '<div class="kb-detail-title">'+kbEsc(kbLang.legal_tax_registration)+'</div>'+
          kbGrid([
            kbRow(kbLang.npwp,d.npwp),
            kbRow(kbLang.tax_registration_no,d.tax_registration_no),
            kbRow(kbLang.nib,d.nomor_nib),
            kbRow(kbLang.api,d.nomor_api),
            kbRow(kbLang.skep_kb,d.skepkb),
            kbRow(kbLang.skep_date,d.tglskep),
            kbRow('PBOB',d.pbob),
            kbRow('CDOB',d.cdob)
          ])+
        '</div>'+
        '<div class="tab-pane" id="kb_detail_customs">'+
          '<div class="kb-detail-title">'+kbEsc(kbLang.customs_ceisa_profile)+'</div>'+
          kbGrid([
            kbRow(kbLang.ceisa_code,d.kode_ceisa),
            kbRow(kbLang.facility_type,d.jenis_fasilitas),
            kbRow(kbLang.customs_office,d.kantor_pengawas)
          ])+
        '</div>'+
        '<div class="tab-pane" id="kb_detail_sap">'+
          '<div class="kb-detail-title">'+kbEsc(kbLang.sap_org_assignment)+'</div>'+
          kbGrid([
            kbRow(kbLang.company_code,d.company_code),
            kbRow(kbLang.business_area,d.business_area),
            kbRow(kbLang.default_plant,d.default_plant),
            kbRow(kbLang.purchasing_org,d.purchasing_org),
            kbRow(kbLang.sales_org,d.sales_org),
            kbRow(kbLang.fiscal_year_variant,d.fiscal_year_variant),
            kbRow(kbLang.currency,d.local_currency)
          ])+
        '</div>'+
        '<div class="tab-pane" id="kb_detail_bank">'+
          '<div class="kb-detail-title">'+kbEsc(kbLang.bank_account)+'</div>'+
          kbGrid([
            kbRow(kbLang.bank_name,d.bank_name),
            kbRow(kbLang.account_holder,d.bank_account_name),
            kbRow(kbLang.account_1,d.rek1),
            kbRow(kbLang.account_2,d.rek2),
            kbRow(kbLang.swift_code,d.swift_code),
            kbRow(kbLang.bank_currency,d.bank_currency),
            kbRow(kbLang.bank_detail,d.bank)
          ])+
        '</div>'+
      '</div>';
  }
  $(document).off('click.infoKbDetail').on('click.infoKbDetail','.btn-kb-detail',function(){
    var d=$(this).data('detail') || {};
    if (typeof d === 'string') {
      try { d = JSON.parse(d); } catch(e) { d = {}; }
    }
    $('#kb_detail_body').html(kbDetailTabs(d));
    $('#modal_kb_detail').modal('show');
  });

  var dt=null;
  if($.fn.DataTable){
    try {
      var dtOptions = {
        pageLength:25,
        columnDefs:[{targets:[16],orderable:false,searchable:false}]
      };
      if ($.fn.dataTable && $.fn.dataTable.Buttons) {
        dtOptions.dom = "<'row'<'col-sm-12'B>>"+"<'row'<'col-sm-6'l><'col-sm-6'f>>"+"<'row'<'col-sm-12'tr>>"+"<'row'<'col-sm-5'i><'col-sm-7'p>>";
        dtOptions.buttons = [{extend:'collection',text:kbLang.export_data || 'Export Data',buttons:['copyHtml5','excelHtml5','print']}];
      }
      dt=$('#dtb_manual').DataTable(dtOptions);
    } catch (e) {
      console.warn('KB Profile DataTable init skipped:', e);
      dt = null;
    }
  }
  $('#filter_plant').on('change',function(){if(dt)dt.column(5).search($(this).val()).draw();});
  $('#filter_legal').on('change',function(){if(!dt)return;var v=$(this).val();dt.column(14).search(v==='Ada SKEP'?'.+':(v==='Belum Lengkap'?'^$':''),true,false).draw();});
  $('#btn_reset_kb').on('click',function(){$('#filter_plant,#filter_legal').val('').trigger('change');if(dt)dt.search('').columns().search('').draw();});
});
</script>
