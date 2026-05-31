<?php


function numberFormat($number){
	$data = sprintf("%01.2f", $number);
return $data ;
}


function checkIp($ip){
  if (0 <= $ip && $ip <= 4) {
      return true;
  }
  return false;
}


function en($value) {
	$objenc = new Encrypt;
	$enc = $objenc->enc($value);
	return $enc;
}

function de($value) {
	$objdec = new Decrypt;
	$dec = $objdec->dec($value);
	return $dec;
}

class Encrypt{

	function enc ($data){

	$pj = strlen($data);
	$enc="";

	for ($i=0;$i<=$pj-1;$i++){
		$sample = substr($data,$i,1);
		if ($sample=="a") $enc .= "v";
		else if ($sample=="b") $enc .= "w";
		else if ($sample=="c") $enc .= "x";
		else if ($sample=="d") $enc .= "y";
		else if ($sample=="e") $enc .= "z";
		else if ($sample=="f") $enc .= "A";
		else if ($sample=="g") $enc .= "B";
		else if ($sample=="h") $enc .= "C";
		else if ($sample=="i") $enc .= "D";
		else if ($sample=="j") $enc .= "E";
		else if ($sample=="k") $enc .= "F";
		else if ($sample=="l") $enc .= "G";
		else if ($sample=="m") $enc .= "H";
		else if ($sample=="n") $enc .= "I";
		else if ($sample=="o") $enc .= "J";
		else if ($sample=="p") $enc .= "K";
		else if ($sample=="q") $enc .= "L";
		else if ($sample=="r") $enc .= "M";
		else if ($sample=="s") $enc .= "N";
		else if ($sample=="t") $enc .= "O";
		else if ($sample=="u") $enc .= "P";
		else if ($sample=="v") $enc .= "Q";
		else if ($sample=="w") $enc .= "R";
		else if ($sample=="x") $enc .= "S";
		else if ($sample=="y") $enc .= "T";
		else if ($sample=="z") $enc .= "U";
		else if ($sample=="A") $enc .= "V";
		else if ($sample=="B") $enc .= "W";
		else if ($sample=="C") $enc .= "X";
		else if ($sample=="D") $enc .= "Y";
		else if ($sample=="E") $enc .= "Z";
		else if ($sample=="F") $enc .= "1";
		else if ($sample=="G") $enc .= "2";
		else if ($sample=="H") $enc .= "3";
		else if ($sample=="I") $enc .= "4";
		else if ($sample=="J") $enc .= "5";
		else if ($sample=="K") $enc .= "6";
		else if ($sample=="L") $enc .= "7";
		else if ($sample=="M") $enc .= "8";
		else if ($sample=="N") $enc .= "9";
		else if ($sample=="O") $enc .= "0";
		else if ($sample=="P") $enc .= "a";
		else if ($sample=="Q") $enc .= "b";
		else if ($sample=="R") $enc .= "c";
		else if ($sample=="S") $enc .= "d";
		else if ($sample=="T") $enc .= "e";
		else if ($sample=="U") $enc .= "f";
		else if ($sample=="V") $enc .= "g";
		else if ($sample=="W") $enc .= "h";
		else if ($sample=="X") $enc .= "i";
		else if ($sample=="Y") $enc .= "j";
		else if ($sample=="Z") $enc .= "k";
		else if ($sample=="1") $enc .= "l";
		else if ($sample=="2") $enc .= "m";
		else if ($sample=="3") $enc .= "n";
		else if ($sample=="4") $enc .= "o";
		else if ($sample=="5") $enc .= "p";
		else if ($sample=="6") $enc .= "q";
		else if ($sample=="7") $enc .= "r";
		else if ($sample=="8") $enc .= "s";
		else if ($sample=="9") $enc .= "t";
		else if ($sample=="0") $enc .= "u";
		else if ($sample=="-") $enc .= "]";
		else if ($sample=="?") $enc .= "[";
		else if ($sample=="+") $enc .= "_";
		else if ($sample==" ") $enc .= "!";
		else if ($sample==".") $enc .= ".";
		else $enc .= $sample ;
	}
	$today      = md5(date("F j, Y, g:i a"));
	$salt_left  = substr($today,0,5);
	$salt_right = substr($today,-5);
	$salt_midle = substr($today,6,5);

	$pj_enc = strlen($enc);
	if ($pj_enc <=4){
		$salt_left .= "$".$pj_enc;
	
	}

	$pfx = substr($enc,0,5);
	$sfx = substr($enc,5);

	$encrypt = $salt_left.$pfx.$salt_midle.$sfx.$salt_right;

	$data = strrev($encrypt);
	return $data;
	}
}

class Decrypt{

	function dec ($data){
	 
		$enc = substr($data,5);
		$enc = strrev($enc);
		$enc = substr($enc,5);
		$dec = "";

		$size_c = substr($enc,1,1);
		$size_s = substr($enc,0,1);
		
		if ($size_c <=4 && $size_s=="$"){
			$pfx = substr($enc,2,$size_c);
			$sfx = "";
		}else {
			$pfx = substr($enc,0,5);
			$sfx = substr($enc,10);
		}

		$mdl = substr($enc,6,5);
		
		$enc = $pfx.$sfx;
		$pjenc = strlen($enc);

		for ($i=0;$i<=$pjenc-1;$i++){
		$sample = substr($enc,$i,1);
		if ($sample=="v") $dec .= "a";
		else if ($sample=="w") $dec .= "b";
		else if ($sample=="x") $dec .= "c";
		else if ($sample=="y") $dec .= "d";
		else if ($sample=="z") $dec .= "e";
		else if ($sample=="A") $dec .= "f";
		else if ($sample=="B") $dec .= "g";
		else if ($sample=="C") $dec .= "h";
		else if ($sample=="D") $dec .= "i";
		else if ($sample=="E") $dec .= "j";
		else if ($sample=="F") $dec .= "k";
		else if ($sample=="G") $dec .= "l";
		else if ($sample=="H") $dec .= "m";
		else if ($sample=="I") $dec .= "n";
		else if ($sample=="J") $dec .= "o";
		else if ($sample=="K") $dec .= "p";
		else if ($sample=="L") $dec .= "q";
		else if ($sample=="M") $dec .= "r";
		else if ($sample=="N") $dec .= "s";
		else if ($sample=="O") $dec .= "t";
		else if ($sample=="P") $dec .= "u";
		else if ($sample=="Q") $dec .= "v";
		else if ($sample=="R") $dec .= "w";
		else if ($sample=="S") $dec .= "x";
		else if ($sample=="T") $dec .= "y";
		else if ($sample=="U") $dec .= "z";
		else if ($sample=="V") $dec .= "A";
		else if ($sample=="W") $dec .= "B";
		else if ($sample=="X") $dec .= "C";
		else if ($sample=="Y") $dec .= "D";
		else if ($sample=="Z") $dec .= "E";
		else if ($sample=="1") $dec .= "F";
		else if ($sample=="2") $dec .= "G";
		else if ($sample=="3") $dec .= "H";
		else if ($sample=="4") $dec .= "I";
		else if ($sample=="5") $dec .= "J";
		else if ($sample=="6") $dec .= "K";
		else if ($sample=="7") $dec .= "L";
		else if ($sample=="8") $dec .= "M";
		else if ($sample=="9") $dec .= "N";
		else if ($sample=="0") $dec .= "O";
		else if ($sample=="a") $dec .= "P";
		else if ($sample=="b") $dec .= "Q";
		else if ($sample=="c") $dec .= "R";
		else if ($sample=="d") $dec .= "S";
		else if ($sample=="e") $dec .= "T";
		else if ($sample=="f") $dec .= "U";
		else if ($sample=="g") $dec .= "V";
		else if ($sample=="h") $dec .= "W";
		else if ($sample=="i") $dec .= "X";
		else if ($sample=="j") $dec .= "Y";
		else if ($sample=="k") $dec .= "Z";
		else if ($sample=="l") $dec .= "1";
		else if ($sample=="m") $dec .= "2";
		else if ($sample=="n") $dec .= "3";
		else if ($sample=="o") $dec .= "4";
		else if ($sample=="p") $dec .= "5";
		else if ($sample=="q") $dec .= "6";
		else if ($sample=="r") $dec .= "7";
		else if ($sample=="s") $dec .= "8";
		else if ($sample=="t") $dec .= "9";
		else if ($sample=="u") $dec .= "0";
		else if ($sample=="]") $dec .= "-";
		else if ($sample=="[") $dec .= "?";
		else if ($sample=="_") $dec .= "+";
		else if ($sample=="!") $dec .= " ";
		else if ($sample==".") $dec .= ".";
		else $dec .= $sample ;
	}
	$data = $dec;
	return $data;
	}
}
?>