<?php
// Expects $pageTitle and optional $pageSubtitle to be set before include.
$u = currentUser();
?>
<div class="topbar">
  <div style="display:flex;align-items:center;gap:14px;">
    <button class="menu-toggle" id="menuToggle"><i class="fa-solid fa-bars"></i></button>
    <div>
      <h1><?= e($pageTitle ?? 'Dashboard') ?></h1>
      <?php if (!empty($pageSubtitle)): ?><div class="topbar-sub"><?= e($pageSubtitle) ?></div><?php endif; ?>
    </div>
  </div>
  <div class="user-chip">
    <div class="avatar"><?= e(strtoupper(substr($u['full_name'], 0, 1))) ?></div>
    <div class="meta">
      <strong><?= e($u['full_name']) ?></strong>
      <span><?= e($u['role']) ?></span>
    </div>
    <?php if ($u['role'] !== 'admin'): ?>
      <span class="balance"><i class="fa-solid fa-wallet"></i> Rs. <?= number_format($u['balance'], 2) ?></span>
    <?php endif; ?>
  </div>
</div>
