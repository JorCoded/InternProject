<?php
/**
 * admin/attendance.php
 * ====================
 * Admin view of all attendance records with the ability to
 * correct clock-in/out times when mistakes occur.
 *
 * ALL modifications are permanently logged in the
 * attendance_modification_log table (PHR compliance requirement).
 * This means every change records: who changed it, the original
 * values, the new values, and the reason.
 *
 * HR can VIEW records but only admins can MODIFY them.
 */
require_once '../includes/auth.php';
requireRole('admin');
$pageTitle = 'Attendance Management';

$attModel = new Attendance();

// ── Handle modification form submission (POST) ────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'modify') {

    $attId  = intval($_POST['att_id']);
    $newIn  = $_POST['new_clock_in']  ?? '';
    $newOut = $_POST['new_clock_out'] ?? '';
    $reason = trim($_POST['reason']   ?? '');

    if (!$reason) {
        flash('A reason is required for attendance modification.', 'error');
    } else {
        $ok = $attModel->modify($attId, $newIn, $newOut, Auth::userId(), $reason);
        flash(
            $ok ? 'Attendance modified and logged.' : 'Could not find attendance record.',
            $ok ? 'success' : 'error'
        );
    }

    redirect('attendance.php');
}

// ── Load records with filters ─────────────────────────────────
$search   = sanitize($_GET['search']    ?? '');
$dateFrom = sanitize($_GET['date_from'] ?? date('Y-m-01')); // default: first day of this month
$dateTo   = sanitize($_GET['date_to']   ?? date('Y-m-d'));  // default: today
$page     = max(1, intval($_GET['page'] ?? 1));
$perPage  = 20;
$offset   = ($page - 1) * $perPage;

$total      = $attModel->countAllRecords($dateFrom, $dateTo, $search);
$records    = $attModel->getAllRecords($dateFrom, $dateTo, $search, $perPage, $offset);
$totalPages = (int)ceil($total / $perPage);

require_once '../includes/header.php';
?>

<!-- ── Filter bar ── -->
<div class="d-flex gap-2 flex-wrap mb-3 align-items-center">
  <input type="text" class="form-control form-control-sm" style="width:190px"
         placeholder="Search employee…" value="<?= $search ?>" id="searchInput">
  <input type="date" class="form-control form-control-sm" style="width:145px"
         value="<?= $dateFrom ?>" id="dateFrom">
  <input type="date" class="form-control form-control-sm" style="width:145px"
         value="<?= $dateTo ?>" id="dateTo">
  <button class="btn btn-sm btn-outline-primary"
          onclick="applyFilter({
            search:    document.getElementById('searchInput').value,
            date_from: document.getElementById('dateFrom').value,
            date_to:   document.getElementById('dateTo').value
          })">
    <i class="bi bi-search me-1"></i>Filter
  </button>
  <span class="ms-auto text-muted fs-xs"><?= $total ?> records</span>
</div>

<!-- ── Records table ── -->
<div class="card">
  <div class="card-header">
    <i class="bi bi-calendar-check me-2" style="color:#4f6af0"></i>Attendance Records
  </div>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th>Employee</th><th>Date</th><th>Clock In</th><th>Clock In Location</th>
          <th>Clock Out</th><th>Clock Out Location</th>
          <th>Break</th><th>Hours</th><th>Status</th><th>Modified</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($records)): ?>
          <tr><td colspan="11" class="text-center text-muted py-4">No records found</td></tr>
        <?php else: ?>
          <?php foreach ($records as $a): ?>
            <tr>
              <td>
                <div class="fs-sm fw-600"><?= sanitize($a['first_name'] . ' ' . $a['last_name']) ?></div>
                <div class="fs-xs text-muted"><?= sanitize($a['employee_id']) ?></div>
              </td>
              <td class="fs-sm"><?= date('M d, Y', strtotime($a['date'])) ?></td>
              <td class="fs-sm"><?= $a['clock_in']  ? date('h:i A', strtotime($a['clock_in']))  : '—' ?></td>
              <!-- Clock-in location -->
              <td class="fs-xs text-muted" style="max-width:160px;white-space:normal">
                <?= $a['clock_in_location'] ? sanitize($a['clock_in_location']) : '—' ?>
              </td>
              <td class="fs-sm">
                <?= $a['clock_out']
                  ? date('h:i A', strtotime($a['clock_out']))
                  : '<span class="text-success fs-xs">Active</span>' ?>
              </td>
              <!-- Clock-out location -->
              <td class="fs-xs text-muted" style="max-width:160px;white-space:normal">
                <?= $a['clock_out_location'] ? sanitize($a['clock_out_location']) : '—' ?>
              </td>
              <!-- Break period: shows start–end times or just "—" if no break -->
              <td class="fs-xs">
                <?php if ($a['break_start']): ?>
                  <?= date('h:i', strtotime($a['break_start'])) ?>
                  – <?= $a['break_end'] ? date('h:i', strtotime($a['break_end'])) : '…' ?>
                <?php else: ?>—<?php endif; ?>
              </td>
              <td class="fs-sm">
                <?= $a['total_hours'] ? Attendance::formatHours($a['total_hours']) : '—' ?>
              </td>
              <td>
                <span class="status-badge <?=
                    $a['status'] === 'present'  ? 'bg-success text-white' :
                    ($a['status'] === 'late'     ? 'bg-warning text-dark'  :
                    ($a['status'] === 'on_leave' ? 'bg-info text-dark'     : 'bg-danger text-white'))
                ?>">
                  <?= ucfirst(str_replace('_', ' ', $a['status'])) ?>
                </span>
              </td>
              <!-- Show a pencil icon if this record has been modified before -->
              <td>
                <?php if ($a['modification_reason']): ?>
                  <i class="bi bi-pencil-square text-warning"
                     title="<?= sanitize($a['modification_reason']) ?>"
                     data-bs-toggle="tooltip"></i>
                <?php endif; ?>
              </td>
              <!-- Modify button — opens the modal pre-filled with this record -->
              <td>
                <button class="btn btn-sm btn-outline-warning py-0 px-2"
                        onclick="openModify(<?= htmlspecialchars(json_encode($a)) ?>)"
                        data-bs-toggle="modal" data-bs-target="#modifyModal">
                  <i class="bi bi-pencil"></i>
                </button>
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
               href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&date_from=<?= $dateFrom ?>&date_to=<?= $dateTo ?>">
              <?= $i ?>
            </a>
          </li>
        <?php endfor; ?>
      </ul></nav>
    </div>
  <?php endif; ?>
</div>

<!-- ── Modify Attendance Modal ── -->
<div class="modal fade" id="modifyModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Modify Attendance</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <input type="hidden" name="form_action" value="modify">
        <input type="hidden" name="att_id"      id="mAttId">
        <div class="modal-body">

          <!-- Compliance notice — all changes are logged permanently -->
          <div class="alert alert-info py-2 fs-xs">
            <i class="bi bi-shield-check me-1"></i>
            All modifications are logged with original and new times (PHR compliance).
          </div>

          <!-- Shows original values so the admin can compare -->
          <div id="origInfo" class="mb-3 p-2 fs-xs text-muted"
               style="background:#f8f9ff;border-radius:8px;border:1px solid #e5e7eb"></div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label form-label-sm">New Clock In</label>
              <!-- datetime-local format: YYYY-MM-DDTHH:MM -->
              <input type="datetime-local" name="new_clock_in" id="mClockIn"
                     class="form-control form-control-sm">
            </div>
            <div class="col-6">
              <label class="form-label form-label-sm">New Clock Out</label>
              <input type="datetime-local" name="new_clock_out" id="mClockOut"
                     class="form-control form-control-sm">
            </div>
          </div>

          <div>
            <label class="form-label form-label-sm">Reason for Modification *</label>
            <textarea name="reason" class="form-control form-control-sm" rows="3" required
                      placeholder="Explain why this record is being modified…"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning btn-sm">
            <i class="bi bi-save me-1"></i>Save Modification
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
/**
 * Pre-fill the modification modal with the selected attendance record.
 * Shows the original times so the admin can compare before saving.
 *
 * @param {Object} a  Attendance record (JSON-encoded from PHP)
 */
function openModify(a) {
  document.getElementById('mAttId').value = a.id;

  // datetime-local inputs need format "YYYY-MM-DDTHH:MM" (the T separates date and time)
  // We get "YYYY-MM-DD HH:MM:SS" from MySQL, so we replace the space with T and trim seconds
  function toDatetimeLocal(dt) {
    return dt ? dt.substring(0, 16).replace(' ', 'T') : '';
  }

  document.getElementById('mClockIn').value  = toDatetimeLocal(a.clock_in);
  document.getElementById('mClockOut').value = toDatetimeLocal(a.clock_out);

  // Show the original values (before any previous modifications) for reference
  var origIn  = a.original_clock_in  || a.clock_in  || '—';
  var origOut = a.original_clock_out || a.clock_out || '—';

  document.getElementById('origInfo').innerHTML =
    '<strong>Employee:</strong> ' + a.first_name + ' ' + a.last_name + ' &nbsp;·&nbsp; ' +
    '<strong>Date:</strong> ' + a.date + '<br>' +
    '<strong>Original In:</strong> ' + origIn + ' &nbsp;·&nbsp; ' +
    '<strong>Original Out:</strong> ' + origOut;
}

// Initialise Bootstrap tooltips for the "modified" pencil icons in the table
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el) {
    new bootstrap.Tooltip(el);
  });
});
</script>

<?php require_once '../includes/footer.php'; ?>
