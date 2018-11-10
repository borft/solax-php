<?php
namespace solax_php;

use \Closure;

trait SolaxScraperUserHelper {
	protected $username;
	protected $password;

	protected $passwordHashMethod;

	/**
	 * login user to obtain tokenID
	 */
        protected function login() : void{
                $c = $this->buildRequest(
			sprintf($this->endPoints['login']['url'],
				($this->passwordHashMethod)($this->password),
				$this->username),
			$this->endPoints['login']['method']);

		$response = $this->getResponse($c);
		if ( !$this->checkSuccess($response) ){
			throw new Exception(sprintf('error logging in'));
		}

		$this->user = $this->getUserData($response);
        }

	/**
	 * allow custom password hash function
	 */
	protected function setPasswordHashMethod(Closure $c) : void {
		$this->passwordHashMethod = $c;
	}
}
