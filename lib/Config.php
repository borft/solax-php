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
				$this->_set('global.date', $args[($i+1)]);
				$i++;
			} elseif ( $args[$i] == '--set' ){
				list($term, $value) = explode('=', $args[($i+1)]);
				$this->_set($term, $value);
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
	public static function get(string $term, string $default = '') : string {
		return self::getInstance()->_get($term, $default);
	}

	/**
	 * instance function to return values
	 */
	protected function _get(string $term, string $default) : string {
		list($section, $key) = explode('.', $term);
		if ( isset($this->config[$section][$key]) ){
			return $this->config[$section][$key];
		}
		if ( $default != '' ){
			return $default;
		}
		throw new Exception(sprintf('Could not find key %s in section %s', $key, $section));
	}

	public static function set(string $term, string $value) : void {
		self::getInstance()->_set($term, $value);
	}

	protected function _set(string $term, string $value) : void {
		list($section, $key) = explode('.', $term);
		$this->config[$section][$key] = $value;
	}

}

?>
