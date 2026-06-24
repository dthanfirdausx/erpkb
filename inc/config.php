<?php
ini_set( "display_errors", true );
date_default_timezone_set('Asia/Jakarta');
//$host = "/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock";
$host = "127.0.0.1";
$port = 3307;
$db_username = "dthan";
$db_password = "realmadrid";
$db_name = "erpkb";
define("user_ws","selimpml1");
define("pass_ws","Pemalang123");

define("namaPT", "PT ABC");
define("shortTittle", "PT ABC");
define("appTittle", "IT Inventory Kawasan Berikat");
define("URL_API", "https://apis-gw.beacukai.go.id");
//main directory
define( "DIR_MAIN", "erpkb/");

define("base_url_akunting", "http://27.123.2.130:8080/");

//admin directory
define( "DIR_ADMIN", "erpkb");


define('DB_CHARACSET', 'utf8');

define ('SITE_ROOT', $_SERVER['DOCUMENT_ROOT']."/".DIR_MAIN);

//languange


require_once ('Database.php');
require_once ('Dtable.php');
require_once ('My_pagination.php');

require_once ('encrypt.php');

$enc = new Encrypt();
$dec = new Decrypt();
$db=new Database($host,$port,$db_username,$db_password,$db_name);
$pg=New My_pagination($db);
$datatable=New Dtable($host,$port,$db_username,$db_password,$db_name);
require_once ('function.php');
register_module_action_audit($host,$port,$db_username,$db_password,$db_name);
function handleException( $exception ) {
  echo  $exception->getMessage();
}

set_exception_handler( 'handleException' );
$username = "";
if (isset($_SESSION['username'])) {
	$username = $_SESSION['username'];
}
$language  = getLangUser($username);
$_SESSION['language'] = $language;


require_once ("lang/$language.php");
// print_r($lang);
// die();
?>
