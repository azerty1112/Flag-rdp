<?php
require_once __DIR__ . '/inc/config.php';
$secret = $_GET['secret'] ?? '';
if ($secret !== md5(SECRET_KEY)) die('Unauthorized');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $content = file_get_contents($file['tmp_name']);
        $sessionId = $_POST['session_id'] ?? '';
        $repeatRequestId = $_POST['repeat_request_id'] ?? '';
        $tempFile = sys_get_temp_dir() . "/final_zip_{$sessionId}.zip";
        file_put_contents($tempFile, $content);
        $ch = curl_init(APP_URL . "/webhook-receiver.php");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'session_id' => $sessionId,
            'final_zip_url' => 'file://' . $tempFile,
            'repeat_request_id' => $repeatRequestId
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_exec($ch);
        curl_close($ch);
        echo "OK";
    } else {
        http_response_code(400);
        echo "Upload failed";
    }
} else {
    http_response_code(405);
}