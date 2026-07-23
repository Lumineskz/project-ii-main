<?php
/**
 * Public-facing header. Included by index.php and the auth pages.
 * Dynamically switches between "Login / Register" buttons and a
 * logged-in user chip + dashboard/logout buttons, based on role.
 */
$user = currentUser();
$dashboardUrl = BASE_URL . '/index.php';
if ($user) {
    if ($user['role'] === 'admin') $dashboardUrl = BASE_URL . '/admin/dashboard.php';
    else $dashboardUrl = BASE_URL . '/student/dashboard.php';
}
?>
<header class="site-header">
  <div class="container">
    <a href="<?= BASE_URL ?>/index.php" class="brand">
      <i class="fa-solid fa-utensils"></i> <?= e(SITE_NAME) ?>
    </a>

    <nav class="header-nav">
      <a href="<?= BASE_URL ?>/index.php">Home</a>
      <a href="<?= BASE_URL ?>/index.php#how-it-works">How it works</a>
      <a href="<?= BASE_URL ?>/index.php#features">Features</a>
    </nav>

    <div class="header-actions">
      <?php if (!$user): ?>
        <a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-outline btn-sm">
          <i class="fa-solid fa-right-to-bracket"></i> Log In
        </a>
        <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-primary btn-sm">
          <i class="fa-solid fa-user-plus"></i> Register
        </a>
      <?php else: ?>
        <div class="user-chip">
          <div class="avatar"><?= e(strtoupper(substr($user['full_name'], 0, 1))) ?></div>
          <div class="meta">
            <strong><?= e($user['full_name']) ?></strong>
            <span><?= e($user['role']) ?></span>
          </div>
        </div>
        <a href="<?= $dashboardUrl ?>" class="btn btn-primary btn-sm">
          <i class="fa-solid fa-gauge"></i> Dashboard
        </a>
        <a href="<?= BASE_URL ?>/auth/logout.php" class="btn btn-outline btn-sm">
          <i class="fa-solid fa-right-from-bracket"></i>
        </a>
      <?php endif; ?>
    </div>
  </div>
</header>
