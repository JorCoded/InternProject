<?php
require_once '../includes/auth.php';
requireRole(['employee','hr','executive','admin']);
$pageTitle = 'My Dashboard';
$user      = Auth::currentUser();
$attModel  = new Attendance();
$att       = $attModel->getToday($user['id']);

// ── Handle break/clock actions ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fa = $_POST['form_action'] ?? '';

    if ($fa === 'break_start') {
        if (!$att) {
            flash('You are not clocked in.', 'error');
        } else {
            $result = $attModel->startBreak($att['id']);
            flash($result['msg'], $result['ok'] ? 'success' : 'error');
        }
    } elseif ($fa === 'break_end') {
        if (!$att) {
            flash('You are not clocked in.', 'error');
        } else {
            $result = $attModel->endBreak($att['id']);
            flash($result['msg'], $result['ok'] ? ($result['warn'] ? 'warning' : 'success') : 'error');
        }
    }

    redirect('../' . $user['role'] . '/dashboard.php');
}

// Refresh after possible POST
$att = $attModel->getToday($user['id']);

// ── Data for dashboard ──────────────────────────────────────
$taskModel     = new Task();
$myPending     = $taskModel->countForUser($user['id'], 'pending');
$completedToday = dbFetchOne("SELECT COUNT(*) as cnt FROM tasks WHERE assigned_to=? AND DATE(completed_at)=CURDATE()", [$user['id']], 'i')['cnt'] ?? 0;
$myTasks       = $taskModel->getForUser($user['id'], 'pending', 5, 0);
$attHistory    = $attModel->getHistory($user['id'], date('Y-m-01'), date('Y-m-d'), 7, 0);
$monthStats    = $attModel->getMonthStats($user['id']);
$announcements = (new Announcement())->getAll(true, 3, 0);

require_once '../includes/header.php';
?>

<div class="row g-3 mb-4">
  <!-- Clock Widget -->
  <div class="col-md-4">
    <div class="clock-widget">
      <div class="live-clock clock-time">00:00:00</div>
      <div class="live-date clock-date"></div>
      <hr>
      <?php if (!$att): ?>
        <div class="opacity-75 fs-sm">Not clocked in today</div>
        <div class="opacity-50 fs-xs mt-1">Logging in registers your clock-in</div>
      <?php elseif (!$att['clock_out']): ?>
        <?php if ($att['break_start'] && !$att['break_end']): ?>
          <span class="badge bg-warning text-dark mb-2">On Break</span>
          <div class="fs-sm opacity-80">Started: <?= date('h:i A', strtotime($att['break_start'])) ?></div>
          <form method="POST" class="mt-3">
            <input type="hidden" name="form_action" value="break_end">
            <button class="btn btn-sm btn-warning w-100"><i class="bi bi-stop-circle me-1"></i>End Break</button>
          </form>
        <?php else: ?>
          <span class="badge bg-success mb-2">Clocked In – <?= date('h:i A', strtotime($att['clock_in'])) ?></span>
          <?php if (!$att['break_start']): ?>
            <form method="POST" class="mt-3">
              <input type="hidden" name="form_action" value="break_start">
              <button class="btn btn-sm btn-outline-light w-100"><i class="bi bi-cup-hot me-1"></i>Start Break</button>
            </form>
          <?php else: ?>
            <div class="fs-xs opacity-70 mt-2">Break already taken today</div>
          <?php endif; ?>
        <?php endif; ?>
      <?php else: ?>
        <span class="badge bg-secondary mb-2">Clocked Out</span>
        <div class="fs-sm opacity-80"><?= date('h:i A', strtotime($att['clock_in'])) ?> → <?= date('h:i A', strtotime($att['clock_out'])) ?></div>
        <div class="fs-xs opacity-70 mt-1">Total: <?= Attendance::formatHours($att['total_hours']) ?></div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Leave Balance -->
  <div class="col-md-4">
    <div class="card h-100">
      <div class="card-header"><i class="bi bi-calendar-heart me-2" style="color:#4f6af0"></i>Leave Balance</div>
      <div class="card-body">
        <div class="mb-3">
          <div class="d-flex justify-content-between mb-1"><span class="fs-sm">Local Leaves</span><strong class="fs-sm"><?= $user['local_leaves'] ?> / 22</strong></div>
          <div class="progress" style="height:8px"><div class="progress-bar bg-primary" style="width:<?= ($user['local_leaves']/22)*100 ?>%"></div></div>
        </div>
        <div class="mb-3">
          <div class="d-flex justify-content-between mb-1"><span class="fs-sm">Sick Leaves</span><strong class="fs-sm"><?= $user['sick_leaves'] ?> / 15</strong></div>
          <div class="progress" style="height:8px"><div class="progress-bar bg-info" style="width:<?= ($user['sick_leaves']/15)*100 ?>%"></div></div>
        </div>
        <div class="d-flex justify-content-between"><span class="fs-sm">Unpaid Used</span><strong class="fs-sm"><?= $user['unpaid_leaves'] ?></strong></div>
        <?php if (in_array($user['role'], ['employee','hr'])): ?>
          <a href="../employee/leaves.php" class="btn btn-sm btn-outline-primary mt-3 w-100">Request Leave</a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Month Stats -->
  <div class="col-md-4">
    <div class="card h-100">
      <div class="card-header"><i class="bi bi-bar-chart me-2" style="color:#4f6af0"></i>This Month</div>
      <div class="card-body">
        <div class="row g-2 text-center">
          <div class="col-6"><div style="background:#f0fdf4;border-radius:8px;padding:12px"><div style="font-size:1.5rem;font-weight:700;color:#16a34a"><?= $monthStats['present']??0 ?></div><div class="fs-xs text-muted">Days Present</div></div></div>
          <div class="col-6"><div style="background:#fffbeb;border-radius:8px;padding:12px"><div style="font-size:1.5rem;font-weight:700;color:#d97706"><?= $monthStats['late']??0 ?></div><div class="fs-xs text-muted">Late</div></div></div>
          <div class="col-6"><div style="background:#fef2f2;border-radius:8px;padding:12px"><div style="font-size:1.5rem;font-weight:700;color:#dc2626"><?= $myPending ?></div><div class="fs-xs text-muted">Pending Tasks</div></div></div>
          <div class="col-6"><div style="background:#eff6ff;border-radius:8px;padding:12px"><div style="font-size:1.5rem;font-weight:700;color:#2563eb"><?= $completedToday ?></div><div class="fs-xs text-muted">Done Today</div></div></div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <!-- Recent Attendance -->
  <div class="col-md-6">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-calendar-check me-2" style="color:#4f6af0"></i>Recent Attendance</span>
        <a href="../employee/attendance.php" class="btn btn-sm btn-outline-primary" style="font-size:.75rem">View All</a>
      </div>
      <div class="card-body p-0">
        <?php if (empty($attHistory)): ?>
          <div class="text-center text-muted py-4 fs-sm">No records yet</div>
        <?php else: ?>
          <?php foreach ($attHistory as $a): ?>
            <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
              <div>
                <div class="fs-sm fw-500"><?= date('D, M d', strtotime($a['date'])) ?></div>
                <div class="fs-xs text-muted"><?= $a['clock_in'] ? date('h:i A', strtotime($a['clock_in'])) : '—' ?><?= $a['clock_out'] ? ' → '.date('h:i A', strtotime($a['clock_out'])) : '' ?></div>
              </div>
              <div class="text-end">
                <span class="status-badge <?= $a['status']==='present'?'bg-success text-white':($a['status']==='late'?'bg-warning text-dark':($a['status']==='on_leave'?'bg-info text-dark':'bg-danger text-white')) ?>"><?= ucfirst(str_replace('_',' ',$a['status'])) ?></span>
                <?php if ($a['total_hours']): ?><div class="fs-xs text-muted mt-1"><?= Attendance::formatHours($a['total_hours']) ?></div><?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Tasks + Announcements -->
  <div class="col-md-6">
    <div class="card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-check2-square me-2" style="color:#4f6af0"></i>Pending Tasks</span>
        <a href="../employee/tasks.php" class="btn btn-sm btn-outline-primary" style="font-size:.75rem">View All</a>
      </div>
      <div class="card-body p-0">
        <?php if (empty($myTasks)): ?>
          <div class="text-center text-muted py-3 fs-sm"><i class="bi bi-check-all d-block fs-4 mb-1"></i>All caught up!</div>
        <?php else: ?>
          <?php foreach ($myTasks as $t): ?>
            <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
              <div><div class="fs-sm fw-500"><?= sanitize($t['title']) ?></div><div class="fs-xs text-muted">Due: <?= $t['due_date'] ? date('M d', strtotime($t['due_date'])) : 'N/A' ?></div></div>
              <span class="status-badge <?= $t['priority']==='high'?'bg-danger text-white':($t['priority']==='medium'?'bg-warning text-dark':'bg-success text-white') ?>"><?= ucfirst($t['priority']) ?></span>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><i class="bi bi-megaphone me-2" style="color:#4f6af0"></i>Announcements</div>
      <div class="card-body p-0">
        <?php if (empty($announcements)): ?>
          <div class="text-center text-muted py-3 fs-sm">No announcements</div>
        <?php else: ?>
          <?php foreach ($announcements as $a): ?>
            <div class="px-3 py-2 border-bottom">
              <div class="fs-sm fw-600"><?= sanitize($a['title']) ?></div>
              <div class="fs-xs text-muted mt-1"><?= sanitize(substr($a['content'], 0, 100)) ?><?= strlen($a['content']) > 100 ? '…' : '' ?></div>
              <div class="fs-xs mt-1" style="color:#9ca3af"><?= date('M d, Y', strtotime($a['created_at'])) ?></div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>
