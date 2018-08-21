<?php
namespace solax_php;
use \PDO as PDO;

require_once('../lib/autoloader.php');

// setup db connection
$db = new PDO(sprintf('pgsql:host=%s;user=%s;dbname=%s;password=%s',
	Config::get('database', 'hostname'),
	Config::get('database', 'username'),
	Config::get('database', 'database'),
	Config::get('database', 'password')));
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// load solax API scraper
try {
	// login to api (to obtain tokenID)
	$s = new SolaxScraper(Config::get('solax', 'username'), Config::get('solax', 'password'));

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
//print_r($info);
// map fields in JSON to DB table columns
$fields = [ 	'sample' => 'uploadTimeValueGenerated', 
		'current_dc_1' => 'idc1',
 		'current_dc_2' => 'idc2',
		'voltage_dc_1' => 'vdc1', 
		'voltage_dc_2' => 'vdc2', 
		'power_dc_1' => 'powerdc1',
		'power_dc_2' => 'powerdc2', 
		'current_ac' => 'iac1', 
		'voltage_ac' => 'vac1', 
		'power_ac' => 'gridpower', 
		'yield_today' => 'yieldtoday', 
		'yield_total' => 'yieldtotal',
		'net_frequency' => 'fac1', 
		'temperature' => 'temperature'
	];

// build insert/update query
$query = sprintf('INSERT INTO solax (%s) VALUES(%s) ON CONFLICT (sample) DO UPDATE SET %s',
	implode(array_keys($fields), ','),
	implode(array_map(function ($field ){
			return sprintf(':%s', $field);
		}, 
		array_keys($fields)),','),
	implode(array_map(function($field){
			return sprintf('%s=excluded.%s', $field, $field);
		},
		array_keys($fields)),',')
	);
$stmt = $db->prepare($query);

$counter = 0;
foreach ( $info as $sample ){
	// we don't need records in the future
	if ( time() < (3600 * Config::get('solax', 'time_offset')) + $sample->uploadTime/1000) {
		break;
	}

	// don't insert bogus
	if ( $sample->gridpower == '' ){
		continue;
	}	

	// calculate corrected timestamp
	$dateTime = date('Y-m-d H:i:s', (3600* Config::get('solax', 'time_offset')) + $sample->uploadTime/1000);

	$sample->uploadTimeValueGenerated = $dateTime;

	// no yield befor 3am
	// this is to prevent crap in the db
	if ( date('H', (3600*7) + $sample->uploadTime/1000) < 4 && $sample->yieldtoday > 0){
		printf("Changing yield from %f to %f @ %s\n", $sample->yieldtoday, 0, $sample->uploadTimeValueGenerated);
		$sample->yieldtoday = 0;

	}

	// bind the actual values to the query
	array_walk($fields,
		function (string $apiField, string $dbField) use ($stmt, $sample) : void {
			//printf ("Binding %s to %s with value %s \n", $dbField, $apiField, $sample->{$apiField});
			$stmt->bindValue(
				sprintf(':%s', $dbField),
				$sample->{$apiField}
			);
		});
	try {
		$stmt->execute();
		$counter++;
	} catch ( PDOException $e ){
		// print $e;
		//  print "time: " . $sample->uploadTimeValue . "\n";
	}
}
	

printf("%d rows inserted @ %s\n", $counter, date('Ymd H:i'));

?>
