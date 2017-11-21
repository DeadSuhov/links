<?php
class UserNotFoundException extends Exception {
	private $userLogin;

	public function __construct($userLogin) {
		$this->userLogin = $userLogin;
	}

	public function getLogin() {
		return $this->userLogin;
	}
}
?>
