<?php
/**
 * simple autoloader
 *
 * @author: mike <niblett@gmail.com>
 */
spl_autoload_register( function($class){
	$parts = explode('\\', $class);
	$part = array_pop($parts);

	$path = __DIR__ . DIRECTORY_SEPARATOR . $part . '.php';

	if ( is_readable($path) ){
		return require_once($path);
		
	}
	return false;
});
