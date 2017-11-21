<?php
require_once "Controller.php";
require_once "UserNotFoundException.php";
require_once "DbException.php";

class UserRequestController extends Controller {
	protected function processRequestPost($url, $data) {
		try {
			$newUserId = $this->userStore->regNewUser($this->userLogin, $this->userPassword);
			$response = [];
			$response["id"] = $newUserId;
			http_response_code(201);
			echo json_encode($response);
		} catch (DbException $e) {
			$errorNo = $e->getErrorCode();
			if ($errorNo == 1062) {
				http_response_code(409);
			} else {
				http_response_code(500);
			}
		}
	}

	protected function processRequestGet($url) {
		$response = [];
		try {
			$response["id"] = $this->userStore->getIdByLogin($this->userLogin);
			http_response_code(200);
			echo json_encode($response);
		} catch (DbConnection $e) {
			http_response_code(500);
		} catch (UserNotFoundException $e) {
			http_response_code(401);
		}
	}
}
?>
