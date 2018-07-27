<?php

$dev = '/dev/ttyUSB0';

$fd = dio_open($dev, O_RDWR | O_NOCTTY );

dio_tcsetattr($fd, [
  'baud' => 115200,
  'bits' => 8,
  'stop'  => 1,
  'parity' => 0
]); 




$serialData = '';
while (1) {
	$line = dio_read($fd, 1024);

	// look for first line
	// /KFM5KAIFA-METER
	if ( preg_match('/^\/.*/', $line) ){
		$serialData .= $line;
		continue;
	}
	if ( count($serialData) > 0 ){
		$serialData .= $line;
	} else {
		// wait until we get header line to make sure we get a full telegram
		continue;
	}
	// this is the last line of the telegram
	if ( preg_match('/\![A-Z0-9]{4}/', $serialData) ){
		 break;
	}
} 

$data = explode("\n", $serialData);
$mappingTable = [
	'0.2.8' => 'version_info',
	'1.0.0' => 'timestamp',
	'1.7.0' => 'power_in',
	'2.7.0' => 'power_out',
        '1.8.1' => 'kwh_in_1',
	'1.8.2' => 'kwh_in_2',
	'2.8.1' => 'kwh_out_1',
	'2.8.2' => 'kwh_out_2',
	'31.7.0' => 'current_l1',
	'51.7.0' => 'current_l2',
	'71.7.0' => 'current_l3',
	'21.7.0' => 'power_in_l1',
	'41.7.0' => 'power_in_l2',
	'61.7.0' => 'power_in_l3',
	'22.7.0' => 'power_out_l1',
	'42.7.0' => 'power_out_l2',
	'62.7.0' => 'power_out_l3'
];

$result = [];
$parseP1Line = function ( string $line ) use ($mappingTable, &$result) : void {
	if ( strlen(trim($line)) == 0){
		return;
	}
	$matches = [];
	// 1-0:1.8.1(005368.448*kWh)
	if ( preg_match('/^\d\-\d\:(\d+\.\d\.\d)\((\d+\.?\d*)\*(.*?)\)/', $line, $matches) ){
		$result[] = [
			'code' => $matches[1],
			'field' => $mappingTable[($matches[1])],
			'value' => $matches[2],
			'unit' => $matches[3]
			
		];
	} elseif (preg_match('/^\d\-\d\:(\d+\.\d\.\d)\((.*?)\)/', $line, $matches) ){
		$result[] = [
			'code' => $matches[1],
			'field' => $mappingTable[($matches[1])],
			'value' => $matches[2],
			'unit' => ''
		];
	} else {
	//	$result [] = $line;
}
};


array_walk ($data, $parseP1Line);
//print_r($result);

// setup db connection
$db = new PDO('pgsql:host=host;user=user;dbname=dbname;password=password');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// time to import data

// build insert query

$fields = array_values($mappingTable);
unset($fields[0]);
$fields[1] = 'sample';
$query = sprintf('INSERT INTO electricity (%s) VALUES(%s)',
	implode($fields, ','),
        implode(array_map(function ($field ){
		return sprintf(':%s', $field);
		},
		$fields),','));
$stmt = $db->prepare($query);

$dateTime = date('Y-m-d H:i:s', (3600*7) + $sample->uploadTime/1000);
//      print "jow: $dateTime :: " . $sample->uploadTimeValue . "\n";

array_walk($result,
	function (array $data) use ($stmt, $fields) : void {
		//printf ("Binding %s to %s with value %s \n", $dbField, $apiField, $sample->{$apiField});

		if ( $data['field'] == 'timestamp' ){
			// 180707151831S
			$data['value'] = date('Y-m-d H:i:s', strtotime('20' .$data['value']));
			$data['field'] = 'sample';
		}

		if ( in_array($data['field'], $fields) ){
			$stmt->bindValue(sprintf(':%s', $data['field']), $data['value']);								
		}
	});
try {
	$stmt->execute();
	$counter++;
} catch ( PDOException $e ){
	 print $e;
}







