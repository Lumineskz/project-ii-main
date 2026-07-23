<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['student', 'faculty']);
refreshSessionBalance($conn, $_SESSION['user_id']);

$currentPage = 'cart';
$pageTitle = 'My Cart';
$pageSubtitle = 'Reservations are finalized automatically when the ordering window closes';

$userId = $_SESSION['user_id'];

$sql = "SELECT c.*, m.name, m.price, m.image, ms.meal_name, ms.order_close_time
        FROM cart c
        JOIN menu_items m ON m.id = c.menu_item_id
        JOIN meal_schedules ms ON ms.id = c.schedule_id
        WHERE c.user_id = ?
        ORDER BY ms.order_close_time ASC, c.added_at ASC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);
$cartRows = mysqli_stmt_get_result($stmt);

$grouped = [];
$grandTotal = 0;
while ($row = mysqli_fetch_assoc($cartRows)) {
    $grouped[$row['meal_name']][] = $row;
    $grandTotal += $row['price'] * $row['quantity'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Cart — <?= e(SITE_NAME) ?></title>
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

      <div class="alert alert-info">
        <i class="fa-solid fa-circle-info"></i>
        These are reservations, not confirmed orders yet. When each meal's ordering window closes, your reservation is automatically finalized and the amount is deducted from your balance — there's nothing you need to check out manually.
      </div>

      <?php if (empty($grouped)): ?>
        <div class="card">
          <div class="empty-state">
            <i class="fa-solid fa-cart-shopping"></i>
            <p>Your cart is empty. Browse the menu to reserve a meal.</p>
            <a href="menu.php" class="btn btn-primary btn-sm"><i class="fa-solid fa-bowl-food"></i> Browse menu</a>
          </div>
        </div>
      <?php else: ?>
        <?php foreach ($grouped as $mealName => $rows): ?>
          <div class="card">
            <div class="card-head">
              <h2><?= e($mealName) ?></h2>
              <span class="timer-pill" data-close="<?= $rows[0]['order_close_time'] ?>"><i class="fa-regular fa-clock"></i> …</span>
            </div>
            <?php foreach ($rows as $r): ?>
              <div class="cart-row">
                <img src="<?= $r['image'] ? '../uploads/menu/' . e($r['image']) : placeholderImage() ?>" alt="">
                <div class="info">
                  <h4><?= e($r['name']) ?></h4>
                  <span>Qty <?= (int)$r['quantity'] ?> × Rs. <?= number_format($r['price'], 2) ?></span>
                </div>
                <strong>Rs. <?= number_format($r['price'] * $r['quantity'], 2) ?></strong>
                <button type="button" class="btn btn-danger btn-sm cart-remove-btn" data-cart-id="<?= $r['id'] ?>">
                  <i class="fa-solid fa-trash"></i>
                </button>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endforeach; ?>

        <div class="card" style="max-width:380px;margin-left:auto;">
          <div class="cart-summary-line"><span>Items reserved</span><span><?= cartCount($conn, $userId) ?></span></div>
          <div class="cart-summary-line total"><span>Total to be deducted</span><span id="cartTotal">Rs. <?= number_format($grandTotal, 2) ?></span></div>
          <p class="text-muted" style="font-size:.78rem;margin-top:10px;margin-bottom:0;">Current balance: Rs. <?= number_format($_SESSION['balance'], 2) ?></p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
