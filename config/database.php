<?php
declare(strict_types=1);

namespace Config;

use PDO;
use PDOException;

class Database {
    private static ?Database $instance = null;
    private PDO $connection;

    private string $host = 'localhost';
    private string $db_name = 'college_cms';
    private string $username = 'root'; // Adjust credentials as appropriate
    private string $password = '';
    private string $charset = 'utf8mb4';

    /**
     * Private constructor to enforce Singleton pattern
     */
    private function __construct() {
        $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false, // Ensures real prepared statements are used
        ];

        try {
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            // Log this securely in a production app rather than outputting to end user
            error_log("Database Connection failed: " . $e->getMessage());
            die("Critical Error: Unable to connect to the database. Please contact system administrator.");
        }
    }

    /**
     * Get singleton instance of Database
     *
     * @return Database
     */
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    /**
     * Retrieve the PDO connection instance
     *
     * @return PDO
     */
    public function getConnection(): PDO {
        return $this->connection;
    }

    // Prevent cloning and unserializing
    private function __clone() {}
    public function __wakeup() {}
}
