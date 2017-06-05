<?php

class scraper {
	var $serial;
	var $siteToSearch;
	var $siteBase;
	var $siteCid;
	var $siteName;

	function __construct() {
		global $db;
		$this->startTime = time();
		$this->defaultSettings();
		$this->getSettings();

		date_default_timezone_set($this->arrSettings['timezone']);

		if($this->arrSettings['pause_engine'] == 1){
			$this->log("Engine paused");
			exit();
		}

		if($this->checkIfAlreadyRunning() == FALSE) exit();

		if($this->checkIfDowntime() == TRUE) exit();

		// Record when we started running the engine
		$this->serial = md5(microtime().mt_rand(0,10000000));
		$query = "INSERT INTO status SET stage = 'scraping', serial = '{$this->serial}', start_time = '{$this->startTime}'";
		$db->query($query);
	}

	function defaultSettings(){
		global $db;

		// List of default settings
		$arrDefaultSettings['delay'] = 3;
		$arrDefaultSettings['timezone'] = 'America/Chicago';
		$arrDefaultSettings['pause_engine'] = 0;
		$arrDefaultSettings['limit_time'] = 90;
		$arrDefaultSettings['use_downtimes'] = 1;
		$arrDefaultSettings['tax_year'] = 2017;

		// Get the settings that are in the database
		$query = "SELECT * FROM settings";
		$result = $db->query($query);
		$arrDbSettings = $result->fetch_all(MYSQLI_ASSOC);

		// If any default settings are not in the database, then put them there
		foreach($arrDefaultSettings as $defaultSettingName => $defaultSettingValue){
			$existsInDb = 0;
			foreach ($arrDbSettings as $dbSettingKey => $dbSettingRow) {
				if($dbSettingRow['name'] == $defaultSettingName){
					$existsInDb = 1;
				}
			}
			if($existsInDb == 0){
				$query = "INSERT INTO settings SET name = '{$defaultSettingName}', value = '{$defaultSettingValue}'";
				$db->query($query);
			}else{
				$existsInDb = 0;
			}
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
			$this->arrSettings[$value['name']] = $value['value'];
		}
	}

	function checkIfAlreadyRunning(){
		global $db;

		// Check to make sure an engine is not running at the same time as we are starting this one
		$query = "SELECT * FROM status WHERE end_time IS NULL ORDER BY start_time DESC";
		$result = $db->query($query);
		if(!$result){
			// If the database query failed, then quit
			$this->log("No go-- unable to retrieve latest start time");
			return FALSE;
		}

		if($result->num_rows < 1){
			// If there is no record where end time is null, then we are ok to start
			return TRUE;
		}

		$latestStartTime = $result->fetch_assoc()['start_time'];

		if($latestStartTime == ''){
			$this->log("No go-- start time blank or not present");
			$this->log("Start time blank '{$latestStartTime}'");
			return FALSE;
		}

		if(!is_numeric($latestStartTime)){
			$this->log("No go-- start time not numeric");
			return FALSE;
		}

		// Check to see if the start time we found is too recent to allow starting the engine
		$elapsedTime = $this->startTime - $latestStartTime;
		if($elapsedTime < $this->arrSettings[limit_time]){
			$this->log("No go-- another one was started less than {$this->arrSettings[limit_time]} sec ago");
			return FALSE;
		}

	// If it didn't fail the previous tests it means that the start time was long ago enough
	// Start engine
	return TRUE;
	}

	function checkIfDowntime(){
		global $db;

		// If downtimes are disabled, then return false and start the engine
		if($this->arrSettings['use_downtimes'] == 0){
			return FALSE;
		}

		// See if any downtime intervals are in the database for today
		$todayDate = date("Y-m-d");
		$query = "SELECT * FROM downtime WHERE date = '{$todayDate}'";
		$result = $db->query($query);

		if(!$result){
			$this->log("Problem with downtime query. Cannot start engine.");
			return TRUE;
		}

		// If there are no down times for today in the database, then add them
		// This will generate some possible downtimes to use
		if($result->num_rows < 1){
			$downDuration[] = rand(0,1) . "." . rand(1,9) . rand(1,9);
			$downDuration[] = rand(0,2) . "." . rand(1,9) . rand(1,9);
			$downDuration[] = rand(0,3) . "." . rand(1,9) . rand(1,9);
			$downDuration[] = rand(5,8) . "." . rand(1,9) . rand(1,9);

			// Randomize how many downtimes we will have today and when they will be
			$downIntervals = array();
			for($i=0; $i<rand(2,3); $i++){
				$downIntervals[] = array('date' => $todayDate, 'start_time_hour' => rand(4,23) . "." . rand(1,9) . rand(1,9), 'duration_hours' => $downDuration[$i]);
			}
			// Always one long downtime at night
			$downIntervals[] = array('date' => $todayDate, 'start_time_hour' => rand(20,23) . "." . rand(1,9) . rand(1,9), 'duration_hours' => $downDuration[3]);

			// Add downtimes to the database
			foreach ($downIntervals as $key => $value) {
				$query = "INSERT INTO downtime SET date = '{$value['date']}', start_time_hour = '{$value['start_time_hour']}', duration_hours = '{$value['duration_hours']}'";
				$db->query($query);
			}

		// If we return true, the engine will not run until it gets hit by the chron job again a minute later
		$this->log("Set downtimes for today");
		return TRUE;
		}

	// Check if we are within any of the downtime intervals in the database
	$query = "SELECT * FROM downtime ORDER BY date DESC LIMIT 8";
	$result = $db->query($query);
	$downTimes = $result->fetch_all(MYSQLI_ASSOC);
	foreach ($downTimes as $key => $value) {
		$startDowntime = strtotime($value['date']) + $value['start_time_hour'] * 3600;
		$endDowntime = strtotime($value['date']) + $value['start_time_hour'] * 3600 + $value['duration_hours'] * 3600;
		if(time() > $startDowntime && time() < $endDowntime){
			// If we are within any of the downtimes, don't run the engine
			$this->log("Downtime");
			return TRUE;
		}
	}
	// If we got this far, it's ok to run the engine
	return FALSE;
	}

	function delaySeconds(){
		// Because we don't want to sleep for the same number of seconds every time
		$delayMultiplier = rand(1,10)/10 + rand(0,9)/100;
		$startRange = $delayMultiplier * $this->arrSettings['delay'];
		$endRange = (1.5 + $delayMultiplier) * $this->arrSettings['delay'];
		$delaySeconds = rand($startRange,$endRange) + rand(1,100)/100;
		$this->log("Delay " . $delaySeconds . " seconds");
		return $delaySeconds;
	}

	function sleepOrTimeout($delaySeconds = 0){
		global $db;
		$elapsedSeconds = time() + $delaySeconds - $this->startTime;
		if($elapsedSeconds < $this->arrSettings[limit_time]) {
			usleep($delaySeconds * 1000000);
			// False means there is still time left to run the next search before we run out of time
			return FALSE;
		}
	// True means we are out of time if we do the sleep
	// Update the database before we quit
	$query = "UPDATE status SET end_time = '" . time() . "' WHERE serial = '{$this->serial}'";
	$db->query($query);
	return TRUE;
	}

	function siteToSearch($siteID) {
		$this->siteToSearch = $siteID;

		if ($siteID == 0) {
			$this->siteBase='http://propaccess.trueautomation.com/clientDB/';
			$this->siteCid=20;
			$this->siteName='McLennan';
		}
		if ($siteID == 1) {
			$this->siteBase='http://propaccess.bellcad.org/clientdb/';
			$this->siteCid=1;
			$this->siteName='Bell';
		}
		if ($siteID == 2) {
			$this->siteBase='http://bcad.org/clientdb/';
			$this->siteCid=1;
			$this->siteName='Bexar';
		}
		if ($siteID == 3) {
			$this->siteBase='http://www.traviscad.org/';
			$this->siteName='Travis';
		}
	}

	function log($text) {
		global $db;
		$text=addslashes($text);
		$db->query("INSERT INTO logs SET time = UNIX_TIMESTAMP(), logtext='$text'");
	}

	function search($searchString = '') {
		global $db;

		if($searchString == ''){
			$this->log("Search steing empty");
			exit();
		}

		// Search McLennan CAD site by owner's name field
		if ($this->siteToSearch == 0) {
			$url = 'https://propaccess.trueautomation.com/clientdb/propertysearch.aspx?cid=20';
			$searchurl='https://propaccess.trueautomation.com/clientdb/?cid=20';

			// First, let's load the search form.
			$searchpage=http_get($url,'');

			// Get values for the hidden fields
			$VIEWSTATE=preg_return('@__VIEWSTATE\" value=\"(.+)\"@i',$searchpage['FILE']);
			$EVENTVALIDATION=preg_return('@__EVENTVALIDATION\" value=\"(.+)\"@i',$searchpage['FILE']);
			$VIEWSTATEGENERATOR = preg_return('@__VIEWSTATEGENERATOR\" value=\"(.+)\"@i',$searchpage['FILE']);

			//  Now prepare to post our search.
			$options=array('__EVENTTARGET' => '',
				'__EVENTARGUMENT' => '',
				'__VIEWSTATE' => $VIEWSTATE,
				'__VIEWSTATEGENERATOR' => $VIEWSTATEGENERATOR,
				'__EVENTVALIDATION' => $EVENTVALIDATION,
				'propertySearchOptions:searchType' => 'Owner Name',
				'propertySearchOptions:ownerName'=>$searchString[0],
				'propertySearchOptions:streetNumber'=>'',
				'propertySearchOptions:streetName'=>'',
				'propertySearchOptions:propertyid'=>'',
				'propertySearchOptions:geoid'=>'',
				'propertySearchOptions:dba'=>'',
				'propertySearchOptions:abstract'=>'',
				'propertySearchOptions:subdivision'=>'',
				'propertySearchOptions:mobileHome'=>'',
				'propertySearchOptions:condo'=>'',
				'propertySearchOptions:taxyear'=>$this->arrSettings['tax_year'],
				'propertySearchOptions:propertyType'=>'R',
				'propertySearchOptions:orderResultsBy'=>'Owner Name',
				'propertySearchOptions:recordsPerPage'=>'250',
				'propertySearchOptions:search'=>'Search');

			$searchResults = http($searchurl,$url,'POST',$options,EXCL_HEAD);

			// Get the last page number of results
			if(preg_match_all('@page=.+?>(?P<pages>[0-9]+?)</a@', $searchResults['FILE'] ,$matches)) {
				$top_page_num=preg_return('@</a> of <a href.+?>([0-9]+)</a></td>@',$searchResults['FILE']);
				if (!$top_page_num) {
					$matches[1]=array_reverse($matches[1]);
					$top_page_num=$matches[1][1];
				}

				if ($top_page_num>1) {
					$searchResults['PAGES']=range(2,$top_page_num);
				}else{
					$searchResults['PAGES']=1;
				}
			}
		}

			// Search Travis CAD site
			if ($this->siteToSearch == 1) {
			#######Travis Search goes here
			$ref='http://www.traviscad.org/cad_search.php?mode=name&kind=real';
			$target='http://www.traviscad.org/t_list.php';
			// First, let's load the search form.
			$searchpage=http_get($ref,'');
			#display_r($searchpage['FILE']);

				//  Now prepare to post our search.
				$options=array('i.dsn' => 'prelim',
				'i.detail' => 'travisdetail.php?theKey=',
				'i.dbType' => '1',
				'i.themeFile' => 'travistheme.php',
				'i.selfRef' => 't_list.php',
				'i.where' => " where (travis_prop.prop_type_cd like 'R%' and travis_prop.appr_address_suppress_flag = 'F' and travis_prop.appr_confidential_flag = 'F')",
				'k.appr_owner_name' => $search_string,
				'Submit' => 'Find',
				);

				$searchResults=http($target,$ref,'POST',$options,EXCL_HEAD);
				$next=preg_return('@(t_list\.php\?startRow=.+?)"@is',$searchResults['FILE']);

				if ($next) {
					$searchResults['NEXT']=$next;
				}
			}

		$db->query("UPDATE searches SET last=unix_timestamp() WHERE sid={$this->siteToSearch} AND search={$search_string}");

	return $searchResults;
	}

	function getPropertyIDs($page) {
		global $db;
		$count=0;

		if ($this->siteToSearch==0) {
			$regEx='@<span prop_id=\"(?P<ids>[0-9]+?)\">@';
		}else if ($this->siteToSearch==1) {
			$regEx='@theKey=([0-9]{4,7})@i';
		}
		if (preg_match_all($regEx,$page,$matches)) {
			foreach ($matches[1] as $key=>$value) {
				if($this->existsProperty($value) == FALSE) {
					$query = "INSERT INTO props SET updated_time = " . time() . ", pid = $value, sid = {$this->siteToSearch}";
					//$this->log($query);
					$db->query($query);
					$count++;
				}
			}
		}
		$this->log("Added {$count} new properties to database");
	}

	function changePage($page) {
		if($this->siteToSearch == 0) {
			$url = $this->siteBase . 'SearchResults.aspx?rtype=address&page=' . $page;
			// First, let's load the search form.
			$searchPageResult = http_get($url,'');
		}
	return $searchPageResult;
	}

	function existsProperty($pid) {
		global $db;
		$result = $db->query("SELECT COUNT(sid) FROM props WHERE sid={$this->siteToSearch} AND pid={$pid}");
		if($result && $result->fetch_row()[0] > 0){
			return TRUE;
		}
	return FALSE;
	}

	function getProperty($propertyID) {
		global $db;

		// We want to sleep between requests to the site,
		// but we also want to time out if the delay puts us over the limit.
		// If TRUE, then timeout
		if($this->sleepOrTimeout($this->delaySeconds()) == TRUE) return 0;

		// Search the site for the property we want
		if ($this->siteToSearch==0) {
			$url = $this->siteBase . "Property.aspx?prop_id=$propertyID";
		}else{
			return 1;
		}
		$searchpage=http_get($url,'');

		// Get the property address
		$propertyAddress = explode("<td>Address:</td><td>", $searchpage['FILE'])[1];;
		$propertyAddress = explode("</td>", $propertyAddress)[0];

		// Get the owner's name
		$ownerName = explode("<td>Name:</td><td>", $searchpage['FILE'])[1];

		$ownerName = explode("</td>", $ownerName)[0];

		// Get the owner's mailing address
		$ownerMailingAddress = explode("<td>Mailing Address:</td>", $searchpage['FILE']);
		$ownerMailingAddress = explode("<td>", $ownerMailingAddress[1])[1];

		$improvementType = explode('propertyImprovementDescription">',$searchpage['FILE']);
		if(count($improvementType) > 1){
			$improvementType = explode("</td>",$improvementType[1])[0];
		}else {
			$improvementType = "None";
		}

		// Put it in the database
		$query = "UPDATE props SET updated_time = " . time() . ",
															owner_name = '{$ownerName}',
															property_address = '{$propertyAddress}',
															owner_address = '{$ownerMailingAddress}',
															improvement_type = '{$improvementType}'
															WHERE sid = '{$this->siteToSearch}' AND pid='{$propertyID}'";

		$result = $db->query($query);
		if(!$result) $this->log("Fail to update props table");

		// Return FALSE means we could not search for the property,
		// returning TRUE means we did search for it.
		return 2;
	}


	function clean_page($page) {
die("337-clean page");
		$page=remove($page,'<HTML>','END HEADER -->');
		$page=remove($page,'<!--','-->');
		$page=remove($page,'<a','</a>');
		$page=remove($page,'<script','</script>');
		$page=str_replace(chr(9),' ',$page);
		$page=str_replace(chr(13),'',$page);
		$page=str_replace("\n\n","\n",$page);
		$page=str_replace('&nbsp;',' ',$page);
		$page=str_replace('      ',' ',$page);
		$page=str_replace('      ',' ',$page);
		$page=str_replace('     ',' ',$page);
		$page=str_replace('    ',' ',$page);
		$page=str_replace('   ',' ',$page);
		$page=str_replace('  ',' ',$page);
		return strip_tags($page);
	}
}
?>
