<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
requireLogin('resident');
$pageTitle = 'Event Detail';

global $conn;
$uid = $_SESSION['user_id'];
$id  = (int)($_GET['id'] ?? 0);

$event = $conn->query("SELECT e.*, u.full_name as organizer_name, v.name as venue_name, v.price_per_day
    FROM events e LEFT JOIN users u ON e.organizer_id=u.id LEFT JOIN venues v ON e.venue_id=v.id
    WHERE e.id=$id AND e.status='approved'")->fetch_assoc();

if (!$event) { redirect(BASE_PATH . '/resident/browse.php'); }

$registered   = isRegistered($id, $uid);
$feedbackGiven = hasGivenFeedback($id, $uid);
$regCount     = getRegistrationCount($id);
$avgRating    = getAverageRating($id);
$eventPast    = strtotime($event['date']) < strtotime(date('Y-m-d'));

$msg = $err = '';

// Handle join
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'join' && !$registered) {
        $canJoin = false;

        if ($event['event_type'] === 'private') {
            $inv = $conn->query("SELECT id FROM event_invites WHERE event_id=$id AND (user_id=$uid OR email='{$conn->real_escape_string($currentUser['email'])}')")->num_rows;
            if (!$inv) { $err = 'You are not invited to this private event.'; }
            else { $canJoin = true; }
        } else {
            $canJoin = true;
        }

        if ($canJoin) {
            if ($event['event_type'] === 'paid') {
                $conn->query("INSERT INTO registrations (event_id,user_id,payment_status,payment_amount) VALUES ($id,$uid,'paid',{$event['price']}) ON DUPLICATE KEY UPDATE payment_status='paid'");
            } else {
                $conn->query("INSERT INTO registrations (event_id,user_id,payment_status) VALUES ($id,$uid,'not_required') ON DUPLICATE KEY UPDATE payment_status='not_required'");
            }
            addNotification($uid, 'Registration Confirmed!', "You've successfully joined \"{$event['title']}\"!", 'success');
            $msg = 'You have successfully joined this event!';
            $registered = true; $regCount++;
        }
    } elseif ($action === 'feedback' && $registered && $eventPast && !$feedbackGiven) {
        $rating  = (int)($_POST['rating'] ?? 0);
        $comment = sanitize($_POST['comment'] ?? '');
        if ($rating < 1 || $rating > 5) { $err = 'Please select a rating.'; }
        else {
            $stmt = $conn->prepare("INSERT INTO feedback (event_id,user_id,rating,comment) VALUES (?,?,?,?)");
            $stmt->bind_param('iiis', $id, $uid, $rating, $comment);
            $stmt->execute(); $stmt->close();
            $feedbackGiven = true;
            $msg = 'Thank you for your feedback!';
            $avgRating = getAverageRating($id);
        }
    }
}

// Get all feedback
$allFeedback = $conn->query("SELECT f.*, u.full_name FROM feedback f JOIN users u ON f.user_id=u.id WHERE f.event_id=$id ORDER BY f.created_at DESC")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<div style="margin-bottom:1rem">
  <a href="/fyp_soop/smartville/resident/browse.php" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back to Events</a>
</div>

<?php if ($msg): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $msg ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-error"><i class="fas fa-times-circle"></i> <?= $err ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:2rem;align-items:start">
  <!-- Left: Event Info -->
  <div>
    <div class="card" style="margin-bottom:1.5rem;overflow:hidden">
      <div class="event-card-img gradient-<?= ['free'=>1,'paid'=>2,'private'=>3][$event['event_type']] ?? 1 ?>" style="height:300px">
        <?php if ($event['poster']): ?>
          <img src="/fyp_soop/smartville/uploads/posters/<?= htmlspecialchars($event['poster']) ?>" style="width:100%;height:100%;object-fit:cover"/>
        <?php endif; ?>
        <div style="position:absolute;top:16px;left:16px"><?= getTypeBadge($event['event_type']) ?></div>
        <?php if ($eventPast): ?>
          <div style="position:absolute;top:16px;right:16px;background:rgba(255,255,255,.15);backdrop-filter:blur(10px);color:#fff;padding:5px 14px;border-radius:20px;font-size:.8rem;font-weight:700;">PAST EVENT</div>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <h1 style="font-size:1.8rem;color:var(--white);margin-bottom:1rem"><?= htmlspecialchars($event['title']) ?></h1>
        <div style="display:flex;flex-wrap:wrap;gap:1.5rem;margin-bottom:1.5rem">
          <div class="event-meta-item" style="font-size:.95rem"><i class="fas fa-calendar"></i> <?= date('D, M d, Y', strtotime($event['date'])) ?></div>
          <div class="event-meta-item" style="font-size:.95rem"><i class="fas fa-clock"></i> <?= date('h:i A', strtotime($event['time'])) ?><?= $event['end_time'] ? ' &ndash; '.date('h:i A', strtotime($event['end_time'])) : '' ?></div>
          <div class="event-meta-item" style="font-size:.95rem"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($event['venue_name'] ?? 'TBA') ?></div>
          <div class="event-meta-item" style="font-size:.95rem"><i class="fas fa-user"></i> <?= htmlspecialchars($event['organizer_name']) ?></div>
          <div class="event-meta-item" style="font-size:.95rem"><i class="fas fa-users"></i> <?= $regCount ?> attending</div>
          <?php if ($avgRating): ?><div class="event-meta-item" style="font-size:.95rem"><i class="fas fa-star" style="color:#ff9f43"></i> <?= $avgRating ?>/5</div><?php endif; ?>
        </div>
        <h3 style="color:var(--white);margin-bottom:.7rem">About This Event</h3>
        <p style="color:var(--text-muted);line-height:1.8"><?= nl2br(htmlspecialchars($event['description'] ?? 'No description provided.')) ?></p>
      </div>
    </div>

    <!-- Program Flow -->
    <?php if ($event['program_flow']): ?>
      <div class="card" style="margin-bottom:1.5rem">
        <div class="card-header"><h3><i class="fas fa-list" style="color:var(--primary);margin-right:.5rem"></i>Program Flow</h3></div>
        <div class="card-body">
          <pre style="color:var(--text-muted);font-family:'Poppins',sans-serif;line-height:2;white-space:pre-wrap"><?= htmlspecialchars($event['program_flow']) ?></pre>
        </div>
      </div>
    <?php endif; ?>

    <!-- Feedback Section -->
    <?php if ($registered && $eventPast && !$feedbackGiven): ?>
      <div class="card" style="margin-bottom:1.5rem">
        <div class="card-header"><h3><i class="fas fa-star" style="color:#ff9f43;margin-right:.5rem"></i>Leave Your Feedback</h3></div>
        <div class="card-body">
          <form method="POST">
            <input type="hidden" name="action" value="feedback"/>
            <div class="form-group" style="margin-bottom:1.2rem">
              <label class="form-label">Your Rating *</label>
              <div class="star-input" id="starInput">
                <?php for ($i=1;$i<=5;$i++): ?>
                  <i class="fas fa-star" data-value="<?= $i ?>" style="font-size:2rem;color:var(--card-border);cursor:pointer;margin-right:.3rem;transition:color .2s"></i>
                <?php endfor; ?>
                <input type="hidden" name="rating" id="ratingInput" value="0"/>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Your Comment</label>
              <textarea name="comment" class="form-control" rows="4" placeholder="Share your experience..."></textarea>
            </div>
            <button type="submit" class="btn btn-primary" style="margin-top:1rem"><i class="fas fa-paper-plane"></i> Submit Feedback</button>
          </form>
        </div>
      </div>
    <?php endif; ?>

    <!-- All Feedback -->
    <?php if (!empty($allFeedback)): ?>
      <div class="card">
        <div class="card-header"><h3><i class="fas fa-comments" style="color:var(--primary);margin-right:.5rem"></i>Community Reviews (<?= count($allFeedback) ?>)</h3></div>
        <div class="card-body" style="padding:0">
          <?php foreach ($allFeedback as $f): ?>
            <div style="padding:1.2rem;border-bottom:1px solid var(--card-border)">
              <div style="display:flex;align-items:center;gap:.8rem;margin-bottom:.7rem">
                <div style="width:36px;height:36px;border-radius:50%;background:var(--gradient);display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:700;color:#fff"><?= strtoupper(substr($f['full_name'],0,2)) ?></div>
                <div>
                  <strong style="color:var(--white)"><?= htmlspecialchars($f['full_name']) ?></strong>
                  <div style="display:flex;gap:2px">
                    <?php for ($i=1;$i<=5;$i++): ?>
                      <i class="fas fa-star" style="font-size:.8rem;color:<?= $i<=$f['rating']?'#ff9f43':'var(--card-border)' ?>"></i>
                    <?php endfor; ?>
                  </div>
                </div>
                <span style="margin-left:auto;font-size:.75rem;color:var(--text-muted)"><?= timeAgo($f['created_at']) ?></span>
              </div>
              <?php if ($f['comment']): ?>
                <p style="color:var(--text-muted);font-size:.9rem"><?= htmlspecialchars($f['comment']) ?></p>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <!-- Right: Join Card -->
  <div>
    <div class="card" style="position:sticky;top:90px">
      <div class="card-body">
        <?php if ($event['event_type'] === 'paid'): ?>
          <div style="font-size:2rem;font-weight:800;color:#ff9f43;margin-bottom:.5rem">RM<?= number_format($event['price'],2) ?></div>
          <div style="color:var(--text-muted);font-size:.85rem;margin-bottom:1.2rem">per ticket</div>
        <?php elseif ($event['event_type'] === 'free'): ?>
          <div style="font-size:1.5rem;font-weight:800;color:#1dd3b0;margin-bottom:1.2rem">FREE EVENT</div>
        <?php else: ?>
          <div style="font-size:1.2rem;font-weight:700;color:var(--secondary);margin-bottom:1.2rem"><i class="fas fa-lock"></i> Private Event</div>
        <?php endif; ?>

        <?php if (!$eventPast): ?>
          <?php if ($registered): ?>
            <div class="alert alert-success" style="margin-bottom:1rem"><i class="fas fa-check-circle"></i> You're registered!</div>
          <?php else: ?>
            <form method="POST">
              <input type="hidden" name="action" value="join"/>
              <?php if ($event['event_type'] === 'paid'): ?>
                <div class="form-group" style="margin-bottom:1rem">
                  <label class="form-label">Card Number</label>
                  <input type="text" class="form-control" placeholder="1234 5678 9012 3456" maxlength="19"/>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.7rem;margin-bottom:1rem">
                  <div class="form-group">
                    <label class="form-label">Expiry</label>
                    <input type="text" class="form-control" placeholder="MM/YY"/>
                  </div>
                  <div class="form-group">
                    <label class="form-label">CVV</label>
                    <input type="text" class="form-control" placeholder="123"/>
                  </div>
                </div>
              <?php endif; ?>
              <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">
                <?= $event['event_type']==='paid' ? '<i class="fas fa-credit-card"></i> Pay & Register' : '<i class="fas fa-ticket-alt"></i> Join Now' ?>
              </button>
            </form>
          <?php endif; ?>
        <?php else: ?>
          <div class="alert alert-info"><i class="fas fa-info-circle"></i> This event has ended</div>
          <?php if ($registered && !$feedbackGiven): ?>
            <a href="#feedback" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:.5rem"><i class="fas fa-star"></i> Leave Feedback</a>
          <?php endif; ?>
        <?php endif; ?>

        <hr style="border-color:var(--card-border);margin:1.2rem 0"/>
        <div style="display:flex;flex-direction:column;gap:.7rem">
          <div class="event-meta-item"><i class="fas fa-calendar"></i> <?= date('D, M d, Y', strtotime($event['date'])) ?></div>
          <div class="event-meta-item"><i class="fas fa-clock"></i> <?= date('h:i A', strtotime($event['time'])) ?></div>
          <div class="event-meta-item"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($event['venue_name'] ?? 'TBA') ?></div>
          <div class="event-meta-item"><i class="fas fa-users"></i> <?= $regCount ?> attending</div>
          <?php if ($event['max_guests']): ?>
            <div class="event-meta-item"><i class="fas fa-user-lock"></i> <?= $event['max_guests'] ?> max guests</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
.gradient-1{background:linear-gradient(135deg,#6c63ff,#f72585);}
.gradient-2{background:linear-gradient(135deg,#f72585,#ff9f43);}
.gradient-3{background:linear-gradient(135deg,#4cc9f0,#1dd3b0);}
</style>

<script>
// Star rating
document.querySelectorAll('.star-input .fa-star').forEach(star => {
  star.addEventListener('mouseover', function() {
    const v = this.dataset.value;
    document.querySelectorAll('.star-input .fa-star').forEach((s,i) => {
      s.style.color = i < v ? '#ff9f43' : 'var(--card-border)';
    });
  });
  star.addEventListener('click', function() {
    const v = this.dataset.value;
    document.getElementById('ratingInput').value = v;
    document.querySelectorAll('.star-input .fa-star').forEach((s,i) => {
      s.style.color = i < v ? '#ff9f43' : 'var(--card-border)';
    });
  });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

