<?php
/**
 * admin/settings.php
 * ==================
 * Company configuration page. Four separate forms handled in one file:
 *
 *   1. General settings  — company name, work hours, break rules, leave defaults
 *   2. Logo upload       — upload a company logo shown in the sidebar
 *   3. Leave reset       — manually reset all employees' leave balances
 *   4. Change password   — admin changes their own password
 *
 * Each form has a hidden "form_action" field that tells us which one was submitted.
 */
require_once '../includes/auth.php';
requireRole('admin');
$pageTitle = 'Settings';

// ── Handle all form submissions ───────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $formAction = $_POST['form_action'] ?? '';

    // ── 1. Save general settings ──────────────────────────────
    if ($formAction === 'settings') {
        // Save each setting key to the database
        $settingKeys = [
            'company_name', 'work_start_time', 'work_end_time',
            'break_max_hours', 'break_deadline',
            'annual_local_leaves', 'annual_sick_leaves',
        ];
        foreach ($settingKeys as $key) {
            if (isset($_POST[$key])) {
                Setting::set($key, trim($_POST[$key]));
            }
        }
        flash('Settings saved successfully.');

    // ── 2. Upload company logo ────────────────────────────────
    } elseif ($formAction === 'logo') {

        // Where to save the file on disk
        $uploadDir = realpath(__DIR__ . '/../uploads') . DIRECTORY_SEPARATOR . 'logo' . DIRECTORY_SEPARATOR;

        // Create the directory if it doesn't exist yet
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Get info about the uploaded file
        $fileError = $_FILES['logo']['error']    ?? UPLOAD_ERR_NO_FILE;
        $fileTmp   = $_FILES['logo']['tmp_name'] ?? '';
        $fileName  = $_FILES['logo']['name']     ?? '';
        $fileSize  = $_FILES['logo']['size']     ?? 0;

        // Check for PHP upload errors (e.g. file too large per php.ini)
        if ($fileError !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_NO_FILE    => 'No file was selected.',
                UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload limit.',
                UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
                UPLOAD_ERR_NO_TMP_DIR => 'Server temporary folder is missing.',
                UPLOAD_ERR_CANT_WRITE => 'Server failed to write file to disk.',
            ];
            flash($errorMessages[$fileError] ?? "Upload error (code: $fileError).", 'error');
            redirect('settings.php');
        }

        // Only allow image file types
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed   = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'];
        if (!in_array($extension, $allowed)) {
            flash('Invalid file type. Allowed: JPG, PNG, GIF, SVG, WebP.', 'error');
            redirect('settings.php');
        }

        // Limit file size to 2 MB
        if ($fileSize > 2 * 1024 * 1024) {
            flash('File is too large. Maximum allowed size is 2 MB.', 'error');
            redirect('settings.php');
        }

        // Check the upload directory is writable
        if (!is_writable($uploadDir)) {
            flash('Upload directory is not writable. Run: chmod 755 uploads/logo', 'error');
            redirect('settings.php');
        }

        // Delete the old logo file (keep the folder clean)
        $oldLogo = Setting::get('company_logo');
        if ($oldLogo && file_exists($uploadDir . $oldLogo)) {
            @unlink($uploadDir . $oldLogo);
        }

        // Generate a unique safe filename (timestamp + random bytes)
        $newName  = 'logo_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $destPath = $uploadDir . $newName;

        if (move_uploaded_file($fileTmp, $destPath)) {
            Setting::set('company_logo', $newName); // save the filename to the database
            flash('Logo updated successfully.');
        } else {
            flash('Failed to move uploaded file. Check server permissions.', 'error');
        }

    // ── 3. Reset all employee leave balances ──────────────────
    } elseif ($formAction === 'reset_leaves') {
        // Get the configured annual leave amounts from settings
        $local = (int)Setting::get('annual_local_leaves', '22');
        $sick  = (int)Setting::get('annual_sick_leaves',  '15');
        (new User())->resetAllLeaves($local, $sick);
        flash("All employee leave balances reset to {$local} local / {$sick} sick.");

    // ── 4. Change admin's own password ────────────────────────
    } elseif ($formAction === 'change_password') {
        $uid     = Auth::userId();
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $uObj    = new User();

        if (!$uObj->verifyPassword($uid, $current)) {
            flash('Current password is incorrect.', 'error');
        } elseif ($new !== $confirm) {
            flash('New passwords do not match.', 'error');
        } elseif (strlen($new) < 8) {
            flash('Password must be at least 8 characters.', 'error');
        } else {
            $uObj->updatePassword($uid, $new);
            flash('Password changed successfully.');
        }
    }

    redirect('settings.php');
}

// ── Load current settings for display ────────────────────────
$settings = Setting::getAll();
$logo     = $settings['company_logo'] ?? '';

// Check the logo file actually exists on disk before trying to display it
$logoAbsPath = realpath(__DIR__ . '/../uploads/logo') . DIRECTORY_SEPARATOR . $logo;
$logoExists  = $logo && file_exists($logoAbsPath);
$logoUrl     = $logoExists ? '../uploads/logo/' . urlencode($logo) : '';

require_once '../includes/header.php';
?>

<div class="row g-3">

  <!-- ── Left column: work settings ── -->
  <div class="col-md-6">
    <div class="card">
      <div class="card-header">
        <i class="bi bi-building me-2" style="color:#4f6af0"></i>Company &amp; Work Settings
      </div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="form_action" value="settings">

          <div class="mb-3">
            <label class="form-label form-label-sm">Company Name</label>
            <input type="text" name="company_name" class="form-control form-control-sm"
                   value="<?= sanitize($settings['company_name'] ?? '') ?>">
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label form-label-sm">Work Start Time</label>
              <input type="time" name="work_start_time" class="form-control form-control-sm"
                     value="<?= sanitize($settings['work_start_time'] ?? '08:30') ?>">
            </div>
            <div class="col-6">
              <label class="form-label form-label-sm">Work End Time</label>
              <input type="time" name="work_end_time" class="form-control form-control-sm"
                     value="<?= sanitize($settings['work_end_time'] ?? '17:00') ?>">
            </div>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label form-label-sm">Max Break Duration (hrs)</label>
              <input type="number" name="break_max_hours" class="form-control form-control-sm"
                     value="<?= sanitize($settings['break_max_hours'] ?? '1') ?>"
                     min="0.25" max="3" step="0.25">
            </div>
            <div class="col-6">
              <label class="form-label form-label-sm">Break Deadline</label>
              <input type="time" name="break_deadline" class="form-control form-control-sm"
                     value="<?= sanitize($settings['break_deadline'] ?? '15:00') ?>">
            </div>
          </div>

          <div class="row g-2 mb-4">
            <div class="col-6">
              <label class="form-label form-label-sm">Annual Local Leaves</label>
              <input type="number" name="annual_local_leaves" class="form-control form-control-sm"
                     value="<?= sanitize($settings['annual_local_leaves'] ?? '22') ?>" min="0">
            </div>
            <div class="col-6">
              <label class="form-label form-label-sm">Annual Sick Leaves</label>
              <input type="number" name="annual_sick_leaves" class="form-control form-control-sm"
                     value="<?= sanitize($settings['annual_sick_leaves'] ?? '15') ?>" min="0">
            </div>
          </div>

          <button type="submit" class="btn btn-primary btn-sm">
            <i class="bi bi-save me-1"></i>Save Settings
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- ── Right column: logo, leave reset, password ── -->
  <div class="col-md-6">

    <!-- Logo upload card -->
    <div class="card mb-3">
      <div class="card-header">
        <i class="bi bi-image me-2" style="color:#4f6af0"></i>Company Logo
      </div>
      <div class="card-body">

        <!-- Current logo preview area -->
        <div class="mb-3 text-center p-3"
             style="background:#f8f9ff;border-radius:8px;border:1px dashed #d1d5db;min-height:90px;
                    display:flex;align-items:center;justify-content:center">
          <?php if ($logoExists): ?>
            <!-- Show the current logo with a cache-busting query string -->
            <img id="currentLogo" src="<?= htmlspecialchars($logoUrl) ?>?v=<?= time() ?>"
                 alt="Company Logo"
                 style="max-height:80px;max-width:220px;object-fit:contain">
          <?php else: ?>
            <div class="text-muted text-center">
              <i class="bi bi-image d-block fs-2 mb-1"></i>
              <span class="fs-xs">No logo uploaded yet</span>
            </div>
          <?php endif; ?>
        </div>

        <!--
          IMPORTANT: enctype="multipart/form-data" is REQUIRED for file uploads.
          Without it, PHP's $_FILES array will always be empty.
        -->
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="form_action" value="logo">

          <div class="mb-2">
            <label class="form-label form-label-sm">Select new logo</label>
            <input type="file" name="logo" id="logoInput"
                   class="form-control form-control-sm"
                   accept=".jpg,.jpeg,.png,.gif,.svg,.webp"
                   required
                   onchange="previewLogo(this)">
          </div>

          <!-- Live preview of the selected file before submitting -->
          <div id="logoPreviewWrap" style="display:none" class="mb-2 text-center">
            <img id="logoPreviewImg" src="" alt="Preview"
                 style="max-height:60px;max-width:180px;object-fit:contain;border-radius:6px;border:1px solid #e5e7eb">
            <div class="fs-xs text-muted mt-1" id="logoPreviewName"></div>
          </div>

          <div class="text-muted mb-3 fs-xs">
            <i class="bi bi-info-circle me-1"></i>Accepted: JPG, PNG, GIF, SVG, WebP · Max 2 MB
          </div>

          <button type="submit" class="btn btn-primary btn-sm w-100">
            <i class="bi bi-upload me-1"></i>Upload Logo
          </button>
        </form>
      </div>
    </div>

    <!-- Leave balance reset card -->
    <div class="card mb-3">
      <div class="card-header">
        <i class="bi bi-arrow-clockwise me-2" style="color:#4f6af0"></i>Annual Leave Reset
      </div>
      <div class="card-body">
        <p class="text-muted fs-sm mb-3">
          Manually reset all employee leave balances to the annual defaults.
          This also runs automatically on January 1 via <code>cron.php</code>.
        </p>
        <form method="POST"
              onsubmit="return confirm('Reset ALL employee leave balances? This cannot be undone.')">
          <input type="hidden" name="form_action" value="reset_leaves">
          <button type="submit" class="btn btn-warning btn-sm">
            <i class="bi bi-arrow-clockwise me-1"></i>Reset All Leave Balances
          </button>
        </form>
      </div>
    </div>

    <!-- Change password card -->
    <div class="card">
      <div class="card-header">
        <i class="bi bi-lock me-2" style="color:#4f6af0"></i>Change My Password
      </div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="form_action" value="change_password">
          <div class="mb-2">
            <label class="form-label form-label-sm">Current Password</label>
            <input type="password" name="current_password" class="form-control form-control-sm" required>
          </div>
          <div class="mb-2">
            <label class="form-label form-label-sm">New Password <span class="text-muted">(min 8 characters)</span></label>
            <input type="password" name="new_password" class="form-control form-control-sm" minlength="8" required>
          </div>
          <div class="mb-3">
            <label class="form-label form-label-sm">Confirm New Password</label>
            <input type="password" name="confirm_password" class="form-control form-control-sm" required>
          </div>
          <button type="submit" class="btn btn-primary btn-sm">
            <i class="bi bi-shield-lock me-1"></i>Change Password
          </button>
        </form>
      </div>
    </div>

  </div>
</div>

<script>
/**
 * Show a preview of the selected logo image before the form is submitted.
 * Also does a client-side file size check for instant feedback.
 *
 * @param {HTMLInputElement} input  The file input element
 */
function previewLogo(input) {
  var wrap = document.getElementById('logoPreviewWrap');
  var img  = document.getElementById('logoPreviewImg');
  var name = document.getElementById('logoPreviewName');

  if (!input.files || !input.files[0]) {
    wrap.style.display = 'none';
    return;
  }

  var file = input.files[0];

  // Quick client-side check (2 MB limit) — the server also validates this
  if (file.size > 2 * 1024 * 1024) {
    alert('File is too large. Maximum size is 2 MB.');
    input.value = '';
    wrap.style.display = 'none';
    return;
  }

  // Use FileReader to display a preview without uploading
  var reader = new FileReader();
  reader.onload = function(e) {
    img.src          = e.target.result;
    name.textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
    wrap.style.display = 'block';
  };
  reader.readAsDataURL(file);
}
</script>

<?php require_once '../includes/footer.php'; ?>
