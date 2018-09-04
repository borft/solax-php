<?php

namespace solax_php;

class Config {


	protected static $instance;

	protected $config = [];


	/**
	 * constructor, reads ini file
	 */
	protected function __construct(){
		$this->config = parse_ini_file(__DIR__ . '/../config/solax-php.ini', true);
		$this->commandLineParser($GLOBALS['argv']);
	}


	protected function commandLineParser(array $args) : void {
		$len = count($args);
		for ( $i = 1; $i < $len; $i++ ){
			if ( $args[$i] == '--date' ){
				self::set('global.data', $args[($i+1)]);
				$i++;
			} elseif ( $args[$i] == '--set' ){
				list($term, $value) = explode('=', $args[($i+1)]);
				self::set($term, $value);
				$i++;
                        }
                }
	}

	/**
	 * factory to build instance
	 */
	protected static function getInstance() : Config {
		if ( !self::$instance instanceOf Config ){
			self::$instance = new Config();
		}
		return self::$instance;
	}

	/**
	 * static function to get config key/value pair
	 *
	 * @param string $section - section in ini file
	 * @param string $key - name of key
	 * @ret string - 
	 */
	public static function get(string $term) : string {
		list($section, $key) = explode('.', $term);
		return self::getInstance()->_get($section, $key);
	}

	/**
	 * instance function to return values
	 */
	protected function _get(string $section, string $key) : string {
		if ( isset($this->config[$section][$key]) ){
			return $this->config[$section][$key];
		}
		throw new Exception(sprintf('Could not find key %s in section %s', $key, $section));
	}

	public static function set(string $term, string $value) : void {
		list($section, $key) = explode('.', $term);
		print "setting $section  $key -> $value\n";
		self::getInstance()->_set($section, $key, $value);
	}

	protected function _set(string $section, string $key, string $value) : void {
		$this->config[$section][$key] = $value;
	}

}

?>
