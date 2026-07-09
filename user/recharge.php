<?php
include '../config/db.php';
include '../includes/header.php';
include '../includes/reservation_sidebar.php';

/*
|--------------------------------------------------------------------------
| User Authentication
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role']) !== 'STUDENT') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| Fetch Current Balance
|--------------------------------------------------------------------------
*/
$stmt = $conn->prepare("SELECT balance FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$user = $result->fetch_assoc();
$currentBalance = (float)$user['balance'];

$stmt->close();

/*
|--------------------------------------------------------------------------
| Recharge Logic
|--------------------------------------------------------------------------
*/
$success = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $amount = trim($_POST['amount']);

    if (!is_numeric($amount)) {

        $error = "Please enter a valid amount.";

    } else {

        $amount = floatval($amount);

        if ($amount <= 0) {

            $error = "Recharge amount must be greater than zero.";

        } elseif ($amount > 10000) {

            $error = "Maximum recharge amount is Rs. 10,000.";

        } else {

            $newBalance = $currentBalance + $amount;

            $conn->begin_transaction();

            try {

                /*
                ----------------------------------------
                Update User Balance
                ----------------------------------------
                */

                $update = $conn->prepare("
                    UPDATE users
                    SET balance = ?
                    WHERE user_id = ?
                ");

                $update->bind_param("di", $newBalance, $user_id);
                $update->execute();
                $update->close();

                /*
                ----------------------------------------
                Insert Transaction History
                ----------------------------------------
                */

                $description = "Self Recharge";

                $insert = $conn->prepare("
                    INSERT INTO transactions
                    (user_id, type, amount, description)
                    VALUES (?, 'CREDIT', ?, ?)
                ");

                $insert->bind_param(
                    "ids",
                    $user_id,
                    $amount,
                    $description
                );

                $insert->execute();
                $insert->close();

                $conn->commit();

                $currentBalance = $newBalance;

                $success = "Balance successfully recharged.";

            } catch (Exception $e) {

                $conn->rollback();

                $error = "Recharge failed.";

            }

        }

    }

}
?>

<!DOCTYPE html>
<html>

<head>

    <title>Recharge Balance</title>

    <link rel="stylesheet" href="../css/style.css">

</head>

<body>

<div class="recharge-container">

    <h2>
        Recharge Balance
    </h2>

    <?php if($success!=""): ?>

        <div class="success">
            <?= $success ?>
        </div>

    <?php endif; ?>

    <?php if($error!=""): ?>

        <div class="error">
            <?= $error ?>
        </div>

    <?php endif; ?>

    <div class="current-balance">

        Current Balance

        <br><br>

        <span>

            Rs. <?= number_format($currentBalance,2); ?>

        </span>

    </div>

    <form method="POST">

        <label>

            Recharge Amount (Rs.)

        </label>

        <input
            type="number"
            name="amount"
            min="1"
            max="10000"
            step="0.01"
            required
            placeholder="Enter amount">

        <button
            class="btn"
            type="submit">

            Recharge Balance

        </button>

    </form>

    <a class="back" href="./dashboard.php">

        ← Back to Dashboard

    </a>

</div>

</body>

</html>