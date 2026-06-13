<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/auth.php';
use League\OAuth2\Client\Provider\Github;
if (empty($_GET['state']) || $_GET['state'] !== $_SESSION['oauth2state']) die('Invalid state');
$provider = new Github(['clientId' => GITHUB_CLIENT_ID, 'clientSecret' => GITHUB_CLIENT_SECRET, 'redirectUri' => GITHUB_REDIRECT_URI]);
$token = $provider->getAccessToken('authorization_code', ['code' => $_GET['code']]);
$githubUser = $provider->getResourceOwner($token);
$auth = new Auth();
$auth->loginWithGitHub($githubUser->getId(), $githubUser->getNickname(), $githubUser->getEmail(), $token->getToken());
header('Location: dashboard.php');
exit;