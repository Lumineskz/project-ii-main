<?php
$currentPage = $currentPage ?? '';
$links = [
    'dashboard' => ['icon' => 'fa-gauge',      'label' => 'Dashboard',       'href' => 'dashboard.php'],
    'menu'      => ['icon' => 'fa-bowl-food',  'label' => 'Menu',            'href' => 'menu.php'],
    'cart'      => ['icon' => 'fa-cart-shopping','label' => 'My Cart',       'href' => 'cart.php'],
    'orders'    => ['icon' => 'fa-receipt',    'label' => 'My Orders',       'href' => 'orders.php'],
    'recharge'  => ['icon' => 'fa-wallet',     'label' => 'Recharge Balance','href' => 'recharge.php'],
];
$cCount = isset($conn) ? cartCount($conn, $_SESSION['user_id']) : 0;
?>
<aside class="sidebar" id="userSidebar">
  <div class="brand">
    <i class="fa-solid fa-utensils"></i> <?= e(SITE_NAME) ?>
  </div>
  <nav>
    <div class="nav-label"><?= e(ucfirst($_SESSION['role'])) ?> Panel</div>
    <?php foreach ($links as $key => $link): ?>
      <a href="<?= $link['href'] ?>" class="nav-link <?= $currentPage === $key ? 'active' : '' ?>">
        <i class="fa-solid <?= $link['icon'] ?>"></i> <?= $link['label'] ?>
        <?php if ($key === 'cart' && $cCount > 0): ?>
          <span id="cartCount" class="badge badge-blue" style="margin-left:auto;"><?= $cCount ?></span>
        <?php endif; ?>
      </a>
    <?php endforeach; ?>
  </nav>
  <div class="sidebar-foot">
    <a href="<?= BASE_URL ?>/auth/logout.php" class="nav-link">
      <i class="fa-solid fa-right-from-bracket"></i> Logout
    </a>
  </div>
</aside>
