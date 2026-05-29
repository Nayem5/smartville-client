<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
requireLogin('resident');
$pageTitle = 'Dashboard';

global $conn;
$uid = $_SESSION['user_id'];

$myRegs    = $conn->query("SELECT COUNT(*) c FROM registrations WHERE user_id=$uid")->fetch_assoc()['c'];
$upcoming  = $conn->query("SELECT COUNT(*) c FROM registrations r JOIN events e ON r.event_id=e.id WHERE r.user_id=$uid AND e.date >= CURDATE() AND e.status='approved'")->fetch_assoc()['c'];
$feedbacks = $conn->query("SELECT COUNT(*) c FROM feedback WHERE user_id=$uid")->fetch_assoc()['c'];
$total     = $conn->query("SELECT COUNT(*) c FROM events WHERE status='approved'")->fetch_assoc()['c'];

// Upcoming events for this user
$myUpcoming = $conn->query("SELECT e.*, v.name as venue_name FROM registrations r
    JOIN events e ON r.event_id=e.id LEFT JOIN venues v ON e.venue_id=v.id
    WHERE r.user_id=$uid AND e.date >= CURDATE() AND e.status='approved'
    ORDER BY e.date ASC LIMIT 3")->fetch_all(MYSQLI_ASSOC);

// Recommended events (not registered)
$recommended = $conn->query("SELECT e.*, v.name as venue_name,
    (SELECT COUNT(*) FROM registrations r2 WHERE r2.event_id=e.id) as reg_count
    FROM events e LEFT JOIN venues v ON e.venue_id=v.id
    WHERE e.status='approved' AND e.date >= CURDATE()
    AND e.id NOT IN (SELECT event_id FROM registrations WHERE user_id=$uid)
    AND (e.event_type != 'private')
    ORDER BY e.date ASC LIMIT 4")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1>Welcome, <?= htmlspecialchars($currentUser['full_name']) ?>!</h1>
    <p>Discover and join community events in Segamat</p>
  </div>
  <a href="/fyp_soop/smartville/resident/browse.php" class="btn btn-primary"><i class="fas fa-search"></i> Browse Events</a>
</div>

<div class="stats-grid">
  <div class="stat-card" style="--clr:#6c63ff;--icon-bg:rgba(108,99,255,.15);--icon-clr:#6c63ff">
    <div class="stat-icon"><i class="fas fa-ticket-alt"></i></div>
    <div class="stat-info"><h3><?= $myRegs ?></h3><p>Events Joined</p></div>
  </div>
  <div class="stat-card" style="--clr:#1dd3b0;--icon-bg:rgba(29,211,176,.15);--icon-clr:#1dd3b0">
    <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
    <div class="stat-info"><h3><?= $upcoming ?></h3><p>Upcoming Events</p></div>
  </div>
  <div class="stat-card" style="--clr:#f72585;--icon-bg:rgba(247,37,133,.15);--icon-clr:#f72585">
    <div class="stat-icon"><i class="fas fa-star"></i></div>
    <div class="stat-info"><h3><?= $feedbacks ?></h3><p>Reviews Given</p></div>
  </div>
  <div class="stat-card" style="--clr:#4cc9f0;--icon-bg:rgba(76,201,240,.15);--icon-clr:#4cc9f0">
    <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
    <div class="stat-info"><h3><?= $total ?></h3><p>Events Available</p></div>
  </div>
</div>

<?php if (!empty($myUpcoming)): ?>
<div class="page-header" style="margin-bottom:1rem"><h3 style="color:var(--white)">Your Upcoming Events</h3></div>
<div class="events-grid" style="margin-bottom:2rem">
  <?php foreach ($myUpcoming as $e): ?>
    <div class="event-card-dash">
      <div class="event-card-img gradient-<?= ['free'=>1,'paid'=>2,'private'=>3][$e['event_type']] ?? 1 ?>">
        <div style="position:absolute;top:12px;left:12px"><?= getTypeBadge($e['event_type']) ?></div>
        <div style="position:absolute;top:12px;right:12px;background:rgba(29,211,176,.9);color:#0a0a1a;padding:3px 10px;border-radius:20px;font-size:.7rem;font-weight:700;">REGISTERED</div>
      </div>
      <div class="event-card-body-dash">
        <h3><?= htmlspecialchars($e['title']) ?></h3>
        <div style="display:flex;gap:1rem;margin-top:.5rem;flex-wrap:wrap">
          <div class="event-meta-item"><i class="fas fa-calendar"></i> <?= date('M d, Y', strtotime($e['date'])) ?></div>
          <div class="event-meta-item"><i class="fas fa-clock"></i> <?= date('h:i A', strtotime($e['time'])) ?></div>
          <?php if ($e['venue_name']): ?>
            <div class="event-meta-item"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($e['venue_name']) ?></div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="page-header" style="margin-bottom:1rem">
  <h3 style="color:var(--white)">Recommended For You</h3>
  <a href="/fyp_soop/smartville/resident/browse.php" class="btn btn-outline btn-sm">View All</a>
</div>

<?php if (empty($recommended)): ?>
  <div class="empty-state card" style="padding:3rem">
    <i class="fas fa-calendar-check" style="color:#1dd3b0"></i>
    <h3>You've joined all available events!</h3>
    <p>Check back soon for new community activities.</p>
  </div>
<?php else: ?>
  <div class="events-grid">
    <?php foreach ($recommended as $e): ?>
      <a href="/fyp_soop/smartville/resident/event_detail.php?id=<?= $e['id'] ?>" style="text-decoration:none">
        <div class="event-card-dash">
          <div class="event-card-img gradient-<?= ['free'=>1,'paid'=>2,'private'=>3][$e['event_type']] ?? 1 ?>">
            <div style="position:absolute;top:12px;left:12px"><?= getTypeBadge($e['event_type']) ?></div>
          </div>
          <div class="event-card-body-dash">
            <h3><?= htmlspecialchars($e['title']) ?></h3>
            <div style="display:flex;gap:1rem;margin-top:.5rem;flex-wrap:wrap">
              <div class="event-meta-item"><i class="fas fa-calendar"></i> <?= date('M d, Y', strtotime($e['date'])) ?></div>
              <div class="event-meta-item"><i class="fas fa-users"></i> <?= $e['reg_count'] ?> joined</div>
            </div>
          </div>
          <div class="event-card-footer">
            <div class="event-meta-item"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($e['venue_name'] ?? 'TBA') ?></div>
            <span class="btn btn-sm btn-primary">View</span>
          </div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<style>
.gradient-1{background:linear-gradient(135deg,#6c63ff,#f72585);}
.gradient-2{background:linear-gradient(135deg,#f72585,#ff9f43);}
.gradient-3{background:linear-gradient(135deg,#4cc9f0,#1dd3b0);}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

