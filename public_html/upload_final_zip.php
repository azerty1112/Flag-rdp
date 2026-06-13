<?php
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/error_handler.php';

$secret = $_GET['secret'] ?? '';
if ($secret !== md5(SECRET_KEY)) {
    http_response_code(401);
    echo 'Unauthorized';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        app_log_error('UPLOAD', 'فشل استقبال الملف النهائي من GitHub Actions', __FILE__, __LINE__, null, ['error_code' => $file['error']]);
        http_response_code(400);
        echo 'Upload failed';
        exit;
    }

    $content = @file_get_contents($file['tmp_name']);
    if ($content === false) {
        app_log_error('UPLOAD', 'تعذر قراءة الملف المرسل', __FILE__, __LINE__, null, ['name' => $file['name']]);
        http_response_code(500);
        echo 'Read failed';
        exit;
    }

    $sessionId = $_POST['session_id'] ?? '';
    $repeatRequestId = $_POST['repeat_request_id'] ?? '';
    $tempFile = sys_get_temp_dir() . "/final_zip_{$sessionId}.zip";
    if (@file_put_contents($tempFile, $content) === false) {
        app_log_error('UPLOAD', 'تعذر حفظ الملف المؤقت', __FILE__, __LINE__, null, ['path' => $tempFile]);
        http_response_code(500);
        echo 'Save failed';
        exit;
    }

    $ch = curl_init(rtrim(APP_URL, '/') . '/webhook-receiver.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'session_id' => $sessionId,
        'final_zip_url' => 'file://' . $tempFile,
        'repeat_request_id' => $repeatRequestId,
        'status' => 'finalized'
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($response === false || $httpCode >= 400 || $curlErr !== '') {
        app_log_error('WEBHOOK', 'فشل إرسال الملف النهائي إلى webhook', __FILE__, __LINE__, $curlErr ?: 'HTTP ' . $httpCode, ['session_id' => $sessionId, 'http_code' => $httpCode]);
        http_response_code($httpCode >= 400 ? $httpCode : 500);
        echo 'Webhook failed';
        exit;
    }

    echo 'OK';
} else {
    http_response_code(405);
    echo 'Method not allowed';
}