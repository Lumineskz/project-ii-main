<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role'] ?? '') !== 'ADMIN') {
    header('Location: ../login.php');
    exit;
}

$userId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$user = null;
$message = '';

if ($userId > 0) {
    $stmt = $conn->prepare("SELECT user_id, name, email, role, balance FROM users WHERE user_id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
    }
}

if (!$user) {
    header('Location: users.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = strtoupper(trim($_POST['role'] ?? 'STUDENT'));
    $balance = (float) ($_POST['balance'] ?? 0);
    $password = trim($_POST['password'] ?? '');

    if ($name === '' || $email === '') {
        $message = 'Name and email are required.';
    } else {
        $role = in_array($role, ['STUDENT', 'FACULTY'], true) ? $role : 'STUDENT';

        if ($password !== '') {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, role = ?, balance = ?, password = ? WHERE user_id = ?");
            $stmt->bind_param('sssdsi', $name, $email, $role, $balance, $hashedPassword, $userId);
        } else {
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, role = ?, balance = ? WHERE user_id = ?");
            $stmt->bind_param('sssdi', $name, $email, $role, $balance, $userId);
        }

        if ($stmt && $stmt->execute()) {
            header('Location: users.php?updated=1');
            exit;
        }

        $message = 'Unable to update the user right now.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="has-admin-sidebar">
<main class="main-content content-with-sidebar">
    <div class="page-header">
        <div>
            <h1>Edit User</h1>
            <p>Update the user account details and balance.</p>
        </div>
        <a href="users.php" class="btn-secondary"><i class="fas fa-arrow-left"></i> Back to Users</a>
    </div>

    <?php if ($message !== ''): ?>
        <div class="message error"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="card">
        <form method="post" class="form-card" style="max-width: 720px;">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input id="name" name="name" type="text" value="<?= htmlspecialchars($user['name']); ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="<?= htmlspecialchars($user['email']); ?>" required>
            </div>

            <div class="form-group">
                <label for="role">Role</label>
                <select id="role" name="role">
                    <option value="STUDENT" <?= ($user['role'] === 'STUDENT') ? 'selected' : '' ?>>Student</option>
                    <option value="FACULTY" <?= ($user['role'] === 'FACULTY') ? 'selected' : '' ?>>Faculty</option>
                </select>
            </div>

            <div class="form-group">
                <label for="balance">Balance</label>
                <input id="balance" name="balance" type="number" step="0.01" min="0" value="<?= htmlspecialchars((string) $user['balance']); ?>">
            </div>

            <div class="form-group">
                <label for="password">New Password</label>
                <input id="password" name="password" type="password" placeholder="Leave blank to keep current password">
            </div>

            <button type="submit" name="update_user" class="btn btn-primary">Save Changes</button>
        </form>
    </div>
</main>
</body>
</html>