<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
requireLogin('resident');
$pageTitle = 'Browse Events';

global $conn;
$uid    = $_SESSION['user_id'];
$search = sanitize($_GET['search'] ?? '');
$type   = sanitize($_GET['type'] ?? 'all');
$sort   = sanitize($_GET['sort'] ?? 'date');

$where = ["e.status='approved'", "e.date >= CURDATE()"];
if ($search) $where[] = "(e.title LIKE '%$search%' OR e.description LIKE '%$search%' OR e.sector LIKE '%$search%')";
if ($type !== 'all') $where[] = "e.event_type = '$type'";
$whereSQL = 'WHERE ' . implode(' AND ', $where);
$orderSQL = $sort === 'popular' ? 'reg_count DESC' : 'e.date ASC';

$events = $conn->query("SELECT e.*, v.name as venue_name,
    (SELECT COUNT(*) FROM registrations r WHERE r.event_id=e.id) as reg_count,
    (SELECT AVG(rating) FROM feedback f WHERE f.event_id=e.id) as avg_rating
    FROM events e LEFT JOIN venues v ON e.venue_id=v.id
    $whereSQL ORDER BY $orderSQL")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div><h1>Browse Events</h1><p>Discover what's happening in your community</p></div>
</div>

<!-- Search & Filter -->
<div class="card" style="margin-bottom:1.5rem">
  <div class="card-body">
    <form method="GET" style="display:flex;gap:1rem;flex-wrap:wrap;align-items:center">
      <div style="flex:1;min-width:200px">
        <div style="position:relative">
          <i class="fas fa-search" style="position:absolute;left:1rem;top:50%;transform:translateY(-50%);color:var(--text-muted)"></i>
          <input type="text" name="search" class="form-control" style="padding-left:2.8rem"
            placeholder="Search events..." value="<?= htmlspecialchars($search) ?>"/>
        </div>
      </div>
      <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
      <div style="display:flex;gap:.4rem">
        <?php foreach(['all'=>'All','free'=>'Free','paid'=>'Paid','private'=>'Private'] as $k=>$v): ?>
          <a href="?search=<?= urlencode($search) ?>&type=<?= $k ?>&sort=<?= $sort ?>"
            class="btn btn-sm <?= $type===$k?'btn-primary':'btn-outline' ?>"><?= $v ?></a>
        <?php endforeach; ?>
      </div>
      <select name="sort" class="form-control" style="width:auto" onchange="this.form.submit()">
        <option value="date" <?= $sort==='date'?'selected':'' ?>>Sort: Date</option>
        <option value="popular" <?= $sort==='popular'?'selected':'' ?>>Sort: Popular</option>
      </select>
    </form>
  </div>
</div>

<?php if (empty($events)): ?>
  <div class="empty-state card" style="padding:4rem">
    <i class="fas fa-calendar-times"></i>
    <h3>No events found</h3>
    <p>Try different search terms or check back later.</p>
  </div>
<?php else: ?>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:1.5rem">
    <?php foreach ($events as $e): ?>
      <?php $registered = isRegistered($e['id'], $uid); ?>
      <div class="event-card-dash" style="position:relative">
        <a href="/fyp_soop/smartville/resident/event_detail.php?id=<?= $e['id'] ?>" style="text-decoration:none;display:block">
          <div class="event-card-img gradient-<?= ['free'=>1,'paid'=>2,'private'=>3][$e['event_type']] ?? 1 ?>" style="height:180px">
            <?php if ($e['poster']): ?>
              <img src="/fyp_soop/smartville/uploads/posters/<?= htmlspecialchars($e['poster']) ?>" style="width:100%;height:100%;object-fit:cover"/>
            <?php endif; ?>
            <div style="position:absolute;top:12px;left:12px"><?= getTypeBadge($e['event_type']) ?></div>
            <?php if ($registered): ?>
              <div style="position:absolute;top:12px;right:12px;background:rgba(29,211,176,.9);color:#0a0a1a;padding:3px 10px;border-radius:20px;font-size:.7rem;font-weight:700;">REGISTERED</div>
            <?php endif; ?>
          </div>
        </a>
        <div class="event-card-body-dash">
          <a href="/fyp_soop/smartville/resident/event_detail.php?id=<?= $e['id'] ?>" style="text-decoration:none">
            <h3 style="color:var(--white)"><?= htmlspecialchars($e['title']) ?></h3>
          </a>
          <p style="margin:.4rem 0"><?= htmlspecialchars(substr($e['description'] ?? '', 0, 80)) ?>...</p>
          <div style="display:flex;gap:1rem;margin-top:.5rem;flex-wrap:wrap">
            <div class="event-meta-item"><i class="fas fa-calendar"></i> <?= date('M d, Y', strtotime($e['date'])) ?></div>
            <div class="event-meta-item"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($e['venue_name'] ?? 'TBA') ?></div>
          </div>
        </div>
        <div class="event-card-footer">
          <div style="display:flex;align-items:center;gap:.8rem">
            <div class="event-meta-item"><i class="fas fa-users"></i> <?= $e['reg_count'] ?></div>
            <?php if ($e['avg_rating']): ?><div class="event-meta-item"><i class="fas fa-star" style="color:#ff9f43"></i> <?= round($e['avg_rating'],1) ?></div><?php endif; ?>
            <?php if ($e['event_type']==='paid'): ?>
              <div style="font-weight:700;color:#ff9f43">RM<?= number_format($e['price'],2) ?></div>
            <?php endif; ?>
          </div>
          <a href="/fyp_soop/smartville/resident/event_detail.php?id=<?= $e['id'] ?>" class="btn btn-sm btn-primary">
            <?= $registered ? 'View' : 'Join' ?>
          </a>
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

