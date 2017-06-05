<?php
session_start();
?>

<html>
<head>
<meta http-equiv="refresh" content="10; URL=dashboard.php">
</head>

<?php
include_once('includes/config.php');
date_default_timezone_set('America/Chicago');

if (!isset($_SESSION) or ($_SESSION['user'] == "")){
	echo("Not logged in. <a href='index.php'>Click here to login.</a>");
	exit();
}

$arrStats = array();

$statTitle = "Total McLennan Co. properties";
$statQuery = "SELECT count(pid) FROM props WHERE sid = '0'";
$statResult = "NULL";
$arrStats[] = array('Title' => $statTitle, 'Query' => $statQuery, 'Result' => $statResult);

$statTitle = "McLennan Co. properties with owner";
$statQuery = "SELECT count(pid) FROM props WHERE sid = '0' AND owner_address != ''";
$statResult = "NULL";
$arrStats[] = array('Title' => $statTitle, 'Query' => $statQuery, 'Result' => $statResult);

$statTitle = "McLennan Co. properties with owner (residential)";
$statQuery = "SELECT count(pid) FROM props WHERE sid = '0' AND owner_address != '' AND improvement_type = 'Residential'";
$statResult = "NULL";
$arrStats[] = array('Title' => $statTitle, 'Query' => $statQuery, 'Result' => $statResult);

$statTitle = "McLennan Co. properties with owner (commercial)";
$statQuery = "SELECT count(pid) FROM props WHERE sid = '0' AND owner_address != '' AND improvement_type = 'Commercial'";
$statResult = "NULL";
$arrStats[] = array('Title' => $statTitle, 'Query' => $statQuery, 'Result' => $statResult);

$statTitle = "McLennan Co. properties with owner (no improvements)";
$statQuery = "SELECT count(pid) FROM props WHERE sid = '0' AND owner_address != '' AND improvement_type = 'None'";
$statResult = "NULL";
$arrStats[] = array('Title' => $statTitle, 'Query' => $statQuery, 'Result' => $statResult);

$statTitle = "McLennan Co. properties with owner (other)";
$statQuery = "SELECT count(pid) FROM props WHERE sid = '0' AND owner_address != '' AND improvement_type != 'Commercial'
							AND improvement_type != 'Residential' AND improvement_type != 'None' AND improvement_type IS NOT NULL";
$statResult = "NULL";
$arrStats[] = array('Title' => $statTitle, 'Query' => $statQuery, 'Result' => $statResult);

$statTitle = "McLennan Co. properties with owner (improvement_type NULL)";
$statQuery = "SELECT count(pid) FROM props WHERE sid = '0' AND owner_address != '' AND improvement_type IS NULL";
$statResult = "NULL";
$arrStats[] = array('Title' => $statTitle, 'Query' => $statQuery, 'Result' => $statResult);

$statTitle = "McLennan Co. properties not containing 767 or 766 (residential)";
$statQuery = "SELECT count(pid) FROM props WHERE sid = '0' AND owner_address != '' AND owner_address NOT LIKE '%767%' AND owner_address NOT LIKE '%766%' AND improvement_type = 'Residential'";
$statResult = "NULL";
$arrStats[] = array('Title' => $statTitle, 'Query' => $statQuery, 'Result' => $statResult);

$statTitle = "McLennan Co. properties with owner not in TX (residential)";
$statQuery = "SELECT count(pid) FROM props WHERE sid = '0' AND owner_address != '' AND owner_address NOT LIKE '%TX%' AND improvement_type = 'Residential'";
$statResult = "NULL";
$arrStats[] = array('Title' => $statTitle, 'Query' => $statQuery, 'Result' => $statResult);

foreach ($arrStats as $key => $value) {
	$result = $db->query($value['Query']);
	$arrStats[$key]['Result'] = $result->fetch_row()[0];
}
echo(generateTable('Stats',$arrStats) . "<br>");

// Get the latest 10 log entries
$result = $db->query("SELECT * FROM logs ORDER BY time DESC LIMIT 30");
$arrTable = $result->fetch_all(MYSQLI_ASSOC);
echo(generateTable('Scraper log',$arrTable) . "<br>");

// Get the latest 10 statuses
$result = $db->query("SELECT * FROM status ORDER BY start_time DESC LIMIT 20");
$arrTable = $result->fetch_all(MYSQLI_ASSOC);
echo(generateTable('Scraper status',$arrTable) . "<br>");

// Get the latest 10 downtime entries
$result = $db->query("SELECT * FROM downtime ORDER BY date DESC, start_time_hour DESC LIMIT 10");
$arrTable = $result->fetch_all(MYSQLI_ASSOC);
echo(generateTable('Scheduled downtimes',$arrTable) . "<br>");

// Get settings
$result = $db->query("SELECT * FROM settings ORDER BY name DESC");
$arrTable = $result->fetch_all(MYSQLI_ASSOC);
echo(generateTable('Scraper settings',$arrTable));
echo("<br><a href = 'settings.php'>Settings</a><br><br>");

// Get the latest 20 properties updated
$result = $db->query("SELECT * FROM props ORDER BY updated_time DESC LIMIT 30");
$arrTable = $result->fetch_all(MYSQLI_ASSOC);
echo(generateTable('Latest updated properties',$arrTable) . "<br>");

function generateTable($title = 'Title', $arrResult){
	$table = "<table style='border:1px solid black;'><tr><td>$title</td></tr><tr>";
	foreach ($arrResult[0] as $colHeader => $value) {
		if($colHeader == 'Query'){
			continue;
		}else {
			$table .= "<td style='border:1px solid black; text-align:center;'>$colHeader</td>";
		}
	}
	$table .= "</tr>";
	foreach ($arrResult as $row => $rowValues) {
		$table .= "<tr>";
		foreach ($arrResult[$row] as $key => $value) {
			$value = parseField($title,$key,$value);
			if($value == "999skip999"){
				continue;
			}else{
				$table .= "<td style='padding-left:20px; text-align:center;'>$value</td>";
			}
		}
		$table .= "</tr>";
	}
	$table .= "<table>";
return $table;
}

function parseField($title,$key,$value){
	if($key == 'start_time' and $value !== NULL){
		$value = date('d-M g:i a',$value);
	}elseif($key == 'end_time' and $value !== NULL){
		$value = date('d-M g:i a',$value);
	}elseif($key == 'time' and $value !== NULL){
		$value = date('d-M g:i a',$value);
	}elseif($key == 'updated_time' and $value !== NULL){
		$value = date('d-M g:i a',$value);
	}elseif($key == 'start_time_hour' and $value !== NULL){
		$hour = explode('.',$value)[0];
		$minutes = round(explode('.',$value)[1] * 60/100);
		if(strlen($hour) == 1) $hour = "0" . $hour;
		if(strlen($minutes) == 1) $minutes = "0" . $minutes;
		$value = $hour . ":" . $minutes;
	}elseif($key == 'Query'){
		$value = '999skip999';
	}
return $value;
}
?>
