</div> <!--content wrapper -->
<footer class="main-footer">
  <div class="pull-right hidden-xs">
    <b><?=erp_h('footer_version', 'Version');?></b> 2.0
  </div>
  <strong><?=erp_h('footer_copyright', 'Copyright');?> &copy; <?= date("Y") ?><a href="https://dthan.net"> dthan.net</a>.</strong> <?=erp_h('footer_all_rights_reserved', 'All rights reserved.');?>
</footer>
</div><!-- ./wrapper -->

<script src="<?=base_admin();?>assets/plugins/jquery-ui/jquery-ui.min.js" type="text/javascript"></script>
<script>
if ($.ui && $.ui.button) {
  $.widget.bridge('uibutton', $.ui.button);
}
function erpkbInitReusableI18n() {
  if (!window.ERPKB_LANG) return;
  var lang = window.ERPKB_LANG;
  if ($.fn && $.fn.dataTable) {
    $.extend(true, $.fn.dataTable.defaults, {
      language: {
        decimal: lang.datatable_decimal || '',
        emptyTable: lang.datatable_empty_table,
        info: lang.datatable_info,
        infoEmpty: lang.datatable_info_empty,
        infoFiltered: lang.datatable_info_filtered,
        lengthMenu: lang.datatable_length_menu,
        loadingRecords: lang.datatable_loading_records,
        processing: lang.datatable_processing,
        search: lang.datatable_search,
        zeroRecords: lang.datatable_zero_records,
        paginate: {
          first: lang.datatable_paginate_first,
          last: lang.datatable_paginate_last,
          next: lang.datatable_paginate_next,
          previous: lang.datatable_paginate_previous
        },
        buttons: {
          copy: lang.common_copy || 'Copy',
          excel: lang.common_export_excel,
          csv: lang.common_export_csv || 'CSV',
          pdf: lang.common_export_pdf || 'PDF',
          print: lang.common_print || 'Print',
          collection: lang.common_export_data
        }
      }
    });
  }
  if ($.fn && $.fn.select2) {
    $.fn.select2.defaults.set('placeholder', lang.select2_placeholder || lang.common_search || 'Select data');
    $.fn.select2.defaults.set('language', {
      noResults: function() { return lang.select2_no_results || 'No results found'; },
      searching: function() { return lang.select2_searching || lang.common_loading || 'Searching...'; },
      inputTooShort: function() { return lang.common_search || 'Search'; }
    });
  }
  if ($.validator) {
    $.extend($.validator.messages, {
      required: lang.validation_required || 'This field is required.',
      remote: lang.validation_remote || 'Please fix this field.',
      email: lang.validation_email || 'Please enter a valid email address.',
      url: lang.validation_url || 'Please enter a valid URL.',
      date: lang.validation_date || 'Please enter a valid date.',
      dateISO: lang.validation_date || 'Please enter a valid date.',
      number: lang.validation_number || 'Please enter a valid number.',
      digits: lang.validation_digits || 'Please enter only digits.',
      creditcard: lang.validation_creditcard || 'Please enter a valid credit card number.',
      equalTo: lang.validation_equal_to || 'Please enter the same value again.',
      maxlength: $.validator.format(lang.validation_maxlength || 'Please enter no more than {0} characters.'),
      minlength: $.validator.format(lang.validation_minlength || 'Please enter at least {0} characters.'),
      rangelength: $.validator.format(lang.validation_rangelength || 'Please enter a value between {0} and {1} characters long.'),
      range: $.validator.format(lang.validation_range || 'Please enter a value between {0} and {1}.'),
      max: $.validator.format(lang.validation_max || 'Please enter a value less than or equal to {0}.'),
      min: $.validator.format(lang.validation_min || 'Please enter a value greater than or equal to {0}.')
    });
  }
}
function erpkbApplyI18n(scope) {
  var lang = window.ERPKB_LANG || {};
  var $scope = scope ? $(scope) : $(document);
  $scope.find('[data-i18n]').each(function() {
    var key = $(this).data('i18n');
    if (key && lang[key]) $(this).text(lang[key]);
  });
  $scope.find('[data-i18n-html]').each(function() {
    var key = $(this).data('i18n-html');
    if (key && lang[key]) $(this).html(lang[key]);
  });
  $scope.find('[data-i18n-title]').each(function() {
    var key = $(this).data('i18n-title');
    if (key && lang[key]) $(this).attr('title', lang[key]);
  });
  $scope.find('[data-i18n-placeholder]').each(function() {
    var key = $(this).data('i18n-placeholder');
    if (key && lang[key]) $(this).attr('placeholder', lang[key]);
  });
}
function erpLang(key, fallback, replacements) {
  var lang = window.ERPKB_LANG || {};
  var text = (key && Object.prototype.hasOwnProperty.call(lang, key)) ? lang[key] : (fallback || key || '');
  if (replacements) {
    $.each(replacements, function(name, value) {
      text = String(text).replace(new RegExp('\\{' + name + '\\}', 'g'), value);
    });
  }
  return text;
}
function erpSetText(selector, key, fallback, replacements) {
  $(selector).text(erpLang(key, fallback, replacements));
}
function erpSetHtml(selector, key, fallback, replacements) {
  $(selector).html(erpLang(key, fallback, replacements));
}
function erpAlert(key, fallback) {
  alert(erpLang(key, fallback || ''));
}
function erpConfirm(key, fallback) {
  return confirm(erpLang(key, fallback || ''));
}
function erpSwal(type, titleKey, messageKey, titleFallback, messageFallback) {
  var title = erpLang(titleKey, titleFallback || '');
  var message = messageKey ? erpLang(messageKey, messageFallback || '') : '';
  if (window.Swal && Swal.fire) {
    return Swal.fire(title, message, type || 'info');
  }
  if (window.swal) {
    return swal(title, message, type || 'info');
  }
  alert((title ? title + '\n' : '') + message);
}
function erpToastr(type, messageKey, fallback, titleKey, titleFallback) {
  var message = erpLang(messageKey, fallback || '');
  var title = titleKey ? erpLang(titleKey, titleFallback || '') : undefined;
  if (window.toastr && toastr[type]) {
    return toastr[type](message, title);
  }
  if (type === 'error') {
    return erpSwal('error', titleKey || 'common_error', messageKey, titleFallback || 'Error', fallback || '');
  }
  return erpSwal(type || 'info', titleKey || 'common_success', messageKey, titleFallback || '', fallback || '');
}
function erpAjaxError(xhr, fallbackKey) {
  var message = '';
  if (xhr && xhr.responseJSON && (xhr.responseJSON.error_message || xhr.responseJSON.message)) {
    message = xhr.responseJSON.error_message || xhr.responseJSON.message;
  } else if (xhr && xhr.responseText && xhr.responseText.length < 500) {
    message = xhr.responseText;
  }
  return message || erpLang(fallbackKey || 'ajax_error', 'A connection or server error occurred.');
}
function erpConfirmDelete(callback, messageKey) {
  var message = erpLang(messageKey || 'modal_delete_confirm', 'Are you sure you want to delete this data?');
  if (window.Swal && Swal.fire) {
    Swal.fire({
      title: erpLang('confirm_delete_title', 'Delete Confirmation'),
      text: message,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: erpLang('common_delete', 'Delete'),
      cancelButtonText: erpLang('common_cancel', 'Cancel')
    }).then(function(result) {
      if (result.isConfirmed && typeof callback === 'function') callback();
    });
    return false;
  }
  if (confirm(message) && typeof callback === 'function') callback();
  return false;
}
function erpTranslateLiteral(value) {
  if (typeof value !== 'string') return value;
  var text = $.trim(value);
  var map = {
    'Berhasil': erpLang('common_success', 'Success'),
    'Sukses': erpLang('common_success', 'Success'),
    'Success': erpLang('common_success', 'Success'),
    'Saved': erpLang('common_saved', 'Saved'),
    'Tersimpan': erpLang('common_saved', 'Saved'),
    'Deleted': erpLang('common_deleted', 'Deleted'),
    'Terhapus': erpLang('common_deleted', 'Deleted'),
    'Gagal': erpLang('common_error', 'Error'),
    'Error': erpLang('common_error', 'Error'),
    'Loading...': erpLang('common_loading', 'Loading...'),
    'Saving...': erpLang('common_saving', 'Saving...'),
    'Menyimpan...': erpLang('common_saving', 'Saving...'),
    'Export Data': erpLang('common_export_data', 'Export Data'),
    'Export Excel': erpLang('common_export_excel', 'Export Excel'),
    'Print': erpLang('common_print', 'Print'),
    'All': erpLang('common_all', 'All'),
    'Semua': erpLang('common_all', 'All'),
    'Select data': erpLang('select2_placeholder', 'Select data'),
    'Pilih data': erpLang('select2_placeholder', 'Select data'),
    'Cari material...': erpLang('select2_search_material', 'Search material...'),
    'Search material...': erpLang('select2_search_material', 'Search material...'),
    'No results found': erpLang('select2_no_results', 'No results found'),
    'Data tidak ditemukan': erpLang('select2_no_results', 'No results found')
  };
  if (map[text]) {
    return value.replace(text, map[text]);
  }
  if (/^Delete .*\?$/.test(text)) return value.replace(text, erpLang('confirm_delete_title', 'Delete Confirmation'));
  if (/^Data .* gagal dimuat\.?$/.test(text)) return erpLang('common_load_failed', 'Data failed to load.');
  if (/^.* berhasil dihapus\.?$/.test(text)) return erpLang('common_deleted_message', 'Data deleted successfully.');
  if (/^.* gagal dibuka\.?$/.test(text)) return erpLang('common_load_failed', 'Data failed to load.');
  return value;
}
function erpTranslateOptionsDeep(options, depth, seen) {
  depth = depth || 0;
  if (!options || typeof options !== 'object' || depth > 8) return options;
  if (options.nodeType || options === window || options === document || options.jquery) return options;
  if (window.WeakSet) {
    seen = seen || new WeakSet();
    if (seen.has(options)) return options;
    seen.add(options);
  }
  if ($.isArray(options)) {
    $.each(options, function(i, item) {
      if (typeof item === 'string') {
        options[i] = erpTranslateLiteral(item);
      } else if ($.isArray(item) || $.isPlainObject(item)) {
        options[i] = erpTranslateOptionsDeep(item, depth + 1, seen);
      }
    });
    return options;
  }
  if (!$.isPlainObject(options)) return options;
  $.each(options, function(key, val) {
    if (typeof val === 'string' && /^(text|title|confirmButtonText|cancelButtonText|placeholder|inputLabel)$/i.test(key)) {
      options[key] = erpTranslateLiteral(val);
    } else if ($.isArray(val) || $.isPlainObject(val)) {
      options[key] = erpTranslateOptionsDeep(val, depth + 1, seen);
    }
  });
  return options;
}
function erpkbInstallJsI18nAdapters() {
  if (window.__erpkbJsI18nAdaptersInstalled) return;
  window.__erpkbJsI18nAdaptersInstalled = true;

  var nativeAlert = window.alert;
  var nativeConfirm = window.confirm;
  window.alert = function(message) { return nativeAlert.call(window, erpTranslateLiteral(message)); };
  window.confirm = function(message) { return nativeConfirm.call(window, erpTranslateLiteral(message)); };

  if (window.Swal && Swal.fire && !Swal.__erpkbI18nWrapped) {
    var originalSwalFire = Swal.fire;
    Swal.fire = function() {
      var args = Array.prototype.slice.call(arguments);
      if (args.length === 1 && args[0] && typeof args[0] === 'object') {
        args[0] = erpTranslateOptionsDeep(args[0]);
      } else {
        args = $.map(args, function(arg) { return typeof arg === 'string' ? erpTranslateLiteral(arg) : arg; });
      }
      return originalSwalFire.apply(this, args);
    };
    Swal.__erpkbI18nWrapped = true;
  }
  if (window.swal && !window.swal.__erpkbI18nWrapped) {
    var originalSwal = window.swal;
    window.swal = function() {
      var args = Array.prototype.slice.call(arguments);
      args = $.map(args, function(arg) {
        if (arg && typeof arg === 'object') return erpTranslateOptionsDeep(arg);
        return typeof arg === 'string' ? erpTranslateLiteral(arg) : arg;
      });
      return originalSwal.apply(this, args);
    };
    window.swal.__erpkbI18nWrapped = true;
  }
  if (window.toastr && !window.toastr.__erpkbI18nWrapped) {
    $.each(['success', 'error', 'warning', 'info'], function(_, type) {
      if (!toastr[type]) return;
      var originalToastrMethod = toastr[type];
      toastr[type] = function(message, title, optionsOverride) {
        return originalToastrMethod.call(toastr, erpTranslateLiteral(message), erpTranslateLiteral(title), optionsOverride);
      };
    });
    window.toastr.__erpkbI18nWrapped = true;
  }
  if ($.fn && $.fn.select2 && !$.fn.select2.__erpkbI18nWrapped) {
    var originalSelect2 = $.fn.select2;
    $.fn.select2 = function(options) {
      if (options && typeof options === 'object') {
        options = erpTranslateOptionsDeep(options);
        return originalSelect2.apply(this, [options]);
      }
      return originalSelect2.apply(this, arguments);
    };
    $.extend(true, $.fn.select2, originalSelect2);
    $.fn.select2.__erpkbI18nWrapped = true;
  }
  if ($.fn && $.fn.DataTable && !$.fn.DataTable.__erpkbI18nWrapped) {
    var originalDataTable = $.fn.DataTable;
    $.fn.DataTable = function(options) {
      if (options && typeof options === 'object') {
        options = erpTranslateOptionsDeep(options);
        return originalDataTable.apply(this, [options]);
      }
      return originalDataTable.apply(this, arguments);
    };
    $.extend(true, $.fn.DataTable, originalDataTable);
    $.fn.DataTable.__erpkbI18nWrapped = true;
  }
  if ($.fn && $.fn.dataTable && !$.fn.dataTable.__erpkbI18nWrapped) {
    var originalDataTableLower = $.fn.dataTable;
    $.fn.dataTable = function(options) {
      if (options && typeof options === 'object') {
        options = erpTranslateOptionsDeep(options);
        return originalDataTableLower.apply(this, [options]);
      }
      return originalDataTableLower.apply(this, arguments);
    };
    $.extend(true, $.fn.dataTable, originalDataTableLower);
    $.fn.dataTable.__erpkbI18nWrapped = true;
  }
}
window.erpLang = erpLang;
window.erpSetText = erpSetText;
window.erpSetHtml = erpSetHtml;
window.erpAlert = erpAlert;
window.erpConfirm = erpConfirm;
window.erpSwal = erpSwal;
window.erpToastr = erpToastr;
window.erpAjaxError = erpAjaxError;
window.erpConfirmDelete = erpConfirmDelete;
window.erpTranslateLiteral = erpTranslateLiteral;
window.erpTranslateOptionsDeep = erpTranslateOptionsDeep;
window.erpkbInstallJsI18nAdapters = erpkbInstallJsI18nAdapters;
erpkbInitReusableI18n();
erpkbInstallJsI18nAdapters();
$(function(){ erpkbInitReusableI18n(); erpkbInstallJsI18nAdapters(); erpkbApplyI18n(document); });
</script>
<!--form asset -->
<!-- jQuery 2.1.3 -->

<script src="<?=base_admin();?>assets/login/js/jqueryform.js"></script>
<script src="<?=base_admin();?>assets/login/js/validate.js"></script>
<script>erpkbInitReusableI18n();</script>
<script src="<?=base_admin();?>assets/plugins/chosen/chosen.jquery.min.js" type="text/javascript"></script>

<script src="<?=base_admin();?>assets/dist/js/input.js"></script>
<script src="<?=base_admin();?>assets/dist/js/update.js"></script>
<script src="<?=base_admin();?>assets/dist/js/import.js"></script>
<!--delete script-->
<script src="<?=base_admin();?>assets/dist/js/delete.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!--home asset -->
 <?php

 if (uri_segment(1)=='index.php'|| uri_segment(1)=='' || uri_segment(1)=='') {
  ?>
    <!-- AdminLTE dashboard demo (This is only for demo purposes) -->
<script src="<?=base_admin();?>assets/dist/js/pages/dashboard2.js" type="text/javascript"></script>
<?php
}
?>
<!-- Bootstrap 3.3.2 JS -->
<script src='<?=base_admin();?>assets/plugins/fastclick/fastclick.min.js'></script>
<!-- AdminLTE App -->
<script src="<?=base_admin();?>assets/dist/js/app.min.js" type="text/javascript"></script>
<script>
$(document).on('click.erpkbSidebarFallback', '.sidebar-menu li.treeview > a', function(e) {
  if (e.isDefaultPrevented()) return;
  var $menu = $(this).next('.treeview-menu');
  if (!$menu.length) return;
  e.preventDefault();
  var $parent = $(this).parent('li');
  var $siblings = $parent.siblings('.treeview.active');
  $siblings.removeClass('active').children('.treeview-menu').slideUp('normal').removeClass('menu-open');
  $parent.toggleClass('active');
  $menu.stop(true, true).slideToggle('normal').toggleClass('menu-open');
});
</script>
<!-- Sparkline -->
<script src="<?=base_admin();?>assets/plugins/sparkline/jquery.sparkline.min.js" type="text/javascript"></script>
<!-- jvectormap -->
<script src="<?=base_admin();?>assets/plugins/jvectormap/jquery-jvectormap-1.2.2.min.js" type="text/javascript"></script>
<script src="<?=base_admin();?>assets/plugins/jvectormap/jquery-jvectormap-world-mill-en.js" type="text/javascript"></script>
<!-- daterangepicker -->

<script src="<?=base_admin();?>assets/login/js/moment.min.js" type="text/javascript"></script>
<script src="<?=base_admin();?>assets/plugins/daterangepicker/daterangepicker.js" type="text/javascript"></script>
<!-- datepicker -->
<script src="<?=base_admin();?>assets/plugins/datepicker/bootstrap-datepicker.js" type="text/javascript"></script>
<!-- SlimScroll 1.3.0 -->
<script src="<?=base_admin();?>assets/plugins/slimScroll/jquery.slimscroll.min.js" type="text/javascript"></script>
<!-- ChartJS 1.0.1 -->
<script src="<?=base_admin();?>assets/plugins/chartjs/Chart.min.js" type="text/javascript"></script>

<!--list table assets -->
<!-- page script -->

<script src="<?=base_admin();?>assets/plugins/ckeditor/ckeditor.js"></script>
<script src="<?=base_admin();?>assets/plugins/ckeditor/adapters/jquery.js"></script>
<!-- ckeditor and kcfinder integration config -->
<script type="text/javascript">
if ($.fn.ckeditor) {
  $('textarea.editbox').ckeditor({
    filebrowserBrowseUrl: '<?=base_admin();?>assetsplugins/kcfinder/browse.php?type=files',
    filebrowserImageBrowseUrl: '<?=base_admin();?>assets/plugins/kcfinder/browse.php?type=images',
    filebrowserFlashBrowseUrl: '<?=base_admin();?>assets/plugins/kcfinder/browse.php?type=flash',
    filebrowserUploadUrl: '<?=base_admin();?>assets/plugins/kcfinder/upload.php?type=files',
    filebrowserImageUploadUrl: '<?=base_admin();?>assets/plugins/kcfinder/upload.php?type=images',
    filebrowserFlashUploadUrl: '<?=base_admin();?>assets/plugins/kcfinder/upload.php?type=flash'
  });
}
</script>
<!--fancy box -->
<script type="text/javascript" src="<?=base_admin();?>assets/plugins/fancybox/jquery.fancybox.js"></script>
<link rel="stylesheet" type="text/css" href="<?=base_admin();?>assets/plugins/fancybox/jquery.fancybox.css?v=2.1.5" media="screen" />
<script type="text/javascript">
	$(document).ready(function() {
    if (!$.fn.fancybox) return;
		$(".fancybox").fancybox({
		openEffect  : 'none',
		closeEffect : 'none',
		loop : false
		});
	});
</script>
<script src="<?=base_admin();?>assets/dist/js/pass_up.js"></script>
<!--image upload preview -->
<script src="<?=base_admin();?>assets/plugins/holder/holder.js" type="text/javascript"></script>
<script src="<?=base_admin();?>assets/plugins/holder/jasny-bootstrap.min.js" type="text/javascript"></script>
<!-- bootstrap time picker -->
<script src="<?=base_admin();?>assets/plugins/timepicker/bootstrap-timepicker.min.js"></script>
<!--switch button -->
<script src="<?=base_admin();?>assets/plugins/switch/bootstrap-switch.min.js" type="text/javascript"></script>
<!--switch button -->
<script type="text/javascript">
function isNumberKey(evt)
{
 var charCode = (evt.which) ? evt.which : event.keyCode
 if (charCode > 31 && (charCode < 48 || charCode > 57))
    return false;

 return true;
}
$(document).ready(function(){

$("body").tooltip({ selector: '[data-toggle=tooltip]' });
$("body").popover({ selector: '[data-toggle=popover]',trigger :'hover'});

	$.each($('.make-switch'), function () {
    if (!$.fn.bootstrapSwitch) return false;
		$(this).bootstrapSwitch({
		onText: $(this).data('onText'),
		offText: $(this).data('offText'),
		onColor: $(this).data('onColor'),
		offColor: $(this).data('offColor'),
		size: $(this).data('size'),
		labelText: $(this).data('labelText')
		});
	});
	//tags input here
	//  $('#tags').tagsInput();
	//hapus multi foto
	$(".foto_banyak").on('click','.hapus_foto',function() {
		$(this).parent().remove();
	});
	//chosen select
	if ($.fn.chosen) {
    $(".chzn-select").chosen();
    $(".chzn-select-deselect").chosen({
    allow_single_deselect: true
    });
  }
	//Timepicker
	if ($.fn.timepicker) {
    $(".timepicker").timepicker({
      showInputs: false,
      showSeconds:true,
      showMeridian:false,
      minuteStep: 1,
      secondStep:1,
      maxHours:24,
    });
  }
	});
//multi image append
function add_multi(val)
{
var lang = window.ERPKB_LANG || {};
$("#add_next").append('<div class="fileinput fileinput-new" data-provides="fileinput"> <div class="fileinput-new thumbnail" style="width: 200px; height: 150px;"> <img data-src="holder.js/100%x100%" alt="100%x100%" src="data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiIHN0YW5kYWxvbmU9InllcyI/PjxzdmcgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB3aWR0aD0iMTkwIiBoZWlnaHQ9IjE0MCIgdmlld0JveD0iMCAwIDE5MCAxNDAiIHByZXNlcnZlQXNwZWN0UmF0aW89Im5vbmUiPjxkZWZzLz48cmVjdCB3aWR0aD0iMTkwIiBoZWlnaHQ9IjE0MCIgZmlsbD0iI0VFRUVFRSIvPjxnPjx0ZXh0IHg9IjY5LjA1NDY4NzUiIHk9IjcwIiBzdHlsZT0iZmlsbDojQUFBQUFBO2ZvbnQtd2VpZ2h0OmJvbGQ7Zm9udC1mYW1pbHk6QXJpYWwsIEhlbHZldGljYSwgT3BlbiBTYW5zLCBzYW5zLXNlcmlmLCBtb25vc3BhY2U7Zm9udC1zaXplOjEwcHQ7ZG9taW5hbnQtYmFzZWxpbmU6Y2VudHJhbCI+MTkweDE0MDwvdGV4dD48L2c+PC9zdmc+" data-holder-rendered="true" style="height: 100%; width: 100%; display: block;"> </div> <div class="fileinput-preview fileinput-exists thumbnail" style="max-width: 200px; max-height: 150px;"></div> <span class="btn btn-danger hapus_foto"><i class="fa fa-trash"></i></span> <div> <span class="btn btn-default btn-file"><span class="fileinput-new">'+(lang.upload_select_image || 'Select image')+'</span> <span class="fileinput-exists">'+(lang.common_change || 'Change')+'</span> <input type="file" accept="image/*" name="foto_banyak[]"> </span> <a href="#" class="btn btn-default fileinput-exists" data-dismiss="fileinput">'+(lang.common_remove || 'Remove')+'</a> </div> </div>');
}
</script>
<!-- Bootstrap WYSIHTML5 -->
<script src="<?=base_admin();?>assets/plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.all.min.js" type="text/javascript"></script>
<!-- always show up -->
<script src="<?=base_admin();?>assets/plugins/fakeloader/fakeLoader.min.js"></script>
<!-- add new calendar event modal -->
<script>
$(document).ready(function(){
	if ($.fn.fakeLoader) {
	$(".fakeloader").fakeLoader({
		timeToHide:100, //Time in milliseconds for fakeLoader disappear
		zIndex:999, // Default zIndex
		//  spinner:"spinner1",//Options: 'spinner1', 'spinner2', 'spinner3', 'spinner4', 'spinner5', 'spinner6', 'spinner7'
		//  bgColor:"#2ecc71", //Hex, RGB or RGBA colors
		bgColor:"#00a65a",
		spinner:"spinner2"
	});
  }
if ($.fn.daterangepicker) {
$('#reservationtime').daterangepicker({timePicker: true, timePickerIncrement: 30, format: 'YYYY-MM-DD H:mm'});
}

});

function changeLanguage(lang)
{
    let current_url = window.location.href;

    let url = "<?= base_url() ?>set_lang.php?back_url=" 
                + encodeURIComponent(current_url) 
                + "&lang=" + lang;

    window.location.href = url;
}

</script>
<style>
.legacy-action-hero{
  background:linear-gradient(135deg,#1d4ed8,#0f766e);
  color:#fff;
  border-radius:14px;
  padding:19px 22px;
  margin-bottom:16px;
  box-shadow:0 10px 24px rgba(15,23,42,.16);
}
.legacy-action-hero h1{margin:0 0 6px;font-size:25px;font-weight:800}
.legacy-action-hero p{margin:0;opacity:.9}
.legacy-action-hero .legacy-action-buttons{display:flex;gap:8px;justify-content:flex-end;align-items:center;flex-wrap:wrap}
.legacy-action-hero .legacy-action-buttons .btn{border:0;border-radius:8px;font-weight:700;box-shadow:0 8px 18px rgba(15,23,42,.12)}
.legacy-action-hero .legacy-action-buttons .btn-default{background:rgba(255,255,255,.92);color:#334155}
@media(max-width:991px){.legacy-action-hero .legacy-action-buttons{justify-content:flex-start;margin-top:12px}}
</style>
<script>
$(function(){
  var $content = $('.content').first();
  if (!$content.length) return;

  var hasHero = $content.children().filter(function(){
    var cls = this.className || '';
    return $(this).hasClass('mdt-hero') ||
           $(this).hasClass('erp-master-hero') ||
           $(this).hasClass('legacy-action-hero') ||
           /(^|\s)[A-Za-z0-9_-]+-hero(\s|$)/.test(cls);
  }).length > 0;
  if (hasHero) return;

  var $tools = $content.find('.box-header .box-tools').filter(function(){
    return $(this).find('.btn,button,a').filter(function(){
      return $(this).is('.btn') || $(this).find('.fa-plus,.fa-file-excel-o,.fa-download,.fa-upload').length;
    }).length > 0;
  }).first();
  if (!$tools.length) return;

  var $buttons = $tools.children('.btn,button.btn,a.btn').detach();
  if (!$buttons.length) return;
  if ($tools.children().length === 0) $tools.remove();

  var title = $.trim($('.content-header h1').first().clone().children().remove().end().text());
  if (!title) title = (window.ERPKB_LANG && ERPKB_LANG.legacy_module_title) || 'ERP Module';
  var subtitle = (window.ERPKB_LANG && ERPKB_LANG.legacy_action_subtitle) || 'Primary module actions are centralized in this banner for a consistent ERP interface.';
  var $hero = $('<div class="legacy-action-hero"><div class="row"><div class="col-md-7"><h1></h1><p></p></div><div class="col-md-5"><div class="legacy-action-buttons"></div></div></div></div>');
  $hero.find('h1').text(title);
  $hero.find('p').text(subtitle);
  $hero.find('.legacy-action-buttons').append($buttons);
  $content.prepend($hero);
});
</script>

</body>
</html>
