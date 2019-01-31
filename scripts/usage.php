<?php

namespace solax_php;
use \PDO as PDO;

require_once(__DIR__ . '/../lib/autoloader.php');

// setup db connection
$db = new PDO(sprintf('pgsql:host=%s;user=%s;dbname=%s;password=%s',
        Config::get('database.hostname'),
        Config::get('database.username'),
        Config::get('database.database'),
        Config::get('database.password')));
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// setup db connection

function getRowWidths(array $data, array $headers = []) : array {
	$rowWidths = [];
	$data[] = $headers;
	foreach ( $data as $row ){
		foreach ( $row as $index => $value ){
	
			if ( !isset($rowWidths[$index]) ){
				$rowWidths[$index] = strlen($value);
				continue;
			}
			$rowWidths[$index] = max(strlen($value), $rowWidths[$index]);
		}
	}
	return $rowWidths;
}
function printLine($rowWidths, $row){
	$fields = [];
	foreach ($row as $index => $field){
		$formatString = '%' . $rowWidths[$index] . 's';
		$fields[] = sprintf($formatString, $field);
	}
	print "| " . implode(' | ', $fields) . " |\n";
}
function printSeparator($rowWidths){
	$fields = [];
	foreach ( $rowWidths as $width ){
		$fields[] = str_repeat('-', $width);
	}
	print "+-" . implode('-+-', $fields) . "-+\n";
}
function printTable(array $data){
	$headers = [];
	foreach ( $data[0] as $header => $value ){
		$headers[$header] = $header;
	}
	$rowWidths = getRowWidths($data, $headers);

	printSeparator($rowWidths);

	// print headers
	printLine($rowWidths, $headers);
	printSeparator($rowWidths);

	// print data
	foreach ( $data as $line ){
		printLine($rowWidths, $line);
	}
	printSeparator($rowWidths);
}


$query = <<<EOQ
SELECT 
	*,
	bar.in-bar.out as net_usage,
	bar.in - bar.out + s.generation   as consumption
FROM 
	(SELECT 
		*, 
		in_1+in_2 as in , 
		out_1+out_2 as out 
	FROM 
		(SELECT 
			MIN(sample) as start, 
			MAX(sample) as end, 
			MAX(kwh_in_1) - MIN(kwh_in_1) as in_1, 
			MAX(kwh_in_2) - MIN(kwh_in_2) as in_2, 
			MAX(kwh_out_1) - MIN(kwh_out_1) as out_1, 
			MAX(kwh_out_2) - MIN(kwh_out_2) as out_2 
		FROM electricity 
		GROUP BY 
			CONCAT(
				EXTRACT(year FROM sample),
				'-',
				EXTRACT(month FROM sample)
			)
		ORDER BY MIN(sample)
		) as foo
	) as bar
	JOIN (SELECT
		EXTRACT(month FROM sample) AS month,
		MAX(yield_total) - MIN(yield_total) as generation
		FROM solax
		GROUP BY EXTRACT(month FROM sample)	
		) as s ON s.month = EXTRACT(month from bar.start)
	ORDER BY bar.start

EOQ;

$stmt = $db->prepare($query);

$stmt->execute();

$first = 1;
$fp = fopen('php://stdout', 'a+');
$data = [];
while ( $row = $stmt->fetch(PDO::FETCH_ASSOC) ){
	$data[] = $row;
}

printTable($data);
?>
