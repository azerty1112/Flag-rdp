<?php
define('APP_NAME', 'RDP Orchestrator Pro');
define('APP_URL', 'https://yourdomain.com');
define('GITHUB_CLIENT_ID', 'client_id');
define('GITHUB_CLIENT_SECRET', 'client_secret');
define('GITHUB_REDIRECT_URI', APP_URL . '/github-callback.php');
define('PLATFORM_GITHUB_TOKEN', '');
define('TEMPLATE_REPO_OWNER', 'github_username');
define('TEMPLATE_REPO_NAME', 'rdp-template-sync');
define('DB_PATH', __DIR__ . '/../../database/sessions.db');
define('SESSION_TIMEOUT', 6 * 3600);
define('MAX_REPEAT_SESSIONS', 10);
define('MAX_FILE_SIZE', 500 * 1024 * 1024);
define('SECRET_KEY', 'your-32-byte-secret-key');
session_name('RDP_ORCH');
session_start();
require_once __DIR__ . '/error_handler.php';