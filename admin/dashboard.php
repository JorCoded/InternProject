<?php
/**
 * admin/dashboard.php
 * Main admin overview — shows key stats, 7-day attendance chart,
 * today's clock-ins, pending leaves, and recent tasks.
 */
require_once '../includes/auth.php';
requireRole('admin');
$pageTitle = 'Admin Dashboard';

// ── Instantiate models ──────────────────────────────────────
$userObj   = new User();
$attModel  = new Attendance();
$leaveObj  = new Leave();
$taskObj   = new Task();

// ── Summary counters for stat cards ────────────────────────
$totalEmployees = $userObj->countAll('', 'employee');
$presentToday   = $attModel->getPresentCountToday();
$pendingLeaves  = $leaveObj->getPendingCount();
$pendingTasks   = $taskObj->getPendingCount();

// ── Today's clock-in list (latest 10) ──────────────────────
$todayAtt    = $attModel->getTodayList(10);

// ── 5 most-recent pending tasks ────────────────────────────
$recentTasks = $taskObj->getRecent(5);

// ── 5 pending leave requests shown on dashboard ────────────
$recentLeaves = $leaveObj->getAll('pending', 5, 0);

// ── 7-day attendance data for bar chart ────────────────────
$chartData = $attModel->getLast7DaysStats();

require_once '../includes/header.php';
?>

<!-- ── Stat cards ── -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="stat-card" style="background:linear-gradient(135deg,#2c3e7a,#4f6af0)">
      <div class="stat-label">Total Employees</div>
      <div class="stat-value"><?= $totalEmployees ?></div>
      <i class="bi bi-people-fill stat-icon"></i>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card" style="background:linear-gradient(135deg,#16a34a,#22c55e)">
      <div class="stat-label">Present Today</div>
      <div class="stat-value"><?= $presentToday ?></div>
      <i class="bi bi-person-check-fill stat-icon"></i>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card" style="background:linear-gradient(135deg,#d97706,#f59e0b)">
      <div class="stat-label">Pending Leaves</div>
      <div class="stat-value"><?= $pendingLeaves ?></div>
      <i class="bi bi-calendar-minus stat-icon"></i>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card" style="background:linear-gradient(135deg,#dc2626,#ef4444)">
      <div class="stat-label">Pending Tasks</div>
      <div class="stat-value"><?= $pendingTasks ?></div>
      <i class="bi bi-list-task stat-icon"></i>
    </div>
  </div>
</div>

<!-- ── Chart + Today's list ── -->
<div class="row g-3 mb-4">

  <!-- 7-day attendance bar chart -->
  <div class="col-md-8">
    <div class="card h-100">
      <div class="card-header">
        <i class="bi bi-bar-chart-fill me-2" style="color:#4f6af0"></i>7-Day Attendance Overview
      </div>
      <div class="card-body">
        <canvas id="attChart" height="110"></canvas>
      </div>
    </div>
  </div>

  <!-- Today's clock-ins -->
  <div class="col-md-4">
    <div class="card h-100">
      <div class="card-header">
        <i class="bi bi-clock-history me-2" style="color:#4f6af0"></i>Today's Clock-Ins
      </div>
      <div class="card-body p-0" style="max-height:280px;overflow-y:auto">
        <?php if (empty($todayAtt)): ?>
          <div class="text-center text-muted py-4 fs-sm">No check-ins yet today</div>
        <?php else: ?>
          <?php foreach ($todayAtt as $a): ?>
            <div class="d-flex align-items-center gap-2 px-3 py-2 border-bottom">
              <!-- Avatar initials -->
              <div class="avatar" style="width:30px;height:30px;font-size:.75rem">
                <?= strtoupper(substr($a['first_name'], 0, 1)) ?>
              </div>
              <div style="flex:1;min-width:0">
                <div class="fs-sm fw-600"><?= sanitize($a['first_name'] . ' ' . $a['last_name']) ?></div>
                <div class="fs-xs text-muted">
                  <?= date('h:i A', strtotime($a['clock_in'])) ?>
                  <?= $a['clock_out']
                        ? '→ ' . date('h:i A', strtotime($a['clock_out']))
                        : '<span class="text-success">Active</span>' ?>
                </div>
              </div>
              <!-- Late / Present badge -->
              <span class="status-badge <?= $a['status'] === 'late' ? 'bg-warning text-dark' : 'bg-success text-white' ?>">
                <?= ucfirst($a['status']) ?>
              </span>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- ── Pending leaves + Recent tasks ── -->
<div class="row g-3">

  <!-- Pending leave requests -->
  <div class="col-md-6">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-calendar-minus me-2" style="color:#4f6af0"></i>Pending Leaves</span>
        <a href="leaves.php" class="btn btn-sm btn-outline-primary" style="font-size:.75rem">View All</a>
      </div>
      <div class="card-body p-0">
        <?php if (empty($recentLeaves)): ?>
          <div class="text-center text-muted py-4 fs-sm">No pending requests</div>
        <?php else: ?>
          <?php foreach ($recentLeaves as $l): ?>
            <div class="d-flex align-items-center gap-3 px-3 py-2 border-bottom">
              <div class="avatar" style="width:30px;height:30px;font-size:.75rem">
                <?= strtoupper(substr($l['first_name'], 0, 1)) ?>
              </div>
              <div style="flex:1">
                <div class="fs-sm fw-600"><?= sanitize($l['first_name'] . ' ' . $l['last_name']) ?></div>
                <div class="fs-xs text-muted">
                  <?= ucfirst($l['leave_type']) ?> · <?= $l['total_days'] ?> day(s)
                  · <?= date('M d', strtotime($l['start_date'])) ?>
                </div>
              </div>
              <!-- Quick approve / reject buttons -->
              <a href="leaves.php?action=approve&id=<?= $l['id'] ?>"
                 class="btn btn-sm btn-success py-0 px-2"
                 onclick="return confirm('Approve this leave?')">
                <i class="bi bi-check"></i>
              </a>
              <a href="leaves.php?action=reject&id=<?= $l['id'] ?>"
                 class="btn btn-sm btn-danger py-0 px-2 ms-1"
                 onclick="return confirm('Reject this leave?')">
                <i class="bi bi-x"></i>
              </a>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Recent tasks -->
  <div class="col-md-6">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-check2-square me-2" style="color:#4f6af0"></i>Recent Tasks</span>
        <a href="tasks.php" class="btn btn-sm btn-outline-primary" style="font-size:.75rem">View All</a>
      </div>
      <div class="card-body p-0">
        <?php if (empty($recentTasks)): ?>
          <div class="text-center text-muted py-4 fs-sm">No tasks yet</div>
        <?php else: ?>
          <?php foreach ($recentTasks as $t): ?>
            <div class="d-flex align-items-center gap-3 px-3 py-2 border-bottom">
              <div style="flex:1">
                <div class="fs-sm fw-600"><?= sanitize($t['title']) ?></div>
                <div class="fs-xs text-muted">
                  → <?= sanitize($t['first_name'] . ' ' . $t['last_name']) ?>
                  · Due <?= $t['due_date'] ? date('M d', strtotime($t['due_date'])) : 'N/A' ?>
                </div>
              </div>
              <span class="status-badge
                <?= $t['status'] === 'completed' ? 'bg-success text-white'
                    : ($t['status'] === 'delayed' ? 'bg-danger text-white'
                    : 'bg-warning text-dark') ?>">
                <?= ucfirst($t['status']) ?>
              </span>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- ── Chart initialisation ── -->
<script>
document.addEventListener('DOMContentLoaded', function () {
  const ctx = document.getElementById('attChart').getContext('2d');
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: <?= json_encode($chartData['days']) ?>,
      datasets: [
        {
          label: 'Present',
          data:  <?= json_encode($chartData['present']) ?>,
          backgroundColor: 'rgba(79,106,240,.8)',
          borderRadius: 6
        },
        {
          label: 'Absent',
          data:  <?= json_encode($chartData['absent']) ?>,
          backgroundColor: 'rgba(239,68,68,.45)',
          borderRadius: 6
        }
      ]
    },
    options: {
      responsive: true,
      plugins: { legend: { position: 'top' } },
      scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
  });
});
</script>

<?php require_once '../includes/footer.php'; ?>
