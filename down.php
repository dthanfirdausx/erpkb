<?php
class Download
{
	function add_dir($name) {
    $name = str_replace("\\", "/", $name);
    $fr = "\x50\x4b\x03\x04";
    $fr .= "\x0a\x00";
    $fr .= "\x00\x00";
    $fr .= "\x00\x00";
    $fr .= "\x00\x00\x00\x00";
    $fr .= pack("V",0);
    $fr .= pack("V",0);
    $fr .= pack("V",0);
    $fr .= pack("v", strlen($name) ); 
    $fr .= pack("v", 0 );
    $fr .= $name;
    $fr .= pack("V",$crc); 
    $fr .= pack("V",$c_len); 
    $fr .= pack("V",$unc_len);
    $this -> datasec[] = $fr;
    $new_offset = strlen(implode("", $this->datasec));
    $cdrec = "\x50\x4b\x01\x02";
    $cdrec .="\x00\x00"; 
    $cdrec .="\x0a\x00"; 
    $cdrec .="\x00\x00"; 
    $cdrec .="\x00\x00"; 
    $cdrec .="\x00\x00\x00\x00"; 
    $cdrec .= pack("V",0); 
    $cdrec .= pack("V",0); 
    $cdrec .= pack("V",0); 
    $cdrec .= pack("v", strlen($name) );
    $cdrec .= pack("v", 0 );
    $cdrec .= pack("v", 0 ); 
    $cdrec .= pack("v", 0 ); 
    $cdrec .= pack("v", 0 ); 
    $ext = "\x00\x00\x10\x00";
    $ext = "\xff\xff\xff\xff";
    $cdrec .= pack("V", 16 );
    $cdrec .= pack("V", $this -> old_offset );
    $this -> old_offset = $new_offset;
    $cdrec .= $name;
    $this -> ctrl_dir[] = $cdrec;
    }
    function add_file($data, $name)
    {
    $name = str_replace("\\", "/", $name);
    $fr = "\x50\x4b\x03\x04";
    $fr .= "\x14\x00";
    $fr .= "\x00\x00";
    $fr .= "\x08\x00"; 
    $fr .= "\x00\x00\x00\x00";
    $unc_len = strlen($data);
    $crc = crc32($data);
    $zdata = gzcompress($data);
    $zdata = substr( substr($zdata, 0, strlen($zdata) - 4), 2);
    $c_len = strlen($zdata);
    $fr .= pack("V",$crc);
    $fr .= pack("V",$c_len);
    $fr .= pack("V",$unc_len);
    $fr .= pack("v", strlen($name) );
    $fr .= pack("v", 0 );
    $fr .= $name;
    $fr .= $zdata;
    $fr .= pack("V",$crc);
    $fr .= pack("V",$c_len); 
    $fr .= pack("V",$unc_len); 
    $this -> datasec[] = $fr;
    $new_offset = strlen(implode("", $this->datasec));
    $cdrec = "\x50\x4b\x01\x02";
    $cdrec .="\x00\x00";
    $cdrec .="\x14\x00"; 
    $cdrec .="\x00\x00";
    $cdrec .="\x08\x00";
    $cdrec .="\x00\x00\x00\x00"; 
    $cdrec .= pack("V",$crc); 
    $cdrec .= pack("V",$c_len); 
    $cdrec .= pack("V",$unc_len); 
    $cdrec .= pack("v", strlen($name) );
    $cdrec .= pack("v", 0 ); 
    $cdrec .= pack("v", 0 ); 
    $cdrec .= pack("v", 0 ); 
    $cdrec .= pack("v", 0 ); 
    $cdrec .= pack("V", 32 ); 
    $cdrec .= pack("V", $this -> old_offset );
    $this -> old_offset = $new_offset;
    $cdrec .= $name;
    $this -> ctrl_dir[] = $cdrec;
    }
    function file() { 
    $data = implode("", $this -> datasec);
    $ctrldir = implode("", $this -> ctrl_dir);
    return
    $data.
    $ctrldir.
    $this -> eof_ctrl_dir.
    pack("v", sizeof($this -> ctrl_dir)).
    pack("v", sizeof($this -> ctrl_dir)). 
    pack("V", strlen($ctrldir)). 
    pack("V", strlen($data)). 
    "\x00\x00";
  }
  function get_files_from_folder($directory, $put_into) {
    $sp=DIRECTORY_SEPARATOR;
    if ($handle = opendir($directory)) {
    while (false !== ($file = readdir($handle))) {
        if (is_file($directory.$file)) {
        $fileContents = file_get_contents($directory.$file);
        $this->add_file($fileContents, $put_into.$file);
                      }
        elseif ($file != '.' && $file != '..' && is_dir($directory.$file))
        {
          $this->add_dir($put_into.$file.$sp);
          $this->get_files_from_folder($directory.$file.$sp, $put_into.$file.$sp);
        }
                          }
                      }
    closedir($handle);
  }
  function downloadfolder($fd) {
    $this->get_files_from_folder($fd,'');
    header("Content-Disposition: attachment; filename=" .$this->cs(basename($fd)).".zip");   
    header("Content-Type: application/download");
    //header("Content-Length: " . strlen($this -> file()));
    flush();
    echo $this -> file(); 
    exit();
  }
  function cs($t) {
    return str_replace(" ","_",$t);
  }
}
$down = new Download();
echo $down->downloadfolder('C:\xampp\htdocs\php-backend-generator\admina\modul\menu_management\\');
?>

