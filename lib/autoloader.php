<?php
/**
 * simple autoloader
 *
 * @author: mike <niblett@gmail.com>
 */
spl_autoload_register( function($class){
	$parts = explode('\\', $class);
	$path = __DIR__ . DIRECTORY_SEPARATOR . $parts[1] . '.php';
	if ( is_readable($path) ){
		return require_once($path);
		
	}
	return false;

});
