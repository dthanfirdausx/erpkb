<?php
include "../../inc/config.php";
$info_pt = info_pt();
$q = $db->query("select s.*,p.nama as nama_penerima,p.npwp,p.no_izin from sales_order s left join penerima p on p.kode_penerima=s.kode_penerima where s.id_sales_order=?",array($_GET['id']));
foreach ($q as $k) {
	$currency = $k->currency;
	?>

<!DOCTYPE html>
<html>
<head>
	<title>Cetak Sales Order</title>
	
	<style>
	table, td, th {
	  border: 1px solid black;
  	  border-style: none;
	}

	#table1 {
	  border-collapse: separate;
	}

	#table2 {
	  border-collapse: collapse;
  	  border-style: solid;
	}
	#table3 {
  	  border-collapse: collapse;
	}

	/* @page {size:landscape}  */ 
@media print {

    @page {size: A4 portrait;max-height:100%; max-width:100%}

    /* use width if in portrait (use the smaller size to try 
       and prevent image from overflowing page... */
    img { height: 90%; margin: 0; padding: 0; }

	</style>
	<script type="text/javascript">
		var css = '@page { size: landscape; }',
    head = document.head || document.getElementsByTagName('head')[0],
    style = document.createElement('style');

style.type = 'text/css';
style.media = 'print';

if (style.styleSheet){
  style.styleSheet.cssText = css; 
} else {
  style.appendChild(document.createTextNode(css));
}

head.appendChild(style);

	//window.print();
	</script>
</head>
<body>



	<div class="row" style="width: 1000px" >
	
<!--			<table id="table2" style="width: 400px;float: left;margin-right: 10px"> -->
			<table id="table3" style="width: 1000px;float: left;margin-right: 10px">

				<tr style=" border-style: none">
					<td rowspan="5"  style="text-align: left;font-size: 20px; width: 100px; border-style: none"> 
						<img src="<?= base_url() ?>assets/gblight_logo.png" style="width: 120px">
						</td>
						<td colspan = "2" style="text-align: left;font-size: 20px; width: 200px; border-style: none" ><?= $info_pt->nama ?></td>
						<td  style="text-align: left;font-size: 20px; width: 200px; border-style: none" ></td>
						<td style="text-align: left;font-size: 20px; width: 200px; border-style: none" ><?= $k->no_sales_order ?></td>
				</tr>

				<tr>
						<td style="text-align: left;font-size: 20px; width: 50px" >NPWP </td>
						<td style="text-align: left;font-size: 20px; width: 200px" >: <?= $info_pt->npwp ?></td>
						<td  style="text-align: left;font-size: 20px; width: 200px" ></td>
						<td style="text-align: left;font-size: 20px; width: 200px" ></td>
				</tr>
				<tr>
						<td  colspan = "2" style="text-align: left;font-size: 20px; width: 50px" ><?= $info_pt->cdob ?></td>
						<td  style="text-align: left;font-size: 20px; width: 200px" ></td>
						<td style="text-align: left;font-size: 20px; width: 200px" ></td>
				</tr>
				<tr>
						<td colspan = "2" style="text-align: left;font-size: 20px; width: 50px" ><?= $info_pt->pbob ?> </td>
						<td  style="text-align: left;font-size: 20px; width: 200px" ></td>
						<td style="text-align: left;font-size: 20px; width: 200px" ></td>
				</tr>
				<tr>
						<td  rowspan = "2" colspan = "2" style="text-align: left;font-size: 20px; width: 250px" > <?= $info_pt->alamat ?> </td>
						<td  style="text-align: left;font-size: 20px; width: 200px" ></td>
						<td  style="text-align: left;font-size: 20px; width: 200px" ></td>
				</tr>
				<tr>
						<td  style="text-align: left;font-size: 20px; width: 200px" ></td>
						<td  style="text-align: left;font-size: 20px; width: 200px" ></td>
				</tr>

			</table>
	</div>		

<br>

	<div class="row" style="width: 1000px;margin-top: 120px">

			<table id="table2" style="width: 400px;float: left;margin-right: 10px">
					<h3 style="text-align: center"><u>SALES ORDER</u></h3>
			</table>
	</div>		


	<div class="row" style="width: 1000px">
	
			<table id="table3" style="width: 1000px;float: left;margin-right: 10px">

				<tr>
						<td colspan = "1" style="text-align: left;font-size: 20px; width: 100px" >Customer</td>
						<td colspan = "1" style="text-align: left;font-size: 20px; width: 400px" ><?= $k->nama_penerima ?></td>
						<td  style="text-align: left;font-size: 20px; width: 200px" ></td>
						<td style="text-align: left;font-size: 20px; width: 200px" >Tanggal</td>
						<td style="text-align: left;font-size: 20px; width: 200px"><?= $k->so_date ?></td>
				</tr>

				<tr>
						<td style="text-align: left;font-size: 20px; width: 50px" >NPWP </td>
						<td style="text-align: left;font-size: 20px; width: 200px" >: <?= $k->npwp ?></td>
						<td  style="text-align: left;font-size: 20px; width: 200px" ></td>
						<td style="text-align: left;font-size: 20px; width: 200px" >No. PO</td>
						<td style="text-align: left;font-size: 20px; width: 200px"><?= $k->no_po ?></td>
						<td></td>
				</tr>
				<tr>
						<td style="text-align: left;font-size: 20px; width: 50px" >No. Izin</td>
						<td style="text-align: left;font-size: 20px; width: 200px" >: <?= $k->no_izin ?></td>
						<td  style="text-align: left;font-size: 20px; width: 200px" ></td>
						<td  style="text-align: left;font-size: 20px; width: 200px" >Tanggal Kirim</td>
						<td style="text-align: left;font-size: 20px; width: 200px"><?= tgl_indo(date("Y-m-d")) ?></td>
				</tr>
				<tr>
						<td  colspan = "1" style="text-align: left;font-size: 20px; width: 100px" ></td>
						<td  style="text-align: left;font-size: 20px; width: 200px" ></td>
						<td  style="text-align: left;font-size: 20px; width: 200px" ></td>
						<td  style="text-align: left;font-size: 20px; width: 200px" ></td>
						<td></td>
				</tr>
				<tr>
						<td  colspan = "1" style="text-align: left;font-size: 20px; width: 100px" >TOP</td>
						<td  style="text-align: left;font-size: 20px; width: 200px" >: <?= $k->term ?></td>
						<td  style="text-align: left;font-size: 20px; width: 200px" ></td>
						<td  style="text-align: left;font-size: 20px; width: 200px" >Kode Sales</td>
						<td style="text-align: left;font-size: 20px; width: 200px"><?= $k->sales_id ?></td>
						
				</tr>
			</table>
	</div>		


	<div class="row" style="width: 1000px">
	
			<table id="table3" style="width: 1000px;float: left;margin-right: 10px">
 		   
				<tr>
						<td colspan = "1" style=" border: 1px black solid;text-align: left;font-size: 20px; width: 30px" >No.</td>
						<td colspan = "1" style=" border: 1px black solid;text-align: left;font-size: 20px; width: 300px" >Nama Barang</td>
						<td colspan = "2" style=" border: 1px black solid;text-align: left;font-size: 20px; width: 70px" >Qty</td>
						<td style=" border: 1px black solid;text-align: left;font-size: 20px; width: 100px" >Pack</td>

<!--						<td style=" border: 1px black solid;text-align: left;font-size: 20px; width: 200px" >No. Batch</td>
-->
						<td style=" border: 1px black solid;text-align: left;font-size: 20px; width: 130px" >Harga Satuan</td>
						<td style=" border: 1px black solid;text-align: left;font-size: 20px; width: 170px" >Jumlah</td>
				</tr> 
				<?php  
 		   $qd = $db->query("select d.kd_barang,b.nm_barang,b.packing_size,packing,satuan,d.nilai,d.price, d.qty from sales_order_detail d join barang b on b.kd_barang=d.kd_barang  where d.id_sales_order=? ",array($_GET['id']));
 		   $no=1;
 		   $total = 0;
 		   foreach ($qd as $kd) {
 		 
 		   ?>

				<tr>
						<td colspan = "1" style=" border: 1px black solid;text-align: left;font-size: 20px; width: 30px" ><?= $no ?></td>
						<td colspan = "1" style=" border: 1px black solid;text-align: left;font-size: 20px; width: 300px" ><?= $kd->kd_barang." ".$kd->nm_barang ?></td>
						<td colspan = "2" style=" border: 1px black solid;text-align: left;font-size: 20px; width: 70px" ><?= $kd->qty ?></td>
						<td style=" border: 1px black solid;text-align: left;font-size: 20px; width: 100px" ><?= $kd->packing ?></td>
<!--						<td style=" border: 1px black solid;text-align: left;font-size: 20px; width: 200px" ></td>
-->
						<td style=" border: 1px black solid;text-align: left;font-size: 20px; width: 130px" ><?= $currency." ".number_format($kd->price) ?></td>
						<td style=" border: 1px black solid;text-align: left;font-size: 20px; width: 170px" ><?= $currency." ".number_format($kd->price*$kd->qty) ?></td> 
				</tr>

				<?php
				$total = ($kd->price*$kd->qty)+ $total;
				$no++; 
			}
			?>

				<tr>
						<td colspan = "1" style=" border: 1px black solid;text-align: left;font-size: 20px; width: 30px" ></td>
						<td colspan = "1" style=" border: 1px black solid;text-align: left;font-size: 20px; width: 300px" >TOTAL</td>
						<td colspan = "2" style=" border: 1px black solid;text-align: left;font-size: 20px; width: 70px" ></td>
						<td style=" border: 1px black solid;text-align: left;font-size: 20px; width: 100px" ></td>
<!--						<td style=" border: 1px black solid;text-align: left;font-size: 20px; width: 200px" ></td>
-->
						<td style=" border: 1px black solid;text-align: left;font-size: 20px; width: 130px" ></td>
						<td style=" border: 1px black solid;text-align: left;font-size: 20px; width: 170px" ><?= $currency." ".number_format($total) ?></td>
				</tr>
			
				<tr>
						<td colspan = "1" style=" border: 0px black solid;text-align: left;font-size: 20px; width: 30px" ></td>
						<td colspan = "1" style=" border: 0px black solid;text-align: left;font-size: 20px; width: 300px" ></td>
						<td  style=" border: 0px black solid;text-align: left;font-size: 20px; width: 70px" ></td>
						<td style=" border: 0px black solid;text-align: left;font-size: 20px; width: 100px" ></td>
						<td style=" border: 0px black solid;text-align: left;font-size: 20px; width: 200px" ></td>
						<td style=" border: 1px black solid;text-align: left;font-size: 20px; width: 130px" >Subtotal</td>
						<td style=" border: 1px black solid;text-align: left;font-size: 20px; width: 170px" ><?= $currency." ".number_format($total) ?></td>
				</tr>
				<tr>
						<td rowspan = "2"colspan = "4" style=" border: 1px black solid;text-align: left;font-size: 20px; width: 30px" >Says #<?= $curr->toEnglish($total) ?>#</td>
						<td style=" border: 0px black solid;text-align: left;font-size: 20px; width: 200px" ></td>
						<td style=" border: 1px black solid;text-align: left;font-size: 20px; width: 130px" >Diskon</td>
						<td style=" border: 1px black solid;text-align: left;font-size: 20px; width: 170px" ></td>
				</tr>
				<tr>
						<td style=" border: 0px black solid;text-align: left;font-size: 20px; width: 200px" ></td>
						<td style=" border: 1px black solid;text-align: left;font-size: 20px; width: 130px" >Biaya Lain</td>
						<td style=" border: 1px black solid;text-align: left;font-size: 20px; width: 170px" ></td>
				</tr>
<!--
				<tr>
						<td colspan = "4" style="border-bottom:0px solid black ; border-right:1px solid black ;border-left:1px solid black ; text-align: left;font-size: 20px; width: 30px" >Note : Pembayaran harap ditransfer ke rekening</td>
						<td style=" border: 0px black solid;text-align: left;font-size: 20px; width: 200px" ></td>
						<td style=" border: 1px black solid;text-align: left;font-size: 20px; width: 130px" >PPn</td>
						<td style=" border: 1px black solid;text-align: left;font-size: 20px; width: 170px" ></td>
				</tr>
				<tr>
						<td colspan = "4" style="border-bottom:0px solid black ; border-right:1px solid black ;border-left:1px solid black ; text-align: left;font-size: 20px; width: 30px" >Rek 1</td>
						<td style=" border: 0px black solid;text-align: left;font-size: 20px; width: 200px" ></td>
						<td style=" border: 1px black solid;text-align: left;font-size: 20px; width: 130px" >Total</td>
						<td style=" border: 1px black solid;text-align: left;font-size: 20px; width: 170px" ></td>
				</tr>
				<tr>
						<td colspan = "4" style="border-bottom:1px solid black ; border-right:1px solid black ;border-left:1px solid black ; text-align: left;font-size: 20px; width: 30px" >Rek 2</td>
						<td style=" border: 0px black solid;text-align: left;font-size: 20px; width: 200px" ></td>
						<td style=" border: 0px black solid;text-align: left;font-size: 20px; width: 130px" ></td>
						<td style=" border: 0px black solid;text-align: left;font-size: 20px; width: 170px" ></td>
				</tr>
-->
			</table>
	</div>		

	<div class="row" style="width: 1000px">
			<table id="table1" style="width: 1000px;float: left;margin-right: 10px;margin-bottom: 150px">
				<tr>
						<td colspan = "2" style="text-align: Center;font-size: 20px; width: 330px" >Disetujui Oleh,</td>
						<td colspan = "3" style="text-align: Center;font-size: 20px; width: 370px" ></td>
						<td style="text-align: left;font-size: 20px; width: 130px" ></td>
						<td style="text-align: left;font-size: 20px; width: 170px" ></td>
				</tr>
			</table>
	</div>		
	<div class="row" style="width: 1000px">
			<table id="table1" style="width: 1000px;float: left;margin-right: 10px">
				<tr>
						<td colspan = "2" style="text-align: Center;font-size: 20px; width: 330px" >______________</td>
						<td colspan = "3" style="text-align: Center;font-size: 20px; width: 370px" ></td>
						<td style="text-align: left;font-size: 20px; width: 130px" ></td>
						<td style="text-align: left;font-size: 20px; width: 170px" ></td>
				</tr>
				<tr>
						<td colspan = "2" style="text-align: Center;font-size: 20px; width: 330px" >Sales Manager</td>
						<td colspan = "3" style="text-align: Center;font-size: 20px; width: 370px" ></td>
						<td style="text-align: left;font-size: 20px; width: 130px" ></td>
						<td style="text-align: left;font-size: 20px; width: 170px" ></td>
				</tr>
			</table>
	</div>		

</body>
</html>
<?php
}
?>
