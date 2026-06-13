<?php
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/database.php';

$folder_id = (int)($_GET['folder_id'] ?? 0);
$token = (string)($_GET['token'] ?? '');

if ($token !== md5(SECRET_KEY . $folder_id)) {
    http_response_code(401);
    echo 'Unauthorized';
    exit;
}

$db = Database::getInstance();
$stmt = $db->getPDO()->prepare('SELECT zip_file_path, folder_name FROM user_folders WHERE id = ?');
$stmt->execute([$folder_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row || !isset($row['zip_file_path']) || !is_file($row['zip_file_path'])) {
    http_response_code(404);
    echo 'Folder not found';
    exit;
}

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . rawurlencode($row['folder_name'] ?: 'folder') . '.zip"');
header('Content-Length: ' . filesize($row['zip_file_path']));
readfile($row['zip_file_path']);
exit;
