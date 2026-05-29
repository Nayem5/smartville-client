<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/functions.php';

$currentUser = getCurrentUser();
$notifCount  = $currentUser ? getNotificationCount($currentUser['id']) : 0;
$notifs      = $currentUser ? getUnreadNotifications($currentUser['id']) : [];
$role        = $_SESSION['role'] ?? '';
$basePath    = BASE_PATH;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= $pageTitle ?? 'SmartVille' ?> — SmartVille</title>
  <link rel="stylesheet" href="<?= $basePath ?>/dashboard.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="logo-icon"><i class="fas fa-city"></i></div>
    <span class="logo-text">Smart<span class="accent">Ville</span></span>
  </div>

  <nav class="sidebar-nav">
    <?php if ($role === 'admin'): ?>
      <a href="<?= $basePath ?>/admin/index.php" class="nav-item <?= ($pageTitle==='Dashboard')?'active':'' ?>">
        <i class="fas fa-th-large"></i><span>Dashboard</span>
      </a>
      <a href="<?= $basePath ?>/admin/events.php" class="nav-item <?= ($pageTitle==='Manage Events')?'active':'' ?>">
        <i class="fas fa-calendar-alt"></i><span>Manage Events</span>
        <?php
          global $conn;
          $pending = $conn->query("SELECT COUNT(*) as c FROM events WHERE status='pending'")->fetch_assoc()['c'];
          if ($pending > 0) echo "<span class='badge-nav'>$pending</span>";
        ?>
      </a>
      <a href="<?= $basePath ?>/admin/users.php" class="nav-item <?= ($pageTitle==='Manage Users')?'active':'' ?>">
        <i class="fas fa-users-cog"></i><span>Manage Users</span>
      </a>
      <a href="<?= $basePath ?>/admin/venues.php" class="nav-item <?= ($pageTitle==='Venues')?'active':'' ?>">
        <i class="fas fa-map-marker-alt"></i><span>Venues</span>
      </a>

    <?php elseif ($role === 'organizer'): ?>
      <a href="<?= $basePath ?>/organizer/index.php" class="nav-item <?= ($pageTitle==='Dashboard')?'active':'' ?>">
        <i class="fas fa-th-large"></i><span>Dashboard</span>
      </a>
      <a href="<?= $basePath ?>/organizer/create_event.php" class="nav-item <?= ($pageTitle==='Create Event')?'active':'' ?>">
        <i class="fas fa-plus-circle"></i><span>Create Event</span>
      </a>
      <a href="<?= $basePath ?>/organizer/my_events.php" class="nav-item <?= ($pageTitle==='My Events')?'active':'' ?>">
        <i class="fas fa-calendar-alt"></i><span>My Events</span>
      </a>

    <?php elseif ($role === 'resident'): ?>
      <a href="<?= $basePath ?>/resident/index.php" class="nav-item <?= ($pageTitle==='Dashboard')?'active':'' ?>">
        <i class="fas fa-th-large"></i><span>Dashboard</span>
      </a>
      <a href="<?= $basePath ?>/resident/browse.php" class="nav-item <?= ($pageTitle==='Browse Events')?'active':'' ?>">
        <i class="fas fa-search"></i><span>Browse Events</span>
      </a>
      <a href="<?= $basePath ?>/resident/my_events.php" class="nav-item <?= ($pageTitle==='My Events')?'active':'' ?>">
        <i class="fas fa-ticket-alt"></i><span>My Events</span>
      </a>
    <?php endif; ?>

    <a href="<?= $basePath ?>/profile.php" class="nav-item <?= ($pageTitle==='Profile')?'active':'' ?>">
      <i class="fas fa-user-circle"></i><span>Profile</span>
    </a>
  </nav>

  <div class="sidebar-bottom">
    <a href="<?= $basePath ?>/logout.php" class="nav-item logout">
      <i class="fas fa-sign-out-alt"></i><span>Logout</span>
    </a>
  </div>
</aside>

<!-- TOPBAR -->
<div class="main-content">
<header class="topbar">
  <button class="sidebar-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
  <div class="topbar-title"><?= $pageTitle ?? 'Dashboard' ?></div>
  <div class="topbar-right">
    <!-- Notifications -->
    <div class="notif-wrap" onclick="toggleNotif()">
      <button class="icon-btn"><i class="fas fa-bell"></i></button>
      <?php if ($notifCount > 0): ?>
        <span class="notif-count"><?= $notifCount ?></span>
      <?php endif; ?>
      <div class="notif-dropdown" id="notifDropdown">
        <div class="notif-header">
          <span>Notifications</span>
          <a href="<?= $basePath ?>/mark_read.php">Mark all read</a>
        </div>
        <?php if (empty($notifs)): ?>
          <div class="notif-empty"><i class="fas fa-bell-slash"></i><p>No new notifications</p></div>
        <?php else: ?>
          <?php foreach ($notifs as $n): ?>
            <div class="notif-item notif-<?= $n['type'] ?>">
              <i class="fas <?= $n['type']==='success'?'fa-check-circle':'fa-info-circle' ?>"></i>
              <div>
                <strong><?= htmlspecialchars($n['title']) ?></strong>
                <p><?= htmlspecialchars($n['message']) ?></p>
                <span><?= timeAgo($n['created_at']) ?></span>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
    <!-- User -->
    <div class="user-menu" onclick="toggleUserMenu()">
      <div class="user-avatar"><?= strtoupper(substr($currentUser['full_name'],0,2)) ?></div>
      <div class="user-info">
        <span><?= htmlspecialchars($currentUser['full_name']) ?></span>
        <small><?= ucfirst($role) ?></small>
      </div>
      <i class="fas fa-chevron-down"></i>
      <div class="user-dropdown" id="userDropdown">
        <a href="<?= $basePath ?>/profile.php"><i class="fas fa-user"></i> Profile</a>
        <a href="<?= $basePath ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
      </div>
    </div>
  </div>
</header>
<div class="page-body">
