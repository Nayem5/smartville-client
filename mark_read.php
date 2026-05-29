<?php
session_start();
require_once __DIR__ . '/includes/functions.php';
if (isLoggedIn()) markNotificationsRead($_SESSION['user_id']);
header('Location: ' . $_SERVER['HTTP_REFERER']);
exit();
