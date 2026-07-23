<?php
// $currentPage should be set by the including page, e.g. 'dashboard'
$currentPage = $currentPage ?? '';
$links = [
    'dashboard'        => ['icon' => 'fa-gauge',          'label' => 'Dashboard',        'href' => 'dashboard.php'],
    'orders'           => ['icon' => 'fa-truck-fast',     'label' => 'Live Orders',       'href' => 'manage_orders.php'],
    'menu'             => ['icon' => 'fa-bowl-food',      'label' => 'Menu Management',   'href' => 'manage_menu.php'],
    'timings'          => ['icon' => 'fa-clock',          'label' => 'Timing Schedules',  'href' => 'manage_timings.php'],
    'users'            => ['icon' => 'fa-users',          'label' => 'Manage Users',      'href' => 'manage_users.php'],
    'kitchen'          => ['icon' => 'fa-print',          'label' => 'Kitchen Report',    'href' => 'kitchen_report.php'],
];
?>
<aside class="sidebar" id="adminSidebar">
  <div class="brand">
    <i class="fa-solid fa-utensils"></i> <?= e(SITE_NAME) ?>
  </div>
  <nav>
    <div class="nav-label">Admin Panel</div>
    <?php foreach ($links as $key => $link): ?>
      <a href="<?= $link['href'] ?>" class="nav-link <?= $currentPage === $key ? 'active' : '' ?>">
        <i class="fa-solid <?= $link['icon'] ?>"></i> <?= $link['label'] ?>
      </a>
    <?php endforeach; ?>
  </nav>
  <div class="sidebar-foot">
    <a href="<?= BASE_URL ?>/auth/logout.php" class="nav-link">
      <i class="fa-solid fa-right-from-bracket"></i> Logout
    </a>
  </div>
</aside>
