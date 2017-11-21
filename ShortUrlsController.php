<?php
require_once "Controller.php";
require_once "DbException.php";
require_once "UserNotFoundException.php";

class ShortUrlsController extends Controller {
	protected function processRequestGet($url) {
		try {
			$userLinks = $this->linkManager->getUserLinks();
			echo json_encode($userLinks);
		} catch (DbException $e) {
			http_response_code(500);
		} catch (UserNotFoundException $e) {
			http_response_code(401);
		}
	}

	protected function processRequestPost($url, $data) {
		$requestData = json_decode($data, true);
		$longLink = $requestData['link'];
		try {
			$shortLinkId = $this->linkManager->addShortLink($longLink);
			$response = [ "id" => $shortLinkId ];
			http_response_code(201);
			echo json_encode($response);
		} catch (DbException $e) {
			http_response_code(500);
		} catch (UserNotFoundException $e) {
			http_response_code(401);
		}
	}
}
?>
