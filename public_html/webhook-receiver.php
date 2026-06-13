<?php
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/database.php';
require_once __DIR__ . '/inc/folder_sync.php';
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) die('no data');
$sessionId = $input['session_id'] ?? '';
if (isset($input['final_zip_url'])) {
    $finalZipUrl = $input['final_zip_url'];
    $folderSync = new FolderSync();
    $folderSync->finalizeSession($sessionId, $finalZipUrl);
    http_response_code(200);
    echo 'finalized';
    exit;
}
$host = $input['host'] ?? '';
$username = $input['username'] ?? '';
$password = $input['password'] ?? '';
$status = $input['status'] ?? 'ready';
$db = Database::getInstance();
$stmt = $db->getPDO()->prepare("UPDATE sessions SET host = ?, username = ?, password = ?, status = ? WHERE session_id = ?");
$stmt->execute([$host, $username, $password, $status, $sessionId]);
http_response_code(200);
echo 'ok';