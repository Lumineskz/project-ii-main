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
$currentPage = basename($_SERVER['PHP_SELF'] ?? 'dashboard.php');
$scriptPath = $_SERVER['PHP_SELF'] ?? '';

if (strpos($scriptPath, '/admin/') !== false) {
    $loginHref = '../login.php';
    $logoutHref = '../logout.php';
} elseif (strpos($scriptPath, '/user/') !== false) {
    $loginHref = '../login.php';
    $logoutHref = '../logout.php';
} else {
    $loginHref = './login.php';
    $logoutHref = './logout.php';
}

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

    $stmt = $conn->prepare("SELECT balance FROM users WHERE user_id = ? LIMIT 1");

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
/*
|--------------------------------------------------------------------------
| Reservation Count
|--------------------------------------------------------------------------
*/

$reservationCount = 0;

if ($userLoggedIn && !$isAdmin) {

    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(ri.quantity),0) AS total
        FROM reservation_items ri
        INNER JOIN reservations r
            ON ri.reservation_id = r.reservation_id
        WHERE r.user_id = ?
        AND r.status='RESERVED'
    ");

    if($stmt){

        $stmt->bind_param("i",$userId);
        $stmt->execute();

        $result = $stmt->get_result();

        if($row = $result->fetch_assoc()){

            $reservationCount = (int)$row['total'];

        }

        $stmt->close();

    }

}
$balanceDisplay = 'Rs. ' . number_format($balance, 2);

$isActive = function ($pageName) use ($currentPage) {
    return $currentPage === $pageName;
};
?>

<header class="site-header">

<div class="brand">
    Click2Eat
</div>

<div class="header-actions">

    <?php if (!$userLoggedIn): ?>

        <a class="button" href="<?= $loginHref ?>">
            <i class="fas fa-sign-in-alt"></i>
            Log In
        </a>

    <?php elseif ($isAdmin): ?>

        <span class="username">
            <i class="fas fa-user-shield"></i>
            <?= $username ?: 'Admin'; ?>
        </span>

        <a class="button" href="<?= $logoutHref ?>">
            <i class="fas fa-sign-out-alt"></i>
            Logout
        </a>

    <?php else: ?>

        <a href="./dashboard.php" class="top-link <?= $isActive('dashboard.php') ? 'active' : '' ?>">
            <i class="fas fa-tachometer-alt"></i>
            Dashboard
        </a>

        <a href="./order_history.php" class="top-link <?= $isActive('order_history.php') ? 'active' : '' ?>">
            <i class="fas fa-history"></i>
            Order History
        </a>

        <a href="./menu.php" class="top-link <?= $isActive('menu.php') ? 'active' : '' ?>">
            <i class="fas fa-utensils"></i>
            Menu
        </a>

        <a href="#" id="reservationToggle" class="top-link">
    <i class="fas fa-shopping-basket"></i>
    Reservations
    <span id="reservationCount">
    (<?= $reservationCount ?>)
</span>
</a>

        <span class="username top-link">
            <i class="fas fa-user"></i>
            <?= $username ?: 'Student'; ?>
        </span>

        <a href="./recharge.php" class="balance top-link">
            <i class="fas fa-wallet"></i>
            Balance: <?= $balanceDisplay; ?>
        </a>

        <a class="button" href="<?= $logoutHref ?>">
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

<a href="./dashboard.php" class="<?= $isActive('dashboard.php') ? 'active' : '' ?>">
    <i class="fas fa-tachometer-alt"></i>
    Dashboard
</a>

<a href="./register.php" class="<?= $isActive('register.php') ? 'active' : '' ?>">
    <i class="fas fa-user-plus"></i>
    Add User
</a>

<a href="./users.php" class="<?= $isActive('users.php') ? 'active' : '' ?>">
    <i class="fas fa-users"></i>
    Manage Users
</a>

<a href="./add_menu.php" class="<?= $isActive('add_menu.php') ? 'active' : '' ?>">
    <i class="fas fa-plus-circle"></i>
    Add Menu
</a>

<a href="./edit_menu.php" class="<?= $isActive('edit_menu.php') ? 'active' : '' ?>">
    <i class="fas fa-edit"></i>
    Edit Menu
</a>

<a href="./manage_slots.php" class="<?= $isActive('manage_slots.php') ? 'active' : '' ?>">
    <i class="fas fa-clock"></i>
    Timing Schedule
</a>

<a href="./manage_orders.php" class="<?= $isActive('manage_orders.php') ? 'active' : '' ?>">
    <i class="fas fa-tasks"></i>
    Manage Orders
</a>

<a href="./recharge_balance.php" class="<?= $isActive('recharge_balance.php') ? 'active' : '' ?>">
    <i class="fas fa-wallet"></i>
    Recharge Balance
</a>

<a href="./reports.php" class="<?= $isActive('reports.php') ? 'active' : '' ?>">
    <i class="fas fa-chart-line"></i>
    Kitchen Report
</a>

</aside>

<?php endif; ?>
<div id="reservationOverlay"></div>

<div id="reservationSidebar">

    <div class="reservation-header">

        <h2>Reservation Cart</h2>

        <button id="closeReservation">

            &times;

        </button>

    </div>

    <div id="reservationContent">

        Loading...

    </div>

</div>
<script>

document.addEventListener("DOMContentLoaded", function () {

    const toggle = document.getElementById("reservationToggle");
    const sidebar = document.getElementById("reservationSidebar");
    const overlay = document.getElementById("reservationOverlay");
    const close = document.getElementById("closeReservation");

    if (!toggle) return;
    function updateReservationCount(){

    fetch("../user/fetch_reservations.php")
    .then(r=>r.json())
    .then(data=>{

        let total = 0;

        if(!Array.isArray(data)){
    data = [];
}

data.forEach(item=>{

            total += parseInt(item.quantity || 0);

        });

        document.getElementById("reservationCount").innerHTML =
            "(" + total + ")";

    });

}
    toggle.onclick = function (e) {

        e.preventDefault();

        sidebar.classList.add("open");
        overlay.classList.add("show");

        fetch("../includes/reservation_loader.php")
            .then(response => response.text())
            .then(html => {
                document.getElementById("reservationContent").innerHTML = html;

                updateReservationCount();
            });
    };

    close.onclick = function () {
        sidebar.classList.remove("open");
        overlay.classList.remove("show");
    };

    overlay.onclick = function () {
        sidebar.classList.remove("open");
        overlay.classList.remove("show");
    };

});

</script>
<script src="https://kit.fontawesome.com/5f3c0ac785.js" crossorigin="anonymous"></script>
