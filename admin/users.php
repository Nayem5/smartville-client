<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
requireLogin('admin');
$pageTitle = 'Manage Users';

global $conn;
$msg = '';

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id     = (int)$_GET['id'];
    $action = $_GET['action'];
    if ($action === 'deactivate') {
        $conn->query("UPDATE users SET status='deactivated' WHERE id=$id AND role != 'admin'");
        $msg = 'User deactivated.';
    } elseif ($action === 'activate') {
        $conn->query("UPDATE users SET status='active' WHERE id=$id");
        $msg = 'User activated.';
    } elseif ($action === 'delete') {
        $conn->query("DELETE FROM users WHERE id=$id AND role != 'admin'");
        $msg = 'User deleted.';
    } elseif ($action === 'promote') {
        $conn->query("UPDATE users SET role='organizer' WHERE id=$id AND role='resident'");
        $msg = 'User promoted to Organizer.';
    } elseif ($action === 'demote') {
        $conn->query("UPDATE users SET role='resident' WHERE id=$id AND role='organizer'");
        $msg = 'User demoted to Resident.';
    }
}

$search = sanitize($_GET['search'] ?? '');
$roleFilter = sanitize($_GET['role'] ?? 'all');
$where = ["role != 'admin'"];
if ($search) $where[] = "(full_name LIKE '%$search%' OR email LIKE '%$search%' OR username LIKE '%$search%')";
if ($roleFilter !== 'all') $where[] = "role = '$roleFilter'";
$whereSQL = 'WHERE ' . implode(' AND ', $where);

$users = $conn->query("SELECT *, (SELECT COUNT(*) FROM registrations r WHERE r.user_id=users.id) as event_count FROM users $whereSQL ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div><h1>Manage Users</h1><p>View and manage all registered community members</p></div>
</div>

<?php if ($msg): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $msg ?></div><?php endif; ?>

<!-- Filters -->
<div class="card" style="margin-bottom:1.5rem">
  <div class="card-body" style="display:flex;gap:1rem;align-items:center;flex-wrap:wrap">
    <div style="display:flex;gap:.4rem">
      <?php foreach(['all','resident','organizer'] as $r): ?>
        <a href="?role=<?= $r ?>&search=<?= urlencode($search) ?>"
          class="btn btn-sm <?= $roleFilter===$r?'btn-primary':'btn-outline' ?>">
          <?= ucfirst($r) ?>
        </a>
      <?php endforeach; ?>
    </div>
    <form method="GET" style="display:flex;gap:.5rem;margin-left:auto">
      <input type="hidden" name="role" value="<?= $roleFilter ?>">
      <input type="text" name="search" class="form-control" style="width:220px"
        placeholder="Search users..." value="<?= htmlspecialchars($search) ?>">
      <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body" style="padding:0">
    <div class="table-wrap">
      <table>
        <thead><tr><th>#</th><th>User</th><th>Role</th><th>Area</th><th>Events Joined</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead>
        <tbody>
          <?php if (empty($users)): ?>
            <tr><td colspan="8" style="text-align:center;color:var(--text-muted);padding:3rem">No users found.</td></tr>
          <?php else: ?>
            <?php foreach ($users as $i => $u): ?>
              <tr>
                <td style="color:var(--text-muted)"><?= $i+1 ?></td>
                <td>
                  <div style="display:flex;align-items:center;gap:.8rem">
                    <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#6c63ff,#f72585);display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:700;color:#fff;flex-shrink:0">
                      <?= strtoupper(substr($u['full_name'],0,2)) ?>
                    </div>
                    <div>
                      <div style="font-weight:600;color:var(--white)"><?= htmlspecialchars($u['full_name']) ?></div>
                      <div style="font-size:.75rem;color:var(--text-muted)"><?= htmlspecialchars($u['email']) ?></div>
                    </div>
                  </div>
                </td>
                <td>
                  <?php if ($u['role']==='organizer'): ?>
                    <span class="badge badge-warning">Organizer</span>
                  <?php else: ?>
                    <span class="badge badge-secondary">Resident</span>
                  <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($u['area'] ?: '—') ?></td>
                <td style="text-align:center"><?= $u['event_count'] ?></td>
                <td>
                  <?php if ($u['status']==='active'): ?>
                    <span class="badge badge-success">Active</span>
                  <?php elseif ($u['status']==='deactivated'): ?>
                    <span class="badge badge-danger">Deactivated</span>
                  <?php else: ?>
                    <span class="badge badge-secondary"><?= ucfirst($u['status']) ?></span>
                  <?php endif; ?>
                </td>
                <td><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                <td>
                  <div style="display:flex;gap:.3rem;flex-wrap:wrap">
                    <?php if ($u['role']==='resident'): ?>
                      <a href="?action=promote&id=<?= $u['id'] ?>&role=<?= $roleFilter ?>" class="btn btn-sm btn-warning" onclick="return confirm('Promote to Organizer?')"><i class="fas fa-arrow-up"></i></a>
                    <?php elseif ($u['role']==='organizer'): ?>
                      <a href="?action=demote&id=<?= $u['id'] ?>&role=<?= $roleFilter ?>" class="btn btn-sm btn-outline" onclick="return confirm('Demote to Resident?')"><i class="fas fa-arrow-down"></i></a>
                    <?php endif; ?>
                    <?php if ($u['status']==='active'): ?>
                      <a href="?action=deactivate&id=<?= $u['id'] ?>&role=<?= $roleFilter ?>" class="btn btn-sm btn-danger" onclick="return confirm('Deactivate this user?')"><i class="fas fa-ban"></i></a>
                    <?php else: ?>
                      <a href="?action=activate&id=<?= $u['id'] ?>&role=<?= $roleFilter ?>" class="btn btn-sm btn-success"><i class="fas fa-check"></i></a>
                    <?php endif; ?>
                    <a href="?action=delete&id=<?= $u['id'] ?>&role=<?= $roleFilter ?>" class="btn btn-sm btn-outline" onclick="return confirm('Permanently delete this user and all their data?')"><i class="fas fa-trash"></i></a>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
