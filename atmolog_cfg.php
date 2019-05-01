<?php

//Password which also must be set in ATMOCOM configuration
$my_passkey = "mypasskey";

//Directory where SQLIte databases are stored
$dataFolder = "wxdb/";

//Directory where current records data file is stored
$wxdata_dir = "";

//Enable debug log by changing 0 to 1
$debug = 0;

//Disable database storage by setting to 0
//If disabled, only current records file will be generated
$database_store_enabled = 1;

//Change 'true' to 'false' for storing all data using US imperial units
define('use_metric', 'true'); 

//To enable all error reporting, set to -1 instead of 0
error_reporting(0);

?>