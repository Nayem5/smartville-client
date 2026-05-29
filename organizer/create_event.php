<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
requireLogin('organizer');
$pageTitle = 'Create Event';

global $conn;
$msg = $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $type        = sanitize($_POST['event_type'] ?? 'free');
    $sector      = sanitize($_POST['sector'] ?? '');
    $date        = sanitize($_POST['date'] ?? '');
    $time        = sanitize($_POST['time'] ?? '');
    $end_time    = sanitize($_POST['end_time'] ?? '');
    $venue_id    = (int)($_POST['venue_id'] ?? 0);
    $price       = (float)($_POST['price'] ?? 0);
    $max_guests  = (int)($_POST['max_guests'] ?? 0);
    $program     = sanitize($_POST['program_flow'] ?? '');
    $uid         = $_SESSION['user_id'];

    if (empty($title) || empty($date) || empty($time)) {
        $err = 'Title, date and time are required.';
    } else {
        $priceVal    = $type === 'paid' ? $price : 0;
        $maxVal      = $type === 'private' ? $max_guests : null;
        $venueVal    = $venue_id ?: 'NULL';
        $maxSQL      = $maxVal ? $maxVal : 'NULL';
        $endSQL      = $end_time ? "'$end_time'" : 'NULL';
        $venueSQL    = $venue_id ? $venue_id : 'NULL';

        $stmt = $conn->prepare("INSERT INTO events (organizer_id,venue_id,title,description,event_type,sector,date,time,end_time,price,max_guests,program_flow,status)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,'pending')");

        // Handle poster upload
        $poster = null;
        if (!empty($_FILES['poster']['name'])) {
            $ext  = pathinfo($_FILES['poster']['name'], PATHINFO_EXTENSION);
            $name = 'poster_' . time() . '.' . $ext;
            $dest = __DIR__ . '/../uploads/posters/' . $name;
            if (move_uploaded_file($_FILES['poster']['tmp_name'], $dest)) $poster = $name;
        }

        $venueParam  = $venue_id ?: null;
        $maxParam    = $maxVal ?: null;
        $endParam    = $end_time ?: null;

        $stmt = $conn->prepare("INSERT INTO events (organizer_id,venue_id,title,description,event_type,sector,date,time,end_time,price,max_guests,poster,program_flow,status)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,'pending')");
        $stmt->bind_param('iisssssssdiss',
            $uid,$venueParam,$title,$description,$type,$sector,$date,$time,$endParam,$priceVal,$maxParam,$poster,$program);

        if ($stmt->execute()) {
            addNotification($uid, 'Event Submitted!', "Your event \"$title\" has been submitted for admin review.", 'info');
            $msg = 'Event submitted successfully! Waiting for admin approval.';
        } else {
            $err = 'Failed to submit event. Please try again.';
        }
        $stmt->close();
    }
}

$venues = $conn->query("SELECT * FROM venues WHERE status='available' ORDER BY name")->fetch_all(MYSQLI_ASSOC);
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div><h1>Create New Event</h1><p>Fill in the details below to submit your event for approval</p></div>
  <a href="/fyp_soop/smartville/organizer/my_events.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> My Events</a>
</div>

<?php if ($msg): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $msg ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-error"><i class="fas fa-times-circle"></i> <?= $err ?></div><?php endif; ?>

<div class="card">
  <div class="card-body">
    <form method="POST" enctype="multipart/form-data">
      <!-- Step indicator -->
      <div style="display:flex;gap:.5rem;margin-bottom:2rem;background:var(--dark-3);border-radius:12px;padding:.4rem;">
        <button type="button" class="btn btn-primary btn-sm step-nav" id="tab-basic" onclick="showTab('basic')">1. Basic Info</button>
        <button type="button" class="btn btn-outline btn-sm step-nav" id="tab-type" onclick="showTab('type')">2. Event Type</button>
        <button type="button" class="btn btn-outline btn-sm step-nav" id="tab-venue" onclick="showTab('venue')">3. Venue</button>
        <button type="button" class="btn btn-outline btn-sm step-nav" id="tab-media" onclick="showTab('media')">4. Media</button>
      </div>

      <!-- Tab 1: Basic Info -->
      <div class="form-tab" id="tab-basic-content">
        <div class="form-grid">
          <div class="form-group full">
            <label class="form-label">Event Title *</label>
            <input type="text" name="title" class="form-control" placeholder="e.g. Gala of Hope 2025" required value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"/>
          </div>
          <div class="form-group">
            <label class="form-label">Sector / Category</label>
            <select name="sector" class="form-control">
              <option value="">Select category</option>
              <?php foreach(['Social','Sports','Entertainment','Education','Networking','Health','Cultural','Other'] as $s): ?>
                <option value="<?= $s ?>" <?= (($_POST['sector'] ?? '')===$s)?'selected':'' ?>><?= $s ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Event Date *</label>
            <input type="date" name="date" class="form-control" required value="<?= htmlspecialchars($_POST['date'] ?? '') ?>" min="<?= date('Y-m-d') ?>"/>
          </div>
          <div class="form-group">
            <label class="form-label">Start Time *</label>
            <input type="time" name="time" class="form-control" required value="<?= htmlspecialchars($_POST['time'] ?? '') ?>"/>
          </div>
          <div class="form-group">
            <label class="form-label">End Time</label>
            <input type="time" name="end_time" class="form-control" value="<?= htmlspecialchars($_POST['end_time'] ?? '') ?>"/>
          </div>
          <div class="form-group full">
            <label class="form-label">Event Description</label>
            <textarea name="description" class="form-control" rows="4" placeholder="Describe your event..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
          </div>
        </div>
        <div style="display:flex;justify-content:flex-end;margin-top:1rem">
          <button type="button" class="btn btn-primary" onclick="showTab('type')">Next <i class="fas fa-arrow-right"></i></button>
        </div>
      </div>

      <!-- Tab 2: Event Type -->
      <div class="form-tab" id="tab-type-content" style="display:none">
        <div class="form-group">
          <label class="form-label">Event Type *</label>
          <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-top:.5rem" id="typeCards">
            <?php foreach(['free','paid','private'] as $t): ?>
              <label class="type-card <?= (($_POST['event_type'] ?? 'free')===$t)?'active':'' ?>" data-type="<?= $t ?>">
                <input type="radio" name="event_type" value="<?= $t ?>" <?= (($_POST['event_type'] ?? 'free')===$t)?'checked':'' ?> style="display:none"/>
                <div class="type-icon">
                  <?php if($t==='free'): ?><i class="fas fa-gift"></i><?php elseif($t==='paid'): ?><i class="fas fa-ticket-alt"></i><?php else: ?><i class="fas fa-lock"></i><?php endif; ?>
                </div>
                <strong><?= ucfirst($t) ?></strong>
                <span><?= $t==='free'?'Open to all, no charge':($t==='paid'?'Requires ticket payment':'Invite-only event') ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
        <div id="paid-fields" style="display:none;margin-top:1rem">
          <div class="form-group">
            <label class="form-label">Ticket Price (RM)</label>
            <input type="number" name="price" class="form-control" min="0" step="0.01" placeholder="e.g. 45.00" value="<?= htmlspecialchars($_POST['price'] ?? '') ?>"/>
          </div>
        </div>
        <div id="private-fields" style="display:none;margin-top:1rem">
          <div class="form-group">
            <label class="form-label">Maximum Invited Guests</label>
            <input type="number" name="max_guests" class="form-control" min="1" placeholder="e.g. 50" value="<?= htmlspecialchars($_POST['max_guests'] ?? '') ?>"/>
          </div>
        </div>
        <div style="display:flex;justify-content:space-between;margin-top:1rem">
          <button type="button" class="btn btn-outline" onclick="showTab('basic')"><i class="fas fa-arrow-left"></i> Back</button>
          <button type="button" class="btn btn-primary" onclick="showTab('venue')">Next <i class="fas fa-arrow-right"></i></button>
        </div>
      </div>

      <!-- Tab 3: Venue -->
      <div class="form-tab" id="tab-venue-content" style="display:none">
        <label class="form-label">Select Venue</label>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-top:.5rem">
          <?php foreach($venues as $v): ?>
            <label class="venue-card <?= (($_POST['venue_id'] ?? '')==$v['id'])?'active':'' ?>">
              <input type="radio" name="venue_id" value="<?= $v['id'] ?>" <?= (($_POST['venue_id'] ?? '')==$v['id'])?'checked':'' ?> style="display:none"/>
              <div class="venue-img gradient-<?= (array_search($v, $venues) % 3) + 1 ?>"></div>
              <div style="padding:.8rem">
                <strong style="color:var(--white)"><?= htmlspecialchars($v['name']) ?></strong>
                <div style="font-size:.8rem;color:var(--text-muted)">Capacity: <?= $v['capacity'] ?></div>
                <div style="font-size:.85rem;color:#ff9f43;margin-top:.3rem">RM<?= number_format($v['price_per_day'],2) ?>/day</div>
              </div>
            </label>
          <?php endforeach; ?>
        </div>
        <div style="display:flex;justify-content:space-between;margin-top:1.5rem">
          <button type="button" class="btn btn-outline" onclick="showTab('type')"><i class="fas fa-arrow-left"></i> Back</button>
          <button type="button" class="btn btn-primary" onclick="showTab('media')">Next <i class="fas fa-arrow-right"></i></button>
        </div>
      </div>

      <!-- Tab 4: Media -->
      <div class="form-tab" id="tab-media-content" style="display:none">
        <div class="form-grid">
          <div class="form-group full">
            <label class="form-label">Event Poster (Optional)</label>
            <div class="upload-area" id="uploadArea" onclick="document.getElementById('posterInput').click()">
              <i class="fas fa-cloud-upload-alt"></i>
              <p>Click to upload poster image</p>
              <span>JPG, PNG, GIF â€” Max 5MB</span>
              <div id="previewWrap" style="display:none;margin-top:1rem"><img id="posterPreview" style="max-height:200px;border-radius:8px"/></div>
            </div>
            <input type="file" id="posterInput" name="poster" accept="image/*" style="display:none" onchange="previewPoster(this)"/>
          </div>
          <div class="form-group full">
            <label class="form-label">Program Flow (Optional)</label>
            <textarea name="program_flow" class="form-control" rows="5" placeholder="e.g.&#10;6:00 PM - Registration&#10;7:00 PM - Welcome Remarks&#10;8:00 PM - Dinner..."><?= htmlspecialchars($_POST['program_flow'] ?? '') ?></textarea>
          </div>
        </div>
        <div style="display:flex;justify-content:space-between;margin-top:1rem">
          <button type="button" class="btn btn-outline" onclick="showTab('venue')"><i class="fas fa-arrow-left"></i> Back</button>
          <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Submit for Approval</button>
        </div>
      </div>
    </form>
  </div>
</div>

<style>
.type-card{display:flex;flex-direction:column;align-items:center;gap:.5rem;padding:1.5rem;border:2px solid var(--card-border);border-radius:14px;cursor:pointer;transition:all .3s;text-align:center;}
.type-card:hover,.type-card.active{border-color:var(--primary);background:rgba(108,99,255,.1);}
.type-card .type-icon{font-size:2rem;color:var(--primary);margin-bottom:.3rem;}
.type-card strong{color:var(--white);font-size:.95rem;}
.type-card span{color:var(--text-muted);font-size:.8rem;}
.venue-card{border:2px solid var(--card-border);border-radius:14px;overflow:hidden;cursor:pointer;transition:all .3s;}
.venue-card:hover,.venue-card.active{border-color:var(--primary);}
.venue-img{height:100px;}
.upload-area{border:2px dashed var(--card-border);border-radius:14px;padding:3rem;text-align:center;cursor:pointer;transition:all .3s;}
.upload-area:hover{border-color:var(--primary);background:rgba(108,99,255,.05);}
.upload-area i{font-size:2.5rem;color:var(--primary);margin-bottom:1rem;}
.upload-area p{color:var(--white);font-weight:600;margin-bottom:.3rem;}
.upload-area span{color:var(--text-muted);font-size:.82rem;}
</style>

<script>
function showTab(name) {
  document.querySelectorAll('.form-tab').forEach(t => t.style.display='none');
  document.getElementById('tab-'+name+'-content').style.display='block';
  document.querySelectorAll('.step-nav').forEach(b => b.classList.remove('btn-primary'));
  document.querySelectorAll('.step-nav').forEach(b => b.classList.add('btn-outline'));
  document.getElementById('tab-'+name).classList.add('btn-primary');
  document.getElementById('tab-'+name).classList.remove('btn-outline');
}
document.querySelectorAll('.type-card').forEach(card => {
  card.addEventListener('click', () => {
    document.querySelectorAll('.type-card').forEach(c => c.classList.remove('active'));
    card.classList.add('active');
    const t = card.dataset.type;
    document.getElementById('paid-fields').style.display = t==='paid'?'block':'none';
    document.getElementById('private-fields').style.display = t==='private'?'block':'none';
  });
});
document.querySelectorAll('.venue-card').forEach(card => {
  card.addEventListener('click', () => {
    document.querySelectorAll('.venue-card').forEach(c => c.classList.remove('active'));
    card.classList.add('active');
  });
});
function previewPoster(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => {
      document.getElementById('posterPreview').src = e.target.result;
      document.getElementById('previewWrap').style.display = 'block';
    };
    reader.readAsDataURL(input.files[0]);
  }
}
// Init
const currentType = document.querySelector('input[name="event_type"]:checked')?.value;
if (currentType === 'paid') document.getElementById('paid-fields').style.display='block';
if (currentType === 'private') document.getElementById('private-fields').style.display='block';
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

