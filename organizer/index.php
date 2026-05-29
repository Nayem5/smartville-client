<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
requireLogin('organizer');
$pageTitle = 'Dashboard';

global $conn;
$uid = $_SESSION['user_id'];

$myEvents   = $conn->query("SELECT COUNT(*) c FROM events WHERE organizer_id=$uid")->fetch_assoc()['c'];
$approved   = $conn->query("SELECT COUNT(*) c FROM events WHERE organizer_id=$uid AND status='approved'")->fetch_assoc()['c'];
$pending    = $conn->query("SELECT COUNT(*) c FROM events WHERE organizer_id=$uid AND status='pending'")->fetch_assoc()['c'];
$totalAttendees = $conn->query("SELECT COUNT(*) c FROM registrations r JOIN events e ON r.event_id=e.id WHERE e.organizer_id=$uid")->fetch_assoc()['c'];

$recentEvents = $conn->query("SELECT e.*, v.name as venue_name,
    (SELECT COUNT(*) FROM registrations r WHERE r.event_id=e.id) as reg_count,
    (SELECT AVG(rating) FROM feedback f WHERE f.event_id=e.id) as avg_rating
    FROM events e LEFT JOIN venues v ON e.venue_id=v.id
    WHERE e.organizer_id=$uid ORDER BY e.created_at DESC LIMIT 6")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1>Organizer Dashboard</h1>
    <p>Welcome, <?= htmlspecialchars($currentUser['full_name']) ?>! Manage your events here.</p>
  </div>
  <a href="/fyp_soop/smartville/organizer/create_event.php" class="btn btn-primary"><i class="fas fa-plus"></i> Create Event</a>
</div>

<div class="stats-grid">
  <div class="stat-card" style="--clr:#6c63ff;--icon-bg:rgba(108,99,255,.15);--icon-clr:#6c63ff">
    <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
    <div class="stat-info"><h3><?= $myEvents ?></h3><p>Total Events</p></div>
  </div>
  <div class="stat-card" style="--clr:#1dd3b0;--icon-bg:rgba(29,211,176,.15);--icon-clr:#1dd3b0">
    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
    <div class="stat-info"><h3><?= $approved ?></h3><p>Approved</p></div>
  </div>
  <div class="stat-card" style="--clr:#ff9f43;--icon-bg:rgba(255,159,67,.15);--icon-clr:#ff9f43">
    <div class="stat-icon"><i class="fas fa-clock"></i></div>
    <div class="stat-info"><h3><?= $pending ?></h3><p>Pending Review</p></div>
  </div>
  <div class="stat-card" style="--clr:#f72585;--icon-bg:rgba(247,37,133,.15);--icon-clr:#f72585">
    <div class="stat-icon"><i class="fas fa-users"></i></div>
    <div class="stat-info"><h3><?= $totalAttendees ?></h3><p>Total Attendees</p></div>
  </div>
</div>

<div class="page-header" style="margin-bottom:1rem">
  <h3 style="color:var(--white);font-size:1.1rem">My Recent Events</h3>
  <a href="/fyp_soop/smartville/organizer/my_events.php" class="btn btn-outline btn-sm">View All</a>
</div>

<?php if (empty($recentEvents)): ?>
  <div class="empty-state card" style="padding:4rem">
    <i class="fas fa-calendar-plus" style="color:var(--primary)"></i>
    <h3>No events yet</h3>
    <p>Create your first community event!</p>
    <a href="/fyp_soop/smartville/organizer/create_event.php" class="btn btn-primary" style="margin-top:1rem"><i class="fas fa-plus"></i> Create Event</a>
  </div>
<?php else: ?>
  <div class="events-grid">
    <?php foreach ($recentEvents as $e): ?>
      <div class="event-card-dash">
        <div class="event-card-img gradient-<?= ['free'=>1,'paid'=>2,'private'=>3][$e['event_type']] ?? 1 ?>">
          <div style="position:absolute;top:12px;left:12px"><?= getTypeBadge($e['event_type']) ?></div>
          <div style="position:absolute;top:12px;right:12px"><?= getEventStatusBadge($e['status']) ?></div>
        </div>
        <div class="event-card-body-dash">
          <h3><?= htmlspecialchars($e['title']) ?></h3>
          <div style="display:flex;gap:1rem;margin-top:.5rem;flex-wrap:wrap">
            <div class="event-meta-item"><i class="fas fa-calendar"></i> <?= date('M d, Y', strtotime($e['date'])) ?></div>
            <div class="event-meta-item"><i class="fas fa-users"></i> <?= $e['reg_count'] ?> registered</div>
            <?php if ($e['avg_rating']): ?>
              <div class="event-meta-item"><i class="fas fa-star" style="color:#ff9f43"></i> <?= round($e['avg_rating'],1) ?></div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<style>
.gradient-1{background:linear-gradient(135deg,#6c63ff,#f72585);}
.gradient-2{background:linear-gradient(135deg,#f72585,#ff9f43);}
.gradient-3{background:linear-gradient(135deg,#4cc9f0,#1dd3b0);}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

