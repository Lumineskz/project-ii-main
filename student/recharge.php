<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['student', 'faculty']);
refreshSessionBalance($conn, $_SESSION['user_id']);

$currentPage = 'recharge';
$pageTitle = 'Recharge Balance';
$pageSubtitle = 'Top up your canteen wallet';

$userId = $_SESSION['user_id'];
$stmt = mysqli_prepare($conn, "SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);
$transactions = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Recharge Balance — <?= e(SITE_NAME) ?></title>
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

      <div class="grid-2">
        <div class="card">
          <div class="card-head"><h2>Recent transactions</h2></div>
          <div class="table-wrap">
            <table>
              <thead><tr><th>Date</th><th>Type</th><th>Description</th><th>Amount</th></tr></thead>
              <tbody>
                <?php if (mysqli_num_rows($transactions) === 0): ?>
                  <tr><td colspan="4" class="text-center text-muted">No transactions yet.</td></tr>
                <?php endif; ?>
                <?php while ($t = mysqli_fetch_assoc($transactions)): ?>
                  <tr>
                    <td style="font-size:.82rem;"><?= date('M j, g:i A', strtotime($t['created_at'])) ?></td>
                    <td>
                      <?php if ($t['type'] === 'recharge'): ?><span class="badge badge-green">Recharge</span>
                      <?php elseif ($t['type'] === 'refund'): ?><span class="badge badge-blue">Refund</span>
                      <?php else: ?><span class="badge badge-red">Deduction</span><?php endif; ?>
                    </td>
                    <td style="font-size:.85rem;"><?= e($t['description']) ?></td>
                    <td style="font-weight:600;"><?= $t['type'] === 'deduction' ? '−' : '+' ?> Rs. <?= number_format($t['amount'], 2) ?></td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </div>

        <div class="card">
          <div class="card-head"><h2>Add balance</h2></div>
          <p class="text-muted" style="font-size:.85rem;">Current balance: <strong>Rs. <?= number_format($_SESSION['balance'], 2) ?></strong></p>
          <form action="recharge_process.php" method="POST">
            <div class="form-group">
              <label>Amount (Rs.)</label>
              <input type="number" name="amount" min="1" step="0.01" required placeholder="e.g. 500">
            </div>
            <div class="filters" style="margin-bottom:16px;">
              <?php foreach ([100, 300, 500, 1000] as $preset): ?>
                <button type="button" class="chip-filter preset-btn" data-amount="<?= $preset ?>">Rs. <?= $preset ?></button>
              <?php endforeach; ?>
            </div>
            <button type="submit" class="btn btn-success btn-block"><i class="fa-solid fa-wallet"></i> Recharge now</button>
            <p class="text-muted" style="font-size:.76rem;margin-top:10px;margin-bottom:0;">This is a simulated top-up for demo purposes — no real payment is processed.</p>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="../assets/js/main.js"></script>
<script>
  document.querySelectorAll('.preset-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.querySelector('input[name="amount"]').value = btn.getAttribute('data-amount');
    });
  });
</script>
</body>
</html>
