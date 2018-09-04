<?php

namespace solax_php;

/***
 * info from: https://github.com/GitHobi/solax/wiki/Solax-FHEM-integration
 */

class SolaxPortalScraper implements SolaxScraperInterface {

	use SolaxScraperHTTPHelper;
	use SolaxScraperUserHelper;


	protected $headers = [
	];

	protected $sites = [];
	protected $inverters = [];
	
	protected $host = 'www.solax-portal.com';

	protected $endPoints = [
		'login' => [
			'method' => 'GET',
			'url' => 'api/v1/user/Login?&password=%s&username=%s'
		],
		'mysite' => [
			'method' => 'GET',
			'url' => 'api/v1/user/SiteList?token=%s'
		],
		'getInverterInfo' => [
			'method' => 'GET',
			'url' => 'api/v1/site/InverterList/%d?token=%s&date=%s'
		],
		'getDailyInfo' => [
			'method' => 'GET',
			'url' => 'api/v1/site/EnergyTypeColumn/%s?date=%s&timeType=0&reportType=2&lang=en&token=%s'
		],
		'overviewData' => [
			'method' => 'GET',
			'url' => 'dz/home/overviewdata/%d?timetype=string&columnName=%s&timeColumnName=RTCTime&StartTime=%s&EndTime=%s'
		],
		'loginSite' => [
			'method' => 'POST',
			'url' => 'dz/home/login',
			'scheme' => 'https'
		]
	];


	public function __construct(string $username, string $password) {
		$this->username = $username;
		$this->password = $password;

		// no hash function needed here
		$this->setPasswordHashMethod( function ($password) { return $password; } );

		$this->login();
	}

	protected function checkSuccess($response) : bool {
		if ( isset($response->successful) && $response->successful == 1 ){
			return true;
		}
		return false;
	}

	protected function getUserData ( object $response ) : \stdClass {
		return $response->data;
	}

	public function getTokenId() : string {
		return $this->user->token;
	}

	public function getUserId() : int  {
		return $this->user->id;
	}

	public function getSiteId() : int {
		return $this->site->id;
	}
	
	public function mySite() : array {

		$c = $this->buildRequest(
			sprintf($this->endPoints['mysite']['url'], $this->user->token),
			$this->endPoints['mysite']['method']);
		
		$response = $this->getResponse($c);

		if ( !$this->checkSuccess($response) ){
			throw new Exception('Request failed');
		}
		
		$this->sites = $response->data;
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
				$this->site->id, $this->user->token, '2018-08-29'),
			$this->endPoints['getInverterInfo']['method']
			);
		$response = $this->getResponse($c);

		print_r($response);

		if ( !$this->checkSuccess($response) ){
			throw new Exception(sprintf('Could not fetch inverter info: %s', print_r($response,1)));
		}
		$this->inverters = $response->data;
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


	protected function loginSite(){
		print "Trying to log in!\n\n";
		$c = $this->buildRequest(
			$this->endPoints['loginSite']['url'],
			$this->endPoints['loginSite']['method'],
			$this->endPoints['loginSite']['scheme']
			);

		$postvars = [
			'username' => $this->username,
			'password' => $this->password,
			'saveStatus' => 'true',
			'ValidateCode' => 'False'
		];
		$postvarData = [];
		foreach ( $postvars as $var => $value ){
			$postvarData[] = sprintf('%s=%s', $var, $value);
		}
		$c->setOpt(\CURLOPT_POSTFIELDS, implode('&', $postvarData ));
		$c->setOpt(\CURLOPT_HEADER, 1);
	//	$c->setOpt(\CURLOPT_NOBODY, 1);
	//	$c->setOpt(\CURLINFO_HEADER_OUT, 1);
//		$c->setOpt(\CURLOPT_VERBOSE,true);

		$response = $this->getResponse($c);
	//	print_r(curl_getinfo($c->getCurl()));

		$matches = [];
		if ( preg_match_all('/^Set-Cookie(.*?);.*?$/m', $response->response, $matches) ){
			$this->cookies = $matches;
		}
		print_r($matches);
	}


	public function getDailyInfo (string $date) : array {
		$this->loginSite();

		$column = 'dtfdl';

		$c = $this->buildRequest(
			sprintf($this->endPoints['overviewData']['url'],
				$this->site->id, $column,  $date, $date),
			$this->endPoints['overviewData']['method']);
		$c->setOpt(\CURLOPT_HTTPHEADER, ['Cookie: ' . implode('; ', $this->cookies)]);
		$response = $this->getResponse($c);

		print_r($response);
		
	}

	public function getDailyInfo_ (string $date) : array {

		$this->loginSite();
exit;
		$c = $this->buildRequest(
			sprintf($this->endPoints['getDailyInfo']['url'],
				$this->site->id, $date, $this->user->token),
			$this->endPoints['overviewData']['method']);
		$response = $this->getResponse($c);
		print_r($response);
		return [];
	}
}

?>
