<?php
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/auth.php';
$auth = new Auth();
if ($auth->isLoggedIn()) header('Location: dashboard.php');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head><meta charset="UTF-8"><title><?= APP_NAME ?></title><link rel="stylesheet" href="assets/css/style.css"></head>
<body>
    <div class="hero"><div class="hero-content">
        <p class="status-pill">نسخة محسّنة • إدارة ذكية • واجهة عربية</p>
        <h1>🚀 <?= APP_NAME ?></h1>
        <p style="font-size:1.05rem;max-width:780px;margin:0 auto 8px;color:#e5eefb;">منصة متكاملة لبدء جلسات RDP المتتالية ومزامنة المجلدات تلقائيًا مع GitHub، مع لوحة إدارة واضحة ومتابعة الأخطاء مباشرة.</p>
        <div class="hero-badges">
            <div class="badge-card"><strong>⚡ سرعة</strong><span>تسريع إعداد الجلسات المتكررة</span></div>
            <div class="badge-card"><strong>🔐 أمان</strong><span>تسجيل دخول GitHub ومراقبة الأخطاء</span></div>
            <div class="badge-card"><strong>📁 مزامنة</strong><span>رفع ZIP وإدارة المجلدات بشكل واضح</span></div>
        </div>
        <a href="github-login.php" class="btn-github">🔐 تسجيل الدخول عبر GitHub</a>

        <div class="feature-grid">
            <article class="feature-card"><h3>🧭 لوحة تحكم ذكية</h3><p>تابع الجلسات، راجع آخر المجلدات، واطلع على الحالة العامة من مكان واحد.</p></article>
            <article class="feature-card"><h3>🛠️ إدارة مدبرة</h3><p>منح أو إزالة صلاحية المدير بسهولة مع أدوات تنسيق واضحة ومرئية.</p></article>
            <article class="feature-card"><h3>📊 تفاصيل الأخطاء</h3><p>تتبّع المشاكل بسرعة مع سجلات مفصلة لتسهيل التصحيح السريع.</p></article>
        </div>
    </div></div>
</body>
</html>