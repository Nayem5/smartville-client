<?php
session_start();
require_once __DIR__ . '/includes/functions.php';
requireLogin();
$pageTitle = 'Profile';

global $conn;
$uid = $_SESSION['user_id'];
$msg = $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize($_POST['full_name'] ?? '');
    $phone     = sanitize($_POST['phone'] ?? '');
    $area      = sanitize($_POST['area'] ?? '');
    $new_pass  = $_POST['new_password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    if (empty($full_name)) { $err = 'Full name is required.'; }
    else {
        $conn->query("UPDATE users SET full_name='$full_name', phone='$phone', area='$area' WHERE id=$uid");
        $_SESSION['full_name'] = $full_name;

        if (!empty($new_pass)) {
            if (strlen($new_pass) < 8) { $err = 'New password must be at least 8 characters.'; }
            elseif ($new_pass !== $confirm) { $err = 'Passwords do not match.'; }
            else {
                $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
                $conn->query("UPDATE users SET password='$hashed' WHERE id=$uid");
                $msg = 'Profile and password updated successfully!';
            }
        } else {
            $msg = 'Profile updated successfully!';
        }
    }
}

$user = getCurrentUser();
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
  <div><h1>My Profile</h1><p>Update your personal information</p></div>
</div>

<?php if ($msg): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $msg ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-error"><i class="fas fa-times-circle"></i> <?= $err ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:280px 1fr;gap:2rem;align-items:start">
  <!-- Avatar Card -->
  <div class="card" style="text-align:center;padding:2.5rem">
    <div style="width:100px;height:100px;border-radius:50%;background:var(--gradient);display:flex;align-items:center;justify-content:center;font-size:2.5rem;font-weight:800;color:#fff;margin:0 auto 1.2rem">
      <?= strtoupper(substr($user['full_name'],0,2)) ?>
    </div>
    <h3 style="color:var(--white);font-size:1.1rem"><?= htmlspecialchars($user['full_name']) ?></h3>
    <span class="badge <?= $user['role']==='admin'?'badge-danger':($user['role']==='organizer'?'badge-warning':'badge-secondary') ?>" style="margin:.5rem 0;display:inline-block"><?= ucfirst($user['role']) ?></span>
    <p style="color:var(--text-muted);font-size:.85rem;margin-top:.5rem"><?= htmlspecialchars($user['email']) ?></p>
    <p style="color:var(--text-muted);font-size:.8rem;margin-top:.5rem">Member since <?= date('M Y', strtotime($user['created_at'])) ?></p>
  </div>

  <!-- Edit Form -->
  <div class="card">
    <div class="card-header"><h3>Edit Profile</h3></div>
    <div class="card-body">
      <form method="POST">
        <div class="form-grid">
          <div class="form-group full">
            <label class="form-label">Full Name *</label>
            <input type="text" name="full_name" class="form-control" required value="<?= htmlspecialchars($user['full_name']) ?>"/>
          </div>
          <div class="form-group full">
            <label class="form-label">Email (cannot change)</label>
            <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled style="opacity:.5"/>
          </div>
          <div class="form-group">
            <label class="form-label">Phone Number</label>
            <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="e.g. 0112345678"/>
          </div>
          <div class="form-group">
            <label class="form-label">Area / Neighbourhood</label>
            <input type="text" name="area" class="form-control" value="<?= htmlspecialchars($user['area'] ?? '') ?>" placeholder="e.g. Segamat Utara"/>
          </div>
          <div class="form-group" style="grid-column:1/-1;margin-top:.5rem;border-top:1px solid var(--card-border);padding-top:1.2rem">
            <h4 style="color:var(--white);margin-bottom:1rem">Change Password <span style="font-size:.8rem;font-weight:400;color:var(--text-muted)">(Leave blank to keep current)</span></h4>
          </div>
          <div class="form-group">
            <label class="form-label">New Password</label>
            <input type="password" name="new_password" class="form-control" placeholder="Min 8 characters"/>
          </div>
          <div class="form-group">
            <label class="form-label">Confirm New Password</label>
            <input type="password" name="confirm_password" class="form-control" placeholder="Repeat new password"/>
          </div>
        </div>
        <div style="margin-top:1.5rem">
          <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
