<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
requireLogin('organizer');
$pageTitle = 'My Events';

global $conn;
$uid = $_SESSION['user_id'];

if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($_GET['action'] === 'cancel') {
        $conn->query("UPDATE events SET status='cancelled' WHERE id=$id AND organizer_id=$uid");
    }
}

$filter = sanitize($_GET['filter'] ?? 'all');
$where = "WHERE e.organizer_id=$uid";
if ($filter !== 'all') $where .= " AND e.status='$filter'";

$events = $conn->query("SELECT e.*, v.name as venue_name,
    (SELECT COUNT(*) FROM registrations r WHERE r.event_id=e.id) as reg_count,
    (SELECT AVG(rating) FROM feedback f WHERE f.event_id=e.id) as avg_rating
    FROM events e LEFT JOIN venues v ON e.venue_id=v.id
    $where ORDER BY e.date DESC")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div><h1>My Events</h1><p>Track and manage all your event submissions</p></div>
  <a href="/fyp_soop/smartville/organizer/create_event.php" class="btn btn-primary"><i class="fas fa-plus"></i> New Event</a>
</div>

<div class="card" style="margin-bottom:1.5rem">
  <div class="card-body" style="display:flex;gap:.4rem;flex-wrap:wrap">
    <?php foreach(['all','pending','approved','rejected','cancelled'] as $f): ?>
      <a href="?filter=<?= $f ?>" class="btn btn-sm <?= $filter===$f?'btn-primary':'btn-outline' ?>"><?= ucfirst($f) ?></a>
    <?php endforeach; ?>
  </div>
</div>

<?php if (empty($events)): ?>
  <div class="empty-state card" style="padding:4rem">
    <i class="fas fa-calendar-times" style="color:var(--text-muted)"></i>
    <h3>No events found</h3>
    <p>Create your first event to get started!</p>
    <a href="/fyp_soop/smartville/organizer/create_event.php" class="btn btn-primary" style="margin-top:1rem"><i class="fas fa-plus"></i> Create Event</a>
  </div>
<?php else: ?>
  <div class="card">
    <div class="card-body" style="padding:0">
      <div class="table-wrap">
        <table>
          <thead><tr><th>Event</th><th>Type</th><th>Date</th><th>Venue</th><th>Registrations</th><th>Rating</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody>
            <?php foreach ($events as $e): ?>
              <tr>
                <td>
                  <div style="font-weight:600;color:var(--white)"><?= htmlspecialchars($e['title']) ?></div>
                  <div style="font-size:.75rem;color:var(--text-muted)"><?= htmlspecialchars($e['sector'] ?? 'â€”') ?></div>
                  <?php if ($e['status']==='rejected' && $e['admin_note']): ?>
                    <div style="font-size:.75rem;color:#ff6b6b;margin-top:.2rem"><i class="fas fa-info-circle"></i> <?= htmlspecialchars($e['admin_note']) ?></div>
                  <?php endif; ?>
                </td>
                <td><?= getTypeBadge($e['event_type']) ?></td>
                <td><?= date('M d, Y', strtotime($e['date'])) ?><br><small style="color:var(--text-muted)"><?= date('h:i A', strtotime($e['time'])) ?></small></td>
                <td><?= htmlspecialchars($e['venue_name'] ?? 'â€”') ?></td>
                <td style="text-align:center"><strong style="color:var(--white)"><?= $e['reg_count'] ?></strong></td>
                <td>
                  <?php if ($e['avg_rating']): ?>
                    <span style="color:#ff9f43"><i class="fas fa-star"></i> <?= round($e['avg_rating'],1) ?></span>
                  <?php else: ?>
                    <span style="color:var(--text-muted)">â€”</span>
                  <?php endif; ?>
                </td>
                <td><?= getEventStatusBadge($e['status']) ?></td>
                <td>
                  <?php if ($e['status'] === 'approved'): ?>
                    <a href="?action=cancel&id=<?= $e['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Cancel this event?')"><i class="fas fa-ban"></i> Cancel</a>
                  <?php elseif ($e['status'] === 'rejected'): ?>
                    <a href="/fyp_soop/smartville/organizer/create_event.php" class="btn btn-sm btn-warning"><i class="fas fa-redo"></i> Resubmit</a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

