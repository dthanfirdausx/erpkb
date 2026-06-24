<!-- Summernote -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.css">
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.js"></script>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<?php
if (!function_exists('kb_h')) {
    function kb_h($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('kb_v')) {
    function kb_v($row, $field, $default = '')
    {
        return isset($row->$field) ? $row->$field : $default;
    }
}
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
if (!function_exists('info_kb_t')) {
    function info_kb_t($key, $fallback = '')
    {
        return lang_text('info_kb_' . $key, $fallback);
    }
}
$logo_url = info_kb_logo_url(kb_v($data_edit, 'logo'));
$plants = $db->query("SELECT id,plant_code,plant_name FROM erp_plant WHERE status='Aktif' ORDER BY plant_code");
$purchasingOrgs = $db->query("SELECT id,org_code,org_name FROM erp_purchasing_organization WHERE status='Aktif' ORDER BY org_code");
$salesOrgs = $db->query("SELECT id,org_code,org_name FROM erp_sales_organization WHERE status='Aktif' ORDER BY org_code");
?>
<style>
.kb-hero{background:linear-gradient(135deg,#0f766e,#1d4ed8);color:#fff;border-radius:14px;padding:20px 22px;margin-bottom:18px;box-shadow:0 10px 24px rgba(15,23,42,.16)}
.kb-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.kb-hero p{margin:0;opacity:.92}
.kb-card{border-radius:12px;background:#fff;border:1px solid #e5edf5;box-shadow:0 5px 16px rgba(15,23,42,.05);margin-bottom:14px}
.kb-tabs{border-bottom:1px solid #e5e7eb;margin-bottom:18px}.kb-tabs>li>a{border-radius:10px 10px 0 0;color:#334155;font-weight:600}
.kb-tabs>li.active>a,.kb-tabs>li.active>a:focus,.kb-tabs>li.active>a:hover{color:#0f766e;border-top:3px solid #0f766e}
.kb-section-title{font-weight:700;color:#334155;margin:8px 0 14px;border-bottom:1px solid #e5e7eb;padding-bottom:8px}
.kb-logo-preview{margin-bottom:10px;padding:14px;border:1px solid #e5e7eb;border-radius:12px;background:#f8fafc;display:inline-block}
.kb-logo-preview img{max-height:96px;max-width:240px;object-fit:contain}.kb-note{color:#64748b;font-size:12px;margin-top:6px}.kb-actions{border-top:1px solid #eef2f7;padding-top:15px;margin-top:8px}
.required-label:after{content:' *';color:#dd4b39}.select2-container{width:100%!important}
</style>
<section class="content-header">
  <h1><?=kb_h(info_kb_t('title', 'KB Profile'));?> <small><?=kb_h(info_kb_t('edit_title', 'Edit KB Profile'));?></small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
    <li><a href="<?=base_index();?>info-kb"><?=kb_h(info_kb_t('company', 'Company'));?></a></li>
    <li class="active"><?=kb_h(info_kb_t('edit_title', 'Edit KB Profile'));?></li>
  </ol>
</section>
<section class="content">
  <div class="kb-hero">
    <div class="row">
      <div class="col-md-8">
        <h1><?=kb_h(info_kb_t('edit_title', 'Edit KB Profile'));?></h1>
        <p><?=kb_h(info_kb_t('edit_intro', 'Company profile is structured like SAP enterprise data: general data, legal & tax, customs/CEISA, SAP organization assignment, and bank.'));?></p>
      </div>
      <div class="col-md-4 text-right">
        <a href="<?=base_index();?>info-kb" class="btn btn-default"><i class="fa fa-arrow-left"></i> <?=kb_h(info_kb_t('back_to_list', 'Back to List'));?></a>
      </div>
    </div>
  </div>
  <div class="box kb-card">
    <div class="box-header">
      <h3 class="box-title"><i class="fa fa-building"></i> <?=kb_h(info_kb_t('form_title', 'KB Profile Form'));?></h3>
      <div class="box-tools pull-right"><button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-pencil"></i></button></div>
    </div>
    <div class="box-body">
      <div class="alert alert-danger error_data" style="display:none"><button type="button" class="close" data-dismiss="alert">&times;</button><span class="isi_warning"></span></div>
      <form id="edit_info_kb" method="post" enctype="multipart/form-data" class="form-horizontal" action="<?=base_admin();?>modul/info_kb/info_kb_action.php?act=up">
        <ul class="nav nav-tabs kb-tabs">
          <li class="active"><a href="#tab_general" data-toggle="tab"><i class="fa fa-id-card-o"></i> <?=kb_h(info_kb_t('general_data', 'General Data'));?></a></li>
          <li><a href="#tab_legal" data-toggle="tab"><i class="fa fa-balance-scale"></i> <?=kb_h(info_kb_t('legal_tax', 'Legal & Tax'));?></a></li>
          <li><a href="#tab_customs" data-toggle="tab"><i class="fa fa-exchange"></i> <?=kb_h(info_kb_t('customs_ceisa', 'Customs / CEISA'));?></a></li>
          <li><a href="#tab_sap" data-toggle="tab"><i class="fa fa-sitemap"></i> <?=kb_h(info_kb_t('sap_org', 'SAP Organization'));?></a></li>
          <li><a href="#tab_bank" data-toggle="tab"><i class="fa fa-bank"></i> <?=kb_h(info_kb_t('bank', 'Bank'));?></a></li>
        </ul>
        <div class="tab-content">
          <div class="tab-pane active" id="tab_general">
            <div class="kb-section-title"><?=kb_h(info_kb_t('general_company_data', 'General Company Data'));?></div>
            <div class="form-group">
              <label class="control-label col-lg-2 required-label"><?=kb_h(info_kb_t('internal_code', 'Internal Code'));?></label>
              <div class="col-lg-4"><input type="text" name="kode" value="<?=kb_h(kb_v($data_edit,'kode'));?>" class="form-control text-uppercase" required></div>
              <label class="control-label col-lg-2 required-label"><?=kb_h(info_kb_t('company_name', 'Company Name'));?></label>
              <div class="col-lg-4"><input type="text" name="nama" value="<?=kb_h(kb_v($data_edit,'nama'));?>" class="form-control" required></div>
            </div>
            <div class="form-group">
              <label class="control-label col-lg-2"><?=kb_h(info_kb_t('logo', 'Company Logo'));?></label>
              <div class="col-lg-10">
                <?php if ($logo_url !== '') { ?><div class="kb-logo-preview"><img src="<?=kb_h($logo_url);?>" alt="<?=kb_h(info_kb_t('logo', 'Company Logo'));?>"></div><?php } ?>
                <input type="file" name="logo_pt" id="logo_pt" class="form-control" accept="image/png,image/jpeg,image/jpg,image/gif,image/webp">
                <div class="kb-note"><?=kb_h(info_kb_t('logo_edit_note', 'Leave blank if you do not want to change the logo. JPG, PNG, GIF, or WebP format. Maximum size 2MB.'));?></div>
              </div>
            </div>
            <div class="form-group">
              <label class="control-label col-lg-2"><?=kb_h(info_kb_t('legal_address', 'Legal Address'));?></label>
              <div class="col-lg-10"><input type="text" name="alamat" value="<?=kb_h(kb_v($data_edit,'alamat'));?>" class="form-control"></div>
            </div>
            <div class="form-group">
              <label class="control-label col-lg-2"><?=kb_h(info_kb_t('shipping_address', 'Shipping Address'));?></label>
              <div class="col-lg-10"><input type="text" name="alamat_kirim" value="<?=kb_h(kb_v($data_edit,'alamat_kirim'));?>" class="form-control"></div>
            </div>
            <div class="form-group">
              <label class="control-label col-lg-2"><?=kb_h(info_kb_t('province', 'Province'));?></label>
              <div class="col-lg-3"><input type="text" name="prop" value="<?=kb_h(kb_v($data_edit,'prop'));?>" class="form-control"></div>
              <label class="control-label col-lg-1"><?=kb_h(info_kb_t('city', 'City'));?></label>
              <div class="col-lg-3"><input type="text" name="kota" value="<?=kb_h(kb_v($data_edit,'kota'));?>" class="form-control"></div>
              <label class="control-label col-lg-1"><?=kb_h(info_kb_t('postal_code', 'Postal Code'));?></label>
              <div class="col-lg-2"><input type="text" name="postal_code" value="<?=kb_h(kb_v($data_edit,'postal_code'));?>" class="form-control"></div>
            </div>
            <div class="form-group">
              <label class="control-label col-lg-2"><?=kb_h(info_kb_t('country', 'Country'));?></label>
              <div class="col-lg-2"><input type="text" name="country" value="<?=kb_h(kb_v($data_edit,'country','ID'));?>" maxlength="3" class="form-control text-uppercase"></div>
              <label class="control-label col-lg-1"><?=kb_h(info_kb_t('phone', 'Phone'));?></label>
              <div class="col-lg-3"><input type="text" name="telp" value="<?=kb_h(kb_v($data_edit,'telp'));?>" class="form-control"></div>
              <label class="control-label col-lg-1"><?=kb_h(info_kb_t('fax', 'Fax'));?></label>
              <div class="col-lg-3"><input type="text" name="fax" value="<?=kb_h(kb_v($data_edit,'fax'));?>" class="form-control"></div>
            </div>
            <div class="form-group">
              <label class="control-label col-lg-2"><?=kb_h(info_kb_t('email', 'Email'));?></label>
              <div class="col-lg-4"><input type="email" name="email" value="<?=kb_h(kb_v($data_edit,'email'));?>" class="form-control"></div>
              <label class="control-label col-lg-2"><?=kb_h(info_kb_t('website', 'Website'));?></label>
              <div class="col-lg-4"><input type="text" name="website" value="<?=kb_h(kb_v($data_edit,'website'));?>" class="form-control" placeholder="https://..."></div>
            </div>
          </div>
          <div class="tab-pane" id="tab_legal">
            <div class="kb-section-title"><?=kb_h(info_kb_t('legal_tax_registration', 'Legal & Tax Registration'));?></div>
            <div class="form-group">
              <label class="control-label col-lg-2"><?=kb_h(info_kb_t('npwp', 'Tax ID'));?></label>
              <div class="col-lg-4"><input type="text" name="npwp" value="<?=kb_h(kb_v($data_edit,'npwp'));?>" class="form-control"></div>
              <label class="control-label col-lg-2"><?=kb_h(info_kb_t('tax_registration_no', 'Tax Registration No'));?></label>
              <div class="col-lg-4"><input type="text" name="tax_registration_no" value="<?=kb_h(kb_v($data_edit,'tax_registration_no'));?>" class="form-control"></div>
            </div>
            <div class="form-group">
              <label class="control-label col-lg-2"><?=kb_h(info_kb_t('nib', 'Business ID'));?></label>
              <div class="col-lg-4"><input type="text" name="nomor_nib" value="<?=kb_h(kb_v($data_edit,'nomor_nib'));?>" class="form-control"></div>
              <label class="control-label col-lg-2"><?=kb_h(info_kb_t('api', 'API-P / API-U'));?></label>
              <div class="col-lg-4"><input type="text" name="nomor_api" value="<?=kb_h(kb_v($data_edit,'nomor_api'));?>" class="form-control"></div>
            </div>
            <div class="form-group">
              <label class="control-label col-lg-2"><?=kb_h(info_kb_t('skep_kb', 'SKEP KB'));?></label>
              <div class="col-lg-4"><input type="text" name="skepkb" value="<?=kb_h(kb_v($data_edit,'skepkb'));?>" class="form-control"></div>
              <label class="control-label col-lg-2"><?=kb_h(info_kb_t('skep_date', 'SKEP Date'));?></label>
              <div class="col-lg-4"><div class="input-group date" id="tgl1"><input type="text" class="form-control" value="<?=kb_h(kb_v($data_edit,'tglskep'));?>" name="tglskep"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
            </div>
            <div class="form-group">
              <label class="control-label col-lg-2">PBOB</label>
              <div class="col-lg-4"><input type="text" name="pbob" value="<?=kb_h(kb_v($data_edit,'pbob'));?>" class="form-control"></div>
              <label class="control-label col-lg-2">CDOB</label>
              <div class="col-lg-4"><input type="text" name="cdob" value="<?=kb_h(kb_v($data_edit,'cdob'));?>" class="form-control"></div>
            </div>
          </div>
          <div class="tab-pane" id="tab_customs">
            <div class="kb-section-title"><?=kb_h(info_kb_t('customs_ceisa_profile', 'Customs / CEISA 4.0 Profile'));?></div>
            <div class="form-group">
              <label class="control-label col-lg-2"><?=kb_h(info_kb_t('ceisa_code', 'CEISA Code'));?></label>
              <div class="col-lg-4"><input type="text" name="kode_ceisa" value="<?=kb_h(kb_v($data_edit,'kode_ceisa'));?>" maxlength="8" class="form-control text-uppercase"></div>
              <label class="control-label col-lg-2"><?=kb_h(info_kb_t('facility_type', 'Facility Type'));?></label>
              <div class="col-lg-4">
                <select name="jenis_fasilitas" class="form-control kb-select2">
                  <?php $jf = kb_v($data_edit,'jenis_fasilitas','KAWASAN_BERIKAT'); foreach(array('KAWASAN_BERIKAT','GB','PLB','KITE','LAINNYA') as $opt){ ?>
                    <option value="<?=$opt;?>" <?=$jf===$opt?'selected':'';?>><?=$opt;?></option>
                  <?php } ?>
                </select>
              </div>
            </div>
            <div class="form-group">
              <label class="control-label col-lg-2"><?=kb_h(info_kb_t('customs_office', 'Supervising Customs Office'));?></label>
              <div class="col-lg-10"><input type="text" name="kantor_pengawas" value="<?=kb_h(kb_v($data_edit,'kantor_pengawas'));?>" class="form-control" placeholder="061000 - KPPBC ..."></div>
            </div>
          </div>
          <div class="tab-pane" id="tab_sap">
            <div class="kb-section-title"><?=kb_h(info_kb_t('sap_org_assignment', 'SAP Organization Assignment'));?></div>
            <div class="form-group">
              <label class="control-label col-lg-2"><?=kb_h(info_kb_t('company_code', 'Company Code'));?></label>
              <div class="col-lg-4"><input type="text" name="company_code" value="<?=kb_h(kb_v($data_edit,'company_code'));?>" class="form-control text-uppercase" placeholder="GBI1"></div>
              <label class="control-label col-lg-2"><?=kb_h(info_kb_t('business_area', 'Business Area'));?></label>
              <div class="col-lg-4"><input type="text" name="business_area" value="<?=kb_h(kb_v($data_edit,'business_area'));?>" class="form-control"></div>
            </div>
            <div class="form-group">
              <label class="control-label col-lg-2"><?=kb_h(info_kb_t('default_plant', 'Default Plant'));?></label>
              <div class="col-lg-4">
                <select name="default_plant_id" class="form-control kb-select2">
                  <option value=""><?=kb_h(info_kb_t('select_plant', 'Select Plant'));?></option>
                  <?php foreach($plants as $p){ ?><option value="<?=intval($p->id);?>" <?=((string)kb_v($data_edit,'default_plant_id')===(string)$p->id)?'selected':'';?>><?=kb_h($p->plant_code.' - '.$p->plant_name);?></option><?php } ?>
                </select>
              </div>
              <label class="control-label col-lg-2"><?=kb_h(info_kb_t('purchasing_org', 'Purchasing Org'));?></label>
              <div class="col-lg-4">
                <select name="purchasing_org_id" class="form-control kb-select2">
                  <option value=""><?=kb_h(info_kb_t('select_purchasing_org', 'Select Purchasing Org'));?></option>
                  <?php foreach($purchasingOrgs as $o){ ?><option value="<?=intval($o->id);?>" <?=((string)kb_v($data_edit,'purchasing_org_id')===(string)$o->id)?'selected':'';?>><?=kb_h($o->org_code.' - '.$o->org_name);?></option><?php } ?>
                </select>
              </div>
            </div>
            <div class="form-group">
              <label class="control-label col-lg-2"><?=kb_h(info_kb_t('sales_org', 'Sales Org'));?></label>
              <div class="col-lg-4">
                <select name="sales_org_id" class="form-control kb-select2">
                  <option value=""><?=kb_h(info_kb_t('select_sales_org', 'Select Sales Org'));?></option>
                  <?php foreach($salesOrgs as $o){ ?><option value="<?=intval($o->id);?>" <?=((string)kb_v($data_edit,'sales_org_id')===(string)$o->id)?'selected':'';?>><?=kb_h($o->org_code.' - '.$o->org_name);?></option><?php } ?>
                </select>
              </div>
              <label class="control-label col-lg-2"><?=kb_h(info_kb_t('fiscal_year_variant', 'Fiscal Year Variant'));?></label>
              <div class="col-lg-2"><input type="text" name="fiscal_year_variant" value="<?=kb_h(kb_v($data_edit,'fiscal_year_variant','K4'));?>" class="form-control text-uppercase"></div>
              <label class="control-label col-lg-1"><?=kb_h(info_kb_t('currency', 'Currency'));?></label>
              <div class="col-lg-1"><input type="text" name="local_currency" value="<?=kb_h(kb_v($data_edit,'local_currency','IDR'));?>" maxlength="3" class="form-control text-uppercase"></div>
            </div>
          </div>
          <div class="tab-pane" id="tab_bank">
            <div class="kb-section-title"><?=kb_h(info_kb_t('bank_account', 'Bank Account'));?></div>
            <div class="form-group">
              <label class="control-label col-lg-2"><?=kb_h(info_kb_t('bank_name', 'Bank Name'));?></label>
              <div class="col-lg-4"><input type="text" name="bank_name" value="<?=kb_h(kb_v($data_edit,'bank_name'));?>" class="form-control"></div>
              <label class="control-label col-lg-2"><?=kb_h(info_kb_t('account_holder', 'Account Holder'));?></label>
              <div class="col-lg-4"><input type="text" name="bank_account_name" value="<?=kb_h(kb_v($data_edit,'bank_account_name'));?>" class="form-control"></div>
            </div>
            <div class="form-group">
              <label class="control-label col-lg-2"><?=kb_h(info_kb_t('account_1', 'Account 1'));?></label>
              <div class="col-lg-4"><input type="text" name="rek1" value="<?=kb_h(kb_v($data_edit,'rek1'));?>" class="form-control"></div>
              <label class="control-label col-lg-2"><?=kb_h(info_kb_t('account_2', 'Account 2'));?></label>
              <div class="col-lg-4"><input type="text" name="rek2" value="<?=kb_h(kb_v($data_edit,'rek2'));?>" class="form-control"></div>
            </div>
            <div class="form-group">
              <label class="control-label col-lg-2"><?=kb_h(info_kb_t('swift_code', 'SWIFT Code'));?></label>
              <div class="col-lg-4"><input type="text" name="swift_code" value="<?=kb_h(kb_v($data_edit,'swift_code'));?>" class="form-control text-uppercase"></div>
              <label class="control-label col-lg-2"><?=kb_h(info_kb_t('bank_currency', 'Bank Currency'));?></label>
              <div class="col-lg-4"><input type="text" name="bank_currency" value="<?=kb_h(kb_v($data_edit,'bank_currency','IDR'));?>" maxlength="3" class="form-control text-uppercase"></div>
            </div>
            <div class="form-group">
              <label class="control-label col-lg-2"><?=kb_h(info_kb_t('bank_detail', 'Bank Detail'));?></label>
              <div class="col-lg-10"><textarea name="bank" id="bank" class="form-control summernote"><?=kb_h(kb_v($data_edit,'bank'));?></textarea></div>
            </div>
          </div>
        </div>
        <input type="hidden" name="id" value="<?=kb_h(kb_v($data_edit,'id'));?>">
        <div class="form-group kb-actions">
          <label class="control-label col-lg-2">&nbsp;</label>
          <div class="col-lg-10">
            <a href="<?=base_index();?>info-kb" class="btn btn-default"><i class="fa fa-step-backward"></i> <?=kb_h(lang_text('back_button', 'Back'));?></a>
            <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> <?=kb_h(info_kb_t('save_profile', 'Save KB Profile'));?></button>
          </div>
        </div>
      </form>
    </div>
  </div>
</section>
<script type="text/javascript">
$(document).ready(function() {
  $('.summernote').summernote({height:200,toolbar:[['style',['style']],['font',['bold','italic','underline','clear']],['fontname',['fontname']],['fontsize',['fontsize']],['color',['color']],['para',['ul','ol','paragraph']],['table',['table']],['insert',['link']],['view',['fullscreen','codeview']]]});
  if ($.fn.select2) {
    $('.kb-select2').select2({width:'100%',allowClear:true});
  }
  $("#tgl1").datepicker({format:"yyyy-mm-dd",autoclose:true,todayHighlight:true}).on("change",function(){$("#tgl1 :input").valid();});
  $('.text-uppercase').on('keyup change',function(){this.value=this.value.toUpperCase();});
  $("#edit_info_kb").validate({
    errorClass:"help-block",
    errorElement:"span",
    highlight:function(element){$(element).parents(".form-group").removeClass("has-success").addClass("has-error");},
    unhighlight:function(element){$(element).parents(".form-group").removeClass("has-error").addClass("has-success");},
    errorPlacement:function(error,element){
      if(element.hasClass("select2-hidden-accessible")){error.insertAfter(element.next('.select2'));}
      else if(element.attr("type")=="checkbox"||element.attr("type")=="radio"){element.parent().parent().append(error);}
      else{error.insertAfter(element);}
    },
    submitHandler:function(form){
      $("#loadnya").show();
      $(form).ajaxSubmit({
        url:$(form).attr("action"),
        dataType:"json",
        type:"post",
        error:function(data){$("#loadnya").hide();console.log(data);},
        success:function(responseText){
          $("#loadnya").hide();
          $.each(responseText,function(index){
            if(responseText[index].status=="die"){$("#informasi").modal("show");}
            else if(responseText[index].status=="error"){$(".isi_warning").text(responseText[index].error_message);$(".error_data").focus().fadeIn();}
            else if(responseText[index].status=="good"){$(".error_data").hide();$(".notif_top_up").fadeIn(1000);$(".notif_top_up").fadeOut(1000,function(){window.history.back();});}
            else{$(".isi_warning").text(responseText[index].error_message);$(".error_data").focus().fadeIn();}
          });
        }
      });
    }
  });
});
</script>
