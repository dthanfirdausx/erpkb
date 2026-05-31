<!-- Content Header (Page header) -->
<section class="content-header">
  <h1>Info KB</h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
    <li><a href="<?=base_index();?>info-kb">Info KB</a></li>
    <li class="active">Info KB List</li>
  </ol>
</section>
<!-- Main content -->
<section class="content">
  <div class="row">
    <div class="col-xs-12">
      <div class="box">
        <div class="box-header">
         
          </div><!-- /.box-header -->
          <div class="box-body table-responsive">
 <div class="alert alert-warning fade in error_data_delete" style="display:none">
          <button type="button" class="close hide_alert_notif">&times;</button>
          <i class="icon fa fa-warning"></i> <span class="isi_warning_delete"></span>
        </div>
            <table id="dtb_manual" class="table table-bordered table-striped">
              <thead>
                <tr>
                  <th style="width:25px" align="center">No</th>
                  
                                  <th>Kode</th>
                                  <th>Nama</th>
                                  <th>Alamat</th>
                                  <th>Prop</th>
                                  <th>Kota</th>
                                  <th>NPWP</th>
                                  <th>Telp</th>
                                  <th>Fax</th>
                                  <th>Skep KB</th>
                                  <th>Tgl Skep KB</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                
      <?php
      $dtb=$db->query("select infokb.kode,infokb.nama,infokb.alamat,infokb.prop,infokb.kota,infokb.npwp,infokb.telp,infokb.fax,infokb.skepkb,infokb.tglskep,infokb.id from infokb");
      $i=1;
      foreach ($dtb as $isi) {
        ?><tr id="line_<?=$isi->id;?>">
          <td align="center"><?=$i;?></td>
          <td><?=$isi->kode;?></td>
          <td><?=$isi->nama;?></td>
          <td><?=$isi->alamat;?></td>
          <td><?=$isi->prop;?></td>
          <td><?=$isi->kota;?></td>
          <td><?=$isi->npwp;?></td>
          <td><?=$isi->telp;?></td>
          <td><?=$isi->fax;?></td>
          <td><?=$isi->skepkb;?></td>
          <td><?=$isi->tglskep;?></td>
        <td>
            <?php
            // echo '<a href="'.base_index().'info-kb/detail/'.$isi->id.'" class="btn btn-success "><i class="fa fa-eye"></i></a> ';
            if($role_act["up_act"]=="Y") {
              echo '<a href="'.base_index().'info-kb/edit/'.$isi->id.'" data-id="'.$isi->id.'" class="btn edit_data btn-primary "><i class="fa fa-pencil"></i></a> ';
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

    </section><!-- /.content -->
