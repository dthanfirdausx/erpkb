<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title><?= appTittle ?></title>
<link rel="icon" href="<?=base_admin();?>assets/login/img/favicon.png?v=20260620" type="image/png">
<link rel="shortcut icon" href="<?=base_admin();?>assets/login/img/favicon.png?v=20260620" type="image/png">
<link rel="apple-touch-icon" href="<?=base_admin();?>assets/login/img/favicon.png?v=20260620">

<!-- ================= CSS ================= -->

<!-- ✅ BOOTSTRAP (HANYA 1) -->
<link rel="stylesheet" href="<?=base_admin();?>assets/bootstrap/css/bootstrap.min.css">

<!-- ✅ ADMIN LTE -->
<link rel="stylesheet" href="<?=base_admin();?>assets/dist/css/AdminLTE.min.css">

<!-- ✅ FONT AWESOME (PILIH 1 - FA6) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

<!-- ✅ DATATABLE (WAJIB) -->
<link rel="stylesheet" href="<?=base_admin();?>assets/plugins/datatables/dataTables.bootstrap.css">
<link rel="stylesheet" href="<?=base_admin();?>assets/plugins/datatables/extensions/Buttons/css/buttons.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">

<!-- ✅ SELECT -->
<link rel="stylesheet" href="<?=base_admin();?>assets/plugins/select2/select2.min.css">
<link rel="stylesheet" href="<?=base_admin();?>assets/plugins/chosen/chosen.min.css">

<!-- ✅ DATEPICKER -->
<link rel="stylesheet" href="<?=base_admin();?>assets/plugins/datepicker/datepicker3.css">

<!-- ✅ CUSTOM -->
<link rel="stylesheet" href="<?=base_admin();?>assets/css/modern-erp.css">
<link rel="stylesheet" href="<?=base_admin();?>assets/plugins/jquery-ui/jquery-ui.min.css">

<!-- ================= JS ================= -->

<!-- ✅ JQUERY -->
<script src="<?=base_admin();?>assets/plugins/jQuery/jQuery-2.1.3.min.js"></script>

<!-- ✅ BOOTSTRAP JS -->
<script src="<?=base_admin();?>assets/bootstrap/js/bootstrap.min.js"></script>

<!-- ✅ DATATABLE -->
<script src="<?=base_admin();?>assets/plugins/datatables/jquery.dataTables.js"></script>
<script src="<?=base_admin();?>assets/plugins/datatables/dataTables.bootstrap.js"></script>
<script src="<?=base_admin();?>assets/plugins/datatables/extensions/Buttons/js/dataTables.buttons.min.js"></script>
<script src="<?=base_admin();?>assets/plugins/datatables/extensions/Buttons/js/buttons.html5.min.js"></script>
<script src="<?=base_admin();?>assets/plugins/datatables/extensions/Responsive/js/dataTables.responsive.min.js"></script>

<!-- ✅ DATEPICKER -->
<script src="<?=base_admin();?>assets/plugins/datepicker/bootstrap-datepicker.js"></script>


<!-- ================= FIX UI ================= -->

<style>
/* FIX DROPDOWN & DATEPICKER */
.dropdown-menu {
    z-index: 9999 !important;
}
.datepicker {
    z-index: 9999 !important;
}

/* FIX DATATABLE */
table.dataTable {
    width: 100% !important;
}

.dataTables_wrapper .dataTables_filter {
    text-align: right;
}

.dataTables_wrapper .dataTables_length {
    float: left;
}
.dataTables_wrapper {
    overflow: visible !important;
}

.table-responsive {
    overflow: visible !important;
}
.dataTables_wrapper,
.table-responsive { 
    overflow: visible !important;
}

.dropdown-menu {
    z-index: 99999 !important;
}

</style>

</head>
<body class="skin-blue">

    <div class="fakeloader"></div>
    <div id="loadnya" style="display:none">
      <img src="<?=base_admin();?>assets/dist/img/loadnya.gif" class="ajax-loader"/>
    </div>
    <!--notif here -->
    <div class="notif_top" style="display:none">
      <div class="alert alert-success" style="margin-left:0">
        <button class="close" data-dismiss="alert">×</button>
        <center>
        <strong><?=erp_h('alert_data_added', 'Data has been added successfully');?></strong>
        </center>
      </div>
    </div>
    <div class="notif_top_up" style="display:none">
      <div class="alert alert-success" style="margin-left:0">
        <button class="close" data-dismiss="alert">×</button>
        <center>
        <strong><?=erp_h('alert_data_updated', 'Data has been updated successfully');?></strong>
        </center>
      </div>
    </div>
    <script>
      window.ERPKB_LANG = <?=erp_lang_js_bundle(array(
        'common_add' => 'Add',
        'common_add_new' => 'Add New',
        'common_all' => 'All',
        'common_back' => 'Back',
        'common_cancel' => 'Cancel',
        'common_close' => 'Close',
        'common_confirm' => 'Confirm',
        'common_change' => 'Change',
        'common_copy' => 'Copy',
        'common_delete' => 'Delete',
        'common_deleted' => 'Deleted',
        'common_deleted_message' => 'Data deleted successfully.',
        'common_detail' => 'Detail',
        'common_edit' => 'Edit',
        'common_error' => 'Error',
        'common_export_csv' => 'CSV',
        'common_export_data' => 'Export Data',
        'common_export_excel' => 'Export Excel',
        'common_export_pdf' => 'PDF',
        'common_filter' => 'Filter',
        'common_import' => 'Import',
        'common_load_failed' => 'Data failed to load.',
        'common_loading' => 'Loading...',
        'common_no' => 'No',
        'common_no_text' => 'No',
        'common_ok' => 'OK',
        'common_process_failed' => 'Process failed.',
        'common_print' => 'Print',
        'common_profile' => 'Profile',
        'common_remove' => 'Remove',
        'common_reset' => 'Reset',
        'common_save' => 'Save',
        'common_saved' => 'Saved',
        'common_saving' => 'Saving...',
        'common_search' => 'Search',
        'common_sign_out' => 'Sign out',
        'common_success' => 'Success',
        'common_yes' => 'Yes',
        'common_empty_state' => 'No data to display.',
        'module_master_data' => 'Master Data',
        'master_workbench' => 'Workbench',
        'master_total_data' => 'Total Data',
        'master_active_open' => 'Active/Open',
        'master_field_count' => 'Master Fields',
        'master_primary_key' => 'Primary Key',
        'master_list_title' => 'List',
        'master_search_placeholder' => 'Search code, name, status, or master data',
        'master_excel_required' => 'Excel file is required.',
        'master_upload_import' => 'Upload & Import',
        'master_import_success' => 'Import successful.',
        'master_import_failed' => 'Import failed.',
        'datatable_decimal' => '',
        'datatable_empty_table' => 'No data available in table',
        'datatable_info' => 'Showing _START_ to _END_ of _TOTAL_ entries',
        'datatable_info_empty' => 'Showing 0 to 0 of 0 entries',
        'datatable_info_filtered' => '(filtered from _MAX_ total entries)',
        'datatable_length_menu' => 'Show _MENU_ entries',
        'datatable_loading_records' => 'Loading...',
        'datatable_processing' => 'Processing...',
        'datatable_search' => 'Search:',
        'datatable_zero_records' => 'No matching records found',
        'datatable_paginate_first' => 'First',
        'datatable_paginate_last' => 'Last',
        'datatable_paginate_next' => 'Next',
        'datatable_paginate_previous' => 'Previous',
        'alert_data_added' => 'Data has been added successfully',
        'alert_data_updated' => 'Data has been updated successfully',
        'alert_process_failed' => 'Process failed.',
        'ajax_error' => 'A connection or server error occurred.',
        'confirm_delete_title' => 'Delete Confirmation',
        'modal_delete_confirm' => 'Are you sure you want to delete this data?',
        'select2_placeholder' => 'Select data',
        'select2_search_material' => 'Search material...',
        'select2_searching' => 'Searching...',
        'select2_no_results' => 'No results found',
        'validation_required' => 'This field is required.',
        'validation_remote' => 'Please fix this field.',
        'validation_email' => 'Please enter a valid email address.',
        'validation_url' => 'Please enter a valid URL.',
        'validation_date' => 'Please enter a valid date.',
        'validation_number' => 'Please enter a valid number.',
        'validation_digits' => 'Please enter only digits.',
        'validation_creditcard' => 'Please enter a valid credit card number.',
        'validation_equal_to' => 'Please enter the same value again.',
        'validation_maxlength' => 'Please enter no more than {0} characters.',
        'validation_minlength' => 'Please enter at least {0} characters.',
        'validation_rangelength' => 'Please enter a value between {0} and {1} characters long.',
        'validation_range' => 'Please enter a value between {0} and {1}.',
        'validation_max' => 'Please enter a value less than or equal to {0}.',
        'validation_min' => 'Please enter a value greater than or equal to {0}.',
        'upload_select_image' => 'Select image',
        'legacy_module_title' => 'ERP Module',
        'legacy_action_subtitle' => 'Primary module actions are centralized in this banner for a consistent ERP interface.'
      ));?>;
      if (window.jQuery && $.fn && $.fn.dataTable) {
        $.extend(true, $.fn.dataTable.defaults, {
          language: {
            decimal: ERPKB_LANG.datatable_decimal || '',
            emptyTable: ERPKB_LANG.datatable_empty_table,
            info: ERPKB_LANG.datatable_info,
            infoEmpty: ERPKB_LANG.datatable_info_empty,
            infoFiltered: ERPKB_LANG.datatable_info_filtered,
            lengthMenu: ERPKB_LANG.datatable_length_menu,
            loadingRecords: ERPKB_LANG.datatable_loading_records,
            processing: ERPKB_LANG.datatable_processing,
            search: ERPKB_LANG.datatable_search,
            zeroRecords: ERPKB_LANG.datatable_zero_records,
            paginate: {
              first: ERPKB_LANG.datatable_paginate_first,
              last: ERPKB_LANG.datatable_paginate_last,
              next: ERPKB_LANG.datatable_paginate_next,
              previous: ERPKB_LANG.datatable_paginate_previous
            },
            buttons: {
              copy: ERPKB_LANG.common_copy,
              excel: ERPKB_LANG.common_export_excel,
              csv: ERPKB_LANG.common_export_csv,
              pdf: ERPKB_LANG.common_export_pdf,
              print: ERPKB_LANG.common_print,
              collection: ERPKB_LANG.common_export_data
            }
          }
        });
      }
    </script>
    <div class="wrapper">
      <?php
      include "top_bar.php";
      include "left_nav.php";
      ?>
