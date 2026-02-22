<?php
class Database {
    private static ?PDO $connection = null;
    public static function connection(): PDO {
        if (self::$connection instanceof PDO) return self::$connection;
        $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
        $port = (int)($_ENV['DB_PORT'] ?? 3306);
        $db = $_ENV['DB_DATABASE'] ?? 'idea_foundation_db';
        $user = $_ENV['DB_USERNAME'] ?? 'root';
        $pass = $_ENV['DB_PASSWORD'] ?? '';
        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ];
        self::$connection = new PDO($dsn, $user, $pass, $options);
        return self::$connection;
    }
}
