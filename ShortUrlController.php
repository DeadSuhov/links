<?php
require_once "Controller.php";
require_once "DbException.php";
require_once "UserNotFoundException.php";

class ShortUrlController extends Controller {
	protected function processRequestGet($linkId) {
		try {
			$linkInfo = $this->linkManager->getLinkInfo($linkId);
			echo json_encode($linkInfo);
		} catch (DbException $e) {
			http_response_code(500);
		}
	}

	protected function processRequestDelete($linkId) {
		try {
			$this->linkManager->deleteShortLink($linkId);
		} catch (DbException $e) {
			http_response_code(500);
		}
	}
}
?>
