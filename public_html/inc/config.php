<?php
if (!function_exists('env')) {
    function env($key, $default = null) {
        $value = getenv($key);
        if ($value === false || $value === '') return $default;
        return $value;
    }
}

define('APP_NAME', env('APP_NAME', 'RDP Orchestrator Pro'));
define('APP_URL', env('APP_URL', 'http://localhost'));
define('GITHUB_CLIENT_ID', env('GITHUB_CLIENT_ID', 'client_id'));
define('GITHUB_CLIENT_SECRET', env('GITHUB_CLIENT_SECRET', 'client_secret'));
define('GITHUB_REDIRECT_URI', APP_URL . '/github-callback.php');
define('PLATFORM_GITHUB_TOKEN', env('PLATFORM_GITHUB_TOKEN', ''));
define('TEMPLATE_REPO_OWNER', env('TEMPLATE_REPO_OWNER', 'github_username'));
define('TEMPLATE_REPO_NAME', env('TEMPLATE_REPO_NAME', 'rdp-template-sync'));
define('DB_PATH', env('DB_PATH', __DIR__ . '/../../database/sessions.db'));
define('SESSION_TIMEOUT', (int)env('SESSION_TIMEOUT', 6 * 3600));
define('MAX_REPEAT_SESSIONS', (int)env('MAX_REPEAT_SESSIONS', 10));
define('MAX_FILE_SIZE', (int)env('MAX_FILE_SIZE', 500 * 1024 * 1024));
define('SECRET_KEY', env('SECRET_KEY', 'change-me-32-byte-secret'));
if (session_status() === PHP_SESSION_NONE) {
    session_name(env('SESSION_NAME', 'RDP_ORCH'));
    session_start();
}
require_once __DIR__ . '/error_handler.php';