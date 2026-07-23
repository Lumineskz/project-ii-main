<?php
require_once __DIR__ . '/../config/config.php';

requireRole('admin');

$currentPage = 'dashboard';
$pageTitle = 'Dashboard';
$pageSubtitle = 'Overview of today — ' . date('l, F j, Y');

$today = date('Y-m-d');

$todayOrders   = scalarQuery($conn, "SELECT COUNT(*) FROM orders WHERE order_date = '$today'");
$todayRevenue  = scalarQuery($conn, "SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE order_date = '$today' AND status != 'cancelled'");
$pendingCarts  = scalarQuery($conn, "SELECT COALESCE(SUM(quantity),0) FROM cart WHERE order_date = '$today'");
$totalUsers    = scalarQuery($conn, "SELECT COUNT(*) FROM users WHERE role != 'admin'");
$lowStockCount = scalarQuery($conn, "SELECT COUNT(*) FROM menu_items WHERE stock <= 5 AND availability = 'available'");

$recentOrders = mysqli_query($conn, "SELECT o.*, u.full_name, u.role, m.meal_name
                                      FROM orders o
                                      JOIN users u ON u.id = o.user_id
                                      JOIN meal_schedules m ON m.id = o.schedule_id
                                      ORDER BY o.created_at DESC LIMIT 8");

$schedulesToday = mysqli_query($conn, "SELECT * FROM meal_schedules WHERE is_active = 1 ORDER BY order_close_time ASC");

$lowStockItems = mysqli_query($conn, "SELECT * FROM menu_items WHERE stock <= 5 AND availability = 'available' ORDER BY stock ASC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard — <?= e(SITE_NAME) ?></title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <script src="https://kit.fontawesome.com/5f3c0ac785.js" crossorigin="anonymous"></script>
</head>
<body>
<div class="app-shell">
  <?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>

  <div class="main-col">
    <?php include __DIR__ . '/../includes/topbar.php'; ?>

    <div class="page-body">
      <?php include __DIR__ . '/../includes/flash.php'; ?>

      <div class="stat-grid">
        <div class="stat-card">
          <div class="icon blue"><i class="fa-solid fa-receipt"></i></div>
          <div><div class="value"><?= (int)$todayOrders ?></div><div class="label">Orders finalized today</div></div>
        </div>
        <div class="stat-card">
          <div class="icon green"><i class="fa-solid fa-sack-dollar"></i></div>
          <div><div class="value">Rs. <?= number_format($todayRevenue, 2) ?></div><div class="label">Revenue today</div></div>
        </div>
        <div class="stat-card">
          <div class="icon amber"><i class="fa-solid fa-cart-shopping"></i></div>
          <div><div class="value"><?= (int)$pendingCarts ?></div><div class="label">Items reserved (pending)</div></div>
        </div>
        <div class="stat-card">
          <div class="icon red"><i class="fa-solid fa-triangle-exclamation"></i></div>
          <div><div class="value"><?= (int)$lowStockCount ?></div><div class="label">Items low on stock</div></div>
        </div>
      </div>

      <div class="grid-2">
        <div class="card">
          <div class="card-head">
            <div>
              <h2>Recent orders</h2>
              <p>Latest finalized reservations across all meal windows.</p>
            </div>
            <a href="manage_orders.php" class="btn btn-outline btn-sm"><i class="fa-solid fa-truck-fast"></i> View all</a>
          </div>
          <div class="table-wrap">
            <table>
              <thead><tr><th>Order</th><th>User</th><th>Meal</th><th>Amount</th><th>Status</th></tr></thead>
              <tbody>
              <?php if (mysqli_num_rows($recentOrders) === 0): ?>
                <tr><td colspan="5" class="text-center text-muted">No orders yet today.</td></tr>
              <?php endif; ?>
              <?php while ($o = mysqli_fetch_assoc($recentOrders)): ?>
                <tr>
                  <td>#<?= $o['id'] ?></td>
                  <td><?= e($o['full_name']) ?> <span class="badge badge-gray"><?= e($o['role']) ?></span></td>
                  <td><?= e($o['meal_name']) ?></td>
                  <td>Rs. <?= number_format($o['total_amount'], 2) ?></td>
                  <td>
                    <?php
                      $statusColors = ['finalized'=>'blue','preparing'=>'amber','ready'=>'green','completed'=>'gray','cancelled'=>'red'];
                      $c = $statusColors[$o['status']] ?? 'gray';
                    ?>
                    <span class="badge badge-<?= $c ?>"><?= e($o['status']) ?></span>
                  </td>
                </tr>
              <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </div>

        <div>
          <div class="card">
            <div class="card-head"><h2>Today's schedules</h2></div>
            <?php if (mysqli_num_rows($schedulesToday) === 0): ?>
              <p class="text-muted">No active timing schedules yet. <a href="manage_timings.php">Add one</a>.</p>
            <?php endif; ?>
            <?php while ($s = mysqli_fetch_assoc($schedulesToday)): ?>
              <div class="flex-between" style="padding:10px 0;border-bottom:1px solid var(--gray-100);">
                <div>
                  <strong style="display:block;font-size:.9rem;"><?= e($s['meal_name']) ?></strong>
                  <span class="text-muted" style="font-size:.78rem;"><?= date('g:i A', strtotime($s['start_time'])) ?> – <?= date('g:i A', strtotime($s['end_time'])) ?></span>
                </div>
                <span class="timer-pill" data-close="<?= $s['order_close_time'] ?>"><i class="fa-regular fa-clock"></i> …</span>
              </div>
            <?php endwhile; ?>
          </div>

          <div class="card">
            <div class="card-head"><h2>Low stock alerts</h2></div>
            <?php if (mysqli_num_rows($lowStockItems) === 0): ?>
              <p class="text-muted">All menu items are well stocked.</p>
            <?php endif; ?>
            <?php while ($item = mysqli_fetch_assoc($lowStockItems)): ?>
              <div class="flex-between" style="padding:9px 0;border-bottom:1px solid var(--gray-100);">
                <span style="font-size:.88rem;"><?= e($item['name']) ?></span>
                <span class="badge badge-red"><?= (int)$item['stock'] ?> left</span>
              </div>
            <?php endwhile; ?>
            <a href="manage_menu.php" class="btn btn-outline btn-sm btn-block" style="margin-top:14px;"><i class="fa-solid fa-bowl-food"></i> Manage menu</a>
          </div>
        </div>
      </div>

      <div class="card" style="text-align:center;background:var(--blue-50);border-style:dashed;">
        <p class="text-muted" style="margin:0;font-size:.85rem;"><i class="fa-solid fa-circle-info"></i> Total registered students &amp; faculty: <strong><?= (int)$totalUsers ?></strong></p>
      </div>
    </div>
  </div>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>
