<?php
/**
 * admin/employees.php
 * ===================
 * Full employee management — create, edit, activate/deactivate.
 *
 * HOW THIS PAGE WORKS:
 * --------------------
 * The page serves a dual purpose:
 *  - GET requests load the employee list (with optional search/filter)
 *  - POST requests handle form submissions (create or update an employee)
 *
 * The modal form uses a hidden field called "form_action" to tell the server
 * whether to create a new employee or update an existing one.
 *
 * Activate/deactivate uses simple GET links: ?action=delete&id=5
 */
require_once '../includes/auth.php';
requireRole('admin');
$pageTitle = 'Employees';

$userObj = new User();

// ── Handle form submissions (POST) ───────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $formAction = $_POST['form_action'] ?? '';

    // ── CREATE a new employee ─────────────────────────────────
    if ($formAction === 'create') {

        // Check that the email and employee ID are not already in use
        if ($userObj->emailExists($_POST['email'])) {
            flash('An account with that email already exists.', 'error');

        } elseif ($userObj->employeeIdExists($_POST['employee_id'])) {
            flash('That Employee ID is already taken.', 'error');

        } else {
            // Create the account
            $newId = $userObj->create([
                'employee_id'  => trim($_POST['employee_id']),
                'first_name'   => trim($_POST['first_name']),
                'last_name'    => trim($_POST['last_name']),
                'email'        => trim($_POST['email']),
                'password'     => $_POST['password'] ?: 'Password@123', // default if blank
                'role'         => $_POST['role'],
                'department'   => trim($_POST['department']   ?? ''),
                'position'     => trim($_POST['position']     ?? ''),
                'phone'        => trim($_POST['phone']        ?? ''),
                'local_leaves' => intval($_POST['local_leaves'] ?? 22),
                'sick_leaves'  => intval($_POST['sick_leaves']  ?? 15),
            ]);

            if ($newId) {
                // Send the new employee a welcome notification
                (new Notification())->create(
                    $newId, 'account', 'Welcome!',
                    'Your account has been created. Login with: ' . trim($_POST['email'])
                );
                flash('Employee created successfully.');
            } else {
                flash('Error creating employee. Please try again.', 'error');
            }
        }

    // ── UPDATE an existing employee ───────────────────────────
    } elseif ($formAction === 'update') {

        $uid = intval($_POST['user_id']);

        // Check email uniqueness — exclude the current user's own record
        if ($userObj->emailExists(trim($_POST['email']), $uid)) {
            flash('That email is already used by another account.', 'error');
        } else {
            $userObj->update($uid, [
                'first_name'   => trim($_POST['first_name']),
                'last_name'    => trim($_POST['last_name']),
                'email'        => trim($_POST['email']),
                'role'         => $_POST['role'],
                'department'   => trim($_POST['department']   ?? ''),
                'position'     => trim($_POST['position']     ?? ''),
                'phone'        => trim($_POST['phone']        ?? ''),
                'local_leaves' => intval($_POST['local_leaves'] ?? 22),
                'sick_leaves'  => intval($_POST['sick_leaves']  ?? 15),
            ]);

            // Only update password if admin typed a new one (blank = keep existing)
            if (!empty($_POST['password'])) {
                $userObj->updatePassword($uid, $_POST['password']);
            }

            flash('Employee updated successfully.');
        }
    }

    // Redirect to clear the POST data (prevents double-submit on refresh)
    redirect('employees.php');
}

// ── Handle quick actions from GET links ──────────────────────
$action = $_GET['action'] ?? '';
$editId = intval($_GET['id'] ?? 0);

if ($action === 'delete' && $editId && $editId !== Auth::userId()) {
    // Prevent admin from deactivating their own account
    $userObj->deactivate($editId);
    flash('Employee deactivated.');
    redirect('employees.php');
}

if ($action === 'activate' && $editId) {
    $userObj->activate($editId);
    flash('Employee activated.');
    redirect('employees.php');
}

// ── Load data for the page ────────────────────────────────────
$search     = sanitize($_GET['search']      ?? '');
$filterRole = sanitize($_GET['role_filter'] ?? '');
$page       = max(1, intval($_GET['page']   ?? 1));
$perPage    = 15;
$offset     = ($page - 1) * $perPage;

$total      = $userObj->countAll($search, $filterRole);
$employees  = $userObj->getAll($search, $filterRole, $perPage, $offset);
$totalPages = (int)ceil($total / $perPage);

require_once '../includes/header.php';
?>

<!-- ── Toolbar with search and "Add Employee" button ── -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <div class="d-flex gap-2 flex-wrap">
    <input type="text" class="form-control form-control-sm" style="width:200px"
           placeholder="Search name, email, ID…"
           value="<?= $search ?>" id="searchInput">
    <select class="form-select form-select-sm" style="width:140px" id="roleFilter">
      <option value="">All Roles</option>
      <?php foreach (['employee', 'hr', 'executive', 'admin'] as $r): ?>
        <option value="<?= $r ?>" <?= $filterRole === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-sm btn-outline-primary"
            onclick="applyFilter({ search: document.getElementById('searchInput').value, role_filter: document.getElementById('roleFilter').value })">
      <i class="bi bi-search me-1"></i>Filter
    </button>
  </div>
  <!-- This button opens the modal form in "create" mode -->
  <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#empModal">
    <i class="bi bi-plus-circle me-1"></i>Add Employee
  </button>
</div>

<!-- ── Employee table ── -->
<div class="card">
  <div class="card-header">
    <i class="bi bi-people-fill me-2" style="color:#4f6af0"></i>Employees
    <span class="text-muted fs-xs ms-1">(<?= $total ?>)</span>
  </div>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th>EMP ID</th><th>Name</th><th>Email</th>
          <th>Role</th><th>Department</th>
          <th>Leaves (L/S)</th><th>Status</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($employees)): ?>
          <tr><td colspan="8" class="text-center text-muted py-4">No employees found</td></tr>
        <?php else: ?>
          <?php foreach ($employees as $e): ?>
            <tr>
              <td><code class="fs-xs"><?= sanitize($e['employee_id']) ?></code></td>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <!-- Avatar shows the first letter of the employee's name -->
                  <div class="avatar" style="width:30px;height:30px;font-size:.78rem">
                    <?= strtoupper(substr($e['first_name'], 0, 1)) ?>
                  </div>
                  <span class="fs-sm"><?= sanitize($e['first_name'] . ' ' . $e['last_name']) ?></span>
                </div>
              </td>
              <td class="fs-sm"><?= sanitize($e['email']) ?></td>
              <td><span class="badge bg-primary badge-role"><?= ucfirst($e['role']) ?></span></td>
              <td class="fs-sm"><?= sanitize($e['department'] ?? '—') ?></td>
              <!-- L = local leaves remaining, S = sick leaves remaining -->
              <td class="fs-sm"><?= $e['local_leaves'] ?> / <?= $e['sick_leaves'] ?></td>
              <td>
                <span class="status-badge <?= $e['is_active'] ? 'bg-success text-white' : 'bg-secondary text-white' ?>">
                  <?= $e['is_active'] ? 'Active' : 'Inactive' ?>
                </span>
              </td>
              <td>
                <!-- Edit button — passes all employee data to the modal via loadEdit() -->
                <button class="btn btn-sm btn-outline-primary py-0 px-2"
                        onclick="loadEdit(<?= htmlspecialchars(json_encode($e)) ?>)"
                        data-bs-toggle="modal" data-bs-target="#empModal">
                  <i class="bi bi-pencil"></i>
                </button>

                <!-- Deactivate / Reactivate toggle -->
                <?php if ($e['is_active']): ?>
                  <a href="?action=delete&id=<?= $e['id'] ?>"
                     class="btn btn-sm btn-outline-danger py-0 px-2 ms-1"
                     onclick="return confirm('Deactivate this employee?')">
                    <i class="bi bi-person-x"></i>
                  </a>
                <?php else: ?>
                  <a href="?action=activate&id=<?= $e['id'] ?>"
                     class="btn btn-sm btn-outline-success py-0 px-2 ms-1">
                    <i class="bi bi-person-check"></i>
                  </a>
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
               href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&role_filter=<?= urlencode($filterRole) ?>">
              <?= $i ?>
            </a>
          </li>
        <?php endfor; ?>
      </ul></nav>
    </div>
  <?php endif; ?>
</div>

<!-- ── Create / Edit Employee Modal ── -->
<!--
  This single modal handles both CREATE and EDIT.
  When opened fresh it's in "create" mode.
  When the Edit button is clicked, loadEdit() fills in the existing data
  and changes form_action to "update".
-->
<div class="modal fade" id="empModal" tabindex="-1" aria-labelledby="empModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="empModalTitle">Add Employee</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" id="empForm">
        <!-- These hidden fields control whether we're creating or updating -->
        <input type="hidden" name="form_action" id="formAction" value="create">
        <input type="hidden" name="user_id"     id="userId">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label form-label-sm">Employee ID *</label>
              <input type="text" name="employee_id" id="fEmpId" class="form-control form-control-sm" required>
            </div>
            <div class="col-md-4">
              <label class="form-label form-label-sm">First Name *</label>
              <input type="text" name="first_name" id="fFN" class="form-control form-control-sm" required>
            </div>
            <div class="col-md-4">
              <label class="form-label form-label-sm">Last Name *</label>
              <input type="text" name="last_name" id="fLN" class="form-control form-control-sm" required>
            </div>
            <div class="col-md-6">
              <label class="form-label form-label-sm">Email *</label>
              <input type="email" name="email" id="fEmail" class="form-control form-control-sm" required>
            </div>
            <div class="col-md-6">
              <label class="form-label form-label-sm">Role *</label>
              <select name="role" id="fRole" class="form-select form-select-sm">
                <option value="employee">Employee</option>
                <option value="hr">HR</option>
                <option value="executive">Executive</option>
                <option value="admin">Admin</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label form-label-sm">Department</label>
              <input type="text" name="department" id="fDept" class="form-control form-control-sm">
            </div>
            <div class="col-md-6">
              <label class="form-label form-label-sm">Position</label>
              <input type="text" name="position" id="fPos" class="form-control form-control-sm">
            </div>
            <div class="col-md-6">
              <label class="form-label form-label-sm">Phone</label>
              <input type="text" name="phone" id="fPhone" class="form-control form-control-sm">
            </div>
            <div class="col-md-3">
              <label class="form-label form-label-sm">Local Leaves</label>
              <input type="number" name="local_leaves" id="fLL" class="form-control form-control-sm" value="22" min="0">
            </div>
            <div class="col-md-3">
              <label class="form-label form-label-sm">Sick Leaves</label>
              <input type="number" name="sick_leaves" id="fSL" class="form-control form-control-sm" value="15" min="0">
            </div>
            <div class="col-12">
              <label class="form-label form-label-sm" id="pwdLabel">Password *</label>
              <input type="password" name="password" id="fPwd" class="form-control form-control-sm"
                     placeholder="Leave blank to keep current (on edit)">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm">
            <i class="bi bi-check me-1"></i>Save Employee
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
/**
 * Switch the modal into EDIT mode and fill in the employee's current data.
 * This is called when the Edit (pencil) button is clicked.
 *
 * @param {Object} u  The employee record passed as JSON from PHP
 */
function loadEdit(u) {
  // Change the title and the hidden form_action field
  document.getElementById('empModalTitle').textContent = 'Edit Employee';
  document.getElementById('formAction').value  = 'update';  // tells PHP to UPDATE, not INSERT

  // Fill in the form fields with the employee's existing values
  document.getElementById('userId').value   = u.id;
  document.getElementById('fEmpId').value   = u.employee_id;
  document.getElementById('fFN').value      = u.first_name;
  document.getElementById('fLN').value      = u.last_name;
  document.getElementById('fEmail').value   = u.email;
  document.getElementById('fRole').value    = u.role;
  document.getElementById('fDept').value    = u.department  || '';
  document.getElementById('fPos').value     = u.position    || '';
  document.getElementById('fPhone').value   = u.phone       || '';
  document.getElementById('fLL').value      = u.local_leaves;
  document.getElementById('fSL').value      = u.sick_leaves;

  // Password is optional when editing — blank = keep the existing one
  document.getElementById('fPwd').required         = false;
  document.getElementById('pwdLabel').textContent  = 'New Password (leave blank to keep current)';
}

// When the modal closes, reset it back to "create" mode
document.getElementById('empModal').addEventListener('hidden.bs.modal', function() {
  document.getElementById('empModalTitle').textContent = 'Add Employee';
  document.getElementById('formAction').value  = 'create';
  document.getElementById('userId').value      = '';
  document.getElementById('empForm').reset();
  document.getElementById('fLL').value         = 22;
  document.getElementById('fSL').value         = 15;
  document.getElementById('fPwd').required     = true;
  document.getElementById('pwdLabel').textContent = 'Password *';
});

// Allow pressing Enter in the search box to trigger the filter
document.getElementById('searchInput').addEventListener('keypress', function(e) {
  if (e.key === 'Enter') {
    applyFilter({
      search: e.target.value,
      role_filter: document.getElementById('roleFilter').value
    });
  }
});
</script>

<?php require_once '../includes/footer.php'; ?>
