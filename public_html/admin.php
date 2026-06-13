<?php
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/database.php';
require_once __DIR__ . '/inc/error_handler.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: index.php');
    exit;
}
if (!$auth->isAdmin()) {
    header('Location: dashboard.php');
    exit;
}

$db = Database::getInstance();
$pdo = $db->getPDO();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['user_id'])) {
    $targetUserId = (int)$_POST['user_id'];
    $action = $_POST['action'] === 'make_admin' ? 1 : 0;
    $pdo->prepare('UPDATE users SET is_admin = ? WHERE id = ?')->execute([$action, $targetUserId]);
    $msg = $action ? '✅ تم منح الصلاحية للعضو المحدد' : '✅ تم إلغاء الصلاحية';
    $type = 'success';
}

$users = $pdo->query('SELECT id, username, email, github_id, is_admin, created_at FROM users ORDER BY id ASC')->fetchAll(PDO::FETCH_ASSOC);
$sessions = $pdo->query('SELECT s.*, u.username AS user_name FROM sessions s LEFT JOIN users u ON u.id = s.user_id ORDER BY s.created_at DESC LIMIT 10')->fetchAll(PDO::FETCH_ASSOC);
$folders = $pdo->query('SELECT f.*, u.username AS user_name FROM user_folders f LEFT JOIN users u ON u.id = f.user_id ORDER BY f.created_at DESC LIMIT 10')->fetchAll(PDO::FETCH_ASSOC);
$repeatRequests = $pdo->query('SELECT r.*, u.username AS user_name FROM repeat_requests r LEFT JOIN users u ON u.id = r.user_id ORDER BY r.created_at DESC LIMIT 10')->fetchAll(PDO::FETCH_ASSOC);
$errorLogs = $pdo->query('SELECT e.*, u.username AS user_name FROM error_logs e LEFT JOIN users u ON u.id = e.user_id ORDER BY e.created_at DESC LIMIT 20')->fetchAll(PDO::FETCH_ASSOC);

$stats = [
  'users' => (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
  'sessions' => (int)$pdo->query('SELECT COUNT(*) FROM sessions')->fetchColumn(),
  'folders' => (int)$pdo->query('SELECT COUNT(*) FROM user_folders')->fetchColumn(),
  'repeat_requests' => (int)$pdo->query('SELECT COUNT(*) FROM repeat_requests')->fetchColumn(),
];
?>
<!DOCTYPE html>
<html dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>لوحة الإدارة</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>🛠️ لوحة الإدارة</h1>
      <div class="nav-links">
        <a href="dashboard.php">← العودة للوحة</a>
        <a href="logout.php">🚪 خروج</a>
      </div>
    </div>

    <?php if (!empty($msg)) echo "<div class='alert $type'>$msg</div>"; ?>

    <div class="stat-grid">
      <div class="stat-card"><strong><?= $stats['users'] ?></strong><span>مستخدم</span></div>
      <div class="stat-card"><strong><?= $stats['sessions'] ?></strong><span>جلسة</span></div>
      <div class="stat-card"><strong><?= $stats['folders'] ?></strong><span>مجلد</span></div>
      <div class="stat-card"><strong><?= $stats['repeat_requests'] ?></strong><span>طلب تكرار</span></div>
    </div>

    <div class="card">
      <h2>👥 إدارة المستخدمين</h2>
      <table class="data-table">
        <thead>
          <tr><th>المستخدم</th><th>البريد</th><th>الحالة</th><th>الإجراء</th></tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
            <tr>
              <td><?= htmlspecialchars($u['username'] ?: 'مستخدم #' . $u['id']) ?></td>
              <td><?= htmlspecialchars($u['email'] ?: '-') ?></td>
              <td><?= !empty($u['is_admin']) ? 'مدير' : 'عضو' ?></td>
              <td>
                <?php if (empty($u['is_admin'])): ?>
                  <form method="post" class="inline-form">
                    <input type="hidden" name="action" value="make_admin">
                    <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                    <button type="submit" class="btn-primary">تعيين مدير</button>
                  </form>
                <?php else: ?>
                  <form method="post" class="inline-form">
                    <input type="hidden" name="action" value="remove_admin">
                    <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                    <button type="submit" class="btn-secondary">إزالة المدير</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="card">
      <h2>📋 آخر الجلسات</h2>
      <?php if ($sessions): foreach ($sessions as $s): ?>
        <div class="mini-card">#<?= (int)$s['id'] ?> — <?= htmlspecialchars($s['user_name'] ?: 'مستخدم') ?> — <?= htmlspecialchars($s['repo_name'] ?: '-') ?> — <?= htmlspecialchars($s['status'] ?: 'unknown') ?></div>
      <?php endforeach; else: ?><p>لا توجد جلسات.</p><?php endif; ?>
    </div>

    <div class="card">
      <h2>� طلبات التكرار</h2>
      <?php if ($repeatRequests): foreach ($repeatRequests as $r): ?>
        <div class="mini-card">#<?= (int)$r['id'] ?> — <?= htmlspecialchars($r['user_name'] ?: 'مستخدم') ?> — التكرار: <?= (int)$r['repeat_count'] ?> | مكتمل: <?= (int)$r['completed_count'] ?> | الحالة: <?= htmlspecialchars($r['status'] ?: 'unknown') ?></div>
      <?php endforeach; else: ?><p>لا توجد طلبات تكرار.</p><?php endif; ?>
    </div>

    <div class="card">
      <h2>�📁 آخر المجلدات</h2>
      <?php if ($folders): foreach ($folders as $f): ?>
        <div class="mini-card">📂 <?= htmlspecialchars($f['folder_name']) ?> — المستخدم: <?= htmlspecialchars($f['user_name'] ?: 'مستخدم') ?> — <?= date('Y-m-d H:i', (int)$f['created_at']) ?></div>
      <?php endforeach; else: ?><p>لا توجد مجلدات بعد.</p><?php endif; ?>
    </div>

    <div class="card">
      <h2>⚠️ تفاصيل الأخطاء الأخيرة</h2>
      <?php if ($errorLogs): foreach ($errorLogs as $e): ?>
        <article class="error-card">
          <div class="error-title">[<?= htmlspecialchars($e['error_type'] ?: 'UNKNOWN') ?>] <?= htmlspecialchars($e['message']) ?></div>
          <div class="error-meta">المستخدم: <?= htmlspecialchars($e['user_name'] ?: 'ضيف') ?> | المسار: <?= htmlspecialchars($e['file_path'] ?: '-') ?> | السطر: <?= (int)($e['line_number'] ?? 0) ?> | الوقت: <?= date('Y-m-d H:i', (int)$e['created_at']) ?></div>
          <?php if (!empty($e['trace'])): ?><pre class="trace-box"><?= htmlspecialchars($e['trace']) ?></pre><?php endif; ?>
        </article>
      <?php endforeach; else: ?><p>لا توجد أخطاء مسجلة حتى الآن.</p><?php endif; ?>
    </div>
  </div>
</body>
</html>
