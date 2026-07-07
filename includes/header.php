<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

/*
|--------------------------------------------------------------------------
| User Login Status
|--------------------------------------------------------------------------
*/
$userLoggedIn = isset($_SESSION['user_id']);

$role = isset($_SESSION['role'])
    ? strtoupper($_SESSION['role'])
    : '';

$isAdmin = ($role === 'ADMIN');

/*
|--------------------------------------------------------------------------
| Username
|--------------------------------------------------------------------------
*/
$username = '';

if (!empty($_SESSION['full_name'])) {
    $username = htmlspecialchars($_SESSION['full_name']);
}

/*
|--------------------------------------------------------------------------
| Balance
|--------------------------------------------------------------------------
*/
$balance = 0.00;

if ($userLoggedIn && !$isAdmin) {

    $userId = (int) $_SESSION['user_id'];

    $stmt = $conn->prepare("
        SELECT balance
        FROM users
        WHERE user_id = ?
        LIMIT 1
    ");

    if ($stmt) {

        $stmt->bind_param("i", $userId);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $balance = (float)$row['balance'];
        }

        $stmt->close();
    }
}

$balanceDisplay = 'Rs. ' . number_format($balance, 2);
?>

<header class="site-header">


<div class="brand">
    Click2Eat
</div>

<div class="header-actions">

    <?php if (!$userLoggedIn): ?>

        <a class="button" href="./login.php">
            <i class="fas fa-sign-in-alt"></i>
            Log In
        </a>

    <?php elseif ($isAdmin): ?>

        <span class="username">
            <i class="fas fa-user-shield"></i>
            <?= $username ?: 'Admin'; ?>
        </span>

        <a class="button" href="../logout.php">
            <i class="fas fa-sign-out-alt"></i>
            Logout
        </a>

    <?php else: ?>

        <a href="./dashboard.php" class="top-link">
            <i class="fas fa-tachometer-alt"></i>
            Dashboard
        </a>

        <a href="./order_history.php" class="top-link">
            <i class="fas fa-history"></i>
            Order History
        </a>

        <a href="./menu.php" class="top-link">
            <i class="fas fa-utensils"></i>
            Menu
        </a>

        <a href="./orders.php" class="top-link">
            <i class="fas fa-shopping-cart"></i>
            Orders
        </a>

        <span class="username top-link">
            <i class="fas fa-user"></i>
            <?= $username ?: 'Student'; ?>
        </span>

        <span class="balance top-link">
            <i class="fas fa-wallet"></i>
            Balance: <?= $balanceDisplay; ?>
        </span>

        <a class="button" href="../logout.php">
            <i class="fas fa-sign-out-alt"></i>
            Logout
        </a>

    <?php endif; ?>

</div>


</header>

<?php if ($isAdmin): ?>

<aside class="admin-sidebar">

<div class="sidebar-title">
    Admin Menu
</div>

<a href="./dashboard.php">
    <i class="fas fa-tachometer-alt"></i>
    Dashboard
</a>



<a href="./register.php">
    <i class="fas fa-user-plus"></i>
    Add User
</a>

<a href="./users.php">
    <i class="fas fa-users"></i>
    Manage Users
</a>

<a href="./add_menu.php">
    <i class="fas fa-plus-circle"></i>
    Add Menu
</a>

<a href="./edit_menu.php">
    <i class="fas fa-edit"></i>
    Edit Menu
</a>

<a href="./manage_slots.php">
    <i class="fas fa-clock"></i>
    Timing Schedule
</a>

<a href="./manage_orders.php">
    <i class="fas fa-tasks"></i>
    Manage Orders
</a>

<a href="./recharge_balance.php">
    <i class="fas fa-wallet"></i>
    Recharge Balance
</a>

<a href="./reports.php">
    <i class="fas fa-chart-line"></i>
    Kitchen Report
</a>

</aside>

<?php endif; ?>

<script src="https://kit.fontawesome.com/5f3c0ac785.js" crossorigin="anonymous"></script>
