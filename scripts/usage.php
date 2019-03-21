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

function getRowWidths(array $data, array $headers = [], array $footer = []) : array {
	$rowWidths = [];
	$data[] = $headers;
	$data[] = $footer;
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
function printTable(array $data, array $footer = []){
	$headers = [];
	foreach ( $data[0] as $header => $value ){
		$headers[$header] = $header;
	}
	$rowWidths = getRowWidths($data, $headers, $footer);

	printSeparator($rowWidths);

	// print headers
	printLine($rowWidths, $headers);
	printSeparator($rowWidths);

	// print data
	foreach ( $data as $line ){
		printLine($rowWidths, $line);
	}
	printSeparator($rowWidths);

	if ( count($footer) > 0 ){
		printLine($rowWidths, $footer);
		printSeparator($rowWidths);
	}
}


$query = <<<EOQ
SELECT 
	bar.start,
	bar.end,
	bar.in,
	bar.out,
	bar.in-bar.out as net_usage,
	bar.in - bar.out + s.generation   as consumption,
	s.generation,
	TRUNC(100 * (s.generation - bar.out)  / ( s.generation),2) as own_usage,
	TRUNC(100 * (s.generation - bar.out) / (bar.in - bar.out + s.generation), 2) as self_suff
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
		/* start date of Powerpeers subscription */
		WHERE date(sample) > '2018-07-11'
		GROUP BY 
			to_char(sample, 'YYYY-MM')
		ORDER BY MIN(sample)
		) as foo
	) as bar
	JOIN (SELECT
		to_char(sample, 'YYYY-MM') AS yearmonth,
		MAX(yield_total) - MIN(yield_total) as generation
		FROM solax s
		WHERE date(sample) > '2018-07-11'
		GROUP BY
			to_char(sample, 'YYYY-MM')
			
		) as s ON s.yearmonth = to_char(bar.start, 'YYYY-MM')
	ORDER BY bar.start

EOQ;

$stmt = $db->prepare($query);

$stmt->execute();

$first = 1;
$fp = fopen('php://stdout', 'a+');
$data = [];
$totals = [
	'start' => '',
	'end' => '',
	'in' => 0,
	'out' => 0,
	'net_usage' => 0,
	'consumption' => 0,
	'generation' => 0,
	'own_usage' => 0,
	'self_suff' => 0
];
while ( $row = $stmt->fetch(PDO::FETCH_ASSOC) ){
	$data[] = $row;
	if ( $totals['start'] == '' ){
		$totals['start'] = $row['start'];
	}
	$totals['start'] = min($totals['start'], $row['start']);
	$totals['end'] = max($totals['end'], $row['end']);
	$totals['in'] += $row['in'];
	$totals['out'] += $row['out'];
	$totals['net_usage'] += $row['net_usage'];
	$totals['consumption'] += $row['consumption'];
	$totals['generation'] += $row['generation'];
}
$totals['own_usage'] = sprintf('%1.2f', 100 * ($totals['generation'] - $totals['out'])/$totals['generation']);
$totals['self_suff'] = sprintf('%1.2f', 100 * ($totals['generation'] - $totals['out'])/$totals['consumption']);

printTable($data, $totals);
?>
