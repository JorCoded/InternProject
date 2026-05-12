<?php
/**
 * employee/announcements.php
 * Shows all active announcements to employees (read-only view).
 * Posted by admin; toggled hidden/visible in admin panel.
 */
require_once '../includes/auth.php';
requireRole(['employee','hr','executive','admin']);
$pageTitle = 'Announcements';

$annObj  = new Announcement();

// ── Paginate active announcements only ───────────────────────
$page       = max(1, intval($_GET['page'] ?? 1));
$perPage    = 10;
$offset     = ($page - 1) * $perPage;
$total      = $annObj->countAll(true);              // active only
$anns       = $annObj->getAll(true, $perPage, $offset);
$totalPages = (int)ceil($total / $perPage);

require_once '../includes/header.php';
?>

<div class="row g-3">
  <?php if (empty($anns)): ?>
    <div class="col-12">
      <div class="card">
        <div class="card-body text-center text-muted py-5">
          <i class="bi bi-megaphone d-block fs-1 mb-2"></i>
          No announcements at this time
        </div>
      </div>
    </div>
  <?php else: ?>
    <?php foreach ($anns as $a): ?>
      <div class="col-12">
        <div class="card">
          <div class="card-body">
            <div class="d-flex gap-3">
              <!-- Colour accent bar -->
              <div class="ann-bar"></div>
              <div style="flex:1">
                <h6 class="mb-1"><?= sanitize($a['title']) ?></h6>
                <!-- Preserve line breaks in content -->
                <p class="mb-2 fs-sm" style="white-space:pre-wrap;color:#374151">
                  <?= nl2br(sanitize($a['content'])) ?>
                </p>
                <!-- Footer: who posted + when -->
                <div class="fs-xs text-muted">
                  <i class="bi bi-person me-1"></i>
                  <?= sanitize($a['first_name'] . ' ' . $a['last_name']) ?>
                  &nbsp;·&nbsp;
                  <i class="bi bi-clock me-1"></i>
                  <?= date('M d, Y H:i', strtotime($a['created_at'])) ?>
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

<?php require_once '../includes/footer.php'; ?>
