<!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1>LP Barang Jadi</h1>
                   <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
                        <li><a href="<?=base_index();?>lp-barang-jadi">LP Barang Jadi</a></li>
                        <li class="active">Detail LP Barang Jadi</li>
                    </ol>
                </section>

                <!-- Main content -->
                <section class="content">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="box box-solid box-primary">
                            <div class="box-header">
                            <h3 class="box-title">Detail LP Barang Jadi</h3>
                                <div class="box-tools pull-right">
                                    <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-minus"></i></button>
                                    <button class="btn btn-info btn-sm" data-widget="remove"><i class="fa fa-times"></i></button>
                                </div>
                            </div>

                    <div class="box-body">
                      <form class="form-horizontal">
              
              
              <div class="form-group">
                <label for="No LP" class="control-label col-lg-2">No LP <span style="color:#FF0000">*</span></label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->no_bpb;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
          <div class="form-group">
              <label for="Tanggal LP" class="control-label col-lg-2">Tanggal LP <span style="color:#FF0000">*</span></label>
              <div class="col-lg-10">
                <input type="text" disabled="" value="<?=tgl_indo($data_edit->tgl_bpb);?>" class="form-control">
              </div>
          </div><!-- /.form-group -->
          
              <div class="form-group">
                <label for="Project" class="control-label col-lg-2">Project <span style="color:#FF0000">*</span></label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->project;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
            <div class="form-group">
  <label class="control-label col-lg-2">Proses Produksi <span style="color:#FF0000">*</span></label>
  <div class="col-lg-10">

    <?php
    $dept = json_decode($data_edit->dept, true);

    if(is_array($dept) && count($dept) > 0){
        echo "<ul style='padding-left:20px;'>";

        foreach($dept as $d){
            echo "<li>$d</li>";
        }

        echo "</ul>";
    }else{
        echo "<span class='text-muted'>-</span>";
    }
    ?>

  </div>
</div>
              <div class="form-group">
                <label for="Nama PPC" class="control-label col-lg-2">Nama PPC </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->name_ppc;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Catatan" class="control-label col-lg-2">Catatan </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->catatan;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
               <div class="form-group">
  <label class="control-label col-lg-2">Detail Produksi</label>
  <div class="col-lg-10">

    <?php
    $no=1;
    $q = $db->query("
      SELECT 
        d.id_produksi_detail,
        b.kd_barang,
        b.nm_barang,
        b.satuan,
        d.jumlah
      FROM brgjadi_detail d 
      JOIN barang b ON b.kd_barang=d.kode 
      WHERE d.id_produksi='".uri_segment(3)."' limit 1
    ");

    foreach ($q as $k) {
    ?>

    <!-- 🔺 BARANG JADI -->
     <!-- 🔺 BARANG JADI -->
<div class="box box-success" style="margin-bottom:20px;">
  <div class="box-header">
    <h4 class="box-title">
      🔺 Barang Jadi (Hasil Produksi)
    </h4>
  </div>

  <div class="box-body">
    <table class="table table-bordered table-striped">
      <thead>
        <tr>
          <th>No</th>
          <th>Kode Barang</th>
          <th>Nama Barang</th>
          <th style="text-align:right">Qty Produksi</th>
          <th>Satuan</th>
        </tr>
      </thead>
      <tbody>

      <?php
      $no_fg = 1;

      $q_fg = $db->query("
        SELECT 
          d.id_produksi_detail,
          b.kd_barang,
          b.nm_barang,
          b.satuan,
          d.jumlah
        FROM brgjadi_detail d
        JOIN barang b ON b.kd_barang = d.kode
        WHERE d.id_produksi='".uri_segment(3)."'
      ");

      foreach ($q_fg as $fg){

        echo "<tr>
          <td>$no_fg</td>
          <td>$fg->kd_barang</td>
          <td>$fg->nm_barang</td>
          <td style='text-align:right'><b>".number_format($fg->jumlah,2)."</b></td>
          <td>$fg->satuan</td>
        </tr>";

        $no_fg++;
      }
      ?>

      </tbody>
    </table>
  </div>
</div>

    <!-- 🔻 BAHAN BAKU -->
  <!-- 🔻 BAHAN BAKU -->
<div class="box box-danger" style="margin-bottom:25px;">
  <div class="box-header">
    <h4 class="box-title">
      🔻 Bahan Baku Digunakan (Detail EXBC)
    </h4>
  </div>

  <div class="box-body">
    <table class="table table-striped table-bordered">
      <thead>
        <tr>
          <th>No</th>
          <th>Kode Barang</th>
          <th>Nama Barang</th>
          <th>No Aju</th>
          <th>No Dokpab</th>
          <th style="text-align:right">Qty Pakai</th>
          <th>Satuan</th>
        </tr>
      </thead>
      <tbody>

      <?php
      $no2 = 1;

      $qb = $db->query("
        SELECT 
          bb.kode,
          b.nm_barang,
          b.satuan,
          bb.jumlah,
          bb.no_aju,
          bb.no_dokpab
        FROM bahanbaku_detail bb
        JOIN barang b ON b.kd_barang = bb.kode
        WHERE bb.id_produksi_detail = '".$k->id_produksi_detail."'
        ORDER BY bb.kode, bb.no_aju
      ");

      if($qb->rowCount() > 0){

        foreach ($qb as $bb) {

          echo "<tr>
            <td>$no2</td>
            <td>$bb->kode</td>
            <td>$bb->nm_barang</td>
            <td>".($bb->no_aju ?: '-')."</td>
            <td>".($bb->no_dokpab ?: '-')."</td>
            <td style='text-align:right'>".number_format($bb->jumlah,2)."</td>
            <td>$bb->satuan</td>
          </tr>";

          $no2++;
        }

      } else {

        echo "<tr>
          <td colspan='7' class='text-center text-danger'>
            Tidak menggunakan bahan baku / Tidak ada EXBC
          </td>
        </tr>";
      }
      ?>

      </tbody>
    </table>
  </div>
</div>

    <?php
      $no++;
    }
    ?>

  </div>
</div>
                        
                      </form>
                      <a href="<?=base_index();?>lp-barang-jadi" class="btn btn-success "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>

                        </div>
                      </div>
                    </div>
                </div>
                <div id="modal_bahan_baku" class="modal fade" role="dialog">
                  <div class="modal-dialog modal-lg" style="width: 80%">

                    <!-- Modal content-->
                    <div class="modal-content">
                      <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title">Detail Bahan Baku</h4>
                      </div>
                      <div class="modal-body" id="isi_detail">
                        <p>Some text in the modal.</p>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                      </div>
                    </div>

                  </div>
                </div>

                </section><!-- /.content -->
  <script type="text/javascript">
    function detail_bahan_baku(id_produksi) { 
      $.ajax({
         url : "<?= base_url() ?>modul/lp_barang_jadi/lp_barang_jadi_action.php?act=detail_bahan_baku",
         type  : "POST",
         data : {
          id_produksi_detail : id_produksi 
         },
         success : function(data){
            $("#isi_detail").html(data); 
            $("#modal_bahan_baku").modal('show');
         }
      })
     // $("#detail_bahan_baku_"+id_produksi).show();
    }
  </script>
