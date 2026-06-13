<?php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/security.php';
class Auth {
    private $db;
    public function __construct() {
        $this->db = Database::getInstance();
    }
    public function isLoggedIn() { return isset($_SESSION['user_id']); }
    public function isAdmin() {
        $user = $this->getUser();
        if (!$user) return false;
        return !empty($user['is_admin']) || (int)$user['id'] === 1;
    }
    public function getUserId() { return $_SESSION['user_id'] ?? null; }
    public function getUser() {
        if (!$this->isLoggedIn()) return null;
        $stmt = $this->db->getPDO()->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function saveGithubToken($userId, $token) {
        $encrypted = Security::encrypt($token);
        $stmt = $this->db->getPDO()->prepare("INSERT OR REPLACE INTO user_tokens (user_id, github_token, updated_at) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $encrypted, time()]);
    }
    public function getGithubToken($userId) {
        $stmt = $this->db->getPDO()->prepare("SELECT github_token FROM user_tokens WHERE user_id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || empty($row['github_token'])) return null;

        $token = Security::decrypt($row['github_token']);
        return ($token !== false && $token !== null && $token !== '') ? $token : null;
    }
    public function loginWithGitHub($githubId, $username, $email, $token) {
        $stmt = $this->db->getPDO()->prepare("SELECT * FROM users WHERE github_id = ?");
        $stmt->execute([$githubId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            $stmt = $this->db->getPDO()->prepare("INSERT INTO users (github_id, username, email, created_at) VALUES (?, ?, ?, ?)");
            $stmt->execute([$githubId, $username, $email, time()]);
            $userId = $this->db->getPDO()->lastInsertId();
        } else {
            $userId = $user['id'];
        }
        $this->saveGithubToken($userId, $token);
        $_SESSION['user_id'] = $userId;
        return true;
    }
    public function logout() {
        session_destroy();
        header('Location: index.php');
        exit;
    }
}