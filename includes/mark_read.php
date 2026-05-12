<?php
require_once __DIR__ . '/../includes/auth.php';
Auth::requireLogin();
$notif = new Notification();
$id    = intval($_GET['id'] ?? 0);
$uid   = Auth::userId();
if ($id) $notif->markRead($id, $uid);
else     $notif->markAllRead($uid);
$ref = $_SERVER['HTTP_REFERER'] ?? '../index.php';
header("Location: $ref"); exit;
