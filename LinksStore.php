<?php
/**
 * Класс описывает хранилище ссылок и предоставляет интерфейс для работы с ним.
 *
 * Для хранения использует БД MySql, коннектор к которой передается при создании
 * объекта класса. Так же в конструктор необходимо передать имя таблицы, в
 * которой будет сохраняться информация о ссылках.
 *
 * Схема таблицы должна быть следующей:
 * ```
 * id int not null primary key auto_increment
 * link varchar not null
 * ```
 *
 * ! Внимание !
 * При выполнении запросов к БД имя БД не указывается. Поэтому оно должно быть
 * задано при создании объекта коннектора.
 */
class LinksStore {
	/**
	 * Коннектор к БД MySql
	 * @var mysqli
	 */
	protected $dbConnection;

	/**
	 * Имя таблицы, в которой хранятся записи о ссылках
	 * @var string
	 */
	protected $linksTableName;

	/**
	 * При создании объекта хранилища в конструктор необходимо передать объект
	 * соединения с БД и имя таблицы, в которой будут сохраняться данные о
	 * длинных ссылках.
	 *
	 * @param mysqli $dbConnection коннектор к БД MySql
	 * @param string $linksTableName имя таблицы, в которой будет сохраняться информация о ссылках
	 */
	public function __construct($dbConnection, $linksTableName) {
		$this->dbConnection = $dbConnection;
		$this->linksTableName = $linksTableName;
	}

	/**
	 * Сохраняет указанную ссылку в хранилище и возвращает идентификатор, по
	 * которому можно получить значение ссылки.
	 *
	 * Если функция вернула отрицательное значение, то это указывает на ошибку
	 * при выполнении запроса.
	 *
	 * @param string $longLink длинная ссылка, котрую небходимо сохранить в БД
	 * @return int идентификатор сохраненной ссылки
	 */
	public function addLink($longLink) {
		/*
		 * Логика работы:
		 * 1. Формируем строку запроса к БД
		 * 2. С помощью объекта dbConnection, который передали при создании
		 *    хранилища, выполняем запрос и сохраняем результат в переменную.
		 *    О том, что можно получить в результате выполнения запроса
		 *    можно посмотреть на странице http://php.net/manual/ru/class.mysqli-result.php
		 * 3. Анализируем результат запроса. Если он не равен TRUE, значит при
		 *    выполнении запроса произошли какие-то ошибки. В этом случае вместо
		 *    идентификатора новой ссылки возвращаем код ошибки (только отрицательный).
		 *    Если же запрос прошёл удачно, то возвращаем идентификатор вставленной
		 *    записи ($this->dbConnection->insert_id).
		 */
		$encodedLink = base64_encode($longLink);
		$query = "insert into {$this->linksTableName} (link) values (\"{$encodedLink}\")";
		$queryResult = $this->dbConnection->query($query);
		if ($queryResult !== TRUE) {
			return (-$this->dbConnection->errno);
		} else {
			return $this->dbConnection->insert_id;
		}
	}

	/**
	 * По указанному идентификатору ссылки возвращает полную ссылку.
	 * 
	 * Если указан несуществующий идентификатор ссылки, то вернет пустую строку.
	 * Если при выполнении запроса возникли ошибки - вернет отрицательное число.
	 * 
	 * @param int $longLinkId идентификатор ранее сохраненной ссылки
	 * @return string полный текст ранее сохраненной ссылки
	 * @throws DbException если при создании пользователя возникли ошибки, связанные
	 *                     с подключением или некорректно составленным запросом
	 * @throws LinkNotFoundException если ссылка с указанным идентификатором не найдена
	 */
	public function getLinkById($longLinkId) {
		/*
		 * Логика работы:
		 * 1. Формируем строку запроса.
		 * 2. С помощью объекта dbConnection, который передали при создании
		 *    хранилища, выполняем запрос и сохраняем результат в переменную.
		 *    О том, что можно получить в результате выполнения запроса
		 *    можно посмотреть на странице http://php.net/manual/ru/class.mysqli-result.php
		 * 3. Анализируем результат запроса. Если он приводится к FALSE (null, 0, false) -
		 *    возвращаем код ошибки сервера (только отрицательный). Если же в
		 *    результате запроса вернулся mysqli_result, то запрашиваем у него
		 *    первую строку из набора строк таблицы. Если первая строка существует -
		 *    берем значение первой колонки и возвращаем клиенту.
		 */
		$query = "select link from {$this->linksTableName} where id = \"{$longLinkId}\"";
		$queryResult = $this->dbConnection->query($query);
		$result = "";
		if ($queryResult === FALSE) {
			throw new DbException($thid->dbConnection->error, $this->dbConnection->errno);
		} else {
			if ($resultRow = $queryResult->fetch_row()) {
				$result = base64_decode($resultRow[0]);
			} else {
				throw new LinkNotFoundException($longLinkId);
			}
		}
		return $result;
	}

	/**
	 * Удаляет ссылку с указанным идентификатором.
	 * 
	 * Предварительная проверка на существование ссылки с указанным идентификатором
	 * не производится. В случае ее отсутствия никаких дополнительных действий
	 * не производится.
	 *
	 * @param int $longLinkId идентификатор ссылки, которую нужно удалить
	 * @throws DbException если при создании пользователя возникли ошибки, связанные
	 *                     с подключением или некорректно составленным запросом
	 */
	public function deleteLinkById($longLinkId) {
		$query = "delete from {$this->linksTableName} where id = \"{$longLinkId}\"";
		$queryResult = $this->dbConnection->query($query);
		if ($queryResult === FALSE) {
			throw new DbException($this->dbConnection->error, $this->dbConnection->errno);
		}
	}
}
?>
