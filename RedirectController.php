<?php
require_once "Controller.php";
require_once "DbException.php";

class RedirectController extends Controller {
	protected function processRequestGet($linkId) {
		try {
			$referer = isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : "";
			$longLink = $this->linkManager->getLinkForRedirect($linkId, $referer);
			header("Location: {$longLink}");
			http_response_code(302);
		} catch (DbException $e) {
			echo $e->getErrorMessage();
			http_response_code(500);
		}
	}
}
?>
