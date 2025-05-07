<?php
class Database {
    private static $instance = null;
    private $conn;
    private $dbType; // 'sqlite' or 'mysqli'

    private function __construct($type = 'sqlite') {
        $this->dbType = $type;
        if ($type === 'sqlite') {
            $this->conn = new SQLite3(__DIR__ . '/../database/ecommerce.db');
        } else {
            // MySQLi connection (configurable)
            $host = 'localhost';
            $username = 'root';
            $password = '';
            $database = 'ecommerce';
            $this->conn = new mysqli($host, $username, $password, $database);
            if ($this->conn->connect_error) {
                die("Connection failed: " . $this->conn->connect_error);
            }
        }
    }

    public static function getInstance($type = 'sqlite') {
        if (self::$instance === null) {
            self::$instance = new Database($type);
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->conn;
    }

    public function query($sql) {
        if ($this->dbType === 'sqlite') {
            return $this->conn->query($sql);
        } else {
            return $this->conn->query($sql);
        }
    }
}
?>
