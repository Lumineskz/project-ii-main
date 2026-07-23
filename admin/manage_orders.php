<?php
require_once __DIR__ . '/../config/config.php';

requireRole('admin');

$currentPage = 'orders';
$pageTitle = 'Live Orders';
$pageSubtitle = 'Track and update the status of finalized reservations';

$filterDate = $_GET['date'] ?? date('Y-m-d');
$filterStatus = $_GET['status'] ?? '';

$sql = "SELECT o.*, u.full_name, u.role, u.email, m.meal_name
        FROM orders o
        JOIN users u ON u.id = o.user_id
        JOIN meal_schedules m ON m.id = o.schedule_id
        WHERE o.order_date = ?";
$params = [$filterDate];
$types = 's';
if ($filterStatus !== '') {
    $sql .= " AND o.status = ?";
    $params[] = $filterStatus;
    $types .= 's';
}
$sql .= " ORDER BY o.created_at DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$orders = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Live Orders — <?= e(SITE_NAME) ?></title>
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

      <div class="card">
        <form method="GET" class="menu-toolbar">
          <div class="filters">
            <input type="date" name="date" value="<?= e($filterDate) ?>" onchange="this.form.submit()">
            <select name="status" onchange="this.form.submit()">
              <option value="">All statuses</option>
              <?php foreach (['finalized','preparing','ready','completed','cancelled'] as $s): ?>
                <option value="<?= $s ?>" <?= $filterStatus === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <span class="text-muted" style="font-size:.85rem;"><?= mysqli_num_rows($orders) ?> order(s) found</span>
        </form>

        <div class="table-wrap">
          <table>
            <thead>
              <tr><th>Order</th><th>User</th><th>Meal</th><th>Items</th><th>Amount</th><th>Status</th><th>Update</th></tr>
            </thead>
            <tbody>
              <?php if (mysqli_num_rows($orders) === 0): ?>
                <tr><td colspan="7" class="text-center text-muted">No orders found for this filter.</td></tr>
              <?php endif; ?>
              <?php while ($o = mysqli_fetch_assoc($orders)):
                $itemsRes = mysqli_query($conn, "SELECT item_name, quantity FROM order_items WHERE order_id = " . (int)$o['id']);
                $itemLabels = [];
                while ($it = mysqli_fetch_assoc($itemsRes)) $itemLabels[] = $it['item_name'] . ' ×' . $it['quantity'];
                $statusColors = ['finalized'=>'blue','preparing'=>'amber','ready'=>'green','completed'=>'gray','cancelled'=>'red'];
                $c = $statusColors[$o['status']] ?? 'gray';
              ?>
                <tr>
                  <td>#<?= $o['id'] ?><br><span class="text-muted" style="font-size:.72rem;"><?= date('g:i A', strtotime($o['created_at'])) ?></span></td>
                  <td><?= e($o['full_name']) ?><br><span class="badge badge-gray"><?= e($o['role']) ?></span></td>
                  <td><?= e($o['meal_name']) ?></td>
                  <td style="max-width:220px;font-size:.8rem;"><?= e(implode(', ', $itemLabels)) ?></td>
                  <td>Rs. <?= number_format($o['total_amount'], 2) ?></td>
                  <td><span class="badge badge-<?= $c ?>"><?= e($o['status']) ?></span></td>
                  <td>
                    <?php if (!in_array($o['status'], ['completed','cancelled'])): ?>
                    <form action="orders_process.php" method="POST" style="display:flex;gap:6px;">
                      <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                      <input type="hidden" name="redirect_date" value="<?= e($filterDate) ?>">
                      <select name="status" onchange="this.form.submit()">
                        <option value="finalized" <?= $o['status']==='finalized'?'selected':'' ?>>Finalized</option>
                        <option value="preparing" <?= $o['status']==='preparing'?'selected':'' ?>>Preparing</option>
                        <option value="ready" <?= $o['status']==='ready'?'selected':'' ?>>Ready</option>
                        <option value="completed" <?= $o['status']==='completed'?'selected':'' ?>>Completed</option>
                        <option value="cancelled" <?= $o['status']==='cancelled'?'selected':'' ?>>Cancelled</option>
                      </select>
                    </form>
                    <?php else: ?>
                      <span class="text-muted" style="font-size:.78rem;">—</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
