<?php
require_once __DIR__ . '/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('login.php');
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    setFlash('error', 'Please fill in both fields.');
    redirect('login.php');
}

$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($res);

if (!$user || !password_verify($password, $user['password'])) {
    setFlash('error', 'Invalid email or password.');
    redirect('login.php');
}

if ($user['status'] === 'disabled') {
    setFlash('error', 'Your account has been disabled. Please contact the canteen admin.');
    redirect('login.php');
}

$_SESSION['user_id']   = $user['id'];
$_SESSION['role']      = $user['role'];
$_SESSION['full_name'] = $user['full_name'];
$_SESSION['email']     = $user['email'];
$_SESSION['balance']   = $user['balance'];

setFlash('success', 'Welcome back, ' . $user['full_name'] . '!');

if ($user['role'] === 'admin') {
    redirect('../admin/dashboard.php');
} else {
    redirect('../student/dashboard.php');
}
