<?php
namespace solax_php;

class Curl {

	protected $curl;

	public function __construct(){
		$this->curl = curl_init();
	}

	public function __destruct(){
		curl_close($this->curl);
	}
	
	public function setOpt( int $opt, $param){
		curl_setopt($this->curl, $opt, $param);
	}

	public function setOptArray(array $opts): void{
		curl_setopt_array($this->curl, $opts);
	}

	public function getCurl(){
	return $this->curl;
	}

	public function exec() : string {
		return curl_exec($this->curl);
	}
}
?>
