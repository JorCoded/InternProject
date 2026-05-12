<?php
/**
 * hr/dashboard.php
 * HR overview — pending leaves, today's attendance summary,
 * and quick approve/reject actions.
 */
require_once '../includes/auth.php';
requireRole('hr');
$pageTitle = 'HR Dashboard';

$userObj  = new User();
$attModel = new Attendance();
$leaveObj = new Leave();

// ── Dashboard counters ────────────────────────────────────────
$totalEmployees = $userObj->countAll('', 'employee');
$presentToday   = $attModel->getPresentCountToday();
$pendingLeaves  = $leaveObj->getPendingCount();
$absentToday    = max(0, $totalEmployees - $presentToday);

// ── Pending leave requests (up to 10 on dashboard) ───────────
$pendingList = $leaveObj->getAll('pending', 10, 0);

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
    <div class="stat-card" style="background:linear-gradient(135deg,#7c3aed,#a78bfa)">
      <div class="stat-label">Absent Today</div>
      <div class="stat-value"><?= $absentToday ?></div>
      <i class="bi bi-person-x-fill stat-icon"></i>
    </div>
  </div>
</div>

<!-- ── Pending leave table ── -->
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span>
      <i class="bi bi-calendar-minus me-2" style="color:#4f6af0"></i>Pending Leave Requests
    </span>
    <a href="leaves.php" class="btn btn-sm btn-outline-primary" style="font-size:.75rem">
      View All
    </a>
  </div>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th>Employee</th><th>Type</th><th>Period</th>
          <th>Days</th><th>Reason</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($pendingList)): ?>
          <tr>
            <td colspan="6" class="text-center text-muted py-4">No pending requests</td>
          </tr>
        <?php else: ?>
          <?php foreach ($pendingList as $l): ?>
            <tr>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <div class="avatar" style="width:28px;height:28px;font-size:.72rem">
                    <?= strtoupper(substr($l['first_name'], 0, 1)) ?>
                  </div>
                  <div>
                    <div class="fs-sm fw-600"><?= sanitize($l['first_name'] . ' ' . $l['last_name']) ?></div>
                    <div class="fs-xs text-muted"><?= sanitize($l['employee_id']) ?></div>
                  </div>
                </div>
              </td>
              <td>
                <span class="badge
                  <?= $l['leave_type'] === 'sick'   ? 'bg-info text-dark'
                    : ($l['leave_type'] === 'local' ? 'bg-primary'
                    : 'bg-secondary') ?>">
                  <?= ucfirst($l['leave_type']) ?>
                </span>
              </td>
              <td class="fs-sm">
                <?= date('M d', strtotime($l['start_date'])) ?>
                – <?= date('M d, Y', strtotime($l['end_date'])) ?>
              </td>
              <td class="fs-sm fw-600"><?= $l['total_days'] ?></td>
              <td class="fs-xs" style="max-width:160px">
                <?= sanitize(substr($l['reason'], 0, 70)) ?><?= strlen($l['reason']) > 70 ? '…' : '' ?>
              </td>
              <td>
                <!-- HR can approve/reject directly from dashboard -->
                <a href="leaves.php?action=approve&id=<?= $l['id'] ?>"
                   class="btn btn-sm btn-success py-0 px-2"
                   onclick="return confirm('Approve this leave request?')">
                  <i class="bi bi-check"></i>
                </a>
                <a href="leaves.php?action=reject&id=<?= $l['id'] ?>"
                   class="btn btn-sm btn-danger py-0 px-2 ms-1"
                   onclick="return confirm('Reject this leave request?')">
                  <i class="bi bi-x"></i>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>
