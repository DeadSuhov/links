<?php
/**
 * Класс ShortUrlsManager перенаправляет пользователя по длинному адресу
 * 
 * При получении информации о короткой ссылке (ее длинного аналога) с помощью
 * метода {@link #redirect()} заносит информацию о переходе по этой ссылке в
 * БД.
 * 
 * Схема таблицы с историей переходов должна быть следующей:
 * ```
 * link int not null
 * date date not null
 * referer text
 * ```
 * Поле `link` - идентификатор ссылки, о которой сохраняется история. Должно быть
 * связано с полем id таблицы, управляемой {@link LinkStore }-менеджером
 * 
 * Для хранения истории переходов использует БД MySql, коннектор к которой
 * должен передаваться при создании объекта класса.
 * 
 * Для получения информации о длинных ссылках использует объект LinkStore, который
 * так же передается при создании объекта ShortUrlsManager.
 * 
 * ! Внимание !
 * При выполнении запросов к БД имя БД не указывается. Поэтому оно должно быть
 * задано при создании объекта коннектора.
 */
class ShortUrlsManager {
	/**
	 * Коннектор к БД MySql
	 * @var mysqli
	 */
	protected $dbConnection;
	
	/**
	 * Имя таблицы, в которой хранится история переходов
	 * @var string
	 */
	protected $historyTableName;
	
	/**
	 * Хранилище информации о ссылках
	 * @var LinkStore
	 */
	protected $linkStore;
	
	/**
	 * При создании менеджера коротких ссылок необходимо передать объект соединения
	 * с БД, имя таблицы в которой хранится информация о переходах а так же
	 * объект LinkStore, позволяющий сохранять и получать информацию о ссылках.
	 * 
	 * @param mysqli $dbConnection коннектор к БД MySql
	 * @param string $historyTableName имя таблицы, в которой хранится история переходов по ссылкам
	 * @param LinkStore $linkStore хранилище ссылок
	 */
	public function __construct($dbConnection, $historyTableName, $linkStore) {
		$this->dbConnection = $dbConnection;
		$this->historyTableName = $history;
		$this->linkStore = $linkStore;
	}
	
	/**
	 * Перенаправляет пользователя по адресу, который был сохранен при создании
	 * короткой ссылки, идентификатор которой передан в качестве параметра.
	 * 
	 * Если запись о ссылке с указанным идентификатором отсутствует возвращает
	 * пользователю 404-ую ошибку.
	 *
	 * @param string $linkId идентификатор ссылки, по которой пришёл пользователь
	 * @return none
	 * @throws DbException если при создании пользователя возникли ошибки, связанные
	 *                     с подключением или некорректно составленным запросом
	 */
	public function redirect($linkId) {
		$longLink = $linkStore->getLinkById($linkId);
		$referer = $_SERVER["HTTP_REFERER"];
		$query = "insert into {$this->historyTableName} (linkId, referer, date) values (\"{$linkId}\", \"{$referer}\", now()";
		$queryResult = $this->dbConnection->query($query);
		if ($queryResult === FALSE) {
			throw new DbException($this->dbConnection->errno);
		} else {
			if ($longLink == "") {
				http_response_code(404);
			} else {
				http_response_code(302);
				header("Location: {$longLink}");
			}
		}
	}
}
?>