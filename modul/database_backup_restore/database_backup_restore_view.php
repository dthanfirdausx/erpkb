<?php
function dbr_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function dbr_bytes($mb) {
  $bytes = (float)$mb * 1024 * 1024;
  if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2, ',', '.').' GB';
  if ($bytes >= 1048576) return number_format($bytes / 1048576, 2, ',', '.').' MB';
  if ($bytes >= 1024) return number_format($bytes / 1024, 2, ',', '.').' KB';
  return number_format($bytes, 0, ',', '.').' B';
}
$canManage = isset($_SESSION['group_level']) && in_array($_SESSION['group_level'], array('admin','system_administrator'), true);
$dbNameRow = $db->fetch("SELECT DATABASE() db_name, VERSION() db_version");
$dbName = $dbNameRow ? $dbNameRow->db_name : '';
$summary = $db->fetch("SELECT COUNT(*) table_count, COALESCE(SUM(table_rows),0) row_count, COALESCE(SUM(data_length+index_length),0)/1024/1024 size_mb, COALESCE(SUM(data_length),0)/1024/1024 data_mb, COALESCE(SUM(index_length),0)/1024/1024 index_mb FROM information_schema.TABLES WHERE table_schema=DATABASE()");
$tables = $db->query("SELECT table_name, engine, table_rows, ROUND(data_length/1024/1024,2) data_mb, ROUND(index_length/1024/1024,2) index_mb, ROUND((data_length+index_length)/1024/1024,2) total_mb, table_collation, update_time, create_time FROM information_schema.TABLES WHERE table_schema=DATABASE() ORDER BY (data_length+index_length) DESC, table_name");
?>
<style>
.dbr-hero{background:linear-gradient(135deg,#0f172a,#1d4ed8);color:#fff;border-radius:16px;padding:22px;margin-bottom:18px;box-shadow:0 12px 28px rgba(15,23,42,.18)}
.dbr-hero h1{margin:0 0 7px;font-size:28px;font-weight:800}.dbr-hero p{margin:0;color:#dbeafe}.dbr-actions{display:flex;gap:8px;justify-content:flex-end;flex-wrap:wrap}.dbr-actions .btn{border:0;border-radius:9px;font-weight:700;box-shadow:0 8px 18px rgba(15,23,42,.14)}
.dbr-card{border:1px solid #e5edf5;border-radius:14px;background:#fff;box-shadow:0 7px 20px rgba(15,23,42,.055);margin-bottom:16px}.dbr-card .box-header{border-bottom:1px solid #edf2f7;padding:14px 16px}.dbr-card .box-title{font-weight:800;color:#0f172a}
.dbr-kpi{border:1px solid #e5edf5;border-radius:14px;background:#fff;padding:15px;min-height:108px;margin-bottom:16px;box-shadow:0 6px 16px rgba(15,23,42,.045)}.dbr-kpi i{width:38px;height:38px;border-radius:12px;display:inline-flex;align-items:center;justify-content:center;color:#fff;background:#1d4ed8;margin-bottom:9px}.dbr-kpi span{display:block;color:#64748b;font-size:12px;font-weight:800;text-transform:uppercase}.dbr-kpi strong{display:block;font-size:22px;color:#0f172a;line-height:1.25}
#dtb_database_tables th,#dtb_database_tables td{font-size:12px;vertical-align:middle}.dbr-badge{display:inline-block;padding:4px 8px;border-radius:999px;background:#eff6ff;color:#1d4ed8;font-weight:700}.dbr-warning{border-left:4px solid #f59e0b;background:#fffbeb;padding:12px;border-radius:10px;color:#92400e}.select2-container{width:100%!important}@media(max-width:991px){.dbr-actions{justify-content:flex-start;margin-top:12px}}
</style>
<section class="content-header">
  <h1>Backup Restore Database <small>Database Administration</small></h1>
  <ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li><li>Management System</li><li class="active">Backup Restore Database</li></ol>
</section>
<section class="content">
  <div class="dbr-hero">
    <div class="row">
      <div class="col-md-7">
        <h1><i class="fa fa-database"></i> Backup Restore Database</h1>
        <p>Monitoring kondisi database aktif, ukuran tabel, download backup SQL, dan restore database dari file SQL saat dibutuhkan.</p>
      </div>
      <div class="col-md-5 dbr-actions">
        <?php if ($canManage) { ?>
          <a class="btn btn-success" href="<?=base_admin();?>modul/database_backup_restore/database_backup_restore_action.php?act=backup"><i class="fa fa-download"></i> Download Backup SQL</a>
          <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#modal_restore_db"><i class="fa fa-upload"></i> Restore SQL</button>
        <?php } ?>
        <button type="button" class="btn btn-default" onclick="location.reload()"><i class="fa fa-refresh"></i> Refresh</button>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-md-3"><div class="dbr-kpi"><i class="fa fa-server"></i><span>Database</span><strong><?=dbr_h($dbName);?></strong><small>MariaDB/MySQL <?=dbr_h($dbNameRow ? $dbNameRow->db_version : '-');?></small></div></div>
    <div class="col-md-3"><div class="dbr-kpi"><i class="fa fa-table"></i><span>Total Table</span><strong><?=number_format($summary ? $summary->table_count : 0,0,',','.');?></strong><small>Tabel dalam schema aktif</small></div></div>
    <div class="col-md-3"><div class="dbr-kpi"><i class="fa fa-hdd-o"></i><span>Database Size</span><strong><?=dbr_bytes($summary ? $summary->size_mb : 0);?></strong><small>Data <?=dbr_bytes($summary ? $summary->data_mb : 0);?>, Index <?=dbr_bytes($summary ? $summary->index_mb : 0);?></small></div></div>
    <div class="col-md-3"><div class="dbr-kpi"><i class="fa fa-list-ol"></i><span>Est. Rows</span><strong><?=number_format($summary ? $summary->row_count : 0,0,',','.');?></strong><small>Estimasi engine database</small></div></div>
  </div>

  <div class="row">
    <div class="col-md-8">
      <div class="box dbr-card">
        <div class="box-header"><h3 class="box-title"><i class="fa fa-table"></i> Detail Tabel Database</h3></div>
        <div class="box-body table-responsive">
          <table id="dtb_database_tables" class="table table-bordered table-striped" style="width:100%">
            <thead>
              <tr>
                <th>No</th><th>Table</th><th>Engine</th><th>Rows</th><th>Data</th><th>Index</th><th>Total</th><th>Collation</th><th>Last Update</th>
              </tr>
            </thead>
            <tbody>
              <?php $no=1; foreach($tables as $t) { ?>
                <tr>
                  <td><?=number_format($no++,0,',','.');?></td>
                  <td><b><?=dbr_h($t->table_name);?></b></td>
                  <td><span class="dbr-badge"><?=dbr_h($t->engine ?: '-');?></span></td>
                  <td class="text-right"><?=number_format((float)$t->table_rows,0,',','.');?></td>
                  <td class="text-right"><?=dbr_bytes($t->data_mb);?></td>
                  <td class="text-right"><?=dbr_bytes($t->index_mb);?></td>
                  <td class="text-right"><b><?=dbr_bytes($t->total_mb);?></b></td>
                  <td><?=dbr_h($t->table_collation);?></td>
                  <td><?=dbr_h($t->update_time ?: $t->create_time);?></td>
                </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="box dbr-card">
        <div class="box-header"><h3 class="box-title"><i class="fa fa-shield"></i> Restore Safety</h3></div>
        <div class="box-body">
          <div class="dbr-warning">
            <b>Penting:</b> restore SQL dapat menimpa struktur dan data. Sebelum restore, sistem otomatis membuat backup saat ini terlebih dahulu di folder <code>upload/db_backup</code>.
          </div>
          <hr>
          <ul class="list-unstyled">
            <li><i class="fa fa-check text-green"></i> Akses restore hanya admin/system administrator.</li>
            <li><i class="fa fa-check text-green"></i> File restore dibatasi format <code>.sql</code>.</li>
            <li><i class="fa fa-check text-green"></i> Aktivitas backup/restore masuk log aktivitas.</li>
          </ul>
        </div>
      </div>
      <div class="box dbr-card">
        <div class="box-header"><h3 class="box-title"><i class="fa fa-info-circle"></i> Rekomendasi</h3></div>
        <div class="box-body">
          <p>Untuk server production, lakukan backup sebelum deploy, sebelum import besar, dan sebelum restore data dari environment lain.</p>
          <p class="text-muted">Gunakan restore hanya saat maintenance window agar user tidak sedang transaksi.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="modal fade" id="modal_restore_db">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="form_restore_db" method="post" enctype="multipart/form-data" action="<?=base_admin();?>modul/database_backup_restore/database_backup_restore_action.php?act=restore">
        <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title"><i class="fa fa-upload"></i> Restore Database</h4></div>
        <div class="modal-body">
          <div id="restore_alert" class="alert" style="display:none"></div>
          <div class="form-group">
            <label>File SQL</label>
            <input type="file" name="sql_file" class="form-control" accept=".sql" required>
            <small class="text-muted">Upload file backup SQL dari menu ini atau dari server yang kompatibel.</small>
          </div>
          <label><input type="checkbox" name="confirm_restore" value="Y" required> Saya paham restore dapat mengganti struktur dan data database.</label>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-warning"><i class="fa fa-upload"></i> Restore Sekarang</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
$(function(){
  var dt=null;
  if($.fn.DataTable){
    dt=$('#dtb_database_tables').DataTable({pageLength:25,order:[[6,'desc']],dom:"<'row'<'col-sm-12'B>>"+"<'row'<'col-sm-6'l><'col-sm-6'f>>tr<'row'<'col-sm-5'i><'col-sm-7'p>>",buttons:['copyHtml5','excelHtml5','print']});
  }
  $('#form_restore_db').on('submit',function(e){
    e.preventDefault();
    if(!confirm('Restore database sekarang? Pastikan tidak ada user sedang transaksi.')) return;
    var form=this, data=new FormData(form), btn=$(form).find('button[type=submit]');
    $('#restore_alert').hide();
    btn.prop('disabled',true).html('<i class="fa fa-spinner fa-spin"></i> Restoring...');
    $.ajax({url:form.action,type:'POST',data:data,processData:false,contentType:false,dataType:'json'})
      .done(function(res){
        if(res.status==='good'){
          $('#restore_alert').removeClass('alert-danger').addClass('alert-success').html(res.message||'Restore berhasil.').show();
          setTimeout(function(){location.reload();},1200);
        }else{
          $('#restore_alert').removeClass('alert-success').addClass('alert-danger').html(res.error_message||'Restore gagal.').show();
        }
      })
      .fail(function(xhr){$('#restore_alert').removeClass('alert-success').addClass('alert-danger').html(xhr.responseText||'Restore gagal.').show();})
      .always(function(){btn.prop('disabled',false).html('<i class="fa fa-upload"></i> Restore Sekarang');});
  });
});
</script>
