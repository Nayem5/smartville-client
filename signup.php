<?php
session_start();
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) redirect(BASE_PATH . '/' . $_SESSION['role'] . '/index.php');

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize($_POST['full_name'] ?? '');
    $email     = sanitize($_POST['email'] ?? '');
    $username  = sanitize($_POST['username'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';
    $phone     = sanitize($_POST['phone'] ?? '');
    $area      = sanitize($_POST['area'] ?? '');

    if (empty($full_name) || empty($email) || empty($username) || empty($password)) {
        $error = 'All required fields must be filled.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        global $conn;
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $checkStmt->bind_param('ss', $email, $username);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            $error = 'Email or username already exists.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $insStmt = $conn->prepare("INSERT INTO users (full_name, username, email, password, phone, area, role) VALUES (?,?,?,?,?,?,'resident')");
            $insStmt->bind_param('ssssss', $full_name, $username, $email, $hashed, $phone, $area);

            if ($insStmt->execute()) {
                $userId = $conn->insert_id;
                addNotification($userId, 'Welcome to SmartVille!', "Hi $full_name, your account has been created. Start browsing events!", 'success');
                $success = 'Account created! Redirecting to login...';
                header("Refresh: 2; url=" . BASE_PATH . "/login.php");
            } else {
                $error = 'Registration failed. Please try again.';
            }
            $insStmt->close();
        }
        $checkStmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Sign Up â€” SmartVille</title>
  <link rel="stylesheet" href="/fyp_soop/smartville/auth.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body>
<div class="cursor-dot"></div><div class="cursor-outline"></div>
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
      <div class="visual-circle c1"></div><div class="visual-circle c2"></div><div class="visual-circle c3"></div>
      <div class="visual-card vc1"><i class="fas fa-bolt"></i><span>Instant Access</span></div>
      <div class="visual-card vc2"><i class="fas fa-shield-alt"></i><span>Secure & Private</span></div>
      <div class="visual-card vc3"><i class="fas fa-gift"></i><span>Free to Join</span></div>
      <div class="visual-text">
        <h2>Join SmartVille!</h2>
        <p>Create your account and become part of Segamat's most vibrant community platform.</p>
      </div>
    </div>
  </div>

  <div class="auth-right">
    <div class="auth-form-container">
      <div class="auth-header">
        <h1>Create Account</h1>
        <p>Join the SmartVille community â€” it's free!</p>
      </div>

      <?php if ($error): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
      <?php endif; ?>

      <div class="steps-indicator">
        <div class="step active" id="step-1-ind"><span>1</span></div>
        <div class="step-line" id="line-1"></div>
        <div class="step" id="step-2-ind"><span>2</span></div>
        <div class="step-line" id="line-2"></div>
        <div class="step" id="step-3-ind"><span>3</span></div>
      </div>

      <form method="POST" id="signupForm" class="auth-form">
        <!-- Step 1 -->
        <div class="form-step active" id="step-1">
          <h3 class="step-title">Basic Info</h3>
          <div class="input-group">
            <div class="input-icon"><i class="fas fa-user"></i></div>
            <input type="text" name="full_name" placeholder="Full Name" required value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"/>
          </div>
          <div class="input-group">
            <div class="input-icon"><i class="fas fa-envelope"></i></div>
            <input type="email" name="email" placeholder="Email Address" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"/>
          </div>
          <p class="input-hint"><i class="fas fa-info-circle"></i> Your email won't be disclosed publicly</p>
          <button type="button" class="auth-submit-btn" onclick="nextStep(2)"><span>Continue</span><i class="fas fa-arrow-right"></i></button>
        </div>

        <!-- Step 2 -->
        <div class="form-step" id="step-2">
          <h3 class="step-title">Account Details</h3>
          <div class="input-group">
            <div class="input-icon"><i class="fas fa-at"></i></div>
            <input type="text" name="username" placeholder="Username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"/>
          </div>
          <div class="input-group">
            <div class="input-icon"><i class="fas fa-lock"></i></div>
            <input type="password" name="password" id="pass1" placeholder="Password (min 8 chars)" required/>
            <button type="button" class="toggle-pass" onclick="togglePass(this)"><i class="fas fa-eye"></i></button>
          </div>
          <div class="password-strength">
            <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
            <span id="strengthText">At least 8 characters required</span>
          </div>
          <div class="input-group">
            <div class="input-icon"><i class="fas fa-lock"></i></div>
            <input type="password" name="confirm_password" placeholder="Confirm Password" required/>
          </div>
          <div class="step-btns">
            <button type="button" class="btn-back" onclick="prevStep(1)"><i class="fas fa-arrow-left"></i> Back</button>
            <button type="button" class="auth-submit-btn" onclick="nextStep(3)"><span>Continue</span><i class="fas fa-arrow-right"></i></button>
          </div>
        </div>

        <!-- Step 3 -->
        <div class="form-step" id="step-3">
          <h3 class="step-title">Almost Done!</h3>
          <div class="input-group">
            <div class="input-icon"><i class="fas fa-phone"></i></div>
            <input type="tel" name="phone" placeholder="Phone Number (optional)" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"/>
          </div>
          <div class="input-group">
            <div class="input-icon"><i class="fas fa-map-marker-alt"></i></div>
            <input type="text" name="area" placeholder="Area / Neighbourhood" value="<?= htmlspecialchars($_POST['area'] ?? '') ?>"/>
          </div>
          <label class="checkbox-label terms">
            <input type="checkbox" required>
            <span>I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></span>
          </label>
          <div class="step-btns">
            <button type="button" class="btn-back" onclick="prevStep(2)"><i class="fas fa-arrow-left"></i> Back</button>
            <button type="submit" class="auth-submit-btn"><span>Create Account</span><i class="fas fa-check"></i></button>
          </div>
        </div>
      </form>

      <p class="auth-switch">Already have an account? <a href="/fyp_soop/smartville/login.php">Log In</a></p>
    </div>
  </div>
</div>

<script src="/fyp_soop/smartville/auth.js"></script>
</body>
</html>

