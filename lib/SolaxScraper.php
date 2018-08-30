<?php
namespace solax_php;


class SolaxScraper {


	public static function factory (string $solaxScraperType, string $username, string $password) : SolaxScraperInterface {
		$scraper = sprintf('\solax_php\%sScraper', $solaxScraperType);
		return new $scraper($username, $password);
	}
}

