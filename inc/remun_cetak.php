<?php
$kehormatan="";
$tahun=$_GET['tahun'];
$q=$db->fetch_custom("select bulan,tahun from tb_kehormatan s where s.tahun='$tahun' group by s.bulan order by s.bulan asc");
foreach ($q as $k) {
  $qj=$db->fetch_custom("select s.ket from tb_kehormatan s where s.tahun='$tahun' and bulan='$k->bulan' group by s.ket");
  foreach ($qj as $ks) {
  	$ket=$ks->ket=='normal'?'':$ks->ket;
  	$kehormatan.='<h2 style="text-align:center">Data Kehormatan '.get_bulan($k->bulan).' '.$ket.'</h2>
	            <table border="1"  style="font-size: 12px">
	             <thead>
	               <tr>
	                 <th>No</th>
	                 <th>NIP</th>
	                 <th>Nama</th>
	                 <th style="text-align:center">Pokok</th>
	                 <th style="text-align:center">Pph</th>
	                 <th style="text-align:center">Bersih</th>
	               </tr>
	             </thead>
	             <tbody>';
	 $qq=$db->fetch_custom("select s.tunjangan, s.nip,p.nama,s.pokok,s.pph,s.bersih from tb_kehormatan s join tb_pegawai p on p.nip=s.nip
	 	where s.tahun='$tahun' and bulan='$k->bulan' and s.ket='$ks->ket' ");
	 $no=1;
	 $tot_tunjangan=0;
	 $tot_pph=0;
	 $tot_bersih=0;
	 foreach ($qq as $kk) {
	 	$kehormatan.='<tr>
	 	             <td>'.$no.'</td>
	 	             <td>\''.$kk->nip.'</td>
	 	             <td>'.$kk->nama.'</td>
	 	             <td style="text-align:right">'.$kk->tunjangan.'</td>
	 	             <td style="text-align:right">'.$kk->pph.'</td>
	 	             <td style="text-align:right">'.$kk->bersih.'</td>	 	
	 	           </tr>';
	 	           $tot_tunjangan=$tot_tunjangan+$kk->tunjangan;
	 	           $tot_pph=$tot_pph+$kk->pph;
	 	           $tot_bersih=$tot_bersih+$kk->bersih;
	 	$no++;
	 }
	 $kehormatan.='<tr>
	                <td colspan="3">Jumlah</td>
	                <td>'.$tot_tunjangan.'</td> <td>'.$tot_pph.'</td> <td>'.$tot_bersih.'</td>
	              </tr>';
	 $kehormatan.='</tbody></table>';
  }
	
}

//echo "$kehormatan";
?>