<?php
/**
 * admin/announcements.php
 * Post, toggle visibility, and delete company announcements.
 * All active announcements are visible to all employees.
 */
require_once '../includes/auth.php';
requireRole('admin');
$pageTitle = 'Announcements';

$annObj = new Announcement();

// ── Handle POST actions ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fa    = $_POST['form_action'] ?? '';
    $annId = intval($_POST['ann_id'] ?? 0);

    if ($fa === 'create') {
        // Post a new announcement
        $id = $annObj->create(
            trim($_POST['title']),
            trim($_POST['content']),
            Auth::userId()
        );
        flash($id ? 'Announcement posted.' : 'Error posting announcement.', $id ? 'success' : 'error');

    } elseif ($fa === 'delete') {
        // Permanently delete an announcement
        $annObj->delete($annId);
        flash('Announcement deleted.');

    } elseif ($fa === 'toggle') {
        // Toggle active / hidden state
        $annObj->toggle($annId);
        flash('Announcement visibility updated.');
    }

    redirect('announcements.php');
}

// ── Paginate announcements ──────────────────────────────────
$page       = max(1, intval($_GET['page'] ?? 1));
$perPage    = 10;
$offset     = ($page - 1) * $perPage;
$total      = $annObj->countAll();
$anns       = $annObj->getAll(false, $perPage, $offset);
$totalPages = (int)ceil($total / $perPage);

require_once '../includes/header.php';
?>

<!-- ── New announcement button ── -->
<div class="d-flex justify-content-end mb-3">
  <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#annModal">
    <i class="bi bi-plus-circle me-1"></i>New Announcement
  </button>
</div>

<!-- ── Announcement cards ── -->
<div class="row g-3">
  <?php if (empty($anns)): ?>
    <div class="col-12">
      <div class="card">
        <div class="card-body text-center text-muted py-5">
          <i class="bi bi-megaphone d-block fs-1 mb-2"></i>No announcements yet
        </div>
      </div>
    </div>
  <?php else: ?>
    <?php foreach ($anns as $a): ?>
      <div class="col-12">
        <div class="card <?= !$a['is_active'] ? 'opacity-60' : '' ?>">
          <div class="card-body">
            <div class="d-flex gap-3">
              <!-- Colour bar indicator -->
              <div class="ann-bar" style="background:<?= $a['is_active'] ? '#4f6af0' : '#9ca3af' ?>"></div>
              <div style="flex:1">
                <div class="d-flex justify-content-between align-items-start mb-1">
                  <h6 class="mb-0 d-flex align-items-center gap-2">
                    <?= sanitize($a['title']) ?>
                    <?php if ($a['is_active']): ?>
                      <span class="badge bg-success" style="font-size:.68rem">Active</span>
                    <?php else: ?>
                      <span class="badge bg-secondary" style="font-size:.68rem">Hidden</span>
                    <?php endif; ?>
                  </h6>
                  <!-- Action buttons -->
                  <div class="d-flex gap-1 ms-2">
                    <!-- Toggle visibility -->
                    <form method="POST" class="d-inline">
                      <input type="hidden" name="form_action" value="toggle">
                      <input type="hidden" name="ann_id"     value="<?= $a['id'] ?>">
                      <button class="btn btn-sm btn-outline-secondary py-0 px-2"
                              title="<?= $a['is_active'] ? 'Hide' : 'Show' ?> announcement">
                        <i class="bi bi-eye<?= $a['is_active'] ? '-slash' : '' ?>"></i>
                      </button>
                    </form>
                    <!-- Delete permanently -->
                    <form method="POST" class="d-inline"
                          onsubmit="return confirm('Delete this announcement permanently?')">
                      <input type="hidden" name="form_action" value="delete">
                      <input type="hidden" name="ann_id"     value="<?= $a['id'] ?>">
                      <button class="btn btn-sm btn-outline-danger py-0 px-2">
                        <i class="bi bi-trash"></i>
                      </button>
                    </form>
                  </div>
                </div>

                <!-- Content — preserve line breaks -->
                <p class="mb-2 fs-sm" style="white-space:pre-wrap;color:#374151">
                  <?= nl2br(sanitize($a['content'])) ?>
                </p>

                <!-- Footer: author + date -->
                <div class="fs-xs text-muted">
                  <i class="bi bi-person me-1"></i><?= sanitize($a['first_name'] . ' ' . $a['last_name']) ?>
                  &nbsp;·&nbsp;
                  <i class="bi bi-clock me-1"></i><?= date('M d, Y H:i', strtotime($a['created_at'])) ?>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
      <div class="col-12 d-flex justify-content-center">
        <nav><ul class="pagination pagination-sm mb-0">
          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
              <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>
        </ul></nav>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>

<!-- ── New Announcement Modal ── -->
<div class="modal fade" id="annModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">New Announcement</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <input type="hidden" name="form_action" value="create">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label form-label-sm">Title *</label>
            <input type="text" name="title" class="form-control form-control-sm"
                   placeholder="Announcement title…" required>
          </div>
          <div class="mb-3">
            <label class="form-label form-label-sm">Content *</label>
            <textarea name="content" class="form-control form-control-sm" rows="7" required
                      placeholder="Write your announcement here…&#10;&#10;You can use multiple lines."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm">
            <i class="bi bi-megaphone me-1"></i>Post Announcement
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>
