<?php
/**
 * employee/attendance.php
 * Shows the current user's attendance history filtered by month.
 * Displays summary stats (present, late, absent, total hours)
 * and a paginated table of daily records.
 */
require_once '../includes/auth.php';
requireRole(['employee','hr','executive','admin']);
$pageTitle = 'My Attendance';

$uid      = Auth::userId();
$attModel = new Attendance();

// ── Month filter (defaults to current month) ─────────────────
$monthFilter = sanitize($_GET['month'] ?? date('Y-m'));
$dateFrom    = $monthFilter . '-01';
$dateTo      = date('Y-m-t', strtotime($dateFrom)); // last day of selected month

// ── Pagination ───────────────────────────────────────────────
$page       = max(1, intval($_GET['page'] ?? 1));
$perPage    = 20;
$offset     = ($page - 1) * $perPage;

$total      = $attModel->countHistory($uid, $dateFrom, $dateTo);
$records    = $attModel->getHistory($uid, $dateFrom, $dateTo, $perPage, $offset);
$totalPages = (int)ceil($total / $perPage);

// ── Summary stats for the selected month ─────────────────────
$stats = Database::getInstance()->fetchOne(
    "SELECT
        COUNT(*) as total_days,
        SUM(CASE WHEN status='present'  THEN 1 ELSE 0 END) as present,
        SUM(CASE WHEN status='late'     THEN 1 ELSE 0 END) as late,
        SUM(CASE WHEN status='absent'   THEN 1 ELSE 0 END) as absent,
        SUM(CASE WHEN status='on_leave' THEN 1 ELSE 0 END) as on_leave,
        SUM(total_hours) as total_hours
     FROM attendance
     WHERE user_id=? AND date BETWEEN ? AND ?",
    [$uid, $dateFrom, $dateTo], 'iss'
);

require_once '../includes/header.php';
?>

<!-- ── Month picker ── -->
<div class="d-flex gap-2 mb-3 align-items-center">
  <input type="month" class="form-control form-control-sm" style="width:170px"
         value="<?= $monthFilter ?>" id="monthPicker">
  <button class="btn btn-sm btn-outline-primary"
          onclick="window.location.href='?month='+document.getElementById('monthPicker').value">
    <i class="bi bi-arrow-right me-1"></i>Go
  </button>
  <span class="text-muted fs-xs ms-auto"><?= $total ?> record(s)</span>
</div>

<!-- ── Summary stat cards ── -->
<div class="row g-3 mb-3">
  <div class="col-6 col-md-3">
    <div class="card text-center py-3">
      <div style="font-size:1.6rem;font-weight:700;color:#16a34a"><?= $stats['present'] ?? 0 ?></div>
      <div class="fs-xs text-muted">Present</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card text-center py-3">
      <div style="font-size:1.6rem;font-weight:700;color:#d97706"><?= $stats['late'] ?? 0 ?></div>
      <div class="fs-xs text-muted">Late</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card text-center py-3">
      <div style="font-size:1.6rem;font-weight:700;color:#dc2626"><?= $stats['absent'] ?? 0 ?></div>
      <div class="fs-xs text-muted">Absent</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card text-center py-3">
      <div style="font-size:1.6rem;font-weight:700;color:#2563eb">
        <?= Attendance::formatHours((float)($stats['total_hours'] ?? 0)) ?>
      </div>
      <div class="fs-xs text-muted">Total Hours</div>
    </div>
  </div>
</div>

<!-- ── Attendance history table ── -->
<div class="card">
  <div class="card-header">
    <i class="bi bi-calendar-check me-2" style="color:#4f6af0"></i>
    Attendance – <?= date('F Y', strtotime($dateFrom)) ?>
  </div>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th>Date</th><th>Clock In</th><th>Clock Out</th>
          <th>Break</th><th>Total Hours</th><th>Status</th><th>Clock-In Location</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($records)): ?>
          <tr><td colspan="7" class="text-center text-muted py-4">No records for this month</td></tr>
        <?php else: ?>
          <?php foreach ($records as $a): ?>
            <tr>
              <td class="fw-500 fs-sm"><?= date('D, M d', strtotime($a['date'])) ?></td>
              <td class="fs-sm">
                <?= $a['clock_in'] ? date('h:i A', strtotime($a['clock_in'])) : '—' ?>
              </td>
              <td class="fs-sm">
                <?= $a['clock_out']
                  ? date('h:i A', strtotime($a['clock_out']))
                  : '<span class="text-success">Active</span>' ?>
              </td>
              <!-- Break period — shows start–end or just start if ongoing -->
              <td class="fs-xs">
                <?php if ($a['break_start']): ?>
                  <?= date('h:i', strtotime($a['break_start'])) ?>
                  –
                  <?= $a['break_end'] ? date('h:i', strtotime($a['break_end'])) : '…' ?>
                <?php else: ?>—<?php endif; ?>
              </td>
              <td class="fs-sm">
                <?= $a['total_hours'] ? Attendance::formatHours($a['total_hours']) : '—' ?>
              </td>
              <td>
                <span class="status-badge
                  <?= $a['status'] === 'present'  ? 'bg-success text-white'
                    : ($a['status'] === 'late'     ? 'bg-warning text-dark'
                    : ($a['status'] === 'on_leave' ? 'bg-info text-dark'
                    : 'bg-danger text-white')) ?>">
                  <?= ucfirst(str_replace('_', ' ', $a['status'])) ?>
                </span>
              </td>
              <!-- Geo-location captured at clock-in -->
              <td class="fs-xs text-muted" style="max-width:160px;white-space:normal">
                <?= sanitize($a['clock_in_location'] ?? '—') ?>
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
            <a class="page-link" href="?page=<?= $i ?>&month=<?= $monthFilter ?>"><?= $i ?></a>
          </li>
        <?php endfor; ?>
      </ul></nav>
    </div>
  <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
