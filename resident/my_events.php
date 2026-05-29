<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
requireLogin('resident');
$pageTitle = 'My Events';

global $conn;
$uid    = $_SESSION['user_id'];
$filter = sanitize($_GET['filter'] ?? 'upcoming');

$where = "WHERE r.user_id=$uid";
if ($filter === 'upcoming') $where .= " AND e.date >= CURDATE()";
elseif ($filter === 'past')  $where .= " AND e.date < CURDATE()";

$events = $conn->query("SELECT e.*, v.name as venue_name, r.registered_at, r.payment_status,
    (SELECT AVG(rating) FROM feedback f WHERE f.event_id=e.id) as avg_rating,
    (SELECT COUNT(*) FROM feedback fme WHERE fme.event_id=e.id AND fme.user_id=$uid) as gave_feedback
    FROM registrations r JOIN events e ON r.event_id=e.id LEFT JOIN venues v ON e.venue_id=v.id
    $where ORDER BY e.date DESC")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div><h1>My Events</h1><p>Events you've registered for</p></div>
  <a href="/fyp_soop/smartville/resident/browse.php" class="btn btn-primary"><i class="fas fa-search"></i> Browse More</a>
</div>

<div class="card" style="margin-bottom:1.5rem">
  <div class="card-body" style="display:flex;gap:.4rem">
    <a href="?filter=upcoming" class="btn btn-sm <?= $filter==='upcoming'?'btn-primary':'btn-outline' ?>">Upcoming</a>
    <a href="?filter=past" class="btn btn-sm <?= $filter==='past'?'btn-primary':'btn-outline' ?>">Past</a>
    <a href="?filter=all" class="btn btn-sm <?= $filter==='all'?'btn-primary':'btn-outline' ?>">All</a>
  </div>
</div>

<?php if (empty($events)): ?>
  <div class="empty-state card" style="padding:4rem">
    <i class="fas fa-ticket-alt"></i>
    <h3>No events registered</h3>
    <p>Browse and join community events!</p>
    <a href="/fyp_soop/smartville/resident/browse.php" class="btn btn-primary" style="margin-top:1rem"><i class="fas fa-search"></i> Browse Events</a>
  </div>
<?php else: ?>
  <div class="card">
    <div class="card-body" style="padding:0">
      <div class="table-wrap">
        <table>
          <thead><tr><th>Event</th><th>Type</th><th>Date</th><th>Venue</th><th>Payment</th><th>Rating</th><th>Actions</th></tr></thead>
          <tbody>
            <?php foreach ($events as $e): ?>
              <tr>
                <td>
                  <div style="font-weight:600;color:var(--white)"><?= htmlspecialchars($e['title']) ?></div>
                  <div style="font-size:.75rem;color:var(--text-muted)">Registered <?= timeAgo($e['registered_at']) ?></div>
                </td>
                <td><?= getTypeBadge($e['event_type']) ?></td>
                <td><?= date('M d, Y', strtotime($e['date'])) ?></td>
                <td><?= htmlspecialchars($e['venue_name'] ?? 'â€”') ?></td>
                <td>
                  <?php if ($e['payment_status']==='paid'): ?>
                    <span class="badge badge-success">Paid</span>
                  <?php elseif ($e['payment_status']==='not_required'): ?>
                    <span class="badge badge-secondary">Free</span>
                  <?php else: ?>
                    <span class="badge badge-warning"><?= ucfirst($e['payment_status']) ?></span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if (strtotime($e['date']) < strtotime(date('Y-m-d'))): ?>
                    <?php if ($e['gave_feedback']): ?>
                      <span style="color:#1dd3b0"><i class="fas fa-check"></i> Reviewed</span>
                    <?php else: ?>
                      <a href="/fyp_soop/smartville/resident/event_detail.php?id=<?= $e['id'] ?>#feedback" class="btn btn-sm btn-warning"><i class="fas fa-star"></i> Review</a>
                    <?php endif; ?>
                  <?php else: ?>
                    <span style="color:var(--text-muted)">Upcoming</span>
                  <?php endif; ?>
                </td>
                <td>
                  <a href="/fyp_soop/smartville/resident/event_detail.php?id=<?= $e['id'] ?>" class="btn btn-sm btn-outline"><i class="fas fa-eye"></i> View</a>
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

