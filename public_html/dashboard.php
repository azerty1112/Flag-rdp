<?php
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/database.php';
$auth = new Auth();
if (!$auth->isLoggedIn()) header('Location: index.php');
$user = $auth->getUser();
$db = Database::getInstance();
$sessions = $db->getPDO()->prepare("SELECT * FROM sessions WHERE user_id = ? ORDER BY created_at DESC");
$sessions->execute([$user['id']]);
$sessions = $sessions->fetchAll(PDO::FETCH_ASSOC);
$canAccessAdmin = $auth->isAdmin();
$stats = [
  'sessions' => count($sessions),
  'folders' => $db->getPDO()->query("SELECT COUNT(*) FROM user_folders WHERE user_id = {$user['id']}")->fetchColumn(),
  'repeat_requests' => $db->getPDO()->query("SELECT COUNT(*) FROM repeat_requests WHERE user_id = {$user['id']}")->fetchColumn(),
];
?>
<!DOCTYPE html>
<html dir="rtl">
<head><title>لوحة التحكم</title><link rel="stylesheet" href="assets/css/style.css"></head>
<body>
    <div class="container">
        <div class="header"><h1>مرحباً <?= htmlspecialchars($user['username']) ?></h1>
        <div class="nav-links">
            <a href="folder_manager.php">📁 مجلداتي</a>
            <?php if ($canAccessAdmin): ?><a href="admin.php">🛠️ لوحة الإدارة</a><?php endif; ?>
            <a href="logout.php">🚪 خروج</a>
        </div></div>
        <div class="stat-grid">
            <div class="stat-card"><strong><?= (int)$stats['sessions'] ?></strong><span>جلسة نشطة</span></div>
            <div class="stat-card"><strong><?= (int)$stats['folders'] ?></strong><span>مجلد محفوظ</span></div>
            <div class="stat-card"><strong><?= (int)$stats['repeat_requests'] ?></strong><span>طلب تكرار</span></div>
            <div class="stat-card"><strong><?= htmlspecialchars($user['username']) ?></strong><span>حسابك الحالي</span></div>
        </div>
        <div class="card">
            <div class="card-header"><h2>⚡ إجراءات سريعة</h2><span class="status-pill">واجهة محسنة</span></div>
            <div class="quick-grid">
                <a class="quick-card" href="folder_manager.php">📁 إدارة المجلدات</a>
                <?php if ($canAccessAdmin): ?><a class="quick-card" href="admin.php">🛠️ لوحة الإدارة</a><?php endif; ?>
                <a class="quick-card" href="logout.php">🚪 تسجيل الخروج</a>
            </div>
            <p class="muted">يمكنك من هنا متابعة الجلسات، رفع المجلدات، ومراجعة الأخطاء من لوحة الإدارة في تجربة أكثر سلاسة.</p>
        </div>
        <h2>📋 جلساتي</h2>
        <div class="sessions-grid">
            <?php if (!$sessions): ?>
                <div class="card">لا توجد جلسات حتى الآن. ارفع مجلدًا أو ابدأ طلب تكرار لرؤية التقدم هنا.</div>
            <?php endif; ?>
            <?php foreach ($sessions as $s): ?>
                <div class="session-card">
                    <div class="session-status <?= $s['status'] ?>"><?= $s['status'] ?></div>
                    <div>🆔 <?= substr($s['session_id'], 0, 16) ?></div>
                    <div>📅 <?= date('Y-m-d H:i', $s['created_at']) ?></div>
                    <?php if ($s['host']): ?>
                        <div>🌐 <code><?= htmlspecialchars($s['host']) ?></code></div>
                        <div>👤 <code><?= htmlspecialchars($s['username']) ?></code></div>
                        <div>🔑 <code><?= htmlspecialchars($s['password']) ?></code></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>