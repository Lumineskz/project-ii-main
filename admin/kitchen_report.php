<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin');

$currentPage = 'kitchen';
$pageTitle = 'Kitchen Report';
$pageSubtitle = 'Aggregated prep quantities for kitchen staff';

$reportDate = $_GET['date'] ?? date('Y-m-d');
$scheduleId = $_GET['schedule_id'] ?? '';

$schedules = mysqli_query($conn, "SELECT * FROM meal_schedules ORDER BY start_time ASC");
$scheduleList = [];
mysqli_data_seek($schedules, 0);
while ($row = mysqli_fetch_assoc($schedules)) $scheduleList[] = $row;

$sql = "SELECT oi.item_name, SUM(oi.quantity) AS total_qty, SUM(oi.quantity * oi.price_each) AS total_value, COUNT(DISTINCT oi.order_id) AS order_count
        FROM order_items oi
        JOIN orders o ON o.id = oi.order_id
        WHERE o.order_date = ? AND o.status != 'cancelled'";
$params = [$reportDate];
$types = 's';
if ($scheduleId !== '') {
    $sql .= " AND o.schedule_id = ?";
    $params[] = $scheduleId;
    $types .= 'i';
}
$sql .= " GROUP BY oi.item_name ORDER BY total_qty DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$report = mysqli_stmt_get_result($stmt);

$grandTotalQty = 0;
$grandTotalValue = 0;
$reportRows = [];
while ($r = mysqli_fetch_assoc($report)) {
    $reportRows[] = $r;
    $grandTotalQty += $r['total_qty'];
    $grandTotalValue += $r['total_value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kitchen Report — <?= e(SITE_NAME) ?></title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <script src="https://kit.fontawesome.com/5f3c0ac785.js" crossorigin="anonymous"></script>
  <style>
    @media print {
      .sidebar, .topbar, .menu-toolbar, .no-print { display: none !important; }
      .page-body { padding: 0 !important; }
      body { background: #fff; }
    }
    
  </style>
</head>
<body>
<div class="app-shell">
  <?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>

  <div class="main-col">
    <?php include __DIR__ . '/../includes/topbar.php'; ?>

    <div class="page-body">
      <?php include __DIR__ . '/../includes/flash.php'; ?>

      <div class="card">
        <form method="GET" class="menu-toolbar no-print">
          <div class="filters">
            <input type="date" name="date" value="<?= e($reportDate) ?>" onchange="this.form.submit()">
            <select name="schedule_id" onchange="this.form.submit()">
              <option value="">All meals</option>
              <?php foreach ($scheduleList as $s): ?>
                <option value="<?= $s['id'] ?>" <?= (string)$scheduleId === (string)$s['id'] ? 'selected' : '' ?>><?= e($s['meal_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="button" class="btn btn-primary btn-sm" onclick="window.print()"><i class="fa-solid fa-print"></i> Print report</button>
        </form>

        <div style="text-align:center;margin-bottom:22px;">
          <h2 style="margin-bottom:2px;"><?= e(SITE_NAME) ?> — Kitchen Prep Report</h2>
          <p class="text-muted" style="margin:0;">
            Date: <?= date('l, F j, Y', strtotime($reportDate)) ?>
            <?php if ($scheduleId !== ''): foreach ($scheduleList as $s) if ($s['id'] == $scheduleId): ?>
              &nbsp;·&nbsp; Meal: <?= e($s['meal_name']) ?>
            <?php endif; endif; ?>
          </p>
        </div>

        <div class="table-wrap">
          <table>
            <thead><tr><th>#</th><th>Item</th><th>Total quantity to prepare</th><th>Orders</th><th>Total value</th></tr></thead>
            <tbody>
              <?php if (empty($reportRows)): ?>
                <tr><td colspan="5" class="text-center text-muted">No finalized orders for this selection yet.</td></tr>
              <?php endif; ?>
              <?php $i = 1; foreach ($reportRows as $r): ?>
                <tr>
                  <td><?= $i++ ?></td>
                  <td><strong><?= e($r['item_name']) ?></strong></td>
                  <td><span class="badge badge-blue"><?= (int)$r['total_qty'] ?> units</span></td>
                  <td><?= (int)$r['order_count'] ?></td>
                  <td>Rs. <?= number_format($r['total_value'], 2) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
            <?php if (!empty($reportRows)): ?>
            <tfoot>
              <tr>
                <td colspan="2" style="font-weight:700;">Total</td>
                <td style="font-weight:700;"><?= (int)$grandTotalQty ?> units</td>
                <td></td>
                <td style="font-weight:700;">Rs. <?= number_format($grandTotalValue, 2) ?></td>
              </tr>
            </tfoot>
            <?php endif; ?>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
