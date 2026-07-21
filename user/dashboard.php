<?php
session_start();
require_once __DIR__ . '/../config/db.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$username = htmlspecialchars($_SESSION['full_name'] ?? '');

$balance = 0.00;
$recentOrders = [];
$menuCount = 0;
$pendingOrders = 0;
$totalSpent = 0.00;

$stmt = $conn->prepare("SELECT balance FROM users WHERE user_id = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $balance = (float) $row['balance'];
    }
    $stmt->close();
}

$result = $conn->query("SELECT COUNT(*) AS count FROM menu_items WHERE is_available = 1");
if ($result) {
    $row = $result->fetch_assoc();
    $menuCount = (int) ($row['count'] ?? 0);
    $result->free();
}

$stmt = $conn->prepare("SELECT COUNT(*) AS count, COALESCE(SUM(total_amount), 0) AS total_spent FROM orders WHERE user_id = ?");
if ($stmt) {
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $pendingOrders = (int) ($row['count'] ?? 0);
        $totalSpent = (float) ($row['total_spent'] ?? 0);
    }
    $stmt->close();
}

$stmt = $conn->prepare("SELECT o.order_id, o.status, o.total_amount, o.order_time, GROUP_CONCAT(CONCAT(mi.item_name, ' x', oi.quantity) SEPARATOR ', ') AS items FROM orders o JOIN order_items oi ON o.order_id = oi.order_id JOIN menu_items mi ON oi.item_id = mi.item_id WHERE o.user_id = ? GROUP BY o.order_id ORDER BY o.order_time DESC LIMIT 5");
if ($stmt) {
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $recentOrders[] = $row;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://kit.fontawesome.com/5f3c0ac785.js" crossorigin="anonymous"></script>
</head>
<body>
<header class="site-header">
    <div class="brand">Click2Eat</div>
    <div class="header-actions">
        <a href="menu.php" class="top-link"><i class="fas fa-utensils"></i> Menu</a>
        <a href="orders.php" class="top-link"><i class="fas fa-shopping-cart"></i> Orders</a>
        <a href="order_history.php" class="top-link"><i class="fas fa-history"></i> Order History</a>
        <span class="balance"><i class="fas fa-wallet"></i> Rs. <?= number_format($balance, 2) ?></span>
        <a href="../logout.php" class="button"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</header>

<main class="main-content">
    <section class="page-header">
        <div>
            <h1>Welcome, <?= $username ?: 'User' ?></h1>
            <p>Here is your canteen summary and recent order activity.</p>
        </div>
    </section>

    <section class="dashboard-grid">
        <div class="card data-card">
            <div class="data-card-icon"><i class="fas fa-wallet"></i></div>
            <div class="label">Current Balance</div>
            <div class="value">Rs. <?= number_format($balance, 2) ?></div>
            <div class="meta">Available funds for your next order.</div>
        </div>

        <div class="card data-card">
            <div class="data-card-icon"><i class="fas fa-utensils"></i></div>
            <div class="label">Available Menu Items</div>
            <div class="value"><?= $menuCount ?></div>
            <div class="meta">Fresh and available meals ready to order.</div>
        </div>

        <div class="card data-card">
            <div class="data-card-icon"><i class="fas fa-shopping-bag"></i></div>
            <div class="label">Your Orders</div>
            <div class="value"><?= $pendingOrders ?></div>
            <div class="meta">Total orders you have placed so far.</div>
        </div>

        <div class="card data-card">
            <div class="data-card-icon"><i class="fas fa-coins"></i></div>
            <div class="label">Total Spent</div>
            <div class="value">Rs. <?= number_format($totalSpent, 2) ?></div>
            <div class="meta">Amount spent on meals.</div>
        </div>
    </section>

    <section class="card">
        <div class="section-title">Recent Orders</div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Status</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>When</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($recentOrders) === 0): ?>
                        <tr><td colspan="5">No recent orders found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td><?= htmlspecialchars($order['order_id']) ?></td>
                                <td><span class="status-pill <?= strtolower($order['status']) ?>"><?= htmlspecialchars($order['status']) ?></span></td>
                                <td><?= htmlspecialchars($order['items']) ?></td>
                                <td>Rs. <?= number_format($order['total_amount'], 2) ?></td>
                                <td><?= htmlspecialchars($order['order_time']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
</body>
</html>
