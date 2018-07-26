<?php

require_once('SolaxScraper.php');
try {
	// login to api (to obtain tokenID)
	$s = new solaxScraper( 'username', 'password');

	// get sites (default selects site 0)
	$s->mysite();

	printf("Connected... tokenId:%s, userId: %d, siteId: %d\n",
		$s->getTokenId(),
		$s->getUserId(),
		$s->getSiteId());

	// get list of inverters for selected site (default selects 0)
	$s->getInverterInfo();

	// get info for cuurent day for selected site + inverter
	$date = date('Y-m-d', time() - 7200);

	if ( isset($GLOBALS['argv'], $GLOBALS['argv'][1]) ){
		$date = $GLOBALS['argv'][1];
	}

	//$date = '2018-07-06';
	$info = $s->getDailyInfo($date);
} catch ( Exception $e ){
	print $e;
	exit;
}
printf("Got %d rows\n", count($info));

print_r($info);

?>
