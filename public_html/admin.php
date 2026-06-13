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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentUserId = (int)($_SESSION['user_id'] ?? 0);

    if (isset($_POST['action'], $_POST['user_id']) && in_array($_POST['action'], ['make_admin', 'remove_admin'], true)) {
        $targetUserId = (int)$_POST['user_id'];
        $action = $_POST['action'] === 'make_admin' ? 1 : 0;
        if ($targetUserId !== $currentUserId || $action === 0) {
            $pdo->prepare('UPDATE users SET is_admin = ? WHERE id = ?')->execute([$action, $targetUserId]);
            $msg = $action ? '✅ تم منح الصلاحية للعضو المحدد' : '✅ تم إلغاء الصلاحية';
            $type = 'success';
        } else {
            $msg = '⚠️ لا يمكنك إزالة صلاحية حسابك الحالي';
            $type = 'warning';
        }
    }

    if (isset($_POST['action'], $_POST['user_id']) && $_POST['action'] === 'delete_user') {
        $targetUserId = (int)$_POST['user_id'];
        if ($targetUserId !== $currentUserId) {
            $pdo->prepare('DELETE FROM user_tokens WHERE user_id = ?')->execute([$targetUserId]);
            $pdo->prepare('DELETE FROM sessions WHERE user_id = ?')->execute([$targetUserId]);
            $pdo->prepare('DELETE FROM user_folders WHERE user_id = ?')->execute([$targetUserId]);
            $pdo->prepare('DELETE FROM repeat_requests WHERE user_id = ?')->execute([$targetUserId]);
            $pdo->prepare('DELETE FROM error_logs WHERE user_id = ?')->execute([$targetUserId]);
            $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$targetUserId]);
            $msg = '🗑️ تم حذف المستخدم والبيانات المرتبطة به';
            $type = 'success';
        } else {
            $msg = '⚠️ لا يمكنك حذف حسابك الحالي';
            $type = 'warning';
        }
    }

    if (isset($_POST['action'], $_POST['error_id']) && $_POST['action'] === 'mark_resolved') {
        $errorId = (int)$_POST['error_id'];
        $pdo->prepare('UPDATE error_logs SET resolved = 1 WHERE id = ?')->execute([$errorId]);
        $msg = '✅ تم تصنيف الخطأ على أنه محلول';
        $type = 'success';
    }

    if (isset($_POST['action']) && $_POST['action'] === 'mark_all_resolved') {
        $pdo->exec('UPDATE error_logs SET resolved = 1');
        $msg = '✅ تم تصنيف جميع الأخطاء على أنها محلولة';
        $type = 'success';
    }

    if (isset($_POST['action']) && $_POST['action'] === 'clear_logs') {
        $pdo->exec('DELETE FROM error_logs');
        $msg = '🧹 تم حذف سجلات الأخطاء';
        $type = 'info';
    }

    if (isset($_POST['action']) && $_POST['action'] === 'clear_sessions') {
        $pdo->exec('DELETE FROM sessions');
        $msg = '🧹 تم حذف جميع الجلسات';
        $type = 'info';
    }

    if (isset($_POST['action']) && $_POST['action'] === 'clear_folders') {
        $pdo->exec('DELETE FROM user_folders');
        $msg = '🧹 تم حذف جميع المجلدات';
        $type = 'info';
    }

    if (isset($_POST['action']) && $_POST['action'] === 'clear_repeat_requests') {
        $pdo->exec('DELETE FROM repeat_requests');
        $msg = '🧹 تم حذف جميع طلبات التكرار';
        $type = 'info';
    }

    if (isset($_POST['action'], $_POST['session_id']) && $_POST['action'] === 'delete_session') {
        $pdo->prepare('DELETE FROM sessions WHERE id = ?')->execute([(int)$_POST['session_id']]);
        $msg = '🗑️ تم حذف الجلسة المحددة';
        $type = 'success';
    }

    if (isset($_POST['action'], $_POST['folder_id']) && $_POST['action'] === 'delete_folder') {
        $pdo->prepare('DELETE FROM user_folders WHERE id = ?')->execute([(int)$_POST['folder_id']]);
        $msg = '🗑️ تم حذف المجلد المحدد';
        $type = 'success';
    }

    if (isset($_POST['action'], $_POST['request_id']) && $_POST['action'] === 'delete_repeat_request') {
        $pdo->prepare('DELETE FROM repeat_requests WHERE id = ?')->execute([(int)$_POST['request_id']]);
        $msg = '🗑️ تم حذف طلب التكرار المحدد';
        $type = 'success';
    }
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
  'errors' => (int)$pdo->query('SELECT COUNT(*) FROM error_logs')->fetchColumn(),
  'resolved_errors' => (int)$pdo->query('SELECT COUNT(*) FROM error_logs WHERE resolved = 1')->fetchColumn(),
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
      <div class="stat-card"><strong><?= $stats['errors'] ?></strong><span>خطأ مسجل</span></div>
      <div class="stat-card"><strong><?= $stats['resolved_errors'] ?></strong><span>خطأ محلول</span></div>
    </div>

    <div class="card">
      <div class="card-header"><h2>📈 نظرة سريعة</h2><span class="status-pill">نسخة متقدمة</span></div>
      <p class="muted">يمكنك من هنا إدارة المستخدمين، متابعة الجلسات، ومراجعة الأخطاء الأخيرة بشكل مباشر.</p>
      <div class="quick-grid">
        <a class="quick-card" href="dashboard.php">📊 لوحة المستخدم</a>
        <form method="post" class="quick-card" style="padding:0; border:none; background:transparent;">
          <input type="hidden" name="action" value="mark_all_resolved">
          <button type="submit" class="btn-secondary" style="width:100%;">✅ تصنيف الكل محلول</button>
        </form>
        <form method="post" class="quick-card" style="padding:0; border:none; background:transparent;">
          <input type="hidden" name="action" value="clear_logs">
          <button type="submit" class="btn-secondary" style="width:100%;">🧹 حذف سجلات الأخطاء</button>
        </form>
        <form method="post" class="quick-card" style="padding:0; border:none; background:transparent;">
          <input type="hidden" name="action" value="clear_sessions">
          <button type="submit" class="btn-secondary" style="width:100%;">🧹 حذف جميع الجلسات</button>
        </form>
        <form method="post" class="quick-card" style="padding:0; border:none; background:transparent;">
          <input type="hidden" name="action" value="clear_folders">
          <button type="submit" class="btn-secondary" style="width:100%;">🧹 حذف جميع المجلدات</button>
        </form>
        <form method="post" class="quick-card" style="padding:0; border:none; background:transparent;">
          <input type="hidden" name="action" value="clear_repeat_requests">
          <button type="submit" class="btn-secondary" style="width:100%;">🧹 حذف جميع طلبات التكرار</button>
        </form>
      </div>
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
                <form method="post" class="inline-form" onsubmit="return confirm('هل أنت متأكد من حذف هذا المستخدم وجميع بياناته؟');">
                  <input type="hidden" name="action" value="delete_user">
                  <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                  <button type="submit" class="btn-danger">🗑️ حذف المستخدم</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="card">
      <h2>📋 آخر الجلسات</h2>
      <?php if ($sessions): foreach ($sessions as $s): ?>
        <div class="mini-card">
          #<?= (int)$s['id'] ?> — <?= htmlspecialchars($s['user_name'] ?: 'مستخدم') ?> — <?= htmlspecialchars($s['repo_name'] ?: '-') ?> — <?= htmlspecialchars($s['status'] ?: 'unknown') ?>
          <form method="post" class="inline-form" style="margin-top:6px;" onsubmit="return confirm('حذف هذه الجلسة؟');">
            <input type="hidden" name="action" value="delete_session">
            <input type="hidden" name="session_id" value="<?= (int)$s['id'] ?>">
            <button type="submit" class="btn-danger">🗑️ حذف</button>
          </form>
        </div>
      <?php endforeach; else: ?><p>لا توجد جلسات.</p><?php endif; ?>
    </div>

    <div class="card">
      <h2>🔁 طلبات التكرار</h2>
      <?php if ($repeatRequests): foreach ($repeatRequests as $r): ?>
        <div class="mini-card">
          #<?= (int)$r['id'] ?> — <?= htmlspecialchars($r['user_name'] ?: 'مستخدم') ?> — التكرار: <?= (int)$r['repeat_count'] ?> | مكتمل: <?= (int)$r['completed_count'] ?> | الحالة: <?= htmlspecialchars($r['status'] ?: 'unknown') ?>
          <form method="post" class="inline-form" style="margin-top:6px;" onsubmit="return confirm('حذف طلب التكرار هذا؟');">
            <input type="hidden" name="action" value="delete_repeat_request">
            <input type="hidden" name="request_id" value="<?= (int)$r['id'] ?>">
            <button type="submit" class="btn-danger">🗑️ حذف</button>
          </form>
        </div>
      <?php endforeach; else: ?><p>لا توجد طلبات تكرار.</p><?php endif; ?>
    </div>

    <div class="card">
      <h2>📁 آخر المجلدات</h2>
      <?php if ($folders): foreach ($folders as $f): ?>
        <div class="mini-card">
          📂 <?= htmlspecialchars($f['folder_name']) ?> — المستخدم: <?= htmlspecialchars($f['user_name'] ?: 'مستخدم') ?> — <?= date('Y-m-d H:i', (int)$f['created_at']) ?>
          <form method="post" class="inline-form" style="margin-top:6px;" onsubmit="return confirm('حذف هذا المجلد؟');">
            <input type="hidden" name="action" value="delete_folder">
            <input type="hidden" name="folder_id" value="<?= (int)$f['id'] ?>">
            <button type="submit" class="btn-danger">🗑️ حذف</button>
          </form>
        </div>
      <?php endforeach; else: ?><p>لا توجد مجلدات بعد.</p><?php endif; ?>
    </div>

    <div class="card">
      <div class="card-header"><h2>⚠️ تفاصيل الأخطاء الأخيرة</h2><span class="status-pill">سجل مراقبة</span></div>
      <?php if ($errorLogs): foreach ($errorLogs as $e): ?>
        <article class="error-card">
          <div class="error-title">[<?= htmlspecialchars($e['error_type'] ?: 'UNKNOWN') ?>] <?= htmlspecialchars($e['message']) ?></div>
          <div class="error-meta">المستخدم: <?= htmlspecialchars($e['user_name'] ?: 'ضيف') ?> | المسار: <?= htmlspecialchars($e['file_path'] ?: '-') ?> | السطر: <?= (int)($e['line_number'] ?? 0) ?> | الوقت: <?= date('Y-m-d H:i', (int)$e['created_at']) ?> | الحالة: <?= !empty($e['resolved']) ? 'محلول' : 'قيد المراجعة' ?></div>
          <?php if (!empty($e['trace'])): ?><pre class="trace-box"><?= htmlspecialchars($e['trace']) ?></pre><?php endif; ?>
          <?php if (empty($e['resolved'])): ?>
            <form method="post" class="inline-form" style="margin-top:8px;">
              <input type="hidden" name="action" value="mark_resolved">
              <input type="hidden" name="error_id" value="<?= (int)$e['id'] ?>">
              <button type="submit" class="btn-secondary">✅ تحديد كـ محلول</button>
            </form>
          <?php endif; ?>
        </article>
      <?php endforeach; else: ?><p>لا توجد أخطاء مسجلة حتى الآن.</p><?php endif; ?>
    </div>
  </div>
</body>
</html>
