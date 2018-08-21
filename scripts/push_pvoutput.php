<?php

namespace solax_php;
use \PDO as PDO;
use \Exception as Exception;

require_once(__DIR__ . '/../lib/autoloader.php');

// setup db connection
$db = new PDO(sprintf('pgsql:host=%s;user=%s;dbname=%s;password=%s',
        Config::get('database', 'hostname'),
        Config::get('database', 'username'),
        Config::get('database', 'database'),
        Config::get('database', 'password')));
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pvo = new PVOutput(Config::get('pvoutput','api-key'), Config::get('pvoutput', 'site-id'));

/*
 * function to get power meter stats, using interval from
 * start of the day (first sample), until $timestamp
 */
$getDeliveredEnergy = function (string $timestamp) use ($db) : array  {
	$query =<<< EOI
	SELECT
		e2.sample as start,
		e1.sample as end,
		e1.power_in *1000 AS power_in,
		e1.power_out *1000 AS power_out,
		/* calculate difference betwen current and initial
		 * meter readouts to determine usage */
		(e1.kwh_in_1 - e2.kwh_in_1) *1000 AS kwh_in_1,
		(e1.kwh_in_2 - e2.kwh_in_2) *1000 AS kwh_in_2,
		(e1.kwh_out_1 - e2.kwh_out_1) *1000 AS kwh_out_1,
		(e1.kwh_out_2 - e2.kwh_out_2) *1000 AS kwh_out_2
	FROM electricity AS e1
	INNER JOIN electricity AS e2 ON DATE(e2.sample) = date(e1.sample)
	WHERE
		/* trye to find record closest to requested timestamp */
		e1.sample IN (
			SELECT e3.sample
			FROM electricity AS e3
			WHERE DATE(e3.sample) = DATE(:timestamp)
			ORDER BY abs(extract(epoch from sample) - 
				(extract(epoch from :timestamp) + extract(timezone from :timestamp)) ) ASC
			LIMIT 1
		)
		AND
		/* try to find first record on requested day */
		e2.sample IN (
			SELECT e4.sample
			FROM electricity AS e4
			WHERE DATE(e4.sample) = DATE(:timestamp)
			ORDER BY sample ASC
			LIMIT 1
		)
EOI;

	$stmt = $db->prepare($query);
	$stmt->bindValue(':timestamp', $timestamp);
	$stmt->execute();

	$ret = $stmt->fetch(PDO::FETCH_ASSOC);	

	if ( $ret === false ){
		$ret = [];
	}
	return $ret;
};

// only send daily totals if so requested
$argv = $GLOBALS['argv'];
if ( isset($argv[1]) && $argv[1] == 'daily' ){
	// build list of outputs
	$query =<<< EOI
	SELECT * FROM 
		(SELECT 
		MAX(sample) AS sample,
		DATE(MAX(sample)) as date,
		MAX(yield_today)*1000 as yield_today,
		MAX(power_ac) as power_ac
		FROM solax s
		GROUP BY date(sample))
	 AS q 
	WHERE q.date > (CURRENT_TIMESTAMP - INTERVAL '2 days')
	ORDER BY q.date DESC
EOI;

	$stmt = $db->prepare($query);
	$stmt->execute();
	$data = [];
	while ( $row = $stmt->fetch(PDO::FETCH_ASSOC) ){
		$eStats = $getDeliveredEnergy($row['sample']);

		if ( count($eStats) > 0 ){
			// sum of energy out in both tariffs
			$energyExported = $eStats['kwh_out_1'] + $eStats['kwh_out_2'];

			// generated energy - exported energy + consumed energy
			$energyUsed = $row['yield_today'] - $energyExported + $eStats['kwh_in_1'] + $eStats['kwh_in_2']; 
		} else {
			$energyExported = '';
			$energyUsed = '';
		}
		$data[] = implode(',',[
			str_replace('-','',$row['date']), (int)$row['yield_today'],
			(int)$energyExported, (int)$energyUsed,
			$row['power_ac']
		]);
	}

	$pvo->addBatchOutputs($data);
}

$query =<<< EOI
SELECT
	sample, 
	DATE(sample) as date,
	CONCAT(lpad(cast(extract(hour from sample) as varchar),2,'0'), ':', lpad(cast(extract(minute from sample) as varchar),2,'0')) as time,
	yield_today * 1000 as yield_today,
	-1 as yield_today2,
	power_ac,
	power_dc_1,
	power_dc_2,
	current_dc_1,
	current_dc_2,
	voltage_dc_1,
	voltage_dc_2,
	voltage_ac,
	temperature
FROM solax
WHERE sample > (NOW() - INTERVAL '%s')
ORDER BY sample ASC
EOI;

$query = sprintf($query, Config::get('pvoutput', 'push_window'));
$stmt = $db->prepare($query);
$stmt->execute();

$data = [];
while ( $row = $stmt->fetch(PDO::FETCH_ASSOC) ){
	//get stats from meter
	$eStats = $getDeliveredEnergy($row['sample']);

	if ( count($eStats) > 0 ){	
		// energy consumption is:
		// (generated_energy) - (exported enery) + (imported energy)
		$energyConsumption = $row['yield_today'] - $eStats['kwh_out_1'] - $eStats['kwh_out_2'] + $eStats['kwh_in_1'] + $eStats['kwh_in_2'];
	
		// current power consumption:
		// (pv power generation) - (exported power) + (imported_power)
		$powerConsumption = $row['power_ac'] - $eStats['power_out'] + $eStats['power_in'];
	} else {
		$energyConsumption = $powerConsumption = '';
	}

	if ( Config::get('pvoutput', 'extended_data') == '1' ){

		$data[] = implode(',', [
			str_replace('-','',$row['date']), $row['time'], (int)$row['yield_today'], 
			(int)$row['power_ac'], $energyConsumption, $powerConsumption, $row['temperature'], $row['voltage_ac'],
			$row['power_dc_1'], $row['power_dc_2'],
			$row['current_dc_1'], $row['current_dc_2'],
			$row['voltage_dc_1'], $row['voltage_dc_2']
		]);
	} else {

		$data[] = implode(',', [
			str_replace('-','',$row['date']), $row['time'], (int)$row['yield_today'], 
			(int)$row['power_ac'], $energyConsumption, $powerConsumption, $row['temperature'], $row['voltage_ac']
		]);

	}
}
//print_r($data);
printf("Found %d records @ %s\n", count($data), date('Ymd H:i:s'));
$pvo->addBatchStatus($data);

?>
