<?php

namespace solax_php;

interface SolaxScraperInterface {
	public function __construct(string $username, string $password);


	public function mySite() : array;

	public function setSite(int $siteIndex = 0) : void;

	public function getInverterInfo () : array;

	public function setInverter(int $inverterIndex = 0) : void;
	public function getDailyInfo (string $date) : array;

	public function getUserId () : int;
	public function getTokenId() : string;
}

?>
