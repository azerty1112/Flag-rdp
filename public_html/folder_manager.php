<?php
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/folder_sync.php';
$auth = new Auth();
if (!$auth->isLoggedIn()) header('Location: index.php');
$user = $auth->getUser();
$folderSync = new FolderSync();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['folder_zip'])) {
    $result = $folderSync->uploadFolder($user['id'], $_FILES['folder_zip'], $_POST['folder_name']);
    $msg = $result['error'] ?? ($result['success'] ? '✅ تم رفع المجلد بنجاح وسيظهر في لوحة الإدارة' : '❌ حدث خطأ أثناء الرفع');
    $type = !empty($result['success']) ? 'success' : 'error';
    if (!empty($result['details'])) {
        $msg .= ' — ' . $result['details'];
    }
}
if (isset($_POST['repeat_sync'])) {
    $folderId = intval($_POST['folder_id']);
    $repeatCount = intval($_POST['repeat_count']);
    if ($repeatCount >= 1 && $repeatCount <= MAX_REPEAT_SESSIONS) {
        $requestId = $folderSync->createRepeatRequest($user['id'], $folderId, $repeatCount);
        $msg = $requestId ? "✅ بدأت أول جلسة من {$repeatCount}" : "❌ فشل";
        $type = 'info';
    } else $msg = "عدد التكرار غير صالح";
}
$folders = $folderSync->getUserFolders($user['id']);
?>
<!DOCTYPE html>
<html dir="rtl">
<head><title>المجلدات المتزامنة</title><link rel="stylesheet" href="assets/css/style.css"></head>
<body>
    <div class="container">
        <h1>📁 المجلدات المتزامنة</h1>
        <a href="dashboard.php" class="back-link">← العودة</a>
        <?php if (isset($msg)) echo "<div class='alert $type'>$msg</div>"; ?>
        <div class="card"><h3>رفع مجلد جديد (ZIP)</h3>
            <p class="muted">الحد الأقصى للحجم: <?= number_format(MAX_FILE_SIZE / 1024 / 1024, 1) ?> ميغابايت. الملفات غير الصالحة ستُعرض مع تفاصيل الخطأ.</p>
            <form method="post" enctype="multipart/form-data" class="stack-form">
                <input type="text" name="folder_name" placeholder="اسم المجلد" required>
                <input type="file" name="folder_zip" accept=".zip" required>
                <button type="submit" class="btn-primary">رفع</button>
            </form>
        </div>
        <?php if ($folders): ?>
            <div class="folders-grid">
                <?php foreach ($folders as $f): ?>
                    <div class="folder-card">
                        <h3>📂 <?= htmlspecialchars($f['folder_name']) ?></h3>
                        <p>آخر تحديث: <?= date('Y-m-d H:i', $f['updated_at']) ?></p>
                        <form method="post">
                            <input type="hidden" name="folder_id" value="<?= $f['id'] ?>">
                            <label>عدد الجلسات: <input type="number" name="repeat_count" min="1" max="<?= MAX_REPEAT_SESSIONS ?>" value="1" style="width:60px"></label>
                            <button type="submit" name="repeat_sync" class="btn-secondary">🔄 بدء التكرار</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: echo "<p>لا توجد مجلدات. ارفع مجلداً للبدء.</p>"; endif; ?>
    </div>
</body>
</html>