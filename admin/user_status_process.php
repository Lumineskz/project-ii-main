<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('manage_users.php');
}

$userId = (int)($_POST['user_id'] ?? 0);
$newStatus = in_array($_POST['new_status'] ?? '', ['active', 'disabled']) ? $_POST['new_status'] : 'active';

$stmt = mysqli_prepare($conn, "UPDATE users SET status = ? WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'si', $newStatus, $userId);
mysqli_stmt_execute($stmt);

setFlash('success', 'User status updated.');
redirect('manage_users.php');
