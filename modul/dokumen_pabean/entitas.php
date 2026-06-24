<div class="row" style="padding-top: 15px">
    <div class="col-md-4">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">Importir/Pengusaha TPB</h3>
        </div>
        <form role="form">
          <div class="box-body">
            <div class="form-group">
              <label for="exampleInputEmail1">NPWP</label>
              <input type="text" class="form-control" id="npwp" value="<?= $info->npwp ?>" placeholder="NPWP" readonly="">
            </div>
            <div class="form-group">
              <label for="exampleInputEmail1">Nama</label>
              <input type="text" class="form-control" id="npwp" value="<?= $info->nama ?>" placeholder="NPWP" >
            </div>
            <div class="form-group">
              <label for="exampleInputEmail1">Alamat</label>
              <textarea class="form-control" id="alamat"  placeholder="NPWP" ><?= $info->alamat ?></textarea>
            </div>
            <div class="form-group">
              <label for="exampleInputEmail1"><?=customs_h('tpb_license_number','Nomor Izin TPB');?></label>
              <div class="row">
                 <div class="col-md-6">
                   <input type="text" class="form-control" id="npwp" value="<?= $info->skepkb ?>" placeholder="NPWP" >
                 </div>
                 <div class="col-md-6">
                   <input type="text" class="form-control" id="tglskep" value="<?= $info->tglskep ?>" placeholder="NPWP" >
                 </div>
              </div>        
            </div>
            <div class="form-group">
              <label for="exampleInputEmail1">NIB</label>
              <input type="text" class="form-control" id="nib"  placeholder="NIB" >
            </div>
          </div>
        </form>
      </div>
    </div>

    <div class="col-md-4">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">Pemasok</h3>
        </div>
        <form role="form"> 
          <div class="box-body">
            <div class="form-group"> 
              <label for="kantor">Nama</label>
              <select style="width: 100%" class="form-pemasok form-control" name="pemasok" id="pemasok" onchange="get_pemasok(this.value)">
              </select> 
            </div>
            <div class="form-group">
              <label for="kantor">Alamat</label>
              <textarea class="form-control" name="alamat_pemasok" id="alamat_pemasok"></textarea>
            </div>
            <div class="form-group">
              <label for="kantor">Negara</label>
              <select class="form-negara form-control" name="negara" id="negara" style="width: 100%" onchange="save_data(this.value,'KODE_NEGARA_PEMASOK',$('#ID').val(),'tpb_header')" >
              </select>
            </div> 
          </div>
        </form>
      </div> 
    </div> 
 
     <div class="col-md-4">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">Pemilik Barang</h3>
        </div>
        <form role="form">
          <div class="box-body">
            <div class="form-group">
              <label for="exampleInputEmail1">NPWP</label>
              <input type="text" class="form-control" name="ID_PEMILIK"  id="ID_PEMILIK" value="<?= $data_header->ID_PEMILIK ?>" placeholder="NPWP" onkeyup="save_data(this.value,'ID_PEMILIK',$('#ID').val(),'tpb_header')" >
            </div>
            <div class="form-group">
              <label for="exampleInputEmail1">Nama</label>
              <input type="text" class="form-control" name="NAMA_PEMILIK"  id="NAMA_PEMILIK" value="<?= $data_header->NAMA_PEMILIK ?>" placeholder="Nama Pemilik" onkeyup="save_data(this.value,'NAMA_PEMILIK',$('#ID').val(),'tpb_header')" >
            </div>
            <div class="form-group">
              <label for="exampleInputEmail1">Alamat</label>
              <textarea class="form-control" id="ALAMAT_PEMILIK" onkeyup="save_data(this.value,'ALAMAT_PEMILIK',$('#ID').val(),'tpb_header')" name="ALAMAT_PEMILIK"  placeholder="Alamat Pemilik" ><?= $data_header->ALAMAT_PEMILIK ?></textarea>
            </div>
          </div>
        </form> 
    
      </div>
    </div>
    
</div>
<div class="row"> 
  <div class="col-md-12">
    <a style="float: right" data-toggle="tab" class="btn btn-primary" style="left: 45px"  onclick="activaTab('tab_dokumen')">Next >></a>
    <a style="float: right;margin-right: 10px" data-toggle="tab"  class="btn btn-warning" onclick="activaTab('tab_header')">Back <<</a>
  </div>
</div> 
<script type="text/javascript">   


 

  function activaTab(tab){
      $('.nav-tabs a[href="#' + tab + '"]').tab('show');
  }

 
  function simpan_tujuan(val){ 
      save_data(val,'KODE_TUJUAN_TPB',$("#ID").val(),'tpb_header');
  } 
 

  function get_pemasok(val) { 

    //let text = "How are you doing today?";
    const pem = val.split(" - "); 

    save_data(pem[0],'ID_PEMASOK',$("#ID").val(),'tpb_header');
    save_data(pem[1],'NAMA_PEMASOK',$("#ID").val(),'tpb_header');

    $.ajax({
       url : "<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=get_detail_pemasok",
       type : "POST",
       data : {
         id : pem[0]
       },
       dataType : "JSON",
      // dataTye : 'JSON',
       success : function(data){
         // $("#npwp").val(data.npwp);
         // $("#nama").val(data.nama);
         $("#alamat_pemasok").val(data.alamat);
         save_data(data.alamat,'ALAMAT_PEMASOK',$("#ID").val(),'tpb_header');
        // $("#kantor_pabean_pengawas").val(data);
       }
    });

  }




</script>
