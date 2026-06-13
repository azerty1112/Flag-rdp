<?php
require_once __DIR__ . '/inc/config.php';
$folder_id = intval($_GET['folder_id'] ?? 0);
$token = $_GET['token'] ?? '';
if ($token !== md5(SECRET_KEY . $folder_id)) die('Unauthorized');
$db = Database::getInstance();
$stmt = $db->getPDO()->prepare("SELECT zip_file_path FROM user_folders WHERE id = ?");
$stmt->execute([$folder_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row && file_exists($row['zip_file_path'])) {
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="folder.zip"');
    readfile($row['zip_file_path']);
} else {
    http_response_code(404);
    echo "Folder not found";
}