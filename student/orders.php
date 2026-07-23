<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['student', 'faculty']);
refreshSessionBalance($conn, $_SESSION['user_id']);

$currentPage = 'orders';
$pageTitle = 'My Orders';
$pageSubtitle = 'Track the live status of your finalized reservations';

$userId = $_SESSION['user_id'];

$stmt = mysqli_prepare($conn, "SELECT o.*, m.meal_name FROM orders o JOIN meal_schedules m ON m.id = o.schedule_id WHERE o.user_id = ? ORDER BY o.created_at DESC");
mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);
$orders = mysqli_stmt_get_result($stmt);

$statusSteps = ['finalized' => 1, 'preparing' => 2, 'ready' => 3, 'completed' => 4];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Orders — <?= e(SITE_NAME) ?></title>
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

      <?php if (mysqli_num_rows($orders) === 0): ?>
        <div class="card">
          <div class="empty-state">
            <i class="fa-solid fa-receipt"></i>
            <p>You don't have any finalized orders yet.</p>
            <a href="menu.php" class="btn btn-primary btn-sm"><i class="fa-solid fa-bowl-food"></i> Browse menu</a>
          </div>
        </div>
      <?php endif; ?>

      <?php while ($o = mysqli_fetch_assoc($orders)):
        $itemsRes = mysqli_query($conn, "SELECT item_name, quantity, price_each FROM order_items WHERE order_id = " . (int)$o['id']);
        $statusColors = ['finalized'=>'blue','preparing'=>'amber','ready'=>'green','completed'=>'gray','cancelled'=>'red'];
        $c = $statusColors[$o['status']] ?? 'gray';
      ?>
        <div class="card">
          <div class="card-head">
            <div>
              <h2>Order #<?= $o['id'] ?> — <?= e($o['meal_name']) ?></h2>
              <p><?= date('D, M j, Y · g:i A', strtotime($o['created_at'])) ?></p>
            </div>
            <span class="badge badge-<?= $c ?>" style="font-size:.8rem;padding:6px 14px;"><?= e(ucfirst($o['status'])) ?></span>
          </div>

          <div class="table-wrap">
            <table>
              <thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr></thead>
              <tbody>
                <?php while ($it = mysqli_fetch_assoc($itemsRes)): ?>
                  <tr>
                    <td><?= e($it['item_name']) ?></td>
                    <td><?= (int)$it['quantity'] ?></td>
                    <td>Rs. <?= number_format($it['price_each'], 2) ?></td>
                    <td>Rs. <?= number_format($it['price_each'] * $it['quantity'], 2) ?></td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>

          <div class="flex-between" style="margin-top:14px;">
            <?php if ($o['status'] !== 'cancelled'): ?>
              <div style="display:flex;gap:8px;font-size:.78rem;color:var(--gray-600);">
                <?php foreach (['finalized'=>'Finalized','preparing'=>'Preparing','ready'=>'Ready','completed'=>'Completed'] as $key => $label): ?>
                  <span class="badge <?= $statusSteps[$o['status']] >= $statusSteps[$key] ? 'badge-blue' : 'badge-gray' ?>"><?= $label ?></span>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <span class="text-muted" style="font-size:.8rem;"><?= e($o['cancel_reason'] ?: 'This order was cancelled and refunded.') ?></span>
            <?php endif; ?>
            <strong>Total: Rs. <?= number_format($o['total_amount'], 2) ?></strong>
          </div>
        </div>
      <?php endwhile; ?>
    </div>
  </div>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
