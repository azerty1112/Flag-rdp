<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/inc/config.php';
use League\OAuth2\Client\Provider\Github;

if (GITHUB_CLIENT_ID === 'client_id' || GITHUB_CLIENT_SECRET === 'client_secret') {
    http_response_code(500);
    echo '<!doctype html><html dir="rtl"><head><meta charset="UTF-8"><title>إعداد GitHub مطلوب</title></head><body style="font-family:Arial;padding:24px;">';
    echo '<h2>إعداد GitHub غير مكتمل</h2>';
    echo '<p>يرجى تعيين متغيرات البيئة: GITHUB_CLIENT_ID و GITHUB_CLIENT_SECRET و APP_URL.</p>';
    echo '<p>ثم أعد تشغيل التطبيق.</p>';
    echo '</body></html>';
    exit;
}

$provider = new Github(['clientId' => GITHUB_CLIENT_ID, 'clientSecret' => GITHUB_CLIENT_SECRET, 'redirectUri' => GITHUB_REDIRECT_URI]);
$authUrl = $provider->getAuthorizationUrl(['scope' => ['repo', 'workflow']]);
$_SESSION['oauth2state'] = $provider->getState();
header('Location: ' . $authUrl);
exit;