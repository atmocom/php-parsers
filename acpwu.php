<?php

// Define your own passkey here. Max length is 9 characters. 
// This passkey must also be provided in ATMOCOM config, see user manual for details
$my_passkey = "mypasskey";

$dataFolder = "wxdb/";

// uncomment line below for monthly archives. Recommended option 
$dbFile = $dataFolder . "wx". date("Ym"). ".db"; 

// uncomment line below for daily archives 
//$dbFile = $dataFolder . "wx". date("Ymd"). ".db"; 

// uncomment line below for annual archives. Warning - generates big files which may hamper performance
//$dbFile = $dataFolder . "wx". date("Y"). ".db"; 

$dateFormat = "Y-m-d";
$timeFormat = "H:i:s";

$firmware_rev = "0x0";
$psk = "";
$testmode = "n";
if (array_key_exists('passkey', $_REQUEST)) $psk=$_REQUEST['passkey'];
if (array_key_exists('rev', $_REQUEST)) $firmware_rev=$_REQUEST['rev'];
if (array_key_exists('test', $_REQUEST)) $testmode=$_REQUEST['test'];

$data=$_SERVER['QUERY_STRING'];

//On line below change 'true' to 'false' for storing data in US imperial units
define('use_metric', 'true'); 

//Set to 1 to keep logs of the data received from ATMOCOM. Only for debugging
$debug = 0; 

//echo $data;

$data_array = array_fill(0,32,"");

if(isset($psk) && isset($testmode) && $testmode === "y") {
	test_sql($psk, $my_passkey);
	die();
}

if($psk != $my_passkey)
{
  die();
}

if($debug == 1) writelog($data);

if(!isset($data)) die();

//verify this is WU data protocol or exit
if (strpos($data, 'updateweatherstation.php') === false) die();

if(!isset($firmware_rev)) $firmware_rev='0x0';

//If needed create daily SQLite DB archive
create_db();

//Split query string into two where 2nd part should hold wx data
$qstr = explode("?", $data);

if(count($qstr) < 2) die;

//Now split 2nd part further into individual measurements
$wxinfo = explode("&", $qstr[1]);

//Loop over resulting array and pick out relevant values
for($i=0; $i<count($wxinfo); $i++)
{
	$wxdata = explode("=", $wxinfo[$i]);
	setValue($wxdata);
}

insert_db($data_array);

///////////////////////////////////////////
// Functions below
///////////////////////////////////////////
function writelog($param)
{
	global $dateFormat, $timeFormat;
	$fp = fopen('atmocom_' . date("Ymd") . '.txt', 'a');
	fwrite($fp, (date($dateFormat) . " " . date($timeFormat) . " " .$param . "\r\n") );
	fclose($fp);
}

function setValue($param)
{
	global $data_array;
	if(!isset($param[0]) || !isset($param[1])) return; //nothing to do...
	
	if(!strcasecmp($param[0], "ID"))
	{
		$data_array[Params::STATIONID] = $param[1];
	}
	else if(!strcasecmp($param[0], "baromin"))
	{
		$data_array[Params::BARO] = conv_inHg_hPa($param[1]);
	}
	else if(!strcasecmp($param[0], "tempf"))
	{
		$data_array[Params::TEMP] = conv_F_C($param[1]);
	}
	else if(!strcasecmp($param[0], "dewptf"))
	{
		$data_array[Params::DEWPT] = conv_F_C($param[1]);
	}
	else if(!strcasecmp($param[0], "indoortempf"))
	{
		$data_array[Params::INTEMP] = conv_F_C($param[1]);
	}
	else if(!strcasecmp($param[0], "humidity"))
	{
		$data_array[Params::RHUM] = floatval($param[1]);
	}
	else if(!strcasecmp($param[0], "indoorhumidity"))
	{
		$data_array[Params::INRHUM] = floatval($param[1]);
	}
	else if(!strcasecmp($param[0], "winddir"))
	{
		$data_array[Params::WINDDIR] = floatval($param[1]);
	}
	else if(!strcasecmp($param[0], "windgustdir"))
	{
		$data_array[Params::WGUSTDIR] = floatval($param[1]);
	}
	else if(!strcasecmp($param[0], "windspeedmph"))
	{
		$data_array[Params::WINDVEL] = conv_mph_kt($param[1]);
	}
	else if(!strcasecmp($param[0], "windgustmph"))
	{
		$data_array[Params::WGUSTVEL] = conv_mph_kt($param[1]);
	}	
	else if(!strcasecmp($param[0], "rainin"))
	{
		$data_array[Params::PRECIP] = conv_in_mm($param[1]);
	}		
	else if(!strcasecmp($param[0], "dailyrainin"))
	{
		$data_array[Params::PRECIPDAY] = conv_in_mm($param[1]);
	}
	else if(!strcasecmp($param[0], "UV"))
	{
		$data_array[Params::UVIDX] = floatval($param[1]);
	}		
	else if(!strcasecmp($param[0], "solarradiation"))
	{
		$data_array[Params::SOLAR] = floatval($param[1]);
	}		
	else if(!strcasecmp($param[0], "indoortempf"))
	{
		$data_array[Params::INTEMP] = conv_F_C($param[1]);
	}
	else if(!strcasecmp($param[0], "indoorhumidity"))
	{
		$data_array[Params::INRHUM] = floatval($param[1]);
	}			
	else if(!strcasecmp($param[0], "soiltempf"))
	{
		$data_array[Params::SOILTEMP] = conv_F_C($param[1]);
	}
	else if(!strcasecmp($param[0], "soilmoisture"))
	{
		$data_array[Params::SOILMOIST] = floatval($param[1]);
	}
	else if(!strcasecmp($param[0], "leafwetness"))
	{
		$data_array[Params::LEAFWET] = floatval($param[1]);
	}
	else if(!strcasecmp($param[0], "weather"))
	{
		$data_array[Params::WEATHER] = floatval($param[1]);
	}
	else if(!strcasecmp($param[0], "clouds"))
	{
		$data_array[Params::CLOUDS] = floatval($param[1]);
	}
	else if(!strcasecmp($param[0], "visibility"))
	{
		$data_array[Params::VISNM] = floatval($param[1]);
	}	
}

//convert temp F to temp C
function conv_F_C($v)
{
	if(!use_metric) return floatval($v);
	
	$res = 0.0;
	if(settype($v, "float")) {
		$res = ($v - 32) * 0.5556;
	}
	return $res;
}

//convert inches to mm
function conv_in_mm($v)
{
	if(!use_metric) return floatval($v);
	
	$res = 0.0;
	if(settype($v, "float")) {
		$res = $v *  25.4;
	}
	return $res;
}

//convert mph to kt
function conv_mph_kt($v)
{
	if(!use_metric) return floatval($v);
	
	$res = 0.0;
	if(settype($v, "float")) {
		$res = $v *  0.869;
	}
	return $res;
}


//convert inHg to hPa
function conv_inHg_hPa($v)
{
	if(!use_metric) return floatval($v);
	
	$res = 0.0;
	if(settype($v, "float")) {
		$res = $v * 33.86;
	}
	return $res;
}

function insert_db($rec)
{
	//
	global $dbFile, $dateFormat, $timeFormat, $firmware_rev;
	try {
		$db = new PDO('sqlite:' . $dbFile);
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$stmt = $db->prepare('INSERT INTO wxdata VALUES (NULL,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
		// Start transaction
		$db->beginTransaction();
		
		$stmt->bindValue(1, $rec[Params::STATIONID], PDO::PARAM_STR);
		$stmt->bindValue(2, date($dateFormat), PDO::PARAM_STR);
		$stmt->bindValue(3, date($timeFormat), PDO::PARAM_STR);
		$stmt->bindValue(4, (date('Z')/60), PDO::PARAM_STR);
		$stmt->bindValue(5, $rec[Params::TEMP], PDO::PARAM_STR);
		$stmt->bindValue(6, $rec[Params::DEWPT], PDO::PARAM_STR);
		$stmt->bindValue(7, $rec[Params::RHUM], PDO::PARAM_STR);
		$stmt->bindValue(8, $rec[Params::BARO], PDO::PARAM_STR);
		$stmt->bindValue(9, $rec[Params::WINDDIR], PDO::PARAM_STR);
		$stmt->bindValue(10, $rec[Params::WINDVEL], PDO::PARAM_STR);
		$stmt->bindValue(11, $rec[Params::WGUSTDIR], PDO::PARAM_STR);
		$stmt->bindValue(12, $rec[Params::WGUSTVEL], PDO::PARAM_STR);
		
		$stmt->bindValue(13, $rec[Params::PRECIP], PDO::PARAM_STR);
		$stmt->bindValue(14, $rec[Params::PRECIPDAY], PDO::PARAM_STR);
		$stmt->bindValue(15, $rec[Params::UVIDX], PDO::PARAM_STR);
		$stmt->bindValue(16, $rec[Params::SOLAR], PDO::PARAM_STR);
		
		$stmt->bindValue(17, $rec[Params::INTEMP], PDO::PARAM_STR);
		$stmt->bindValue(18, $rec[Params::INRHUM], PDO::PARAM_STR);
		$stmt->bindValue(19, $rec[Params::SOILTEMP], PDO::PARAM_STR);		
		$stmt->bindValue(20, $rec[Params::SOILMOIST], PDO::PARAM_STR);		
		$stmt->bindValue(21, $rec[Params::LEAFWET], PDO::PARAM_STR);		
		$stmt->bindValue(22, $rec[Params::WEATHER], PDO::PARAM_STR);		
		$stmt->bindValue(23, $rec[Params::CLOUDS], PDO::PARAM_STR);		
		$stmt->bindValue(24, $rec[Params::VISNM], PDO::PARAM_STR);		
		$stmt->bindValue(25, $rec[Params::RES1], PDO::PARAM_STR);		
		$stmt->bindValue(26, $rec[Params::RES2], PDO::PARAM_STR);		
		$stmt->bindValue(27, $rec[Params::RES3], PDO::PARAM_STR);		
		$stmt->bindValue(28, $rec[Params::RES4], PDO::PARAM_STR);
		$stmt->bindValue(29, $firmware_rev, PDO::PARAM_STR);				
		$stmt->execute();
		$db->commit();
	} catch(PDOException $e) {
		//echo $e->getMessage();
		die();
  }
	$db=null;
}

//Create SQLite database
function create_db()
{
	global $dbFile;

	make_dbdir();
	if(file_exists($dbFile)) return;
	else {
		try {
			$db = new PDO('sqlite:' . $dbFile);
			$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$db->exec( "CREATE TABLE IF NOT EXISTS wxdata (ID integer primary key, STATIONID text, DATE text, TIME text, UTC real, TEMP real, DEWPT real, RHUM real, BARO real, WINDDIR real, WINDVEL real, WGUSTDIR real, WGUSTVEL real, PRECIP real, PRECIPDAY real, UVIDX real, SOLAR real, INTEMP real, INRHUM real, SOILTEMP real, SOILMOIST real, LEAFWET real, WEATHER text, CLOUDS text, VISNM real, RES1 real, RES2 real, RES3 real, RES4 real, RES5 text)" );
			$db = null;
		}
		catch(PDOException $e) {
			//echo $e->getMessage();
			die();
		}
	}
}

//Create DB directory if it doesn't exist, otherwise no action
function make_dbdir()
{
	global $dataFolder;
	if(file_exists($dataFolder)) return;
	
	mkdir($dataFolder, 0777, true);
}

//Simple function tests
function test_sql($psk1, $psk2)
{
	echo '<font color="#808080">ATMOCOM parser function test -- ' . date(DATE_RFC2822) . '</font><br /><br />';
	if($psk1 != $psk2)
	{
		echo "FAILED: Passkey in URL does not match configured passkey!";
		die();
	}

	echo "0. Passkey match, proceeding with tests<br />";
	
	global $dataFolder, $dateFormat, $timeFormat;

	$temp_dbfile = $dataFolder . "temp.db" ;

	make_dbdir();
	
	//Create a temp SQLIte database
	echo "1. Creating TEMP SQLite database " . $temp_dbfile . "...";
	try {
		$db = new PDO('sqlite:' . $temp_dbfile);
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$db->exec( "CREATE TABLE IF NOT EXISTS temptable (ID integer primary key, DATA1 text, DATA2 text)" );
		$db = null;
	} catch(PDOException $e) {
		echo "<br />***   test_sql(): CREATE TABLE failed -> " . $e->getMessage(); 
		die();
	}
	echo "OK<br />";
	
	//Insert some test data
	echo "2. Inserting data into TEMP database...";
	try {
		$db = new PDO('sqlite:' . $temp_dbfile);
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$stmt = $db->prepare('INSERT INTO temptable VALUES (NULL,?,?)');
		// Start transaction
		$db->beginTransaction();
		
		$stmt->bindValue(1, date($dateFormat), PDO::PARAM_STR);
		$stmt->bindValue(2, date($timeFormat), PDO::PARAM_STR);
		$stmt->execute();
		$db->commit();
	} catch(PDOException $e) {
		echo "<br />***   test_sql(): INSERT failed -> " . $e->getMessage();
		die();
	}		
	$db = null;
	echo "OK<br />";
	
	//Select all inserted data 
	echo "3. Fetching inserted data from TEMP database: ";
	try {
		$db = new PDO('sqlite:' . $temp_dbfile);
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$stmt = $db->prepare('SELECT * FROM temptable');
		$stmt->execute();

		$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
		print_r($res);
	} catch(PDOException $e) {
		echo "<br />***   test_sql(): SELECT failed -> " . $e->getMessage();
		die();
	}
	$db=null;
	echo " ...OK<br />";
	
	echo "4. Deleting TEMP database file...";
	if(!unlink($temp_dbfile))
	{
		echo "FAILED!<br />";
		die();
	}
	echo "OK<br />";

	echo '<br /><font color="#009900"><strong>SUCCESS! All tests passed, you are good to go!</strong></font>';
}

//Define parameters supported by DB table
abstract class Params 
{
	const STATIONID = 1;
	const TEMP = 2;
	const DEWPT = 3;
	const RHUM = 4;
	const BARO = 5;
	const WINDDIR = 6;
	const WINDVEL = 7;
	const WGUSTDIR = 8;
	const WGUSTVEL = 9;
	const PRECIP = 10;
	const PRECIPDAY = 11;
	const UVIDX = 12;
	const SOLAR = 13;
	const INTEMP = 14;
	const INRHUM = 15;
	const SOILTEMP = 16;
	const SOILMOIST = 17;
	const LEAFWET = 18;	
	const WEATHER = 19;
	const CLOUDS = 20;
	const VISNM = 21;
	const RES1 = 22;
	const RES2 = 23;
	const RES3 = 24;
	const RES4 = 25;
	const RES5 = 26;
}
?>
