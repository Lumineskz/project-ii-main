<?php
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

if (!isLoggedIn() || !in_array($_SESSION['role'], ['student', 'faculty'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in again.']);
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $itemId = (int)($_POST['item_id'] ?? 0);
    $scheduleId = (int)($_POST['schedule_id'] ?? 0);
    $qty = max(1, (int)($_POST['qty'] ?? 1));
    $today = date('Y-m-d');

    // Validate schedule is still open
    $schStmt = mysqli_prepare($conn, "SELECT * FROM meal_schedules WHERE id = ? AND is_active = 1");
    mysqli_stmt_bind_param($schStmt, 'i', $scheduleId);
    mysqli_stmt_execute($schStmt);
    $schedule = mysqli_fetch_assoc(mysqli_stmt_get_result($schStmt));

    if (!$schedule || date('H:i:s') >= $schedule['order_close_time']) {
        echo json_encode(['success' => false, 'message' => 'This ordering window has just closed.']);
        exit;
    }

    // Validate item
    $itemStmt = mysqli_prepare($conn, "SELECT * FROM menu_items WHERE id = ? AND availability = 'available'");
    mysqli_stmt_bind_param($itemStmt, 'i', $itemId);
    mysqli_stmt_execute($itemStmt);
    $item = mysqli_fetch_assoc(mysqli_stmt_get_result($itemStmt));

    if (!$item) {
        echo json_encode(['success' => false, 'message' => 'This item is no longer available.']);
        exit;
    }

    // Existing quantity already in cart for this item+schedule+date
    $existStmt = mysqli_prepare($conn, "SELECT id, quantity FROM cart WHERE user_id = ? AND menu_item_id = ? AND schedule_id = ? AND order_date = ?");
    mysqli_stmt_bind_param($existStmt, 'iiis', $userId, $itemId, $scheduleId, $today);
    mysqli_stmt_execute($existStmt);
    $existing = mysqli_fetch_assoc(mysqli_stmt_get_result($existStmt));
    $existingQty = $existing ? (int)$existing['quantity'] : 0;
    $newQty = $existingQty + $qty;

    if ($newQty > (int)$item['stock']) {
        echo json_encode(['success' => false, 'message' => 'Only ' . $item['stock'] . ' left in stock.']);
        exit;
    }

    // Soft balance check: cart total (including this addition) must not exceed balance
    $balStmt = mysqli_prepare($conn, "SELECT balance FROM users WHERE id = ?");
    mysqli_stmt_bind_param($balStmt, 'i', $userId);
    mysqli_stmt_execute($balStmt);
    $balance = (float) mysqli_fetch_assoc(mysqli_stmt_get_result($balStmt))['balance'];

    $cartTotalStmt = mysqli_prepare($conn, "SELECT COALESCE(SUM(c.quantity * m.price),0) AS total FROM cart c JOIN menu_items m ON m.id = c.menu_item_id WHERE c.user_id = ?");
    mysqli_stmt_bind_param($cartTotalStmt, 'i', $userId);
    mysqli_stmt_execute($cartTotalStmt);
    $currentCartTotal = (float) mysqli_fetch_assoc(mysqli_stmt_get_result($cartTotalStmt))['total'];

    $projectedTotal = $currentCartTotal + ($item['price'] * $qty);
    if ($projectedTotal > $balance) {
        echo json_encode(['success' => false, 'message' => 'Insufficient balance for this reservation. Please recharge.']);
        exit;
    }

    if ($existing) {
        $upd = mysqli_prepare($conn, "UPDATE cart SET quantity = ? WHERE id = ?");
        mysqli_stmt_bind_param($upd, 'ii', $newQty, $existing['id']);
        mysqli_stmt_execute($upd);
    } else {
        $ins = mysqli_prepare($conn, "INSERT INTO cart (user_id, menu_item_id, schedule_id, quantity, order_date) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($ins, 'iiiis', $userId, $itemId, $scheduleId, $qty, $today);
        mysqli_stmt_execute($ins);
    }

    echo json_encode([
        'success' => true,
        'message' => $item['name'] . ' added to your cart.',
        'cart_count' => cartCount($conn, $userId),
    ]);
    exit;

} elseif ($action === 'remove') {
    $cartId = (int)($_POST['cart_id'] ?? 0);

    $del = mysqli_prepare($conn, "DELETE FROM cart WHERE id = ? AND user_id = ?");
    mysqli_stmt_bind_param($del, 'ii', $cartId, $userId);
    mysqli_stmt_execute($del);

    $cartTotalStmt = mysqli_prepare($conn, "SELECT COALESCE(SUM(c.quantity * m.price),0) AS total FROM cart c JOIN menu_items m ON m.id = c.menu_item_id WHERE c.user_id = ?");
    mysqli_stmt_bind_param($cartTotalStmt, 'i', $userId);
    mysqli_stmt_execute($cartTotalStmt);
    $newTotal = (float) mysqli_fetch_assoc(mysqli_stmt_get_result($cartTotalStmt))['total'];

    echo json_encode([
        'success' => true,
        'new_total' => number_format($newTotal, 2),
        'empty' => cartCount($conn, $userId) === 0,
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action.']);
