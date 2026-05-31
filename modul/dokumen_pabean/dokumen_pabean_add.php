<!-- Content Header (Page header) -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script type="text/javascript">
  function save_data(val,kolom,id,tabel){
      $.ajax({
       url : "<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=save_data",
       type : "POST",
       data : {
         kolom : kolom,
         nilai : val, 
         id    : id,
         tabel : tabel
       },
      // dataTye : 'JSON',
       success : function(data){
        // $("#kantor_bongkar").val(data);
        // $("#kantor_pabean_pengawas").val(data);
       }
    });
    }
</script>
  
    <!-- Main content -->
    <section class="content">
    <div class="row">
      <div class="col-lg-12">
        <div class="box box-solid box-primary">
          <div class="box-header">
            <h3 class="box-title">Add Dokumen x <?= $nama_pendek ?></h3>
            <div class="box-tools pull-right">
              <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-plus"></i></button>
            </div>
          </div>
          <div class="box-body">
           <div class="alert alert-danger error_data" style="display:none">
          <button type="button" class="close" data-dismiss="alert">&times;</button>
          <span class="isi_warning"></span>
        </div>

        <ul class="nav nav-tabs">
          <li class="active"><a data-toggle="tab" href="#tab_header">Header</a></li>
          <li><a data-toggle="tab" href="#tab_entitas">Entitas</a></li>
          <li><a data-toggle="tab" href="#tab_dokumen">Dokumen</a></li>
          <li><a data-toggle="tab" href="#tab_pengangkut">Pengangkut</a></li>
          <li><a data-toggle="tab" href="#tab_kemasan">Kemasan & Peti Kemas</a></li>
          <li><a data-toggle="tab" href="#tab_transaksi">Transaksi</a></li>
          <li><a data-toggle="tab" href="#tab_barang">Barang</a></li>
          <?php
          if ($jenis_dokpab=='262' || $jenis_dokpab=='261') {
          ?>
           <li><a data-toggle="tab" href="#jaminan">Jaminan</a></li>
          <?php
          }
          ?>
          <li><a data-toggle="tab" href="#pungutan">Pungutan</a></li>
          <li><a data-toggle="tab" href="#pernyataan">Pernyataan</a></li>
        </ul>
        <input type="hidden" name="uuid" id="uuid" value="<?= $data_header->UUID ?>">
        <input type="hidden" name="ID" id="ID" value="<?= $data_header->ID ?>">

        <div class="tab-content">
          <div id="tab_header" class="tab-pane fade in active">
            <?php include "header_dokumen.php" ?>
          </div>
          <div id="tab_entitas" class="tab-pane fade">
             <?php include "entitas.php" ?>
          </div>
          <div id="tab_dokumen" class="tab-pane fade">
             <?php include "dokumen.php" ?> 
          </div>
          <div id="tab_pengangkut" class="tab-pane fade">
             <?php include "pengangkut.php" ?>
          </div>
          <div id="tab_kemasan" class="tab-pane fade">
            <?php include "kemasan.php" ?>
          </div>
          <div id="tab_transaksi" class="tab-pane fade">
            <?php include "transaksi.php" ?>
          </div>
          <div id="tab_barang" class="tab-pane fade">
            <?php include "barang.php" ?>
          </div>
          <div id="tab_jaminan" class="tab-pane fade">
            <?php include "jaminan.php" ?>
          </div>
          <div id="tab_pungutan" class="tab-pane fade">
            <?php include "pungutan.php" ?>
          </div>
          <div id="tab_pernyatan" class="tab-pane fade">
            <?php include "pernyataan.php" ?>
          </div>
        </div>
             

          </div>
        </div>
      </div>
    </div>

    </section><!-- /.content -->

<script type="text/javascript">
    

    $(document).ready(function() {

      $(document).ready(function() {
        //    $(".form-pelabuhan").val("<?= $data_header->KODE_PEL_BONGKAR ?>"); // Select the option with a value of '1'
          $(".form-ref-tps").select2();
          $('.form-ref-angkut').select2(); 
          $('.form-tujuan').select2(); 
          $('.form-ref-dokumen').select2(); 
          // $('.form-bank').select2(); 
          $('.form-pelabuhan').select2({
                minimumInputLength: 2,
                tags: [],
                ajax: {
                    url: '<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=get_pelabuhan',
                    dataType: 'json',
                    type: "GET",
                    quietMillis: 50,
                    data: function (term) {
                        return {
                            term: term
                        };
                    },
                    results: function (data) {
                        return {
                            results: $.map(data, function (item) {
                                return {
                                    text: item.completeName,
                                    slug: item.slug,
                                    id: item.id
                                }
                            })
                        };
                    }
                }
            });

          $('.form-pelabuhan-transit').select2({
                minimumInputLength: 2,
                tags: [],
                ajax: {
                    url: '<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=get_pelabuhan',
                    dataType: 'json',
                    type: "GET",
                    quietMillis: 50,
                    data: function (term) {
                        return {
                            term: term
                        };
                    },
                    results: function (data) {
                        return {
                            results: $.map(data, function (item) {
                                return {
                                    text: item.completeName,
                                    slug: item.slug,
                                    id: item.id
                                }
                            })
                        };
                    }
                }
            });

          $('.form-pelabuhan-muat').select2({
                minimumInputLength: 2,
                tags: [],
                ajax: {
                    url: '<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=get_pelabuhan',
                    dataType: 'json',
                    type: "GET",
                    quietMillis: 50,
                    data: function (term) {
                        return {
                            term: term
                        };
                    },
                    results: function (data) {
                        return {
                            results: $.map(data, function (item) {
                                return {
                                    text: item.completeName,
                                    slug: item.slug,
                                    id: item.id
                                }
                            })
                        };
                    }
                }
            });

          $('.form-pemasok').select2({
                minimumInputLength: 2,
                tags: [],
                ajax: {
                    url: '<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=get_pemasok',
                    dataType: 'json',
                    type: "GET",
                    quietMillis: 50,
                    data: function (term) {
                        return {
                            term: term
                        };
                    },
                    results: function (data) {
                        return {
                            results: $.map(data, function (item) {
                                return {
                                    text: item.completeName,
                                    slug: item.slug,
                                    id: item.id
                                }
                            })
                        };
                    }
                }
            });

          $('.form-negara-angkut').select2({ 
                minimumInputLength: 2,
                tags: [],
                ajax: {
                    url: '<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=get_negara',
                    dataType: 'json',
                    type: "GET",
                    quietMillis: 50,
                    data: function (term) {
                        return {
                            term: term
                        };
                    },
                    results: function (data) {
                        return {
                            results: $.map(data, function (item) {
                                return {
                                    text: item.completeName,
                                    slug: item.slug,
                                    id: item.id
                                }
                            })
                        };
                    }
                }
            });

          $('.form-negara').select2({ 
                minimumInputLength: 2,
                tags: [],
                ajax: {
                    url: '<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=get_negara',
                    dataType: 'json',
                    type: "GET",
                    quietMillis: 50,
                    data: function (term) {
                        return {
                            term: term
                        };
                    },
                    results: function (data) {
                        return {
                            results: $.map(data, function (item) {
                                return {
                                    text: item.completeName,
                                    slug: item.slug,
                                    id: item.id
                                }
                            })
                        };
                    }
                }
            }); 

         


       
      });
     
    
    
    $("#input_dokumen_pabean").validate({
        errorClass: "help-block",
        errorElement: "span",
        highlight: function(element, errorClass, validClass) {
            $(element).parents(".form-group").removeClass(
                "has-success").addClass("has-error");
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).parents(".form-group").removeClass(
                "has-error").addClass("has-success");
        },
        errorPlacement: function(error, element) {
            if (element.hasClass("chzn-select")) {
                var id = element.attr("id");
                error.insertAfter("#" + id + "_chosen");
            } else if (element.attr("type") == "checkbox") {
                element.parent().parent().append(error);
            } else if (element.attr("type") == "radio") {
                element.parent().parent().append(error);
            } else {
                error.insertAfter(element);
            }
        },
        
        submitHandler: function(form) {
            $("#loadnya").show();
            $(form).ajaxSubmit({
                url : $(this).attr("action"),
                dataType: "json",
                type : "post",
                error: function(data ) { 
                  $("#loadnya").hide();
                  console.log(data); 
                },
                success: function(responseText) {
                  $("#loadnya").hide();
                  console.log(responseText);
                      $.each(responseText, function(index) {
                          console.log(responseText[index].status);
                          if (responseText[index].status=="die") {
                            $("#informasi").modal("show");
                          } else if(responseText[index].status=="error") {
                             $(".isi_warning").text(responseText[index].error_message);
                             $(".error_data").focus()
                             $(".error_data").fadeIn();
                          } else if(responseText[index].status=="good") {
                            $(".error_data").hide();
                            $(".notif_top").fadeIn(1000);
                            $(".notif_top").fadeOut(1000, function() {
                                    window.history.back();
                            });
                          } else {
                             console.log(responseText);
                             $(".isi_warning").text(responseText[index].error_message);
                             $(".error_data").focus()
                             $(".error_data").fadeIn();
                          }
                    });
                }
  
            });
        }
    });
});


var data_tps = {
    id: "<?= $data_header->KODE_TPS ?>",
    text: "<?= $data_header->KODE_TPS." - ".$data_header->nama_tps ?>"
};  
var newOptionTps = new Option(data_tps.text, data_tps.id, false, false);
$('.form-ref-tps').append(newOptionTps).trigger('change');


 var data = {
    id: "<?= $data_header->KODE_PEL_BONGKAR ?>",
    text: "<?= $data_header->KODE_PEL_BONGKAR." - ".$data_header->nama_kantor ?>"
};


var newOption = new Option(data.text, data.id, false, false);
$('.form-pelabuhan').append(newOption).trigger('change'); 

 var data2 = {
    id: "<?= $data_header->KODE_PEL_MUAT ?>",
    text: "<?= $data_header->KODE_PEL_MUAT." - ".$data_header->pel_muat ?>"
};


var newOptionMuat = new Option(data2.text, data2.id, false, false);
$('.form-pelabuhan-muat').append(newOptionMuat).trigger('change'); 

 var data3 = {
    id: "<?= $data_header->KODE_PEL_TRANSIT ?>",
    text: "<?= $data_header->KODE_PEL_TRANSIT." - ".$data_header->pel_transit ?>"
};


var newOptionTran = new Option(data3.text, data3.id, false, false);
$('.form-pelabuhan-transit').append(newOptionTran).trigger('change');

var data_pemasok = {
    id: "<?= $data_header->ID_PEMASOK." - ".$data_header->NAMA_PEMASOK ?>",
    text: "<?= $data_header->ID_PEMASOK." - ".$data_header->NAMA_PEMASOK ?>"
};  
var newOptionTujuan = new Option(data_pemasok.text, data_pemasok.id, false, false);

$('.form-pemasok').append(newOptionTujuan).trigger('change');

var data_negara = {
    id: "<?= $data_header->KODE_NEGARA_PEMASOK ?>",
    text: "<?= $data_header->KODE_NEGARA_PEMASOK." - ".$data_header->NAMA_NEGARA ?>"
};  
var newOptionNegara = new Option(data_negara.text, data_negara.id, false, false);

$('.form-negara').append(newOptionNegara).trigger('change');

var data_angkut = {
    id: "<?= $data_header->KODE_CARA_ANGKUT ?>",
    text: "<?= $data_header->KODE_CARA_ANGKUT." - ".$data_header->cara_angkut ?>"
};  
var newOptionAngkut = new Option(data_angkut.text, data_angkut.id, false, false);

$('.form-ref-angkut').val(data_angkut.id).trigger('change'); 

var data_negara2 = {
    id: "<?= $data_header->KODE_BENDERA ?>",
    text: "<?= $data_header->KODE_BENDERA." - ".$data_header->bendera ?>"
};  
var newOptionNegara2 = new Option(data_negara2.text, data_negara2.id, false, false);
$('.form-negara-angkut').append(newOptionNegara2).trigger('change');

 
</script>
