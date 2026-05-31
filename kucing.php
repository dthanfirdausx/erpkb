<?php
  
 function kucing($tikus, $keju){
  if($tikus>$keju){
    return 0;
  }else{
  	//return 1;
   return $tikus + kucing(($tikus* 3 +1),$keju);
 }
}

echo kucing(1,2018);

?>