<?php
/**
 * index.php — Login Page
 * ======================
 * This is the entry point of the entire HRMS application.
 * All users (admin, HR, employee, executive) log in here.
 *
 * HOW THE LOGIN WORKS:
 *   1. GET  request  → show the login form
 *   2. POST request  → process the form:
 *        a. Find the user by email
 *        b. Check the password hash
 *        c. Save user info to the PHP session
 *        d. Auto clock-in the user (record attendance)
 *        e. Redirect to their role-specific dashboard
 *
 * ROLES AND THEIR DASHBOARDS:
 *   admin     → admin/dashboard.php
 *   hr        → hr/dashboard.php
 *   employee  → employee/dashboard.php
 *   executive → executive/dashboard.php
 */
require_once 'config/bootstrap.php';

// If already logged in, redirect to their dashboard immediately
if (Auth::isLoggedIn()) {
    header('Location: ' . Auth::role() . '/dashboard.php');
    exit;
}

$errorMessage = '';

// ── Handle login form submission ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';

    if ($email && $password) {
        // Try to log in — Auth::attempt() handles everything:
        // password check, session creation, and clock-in
        $result = Auth::attempt($email, $password);

        if ($result['ok']) {
            // Redirect to the correct dashboard for this role
            header('Location: ' . $result['role'] . '/dashboard.php');
            exit;
        }
        $errorMessage = $result['msg'];
    } else {
        $errorMessage = 'Please enter your email and password.';
    }
}

// ── Load company branding for the login page ──────────────────
$settings    = Setting::getAll();
$companyName = $settings['company_name'] ?? 'HRMS';
$companyLogo = $settings['company_logo'] ?? '';

// Only show logo if the file actually exists on disk
$logoPath   = __DIR__ . '/uploads/logo/' . $companyLogo;
$logoExists = $companyLogo && file_exists($logoPath);
$logoUrl    = $logoExists ? 'uploads/logo/' . rawurlencode($companyLogo) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Login – <?= htmlspecialchars($companyName) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="login-page">

<div class="login-card">

  <!-- Company branding -->
  <div class="text-center mb-4">
    <?php if ($logoExists): ?>
      <img src="<?= htmlspecialchars($logoUrl) ?>"
           alt="<?= htmlspecialchars($companyName) ?>"
           class="login-logo mb-3"
           style="max-height:80px;max-width:200px;object-fit:contain">
    <?php else: ?>
      <!-- Fallback: show the first letter of the company name in a circle -->
      <div class="login-logo-placeholder mb-3">
        <?= strtoupper(substr($companyName, 0, 1)) ?>
      </div>
    <?php endif; ?>
    <h4 class="fw-700 mb-0" style="color:#1a2456">
      <?= htmlspecialchars($companyName) ?>
    </h4>
    <p class="text-muted fs-sm mb-0">HR Management System</p>
  </div>

  <!-- Error message (shown if login failed) -->
  <?php if ($errorMessage): ?>
    <div class="alert alert-danger alert-dismissible fade show py-2 fs-sm">
      <i class="bi bi-exclamation-circle me-1"></i>
      <?= htmlspecialchars($errorMessage) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Success message (e.g. shown after logout) -->
  <?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success py-2 fs-sm">
      <i class="bi bi-check-circle me-1"></i>
      <?= htmlspecialchars($_GET['msg']) ?>
    </div>
  <?php endif; ?>

  <!-- Login form -->
  <form method="POST" autocomplete="off">

    <div class="mb-3">
      <label class="form-label fw-500 fs-sm">Email Address</label>
      <div class="input-group">
        <span class="input-group-text bg-light border-end-0">
          <i class="bi bi-envelope text-muted"></i>
        </span>
        <input type="email" name="email"
               class="form-control border-start-0"
               placeholder="you@company.com"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
               required autofocus>
      </div>
    </div>

    <div class="mb-4">
      <label class="form-label fw-500 fs-sm">Password</label>
      <div class="input-group">
        <span class="input-group-text bg-light border-end-0">
          <i class="bi bi-lock text-muted"></i>
        </span>
        <input type="password" name="password" id="pwdInput"
               class="form-control border-start-0"
               placeholder="••••••••" required>
        <!-- Eye button to toggle password visibility — calls togglePassword() from app.js -->
        <button type="button" class="btn btn-light border"
                onclick="togglePassword('pwdInput','eyeIcon')"
                tabindex="-1">
          <i class="bi bi-eye" id="eyeIcon"></i>
        </button>
      </div>
    </div>

    <button type="submit" class="btn btn-login w-100">
      <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
    </button>
  </form>

  <!-- Default credentials hint — REMOVE THIS IN PRODUCTION -->
  <div class="text-center mt-4 text-muted" style="font-size:.74rem">
    Default: <code>admin@hrms.com</code> / <code>password</code>
  </div>

</div><!-- /login-card -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>
