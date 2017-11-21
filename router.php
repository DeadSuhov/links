<?php
require_once "UserRequestController.php";
require_once "ShortUrlsController.php";
require_once "ShortUrlController.php";
require_once "RedirectController.php";

$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestUrl = $_SERVER['REQUEST_URI'];

$dbConnection = new mysqli('localhost', 'links', 'links', 'links');
if ($dbConnection->connect_errno != 0) {
	http_response_code(500);
	exit;
}

if (preg_match('|^/api/v1/shorten_urls/(\d+)$|', $requestUrl, $matches)) {
	// GET /api/v1/shorten_urls/{hash} - переход по ссылке (302 redirect)
	// Обрабатывается от остальных url-ов, т.к. не нужна авторизация.
	$linkId = $matches[1];
	if ($requestMethod == "GET") {
		$controller = new RedirectController($dbConnection, "", "");
		$controller->processRequest($requestMethod, $linkId);
	} else {
		http_response_code(501);
	}
	exit;
}

if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) {
	http_response_code(401);
} else {
	$userLogin = $_SERVER['PHP_AUTH_USER'];
	$userPassword = $_SERVER['PHP_AUTH_PW'];

	switch ($requestUrl) {
		case "/api/v1/users": {
			if ($requestMethod == "POST") {
				$controller = new UserRequestController($dbConnection, $userLogin, $userPassword);
				$controller->processRequest("POST", $requestUrl);
			} else {
				http_response_code(501);
			}
			break;
		};
		case "/api/v1/users/me": {
			if ($requestMethod == "GET") {
				$controller = new UserRequestController($dbConnection, $userLogin, $userPassword);
				$controller->processRequest("GET", $requestUrl);
			}
			break;
		};
		case "/api/v1/users/me/shorten_urls": {
			if ($requestMethod == "GET" or $requestMethod == "POST") {
				$controller = new ShortUrlsController($dbConnection, $userLogin, $userPassword);
				$controller->processRequest($requestMethod, $requestUrl);
			}
			break;
		};
		default: {
			if (preg_match('|^/api/v1/users/me/shorten_urls/(\d+)$|', $requestUrl, $matches)) {
				// GET /api/v1/users/me/shorten_urls/{id} - получение информации о конкретной короткой ссылке пользователя
				// DELETE /api/v1/users/me/shorten_urls/{id} - удаление короткой ссылки
				$linkId = $matches[1];
				if ($requestMethod == "GET" || $requestMethod == "DELETE") {
					$controller = new ShortUrlController($dbConnection, $userLogin, $userPassword);
					$controller->processRequest($requestMethod, $linkId);
				} else {
					http_response_code(501);
				}
			} else {
				http_response_code(404);
			}
		}
	}
}

?>
