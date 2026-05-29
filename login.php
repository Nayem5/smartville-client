<?php
session_start();
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    redirect(BASE_PATH . '/' . $_SESSION['role'] . '/index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = sanitize($_POST['role'] ?? 'resident');

    if (empty($username) || empty($password)) {
        $error = 'Please enter your username and password.';
    } else {
        global $conn;
        $stmt = $conn->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND role = ? AND status = 'active'");
        $stmt->bind_param('sss', $username, $username, $role);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];
            redirect(BASE_PATH . '/' . $user['role'] . '/index.php');
        } else {
            $error = 'Invalid credentials. Please try again.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login &ndash; SmartVille</title>
  <link rel="stylesheet" href="/fyp_soop/smartville/auth.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body>
<div class="cursor-dot"></div>
<div class="cursor-outline"></div>
<div id="particles-container"></div>

<div class="auth-page">
  <div class="auth-left">
    <div class="auth-brand">
      <a href="/fyp_soop/smartville/index.html" class="auth-logo">
        <div class="logo-icon"><i class="fas fa-city"></i></div>
        <span>Smart<span class="accent">Ville</span></span>
      </a>
    </div>
    <div class="auth-visual">
      <div class="visual-circle c1"></div>
      <div class="visual-circle c2"></div>
      <div class="visual-circle c3"></div>
      <div class="visual-card vc1"><i class="fas fa-calendar-check"></i><span>350+ Events</span></div>
      <div class="visual-card vc2"><i class="fas fa-users"></i><span>1200+ Members</span></div>
      <div class="visual-card vc3"><i class="fas fa-star"></i><span>4.8 Rating</span></div>
      <div class="visual-text">
        <h2>Welcome Back!</h2>
        <p>Your community is waiting for you. Join events, connect with neighbours, and make a difference.</p>
      </div>
    </div>
  </div>

  <div class="auth-right">
    <div class="auth-form-container">
      <div class="auth-header">
        <h1>Log In</h1>
        <p>Enter your credentials to access SmartVille</p>
      </div>

      <?php if ($error): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <!-- Demo credentials hint -->
      <div class="demo-hint">
        <strong>Demo Credentials</strong>
        <div class="demo-accounts">
          <div onclick="fillDemo('admin','password','admin')">
            <i class="fas fa-shield-alt"></i> Admin
          </div>
          <div onclick="fillDemo('shahidah','password','organizer')">
            <i class="fas fa-chalkboard-teacher"></i> Organizer
          </div>
          <div onclick="fillDemo('ahmad','password','resident')">
            <i class="fas fa-user"></i> Resident
          </div>
        </div>
      </div>

      <form method="POST" class="auth-form">
        <div class="role-selector">
          <button type="button" class="role-btn <?= (!isset($_POST['role']) || $_POST['role']==='resident')?'active':'' ?>"
            onclick="setRole(this,'resident')">
            <i class="fas fa-user"></i> Resident
          </button>
          <button type="button" class="role-btn <?= (isset($_POST['role']) && $_POST['role']==='organizer')?'active':'' ?>"
            onclick="setRole(this,'organizer')">
            <i class="fas fa-chalkboard-teacher"></i> Organizer
          </button>
          <button type="button" class="role-btn <?= (isset($_POST['role']) && $_POST['role']==='admin')?'active':'' ?>"
            onclick="setRole(this,'admin')">
            <i class="fas fa-shield-alt"></i> Admin
          </button>
        </div>
        <input type="hidden" name="role" id="roleInput" value="<?= htmlspecialchars($_POST['role'] ?? 'resident') ?>"/>

        <div class="input-group">
          <div class="input-icon"><i class="fas fa-user"></i></div>
          <input type="text" name="username" id="usernameInput" placeholder="Username or Email"
            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required/>
        </div>
        <div class="input-group">
          <div class="input-icon"><i class="fas fa-lock"></i></div>
          <input type="password" name="password" id="passwordInput" placeholder="Password" required/>
          <button type="button" class="toggle-pass" onclick="togglePass(this)"><i class="fas fa-eye"></i></button>
        </div>
        <div class="form-options">
          <label class="checkbox-label"><input type="checkbox" name="remember"> <span>Remember me</span></label>
          <a href="#" class="forgot-link">Forgot password?</a>
        </div>
        <button type="submit" class="auth-submit-btn"><span>Log In</span><i class="fas fa-arrow-right"></i></button>
      </form>

      <p class="auth-switch">Don't have an account? <a href="/fyp_soop/smartville/signup.php">Sign Up</a></p>
    </div>
  </div>
</div>

<script src="/fyp_soop/smartville/auth.js"></script>
<script>
function setRole(btn, role) {
  document.querySelectorAll('.role-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('roleInput').value = role;
}
function fillDemo(user, pass, role) {
  document.getElementById('usernameInput').value = user;
  document.getElementById('passwordInput').value = pass;
  document.querySelectorAll('.role-btn').forEach(b => b.classList.remove('active'));
  document.querySelector(`[onclick*="${role}"]`).classList.add('active');
  document.getElementById('roleInput').value = role;
}
</script>
</body>
</html>

