<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role']) !== 'ADMIN') {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id'])) {
    die("Invalid User ID.");
}

$user_id = (int)$_GET['id'];

// Get user details
$stmt = $conn->prepare("SELECT name, email, balance FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die("User not found.");
}

// Recharge balance
if (isset($_POST['recharge_balance'])) {

    $amount = (float)$_POST['amount'];

    if ($amount > 0) {

        // Update balance
        $stmt = $conn->prepare("UPDATE users SET balance = balance + ? WHERE user_id = ?");
        $stmt->bind_param("di", $amount, $user_id);
        $stmt->execute();

        // Save transaction
        $stmt = $conn->prepare("INSERT INTO transactions(user_id,type,amount,description)
                                VALUES (?, 'CREDIT', ?, 'Wallet Recharged by Admin')");
        $stmt->bind_param("id", $user_id, $amount);
        $stmt->execute();

        header("Location: users.php?recharged=1");
        exit();
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recharge Balance</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="has-admin-sidebar">
<main class="main-content content-with-sidebar">
    <div class="page-wrapper">
        <div class="page-header">
            <h1>Recharge Balance</h1>
            <p>Recharge user wallet balance from this page.</p>
        </div>
        <div class="card">
            <form method="POST">

                <div class="form-group">
                  <label>User Name</label>

                  <input
                     type="text"
                     value="<?= htmlspecialchars($user['name']); ?>"
                     readonly>
                </div>

                <div class="form-group">
                  <label>Email</label>

                  <input
                     type="text"
                     value="<?= htmlspecialchars($user['email']); ?>"
                     readonly>
                </div>

                <div class="form-group">
                  <label>Current Balance</label>

                  <input
                     type="text"
                     value="Rs. <?= number_format($user['balance'],2); ?>"
                     readonly>
                </div>

                <div class="form-group">
                  <label>Recharge Amount</label>

                  <input
                     type="number"
                     name="amount"
                     min="1"
                     step="0.01"
                     required>
                </div>

                <button
                   type="submit"
                   name="recharge_balance"
                   class="btn btn-success">

                 Recharge Balance

                </button>

            </form>
        </div>
    </div>
</main>
</body>
</html>