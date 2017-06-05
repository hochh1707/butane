<?php
include_once('includes/config.php');

// A little bit of security
if($_GET['blerg'] == 'test811'){
	exit();
	echo("7-Fail");
}elseif($_GET['blerg'] == 'Cyjk9soLYoZurFqFidbj'){
	//exit();
	// Uncomment out the exit for dev purposes
}else{
	exit();
	echo("12-Fail");
}

runEngine();

function runEngine() {
	global $db,$S;

	$S = new scraper();

	// Select McLennan County
	$S->siteToSearch(0);

	// Pick a random letter of the alphabet to search on
	$arrLetters = array(a,b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z);
	$searchString = $arrLetters[rand(0,count($arrLetters)-1)];

	// Get the first page of results for the letter we searched
	$searchResult = $S->search($searchString);

	// Pick a random page number of search results
	$lastPageNumber = $searchResult['PAGES'][count($searchResult['PAGES'])-1];
	$usePageNumber = rand(1,$lastPageNumber);

	// Take the property IDs from the page of search results and put them in the database
	$pg=$S->changePage($usePageNumber);
	$S->getPropertyIDs($pg['FILE']);

	// Now we move to checking individual properties
	// Get the list of property IDs in the database
	// Then search the site for one at a time until we time out or complete the for loop
	$results = $db->query("SELECT pid FROM props");
	$arrPIDs = $results->fetch_all();
	$stopGettingProperties = rand(1,100);
	for($i = 0; $i <= $stopGettingProperties; $i++){
		$randomPropertyID = $arrPIDs[rand(0,count($arrPIDs)-1)][0];
		$getPropReturn = $S->getProperty($randomPropertyID);
		if($getPropReturn == 0){
			$S->log("Time out, i=" . $i . " stopGettingProperties=" . $stopGettingProperties);
			exit();
		}elseif($getPropReturn == 1){
			$S->log("Problem with site ID");
			exit();
		}elseif($getPropReturn == 2){
			//$S->log("Added owner to property");
		}
	}
$S->log("Complete, i=" . $i . " stopGettingProperties=" . $stopGettingProperties);
}
