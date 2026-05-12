<?php
/**
 * profile.php
 * User profile page — available to all roles.
 * Allows updating personal info and changing password.
 */
require_once 'includes/auth.php';
Auth::requireLogin();

$pageTitle = 'My Profile';
$userObj   = new User();
$attModel  = new Attendance();
$uid       = Auth::userId();
$user      = $userObj->findById($uid);

// ── Handle form submissions ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fa = $_POST['form_action'] ?? '';

    if ($fa === 'update_profile') {
        // Update name, phone, address
        $userObj->updateProfile($uid, [
            'first_name' => trim($_POST['first_name'] ?? ''),
            'last_name'  => trim($_POST['last_name']  ?? ''),
            'phone'      => trim($_POST['phone']       ?? ''),
            'address'    => trim($_POST['address']     ?? ''),
        ]);
        flash('Profile updated successfully.');

    } elseif ($fa === 'change_password') {
        $curr = $_POST['current_password'] ?? '';
        $new  = $_POST['new_password']     ?? '';
        $conf = $_POST['confirm_password'] ?? '';

        if (!$userObj->verifyPassword($uid, $curr)) {
            flash('Current password is incorrect.', 'error');
        } elseif ($new !== $conf) {
            flash('New passwords do not match.', 'error');
        } elseif (strlen($new) < 8) {
            flash('Password must be at least 8 characters.', 'error');
        } else {
            $userObj->updatePassword($uid, $new);
            flash('Password changed successfully.');
        }
    }

    redirect('profile.php');
}

// Refresh user data after possible update
$user = $userObj->findById($uid);

// Year-to-date attendance stats for the profile card
$attStats = $attModel->getMonthStats($uid);
$yearStats = Database::getInstance()->fetchOne(
    "SELECT COUNT(*) as total,
            SUM(CASE WHEN status='present' THEN 1 ELSE 0 END) as present,
            SUM(CASE WHEN status='late'    THEN 1 ELSE 0 END) as late,
            SUM(total_hours) as hours
     FROM attendance WHERE user_id=? AND YEAR(date)=YEAR(CURDATE())",
    [$uid], 'i'
);

require_once 'includes/header.php';
?>

<div class="row g-3">

  <!-- ── Profile card ── -->
  <div class="col-md-4">
    <div class="card">
      <div class="card-body text-center py-4">

        <!-- Avatar -->
        <div class="avatar mx-auto mb-3"
             style="width:80px;height:80px;font-size:2rem;
                    background:linear-gradient(135deg,#2c3e7a,#4f6af0)">
          <?= strtoupper(substr($user['first_name'] ?? 'U', 0, 1)) ?>
        </div>

        <h5 class="mb-1"><?= sanitize($user['first_name'] . ' ' . $user['last_name']) ?></h5>
        <div class="mb-2">
          <span class="badge bg-primary badge-role"><?= ucfirst($user['role']) ?></span>
        </div>
        <div class="fs-xs text-muted"><?= sanitize($user['employee_id']) ?></div>
        <div class="fs-xs text-muted"><?= sanitize($user['email']) ?></div>
        <?php if ($user['department']): ?>
          <div class="fs-xs text-muted mt-1">
            <?= sanitize($user['department']) ?> · <?= sanitize($user['position'] ?? '') ?>
          </div>
        <?php endif; ?>

        <hr>

        <!-- Year stats -->
        <div class="row g-2 text-center">
          <div class="col-4">
            <div style="font-size:1.3rem;font-weight:700;color:#16a34a"><?= $yearStats['present'] ?? 0 ?></div>
            <div class="fs-xs text-muted">Present</div>
          </div>
          <div class="col-4">
            <div style="font-size:1.3rem;font-weight:700;color:#d97706"><?= $yearStats['late'] ?? 0 ?></div>
            <div class="fs-xs text-muted">Late</div>
          </div>
          <div class="col-4">
            <div style="font-size:1.3rem;font-weight:700;color:#4f6af0">
              <?= Attendance::formatHours((float)($yearStats['hours'] ?? 0)) ?>
            </div>
            <div class="fs-xs text-muted">Hours</div>
          </div>
        </div>

        <hr>

        <!-- Leave balances -->
        <div class="text-start">
          <div class="fs-sm text-muted mb-1">
            <i class="bi bi-calendar-heart me-2" style="color:#4f6af0"></i>
            Local Leaves: <strong><?= $user['local_leaves'] ?>/22</strong>
          </div>
          <div class="fs-sm text-muted mb-1">
            <i class="bi bi-capsule me-2" style="color:#06b6d4"></i>
            Sick Leaves: <strong><?= $user['sick_leaves'] ?>/15</strong>
          </div>
          <div class="fs-sm text-muted">
            <i class="bi bi-cash me-2" style="color:#f59e0b"></i>
            Unpaid Used: <strong><?= $user['unpaid_leaves'] ?></strong>
          </div>
        </div>

      </div>
    </div>
  </div>

  <!-- ── Edit forms ── -->
  <div class="col-md-8">

    <!-- Personal info -->
    <div class="card mb-3">
      <div class="card-header">
        <i class="bi bi-person-gear me-2" style="color:#4f6af0"></i>Edit Profile
      </div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="form_action" value="update_profile">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label form-label-sm">First Name</label>
              <input type="text" name="first_name" class="form-control form-control-sm"
                     value="<?= sanitize($user['first_name']) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label form-label-sm">Last Name</label>
              <input type="text" name="last_name" class="form-control form-control-sm"
                     value="<?= sanitize($user['last_name']) ?>" required>
            </div>
            <div class="col-md-6">
              <!-- Email is read-only — only admin can change it -->
              <label class="form-label form-label-sm">Email <span class="text-muted">(read-only)</span></label>
              <input type="email" class="form-control form-control-sm bg-light"
                     value="<?= sanitize($user['email']) ?>" disabled>
            </div>
            <div class="col-md-6">
              <label class="form-label form-label-sm">Phone</label>
              <input type="text" name="phone" class="form-control form-control-sm"
                     value="<?= sanitize($user['phone'] ?? '') ?>">
            </div>
            <div class="col-12">
              <label class="form-label form-label-sm">Address</label>
              <textarea name="address" class="form-control form-control-sm" rows="2"><?= sanitize($user['address'] ?? '') ?></textarea>
            </div>
          </div>
          <button type="submit" class="btn btn-primary btn-sm mt-3">
            <i class="bi bi-save me-1"></i>Save Profile
          </button>
        </form>
      </div>
    </div>

    <!-- Change password -->
    <div class="card">
      <div class="card-header">
        <i class="bi bi-lock me-2" style="color:#4f6af0"></i>Change Password
      </div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="form_action" value="change_password">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label form-label-sm">Current Password</label>
              <input type="password" name="current_password"
                     class="form-control form-control-sm" required>
            </div>
            <div class="col-md-4">
              <label class="form-label form-label-sm">New Password <span class="text-muted">(min 8)</span></label>
              <input type="password" name="new_password"
                     class="form-control form-control-sm" minlength="8" required>
            </div>
            <div class="col-md-4">
              <label class="form-label form-label-sm">Confirm Password</label>
              <input type="password" name="confirm_password"
                     class="form-control form-control-sm" required>
            </div>
          </div>
          <button type="submit" class="btn btn-warning btn-sm mt-3">
            <i class="bi bi-shield-lock me-1"></i>Change Password
          </button>
        </form>
      </div>
    </div>

  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
