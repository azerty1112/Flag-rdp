<?php
if (!defined('DB_PATH')) {
    if (!function_exists('env')) {
        function env($key, $default = null) {
            $value = getenv($key);
            return ($value === false || $value === '') ? $default : $value;
        }
    }

    define('DB_PATH', env('DB_PATH', __DIR__ . '/../../database/sessions.db'));
}

class Database {
    private static $instance = null;
    private $pdo;
    private function __construct() {
        $dir = dirname(DB_PATH);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $this->pdo = new PDO('sqlite:' . DB_PATH);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->createTables();
    }
    public static function getInstance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }
    private function createTables() {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                github_id INTEGER UNIQUE,
                username TEXT,
                email TEXT,
                created_at INTEGER
            );
            CREATE TABLE IF NOT EXISTS user_tokens (
                user_id INTEGER PRIMARY KEY,
                github_token TEXT,
                updated_at INTEGER
            );
            CREATE TABLE IF NOT EXISTS sessions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                repo_name TEXT,
                session_id TEXT UNIQUE,
                host TEXT,
                username TEXT,
                password TEXT,
                status TEXT,
                created_at INTEGER,
                expires_at INTEGER
            );
            CREATE TABLE IF NOT EXISTS user_folders (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                folder_name TEXT NOT NULL,
                zip_file_path TEXT,
                created_at INTEGER,
                updated_at INTEGER
            );
            CREATE TABLE IF NOT EXISTS repeat_requests (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                folder_id INTEGER,
                repeat_count INTEGER,
                completed_count INTEGER DEFAULT 0,
                current_session_id TEXT,
                repo_name TEXT,
                status TEXT,
                created_at INTEGER
            );
            CREATE TABLE IF NOT EXISTS updated_folders (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                folder_id INTEGER,
                session_id TEXT,
                zip_url TEXT,
                local_path TEXT,
                created_at INTEGER
            );
            CREATE TABLE IF NOT EXISTS error_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                error_type TEXT,
                message TEXT,
                file_path TEXT,
                line_number INTEGER,
                trace TEXT,
                request_uri TEXT,
                created_at INTEGER,
                resolved INTEGER DEFAULT 0
            );
        ");
    }
    public function getPDO() { return $this->pdo; }
}