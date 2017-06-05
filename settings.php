<?php
include_once('includes/config.php');

session_start();

if (!isset($_SESSION) or ($_SESSION['user'] == "")){
	echo("Not logged in. <a href='index.php'>Click here to login.</a>");
	exit();
}

$arrSettings = getSettings();
$settingsForm = generateSettings($arrSettings);

if($_POST['blerg']){
  updateSettings($_POST);
	echo("<meta http-equiv='refresh' content='0'>");
}
?>

<html>
<br><br>
<a href = "index.php">Home</a>
<br><br>
<form method='post' action='settings.php'>

<?php
echo("$settingsForm");

function updateSettings($newSettings){
	global $db;
	foreach ($newSettings as $key => $value) {
		$query = "UPDATE settings SET value = '{$value}' WHERE name = '{$key}'";
		$db->query($query);
	}
}

function getSettings(){
  global $db;

  //Get the settings that are in the database
  $query = "SELECT * FROM settings";
  $result = $db->query($query);
  $arrResult = $result->fetch_all(MYSQLI_ASSOC);

  // Put them in an array format we can use
  foreach ($arrResult as $key => $value) {
    $arrSettings[$value['name']] = $value['value'];
  }

return $arrSettings;
}

function generateSettings(array $arrSettings){
  if(isset($arrSettings['timezone'])){
      $settingsForm = "<br><br>Time zone:";
      $settingsForm .= "<select name = 'timezone'>";
			$settingsForm .= "<option value = '{$arrSettings['timezone']}'>{$arrSettings['timezone']}</option>";
      $settingsForm .= "<option value = 'America/Chicago'>America/Chicago</option>";
			$settingsForm .= "<option value = 'America/Los_Angeles'>America/Los_Angeles</option>";
			$settingsForm .= "<option value = 'America/New_York'>America/New_York</option>";
      $settingsForm .= "</select>";
    }

    if(isset($arrSettings['use_downtimes'])){
			if($arrSettings['use_downtimes'] == 1){
				$useDowntimes = "checked='checked'";
			}else {
				$dontUseDowntimes = "checked='checked'";
			}
      $settingsForm .= "<br><br>";
      $settingsForm .= "Use downtimes";
      $settingsForm .= "<input type='radio' name='use_downtimes' value=1 {$useDowntimes}>";
      $settingsForm .= "<br>";
      $settingsForm .= "Don't use downtimes";
      $settingsForm .= "<input type='radio' name='use_downtimes' value=0 {$dontUseDowntimes}>";
    }

    if(isset($arrSettings['tax_year'])){
			$settingsForm .= "<br><br>Tax year:";
      $settingsForm .= "<select name = 'tax_year'>";
			for ($i=date('Y'); $i >= date('Y')-4; $i--) {
				if($i == $arrSettings['tax_year']){
					$settingsForm .= "<option value = {$i} selected>{$i}</option>";
				}else {
					$settingsForm .= "<option value = {$i}>{$i}</option>";
				}
			}
      $settingsForm .= "</select>";
    }

    if(isset($arrSettings['pause_engine'])){
      if($arrSettings['pause_engine'] == 1){
        $inputPause = "<input type='radio' name='pause_engine' value=1 checked='checked'>";
        $inputUnPause = "<input type='radio' name='pause_engine' value=0>";
      }else {
        $inputPause = "<input type='radio' name='pause_engine' value=1>";
        $inputUnPause = "<input type='radio' name='pause_engine' value=0 checked='checked'>";
      }
      $settingsForm .= "<br><br>";
      $settingsForm .= "Pause engine:";
      $settingsForm .= $inputPause;
      $settingsForm .= "<br>";
      $settingsForm .= "Unpause engine";
      $settingsForm .= $inputUnPause;
    }

    if(isset($arrSettings['delay'])){
      $settingsForm .= "<br><br>Delay in seconds: <input type='text' name='delay' value='{$arrSettings['delay']}'>";
    }

    if(isset($arrSettings['limit_time'])){
      $settingsForm .= "<br><br>Limit time seconds: <input type='text' name='limit_time' value='{$arrSettings['limit_time']}'>";
    }

    $settingsForm .= "<input type='hidden' name='blerg' value='999'>";
    $settingsForm .= "<br><br><input type='submit' action='settings.php'>";
    $settingsForm .= "</form></html>";
    return $settingsForm;
}
?>
