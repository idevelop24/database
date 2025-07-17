<?php
namespace Framework\Library;

use pdo;
use PDOException;

class Db {
    private string $host;
    private string $username;
    private string $password;
    private string $database;
    private int $port;
    private PDO $pdo;
    private array $queryLog = [];
    private static array $instances = [];

    public function __construct(string $host, string $username, string $password, string $database, int $port = 3306) {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->database = $database;
        $this->port = $port;
        $this->connect();
    }

    public static function getInstance(string $host, string $username, string $password, string $database, int $port = 3306): self {
        $key = md5(serialize(func_get_args()));
        if (!isset(self::$instances[$key])) {
            self::$instances[$key] = new self($host, $username, $password, $database, $port);
        }
        return self::$instances[$key];
    }

    private function connect(): void {
        $dsn = "mysql:host={$this->host};dbname={$this->database};port={$this->port};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => false
        ];

        try {
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
            $this->pdo->exec("SET NAMES utf8mb4");
            $this->pdo->exec("SET time_zone = '+00:00'");
        } catch (PDOException $e) {
            throw new \RuntimeException("Connection failed: " . $e->getMessage());
        }
    }

    public function ping(): bool {
        try {
            $this->pdo->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function beginTransaction(): bool {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool {
        return $this->pdo->commit();
    }

    public function rollBack(): bool {
        return $this->pdo->rollBack();
    }

    public function query(string $sql, array $params = []): \stdClass {
        $start = microtime(true);
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            $result = new \stdClass();
            $result->row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $result->rows = $result->row ? [$result->row] : [];
            $result->num_rows = $stmt->rowCount();

            $this->queryLog[] = [
                'query' => $sql,
                'params' => $params,
                'time' => microtime(true) - $start,
                'rows' => $result->num_rows
            ];

            return $result;
        } catch (PDOException $e) {
            $this->logError($e, $sql, $params);
            throw $e;
        }
    }

    public function prepared(string $sql, array $params = []): \PDOStatement {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            $this->logError($e, $sql, $params);
            throw $e;
        }
    }

    public function lastInsertId(): string {
        return $this->pdo->lastInsertId();
    }

    public function escape(string $value): string {
        return $this->pdo->quote($value);
    }

    public function getQueryLog(): array {
        return $this->queryLog;
    }

    public function closeConnection() {
        $this->pdo = null;
    }

    private function logError(PDOException $e, string $sql = null, array $params = null): void {
        $this->queryLog[] = [
            'error' => $e->getMessage(),
            'code' => $e->getCode(),
            'sql' => $sql,
            'params' => $params,
            'trace' => $e->getTraceAsString()
        ];
    }

   /* public function __destruct() {
        $this->closeConnection();
    }*/
}