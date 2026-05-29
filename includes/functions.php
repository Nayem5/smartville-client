<?php
require_once __DIR__ . '/db.php';

function sanitize($data) {
    global $conn;
    return htmlspecialchars(strip_tags(trim($conn->real_escape_string($data))));
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin($role = null) {
    if (!isLoggedIn()) redirect(BASE_PATH . '/login.php');
    if ($role && $_SESSION['role'] !== $role) {
        redirect(BASE_PATH . '/' . $_SESSION['role'] . '/index.php');
    }
}

function getCurrentUser() {
    global $conn;
    if (!isLoggedIn()) return null;
    $id = (int)$_SESSION['user_id'];
    $res = $conn->query("SELECT * FROM users WHERE id = $id");
    return $res ? $res->fetch_assoc() : null;
}

function getUnreadNotifications($userId) {
    global $conn;
    $id = (int)$userId;
    $res = $conn->query("SELECT * FROM notifications WHERE user_id = $id AND is_read = 0 ORDER BY created_at DESC LIMIT 10");
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

function getNotificationCount($userId) {
    global $conn;
    $id = (int)$userId;
    $res = $conn->query("SELECT COUNT(*) as cnt FROM notifications WHERE user_id = $id AND is_read = 0");
    $row = $res ? $res->fetch_assoc() : ['cnt' => 0];
    return $row['cnt'];
}

function markNotificationsRead($userId) {
    global $conn;
    $id = (int)$userId;
    $conn->query("UPDATE notifications SET is_read = 1 WHERE user_id = $id");
}

function addNotification($userId, $title, $message, $type = 'info') {
    global $conn;
    $uid = (int)$userId;
    $t = $conn->real_escape_string($title);
    $m = $conn->real_escape_string($message);
    $tp = $conn->real_escape_string($type);
    $conn->query("INSERT INTO notifications (user_id, title, message, type) VALUES ($uid, '$t', '$m', '$tp')");
}

function getEventStatusBadge($status) {
    $badges = [
        'pending'  => '<span class="badge badge-warning">Pending</span>',
        'approved' => '<span class="badge badge-success">Approved</span>',
        'rejected' => '<span class="badge badge-danger">Rejected</span>',
        'cancelled'=> '<span class="badge badge-secondary">Cancelled</span>',
    ];
    return $badges[$status] ?? '<span class="badge">' . ucfirst($status) . '</span>';
}

function getTypeBadge($type) {
    $badges = [
        'free'    => '<span class="badge badge-free">Free</span>',
        'paid'    => '<span class="badge badge-paid">Paid</span>',
        'private' => '<span class="badge badge-private">Private</span>',
    ];
    return $badges[$type] ?? '';
}

function timeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    if ($diff->d == 0 && $diff->h == 0) return $diff->i . ' min ago';
    if ($diff->d == 0) return $diff->h . ' hrs ago';
    if ($diff->d == 1) return 'Yesterday';
    return $diff->d . ' days ago';
}

function getRegistrationCount($eventId) {
    global $conn;
    $id = (int)$eventId;
    $res = $conn->query("SELECT COUNT(*) as cnt FROM registrations WHERE event_id = $id");
    $row = $res ? $res->fetch_assoc() : ['cnt' => 0];
    return $row['cnt'];
}

function isRegistered($eventId, $userId) {
    global $conn;
    $eid = (int)$eventId;
    $uid = (int)$userId;
    $res = $conn->query("SELECT id FROM registrations WHERE event_id = $eid AND user_id = $uid");
    return $res && $res->num_rows > 0;
}

function hasGivenFeedback($eventId, $userId) {
    global $conn;
    $eid = (int)$eventId;
    $uid = (int)$userId;
    $res = $conn->query("SELECT id FROM feedback WHERE event_id = $eid AND user_id = $uid");
    return $res && $res->num_rows > 0;
}

function getAverageRating($eventId) {
    global $conn;
    $id = (int)$eventId;
    $res = $conn->query("SELECT AVG(rating) as avg_rating FROM feedback WHERE event_id = $id");
    $row = $res ? $res->fetch_assoc() : null;
    return $row ? round($row['avg_rating'], 1) : 0;
}
