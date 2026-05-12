<?php
/**
 * hr/leaves.php
 * =============
 * HR version of the leave management page.
 * HR can approve or reject pending leave requests.
 *
 * When approved:
 *   - The leave status changes to 'approved'
 *   - The employee's leave balance is reduced
 *   - Attendance is marked as 'on_leave' for each day
 *   - The employee gets a notification
 *
 * When rejected:
 *   - The status changes to 'rejected'
 *   - No balance changes
 *   - The employee gets a notification
 */
require_once '../includes/auth.php';
requireRole(['hr', 'admin']); // only HR and admin can access this page
$pageTitle = 'Leave Requests';

// ── Handle approve / reject actions (sent via URL like ?action=approve&id=5) ──
$action  = $_GET['action'] ?? '';
$leaveId = intval($_GET['id'] ?? 0);

if ($action && $leaveId) {
    $leaveObj = new Leave();
    $leave    = $leaveObj->getById($leaveId);

    if ($leave && $leave['status'] === 'pending') {

        if ($action === 'approve') {
            $leaveObj->approve($leaveId, Auth::userId());
            $label = 'approved';
        } else {
            $leaveObj->reject($leaveId, Auth::userId());
            $label = 'rejected';
        }

        // Notify the employee of the decision
        (new Notification())->create(
            $leave['user_id'],
            'leave',
            'Leave ' . ucfirst($label),
            ucfirst($leave['leave_type']) . " leave request ("
                . date('M d', strtotime($leave['start_date'])) . " – "
                . date('M d', strtotime($leave['end_date'])) . ") has been {$label}.",
            $leaveId
        );

        flash("Leave request {$label} successfully.");
    }

    redirect('leaves.php');
}

// ── Load and filter leave requests ───────────────────────────
$filterStatus = sanitize($_GET['status'] ?? '');
$page         = max(1, intval($_GET['page'] ?? 1));
$perPage      = 20;
$offset       = ($page - 1) * $perPage;

$leaveObj   = new Leave();
$total      = $leaveObj->countAll($filterStatus);
$leaves     = $leaveObj->getAll($filterStatus, $perPage, $offset);
$totalPages = (int)ceil($total / $perPage);

require_once '../includes/header.php';
?>

<!-- ── Status filter bar ── -->
<div class="d-flex gap-2 mb-3 align-items-center flex-wrap">
  <select class="form-select form-select-sm" style="width:160px" id="statusFilter">
    <option value="">All Status</option>
    <option value="pending"  <?= $filterStatus === 'pending'  ? 'selected' : '' ?>>Pending</option>
    <option value="approved" <?= $filterStatus === 'approved' ? 'selected' : '' ?>>Approved</option>
    <option value="rejected" <?= $filterStatus === 'rejected' ? 'selected' : '' ?>>Rejected</option>
  </select>
  <button class="btn btn-sm btn-outline-primary"
          onclick="applyFilter({ status: document.getElementById('statusFilter').value })">
    Filter
  </button>
  <span class="ms-auto text-muted fs-xs"><?= $total ?> requests</span>
</div>

<!-- ── Leave requests table ── -->
<div class="card">
  <div class="card-header">
    <i class="bi bi-calendar-minus me-2" style="color:#4f6af0"></i>Leave Requests
  </div>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th>Employee</th>
          <th>Type</th>
          <th>Period</th>
          <th>Days</th>
          <th>Reason</th>
          <th>Balance</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($leaves)): ?>
          <tr><td colspan="8" class="text-center text-muted py-4">No leave requests found</td></tr>
        <?php else: ?>
          <?php foreach ($leaves as $l): ?>
            <tr>
              <!-- Employee name + ID -->
              <td>
                <div class="fs-sm fw-600"><?= sanitize($l['first_name'] . ' ' . $l['last_name']) ?></div>
                <div class="fs-xs text-muted"><?= sanitize($l['employee_id']) ?></div>
              </td>

              <!-- Leave type badge -->
              <td>
                <span class="badge <?=
                    $l['leave_type'] === 'sick'  ? 'bg-info text-dark' :
                    ($l['leave_type'] === 'local' ? 'bg-primary' : 'bg-secondary')
                ?>">
                  <?= ucfirst($l['leave_type']) ?>
                </span>
              </td>

              <!-- Date range -->
              <td class="fs-sm">
                <?= date('M d', strtotime($l['start_date'])) ?>
                – <?= date('M d, Y', strtotime($l['end_date'])) ?>
              </td>

              <!-- Number of days -->
              <td class="fs-sm fw-600"><?= $l['total_days'] ?></td>

              <!-- Reason (truncated to 60 chars) -->
              <td class="fs-xs" style="max-width:160px">
                <?= sanitize(substr($l['reason'], 0, 60)) ?>
                <?= strlen($l['reason']) > 60 ? '…' : '' ?>
              </td>

              <!-- Employee's current leave balance -->
              <td class="fs-xs text-muted">
                <?= $l['local_leaves'] ?> local<br>
                <?= $l['sick_leaves'] ?> sick
              </td>

              <!-- Status badge -->
              <td>
                <span class="status-badge <?=
                    $l['status'] === 'approved' ? 'bg-success text-white' :
                    ($l['status'] === 'rejected' ? 'bg-danger text-white' : 'bg-warning text-dark')
                ?>">
                  <?= ucfirst($l['status']) ?>
                </span>
              </td>

              <!-- Approve / Reject buttons — only shown for pending requests -->
              <td>
                <?php if ($l['status'] === 'pending'): ?>
                  <a href="?action=approve&id=<?= $l['id'] ?>&status=<?= urlencode($filterStatus) ?>"
                     class="btn btn-sm btn-success py-0 px-2"
                     onclick="return confirm('Approve this leave request?')">
                    <i class="bi bi-check"></i>
                  </a>
                  <a href="?action=reject&id=<?= $l['id'] ?>&status=<?= urlencode($filterStatus) ?>"
                     class="btn btn-sm btn-danger py-0 px-2 ms-1"
                     onclick="return confirm('Reject this leave request?')">
                    <i class="bi bi-x"></i>
                  </a>
                <?php else: ?>
                  <span class="text-muted fs-xs">—</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ($totalPages > 1): ?>
    <div class="card-body d-flex justify-content-center pt-2">
      <nav><ul class="pagination pagination-sm mb-0">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
          <li class="page-item <?= $i === $page ? 'active' : '' ?>">
            <a class="page-link"
               href="?page=<?= $i ?>&status=<?= urlencode($filterStatus) ?>">
              <?= $i ?>
            </a>
          </li>
        <?php endfor; ?>
      </ul></nav>
    </div>
  <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
