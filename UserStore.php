<?php
require_once "DbException.php";
require_once "UserNotFoundException.php";

/**
 * Класс UserStore позволяет управлять учетными записями пользователей.
 *
 * Для хранения информации о пользователях использует БД MySql, коннектор к
 * которой должен быть передан при создании объекта UserStore. Так же при создании
 * объекта UserStore необходимо передать имя таблицы, в которой будет храниться
 * информация о пользователях.
 *
 * Схема таблицы должна быть следующей:
 * ```
 * id int not null primary key auto_increment
 * login varchar(64) not null unique
 * password varchar(64) not null
 * ```
 *
 * ! Внимание !
 * При выполнении запроса к БД имя БД не указывается! Поэтому должно быть указано
 * при создании объекта коннектора.
 */
class UserStore {
	/**
	 * Коннектор к БД MySql
	 * @var mysqli
	 */
	protected $dbConnection;
	
	/**
	 * Имя таблицы, в которой хранятся записи о пользователях
	 * @var string
	 */
	protected $usersTableName;

	/**
	 * При создании объекта хранилища в конструктор нужно передать объект
	 * соединения с БД и имя таблицы, в которой будет хранится информация
	 * о пользователях.
	 *
	 * @param mysqli $dbConnector коннектор к БД MySql
	 * @param string $usersTableName имя таблицы, в которой будет храниться информация о пользователях
	 */
	public function __construct($dbConnection, $usersTableName) {
		$this->dbConnection = $dbConnection;
		$this->usersTableName = $usersTableName;
	}
	
	/**
	 * Регистрирует нового пользователя.
	 * 
	 * По факту - заносит новую запись в таблицу с пользователями.
	 * 
	 * @param string login    логин нового пользователя
	 * @param string password пароль нового пользователя
	 * @return int идентификатор нового пользователя
	 * @throws DbException если при создании пользователя возникли ошибки, связанные
	 *                     с подключением или некорректно составленным запросом
	 */
	public function regNewUser($login, $password) {
		$query = "insert into {$this->usersTableName} (login, password) values (\"{$login}\", \"{$password}\")";
		$queryResult = $this->dbConnection->query($query);
		if ($queryResult !== TRUE) {
			throw new DbException($this->dbConnection->error, $this->dbConnection->errno);
		} else {
			return $this->dbConnection->insert_id;
		}
	}
	
	/**
	 * Возвращает идентификатор пользователя по логину.
	 * 
	 * @param string $login логин пользователя
	 * @return int идентификатор пользователя
	 * @throws DbException если при вывполнении поиска в БД возникли ошибки, связанные с подключением
	 *                     или некорректно составленным запросом
	 * @throws UserNotFoundException если в БД нет записи о пользователе с указанным логином
	 */
	public function getIdByLogin($login) {
		$query = "select Id from {$this->usersTableName} where login = \"{$login}\"";
		$queryResult = $this->dbConnection->query($query);
		$userId = 0;
		if ($queryResult === FALSE) {
			throw new DbException($this->dbConnection->error, $this->dbConnection->errno);
		} else {
			if ($resultRow = $queryResult->fetch_row()) {
				$userId = $resultRow[0];
			} else {
				throw new UserNotFoundException($login);
			}
		}
		return $userId;
	}
}
?>
