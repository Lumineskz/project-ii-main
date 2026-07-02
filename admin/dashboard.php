<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role'] ?? '') !== 'ADMIN') {
    header('Location: ../login.php');
    exit;
}

$username = htmlspecialchars($_SESSION['full_name'] ?? 'Admin');

$totalUsers = 0;
$totalMenuItems = 0;
$totalOrders = 0;
$totalRevenue = 0.00;
$lowStockItems = 0;
$recentOrders = [];

$result = $conn->query("SELECT COUNT(*) AS count FROM users");
if ($result) {
    $row = $result->fetch_assoc();
    $totalUsers = (int) ($row['count'] ?? 0);
    $result->free();
}

$result = $conn->query("SELECT COUNT(*) AS count FROM menu_items");
if ($result) {
    $row = $result->fetch_assoc();
    $totalMenuItems = (int) ($row['count'] ?? 0);
    $result->free();
}

$result = $conn->query("SELECT COUNT(*) AS count, COALESCE(SUM(total_amount), 0) AS revenue FROM orders");
if ($result) {
    $row = $result->fetch_assoc();
    $totalOrders = (int) ($row['count'] ?? 0);
    $totalRevenue = (float) ($row['revenue'] ?? 0);
    $result->free();
}

$result = $conn->query("SELECT COUNT(*) AS count FROM menu_items WHERE available_stock <= 3 AND is_available = 1");
if ($result) {
    $row = $result->fetch_assoc();
    $lowStockItems = (int) ($row['count'] ?? 0);
    $result->free();
}

$recentOrdersQuery = "
    SELECT
        o.order_id,
        u.name AS user_name,
        o.status,
        o.total_amount,
        o.order_time
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.user_id
    ORDER BY o.order_time DESC
    LIMIT 5
";

$result = $conn->query($recentOrdersQuery);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recentOrders[] = $row;
    }
    $result->free();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://kit.fontawesome.com/5f3c0ac785.js" crossorigin="anonymous"></script>
</head>
<body class="has-admin-sidebar">
<header class="site-header">
    <div class="brand">Click2Eat Admin</div>
    <div class="header-actions">
        <span class="username"><i class="fas fa-user-shield"></i> <?= $username ?></span>
        <a href="../logout.php" class="button"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</header>

<aside class="admin-sidebar">
    <div class="sidebar-title">Admin Menu</div>
    <a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="register.php"><i class="fas fa-user-plus"></i> Add User</a>
    <a href="users.php"><i class="fas fa-users"></i> Manage Users</a>
    <a href="add_menu.php"><i class="fas fa-plus-circle"></i> Add Menu</a>
    <a href="edit_menu.php"><i class="fas fa-edit"></i> Edit Menu</a>
    <a href="manage_slots.php"><i class="fas fa-clock"></i> Timing Schedule</a>
    <a href="manage_orders.php"><i class="fas fa-tasks"></i> Manage Orders</a>
    <a href="recharge_balance.php"><i class="fas fa-wallet"></i> Recharge Balance</a>
    <a href="reports.php"><i class="fas fa-chart-line"></i> Kitchen Report</a>
</aside>

<main class="main-content content-with-sidebar">
    <div class="page-header">
        <div>
            <h1>Admin Dashboard</h1>
            <p>Overview of user activity, menu stock, and sales performance.</p>
        </div>
        <div>
            <a href="add_menu.php" class="btn-primary"><i class="fas fa-plus"></i> Add Menu Item</a>
        </div>
    </div>

    <section class="dashboard-grid">
        <div class="card data-card">
            <div class="label">Total Users</div>
            <div class="value"><?= $totalUsers ?></div>
            <div class="meta">Registered students and faculty accounts.</div>
        </div>

        <div class="card data-card">
            <div class="label">Menu Items</div>
            <div class="value"><?= $totalMenuItems ?></div>
            <div class="meta">Available and unavailable menu items.</div>
        </div>

        <div class="card data-card">
            <div class="label">Total Orders</div>
            <div class="value"><?= $totalOrders ?></div>
            <div class="meta">All orders placed through the system.</div>
        </div>

        <div class="card data-card">
            <div class="label">Sales Revenue</div>
            <div class="value">Rs. <?= number_format($totalRevenue, 2) ?></div>
            <div class="meta">Revenue from completed and pending orders.</div>
        </div>
    </section>

    <section class="card">
        <div class="section-title">Low Stock Items</div>
        <p class="meta">Items with 3 or fewer units remaining.</p>
        <div class="notice-box">
            <?= $lowStockItems ?> item<?= $lowStockItems === 1 ? '' : 's' ?> need restocking.
        </div>
    </section>

    <section class="card">
        <div class="section-title">Recent Orders</div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>User</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Placed At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($recentOrders) === 0): ?>
                        <tr><td colspan="5">No orders available yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td><?= htmlspecialchars($order['order_id']) ?></td>
                                <td><?= htmlspecialchars($order['user_name'] ?: 'Unknown') ?></td>
                                <td><span class="status-pill <?= strtolower($order['status']) ?>"><?= htmlspecialchars($order['status']) ?></span></td>
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
