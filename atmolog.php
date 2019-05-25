 <?php

//All configuration is conveniently done in acpwu2_config.php
//No need to modify anything in this file
require 'atmolog_cfg.php';

// set the default timezone if not set at php.ini
if(!ini_get('date.timezone') )
{
    date_default_timezone_set('UTC');
}

$dbFile = $dataFolder . "wx". date("Ym"). ".db"; 

$wxdata_file = "_data.txt";

$dateFormat = "Y-m-d";
$timeFormat = "H:i:s";

$firmware_rev = "0x0";
$psk = "";
$testmode = "n";
if (array_key_exists('passkey', $_REQUEST)) $psk=$_REQUEST['passkey'];
if (array_key_exists('rev', $_REQUEST)) $firmware_rev=$_REQUEST['rev'];
if (array_key_exists('test', $_REQUEST)) $testmode=$_REQUEST['test'];

$data=$_SERVER['QUERY_STRING'];

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

if($database_store_enabled) 
{
	//If needed create daily SQLite DB archive
	create_db();
	insert_db($data_array);
}

update_wxdata_file($data_array);

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
		$data_array[Params::STATIONID] = strtoupper($param[1]);
	}
	else if(!strcasecmp($param[0], "baromin"))
	{
		$data_array[Params::BARO] = conv_inHg_hPa($param[1]);
    }
	else if(!strcasecmp($param[0], "absbaromin"))
	{
		$data_array[Params::ABSBARO] = conv_inHg_hPa($param[1]);
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
        $data_array[Params::WGUSTDIR] = floatval($param[1]);
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
	else if(!strcasecmp($param[0], "weeklyrainin"))
	{
		$data_array[Params::PRECIPWEEK] = conv_in_mm($param[1]);
	}
	else if(!strcasecmp($param[0], "monthlyrainin"))
	{
		$data_array[Params::PRECIPMON] = conv_in_mm($param[1]);
	}
	else if(!strcasecmp($param[0], "yearlyrainin"))
	{
		$data_array[Params::PRECIPYEAR] = conv_in_mm($param[1]);
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
	return round($res, 2);
}

//convert inches to mm
function conv_in_mm($v)
{
	if(!use_metric) return floatval($v);
	
	$res = 0.0;
	if(settype($v, "float")) {
		$res = $v *  25.4;
	}
	return round($res, 2);
}

//convert mph to kt
function conv_mph_kt($v)
{
	if(!use_metric) return floatval($v);
	
	$res = 0.0;
	if(settype($v, "float")) {
		$res = $v *  0.869;
	}
	return round($res, 2);
}


//convert inHg to hPa
function conv_inHg_hPa($v)
{
	if(!use_metric) return floatval($v);
	
	$res = 0.0;
	if(settype($v, "float")) {
		$res = $v * 33.8638816;
	}
	return round($res, 2);
}

function wind_dir_avg($warr)
{
  if (count($warr) == 0) return 0;
	
	$sinsum = 0; $cossum = 0;
 
  foreach ($warr as $val) {
    $sinsum += sin(deg2rad($val));
    $cossum += cos(deg2rad($val));
  }

	return ((rad2deg(atan2($sinsum, $cossum)) + 360) % 360);
}

function update_wxdata_file($rec)
{
	global $wxdata_dir, $wxdata_file, $dateFormat, $timeFormat, $firmware_rev;

	//set units
	$unitStr = "C:hPa:mm";
	$minChgTemp = 0.2;
	$minChgBaro = 0.2;
	if(!use_metric) {
		$unitStr = "F:inHg:in";
		$minChgTemp = 0.36;
		$minChgBaro = 0.05;
	}
	
	//Load file and parse. If it doesn't exist then create new
	//Files are all lowercase
	$station_wxfile = strtolower($rec[Params::STATIONID]) . $wxdata_file;
	$sep = ":";
	$crlf = PHP_EOL;
	$epoch = strtotime(date($dateFormat . ' ' . $timeFormat));

	if(!file_exists($station_wxfile))
	{
		if(!file_exists($wxdata_dir) && strlen($wxdata_dir) > 0) mkdir($wxdata_dir, 0755, true);
		
		//Generate file contents. Min, max and all times are set to current values
		$fstr = '### AUTO-GENERATED, DO NOT MODIFY! ###' . $crlf;
		$fstr = 'stamp:0' . $sep . $epoch . $sep . date('Y:m:d') . $sep . $rec[Params::STATIONID];
		$fstr .= $sep . $unitStr . $sep . $firmware_rev . $crlf;

		//$fstr .= "wind:" . $rec[Params::WINDDIR] . $sep . $rec[Params::WINDVEL] . $sep . $rec[Params::WINDDIR] . $sep . $rec[Params::WGUSTVEL] . $sep . $rec[Params::WINDDIR] . $crlf;
		$fstr .= "wind:" . $rec[Params::WINDDIR] . $sep . $rec[Params::WINDVEL] . $sep . $rec[Params::WINDDIR] . $sep . $rec[Params::WGUSTVEL] . $sep . $rec[Params::WINDDIR] . $sep . $rec[Params::WGUSTVEL] . $sep . $epoch . $crlf;
		$fstr .= "otemp:" . $rec[Params::TEMP] . $sep . $rec[Params::TEMP] . $sep . $rec[Params::TEMP] . $sep . $epoch . $sep . $epoch . $sep . "0" . $crlf;
		$fstr .= "itemp:" . $rec[Params::INTEMP] . $sep . $rec[Params::INTEMP] . $sep . $rec[Params::INTEMP] . $crlf;
		$fstr .= "dewpt:" . $rec[Params::DEWPT] . $sep . $rec[Params::DEWPT] . $sep . $rec[Params::DEWPT] . $crlf;
		$fstr .= "rhum:" . $rec[Params::RHUM] . $sep . $rec[Params::RHUM] . $sep . $rec[Params::RHUM] . $crlf;
		$fstr .= "ihum:" . $rec[Params::INRHUM] . $sep . $rec[Params::INRHUM] . $sep . $rec[Params::INRHUM] . $crlf;
		$fstr .= "baro:" . $rec[Params::BARO] . $sep . $rec[Params::BARO] . $sep . $rec[Params::BARO] . $sep . $epoch . $sep . $epoch . $sep . "0" . $crlf;
		$fstr .= "uvsol:" . $rec[Params::UVIDX] . $sep . $rec[Params::SOLAR] . $crlf;
		$fstr .= "precip:" . $rec[Params::PRECIP] . $sep . $rec[Params::PRECIPDAY] . $sep . "0" . $sep . $rec[Params::PRECIPWEEK] . $sep . $rec[Params::PRECIPMON] . $sep . $rec[Params::PRECIPYEAR] . $crlf;
		
		file_put_contents($station_wxfile, $fstr);
	}
	else {
		//Load and compare saved data with current and update if necessary
		$fstr = file_get_contents($station_wxfile);
		if(strlen($fstr) === 0) die();

		$farr = explode($crlf, $fstr);
		$fstr = '### AUTO-GENERATED, DO NOT MODIFY! ###' . $crlf;

		$pdate = array();
		$day_chg = false;
		foreach($farr as $ln)
		{
			if(isValidData($ln))
			{
				$data = explode(":", $ln);

				if(strcmp($data[0], "stamp") === 0)
				{
					$pdate[0] = $data[3]; //Year
					$pdate[1] = $data[4];	//Month
					$pdate[2] = $data[5];	//Day

					$day_chg = ( $pdate[2] != date('d') );

					$seq = $data[1]+1;
					$fstr = 'stamp' . $sep . $seq . $sep . $epoch . $sep . date('Y:m:d') . $sep . $rec[Params::STATIONID];
					$fstr .= $sep . $unitStr . $sep . $firmware_rev . $crlf;
				}
				else if(strcmp($data[0], "wind") === 0) //Wind dir, gust and velocities
				{
					//Calculate average based on last average + 2 readings. Not that accurate but will do
					$data[5] = wind_dir_avg(array($data[5], $data[1], $rec[Params::WINDDIR]));

					//Set new wind parameters
					$data[1] = $rec[Params::WINDDIR];
					$data[2] = $rec[Params::WINDVEL];
					$data[3] = $rec[Params::WINDDIR]; //Wind gust direction not supplied by most PWS, set equal to main direction
					$data[4] = $rec[Params::WGUSTVEL];

					//Added max wind gust and time of event (20190519)
					//If old format with 5 fields then just add fields 6 & 7
					if(count($data) > 5)
					{
						if($data[6] <= $rec[Params::WGUSTVEL] || $day_chg)
						{
							$data[6] = $rec[Params::WGUSTVEL];
							$data[7] = $epoch;
						}
					}
					else {
						$data[6] = $rec[Params::WGUSTVEL];
						$data[7] = $epoch;
					}

					$fstr .= "wind:" . $data[1] . $sep . $data[2] . $sep . $data[3] . $sep . $data[4] . $sep . $data[5] . $sep . $data[6] . $sep . $data[7] . $crlf;
				}				
				else if(strcmp($data[0], "otemp") === 0) //Outdoor temps 
				{
					//Find trend since last write, ignore changes < 0.2 units of temp
					$temp_trend = $rec[Params::TEMP] - $data[1];
					if(abs($temp_trend) < $minChgTemp) $temp_trend=0;

					//Set new temp, adjust max/min and max time (field 4)/min time (field 5)
					//Reset on change of day
					if($rec[Params::TEMP] > $data[2] || $day_chg) {
						$data[2] = $rec[Params::TEMP];
						$data[4] = $epoch;
					}
					if($rec[Params::TEMP] < $data[3] || $day_chg) {
						$data[3] = $rec[Params::TEMP];
						$data[5] = $epoch;
					}
					$fstr .= "otemp:" . $rec[Params::TEMP] . $sep . $data[2] . $sep . $data[3] . $sep . $data[4] . $sep . $data[5] . $sep . round($temp_trend,2) . $crlf;
				}
				else if(strcmp($data[0], "itemp") === 0) //Indoor temps 
				{
					//Set new indoor temp, adjust max/min
					if($rec[Params::INTEMP] > $data[2]) $data[2] = $rec[Params::INTEMP];
					if($rec[Params::INTEMP] < $data[3]) $data[3] = $rec[Params::INTEMP];
					$fstr .= "itemp:" . $rec[Params::INTEMP] . $sep . $data[2] . $sep . $data[3] . $crlf;
				}
				else if(strcmp($data[0], "dewpt") === 0) //Dewpoint
				{
					//Set new dewpoint, adjust max/min
					if($rec[Params::DEWPT] > $data[2]) $data[2] = $rec[Params::DEWPT];
					if($rec[Params::DEWPT] < $data[3]) $data[3] = $rec[Params::DEWPT];
					$fstr .= "dewpt:" . $rec[Params::DEWPT] . $sep . $data[2] . $sep . $data[3] . $crlf;
				}
				else if(strcmp($data[0], "rhum") === 0) //Relative humidity
				{
					//Set new humidity, adjust max/min
					if($rec[Params::RHUM] > $data[2]) $data[2] = $rec[Params::RHUM];
					if($rec[Params::RHUM] < $data[3]) $data[3] = $rec[Params::RHUM];
					$fstr .= "rhum:" . $rec[Params::RHUM] . $sep . $data[2] . $sep . $data[3] . $crlf;
				}
				else if(strcmp($data[0], "ihum") === 0) //Indoor relative humidity
				{
					//Set new indoor humidity, adjust max/min
					if($rec[Params::INRHUM] > $data[2]) $data[2] = $rec[Params::INRHUM];
					if($rec[Params::INRHUM] < $data[3]) $data[3] = $rec[Params::INRHUM];
					$fstr .= "ihum:" . $rec[Params::INRHUM] . $sep . $data[2] . $sep . $data[3] . $crlf;
				}
				else if(strcmp($data[0], "baro") === 0) //Barometric pressure
				{
					//Find trend since last write, ignore changes < 0.2 units of pressure
					$baro_trend = $rec[Params::BARO] - $data[1];
					if(abs($baro_trend) < $minChgBaro) $baro_trend=0;

					//Set new barometer pressure, adjust max/min and max time (field 4)/min time (field 5)
					if($rec[Params::BARO] > $data[2] || $day_chg) {
						$data[2] = $rec[Params::BARO];
						$data[4] = $epoch;
					}
					if($rec[Params::BARO] < $data[3] || $day_chg) {
						$data[3] = $rec[Params::BARO];
						$data[5] = $epoch;
					}
					$fstr .= "baro:" . $rec[Params::BARO] . $sep . $data[2] . $sep . $data[3] . $sep . $data[4] . $sep . $data[5] . $sep . round($baro_trend,4) . $crlf;
				}
				else if(strcmp($data[0], "uvsol") === 0) //UV index and solar data
				{
					//Set new wind parameters
					$data[1] = $rec[Params::UVIDX];
					$data[2] = $rec[Params::SOLAR];
					$fstr .= "uvsol:" . $data[1] . $sep . $data[2] . $crlf;
				}
				else if(strcmp($data[0], "precip") === 0) //Precipitation
				{
					//if day has changed since last write, move field #2 (today) to #3 (yesterday)
					if($day_chg)
					{
						//Rain yesterday
						$data[3] = $data[2];
					}

					$data[1] = $rec[Params::PRECIP];     //rate
					$data[2] = $rec[Params::PRECIPDAY];  //rain today
					$data[4] = $rec[Params::PRECIPWEEK]; //rain this week
					$data[5] = $rec[Params::PRECIPMON];  //rain this month
					$data[6] = $rec[Params::PRECIPYEAR]; //rain this year

					$fstr .= "precip:" . $data[1] . $sep . $data[2] . $sep . $data[3] . $sep . $data[4] . $sep . $data[5] . $sep . $data[6] . $crlf;
				} // precip
			}
		} //foreach
		file_put_contents($station_wxfile, $fstr);
	}
}

function isValidData($str)
{
	return (strlen($str) > 0 && $str[0] !== '#');
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
		
		$stmt->bindValue(1, strtoupper($rec[Params::STATIONID], PDO::PARAM_STR));
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
		$stmt->bindValue(25, $rec[Params::PRECIPWEEK], PDO::PARAM_STR);		
		$stmt->bindValue(26, $rec[Params::PRECIPMON], PDO::PARAM_STR);		
		$stmt->bindValue(27, $rec[Params::PRECIPYEAR], PDO::PARAM_STR);		
		$stmt->bindValue(28, $rec[Params::ABSBARO], PDO::PARAM_STR);
		$stmt->bindValue(29, $firmware_rev, PDO::PARAM_STR);				
		$stmt->execute();
		$db->commit();
		$stmt=null;
		$db=null;

	} catch(PDOException $e) {
		//echo $e->getMessage();
		die();
  }
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
            $db->exec( "CREATE TABLE IF NOT EXISTS wxdata (ID integer primary key, STATIONID text, DATE text, TIME text, UTC real, TEMP real, DEWPT real, RHUM real, BARO real, WINDDIR real, WINDVEL real, WGUSTDIR real, WGUSTVEL real, PRECIP real, PRECIPDAY real, UVIDX real, SOLAR real, INTEMP real, INRHUM real, SOILTEMP real, SOILMOIST real, LEAFWET real, WEATHER text, CLOUDS text, VISNM real, PRECIPWEEK real, PRECIPMON real, PRECIPYEAR real, ABSBARO real, FIRMWARE_REV text)" );
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
	
	mkdir($dataFolder, 0755, true);
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
		$stmt = null;
		$db = null;

	} catch(PDOException $e) {
		echo "<br />***   test_sql(): INSERT failed -> " . $e->getMessage();
		die();
	}		
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
		$stmt = null;
		$db = null;
	} catch(PDOException $e) {
		echo "<br />***   test_sql(): SELECT failed -> " . $e->getMessage();
		die();
	}
	echo " ...OK<br />";
	
	echo "4. Deleting TEMP database file...";
	$del_fail = false;
	if(!unlink(realpath($temp_dbfile)))
	{
		echo "...FAILED!<br />";
		$del_fail=true;
	} else echo "...OK<br />";

	if(!$del_fail) echo '<br /><font color="#009900"><strong>SUCCESS! All tests passed, you are good to go!</strong></font>';
	else
	{ 
		echo '<br /><font color="#FF9900"><strong>NON-CRITICAL FAILURE! Delete temp file failed.</strong></font><br />';
		echo '<font color="#000000">Things will still work just fine but you have to delete <strong><i>' . $temp_dbfile . '</i></strong> manually.</font>';
	}
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
	const PRECIPWEEK = 22;
	const PRECIPMON = 23;
	const PRECIPYEAR = 24;
	const ABSBARO = 25;
  const FIRMWARE_REV = 26;
}
?>
