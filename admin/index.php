<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
requireLogin('admin');
$pageTitle = 'Dashboard';

// Stats
global $conn;
$totalUsers   = $conn->query("SELECT COUNT(*) c FROM users WHERE role != 'admin'")->fetch_assoc()['c'];
$totalEvents  = $conn->query("SELECT COUNT(*) c FROM events")->fetch_assoc()['c'];
$pendingCount = $conn->query("SELECT COUNT(*) c FROM events WHERE status='pending'")->fetch_assoc()['c'];
$totalRegs    = $conn->query("SELECT COUNT(*) c FROM registrations")->fetch_assoc()['c'];

// Recent events
$recentEvents = $conn->query("SELECT e.*, u.full_name as organizer_name, v.name as venue_name FROM events e LEFT JOIN users u ON e.organizer_id=u.id LEFT JOIN venues v ON e.venue_id=v.id ORDER BY e.created_at DESC LIMIT 6")->fetch_all(MYSQLI_ASSOC);

// Pending events
$pendingEvents = $conn->query("SELECT e.*, u.full_name as organizer_name FROM events e LEFT JOIN users u ON e.organizer_id=u.id WHERE e.status='pending' ORDER BY e.created_at ASC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1>Admin Dashboard</h1>
    <p>Welcome back, <?= htmlspecialchars($currentUser['full_name']) ?>! Here's what's happening.</p>
  </div>
  <a href="/fyp_soop/smartville/admin/events.php" class="btn btn-primary"><i class="fas fa-calendar-check"></i> Review Events</a>
</div>

<!-- Stats -->
<div class="stats-grid">
  <div class="stat-card" style="--clr:#6c63ff;--icon-bg:rgba(108,99,255,.15);--icon-clr:#6c63ff">
    <div class="stat-icon"><i class="fas fa-users"></i></div>
    <div class="stat-info"><h3><?= $totalUsers ?></h3><p>Total Users</p></div>
  </div>
  <div class="stat-card" style="--clr:#f72585;--icon-bg:rgba(247,37,133,.15);--icon-clr:#f72585">
    <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
    <div class="stat-info"><h3><?= $totalEvents ?></h3><p>Total Events</p></div>
  </div>
  <div class="stat-card" style="--clr:#ff9f43;--icon-bg:rgba(255,159,67,.15);--icon-clr:#ff9f43">
    <div class="stat-icon"><i class="fas fa-clock"></i></div>
    <div class="stat-info">
      <h3><?= $pendingCount ?></h3><p>Pending Approval</p>
      <?php if ($pendingCount > 0): ?><span class="stat-change down"><i class="fas fa-exclamation-circle"></i> Needs attention</span><?php endif; ?>
    </div>
  </div>
  <div class="stat-card" style="--clr:#4cc9f0;--icon-bg:rgba(76,201,240,.15);--icon-clr:#4cc9f0">
    <div class="stat-icon"><i class="fas fa-ticket-alt"></i></div>
    <div class="stat-info"><h3><?= $totalRegs ?></h3><p>Registrations</p></div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:2rem;">
  <!-- Pending Events -->
  <div class="card">
    <div class="card-header">
      <h3><i class="fas fa-clock" style="color:#ff9f43;margin-right:.5rem"></i>Pending Approval</h3>
      <a href="/fyp_soop/smartville/admin/events.php?filter=pending" class="btn btn-sm btn-outline">View All</a>
    </div>
    <div class="card-body" style="padding:0">
      <?php if (empty($pendingEvents)): ?>
        <div class="empty-state" style="padding:2rem"><i class="fas fa-check-circle" style="color:#1dd3b0"></i><p>All caught up!</p></div>
      <?php else: ?>
        <?php foreach ($pendingEvents as $e): ?>
          <div style="display:flex;align-items:center;justify-content:space-between;padding:.9rem 1.2rem;border-bottom:1px solid var(--card-border)">
            <div>
              <div style="font-weight:600;color:var(--white);font-size:.9rem"><?= htmlspecialchars($e['title']) ?></div>
              <div style="font-size:.78rem;color:var(--text-muted)"><?= htmlspecialchars($e['organizer_name']) ?> · <?= date('M d, Y', strtotime($e['date'])) ?></div>
            </div>
            <div style="display:flex;gap:.4rem">
              <a href="/fyp_soop/smartville/admin/events.php?action=approve&id=<?= $e['id'] ?>" class="btn btn-sm btn-success"><i class="fas fa-check"></i></a>
              <a href="/fyp_soop/smartville/admin/events.php?action=reject&id=<?= $e['id'] ?>" class="btn btn-sm btn-danger"><i class="fas fa-times"></i></a>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Recent Events -->
  <div class="card">
    <div class="card-header">
      <h3><i class="fas fa-calendar-alt" style="color:var(--primary);margin-right:.5rem"></i>Recent Events</h3>
      <a href="/fyp_soop/smartville/admin/events.php" class="btn btn-sm btn-outline">View All</a>
    </div>
    <div class="card-body" style="padding:0">
      <?php foreach (array_slice($recentEvents,0,5) as $e): ?>
        <div style="display:flex;align-items:center;justify-content:space-between;padding:.9rem 1.2rem;border-bottom:1px solid var(--card-border)">
          <div>
            <div style="font-weight:600;color:var(--white);font-size:.9rem"><?= htmlspecialchars($e['title']) ?></div>
            <div style="font-size:.78rem;color:var(--text-muted)"><?= date('M d, Y', strtotime($e['date'])) ?></div>
          </div>
          <?= getEventStatusBadge($e['status']) ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
