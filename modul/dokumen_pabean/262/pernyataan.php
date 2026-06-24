<div class="row" style="padding-top: 15px">
    <div class="col-md-6">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">Tempat & Tanggal</h3>
        </div>
            <form role="form">
          <div class="box-body">
             <div class="form-group">
              <label for="exampleInputEmail1">Tempat</label>
              <input type="text" class="form-control" name="kotaTtd"  id="kotaTtd" value="<?= $data_header->kotaTtd ?>" placeholder="Kota" onkeyup="save_data(this.value,'kotaTtd',<?= $data_header->id_header ?>,'ws_header','id_header')" >
            </div>
            <div class="form-group">
              <label for="exampleInputEmail1"><?=customs_h('date','Tanggal');?></label>
              <input type="text" class="form-control tgl" name="tanggalTtd"  id="tanggalTtd" value="<?= $data_header->tanggalTtd ?>" placeholder="Tanggal TTD" onchange="save_data(this.value,'tanggalTtd',<?= $data_header->id_header ?>,'ws_header','id_header')" >
            </div>
          </div>
        </form> 
      </div>
    </div>
    <div class="col-md-6">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">Nama</h3>
        </div>
            <form role="form">
          <div class="box-body">
             <div class="form-group">
              <label for="exampleInputEmail1">Nama</label>
              <input type="text" class="form-control" name="namaTtd"  id="namaTtd" value="<?= $data_header->namaTtd ?>" placeholder="Nama Pejabat" onkeyup="save_data(this.value,'namaTtd',<?= $data_header->id_header ?>,'ws_header','id_header')" >
            </div>
            <div class="form-group">
              <label for="exampleInputEmail1">Jabatan</label>
              <input type="text" class="form-control" name="jabatanTtd"  id="jabatanTtd" value="<?= $data_header->jabatanTtd ?>" placeholder="Jabatan" onkeyup="save_data(this.value,'jabatanTtd',<?= $data_header->id_header ?>,'ws_header','id_header')" >
            </div>
          </div>
        </form> 
      </div>
    </div>

   
    
</div>
<div class="row"> 
  <div class="col-md-12">
     <a style="float: right" data-toggle="tab" class="btn btn-primary" onclick="kirim_dokumen()">Kirim Dokumen</a>
    <a style="float: right;margin-right: 3px" data-toggle="tab" class="btn btn-warning" onclick="activaTab('tab_pungutan')">Back <<</a>&nbsp;&nbsp;&nbsp;&nbsp;
    
  </div>
</div>

<script type="text/javascript">  

   function kirim_dokumen(id){
    Swal.fire({ 
      title: 'Yakin dokumen ini akan di kirim ?',
      text: "silahkan periksa kembali kelengkapan dokumen ini",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Ya'
    }).then((result) => {
      if (result.isConfirmed) {
      $("#loadnya").show();
          $.ajax({
       url : "<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=kirim_dokumen_23",
       type : "POST",
       dataType : "json",
       data : { 
         //id : val,
         id_header : $("#ID").val() 
       },
      // dataTye : 'JSON',
       success : function(data){ 
        $("#loadnya").hide();
         if (data.status=='OK') {
           Swal.fire({
              position: "center",
              icon: "success",
              title: "Dokumen Sukses Terkirim Ke Portal Beacukai",
              showConfirmButton: false,
              timer: 3000
            });
         }else{
          Swal.fire({
          icon: "error",
          title: "Oops...",
          text: data.message
          //footer: '<a href="#">Why do I have this issue?</a>'
        });
         }
       }
    });

       
      }
    });
  }



 $(document).ready(function() {
    $(".tgl").datepicker({ 
      format: "yyyy-mm-dd",
      autoclose: true, 
      todayHighlight: true
    });

  

  
  });
 

  function activaTab(tab){
      $('.nav-tabs a[href="#' + tab + '"]').tab('show');
  } 
 
 
  function simpan_tujuan(val){  
      save_data(val,'kodeTujuanTpb',$("#ID").val(),'ws_header','id_header'); 
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

