<?php
class DbException extends Exception {
	private $error;
	private $errno;

	public function __construct($mysqlError, $mysqlErrno) {
		$this->error = $mysqlError;
		$this->errno = $mysqlErrno;
	}

	public function getErrorMessage() {
		return $this->error;
	}

	public function getErrorCode() {
		return $this->errno;
	}
}
?>
