<?php
/**
 * hr/reports.php
 * ==============
 * HR version of the reports page (identical in functionality to admin/reports.php).
 * Generates attendance and task reports with date and employee filters.
 * Supports CSV download and PDF printing.
 */
require_once '../includes/auth.php';
requireRole(['hr', 'admin']);
$pageTitle = 'Reports';

$db = Database::getInstance();

// ── Read filter values from the URL ──────────────────────────
$reportType = sanitize($_GET['type']      ?? 'attendance'); // 'attendance' or 'tasks'
$dateFrom   = sanitize($_GET['date_from'] ?? date('Y-m-01')); // first day of current month
$dateTo     = sanitize($_GET['date_to']   ?? date('Y-m-d'));  // today
$empFilter  = intval($_GET['emp_id']      ?? 0);              // 0 = all employees
$export     = sanitize($_GET['export']    ?? '');             // 'csv' or 'pdf'

// Pagination
$page    = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

// Employee list for the filter dropdown
$employees = $db->fetch("SELECT id, first_name, last_name, employee_id FROM users WHERE is_active=1 ORDER BY first_name");

// ── Build the query depending on report type ─────────────────
if ($reportType === 'attendance') {

    $where  = "WHERE a.date BETWEEN ? AND ?";
    $params = [$dateFrom, $dateTo];
    $types  = 'ss';

    if ($empFilter) {
        $where   .= " AND a.user_id=?";
        $params[] = $empFilter;
        $types   .= 'i';
    }

    $totalCount = (int)($db->fetchOne(
        "SELECT COUNT(*) as cnt FROM attendance a JOIN users u ON a.user_id=u.id $where",
        $params, $types
    )['cnt'] ?? 0);

    $baseQuery = "SELECT a.*, u.first_name, u.last_name, u.employee_id, u.department
                  FROM attendance a JOIN users u ON a.user_id=u.id
                  $where ORDER BY a.date DESC, u.first_name";

    // For the page display (paginated)
    $data = $db->fetch("$baseQuery LIMIT $perPage OFFSET $offset", $params, $types);

    // For export (all rows, no pagination)
    $allData = $export ? $db->fetch($baseQuery, $params, $types) : [];

} else {
    // Task report
    $where  = "WHERE t.created_at BETWEEN ? AND ?";
    $params = [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'];
    $types  = 'ss';

    if ($empFilter) {
        $where   .= " AND t.assigned_to=?";
        $params[] = $empFilter;
        $types   .= 'i';
    }

    $totalCount = (int)($db->fetchOne(
        "SELECT COUNT(*) as cnt FROM tasks t JOIN users u ON t.assigned_to=u.id $where",
        $params, $types
    )['cnt'] ?? 0);

    $baseQuery = "SELECT t.*, u.first_name, u.last_name, u.employee_id
                  FROM tasks t JOIN users u ON t.assigned_to=u.id
                  $where ORDER BY t.created_at DESC";

    $data    = $db->fetch("$baseQuery LIMIT $perPage OFFSET $offset", $params, $types);
    $allData = $export ? $db->fetch($baseQuery, $params, $types) : [];
}

$totalPages = (int)ceil($totalCount / $perPage);

// ── CSV Export ────────────────────────────────────────────────
// When export=csv, send the data as a downloadable .csv file
if ($export === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $reportType . '_report_' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w'); // write directly to the browser

    if ($reportType === 'attendance') {
        fputcsv($out, ['Employee ID', 'Name', 'Date', 'Clock In', 'Clock Out', 'Total Hours', 'Status']);
        foreach ($allData as $r) {
            fputcsv($out, [
                $r['employee_id'],
                $r['first_name'] . ' ' . $r['last_name'],
                $r['date'],
                $r['clock_in']    ?? '',
                $r['clock_out']   ?? '',
                $r['total_hours'] ?? 0,
                $r['status'],
            ]);
        }
    } else {
        fputcsv($out, ['Employee ID', 'Name', 'Task Title', 'Due Date', 'Priority', 'Status', 'Completed At']);
        foreach ($allData as $r) {
            fputcsv($out, [
                $r['employee_id'],
                $r['first_name'] . ' ' . $r['last_name'],
                $r['title'],
                $r['due_date']     ?? '',
                $r['priority'],
                $r['status'],
                $r['completed_at'] ?? '',
            ]);
        }
    }
    fclose($out);
    exit; // stop here — we've sent the file
}

// ── PDF Export ────────────────────────────────────────────────
// When export=pdf, output a simple HTML page that auto-prints
if ($export === 'pdf') {
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Report</title>';
    echo '<style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        h2   { color: #2c3e7a; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; }
        th { background: #2c3e7a; color: #fff; }
        tr:nth-child(even) { background: #f5f5f5; }
    </style></head><body>';

    echo '<h2>' . ucfirst($reportType) . ' Report</h2>';
    echo '<p>Period: ' . date('M d, Y', strtotime($dateFrom)) . ' – ' . date('M d, Y', strtotime($dateTo)) . '</p>';
    echo '<table>';

    if ($reportType === 'attendance') {
        echo '<tr><th>Emp ID</th><th>Name</th><th>Date</th><th>In</th><th>Out</th><th>Hours</th><th>Status</th></tr>';
        foreach ($allData as $r) {
            echo '<tr>'
               . '<td>' . htmlspecialchars($r['employee_id']) . '</td>'
               . '<td>' . htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) . '</td>'
               . '<td>' . $r['date'] . '</td>'
               . '<td>' . ($r['clock_in']  ? date('h:i A', strtotime($r['clock_in']))  : '—') . '</td>'
               . '<td>' . ($r['clock_out'] ? date('h:i A', strtotime($r['clock_out'])) : '—') . '</td>'
               . '<td>' . ($r['total_hours'] ? Attendance::formatHours($r['total_hours']) : '—') . '</td>'
               . '<td>' . ucfirst($r['status']) . '</td>'
               . '</tr>';
        }
    } else {
        echo '<tr><th>Emp ID</th><th>Name</th><th>Task</th><th>Due Date</th><th>Priority</th><th>Status</th></tr>';
        foreach ($allData as $r) {
            echo '<tr>'
               . '<td>' . htmlspecialchars($r['employee_id']) . '</td>'
               . '<td>' . htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) . '</td>'
               . '<td>' . htmlspecialchars($r['title']) . '</td>'
               . '<td>' . ($r['due_date'] ? date('M d, Y', strtotime($r['due_date'])) : '—') . '</td>'
               . '<td>' . ucfirst($r['priority']) . '</td>'
               . '<td>' . ucfirst($r['status']) . '</td>'
               . '</tr>';
        }
    }

    echo '</table>';
    echo '<script>window.print();</script>'; // auto-trigger print dialog
    echo '</body></html>';
    exit;
}

require_once '../includes/header.php';
?>

<!-- ── Filter form ── -->
<div class="card mb-3">
  <div class="card-body">
    <form method="GET" class="row g-2 align-items-end">

      <!-- Report type selector -->
      <div class="col-auto">
        <label class="form-label form-label-sm">Report Type</label>
        <select name="type" class="form-select form-select-sm">
          <option value="attendance" <?= $reportType === 'attendance' ? 'selected' : '' ?>>Attendance</option>
          <option value="tasks"      <?= $reportType === 'tasks'      ? 'selected' : '' ?>>Tasks</option>
        </select>
      </div>

      <div class="col-auto">
        <label class="form-label form-label-sm">From</label>
        <input type="date" name="date_from" class="form-control form-control-sm" value="<?= $dateFrom ?>">
      </div>

      <div class="col-auto">
        <label class="form-label form-label-sm">To</label>
        <input type="date" name="date_to" class="form-control form-control-sm" value="<?= $dateTo ?>">
      </div>

      <div class="col-auto">
        <label class="form-label form-label-sm">Employee</label>
        <select name="emp_id" class="form-select form-select-sm" style="min-width:160px">
          <option value="">All Employees</option>
          <?php foreach ($employees as $e): ?>
            <option value="<?= $e['id'] ?>" <?= $empFilter === $e['id'] ? 'selected' : '' ?>>
              <?= sanitize($e['first_name'] . ' ' . $e['last_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-auto">
        <button type="submit" class="btn btn-primary btn-sm">Generate</button>
      </div>

      <!-- Export buttons — include all current filters in the URL -->
      <div class="col-auto d-flex gap-1">
        <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>"
           class="btn btn-sm btn-success">
          <i class="bi bi-filetype-csv me-1"></i>CSV
        </a>
        <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'pdf'])) ?>"
           target="_blank" class="btn btn-sm btn-danger">
          <i class="bi bi-file-earmark-pdf me-1"></i>PDF
        </a>
      </div>
    </form>
  </div>
</div>

<!-- ── Results table ── -->
<div class="card">
  <div class="card-header">
    <i class="bi bi-file-earmark-bar-graph me-2" style="color:#4f6af0"></i>
    <?= ucfirst($reportType) ?> Report
    <span class="text-muted fs-xs ms-1">(<?= $totalCount ?> records)</span>
  </div>
  <div class="table-responsive">
    <table class="table table-hover mb-0">

      <?php if ($reportType === 'attendance'): ?>
        <!-- Attendance columns -->
        <thead>
          <tr>
            <th>Employee</th><th>Date</th><th>Clock In</th>
            <th>Clock Out</th><th>Hours</th><th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($data as $r): ?>
            <tr>
              <td>
                <div class="fs-sm fw-600"><?= sanitize($r['first_name'] . ' ' . $r['last_name']) ?></div>
                <div class="fs-xs text-muted"><?= sanitize($r['employee_id']) ?></div>
              </td>
              <td class="fs-sm"><?= date('M d, Y', strtotime($r['date'])) ?></td>
              <td class="fs-sm"><?= $r['clock_in']  ? date('h:i A', strtotime($r['clock_in']))  : '—' ?></td>
              <td class="fs-sm"><?= $r['clock_out'] ? date('h:i A', strtotime($r['clock_out'])) : '—' ?></td>
              <td class="fs-sm"><?= $r['total_hours'] ? Attendance::formatHours($r['total_hours']) : '—' ?></td>
              <td>
                <span class="status-badge <?=
                    $r['status'] === 'present'  ? 'bg-success text-white' :
                    ($r['status'] === 'late'     ? 'bg-warning text-dark'  :
                    ($r['status'] === 'on_leave' ? 'bg-info text-dark'     : 'bg-danger text-white'))
                ?>">
                  <?= ucfirst(str_replace('_', ' ', $r['status'])) ?>
                </span>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($data)): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">No records found</td></tr>
          <?php endif; ?>
        </tbody>

      <?php else: ?>
        <!-- Task columns -->
        <thead>
          <tr>
            <th>Employee</th><th>Task</th><th>Due Date</th>
            <th>Priority</th><th>Status</th><th>Completed</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($data as $r): ?>
            <tr>
              <td>
                <div class="fs-sm fw-600"><?= sanitize($r['first_name'] . ' ' . $r['last_name']) ?></div>
                <div class="fs-xs text-muted"><?= sanitize($r['employee_id']) ?></div>
              </td>
              <td class="fs-sm fw-600"><?= sanitize($r['title']) ?></td>
              <td class="fs-sm"><?= $r['due_date'] ? date('M d, Y', strtotime($r['due_date'])) : '—' ?></td>
              <td>
                <span class="status-badge <?=
                    $r['priority'] === 'high'   ? 'bg-danger text-white' :
                    ($r['priority'] === 'medium' ? 'bg-warning text-dark' : 'bg-success text-white')
                ?>">
                  <?= ucfirst($r['priority']) ?>
                </span>
              </td>
              <td>
                <span class="status-badge <?=
                    $r['status'] === 'completed' ? 'bg-success text-white' :
                    ($r['status'] === 'delayed'   ? 'bg-danger text-white'  : 'bg-warning text-dark')
                ?>">
                  <?= ucfirst($r['status']) ?>
                </span>
              </td>
              <td class="fs-xs text-muted">
                <?= $r['completed_at'] ? date('M d, Y', strtotime($r['completed_at'])) : '—' ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($data)): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">No records found</td></tr>
          <?php endif; ?>
        </tbody>
      <?php endif; ?>

    </table>
  </div>

  <!-- Pagination — preserves all active filters in links -->
  <?php if ($totalPages > 1): ?>
    <div class="card-body d-flex justify-content-center pt-2">
      <nav><ul class="pagination pagination-sm mb-0">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
          <li class="page-item <?= $i === $page ? 'active' : '' ?>">
            <a class="page-link"
               href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
              <?= $i ?>
            </a>
          </li>
        <?php endfor; ?>
      </ul></nav>
    </div>
  <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
