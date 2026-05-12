<?php
/**
 * includes/header.php
 * Shared HTML header, sidebar navigation, and topbar.
 * Included at the top of every authenticated page.
 *
 * Expects $pageTitle to be set by the including page.
 */

if (!isset($pageTitle)) $pageTitle = 'HRMS';

// ── Load settings and current user ──────────────────────────
$settings    = Setting::getAll();
$companyName = $settings['company_name'] ?? 'HRMS';
$companyLogo = $settings['company_logo'] ?? '';

$user        = Auth::currentUser();
$role        = Auth::role();

// ── Notifications for the bell icon ─────────────────────────
$notif         = new Notification();
$unreadCount   = $user ? $notif->countUnread($user['id']) : 0;
$notifications = $user ? $notif->getUnread($user['id'], 15) : [];

// ── Base URL path (relative) ─────────────────────────────────
// Calculates how many ../ are needed to reach the project root
// based on how deep in the directory the current script is.
// e.g. admin/dashboard.php  → depth=1 → base='../'
//      index.php             → depth=0 → base=''
$depth = max(0, substr_count($_SERVER['PHP_SELF'], '/') - 1);
$base  = str_repeat('../', $depth);

// ── Logo: check file exists on disk, then build URL ──────────
$logoSrc = '';
if ($companyLogo) {
    // Absolute path on disk (works on Windows and Linux)
    $logoAbsPath = __DIR__ . '/../uploads/logo/' . $companyLogo;

    if (file_exists($logoAbsPath)) {
        // URL relative to the current page depth
        $logoSrc = $base . 'uploads/logo/' . rawurlencode($companyLogo);
    }
}

// ── Active nav helper ─────────────────────────────────────────
$selfPath = $_SERVER['PHP_SELF'] ?? '';
function navActive(string $segment): string {
    global $selfPath;
    return str_contains($selfPath, $segment) ? 'active' : '';
}

function getLocation()
{
    $locationData = [];

    $apiUrl = "http://ip-api.com/json/";
    $response = file_get_contents($apiUrl);

    $data = json_decode($response, true);

    if ($data && $data['status'] === 'success') {
        $city = $data['city'];
        $region = $data['regionName'];
        $country = $data['country'];
        $lat = $data['lat'];
        $lon = $data['lon'];

        $locationData[] = $city;
        $locationData[] = $region;
        $locationData[] = $country;
        $locationData[] = $lat;
        $locationData[] = $lon;

    } else {
        echo "Could not retrieve location.";
    }
    return $locationData;
}







?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= sanitize($pageTitle) ?> – <?= sanitize($companyName) ?></title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <!-- HRMS custom styles (external CSS) -->
  <link href="<?= $base ?>assets/css/style.css" rel="stylesheet">
</head>
<body>

<!-- ════════════════════════════════════════════════════════════
     SIDEBAR
     ════════════════════════════════════════════════════════════ -->
<nav class="sidebar" id="sidebar">

  <!-- Brand / logo -->
  <div class="brand">
    <?php if ($logoSrc): ?>
      <!-- Company logo uploaded via Settings -->
      <img src="<?= htmlspecialchars($logoSrc) ?>"
           alt="<?= sanitize($companyName) ?>"
           class="brand-logo">
    <?php else: ?>
      <!-- Fallback: first letter of company name -->
      <div class="brand-placeholder">
        <?= strtoupper(substr($companyName, 0, 1)) ?>
      </div>
    <?php endif; ?>
    <span class="brand-name"><?= sanitize($companyName) ?></span>
  </div>

  <!-- ── Navigation links (role-based) ── -->

  <?php if ($role === 'admin'): ?>

    <div class="nav-label">Main</div>
    <a href="<?= $base ?>admin/dashboard.php"
       class="nav-link <?= navActive('admin/dashboard') ?>">
      <i class="bi bi-speedometer2"></i> Dashboard
    </a>
    <a href="<?= $base ?>admin/employees.php"
       class="nav-link <?= navActive('admin/employees') ?>">
      <i class="bi bi-people-fill"></i> Employees
    </a>
    <a href="<?= $base ?>admin/tasks.php"
       class="nav-link <?= navActive('admin/tasks') ?>">
      <i class="bi bi-check2-square"></i> Tasks
    </a>
    <a href="<?= $base ?>admin/attendance.php"
       class="nav-link <?= navActive('admin/attendance') ?>">
      <i class="bi bi-calendar-check"></i> Attendance
    </a>
    <a href="<?= $base ?>admin/leaves.php"
       class="nav-link <?= navActive('admin/leaves') ?>">
      <i class="bi bi-calendar-minus"></i> Leave Requests
    </a>
    <a href="<?= $base ?>admin/announcements.php"
       class="nav-link <?= navActive('admin/announcements') ?>">
      <i class="bi bi-megaphone"></i> Announcements
    </a>
    <div class="nav-label">Reports</div>
    <a href="<?= $base ?>admin/reports.php"
       class="nav-link <?= navActive('admin/reports') ?>">
      <i class="bi bi-file-earmark-bar-graph"></i> Reports
    </a>
    <div class="nav-label">Settings</div>
    <a href="<?= $base ?>admin/settings.php"
       class="nav-link <?= navActive('admin/settings') ?>">
      <i class="bi bi-gear"></i> Settings
    </a>
    <form action="" method="post">
      <button type="submit" name="testLocation">Test Location</button>
    </form>

  <?php elseif ($role === 'hr'): ?>

    <div class="nav-label">Main</div>
    <a href="<?= $base ?>hr/dashboard.php"
       class="nav-link <?= navActive('hr/dashboard') ?>">
      <i class="bi bi-speedometer2"></i> Dashboard
    </a>
    <a href="<?= $base ?>hr/leaves.php"
       class="nav-link <?= navActive('hr/leaves') && !navActive('my_leaves') ? 'active' : '' ?>">
      <i class="bi bi-calendar-minus"></i> Leave Requests
    </a>
    <a href="<?= $base ?>hr/attendance.php"
       class="nav-link <?= navActive('hr/attendance') ?>">
      <i class="bi bi-calendar-check"></i> Attendance
    </a>
    <a href="<?= $base ?>hr/reports.php"
       class="nav-link <?= navActive('hr/reports') ?>">
      <i class="bi bi-file-earmark-bar-graph"></i> Reports
    </a>
    <a href="<?= $base ?>hr/my_leaves.php"
       class="nav-link <?= navActive('hr/my_leaves') ?>">
      <i class="bi bi-calendar-heart"></i> My Leaves
    </a>

  <?php elseif ($role === 'executive'): ?>

    <div class="nav-label">Main</div>
    <a href="<?= $base ?>executive/dashboard.php"
       class="nav-link <?= navActive('executive/dashboard') ?>">
      <i class="bi bi-speedometer2"></i> Dashboard
    </a>
    <a href="<?= $base ?>executive/attendance.php"
       class="nav-link <?= navActive('executive/attendance') ?>">
      <i class="bi bi-calendar-check"></i> My Attendance
    </a>

  <?php else: /* employee */ ?>

    <div class="nav-label">Main</div>
    <a href="<?= $base ?>employee/dashboard.php"
       class="nav-link <?= navActive('employee/dashboard') ?>">
      <i class="bi bi-speedometer2"></i> Dashboard
    </a>
    <a href="<?= $base ?>employee/tasks.php"
       class="nav-link <?= navActive('employee/tasks') ?>">
      <i class="bi bi-check2-square"></i> My Tasks
    </a>
    <a href="<?= $base ?>employee/attendance.php"
       class="nav-link <?= navActive('employee/attendance') ?>">
      <i class="bi bi-calendar-check"></i> Attendance
    </a>
    <a href="<?= $base ?>employee/leaves.php"
       class="nav-link <?= navActive('employee/leaves') ?>">
      <i class="bi bi-calendar-minus"></i> My Leaves
    </a>
    <a href="<?= $base ?>employee/announcements.php"
       class="nav-link <?= navActive('employee/announcements') ?>">
      <i class="bi bi-megaphone"></i> Announcements
    </a>

  <?php endif; ?>

  <div class="sidebar-spacer"></div>
</nav>

<?php if(isset($_POST['testLocation'])){
    var_dump(getLocation());
} ?>

<!-- ════════════════════════════════════════════════════════════
     TOPBAR
     ════════════════════════════════════════════════════════════ -->
<div class="topbar">

  <!-- Mobile sidebar toggle -->
  <button class="btn btn-sm btn-light d-md-none" id="sidebarToggle">
    <i class="bi bi-list fs-5"></i>
  </button>

  <!-- Page title -->
  <span class="page-title"><?= sanitize($pageTitle) ?></span>

  <!-- ── Notification bell ── -->
  <div class="dropdown">
    <button class="btn btn-sm btn-light position-relative"
            data-bs-toggle="dropdown" aria-expanded="false"
            aria-label="Notifications">
      <i class="bi bi-bell fs-5"></i>
      <?php if ($unreadCount > 0): ?>
        <span class="position-absolute top-0 start-100 translate-middle
                     badge rounded-pill bg-danger"
              style="font-size:.6rem">
          <?= $unreadCount > 99 ? '99+' : $unreadCount ?>
        </span>
      <?php endif; ?>
    </button>

    <div class="dropdown-menu dropdown-menu-end"
         style="width:320px;max-height:420px;overflow-y:auto">
      <!-- Header row -->
      <div class="d-flex align-items-center justify-content-between
                  px-3 py-2 border-bottom">
        <strong class="fs-sm">Notifications</strong>
        <?php if ($unreadCount > 0): ?>
          <a href="<?= $base ?>includes/mark_read.php"
             class="text-primary fs-xs">Mark all read</a>
        <?php endif; ?>
      </div>

      <?php if (empty($notifications)): ?>
        <div class="text-center text-muted py-4">
          <i class="bi bi-bell-slash d-block fs-3 mb-2"></i>
          <span class="fs-xs">No new notifications</span>
        </div>
      <?php else: ?>
        <?php foreach ($notifications as $n): ?>
          <a href="<?= $base ?>includes/mark_read.php?id=<?= $n['id'] ?>"
             class="dropdown-item py-2 border-bottom">
            <div class="d-flex gap-2 align-items-start">
              <span class="notif-dot mt-1 flex-shrink-0"></span>
              <div style="min-width:0">
                <div class="fs-sm fw-600"><?= sanitize($n['title']) ?></div>
                <div class="fs-xs text-muted text-truncate">
                  <?= sanitize(substr($n['message'], 0, 80)) ?>
                </div>
                <div style="font-size:.7rem;color:#9ca3af">
                  <?= timeAgo($n['created_at']) ?>
                </div>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- ── User avatar / dropdown ── -->
  <div class="dropdown">
    <button class="btn btn-sm btn-light d-flex align-items-center gap-2"
            data-bs-toggle="dropdown">
      <div class="avatar" style="width:30px;height:30px;font-size:.78rem">
        <?= $user ? strtoupper(substr($user['first_name'], 0, 1)) : 'U' ?>
      </div>
      <span class="d-none d-md-inline fs-sm">
        <?= sanitize($user['first_name'] ?? '') ?>
      </span>
      <i class="bi bi-chevron-down" style="font-size:.65rem"></i>
    </button>

    <div class="dropdown-menu dropdown-menu-end" style="min-width:200px">
      <div class="px-3 py-2 border-bottom">
        <div class="fw-600 fs-sm">
          <?= sanitize(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?>
        </div>
        <div class="fs-xs text-muted">
          <?= ucfirst($user['role'] ?? '') ?>
          &nbsp;·&nbsp;
          <?= sanitize($user['employee_id'] ?? '') ?>
        </div>
      </div>
      <a href="<?= $base ?>profile.php" class="dropdown-item fs-sm">
        <i class="bi bi-person me-2"></i>My Profile
      </a>
      <div class="dropdown-divider"></div>
      <a href="<?= $base ?>logout.php" class="dropdown-item text-danger fs-sm">
        <i class="bi bi-box-arrow-right me-2"></i>Logout
      </a>
    </div>
  </div>

</div><!-- /topbar -->

<!-- ════════════════════════════════════════════════════════════
     MAIN CONTENT WRAPPER
     ════════════════════════════════════════════════════════════ -->
<div class="main-content">
  <div class="main-inner">
    <?php showFlash(); ?>
