<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/inc/config.php';
use League\OAuth2\Client\Provider\Github;
$provider = new Github(['clientId' => GITHUB_CLIENT_ID, 'clientSecret' => GITHUB_CLIENT_SECRET, 'redirectUri' => GITHUB_REDIRECT_URI]);
$authUrl = $provider->getAuthorizationUrl(['scope' => ['repo', 'workflow']]);
$_SESSION['oauth2state'] = $provider->getState();
header('Location: ' . $authUrl);
exit;