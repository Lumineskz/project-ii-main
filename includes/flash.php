<?php $flash = getFlash(); ?>
<?php if ($flash): ?>
  <div class="alert alert-<?= e($flash['type']) ?>" data-autohide>
    <i class="fa-solid <?= $flash['type'] === 'success' ? 'fa-circle-check' : ($flash['type'] === 'error' ? 'fa-circle-exclamation' : 'fa-circle-info') ?>"></i>
    <?= e($flash['message']) ?>
  </div>
<?php endif; ?>
