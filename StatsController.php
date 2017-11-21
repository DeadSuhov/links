<?php
require_once "Controller.php";
require_once "DbException.php";

class StatsController extends Controller {
	protected function processRequestGet($requestData) {
		try {
			$result = $this->linkManager->getReport($requestData['link'], strtolower($requestData['interval']), $requestData['fromDate'], $requestData['toDate']);
			echo json_encode($result);
		} catch (DbException $e) {
			http_response_code(501);
		}
	}
}
?>
