<?php
require_once __DIR__ . '/inc/auth.php';
$auth = new Auth();
if (session_status() === PHP_SESSION_NONE) session_start();
$auth->logout();