<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['student', 'faculty']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('recharge.php');
}

$userId = $_SESSION['user_id'];
$amount = (float)($_POST['amount'] ?? 0);

if ($amount <= 0) {
    setFlash('error', 'Please enter a valid amount.');
    redirect('recharge.php');
}

$stmt = mysqli_prepare($conn, "UPDATE users SET balance = balance + ? WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'di', $amount, $userId);
mysqli_stmt_execute($stmt);

$desc = 'Self recharge';
$txn = mysqli_prepare($conn, "INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, 'recharge', ?)");
mysqli_stmt_bind_param($txn, 'ids', $userId, $amount, $desc);
mysqli_stmt_execute($txn);

refreshSessionBalance($conn, $userId);

setFlash('success', 'Rs. ' . number_format($amount, 2) . ' added to your balance.');
redirect('recharge.php');
