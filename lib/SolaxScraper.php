<?php

namespace solax_php;

class solaxScraper {
	protected $username;
	protected $password;

	protected $headers = [
		'user-Agent' => 'Mozilla/5.0 (Linux; Android 8.1.0; ONEPLUS A5010 Build/OPM1.171019.011; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/68.0.3440.33 Mobile Safari/537.36',
		'Origin' => 'file://',
		'X-Requested-With' => 'com.solaxcloud.starter'
	];
	
	protected $host = 'www.solaxcloud.com:6080';

	protected $endPoints = [
		'login' => [
			'url' => '/proxy//login/login?password=%s&userName=%s&userType=5',
			'method' => 'POST'
		],
		'mysite' => [
			'url' => '/proxy//mysite/mySite',
			'method' => 'POST'
		],
		'getYield' => [
			'url' => '/proxy//mysite/getYield?month=%d&reportType=2&siteId=%d&tokenId=%s&webTime=%d,%d,26&year=%d',
			'method' => 'POST'
		],
		'getInverterInfo' => [
			'url' => '/proxy//mysite/getInverterInfo?siteId=%d&tokenId=%s',
			'method' => 'POST'
		],
		// /proxy//inverter/getDailyInfo?inverterSn=XB302182103106&today=2018-06-24&tokenId=b679ae3ccb80ee454dc1fd75dffeace2&wifiSn=SPWM9AKAKL
		'getDailyInfo' => [
			'url' => '/proxy//inverter/getDailyInfo?inverterSn=%s&today=%s&tokenId=%s&wifiSn=%s',
			'method' => 'POST'
		]
	];


	public function __construct(string $username, string $password) {
		$this->username = $username;
		$this->password = $password;

		$this->login();
	}

	protected function makeURL( string $url) : string {
		return sprintf('http://%s/%s', $this->host, $url);
	}

	protected function buildRequest (string $endpoint) : CURL{
		$c = new Curl();
		$c->setOptArray([
			CURLOPT_URL => 
				$this->makeURL($endpoint),
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_POST => 1,
			CURLOPT_HTTPHEADER => $this->headers
			]);
		return $c;
	}
	
	protected function getResponse( CURL $c ) : object {
		if ( ( $response = $c->exec() ) !== false ){
			return json_decode($response);
		} else {
			throw new Exception(sprintf('Request failed! %s', $response));
		}
	}

	protected function login() : void{
		$c = $this->buildRequest(sprintf($this->endPoints['login']['url'], 
					md5($this->password),
					$this->username));

		$response = $this->getResponse($c);
		if ( $response->success != 1 ){
			throw new Exception(sprintf('error logging in'));
		}

		$this->user = $response->result;
	}


	public function mySite() : array {

		$c = $this->buildRequest($this->endPoints['mysite']['url']);
		$c->setOpt(CURLOPT_POSTFIELDS, 
			sprintf('tokenId=%s&userId=%d',
				$this->user->tokenId,
				$this->user->userId));	
		$response = $this->getResponse($c);

		if ( $response->success == 1 ){
			$this->sites = $response->result;
		}

		// set default site to index 0
		$this->setSite();
		return $this->sites;
	}

	public function setSite(int $siteIndex = 0) : void {
		$this->site = $this->sites[$siteIndex];
	}

	public function getInverterInfo () : array {
		$c = $this->buildRequest(
			sprintf($this->endPoints['getInverterInfo']['url'],
				$this->site->siteId, $this->user->tokenId)
			);
		$response = $this->getResponse($c);
		if ( $response->success != 1){
			throw new Exception(sprintf('Could not fetch inverter info: %s', print_r($response,1)));
		}
		$this->inverters = $response->result;

		// let's set a default
		$this->setInverter();

		return $this->inverters;
	}

	public function setInverter(int $inverterIndex = 0) : void {
		if ( count($this->inverters) == 0 ){
			throw new Exception('No inverters yet dummy!');
		}
		$this->inverter = $this->inverters[$inverterIndex];
	}

	// /proxy//inverter/getDailyInfo?inverterSn=%s&today=%s&tokenId=%s&wifiSn=%s'
	public function getDailyInfo (string $date) : array {
		$c = $this->buildRequest(
			sprintf(
				$this->endPoints['getDailyInfo']['url'],
				$this->inverter->inverterSN,
				$date,
				$this->user->tokenId,
				$this->inverter->wifiSN
			));

		$response = $this->getResponse($c);
//		print_R($response);
		if ( $response->success != 1 ){
			throw new Exception('Could nog get daily info :(');
		}

		return $response->result;
	}

	public function getUserId () : int {
		return $this->user->userId;
	}
	public function getTokenId() : string {
		return $this->user->tokenId;
	}
	public function getSiteId() : int {
		return $this->site->siteId;
	}
}

?>
