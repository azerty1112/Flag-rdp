<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/github_api.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/error_handler.php';
class FolderSync {
    private $db;
    public function __construct() {
        $this->db = Database::getInstance();
    }
    public function uploadFolder($userId, $zipFile, $folderName) {
        if ($zipFile['error'] !== UPLOAD_ERR_OK) {
            app_log_error('UPLOAD', 'فشل رفع الملف', __FILE__, __LINE__, null, ['error_code' => $zipFile['error']]);
            return ['error' => 'فشل رفع الملف', 'details' => 'يرجى التحقق من حجم الملف ورفع نسخة جديدة.', 'location' => 'FolderSync::uploadFolder'];
        }
        $ext = strtolower(pathinfo($zipFile['name'], PATHINFO_EXTENSION));
        if ($ext !== 'zip') {
            app_log_error('UPLOAD', 'تم رفض ملف غير ZIP', __FILE__, __LINE__, null, ['name' => $zipFile['name']]);
            return ['error' => 'يُسمح فقط بملفات ZIP', 'details' => 'يرجى رفع ملف ZIP صالح.', 'location' => 'FolderSync::uploadFolder'];
        }
        if ($zipFile['size'] > MAX_FILE_SIZE) {
            app_log_error('UPLOAD', 'حجم الملف يتجاوز الحد المسموح', __FILE__, __LINE__, null, ['size' => $zipFile['size']]);
            return ['error' => 'حجم الملف كبير جدًا', 'details' => 'الحد الأقصى هو ' . number_format(MAX_FILE_SIZE / 1024 / 1024, 1) . ' ميغابايت.', 'location' => 'FolderSync::uploadFolder'];
        }
        $uploadDir = __DIR__ . "/../assets/user_folders/{$userId}/";
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            app_log_error('UPLOAD', 'تعذر إنشاء مجلد المستخدم', __FILE__, __LINE__);
            return ['error' => 'تعذر إنشاء مجلد المستخدم', 'details' => 'تحقق من صلاحيات المجلدات على الخادم.', 'location' => 'FolderSync::uploadFolder'];
        }
        $uniqueName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $folderName) . '.zip';
        $targetPath = $uploadDir . $uniqueName;
        if (!move_uploaded_file($zipFile['tmp_name'], $targetPath)) {
            app_log_error('UPLOAD', 'تعذر حفظ الملف على الخادم', __FILE__, __LINE__, null, ['target' => $targetPath]);
            return ['error' => 'تعذر حفظ الملف', 'details' => 'تحقق من صلاحيات الكتابة على مجلد المستخدم.', 'location' => 'FolderSync::uploadFolder'];
        }
        $stmt = $this->db->getPDO()->prepare("INSERT INTO user_folders (user_id, folder_name, zip_file_path, created_at, updated_at) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $folderName, $targetPath, time(), time()]);
        return ['success' => true, 'folder_id' => $this->db->getPDO()->lastInsertId()];
    }
    public function getUserFolders($userId) {
        $stmt = $this->db->getPDO()->prepare("SELECT * FROM user_folders WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function createRepeatRequest($userId, $folderId, $repeatCount) {
        $folder = $this->getFolderById($folderId);
        if (!$folder) {
            return ['error' => 'المجلد المطلوب غير موجود', 'details' => 'لم يعد هذا المجلد متاحًا، جرّب اختيار مجلد آخر.', 'location' => 'FolderSync::createRepeatRequest'];
        }

        $auth = new Auth();
        $githubToken = $auth->getGithubToken($userId) ?: PLATFORM_GITHUB_TOKEN;
        if (!$githubToken) {
            return [
                'error' => 'لم يتم العثور على رمز GitHub',
                'details' => 'لا يوجد رمز GitHub صالح متاح الآن. أضف PLATFORM_GITHUB_TOKEN في متغيرات البيئة أو أعد تسجيل الدخول عبر GitHub ثم جرّب مرة أخرى.',
                'location' => 'FolderSync::createRepeatRequest'
            ];
        }

        $api = new GitHubAPI($githubToken);
        $repoName = "rdp-sync-" . bin2hex(random_bytes(4));
        $sessionId = uniqid('sess_');
        $username = $this->getUserGithubUsername($userId);
        $result = $api->createRepoFromTemplate($username, $repoName, TEMPLATE_REPO_OWNER, TEMPLATE_REPO_NAME);

        if ($result['code'] !== 201) {
            $message = $result['body']['message'] ?? 'فشل إنشاء المستودع على GitHub';
            $details = $result['body']['errors'][0]['message'] ?? 'تحقق من صلاحية الوصول أو إعدادات القالب. إذا كان الرمز غير صالح، أعد تهيئة PLATFORM_GITHUB_TOKEN.';
            app_log_error('GITHUB', 'فشل إنشاء مستودع التكرار', __FILE__, __LINE__, $message, ['code' => $result['code'], 'repo' => $repoName, 'owner' => $username, 'details' => $details]);
            return ['error' => 'فشل إنشاء المستودع', 'details' => $details, 'location' => 'FolderSync::createRepeatRequest'];
        }

        $stmt = $this->db->getPDO()->prepare("INSERT INTO repeat_requests (user_id, folder_id, repeat_count, repo_name, status, created_at) VALUES (?, ?, ?, ?, 'pending', ?)");
        $stmt->execute([$userId, $folderId, $repeatCount, $repoName, time()]);
        $requestId = $this->db->getPDO()->lastInsertId();
        $sessionResult = $this->startSession($userId, $repoName, $folderId, $sessionId, $requestId);

        if (!$sessionResult) {
            return ['error' => 'تعذر بدء الجلسة', 'details' => 'فشل تشغيل Workflow على GitHub. راجع سجل الأخطاء أو حاول مرة أخرى.', 'location' => 'FolderSync::startSession'];
        }

        return ['success' => true, 'request_id' => $requestId];
    }
    private function startSession($userId, $repoName, $folderId, $sessionId, $requestId) {
        $auth = new Auth();
        $githubToken = $auth->getGithubToken($userId) ?: PLATFORM_GITHUB_TOKEN;
        if (!$githubToken) {
            return false;
        }
        $api = new GitHubAPI($githubToken);
        $folder = $this->getFolderById($folderId);
        if (!$folder) {
            return false;
        }
        $downloadToken = md5(SECRET_KEY . $folderId);
        $folderZipUrl = APP_URL . "/download_folder.php?folder_id={$folderId}&token={$downloadToken}";
        $inputs = [
            'session_id' => $sessionId,
            'webhook_url' => APP_URL . '/webhook-receiver.php',
            'folder_zip_url' => $folderZipUrl,
            'folder_name' => $folder['folder_name'],
            'repeat_request_id' => $requestId
        ];
        $username = $this->getUserGithubUsername($userId);
        $workflowResult = $api->triggerWorkflow($username, $repoName, 'rdp-template-sync.yml', 'main', $inputs);
        if ($workflowResult['code'] !== 204) {
            $message = $workflowResult['body']['message'] ?? 'فشل تشغيل Workflow';
            $details = $workflowResult['body']['errors'][0]['message'] ?? 'تعذر بدء الجلسة على GitHub.';
            app_log_error('GITHUB', 'فشل تشغيل Workflow التكرار', __FILE__, __LINE__, $message, ['code' => $workflowResult['code'], 'repo' => $repoName, 'session_id' => $sessionId, 'details' => $details]);
            return false;
        }

        $stmt = $this->db->getPDO()->prepare("INSERT INTO sessions (user_id, repo_name, session_id, status, created_at, expires_at) VALUES (?, ?, ?, 'pending', ?, ?)");
        $stmt->execute([$userId, $repoName, $sessionId, time(), time() + SESSION_TIMEOUT]);
        $stmt = $this->db->getPDO()->prepare("UPDATE repeat_requests SET current_session_id = ?, status = 'running' WHERE id = ?");
        $stmt->execute([$sessionId, $requestId]);
        return true;
    }
    public function startNextSession($repeatRequestId) {
        $stmt = $this->db->getPDO()->prepare("SELECT * FROM repeat_requests WHERE id = ?");
        $stmt->execute([$repeatRequestId]);
        $req = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$req || $req['status'] !== 'running') return false;
        $newSessionId = uniqid('sess_');
        return $this->startSession($req['user_id'], $req['repo_name'], $req['folder_id'], $newSessionId, $repeatRequestId);
    }
    public function finalizeSession($sessionId, $finalZipUrl) {
        $stmt = $this->db->getPDO()->prepare("SELECT * FROM repeat_requests WHERE current_session_id = ?");
        $stmt->execute([$sessionId]);
        $req = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$req) return;
        $zipContent = @file_get_contents($finalZipUrl);
        if ($zipContent === false) return;
        $folder = $this->getFolderById($req['folder_id']);
        if (!$folder) return;
        $updatedDir = dirname($folder['zip_file_path']) . '/updated/';
        if (!is_dir($updatedDir)) mkdir($updatedDir, 0755, true);
        $updatedZipPath = $updatedDir . 'final_' . time() . '.zip';
        file_put_contents($updatedZipPath, $zipContent);
        $stmt = $this->db->getPDO()->prepare("UPDATE user_folders SET zip_file_path = ?, updated_at = ? WHERE id = ?");
        $stmt->execute([$updatedZipPath, time(), $req['folder_id']]);
        $newCompleted = $req['completed_count'] + 1;
        if ($newCompleted >= $req['repeat_count']) {
            $stmt = $this->db->getPDO()->prepare("UPDATE repeat_requests SET status = 'completed', completed_count = ? WHERE id = ?");
            $stmt->execute([$newCompleted, $req['id']]);
        } else {
            $stmt = $this->db->getPDO()->prepare("UPDATE repeat_requests SET completed_count = ? WHERE id = ?");
            $stmt->execute([$newCompleted, $req['id']]);
            $this->startNextSession($req['id']);
        }
        $stmt = $this->db->getPDO()->prepare("INSERT INTO updated_folders (folder_id, session_id, zip_url, local_path, created_at) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$req['folder_id'], $sessionId, $finalZipUrl, $updatedZipPath, time()]);
    }
    private function getFolderById($folderId) {
        $stmt = $this->db->getPDO()->prepare("SELECT * FROM user_folders WHERE id = ?");
        $stmt->execute([$folderId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    private function getUserGithubUsername($userId) {
        $stmt = $this->db->getPDO()->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['username'];
    }
}