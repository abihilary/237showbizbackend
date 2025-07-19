<?php

class Database {
    private $host = 'localhost';
    private $db   = 'api_237showbiz';
    private $user = 'api_237Showbiz';
    private $pass = '2025#Api2025';
    private $charset = 'utf8mb4';
    private $pdo;

    public function __construct() {
        $dsn = "mysql:host={$this->host};dbname={$this->db};charset={$this->charset}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        try {
            $this->pdo = new PDO($dsn, $this->user, $this->pass, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Database connection failed',
                'details' => $e->getMessage()
            ]);
            exit;
        }
    }

    public function runQuery(string $sql, array $params = []) {
    try {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        if (preg_match('/^\s*(SELECT|SHOW|DESCRIBE|PRAGMA)/i', $sql)) {
            return $stmt->fetchAll();
        } else {
            return $stmt->rowCount();
        }
    } catch (PDOException $e) {
        error_log('Database error: ' . $e->getMessage());
        return false; // Always return false on failure
    }
}

    // Add this method:
    public function prepare($sql) {
        return $this->pdo->prepare($sql);
    }
}
