<?php
require_once "LinksStore.php";
require_once "UserStore.php";
require_once "LinkManager.php";

class Controller {
	protected $userLogin;
	protected $userPassword;
	protected $dbConnection;
	protected $userStore;
	protected $linkStore;
	protected $linkManager;
	protected $tables = ["links" => "Links", "users" => "Users", "userLinks" => "UserLinks", "history" => "History"];

	public function __construct($dbConnection, $userLogin, $userPassword) {
		$this->userLogin = $userLogin;
		$this->userPassword = $userPassword;
		$this->dbConnection = $dbConnection;
		$this->linkStore = new LinksStore($this->dbConnection, $this->tables["links"]);
		$this->userStore = new UserStore($this->dbConnection, $this->tables["users"]);
		$this->linkManager = new LinkManager($this->dbConnection, $this->userLogin, $this->tables["userLinks"], $this->linkStore, $this->userStore, $this->tables["history"]);
	}

	public function processRequest($method, $url) {
		switch ($method) {
			case "GET":
				return $this->processRequestGet($url);
			case "POST":
				$data = file_get_contents("php://input");
				return $this->processRequestPost($url, $data);
				break;
			case "DELETE":
				return $this->processRequestDelete($url);
				break;
			default:
				http_response_code(501);
				break;
		}
	}

	protected function processRequestGet($url) {}
	protected function processRequestPost($url, $data) {}
	protected function processRequestDelete($url) {}
}
?>
