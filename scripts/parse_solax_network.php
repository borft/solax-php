<?php

/**
tcpdump cmdline:
tcpdump -tttt -A -n -v -s0 -i br-lan src 192.168.63.234

*/

error_reporting(E_ALL);

$fp = fopen('php://stdin', 'r');



/** sample data:
 [Data] => Array
        (
            [0] => 4.4
            [1] => 3.4
            [2] => 191.6
            [3] => 202.3
            [4] => 6.4
            [5] => 235.8
            [6] => 1498
            [7] => 37
            [8] => 3.1
            [9] => 6.5
            [10] => 0
            [11] => 855
            [12] => 701
*/

function fixData($data){
	$realData = [];
	$fields = [
		'DC current 1',
		'DC current 2',
		'DC voltage 1',
		'DC voltage 2',
		'AC current',
		'AC voltage',
		'AC power',
		'Inverter temperature',
		'Yield today',
		'Monthly yield',
		'10',
		'DC power 1',
		'DC power 2'
	];
	if ( count($data) < 10 ){
		return  $data;
	}
	$fields[50] = 'Net frequency';
	foreach ( $data as $index => $value ){
		if ( isset($fields[$index]) ) {
			$label = $fields[$index];
		} else {
			$label = $index;
			if ( $value == 0 ){
				unset($value);
			}
		}
		if ( isset($value) ){
			$realData[$label] = $data[$index];
		}
	}
	return $realData;
}


/**

	sample log

10:15:41.343659 IP (tos 0x0, ttl 128, id 43257, offset 0, flags [DF], proto TCP (6), length 40)
    192.168.63.234.1883 > 47.254.152.103.2901: Flags [.], cksum 0xf7bd (correct), seq 8, ack 9, win 4096, length 0
E..(..@.......?./..g.[.U{.On...&P.............
10:15:50.825881 IP (tos 0x0, ttl 128, id 43258, offset 0, flags [DF], proto TCP (6), length 428)
    192.168.63.234.1883 > 47.254.152.103.2901: Flags [P.], cksum 0x1f5c (correct), seq 8:396, ack 9, win 4096, length 388
E.....@....Y..?./..g.[.U{.On...&P....\..2....loc/SPWM9AKAKL..{"type":"X1-Boost-Air-Mini","SN":"SPWM9AKAKL","ver":"2.06.4","Data":[4.0,3.1,194.1,202.5,5.9,237.3,1365,35,2.5,5.9,0,783,637,0.00,0.00,0,0,0,0.0,0.0,0.00,0.00,0,0,0,0.0,0.0,0.00,0.00,0,0,0,0.0,0.0,0,0,0,0,0,0,0,0.00,0.00,0,0,0,0,0,0,0,49.99,0,0,0,0,0,0,0,0,0,0.00,0,8,0,0,0.00,0,8,2],"Information":[3.000,4,"X1-Boost-Air-Mini","XB302182103106",2,3.20,1.07,1.08,0.00]}
*/

$db = new PDO('pgsql:host=host;user=solax;dbname=power;password=password');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$fields = [     'sample' => 'uploadTimeValueGenerated',
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
$newPacket = false;
$newHeader = false;
while ( $line = fgets($fp) ){
	// we read forever!

	// digit on start of line, so this is start of a new packet
	$matches = [];
	if ( !$newPacket || preg_match('/^(\d+\-\d+\-\d+)?\s?(\d+\:\d+\:\d+).*?$/', $line, $matches ) ){
		if ( $newPacket == true ){
		}
		$newPacket = true;
		if (isset($matches[2]) ) {
			$date = $matches[1];
			$time = $matches[2];
		} else {
			$time = $matches[1];
			$date = 'today';
		}
		continue;
	}
	$matches = [];
	if ( !$newHeader && preg_match('/^\d+\..*?$/', trim($line), $matches) ){
		// should be headerline
		$newHeader = true;
		//print_r($matches);
		continue;
	}
	if ( $newHeader && $newPacket ){
		// this should be the payload
		// we don't want tiny packets
		// try to get json from it
		if ( strlen($line) > 200 ){
			$matches = [];
			if ( !preg_match('/^.*?\.(\{\".*?\})$/', $line, $matches) ){
				$newPacket = $newHeader = false;
			} else {
				print "date: $date, time: $time\n";
				$data = json_decode($matches[1], 1);
				$fixedData = fixData($data['Data']);

				if ( Strlen($date) > 0 ){
					$sample = sprintf('%s %s', $date, $time);

					// check if record exists
					$query = 'SELECT * FROM solax WHERE sample = :sample';
					$stmt = $db->prepare($query);
					$stmt->bindParam(':sample', $sample);
					$stmt->execute();
					if ( ! ($row = $stmt->fetch()) || $row['power_ac'] == '' ){
						print "Inserting data @ $sample\n";
					
					        // no yield befor 3am
						// this is to prevent crap in the db
						if ( date('G', strtotime($sample)) > 4 ){
							print "jow: hour > 4\n";
						
							$query = 'INSERT INTO solax
							(sample,current_dc_1,current_dc_2,voltage_dc_1,voltage_dc_2,
							power_dc_1,power_dc_2,current_ac,voltage_ac,power_ac,
							yield_today,yield_total,net_frequency,temperature) 
							VALUES
							(:sample,:current_dc_1,:current_dc_2,:voltage_dc_1,:voltage_dc_2,
							:power_dc_1,:power_dc_2,:current_ac,:voltage_ac,:power_ac,
							:yield_today,:yield_total,:net_frequency,:temperature)
								ON CONFLICT (sample) DO UPDATE SET
								current_dc_1 = :current_dc_1,
								current_dc_2 = :current_dc_2,
								voltage_dc_1 = :voltage_dc_1,
								voltage_dc_2 = :voltage_dc_2,
								power_dc_1 = :power_dc_1,
								power_dc_2 = :power_dc_2,
								power_ac = :power_ac,
								voltage_ac = :voltage_ac,
								current_ac = :current_ac,
								temperature = :temperature,
								net_frequency = :net_frequency,
								yield_today = :yield_today,
								yield_total = :yield_total
								';
							$stmt = $db->prepare($query);
							$stmt->bindValue(':current_dc_1', $fixedData['DC current 1']);
							$stmt->bindValue(':current_dc_2', $fixedData['DC current 2']);
							$stmt->bindValue(':voltage_dc_1', $fixedData['DC voltage 1']);
							$stmt->bindValue(':voltage_dc_2', $fixedData['DC voltage 2']);
							$stmt->bindValue(':power_dc_1', $fixedData['DC power 1']);
							$stmt->bindValue(':power_dc_2', $fixedData['DC power 2']);
							$stmt->bindValue(':power_ac', $fixedData['AC power']);
							$stmt->bindValue(':current_ac', $fixedData['AC current']);
							$stmt->bindValue(':voltage_ac', $fixedData['AC voltage']);
							$stmt->bindValue(':temperature', $fixedData['Inverter temperature']);
							$stmt->bindValue(':net_frequency', $fixedData['Net frequency']);
							$stmt->bindValue(':yield_today', $fixedData['Yield today']);
							$stmt->bindValue(':yield_total', $fixedData['Monthly yield']);
							$stmt->bindValue(':sample', $sample);
							$stmt->execute();
						}
					}
				}
				print_r($fixedData);
			}	
		}
	}
}
?>
