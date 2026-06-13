<?php
require_once __DIR__ . '/database.php';

function app_log_error($type, $message, $file = null, $line = null, $trace = null, $context = []) {
    try {
        $db = Database::getInstance();
        $stmt = $db->getPDO()->prepare(
            'INSERT INTO error_logs (user_id, error_type, message, file_path, line_number, trace, request_uri, created_at, resolved) ' .
            'VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)'
        );
        $userId = $_SESSION['user_id'] ?? null;
        $stmt->execute([
            $userId,
            $type,
            substr((string)$message, 0, 2000),
            $file,
            $line,
            $trace ? substr($trace, 0, 4000) : null,
            $_SERVER['REQUEST_URI'] ?? null,
            time(),
        ]);
    } catch (Throwable $e) {
        error_log('Error logger failed: ' . $e->getMessage());
    }
}

function app_handle_error($severity, $message, $file = '', $line = 0) {
    $levels = [E_ERROR => 'ERROR', E_PARSE => 'PARSE', E_CORE_ERROR => 'CORE_ERROR', E_COMPILE_ERROR => 'COMPILE_ERROR', E_USER_ERROR => 'USER_ERROR'];
    if (!(error_reporting() & $severity)) return false;
    $type = $levels[$severity] ?? 'PHP_ERROR';
    app_log_error($type, $message, $file, $line, debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3));
    return true;
}

function app_handle_exception(Throwable $e) {
    app_log_error('EXCEPTION', $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString());
    http_response_code(500);
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=UTF-8');
    }
    echo '<!doctype html><html dir="rtl"><head><meta charset="UTF-8"><title>خطأ في التطبيق</title></head><body style="font-family:Arial,sans-serif;padding:24px;">';
    echo '<h2>حدث خطأ غير متوقع</h2>';
    echo '<p>تم تسجيل التفاصيل تلقائيًا في لوحة الإدارة.</p>';
    echo '<p><a href="dashboard.php">العودة للوحة</a></p>';
    echo '</body></html>';
    exit(1);
}

function app_handle_shutdown() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
        app_log_error('FATAL', $error['message'], $error['file'], $error['line'], null);
    }
}

set_error_handler('app_handle_error');
set_exception_handler('app_handle_exception');
register_shutdown_function('app_handle_shutdown');
