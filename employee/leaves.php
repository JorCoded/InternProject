<?php
/**
 * employee/leaves.php
 * Employees submit leave requests and view their leave history.
 * Validates balance before submission.
 * Notifies HR / Admin on new request.
 */
require_once '../includes/auth.php';
requireRole(['employee','hr','executive','admin']);
$pageTitle = 'My Leaves';

$uid      = Auth::userId();
$user     = Auth::currentUser();
$leaveObj = new Leave();
$notif    = new Notification();

// ── Handle leave request submission ─────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type   = $_POST['leave_type'] ?? '';
    $start  = $_POST['start_date'] ?? '';
    $end    = $_POST['end_date']   ?? '';
    $reason = trim($_POST['reason'] ?? '');

    if ($type && $start && $end && $reason) {
        $result = $leaveObj->request($uid, $type, $start, $end, $reason);

        if ($result['ok']) {
            flash('Leave request submitted. Awaiting approval.');
            // Notify admins/HR about the new request
            $notif->notifyAdmins(
                'leave_request',
                'New Leave Request',
                "{$user['first_name']} {$user['last_name']} requested {$result['days']} day(s) of "
                    . ucfirst($type) . " leave.",
                $result['id']
            );
        } else {
            flash($result['msg'], 'error');
        }
    } else {
        flash('Please fill in all required fields.', 'error');
    }

    redirect('leaves.php');
}

// ── Leave history ─────────────────────────────────────────────
$page       = max(1, intval($_GET['page'] ?? 1));
$perPage    = 10;
$offset     = ($page - 1) * $perPage;
$total      = $leaveObj->countForUser($uid);
$leaves     = $leaveObj->getForUser($uid, $perPage, $offset);
$totalPages = (int)ceil($total / $perPage);

// Refresh user to show latest leave balances
$user = Auth::currentUser();

require_once '../includes/header.php';
?>

<!-- ── Leave balance cards ── -->
<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="card text-center py-3" style="border-left:4px solid #4f6af0">
      <div style="font-size:2rem;font-weight:700;color:#4f6af0"><?= $user['local_leaves'] ?></div>
      <div class="fs-sm text-muted">Local Leaves Remaining</div>
      <div class="fs-xs text-muted">of 22 annual</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card text-center py-3" style="border-left:4px solid #06b6d4">
      <div style="font-size:2rem;font-weight:700;color:#06b6d4"><?= $user['sick_leaves'] ?></div>
      <div class="fs-sm text-muted">Sick Leaves Remaining</div>
      <div class="fs-xs text-muted">of 15 annual</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card text-center py-3" style="border-left:4px solid #f59e0b">
      <div style="font-size:2rem;font-weight:700;color:#f59e0b"><?= $user['unpaid_leaves'] ?></div>
      <div class="fs-sm text-muted">Unpaid Leaves Used</div>
      <div class="fs-xs text-muted">this year</div>
    </div>
  </div>
</div>

<div class="row g-3">

  <!-- ── Request form ── -->
  <div class="col-md-4">
    <div class="card">
      <div class="card-header">
        <i class="bi bi-plus-circle me-2" style="color:#4f6af0"></i>Request Leave
      </div>
      <div class="card-body">
        <form method="POST" id="leaveForm">

          <!-- Leave type — options disabled when balance is 0 -->
          <div class="mb-3">
            <label class="form-label form-label-sm">Leave Type *</label>
            <select name="leave_type" class="form-select form-select-sm" required
                    onchange="updateDayCount()">
              <option value="">— Select type —</option>
              <option value="local" <?= $user['local_leaves'] <= 0 ? 'disabled' : '' ?>>
                Local Leave (<?= $user['local_leaves'] ?> remaining)
              </option>
              <option value="sick" <?= $user['sick_leaves'] <= 0 ? 'disabled' : '' ?>>
                Sick Leave (<?= $user['sick_leaves'] ?> remaining)
              </option>
              <!-- Unpaid only available when all paid leaves exhausted -->
              <option value="unpaid"
                      <?= ($user['local_leaves'] > 0 || $user['sick_leaves'] > 0) ? 'disabled' : '' ?>>
                Unpaid Leave
              </option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label form-label-sm">Start Date *</label>
            <input type="date" name="start_date" id="startDate"
                   class="form-control form-control-sm"
                   min="<?= date('Y-m-d') ?>" required
                   onchange="updateDayCount()">
          </div>

          <div class="mb-3">
            <label class="form-label form-label-sm">End Date *</label>
            <input type="date" name="end_date" id="endDate"
                   class="form-control form-control-sm"
                   min="<?= date('Y-m-d') ?>" required
                   onchange="updateDayCount()">
          </div>

          <!-- Live day counter -->
          <div class="mb-3 p-2 text-center fs-sm"
               style="background:#f8f9ff;border-radius:8px;border:1px solid #e5e7eb">
            Duration: <strong id="dayCount">—</strong>
          </div>

          <div class="mb-3">
            <label class="form-label form-label-sm">Reason *</label>
            <textarea name="reason" class="form-control form-control-sm" rows="3"
                      required placeholder="Reason for leave…"></textarea>
          </div>

          <button type="submit" class="btn btn-primary btn-sm w-100">
            <i class="bi bi-send me-1"></i>Submit Request
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- ── Leave history table ── -->
  <div class="col-md-8">
    <div class="card">
      <div class="card-header">
        <i class="bi bi-clock-history me-2" style="color:#4f6af0"></i>Leave History
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th>Type</th><th>Period</th><th>Days</th>
              <th>Reason</th><th>Status</th><th>Reviewed</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($leaves)): ?>
              <tr><td colspan="6" class="text-center text-muted py-4">No leave requests yet</td></tr>
            <?php else: ?>
              <?php foreach ($leaves as $l): ?>
                <tr>
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
                    <span class="status-badge
                      <?= $l['status'] === 'approved' ? 'bg-success text-white'
                        : ($l['status'] === 'rejected' ? 'bg-danger text-white'
                        : 'bg-warning text-dark') ?>">
                      <?= ucfirst($l['status']) ?>
                    </span>
                  </td>
                  <td class="fs-xs text-muted">
                    <?= $l['reviewed_at'] ? date('M d, Y', strtotime($l['reviewed_at'])) : '—' ?>
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
                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
              </li>
            <?php endfor; ?>
          </ul></nav>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
/**
 * Calculate and display the number of leave days
 * whenever the user changes start/end date.
 */
function updateDayCount() {
  const s = document.getElementById('startDate').value;
  const e = document.getElementById('endDate').value;
  const el = document.getElementById('dayCount');
  if (s && e) {
    const days = Math.round((new Date(e) - new Date(s)) / 86400000) + 1;
    el.textContent = days > 0 ? days + ' day(s)' : 'Invalid range';
    el.style.color = days > 0 ? '#2c3e7a' : '#dc2626';
    // Keep end date ≥ start date
    document.querySelector('[name=end_date]').min = s;
  } else {
    el.textContent = '—';
  }
}
</script>

<?php require_once '../includes/footer.php'; ?>
