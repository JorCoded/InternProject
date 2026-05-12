<?php
/**
 * admin/tasks.php
 * ===============
 * Admin task management page.
 * Admins can assign tasks to employees, view all tasks, and delete them.
 *
 * HOW IT WORKS:
 *  - GET  → display the task list (with optional search/status filter)
 *  - POST → create a new task OR delete an existing one
 *           (determined by the hidden "form_action" field)
 *
 * When a task is assigned, the employee gets an in-app notification.
 */
require_once '../includes/auth.php';
requireRole('admin');
$pageTitle = 'Task Management';

$taskObj = new Task();
$notif   = new Notification();

// ── Handle form submissions (POST) ───────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $formAction = $_POST['form_action'] ?? '';

    // ── CREATE a new task ──────────────────────────────────────
    if ($formAction === 'create') {
        $tid = $taskObj->create(
            trim($_POST['title']),
            trim($_POST['description'] ?? ''),
            intval($_POST['assigned_to']),
            Auth::userId(),               // the admin who is creating it
            $_POST['due_date'] ?: null,   // null if no due date was set
            $_POST['priority'] ?? 'medium'
        );

        if ($tid) {
            flash('Task assigned successfully.');
            // Notify the employee they have a new task
            $notif->create(
                intval($_POST['assigned_to']),
                'task',
                'New Task Assigned',
                'You have been assigned: ' . trim($_POST['title']),
                $tid
            );
            // Also send an email to the employee
            $task     = $taskObj->getById($tid);
            $employee = (new User())->findById(intval($_POST['assigned_to']));
            if ($employee && $task) {
                Mailer::taskAssigned($employee, $task);
            }
        } else {
            flash('Error creating task.', 'error');
        }

    // ── DELETE a task permanently ─────────────────────────────
    } elseif ($formAction === 'delete') {
        $taskObj->delete(intval($_POST['task_id']));
        flash('Task deleted.');
    }

    redirect('tasks.php');
}

// ── Load data for the page ────────────────────────────────────
$search       = sanitize($_GET['search'] ?? '');
$filterStatus = sanitize($_GET['status'] ?? '');
$page         = max(1, intval($_GET['page'] ?? 1));
$perPage      = 15;
$offset       = ($page - 1) * $perPage;

$total      = $taskObj->countAll($search, $filterStatus);
$tasks      = $taskObj->getAll($search, $filterStatus, $perPage, $offset);
$totalPages = (int)ceil($total / $perPage);

// Employee list for the "Assign To" dropdown in the modal
$employees = (new User())->getEmployees();

require_once '../includes/header.php';
?>

<!-- ── Toolbar ── -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <div class="d-flex gap-2 flex-wrap">
    <input type="text" class="form-control form-control-sm" style="width:200px"
           placeholder="Search task or employee…"
           value="<?= $search ?>" id="searchInput">
    <select class="form-select form-select-sm" style="width:140px" id="statusFilter">
      <option value="">All Status</option>
      <option value="pending"   <?= $filterStatus === 'pending'   ? 'selected' : '' ?>>Pending</option>
      <option value="completed" <?= $filterStatus === 'completed' ? 'selected' : '' ?>>Completed</option>
      <option value="delayed"   <?= $filterStatus === 'delayed'   ? 'selected' : '' ?>>Delayed</option>
    </select>
    <button class="btn btn-sm btn-outline-primary"
            onclick="applyFilter({ search: document.getElementById('searchInput').value, status: document.getElementById('statusFilter').value })">
      <i class="bi bi-search me-1"></i>Filter
    </button>
  </div>
  <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#taskModal">
    <i class="bi bi-plus-circle me-1"></i>Assign Task
  </button>
</div>

<!-- ── Task table ── -->
<div class="card">
  <div class="card-header">
    <i class="bi bi-check2-square me-2" style="color:#4f6af0"></i>Tasks
    <span class="text-muted fs-xs ms-1">(<?= $total ?>)</span>
  </div>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th>Title</th><th>Assigned To</th><th>Due Date</th>
          <th>Priority</th><th>Status</th><th>Created</th><th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($tasks)): ?>
          <tr><td colspan="7" class="text-center text-muted py-4">No tasks found</td></tr>
        <?php else: ?>
          <?php foreach ($tasks as $t): ?>
            <tr>
              <!-- Task title + optional description excerpt + delay reason -->
              <td>
                <div class="fs-sm fw-600"><?= sanitize($t['title']) ?></div>
                <?php if ($t['description']): ?>
                  <div class="fs-xs text-muted">
                    <?= sanitize(substr($t['description'], 0, 60)) ?>
                    <?= strlen($t['description']) > 60 ? '…' : '' ?>
                  </div>
                <?php endif; ?>
                <!-- Show delay reason in red if task was delayed -->
                <?php if ($t['status'] === 'delayed' && $t['delay_reason']): ?>
                  <div class="fs-xs text-danger mt-1">
                    <i class="bi bi-clock me-1"></i>
                    <?= sanitize(substr($t['delay_reason'], 0, 60)) ?>
                  </div>
                <?php endif; ?>
              </td>

              <!-- Assigned employee -->
              <td>
                <div class="d-flex align-items-center gap-2">
                  <div class="avatar" style="width:26px;height:26px;font-size:.72rem">
                    <?= strtoupper(substr($t['first_name'], 0, 1)) ?>
                  </div>
                  <span class="fs-sm"><?= sanitize($t['first_name'] . ' ' . $t['last_name']) ?></span>
                </div>
              </td>

              <!-- Due date — highlighted red if overdue -->
              <td class="fs-sm">
                <?php if ($t['due_date']): ?>
                  <?php $isOverdue = ($t['status'] === 'pending' && $t['due_date'] < date('Y-m-d')); ?>
                  <span <?= $isOverdue ? 'class="text-danger fw-600"' : '' ?>>
                    <?= date('M d, Y', strtotime($t['due_date'])) ?>
                    <?= $isOverdue ? ' <i class="bi bi-exclamation-circle"></i>' : '' ?>
                  </span>
                <?php else: ?>—<?php endif; ?>
              </td>

              <!-- Priority badge: red=high, yellow=medium, green=low -->
              <td>
                <span class="status-badge <?=
                    $t['priority'] === 'high'   ? 'bg-danger text-white'  :
                    ($t['priority'] === 'medium' ? 'bg-warning text-dark' : 'bg-success text-white')
                ?>">
                  <?= ucfirst($t['priority']) ?>
                </span>
              </td>

              <!-- Status badge -->
              <td>
                <span class="status-badge <?=
                    $t['status'] === 'completed' ? 'bg-success text-white' :
                    ($t['status'] === 'delayed'   ? 'bg-danger text-white'  : 'bg-warning text-dark')
                ?>">
                  <?= ucfirst($t['status']) ?>
                </span>
              </td>

              <td class="fs-xs text-muted"><?= date('M d', strtotime($t['created_at'])) ?></td>

              <!-- Delete button — uses a mini inline form so we can POST the task_id -->
              <td>
                <form method="POST" class="d-inline"
                      onsubmit="return confirm('Delete this task permanently?')">
                  <input type="hidden" name="form_action" value="delete">
                  <input type="hidden" name="task_id"     value="<?= $t['id'] ?>">
                  <button class="btn btn-sm btn-outline-danger py-0 px-2">
                    <i class="bi bi-trash"></i>
                  </button>
                </form>
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
               href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($filterStatus) ?>">
              <?= $i ?>
            </a>
          </li>
        <?php endfor; ?>
      </ul></nav>
    </div>
  <?php endif; ?>
</div>

<!-- ── Assign Task Modal ── -->
<div class="modal fade" id="taskModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Assign New Task</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <input type="hidden" name="form_action" value="create">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label form-label-sm">Task Title *</label>
            <input type="text" name="title" class="form-control form-control-sm"
                   placeholder="e.g. Prepare Q4 report" required>
          </div>
          <div class="mb-3">
            <label class="form-label form-label-sm">Description</label>
            <textarea name="description" class="form-control form-control-sm" rows="3"
                      placeholder="Optional task details…"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label form-label-sm">Assign To *</label>
            <select name="assigned_to" class="form-select form-select-sm" required>
              <option value="">— Select Employee —</option>
              <?php foreach ($employees as $e): ?>
                <option value="<?= $e['id'] ?>">
                  <?= sanitize($e['first_name'] . ' ' . $e['last_name']) ?>
                  (<?= sanitize($e['employee_id']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="row g-2">
            <div class="col-6">
              <label class="form-label form-label-sm">Due Date</label>
              <input type="date" name="due_date" class="form-control form-control-sm"
                     min="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-6">
              <label class="form-label form-label-sm">Priority</label>
              <select name="priority" class="form-select form-select-sm">
                <option value="low">Low</option>
                <option value="medium" selected>Medium</option>
                <option value="high">High</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm">
            <i class="bi bi-send me-1"></i>Assign Task
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Allow pressing Enter in the search box to trigger the filter
document.getElementById('searchInput').addEventListener('keypress', function(e) {
  if (e.key === 'Enter') {
    applyFilter({
      search: e.target.value,
      status: document.getElementById('statusFilter').value
    });
  }
});
</script>

<?php require_once '../includes/footer.php'; ?>
