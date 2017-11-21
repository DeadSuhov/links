<?php
require_once "DbException.php";
require_once "UnknownTimeRangeException.php";

class LinkManager {
	/**
	 * Коннектор к БД MySql
	 * @var mysqli
	 */
	protected $dbConnection;
	
	/**
	 * Имя пользователя
	 * @var string
	 */
	protected $userLogin;
	
	/**
	 * Хранилищие информации о ссылках
	 * @var LinkStore
	 */
	protected $linkStore;
	
	/**
	 * Хранилище информации о пользователях
	 * @var UserStore
	 */
	protected $userStore;

	/**
	 * Имя таблицы, в которой хранятся связь пользователя иcточников его ссылок.
	 * @var string
	 */
	protected $userLinksTableName;
	
	/**
	 * Имя таблицы, в которой хранится история переходов
	 * @var string
	 */
	protected $historyTableName;

	public function __construct($dbConnection, $userLogin, $userLinksTableName, $linkStore, $userStore, $historyTableName) {
		$this->linkStore = $linkStore;
		$this->dbConnection = $dbConnection;
		$this->userLinksTableName = $userLinksTableName;
		$this->historyTableName = $historyTableName;
		$this->userLogin = $userLogin;
		$this->userStore = $userStore;
	}
	
	// Функция которая выдает короткую ссылку
	public function addShortLink($longLink) {
		$this->dbConnection->autocommit(FALSE);
		$this->dbConnection->begin_transaction();
		$longLinkId = $this->linkStore->addLink($longLink);
		$userId = $this->userStore->getIdByLogin($this->user);
		$query = "insert into {$this->userLinksTableName} (user, link) values (\"{$userId}\",\"{$longLinkId}\")";
		$queryResult = $this->dbConnection->query($query);
		if ($queryResult !== TRUE) {
			$dbConnection->rollback();
			$dbConnection->autocommit(TRUE);
			throw new DbException($this->dbConnection->error, $this->dbException->errno);
		} else {
			$this->dbConnection->commit();
			$this->dbConnection->autocommit(TRUE);
			return $longLinkId;
		}
	}
	
	// Функция которая выдает все короткие ссылки пользователю по его UsersId
	public function getUserLinks() {
		$userId = $this->userStore->getIdByLogin($this->user);
		$query = "select link from {$this->userLinksTableName} where user =\"{$userId}\"";
		$queryResult = $this->dbConnection->query($query);
		$arrLink = [];
		if ($queryResult === FALSE) {
			throw new DbException($this->dbConnection->error, $this->dbConnection->errno);
		} else {
			while ($resultRow = $queryResult->fetch_row()) {
				$arrLink[] = $resultRow[0];
			}
			return $arrLink;
		}
	}
	
	// Функия которая выдает конкретную короткую ссылку пользователя и кол-во переходов по ней
	public function getLinkInfo($linkId) {
		$query = "select count(*) from {$this->historyTableName} where link = \"{$linkId}\"";
		$queryResult = $this->dbConnection->query($query);
		$result = 0;
		if ($queryResult === FALSE) {
			throw new DbException($this->dbConnection->error, $this->dbConnection->errno);
		} else {
			$resultRow = $queryResult->fetch_row();
			$result = $resultRow[0];
		}

		$longLink = $this->linkStore->getLinkById($linkId);
		return ["link" => $longLink, "count" => $result];
	}
	
	// 1. DELETE /api/v1/users/me/shorten_urls/{id}- удаление короткой ссылки пользователя
	public function deleteShortLink($linkId) {
		$this->linkStore->deleteLinkById($linkId);
	}
	
	// 2. GET /api/v1/users/me/shorten_urls/{id}/[days,hours,min]?from_date=0000-00-00&to_date=0000-00-00 - 
	// получение временного графика количества переходов с группировкой по дням, часам, минутам.
	/**
	 * Таблица historyTableName 
	 * ```
	 * linkId int
	 * date date
	 * referer string
	 * ```
	 */
	public function dateReport($linkId, $dateRangeType, $fromDate, $toDate) {
		$dateFormat = "";
		$result = [];
		if ($dateRangeType == "days") {
			$dateFormat = "%Y-%m-%d";
		} else if ($dateRangeType == "hours") {
			$dateFormat = "%Y-%m-%d %H";
		} else if ($dateRangeType == "min") {
			$dateFormat = "%Y-%m-%d %H:%M";
		} else {
			throw new UnknownTimeRangeException($dateRangeType);
		}
		
		$query = "select date_format(date, \"{$dateFormat}\") as aggregatedDate, count(*) as count from {$this->historyTableName} where link = \"{$linkId}\" and date > \"{$fromDate}\" and date < \"{$toDate}\" group by aggregatedDate";
		$qeuryResult = $this->dbConnection->query($query);
		if ($queryResult === FALSE) {
			throw new DbException($this->dbConnection->error, $this->dbConnection->errno);
		} else {
			while ($sqlResultRow = $queryResult->fetchRow()) {
				$resultRow = [];
				$resultRow["date"] = $sqlResultRow[0];
				$resultRow["count"] = $sqlResultRow[1];
				$result[] = $resultRow;
			}
		}
		return result;
	}
	
	// 3. GET /api/v1/users/me/shorten_urls/{id}/referers - получение топа из 20 сайтов иcточников переходов
	/**
	 * @return array ассоциативный массив с ключами:
	 *         'referer' - адрес сайта, с которого пришёл посетитель
	 *         'count'   - кол-во переходов с этого сайта
	 */
	public function topReferers($linkId) {
		$query = "select count(*) as redirectCount, referer from {$this->historyTableName} where link = \"{$linkId}\" group by referer order by redirectCount limit 20";
		$queryResult = $this->dbConnection->query($query);
		$result = [];
		if ($queryResult === FALSE) {
			throw new DbException($this->dbConnection->error, $this->dbConnection->errno);
		} else {
			while ($sqlResultRow = $queryResult->fetchRow()) {
				$resultRow = [];
				$resultRow['count'] = $sqlResultRow[0];
				$resultRow['referer'] = $sqlResultRow[1];
			}
		}
		return $result;
	}

	public function getLinkForRedirect($linkId, $referer) {
		$longLink = $this->linkStore->getLinkById($linkId);
		$query = "insert into {$this->historyTableName} (link, referer, date) values (\"{$linkId}\", \"{$referer}\", now())";
		$queryResult = $this->dbConnection->query($query);
		if ($queryResult === FALSE) {
			throw new DbException($this->dbConnection->error, $this->dbConnection->errno);
		} else {
			return $longLink;
		}
	}
}
?>
