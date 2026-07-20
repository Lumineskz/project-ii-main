<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

if(isset($_POST['recharge_balance'])){

    $user_id = $_POST['user_id'];
    $amount = $_POST['amount'];

    $stmt = mysqli_prepare($conn,
    "UPDATE users
     SET balance = balance + ?
     WHERE user_id = ?");

    mysqli_stmt_bind_param($stmt,"di",$amount,$user_id);
    mysqli_stmt_execute($stmt);

    mysqli_query($conn,
    "INSERT INTO transactions(user_id,type,amount,description)
    VALUES('$user_id','CREDIT','$amount','Balance recharged by Admin')");

    header("Location: users.php");
    exit();
}

if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role'] ?? '') !== 'ADMIN') {
    header('Location: ../login.php');
    exit;
}

$message = '';
$search = trim($_GET['search'] ?? '');
$users = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $deleteId = (int) ($_POST['delete_user'] ?? 0);
    $currentUserId = (int) ($_SESSION['user_id'] ?? 0);

    if ($deleteId > 0 && $deleteId !== $currentUserId) {
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ? AND role IN ('STUDENT', 'FACULTY')");
        if ($stmt) {
            $stmt->bind_param('i', $deleteId);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                header('Location: users.php?deleted=1');
                exit;
            }
            $message = 'Unable to delete that user.';
            $stmt->close();
        } else {
            $message = 'Unable to delete that user.';
        }
    } else {
        $message = 'You cannot delete your own admin account.';
    }
}

$sql = "SELECT user_id, name, email, role, balance FROM users WHERE role IN ('STUDENT', 'FACULTY')";
$params = [];
$types = '';

if ($search !== '') {
    $sql .= " AND (name LIKE ? OR email LIKE ?)";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm];
    $types = 'ss';
}

$sql .= " ORDER BY name ASC";

$stmt = $conn->prepare($sql);
if ($stmt) {
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="has-admin-sidebar">
<main class="main-content content-with-sidebar">
    <div class="page-header">
        <div>
            <h1>Manage Users</h1>
            <p>Maintain student and faculty accounts, balances, and account details.</p>
        </div>
        <a href="register.php" class="btn-primary"><i class="fas fa-user-plus"></i> Add New User</a>
    </div>

    <?php if (isset($_GET['updated'])): ?>
        <div class="message success">User updated successfully.</div>
    <?php endif; ?>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="message success">User deleted successfully.</div>
    <?php endif; ?>

    <?php if ($message !== ''): ?>
        <div class="message error"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="card">
        <form method="get" class="search-form" style="max-width: 100%; justify-content: flex-start;">
            <input type="search" name="search" class="search-input" placeholder="Search by name or email" value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="search-button">Search</button>
            <a href="users.php" class="clear-button" style="text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">Clear</a>
        </form>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Balance</th>
                        <th>Recharge Balance</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr><td colspan="7">No users found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $row): ?>
                            <tr>
                                <td><?= (int) $row['user_id']; ?></td>
                                <td><?= htmlspecialchars($row['name']); ?></td>
                                <td><?= htmlspecialchars($row['email']); ?></td>
                                <td><?= htmlspecialchars($row['role']); ?></td>
                                <td>Rs. <?= number_format((float) $row['balance'], 2); ?></td>
                                <td>
                                    <form method="post" class="balance-form inline-form">
                                        <input
                                            type="hidden"
                                            name="user_id"
                                            value="<?= (int)$row['user_id']; ?>">

                                        <input
                                            type="number"
                                            name="amount"
                                            min="1"
                                            placeholder="Rs."
                                            required
                                            class="balance-input">

                                        <button
                                            type="submit"
                                            name="recharge_balance"
                                            class="btn btn-success">
                                            Recharge Balance

                                        </button>

                                    </form>
                                </td>

                                <!-- Action Column -->
                                <td>

                                    <div class="table-actions">

                                        <a class="btn btn-primary"
                                            href="edit_user.php?id=<?= (int)$row['user_id']; ?>">

                                             <i class="fas fa-edit"></i> Edit

                                        </a>

                                        <a class="btn btn-success"
                                            href="recharge_balance.php?id=<?= (int)$row['user_id']; ?>">
                                             <i class="fas fa-plus-circle"></i> Recharge Balance
                                        </a>

                                        <form method="post"
                                            onsubmit="return confirm('Delete this user account?');"
                                            class="inline-form">

                                            <button
                                                type="submit"
                                                name="delete_user"
                                                value="<?= (int)$row['user_id']; ?>"
                                                class="btn btn-danger">

                                                <i class="fas fa-trash"></i> Delete

                                            </button>

                                        </form>

                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
</body>
</html>
