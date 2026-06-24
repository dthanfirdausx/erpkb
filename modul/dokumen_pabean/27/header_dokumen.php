<div class="row" style="padding-top: 15px">
    <div class="col-md-6">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">Pengajuan Asal</h3>
        </div>
        <form role="form">
          <div class="box-body">
            <div class="form-group">
              <label for="exampleInputEmail1"><?=customs_h('submission_number','Nomor Pengajuan');?></label>
              <input type="text" class="form-control" id="no_aju" value="<?= $data_header->nomorAju ?>" placeholder="<?=customs_h('aju_no','Nomor Aju');?>" readonly="">
            </div>
            <div class="form-group">
              <label for="kantor">Kantor Pabean Asal</label>
              <input type="text" class="form-control" name="kantor_bongkar" id="kantor_bongkar" readonly="">
            </div>
            <div class="form-group">  
              <label for="exampleInputEmail1">Jenis TP Asal</label>
              <select class="form-tujuan form-control" name="tujuan" id="tujuan" onchange="simpan_asal_tpb(this.value)" >
                <option value="">-Pilih Tujuan-</option>
                <?php
                $qt = $db->query("select * from ref_jenis_tpb");
                foreach ($qt as $kt) {
                   if ($kt->id_jenis_tpb==$data_header->kodeTujuanTpb) {
                     echo "<option value='$kt->id_jenis_tpb' selected>$kt->jenis_tpb</option>";
                   }else{
                    echo "<option value='$kt->id_jenis_tpb'>$kt->jenis_tpb</option>";
                   }
                    
                }
                ?>
              </select> 
            </div>
          </div>
        </form>
      </div>
    </div>

    <div class="col-md-4" style="display: none">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">Kantor Pabean</h3>
        </div>
        <form role="form">
          <div class="box-body">
            <div class="form-group"> 
              <label for="kantor">Pelabuhan Bongkar</label>
              <select class="form-pelabuhan form-control" name="pelabuhan_bongkar" id="pelabuhan_bongkar" onchange="get_pelabuhan(this.value)">
              </select>
            </div>
            <div class="form-group">
              <label for="kantor">Kantor Pabean Bongkar</label>
              <input type="text" class="form-control" name="kantor_bongkar" id="kantor_bongkar" readonly="">
            </div>
            <div class="form-group">
              <label for="kantor">Kantor Pabean Pengawas</label>
              <input type="text" class="form-control" value="<?= $info->kantor_pengawas ?>" name="kantor_pabean_pengawah" id="kantor_pabean_pengawah" readonly="">
            </div>
          </div>
        </form>
      </div> 
    </div> 
 
     <div class="col-md-6">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">Pengajuan Tujuan</h3>
        </div>
        <form role="form">
          <div class="box-body">
            <div class="form-group">
              <label for="exampleInputEmail1">Kantor Pabean Tujuan</label>
              <select class="form-tujuan form-control" name="kodeKantorTujuan" id="kodeKantorTujuan" onchange="simpan_kantor_tujuan(this.value)" >
                <option value="">-Pilih Tujuan-</option>
                <?php
                $qt = $db->query("select * from ref_kantor"); 
                foreach ($qt as $kt) {
                   if ($kt->id_kantor==$data_header->kodeKantorTujuan) {
                     echo "<option value='$kt->id_kantor' selected>$kt->id_kantor - $kt->nama_kantor</option>";
                   }else{
                    echo "<option value='$kt->id_kantor'>$kt->id_kantor - $kt->nama_kantor</option>";
                   }
                    
                }
                ?>
              </select> 
            </div>
          </div>
          <div class="box-body">
            <div class="form-group">
              <label for="exampleInputEmail1">Jenis TPB Tujuan</label>
              <select class="form-tujuan form-control" name="tujuan" id="tujuan" onchange="simpan_tujuan(this.value)" >
                <option value="">-Pilih Tujuan-</option>
                <?php
                $qt = $db->query("select * from ref_jenis_tpb");
                foreach ($qt as $kt) {
                   if ($kt->id_jenis_tpb==$data_header->kodeTujuanTpb) {
                     echo "<option value='$kt->id_jenis_tpb' selected>$kt->id_jenis_tpb - $kt->jenis_tpb</option>";
                   }else{
                    echo "<option value='$kt->id_jenis_tpb'>$kt->id_jenis_tpb - $kt->jenis_tpb</option>";
                   }
                    
                }
                ?>
              </select> 
            </div>
          </div>
           <div class="box-body">
            <div class="form-group">
              <label for="exampleInputEmail1">Tujuan Pengiriman</label>
              <select class="form-tujuan-tpb form-control" name="kodeTujuanTpb" id="kodeTujuanTpb" onchange="simpan_tujuan_tpb(this.value)" >
                <option value="">-Pilih Tujuan-</option>
                <?php
                $qt = $db->query("select * from ref_tujuan_pengiriman"); 
                foreach ($qt as $kt) {
                   if ($kt->kode_tujuan_pengiriman==$data_header->kodeTujuanTpb) {
                     echo "<option value='$kt->kode_tujuan_pengiriman' selected>$kt->kode_tujuan_pengiriman - $kt->nama_tujuan</option>";
                   }else{
                    echo "<option value='$kt->kode_tujuan_pengiriman'>$kt->kode_tujuan_pengiriman - $kt->nama_tujuan</option>";
                   }
                    
                }
                ?>
              </select> 
            </div>
          </div>
        </form> 
    
      </div>
    </div>
    
</div>
<div class="row"> 
  <div class="col-md-12">
    <a style="float: right" data-toggle="tab" class="btn btn-primary" onclick="activaTab('tab_entitas')">Next >></a>
  </div>
</div>
<script type="text/javascript">  


 

  function activaTab(tab){
      $('.nav-tabs a[href="#' + tab + '"]').tab('show');
  } 
 
 
  function simpan_tujuan(val){  
      save_data(val,'kodeTujuanTpb',$("#ID").val(),'ws_header','id_header'); 
  }

  function simpan_tujuan_tpb(val){  
      save_data(val,'kodeTujuanTpb',$("#ID").val(),'ws_header','id_header'); 
  } 

  function simpan_kantor_tujuan(val){  
      save_data(val,'kodeKantorTujuan',$("#ID").val(),'ws_header','id_header'); 
  } 

   function simpan_asal_tpb(val){  
      save_data(val,'kodeJenisTpb',$("#ID").val(),'ws_header','id_header'); 
  }  
 

  function get_pelabuhan(val) {     
  //  alert("pelabuhan");

    save_data(val,'kodePelBongkar',$("#ID").val(),'ws_header','id_header'); 

    $.ajax({
       url : "<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=get_detail_pelabuhan",
       type : "POST",
       data : {
         id : val,
         id_header : $("#ID").val() 
       },
      // dataTye : 'JSON',
       success : function(datas){ 
         $("#kantor_bongkar").val(datas); 
         var delimiter = " - ";
         var key = datas.split(delimiter); 
         save_data(key[0],'kodeKantorBongkar',$("#ID").val(),'ws_header','id_header'); 
         $.ajax({
           url : "<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=get_tps",
           type : "POST",
           dataType : "JSON",
           data : {
             val : key,
             //id_header : $("#ID").val()
           },
          // dataTye : 'JSON',
           success : function(datap){ 
             $(".form-ref-tps").select2({
               data: datap
             });
           }
        });
        
       }
    });

  }




</script>
