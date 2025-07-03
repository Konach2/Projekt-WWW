<?php
/**
 * Konfiguracja bazy danych dla TV Time Clone
 * Autor: System
 * Data: 2025-06-29
 */

// Stałe konfiguracyjne bazy danych
define('DB_HOST', 'localhost');
define('DB_NAME', 'tvtime_clone');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Klasa do zarządzania połączeniem z bazą danych
 */
class Database {
    private static $instance = null;
    private $connection;
    
    /**
     * Konstruktor prywatny (singleton pattern)
     */
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Błąd połączenia z bazą danych: " . $e->getMessage());
        }
    }
    
    /**
     * Zwraca instancję połączenia (singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Zwraca obiekt połączenia PDO
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Zapobiega klonowaniu obiektu
     */
    private function __clone() {}
    
    /**
     * Zapobiega deserializacji obiektu
     */
    public function __wakeup() {}
}

/**
 * Funkcja pomocnicza do pobrania połączenia z bazą danych
 * @return PDO
 */
function getDB() {
    return Database::getInstance()->getConnection();
}

/**
 * Funkcja do bezpiecznego wykonywania zapytań SQL
 * @param string $sql
 * @param array $params
 * @return PDOStatement
 */
function executeQuery($sql, $params = []) {
    $db = getDB();
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/**
 * Funkcja do pobierania jednego rekordu
 * @param string $sql
 * @param array $params
 * @return array|false
 */
function fetchOne($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetch();
}

/**
 * Funkcja do pobierania wszystkich rekordów
 * @param string $sql
 * @param array $params
 * @return array
 */
function fetchAll($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetchAll();
}

/**
 * Funkcja do pobierania liczby rekordów
 * @param string $sql
 * @param array $params
 * @return int
 */
function fetchCount($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return (int)$stmt->fetchColumn();
}
?>
