<div class="row" style="padding-top: 15px">
    <div class="col-md-12">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">pungutan</h3>
        </div>
           <table class="table">
			<thead>
				<tr> 
					<th>Pungutan</th>
					<th>Tidak Dipungut</th>
					<th>Dibebaskan</th>
					<th>Ditangguhkan</th>
				</tr>
			</thead>
			<tbody></tbody>  
		</table> 
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

