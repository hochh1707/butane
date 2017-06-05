<?php
set_time_limit(0);
//ini_set('zlib.output_compression', 'Off');
ini_set('output_buffering ', '0');
ini_set('implicit_flush', '1');
ob_implicit_flush(true);

define('URL','http://'.$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF']);

require_once ('includes/LIB_http.php');
require_once ('includes/LIB_mysql.php');
require_once ('includes/LIB_parse.php');
require_once ('includes/LIB_resolve_addresses.php');
require_once ('includes/LIB_other.php');
include_once ('includes/classScraper.php');

$_CONFIG =  array(
	'database' => 'butane050817',
	'hostname' => 'mysql.renthousemogul.com',
	'username' => 'butane77mysql',
	'password' => 'napkin88water');

$db = new mysqli('mysql.renthousemogul.com', 'butane77mysql', 'napkin88water', 'butane050817');
