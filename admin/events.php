<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
requireLogin('admin');
$pageTitle = 'Manage Events';

global $conn;
$msg = $err = '';

// Handle approve/reject
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id     = (int)$_GET['id'];
    $action = $_GET['action'];
    $note   = sanitize($_POST['note'] ?? '');

    if ($action === 'approve') {
        $conn->query("UPDATE events SET status='approved' WHERE id=$id");
        $event = $conn->query("SELECT e.*, u.id as uid, u.full_name FROM events e LEFT JOIN users u ON e.organizer_id=u.id WHERE e.id=$id")->fetch_assoc();
        if ($event) addNotification($event['uid'], 'Event Approved! 🎉', "Your event \"{$event['title']}\" has been approved and is now live!", 'success');
        $msg = 'Event approved and published!';
    } elseif ($action === 'reject') {
        $conn->query("UPDATE events SET status='rejected', admin_note='$note' WHERE id=$id");
        $event = $conn->query("SELECT e.*, u.id as uid, u.full_name FROM events e LEFT JOIN users u ON e.organizer_id=u.id WHERE e.id=$id")->fetch_assoc();
        if ($event) addNotification($event['uid'], 'Event Not Approved', "Your event \"{$event['title']}\" was not approved. Reason: $note", 'error');
        $msg = 'Event rejected.';
    } elseif ($action === 'delete') {
        $conn->query("DELETE FROM events WHERE id=$id");
        $msg = 'Event deleted.';
    }
}

$filter = sanitize($_GET['filter'] ?? 'all');
$search = sanitize($_GET['search'] ?? '');
$where  = [];
if ($filter !== 'all') $where[] = "e.status = '$filter'";
if ($search) $where[] = "(e.title LIKE '%$search%' OR u.full_name LIKE '%$search%')";
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$events = $conn->query("SELECT e.*, u.full_name as organizer_name, v.name as venue_name,
    (SELECT COUNT(*) FROM registrations r WHERE r.event_id=e.id) as reg_count
    FROM events e
    LEFT JOIN users u ON e.organizer_id=u.id
    LEFT JOIN venues v ON e.venue_id=v.id
    $whereSQL ORDER BY e.created_at DESC")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div><h1>Manage Events</h1><p>Review, approve, or reject event proposals</p></div>
</div>

<?php if ($msg): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $msg ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-error"><i class="fas fa-times-circle"></i> <?= $err ?></div><?php endif; ?>

<!-- Filters -->
<div class="card" style="margin-bottom:1.5rem">
  <div class="card-body" style="display:flex;gap:1rem;flex-wrap:wrap;align-items:center">
    <form method="GET" style="display:flex;gap:.8rem;flex:1;flex-wrap:wrap">
      <div style="display:flex;gap:.4rem">
        <?php foreach(['all','pending','approved','rejected'] as $f): ?>
          <a href="?filter=<?= $f ?>&search=<?= urlencode($search) ?>"
            class="btn btn-sm <?= $filter===$f?'btn-primary':'btn-outline' ?>">
            <?= ucfirst($f) ?>
            <?php
              $cnt = $conn->query("SELECT COUNT(*) c FROM events WHERE status='$f'")->fetch_assoc()['c'];
              if ($f !== 'all') echo "<span style='margin-left:.3rem;opacity:.7'>($cnt)</span>";
            ?>
          </a>
        <?php endforeach; ?>
      </div>
      <div style="display:flex;gap:.5rem;margin-left:auto">
        <input type="hidden" name="filter" value="<?= $filter ?>">
        <input type="text" name="search" class="form-control" style="width:220px"
          placeholder="Search events..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
      </div>
    </form>
  </div>
</div>

<!-- Events Table -->
<div class="card">
  <div class="card-body" style="padding:0">
    <div class="table-wrap">
      <table>
        <thead><tr>
          <th>#</th><th>Event</th><th>Organizer</th><th>Type</th><th>Date</th>
          <th>Venue</th><th>Registrations</th><th>Status</th><th>Actions</th>
        </tr></thead>
        <tbody>
          <?php if (empty($events)): ?>
            <tr><td colspan="9" style="text-align:center;color:var(--text-muted);padding:3rem">No events found.</td></tr>
          <?php else: ?>
            <?php foreach ($events as $i => $e): ?>
              <tr>
                <td style="color:var(--text-muted)"><?= $i+1 ?></td>
                <td>
                  <div style="font-weight:600;color:var(--white)"><?= htmlspecialchars($e['title']) ?></div>
                  <div style="font-size:.75rem;color:var(--text-muted)"><?= htmlspecialchars($e['sector'] ?? '—') ?></div>
                </td>
                <td><?= htmlspecialchars($e['organizer_name']) ?></td>
                <td><?= getTypeBadge($e['event_type']) ?></td>
                <td><?= date('M d, Y', strtotime($e['date'])) ?><br><small style="color:var(--text-muted)"><?= date('h:i A', strtotime($e['time'])) ?></small></td>
                <td><?= htmlspecialchars($e['venue_name'] ?? '—') ?></td>
                <td style="text-align:center"><span style="font-weight:700;color:var(--white)"><?= $e['reg_count'] ?></span></td>
                <td><?= getEventStatusBadge($e['status']) ?></td>
                <td>
                  <div style="display:flex;gap:.4rem;flex-wrap:wrap">
                    <?php if ($e['status'] === 'pending'): ?>
                      <a href="?filter=<?= $filter ?>&action=approve&id=<?= $e['id'] ?>"
                        class="btn btn-sm btn-success" onclick="return confirm('Approve this event?')">
                        <i class="fas fa-check"></i> Approve
                      </a>
                      <button class="btn btn-sm btn-danger" onclick="showRejectModal(<?= $e['id'] ?>)">
                        <i class="fas fa-times"></i> Reject
                      </button>
                    <?php endif; ?>
                    <a href="?filter=<?= $filter ?>&action=delete&id=<?= $e['id'] ?>"
                      class="btn btn-sm btn-outline" onclick="return confirm('Delete this event permanently?')">
                      <i class="fas fa-trash"></i>
                    </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Reject Modal -->
<div class="modal-overlay" id="rejectModal">
  <div class="modal">
    <div class="modal-header">
      <h3>Reject Event</h3>
      <button class="modal-close" onclick="closeRejectModal()">&times;</button>
    </div>
    <form method="POST" id="rejectForm">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Reason for Rejection (optional)</label>
          <textarea name="note" class="form-control" rows="4" placeholder="Explain why this event is being rejected..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeRejectModal()">Cancel</button>
        <button type="submit" class="btn btn-danger"><i class="fas fa-times"></i> Reject Event</button>
      </div>
    </form>
  </div>
</div>

<script>
function showRejectModal(id) {
  document.getElementById('rejectModal').classList.add('show');
  document.getElementById('rejectForm').action = '?filter=<?= $filter ?>&action=reject&id=' + id;
}
function closeRejectModal() {
  document.getElementById('rejectModal').classList.remove('show');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
