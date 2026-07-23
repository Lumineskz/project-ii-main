<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('manage_timings.php');
}

$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $mealName = trim($_POST['meal_name'] ?? '');
    $start = $_POST['start_time'] ?? '';
    $end = $_POST['end_time'] ?? '';
    $close = $_POST['order_close_time'] ?? '';

    if ($mealName === '' || !$start || !$end || !$close) {
        setFlash('error', 'Please fill in all schedule fields.');
        redirect('manage_timings.php');
    }

    $stmt = mysqli_prepare($conn, "INSERT INTO meal_schedules (meal_name, start_time, end_time, order_close_time, is_active) VALUES (?, ?, ?, ?, 1)");
    mysqli_stmt_bind_param($stmt, 'ssss', $mealName, $start, $end, $close);
    mysqli_stmt_execute($stmt);
    setFlash('success', 'Timing schedule added.');

} elseif ($action === 'update') {
    $id = (int)($_POST['schedule_id'] ?? 0);
    $mealName = trim($_POST['meal_name'] ?? '');
    $start = $_POST['start_time'] ?? '';
    $end = $_POST['end_time'] ?? '';
    $close = $_POST['order_close_time'] ?? '';

    $stmt = mysqli_prepare($conn, "UPDATE meal_schedules SET meal_name=?, start_time=?, end_time=?, order_close_time=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'ssssi', $mealName, $start, $end, $close, $id);
    mysqli_stmt_execute($stmt);
    setFlash('success', 'Timing schedule updated.');

} elseif ($action === 'toggle') {
    $id = (int)($_POST['schedule_id'] ?? 0);
    $stmt = mysqli_prepare($conn, "UPDATE meal_schedules SET is_active = NOT is_active WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    setFlash('success', 'Schedule status toggled.');

} elseif ($action === 'delete') {
    $id = (int)($_POST['schedule_id'] ?? 0);
    $stmt = mysqli_prepare($conn, "DELETE FROM meal_schedules WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    setFlash('success', 'Schedule deleted.');
}

redirect('manage_timings.php');
