<style type="text/css">
	.box.box-primary {
	    border-top-color: #3c8dbc;
	    padding-bottom: 5px;
	}
</style> 
<div class="row" style="padding-top: 15px">
    <div class="col-md-12">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">Barang</h3>
          <div style="float: right;">
          	<!-- <button id="btn_tambah" class="btn btn-default" style="display: inline;" onclick="download_barang()"><i class="fa fa-download"></i> Unduh Excel</button>
          	<button id="btn_tambah" class="btn btn-primary" style="display: inline;" onclick="show_upload_barang()"><i class="fa fa-upload"></i> Unggah Excel</button> -->
          	<button id="btn_tambah" class="btn btn-primary" style="display: inline;" onclick="show_tambah_barang()"><i class="fa fa-plus"></i> Tambah</button> 
          </div>
          
        </div>
        
        <table class="table">
          <thead>
            <tr>
              <th>Seri</th>
              <th>HS</th>
              <th>Uraian</th>
              <th>Nilai Barang</th>
              <th>Jumlah Satuan</th>
              <th>Jenis Satuan</th>
              <th></th> 
            </tr>
          </thead>
          <tbody id="isi_barang">
          <?php
          $q = $db->query("select * from ws_barang  where id_header='$data_header->id_header'  ");
          if ($q->rowCount()==0) {
          ?>
          <tr>
              <td colspan="8">Belum ada data</td>
            </tr>
          <?php
          }else{
            $no=1;
            foreach ($q as $k) {
               echo "<tr id='baris_".$k->idBarang."'>
                       <td>$k->seriBarang</td>
                       <td>$k->hsCode</td>
                       <td>$k->uraian</td>
                       <td>$k->hargaPenyerahan</td>
                       <td>$k->jumlahSatuan</td>
                       <td>$k->kodeSatuanBarang<td>
                       <td>
                         <button class='btn btn-primary' onclick='edit_data_barang($k->idBarang)'><i class='fa fa-pencil'></i></button>
                         <button class='btn btn-danger' onclick='modal_delete_barang($k->idBarang)'><i class='fa fa-trash'></i></button>
                       </td>
                     </tr>";
            }
          }
          ?>
            
          </tbody>
        </table>
      </div>
    </div>  
    
</div>
<div class="row"> 
  <div class="col-md-12">
    <a style="float: right" data-toggle="tab" class="btn btn-primary" style="left: 45px"  onclick="activaTab('tab_pungutan')">Next >></a>
    <a style="float: right;margin-right: 10px" data-toggle="tab"  class="btn btn-warning" onclick="activaTab('tab_transaksi')">Back <<</a>
  </div>
</div> 
<div id="modal_barang" class="modal fade" role="dialog">
                <div class="modal-dialog modal-lg" style="width: 95%">

                  <!-- Modal content-->
                  <div class="modal-content">
                    <div class="modal-header">
                     <div class="modal-footer">
                       <button type="button" class="btn btn-danger" data-dismiss="modal">batal</button>
                       <button type="button" class="btn btn-success" data-dismiss="modal" >Simpan</button>
                    </div>
                      <h4 class="modal-title">Detail Barang</h4>
                    </div>
                    
                     <input type="hidden" name="id_barang" id="id_barang">
                    <div class="modal-body-barang">
                      
                    </div>
                    <div class="modal-footer">
                       <button type="button" class="btn btn-danger" data-dismiss="modal">batal</button>
                       <button type="button" class="btn btn-success" data-dismiss="modal" >Simpan</button>
                    </div>
                  </div>

                </div>
              </div> 
             <div id="modal_dokuman_fasilitas" class="modal fade" role="dialog">
                <div class="modal-dialog modal-lg" >

                  <!-- Modal content-->
                  <div class="modal-content">
                    <div class="modal-header">
                      <button type="button" class="close" data-dismiss="modal">&times;</button>
                      <h4 class="modal-title">Daftar dokumen</h4>
                    </div>
                     <input type="hidden" name="id_barang" id="id_barang">
                    <div class="modal-body-fasilitas">
                      <table class="table">
                        <thead>
                          <tr>
                           <th></th>
                           <th>Seri</th>
                           <th>Jenis</th>
                           <th>Nomor</th>
                           <th>Tanggal</th>
                         
                        </tr>
                        </thead>
                        <tbody id="detail_dokumen_barang">
                        </tbody>
                      </table>
                    </div>
                    <div class="modal-footer">
                       <button type="button" class="btn btn-danger" data-dismiss="modal">batal</button>
                       <button type="button" class="btn btn-success"  data-dismiss="modal"  >Simpan</button>
                    </div>
                  </div>

                </div>
              </div> 
              <div id="modal_dokuman_entitas" class="modal fade" role="dialog">
                <div class="modal-dialog modal-lg" >
 
                  <!-- Modal content-->
                  <div class="modal-content">
                    <div class="modal-header">
                      <button type="button" class="close" data-dismiss="modal">&times;</button>
                      <h4 class="modal-title">Daftar Entitas</h4>
                    </div>
                    <div class="modal-body-fasilitas">
                      <table class="table">
                        <thead>
                          <tr>
                           <th></th>
                           <th>Seri</th>
                           <th>No Identitas</th>
                           <th>Nama</th>
                           <th>Entitas</th>
                        </tr>
                        </thead>
                        <tbody id="detail_entitas_barang_modal">
                        </tbody>
                      </table>
                    </div>
                    <div class="modal-footer">
                       <button type="button" class="btn btn-danger" data-dismiss="modal">batal</button>
                       <button type="button" class="btn btn-success"  data-dismiss="modal"  >Simpan</button>
                    </div>
                  </div>

                </div>
              </div> 
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script type="text/javascript">
   
   function modal_delete_barang(id){
      Swal.fire({ 
        title: 'Yakin akan di hapus ?',
        text: "data yang sudah terhapus tidak bisa di kembalikan",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
      }).then((result) => {  
        if (result.isConfirmed) {
          $.ajax({
             url : "<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=hapus_detail_barang",
             type : "POST",
           //  dataType : "JSON",
             data : {
               id : id
             },
            // dataTye : 'JSON',
             success : function(data){
                $("#baris_"+id).remove();
                Swal.fire(
                  'Deleted!',
                  'Your data has been deleted.',
                  'success'
                )
             }
          });

         
        }
      });
   }

   $(document).ready(function() {
    $(".form-kategori-barang").select2();
    $(".form-negara-barang").select2();
   
    });

	function show_tambah_barang() {
     $.ajax({
       url : "<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=add_barang",
       type : "POST",
       dataType : "JSON", 
       data : {
         id : $("#ID").val()
       },
      // dataTye : 'JSON',
       success : function(data){
        $("#id_barang").val(data.id);
          $.ajax({
		       url : "<?= base_url() ?>modul/dokumen_pabean/30/get_detail_barang.php",
		       type : "POST", 
		       //dataType : "JSON",
		       data : {
		         id : $("#id_barang").val()
		       },
		      // dataTye : 'JSON',
		       success : function(data){ 
		        $(".modal-body-barang").html(data);
		        $("#modal_barang").modal('show');
		       }
		    });
       
       }
    });  
  }

  function edit_data_barang(id) {
   
        $("#id_barang").val(id);
          $.ajax({
		       url : "<?= base_url() ?>modul/dokumen_pabean/30/get_detail_barang.php",
		       type : "POST",
		      // dataType : "JSON",
		       data : { 
		         id : id 
		       },
		      // dataTye : 'JSON',
		       success : function(data){ 
		        //	alert(data);
		         $(".modal-body-barang").html(data);
		         $("#modal_barang").modal('show');
		       }
		    });
     
  }

</script>