<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('manage_orders.php');
}

$action = $_POST['action'] ?? '';
$redirectDate = $_POST['redirect_date'] ?? date('Y-m-d');

if ($action === 'force_finalize') {
    finalizeDueSchedules($conn);
    setFlash('success', 'Due meal schedules have been processed and any pending reservations were finalized.');
    redirect('manage_orders.php?date=' . urlencode($redirectDate));
}

$orderId = (int)($_POST['order_id'] ?? 0);
$newStatus = $_POST['status'] ?? '';
$validStatuses = ['finalized','preparing','ready','completed','cancelled'];

if ($orderId <= 0 || !in_array($newStatus, $validStatuses)) {
    setFlash('error', 'Invalid status update.');
    redirect('manage_orders.php?date=' . urlencode($redirectDate));
}

$stmt = mysqli_prepare($conn, "SELECT * FROM orders WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $orderId);
mysqli_stmt_execute($stmt);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$order) {
    setFlash('error', 'Order not found.');
    redirect('manage_orders.php?date=' . urlencode($redirectDate));
}

if ($newStatus === 'cancelled' && $order['status'] !== 'cancelled') {
    // Refund the user's balance and restore stock for each item
    $refund = mysqli_prepare($conn, "UPDATE users SET balance = balance + ? WHERE id = ?");
    mysqli_stmt_bind_param($refund, 'di', $order['total_amount'], $order['user_id']);
    mysqli_stmt_execute($refund);

    $desc = 'Refund for cancelled order #' . $order['id'];
    $txn = mysqli_prepare($conn, "INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, 'refund', ?)");
    mysqli_stmt_bind_param($txn, 'ids', $order['user_id'], $order['total_amount'], $desc);
    mysqli_stmt_execute($txn);

    $itemsRes = mysqli_query($conn, "SELECT menu_item_id, quantity FROM order_items WHERE order_id = " . (int)$order['id']);
    while ($it = mysqli_fetch_assoc($itemsRes)) {
        $upd = mysqli_prepare($conn, "UPDATE menu_items SET stock = stock + ? WHERE id = ?");
        mysqli_stmt_bind_param($upd, 'ii', $it['quantity'], $it['menu_item_id']);
        mysqli_stmt_execute($upd);
    }
}

$upd = mysqli_prepare($conn, "UPDATE orders SET status = ? WHERE id = ?");
mysqli_stmt_bind_param($upd, 'si', $newStatus, $orderId);
mysqli_stmt_execute($upd);

setFlash('success', 'Order #' . $orderId . ' marked as ' . $newStatus . '.');
redirect('manage_orders.php?date=' . urlencode($redirectDate));
