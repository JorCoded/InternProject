<?php
/**
 * executive/dashboard.php
 * ========================
 * Personal dashboard for executives.
 * Executives only see their own attendance data.
 * They can start/end breaks like regular employees.
 * They do NOT see HR data, tasks, or leave management features.
 */
require_once '../includes/auth.php';
requireRole('executive');
$pageTitle = 'Executive Dashboard';
$user      = Auth::currentUser();
$attModel  = new Attendance();
$att       = $attModel->getToday($user['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fa = $_POST['form_action'] ?? '';
    if ($fa === 'break_start') {
        $result = $att ? $attModel->startBreak($att['id']) : ['ok'=>false,'msg'=>'Not clocked in.'];
        flash($result['msg'], $result['ok'] ? 'success' : 'error');
    } elseif ($fa === 'break_end') {
        $result = $att ? $attModel->endBreak($att['id']) : ['ok'=>false,'msg'=>'Not clocked in.'];
        flash($result['msg'], $result['ok'] ? 'success' : 'error');
    }
    redirect('dashboard.php');
}

$att        = $attModel->getToday($user['id']);
$monthStats = $attModel->getMonthStats($user['id']);
$attHistory = $attModel->getHistory($user['id'], date('Y-m-01'), date('Y-m-d'), 10, 0);

require_once '../includes/header.php';
?>
<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="clock-widget">
      <div class="live-clock clock-time">00:00:00</div>
      <div class="live-date clock-date"></div>
      <hr>
      <?php if (!$att): ?>
        <div class="opacity-75 fs-sm">Not clocked in today</div>
      <?php elseif (!$att['clock_out']): ?>
        <?php if ($att['break_start'] && !$att['break_end']): ?>
          <span class="badge bg-warning text-dark mb-2">On Break</span>
          <div class="fs-sm opacity-80">Started: <?= date('h:i A',strtotime($att['break_start'])) ?></div>
          <form method="POST" class="mt-3"><input type="hidden" name="form_action" value="break_end">
            <button class="btn btn-sm btn-warning w-100">End Break</button></form>
        <?php else: ?>
          <span class="badge bg-success mb-2">Clocked In – <?= date('h:i A',strtotime($att['clock_in'])) ?></span>
          <?php if (!$att['break_start']): ?>
            <form method="POST" class="mt-3"><input type="hidden" name="form_action" value="break_start">
              <button class="btn btn-sm btn-outline-light w-100"><i class="bi bi-cup-hot me-1"></i>Start Break</button></form>
          <?php else: ?><div class="fs-xs opacity-70 mt-2">Break already taken today</div><?php endif; ?>
        <?php endif; ?>
      <?php else: ?>
        <span class="badge bg-secondary mb-2">Clocked Out</span>
        <div class="fs-sm opacity-80"><?= date('h:i A',strtotime($att['clock_in'])) ?> → <?= date('h:i A',strtotime($att['clock_out'])) ?></div>
        <div class="fs-xs opacity-70 mt-1">Total: <?= Attendance::formatHours($att['total_hours']) ?></div>
      <?php endif; ?>
    </div>
  </div>
  <div class="col-md-8">
    <div class="row g-3">
      <div class="col-6"><div class="card text-center py-3"><div style="font-size:2rem;font-weight:700;color:#16a34a"><?= $monthStats['present']??0 ?></div><div class="fs-xs text-muted">Days Present</div></div></div>
      <div class="col-6"><div class="card text-center py-3"><div style="font-size:2rem;font-weight:700;color:#d97706"><?= $monthStats['late']??0 ?></div><div class="fs-xs text-muted">Late Days</div></div></div>
      <div class="col-6"><div class="card text-center py-3"><div style="font-size:2rem;font-weight:700;color:#2563eb"><?= $user['local_leaves'] ?></div><div class="fs-xs text-muted">Local Leaves Left</div></div></div>
      <div class="col-6"><div class="card text-center py-3"><div style="font-size:2rem;font-weight:700;color:#06b6d4"><?= $user['sick_leaves'] ?></div><div class="fs-xs text-muted">Sick Leaves Left</div></div></div>
    </div>
  </div>
</div>
<div class="card">
  <div class="card-header"><i class="bi bi-calendar-check me-2" style="color:#4f6af0"></i>My Attendance This Month</div>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead><tr><th>Date</th><th>Clock In</th><th>Clock Out</th><th>Break</th><th>Hours</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($attHistory as $a): ?>
          <tr>
            <td class="fw-500"><?= date('D, M d Y',strtotime($a['date'])) ?></td>
            <td><?= $a['clock_in']?date('h:i A',strtotime($a['clock_in'])):'—' ?></td>
            <td><?= $a['clock_out']?date('h:i A',strtotime($a['clock_out'])):'Active' ?></td>
            <td class="fs-xs"><?= $a['break_start']?date('h:i',strtotime($a['break_start'])).($a['break_end']?' – '.date('h:i',strtotime($a['break_end'])):'...'):'—' ?></td>
            <td><?= $a['total_hours']?Attendance::formatHours($a['total_hours']):'—' ?></td>
            <td><span class="status-badge <?= $a['status']==='present'?'bg-success text-white':($a['status']==='late'?'bg-warning text-dark':($a['status']==='on_leave'?'bg-info text-dark':'bg-danger text-white')) ?>"><?= ucfirst(str_replace('_',' ',$a['status'])) ?></span></td>
          </tr>
        <?php endforeach; ?>
        <?php if(empty($attHistory)): ?><tr><td colspan="6" class="text-center text-muted py-4">No records yet</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once '../includes/footer.php'; ?>
