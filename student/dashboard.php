<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['student', 'faculty']);
refreshSessionBalance($conn, $_SESSION['user_id']);

$currentPage = 'dashboard';
$pageTitle = 'Dashboard';
$pageSubtitle = 'Welcome back, ' . $_SESSION['full_name'];

$userId = $_SESSION['user_id'];

$cartItemsCount = cartCount($conn, $userId);
$cartTotalRes = mysqli_prepare($conn, "SELECT COALESCE(SUM(c.quantity * m.price),0) AS total FROM cart c JOIN menu_items m ON m.id = c.menu_item_id WHERE c.user_id = ?");
mysqli_stmt_bind_param($cartTotalRes, 'i', $userId);
mysqli_stmt_execute($cartTotalRes);
$cartTotal = mysqli_fetch_assoc(mysqli_stmt_get_result($cartTotalRes))['total'];

$ordersThisMonth = scalarQuery($conn, "SELECT COUNT(*) FROM orders WHERE user_id = $userId AND MONTH(order_date) = MONTH(CURDATE())");

$activeOrders = mysqli_prepare($conn, "SELECT o.*, m.meal_name FROM orders o JOIN meal_schedules m ON m.id = o.schedule_id WHERE o.user_id = ? AND o.status IN ('finalized','preparing','ready') ORDER BY o.created_at DESC LIMIT 5");
mysqli_stmt_bind_param($activeOrders, 'i', $userId);
mysqli_stmt_execute($activeOrders);
$activeOrdersRes = mysqli_stmt_get_result($activeOrders);

$openSchedules = getAllOpenSchedules($conn);
$openScheduleList = [];
while ($s = mysqli_fetch_assoc($openSchedules)) $openScheduleList[] = $s;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard — <?= e(SITE_NAME) ?></title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <script src="https://kit.fontawesome.com/5f3c0ac785.js" crossorigin="anonymous"></script>
</head>
<body>
<div class="app-shell">
  <?php include __DIR__ . '/../includes/user_sidebar.php'; ?>

  <div class="main-col">
    <?php include __DIR__ . '/../includes/topbar.php'; ?>

    <div class="page-body">
      <?php include __DIR__ . '/../includes/flash.php'; ?>

      <div class="stat-grid">
        <div class="stat-card">
          <div class="icon blue"><i class="fa-solid fa-wallet"></i></div>
          <div><div class="value">Rs. <?= number_format($_SESSION['balance'], 2) ?></div><div class="label">Current balance</div></div>
        </div>
        <div class="stat-card">
          <div class="icon amber"><i class="fa-solid fa-cart-shopping"></i></div>
          <div><div class="value"><?= (int)$cartItemsCount ?></div><div class="label">Items reserved (unconfirmed)</div></div>
        </div>
        <div class="stat-card">
          <div class="icon green"><i class="fa-solid fa-receipt"></i></div>
          <div><div class="value">Rs. <?= number_format($cartTotal, 2) ?></div><div class="label">Pending cart value</div></div>
        </div>
        <div class="stat-card">
          <div class="icon red"><i class="fa-solid fa-calendar-check"></i></div>
          <div><div class="value"><?= (int)$ordersThisMonth ?></div><div class="label">Orders this month</div></div>
        </div>
      </div>

      <div class="grid-2">
        <div class="card">
          <div class="card-head">
            <div><h2>Active orders</h2><p>Live status of your recent reservations.</p></div>
            <a href="orders.php" class="btn btn-outline btn-sm"><i class="fa-solid fa-receipt"></i> View all</a>
          </div>
          <?php if (mysqli_num_rows($activeOrdersRes) === 0): ?>
            <div class="empty-state">
              <i class="fa-solid fa-bowl-food"></i>
              <p>No active orders right now. Reserve a meal from the menu before the window closes.</p>
              <a href="menu.php" class="btn btn-primary btn-sm"><i class="fa-solid fa-bowl-food"></i> Browse menu</a>
            </div>
          <?php else: ?>
            <div class="table-wrap">
              <table>
                <thead><tr><th>Order</th><th>Meal</th><th>Amount</th><th>Status</th></tr></thead>
                <tbody>
                <?php while ($o = mysqli_fetch_assoc($activeOrdersRes)):
                  $statusColors = ['finalized'=>'blue','preparing'=>'amber','ready'=>'green'];
                  $c = $statusColors[$o['status']] ?? 'gray';
                ?>
                  <tr>
                    <td>#<?= $o['id'] ?></td>
                    <td><?= e($o['meal_name']) ?></td>
                    <td>Rs. <?= number_format($o['total_amount'], 2) ?></td>
                    <td><span class="badge badge-<?= $c ?>"><?= e($o['status']) ?></span></td>
                  </tr>
                <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>

        <div>
          <div class="card">
            <div class="card-head"><h2>Ordering windows open now</h2></div>
            <?php if (empty($openScheduleList)): ?>
              <p class="text-muted">No ordering windows are currently open. Check back later.</p>
            <?php else: ?>
              <?php foreach ($openScheduleList as $s): ?>
                <div class="flex-between" style="padding:10px 0;border-bottom:1px solid var(--gray-100);">
                  <div>
                    <strong style="display:block;font-size:.9rem;"><?= e($s['meal_name']) ?></strong>
                    <span class="text-muted" style="font-size:.78rem;">Closes at <?= date('g:i A', strtotime($s['order_close_time'])) ?></span>
                  </div>
                  <span class="timer-pill" data-close="<?= $s['order_close_time'] ?>"><i class="fa-regular fa-clock"></i> …</span>
                </div>
              <?php endforeach; ?>
              <a href="menu.php" class="btn btn-primary btn-sm btn-block" style="margin-top:14px;"><i class="fa-solid fa-cart-plus"></i> Reserve now</a>
            <?php endif; ?>
          </div>

          <div class="card" style="background:var(--blue-50);border-style:dashed;">
            <p style="margin:0;font-size:.85rem;color:var(--blue-900);">
              <i class="fa-solid fa-circle-info"></i> Balance runs low? <a href="recharge.php">Recharge your wallet</a> anytime.
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
