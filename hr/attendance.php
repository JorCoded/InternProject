<?php
/**
 * hr/attendance.php
 * HR view of all employee attendance records.
 * Read-only — modifications are admin-only (PHR).
 */
require_once '../includes/auth.php';
requireRole('hr');
$pageTitle = 'Attendance Overview';

$attModel = new Attendance();

// ── Date range filter (default: current month) ───────────────
$dateFrom = sanitize($_GET['date_from'] ?? date('Y-m-01'));
$dateTo   = sanitize($_GET['date_to']   ?? date('Y-m-d'));

// ── Pagination ───────────────────────────────────────────────
$page       = max(1, intval($_GET['page'] ?? 1));
$perPage    = 20;
$offset     = ($page - 1) * $perPage;

$total      = $attModel->countAllRecords($dateFrom, $dateTo);
$records    = $attModel->getAllRecords($dateFrom, $dateTo, '', $perPage, $offset);
$totalPages = (int)ceil($total / $perPage);

require_once '../includes/header.php';
?>

<!-- ── Filter bar ── -->
<div class="d-flex gap-2 mb-3 align-items-center flex-wrap">
  <input type="date" class="form-control form-control-sm" style="width:145px"
         value="<?= $dateFrom ?>" id="df">
  <input type="date" class="form-control form-control-sm" style="width:145px"
         value="<?= $dateTo ?>" id="dt">
  <button class="btn btn-sm btn-outline-primary"
          onclick="applyFilter({date_from:document.getElementById('df').value, date_to:document.getElementById('dt').value})">
    <i class="bi bi-search me-1"></i>Filter
  </button>
  <span class="ms-auto text-muted fs-xs"><?= $total ?> records</span>
</div>

<!-- ── Records table ── -->
<div class="card">
  <div class="card-header">
    <i class="bi bi-calendar-check me-2" style="color:#4f6af0"></i>
    Attendance Records – <?= date('M d', strtotime($dateFrom)) ?> to <?= date('M d, Y', strtotime($dateTo)) ?>
  </div>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th>Employee</th><th>Date</th><th>Clock In</th>
          <th>Clock Out</th><th>Hours</th><th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($records)): ?>
          <tr><td colspan="6" class="text-center text-muted py-4">No records found</td></tr>
        <?php else: ?>
          <?php foreach ($records as $a): ?>
            <tr>
              <td>
                <div class="fs-sm fw-600"><?= sanitize($a['first_name'] . ' ' . $a['last_name']) ?></div>
                <div class="fs-xs text-muted"><?= sanitize($a['employee_id']) ?></div>
              </td>
              <td class="fs-sm"><?= date('M d, Y', strtotime($a['date'])) ?></td>
              <td class="fs-sm"><?= $a['clock_in']  ? date('h:i A', strtotime($a['clock_in']))  : '—' ?></td>
              <td class="fs-sm"><?= $a['clock_out'] ? date('h:i A', strtotime($a['clock_out'])) : '<span class="text-success">Active</span>' ?></td>
              <td class="fs-sm"><?= $a['total_hours'] ? Attendance::formatHours($a['total_hours']) : '—' ?></td>
              <td>
                <span class="status-badge
                  <?= $a['status'] === 'present'  ? 'bg-success text-white'
                    : ($a['status'] === 'late'     ? 'bg-warning text-dark'
                    : ($a['status'] === 'on_leave' ? 'bg-info text-dark'
                    : 'bg-danger text-white')) ?>">
                  <?= ucfirst(str_replace('_', ' ', $a['status'])) ?>
                </span>
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
               href="?page=<?= $i ?>&date_from=<?= $dateFrom ?>&date_to=<?= $dateTo ?>">
              <?= $i ?>
            </a>
          </li>
        <?php endfor; ?>
      </ul></nav>
    </div>
  <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
