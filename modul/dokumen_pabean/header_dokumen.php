<div class="row" style="padding-top: 15px">
    <div class="col-md-4">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">Pengajuan</h3>
        </div>
        <form role="form">
          <div class="box-body">
            <div class="form-group">
              <label for="exampleInputEmail1"><?=customs_h('submission_number','Nomor Pengajuan');?></label>
              <input type="text" class="form-control" id="no_aju" value="<?= $data_header->NOMOR_AJU ?>" placeholder="<?=customs_h('aju_no','Nomor Aju');?>" readonly="">
            </div>
          </div>
        </form>
      </div>
    </div>

    <div class="col-md-4">
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
 
     <div class="col-md-4">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title"><?=customs_h('other_information','Keterangan Lain');?></h3>
        </div>
        <form role="form">
          <div class="box-body">
            <div class="form-group">
              <label for="exampleInputEmail1"><?=customs_h('purpose','Tujuan');?></label>
              <select class="form-tujuan form-control" name="tujuan" id="tujuan" onchange="simpan_tujuan(this.value)" >
                <option value="">-Pilih Tujuan-</option>
                <?php
                $qt = $db->query("select * from ref_jenis_tpb");
                foreach ($qt as $kt) {
                   if ($kt->id_jenis_tpb==$data_header->KODE_TUJUAN_TPB) {
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
      save_data(val,'KODE_TUJUAN_TPB',$("#ID").val(),'tpb_header');
  } 
 

  function get_pelabuhan(val) { 

    save_data(val,'KODE_PEL_BONGKAR',$("#ID").val(),'tpb_header');

    $.ajax({
       url : "<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=get_detail_pelabuhan",
       type : "POST",
       data : {
         id : val,
         id_header : $("#ID").val()
       },
      // dataTye : 'JSON',
       success : function(data){
         $("#kantor_bongkar").val(data);
        // $("#kantor_pabean_pengawas").val(data);
       }
    });

  }




</script>
