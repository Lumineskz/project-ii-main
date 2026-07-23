<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('manage_users.php');
}

$userId = (int)($_POST['user_id'] ?? 0);
$amount = (float)($_POST['amount'] ?? 0);

if ($userId <= 0 || $amount <= 0) {
    setFlash('error', 'Please enter a valid recharge amount.');
    redirect('manage_users.php');
}

$stmt = mysqli_prepare($conn, "UPDATE users SET balance = balance + ? WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'di', $amount, $userId);
mysqli_stmt_execute($stmt);

$desc = 'Balance recharged by admin';
$txn = mysqli_prepare($conn, "INSERT INTO transactions (user_id, amount, type, description, created_by) VALUES (?, ?, 'recharge', ?, ?)");
mysqli_stmt_bind_param($txn, 'idsi', $userId, $amount, $desc, $_SESSION['user_id']);
mysqli_stmt_execute($txn);

setFlash('success', 'Rs. ' . number_format($amount, 2) . ' added successfully.');
redirect('manage_users.php');
