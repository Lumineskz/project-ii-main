<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Determine login and role information. Accept ROLE in any case (e.g., 'ADMIN').
$userLoggedIn = !empty($_SESSION['user'])
    || !empty($_SESSION['username'])
    || !empty($_SESSION['USER'])
    || !empty($_SESSION['USERNAME'])
    || !empty($_SESSION['ROLE'])
    || !empty($_SESSION['role'])
    || !empty($_SESSION['name'])
    || !empty($_SESSION['NAME'])
    || !empty($_SESSION['is_admin']);

// Normalize role from possible session keys and treat 'ADMIN' (case-insensitive) as admin.
$role = '';
if (!empty($_SESSION['role'])) {
    $role = strtoupper($_SESSION['role']);
} elseif (!empty($_SESSION['ROLE'])) {
    $role = strtoupper($_SESSION['ROLE']);
} elseif (!empty($_SESSION['is_admin'])) {
    $role = $_SESSION['is_admin'] ? 'ADMIN' : '';
}

$isAdmin = ($role === 'ADMIN');

$username = '';
if (!empty($_SESSION['full_name'])) {
    $username = htmlspecialchars($_SESSION['full_name']);
}
?>
<html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Click2Eat</title>
        <link rel="stylesheet" type="text/css" href="../css/style.css">
        <script src="https://kit.fontawesome.com/5f3c0ac785.js" crossorigin="anonymous"></script>

    </head>

<header class="site-header">
    <div class="brand">Click2Eat</div>
    <div class="header-actions">
        <?php if (!$userLoggedIn): ?>
            <a class="button" href="./login.php"><i class="fas fa-sign-in-alt"></i> Log In</a>
        <?php elseif ($isAdmin): ?>
            <span class="username"><i class="fas fa-user-shield"></i> <?= $username ?: 'Admin' ?></span>
            <a class="button" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        <?php else: ?>
            <a href="./order-history.php" class="top-link"><i class="fas fa-history"></i> Order History</a>
            <a href="./menu.php" class="top-link"><i class="fas fa-utensils"></i> Menu</a>
            <a href="./orders.php" class="top-link"><i class="fas fa-shopping-cart"></i> Orders</a>
            <span class="username" class="top-link"><i class="fas fa-user"></i> <?= $username ?: 'Username' ?></span>
            <a class="button" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        <?php endif; ?>
    </div>
</header>

<?php if ($isAdmin): ?>
    <aside class="admin-sidebar">
        <div class="sidebar-title">Admin Menu</div>
        <a href="./dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="./users.php"><i class="fas fa-users"></i> Manage Users</a>
        <a href="./register.php"><i class="fas fa-user-plus"></i> Add User</a>
        <a href="./edit_menu.php"><i class="fas fa-edit"></i> Edit Menu</a>
        <a href="./add_menu.php"><i class="fas fa-plus-circle"></i> Add Menu</a>
        <a href="./manage_slots.php"><i class="fas fa-clock"></i> Timing Schedule</a>
        <a href="./manage_orders.php"><i class="fas fa-tasks"></i> Manage Orders</a>
        <a href="./recharge_balance.php"><i class="fas fa-wallet"></i> Recharge Balance</a>
        <a href="./reports.php"><i class="fas fa-chart-line"></i> Kitchen Report</a>
    </aside>
<?php endif; ?>
