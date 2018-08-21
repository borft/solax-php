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

// setup db connection

$table = 'solax';
if ( isset($GLOBALS['argv'], $GLOBALS['argv'][1]) ){
	$table = 'electricity';
}

$query = sprintf('SELECT * FROM %s ORDER BY sample ASC', $table);

$stmt = $db->prepare($query);

$stmt->execute();

$first = 1;
$fp = fopen('php://stdout', 'a+');
while ( $row = $stmt->fetch(PDO::FETCH_ASSOC) ){

	if ( $first ){
		$first = false;
		fputcsv($fp, array_keys($row));
	}
	fputcsv($fp, $row);
}

?>
