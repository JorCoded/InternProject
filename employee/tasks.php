<?php
/**
 * employee/tasks.php
 * Employees view their assigned tasks and can mark them as:
 *  - Completed → notifies admin
 *  - Delayed   → requires a reason → notifies admin
 */
require_once '../includes/auth.php';
requireRole(['employee','hr','executive','admin']);
$pageTitle = 'My Tasks';

$uid     = Auth::userId();
$user    = Auth::currentUser();
$taskObj = new Task();
$notif   = new Notification();

// ── Handle POST (complete / delay) ──────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $taskId = intval($_POST['task_id'] ?? 0);
    $fa     = $_POST['form_action'] ?? '';

    if ($fa === 'complete') {
        $ok = $taskObj->complete($taskId, $uid);
        if ($ok) {
            flash('Task marked as completed!');
            // Notify all admins that the task is done
            $task = $taskObj->getById($taskId);
            $notif->notifyAdmins(
                'task_complete',
                'Task Completed',
                "{$user['first_name']} {$user['last_name']} completed: {$task['title']}",
                $taskId
            );
        } else {
            flash('Could not complete task.', 'error');
        }

    } elseif ($fa === 'delay') {
        $reason = trim($_POST['delay_reason'] ?? '');
        if (!$reason) {
            flash('A reason is required when reporting a delay.', 'error');
            redirect('tasks.php');
        }
        $ok = $taskObj->delay($taskId, $uid, $reason);
        if ($ok) {
            flash('Task marked as delayed.');
            // Notify admins about the delay with the reason
            $task = $taskObj->getById($taskId);
            $notif->notifyAdmins(
                'task_delayed',
                'Task Delayed',
                "{$user['first_name']} {$user['last_name']} delayed: {$task['title']}. Reason: $reason",
                $taskId
            );
        } else {
            flash('Could not update task.', 'error');
        }
    }

    redirect('tasks.php');
}

// ── Filter / paginate ────────────────────────────────────────
$filterStatus = sanitize($_GET['status'] ?? '');
$page         = max(1, intval($_GET['page'] ?? 1));
$perPage      = 12;
$offset       = ($page - 1) * $perPage;

$total      = $taskObj->countForUser($uid, $filterStatus);
$tasks      = $taskObj->getForUser($uid, $filterStatus, $perPage, $offset);
$totalPages = (int)ceil($total / $perPage);

require_once '../includes/header.php';
?>

<!-- ── Filter bar ── -->
<div class="d-flex gap-2 mb-3 align-items-center">
  <select class="form-select form-select-sm" style="width:160px" id="statusFilter"
          onchange="applyFilter({status:this.value})">
    <option value="">All Tasks</option>
    <option value="pending"   <?= $filterStatus === 'pending'   ? 'selected' : '' ?>>Pending</option>
    <option value="completed" <?= $filterStatus === 'completed' ? 'selected' : '' ?>>Completed</option>
    <option value="delayed"   <?= $filterStatus === 'delayed'   ? 'selected' : '' ?>>Delayed</option>
  </select>
  <span class="text-muted ms-auto fs-xs"><?= $total ?> task(s)</span>
</div>

<!-- ── Task cards grid ── -->
<div class="row g-3">
  <?php if (empty($tasks)): ?>
    <div class="col-12">
      <div class="card">
        <div class="card-body text-center text-muted py-5">
          <i class="bi bi-check-all d-block fs-1 mb-2"></i>
          <?= $filterStatus ? 'No ' . $filterStatus . ' tasks.' : 'No tasks assigned yet.' ?>
        </div>
      </div>
    </div>
  <?php else: ?>
    <?php foreach ($tasks as $t): ?>
      <div class="col-md-6 col-lg-4">
        <div class="card h-100">
          <div class="card-body">

            <!-- Priority + status badges -->
            <div class="d-flex justify-content-between align-items-start mb-2">
              <span class="status-badge
                <?= $t['priority'] === 'high'   ? 'bg-danger text-white'
                  : ($t['priority'] === 'medium' ? 'bg-warning text-dark'
                  : 'bg-success text-white') ?>">
                <?= ucfirst($t['priority']) ?>
              </span>
              <span class="status-badge
                <?= $t['status'] === 'completed' ? 'bg-success text-white'
                  : ($t['status'] === 'delayed'   ? 'bg-danger text-white'
                  : 'bg-warning text-dark') ?>">
                <?= ucfirst($t['status']) ?>
              </span>
            </div>

            <!-- Task title -->
            <h6 class="mb-1"><?= sanitize($t['title']) ?></h6>

            <!-- Description (truncated) -->
            <?php if ($t['description']): ?>
              <p class="text-muted fs-xs mb-2">
                <?= sanitize(substr($t['description'], 0, 120)) ?><?= strlen($t['description']) > 120 ? '…' : '' ?>
              </p>
            <?php endif; ?>

            <!-- Delay reason alert -->
            <?php if ($t['delay_reason']): ?>
              <div class="alert alert-danger py-1 px-2 mb-2 fs-xs">
                <strong>Delay reason:</strong> <?= sanitize($t['delay_reason']) ?>
              </div>
            <?php endif; ?>

            <!-- Due date + assigned by -->
            <div class="fs-xs text-muted">
              <i class="bi bi-person me-1"></i>
              <?= sanitize($t['admin_fn'] . ' ' . $t['admin_ln']) ?>
              <?php if ($t['due_date']): ?>
                &nbsp;·&nbsp;
                <?php $isOverdue = ($t['status'] === 'pending' && $t['due_date'] < date('Y-m-d')); ?>
                <i class="bi bi-calendar<?= $isOverdue ? '-x text-danger' : '' ?> me-1"></i>
                <span <?= $isOverdue ? 'class="text-danger fw-600"' : '' ?>>
                  Due <?= date('M d, Y', strtotime($t['due_date'])) ?>
                </span>
              <?php endif; ?>
            </div>

            <!-- Completion timestamp -->
            <?php if ($t['completed_at']): ?>
              <div class="fs-xs text-success mt-1">
                <i class="bi bi-check-circle me-1"></i>
                Completed <?= date('M d, Y H:i', strtotime($t['completed_at'])) ?>
              </div>
            <?php endif; ?>
          </div>

          <!-- Action buttons — for pending and delayed tasks -->
          <?php if ($t['status'] === 'pending' || $t['status'] === 'delayed'): ?>
            <div class="card-footer bg-white border-top d-flex gap-2">
              <!-- Mark complete (available for both pending and delayed tasks) -->
              <form method="POST" class="flex-fill"
                    onsubmit="return confirm('Mark this task as completed?')">
                <input type="hidden" name="form_action" value="complete">
                <input type="hidden" name="task_id"     value="<?= $t['id'] ?>">
                <button class="btn btn-sm btn-success w-100">
                  <i class="bi bi-check-circle me-1"></i>Complete
                </button>
              </form>
              <!-- Report delay — only shown for pending tasks -->
              <?php if ($t['status'] === 'pending'): ?>
                <button class="btn btn-sm btn-outline-danger flex-fill"
                        onclick="openDelay(<?= $t['id'] ?>, '<?= addslashes(sanitize($t['title'])) ?>')"
                        data-bs-toggle="modal" data-bs-target="#delayModal">
                  <i class="bi bi-clock me-1"></i>Delay
                </button>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
  <div class="d-flex justify-content-center mt-3">
    <nav><ul class="pagination pagination-sm mb-0">
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
          <a class="page-link" href="?page=<?= $i ?>&status=<?= urlencode($filterStatus) ?>">
            <?= $i ?>
          </a>
        </li>
      <?php endfor; ?>
    </ul></nav>
  </div>
<?php endif; ?>

<!-- ── Delay Task Modal ── -->
<div class="modal fade" id="delayModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Report Task Delay</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <input type="hidden" name="form_action" value="delay">
        <input type="hidden" name="task_id"     id="delayTaskId">
        <div class="modal-body">
          <!-- Task title shown for context -->
          <p class="fs-sm mb-3">
            Task: <strong id="delayTaskTitle"></strong>
          </p>
          <div>
            <label class="form-label form-label-sm">Reason for Delay *</label>
            <textarea name="delay_reason" class="form-control form-control-sm" rows="4" required
                      placeholder="Explain why this task is delayed and when you expect to complete it…"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger btn-sm">
            <i class="bi bi-clock me-1"></i>Report Delay
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
/**
 * Pre-fill the delay modal with the task details.
 * @param {number} id    - Task ID
 * @param {string} title - Task title for display
 */
function openDelay(id, title) {
  document.getElementById('delayTaskId').value     = id;
  document.getElementById('delayTaskTitle').textContent = title;
}
</script>

<?php require_once '../includes/footer.php'; ?>
