<?php
/**
 * Shared helper functions.
 */

// ---------------------------------------------------------------------
// Basic helpers
// ---------------------------------------------------------------------

function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function currentUser() {
    if (!isLoggedIn()) return null;
    return [
        'id'        => $_SESSION['user_id'],
        'role'      => $_SESSION['role'],
        'full_name' => $_SESSION['full_name'],
        'email'     => $_SESSION['email'],
        'balance'   => $_SESSION['balance'],
    ];
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect(BASE_URL . '/auth/login.php');
    }
}

function requireRole($roles) {
    requireLogin();
    if (!in_array($_SESSION['role'], (array)$roles)) {
        redirect(BASE_URL . '/index.php');
    }
}

function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function refreshSessionBalance($conn, $userId) {
    $stmt = mysqli_prepare($conn, "SELECT balance FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($res)) {
        $_SESSION['balance'] = $row['balance'];
    }
}

// ---------------------------------------------------------------------
// Dashboard stat helpers
// ---------------------------------------------------------------------

function countRows($conn, $sql) {
    $res = mysqli_query($conn, $sql);
    if (!$res) return 0;
    return mysqli_num_rows($res) ? mysqli_fetch_row($res)[0] : 0;
}

function scalarQuery($conn, $sql) {
    $res = mysqli_query($conn, $sql);
    if (!$res) return 0;
    $row = mysqli_fetch_row($res);
    return $row ? $row[0] : 0;
}

// ---------------------------------------------------------------------
// Order finalization engine
// ---------------------------------------------------------------------

/**
 * Checks every active meal schedule. If the current time is on/after a
 * schedule's order_close_time and it has not already been processed for
 * today's date, all cart reservations for that schedule + date are
 * finalized into real orders (balance deducted, stock decremented).
 * Reservations that can no longer be fulfilled (insufficient balance or
 * insufficient stock) are dropped from the cart with no charge.
 */
function finalizeDueSchedules($conn) {
    $now      = date('H:i:s');
    $today    = date('Y-m-d');

    $schedules = mysqli_query($conn, "SELECT * FROM meal_schedules WHERE is_active = 1");
    if (!$schedules) return;

    while ($schedule = mysqli_fetch_assoc($schedules)) {
        if ($now < $schedule['order_close_time']) continue;

        // Already processed for today?
        $check = mysqli_prepare($conn, "SELECT id FROM processed_schedules WHERE schedule_id = ? AND process_date = ?");
        mysqli_stmt_bind_param($check, 'is', $schedule['id'], $today);
        mysqli_stmt_execute($check);
        if (mysqli_num_rows(mysqli_stmt_get_result($check)) > 0) continue;

        processSchedule($conn, $schedule['id'], $today);
    }
}

function processSchedule($conn, $scheduleId, $orderDate) {
    // Lock in the fact that we're processing this schedule/date so a
    // concurrent request doesn't double-process it.
    $lock = mysqli_prepare($conn, "INSERT IGNORE INTO processed_schedules (schedule_id, process_date) VALUES (?, ?)");
    mysqli_stmt_bind_param($lock, 'is', $scheduleId, $orderDate);
    mysqli_stmt_execute($lock);
    if (mysqli_stmt_affected_rows($lock) === 0) return; // someone else grabbed it

    // Pull all cart rows for this schedule/date, oldest first (FIFO stock allocation)
    $stmt = mysqli_prepare($conn, "SELECT c.*, m.price, m.stock, m.name AS item_name
                                    FROM cart c
                                    JOIN menu_items m ON m.id = c.menu_item_id
                                    WHERE c.schedule_id = ? AND c.order_date = ?
                                    ORDER BY c.added_at ASC");
    mysqli_stmt_bind_param($stmt, 'is', $scheduleId, $orderDate);
    mysqli_stmt_execute($stmt);
    $rows = mysqli_stmt_get_result($stmt);

    // Group cart rows by user
    $byUser = [];
    $remainingStock = [];
    while ($row = mysqli_fetch_assoc($rows)) {
        $byUser[$row['user_id']][] = $row;
        if (!isset($remainingStock[$row['menu_item_id']])) {
            $remainingStock[$row['menu_item_id']] = (int)$row['stock'];
        }
    }

    foreach ($byUser as $userId => $items) {
        // Check balance
        $balRes = mysqli_prepare($conn, "SELECT balance FROM users WHERE id = ?");
        mysqli_stmt_bind_param($balRes, 'i', $userId);
        mysqli_stmt_execute($balRes);
        $userRow = mysqli_fetch_assoc(mysqli_stmt_get_result($balRes));
        $balance = (float)$userRow['balance'];

        // Check stock availability for every item in this user's cart first
        $fulfillable = true;
        foreach ($items as $it) {
            if ($remainingStock[$it['menu_item_id']] < $it['quantity']) {
                $fulfillable = false;
                break;
            }
        }

        $total = 0;
        foreach ($items as $it) {
            $total += $it['price'] * $it['quantity'];
        }

        if (!$fulfillable || $balance < $total) {
            // Cannot be fulfilled - drop the cart entries, no charge.
            foreach ($items as $it) {
                $del = mysqli_prepare($conn, "DELETE FROM cart WHERE id = ?");
                mysqli_stmt_bind_param($del, 'i', $it['id']);
                mysqli_stmt_execute($del);
            }
            continue;
        }

        // Reserve stock
        foreach ($items as $it) {
            $remainingStock[$it['menu_item_id']] -= $it['quantity'];
        }

        // Create the order
        $ins = mysqli_prepare($conn, "INSERT INTO orders (user_id, schedule_id, order_date, total_amount, status) VALUES (?, ?, ?, ?, 'finalized')");
        mysqli_stmt_bind_param($ins, 'iisd', $userId, $scheduleId, $orderDate, $total);
        mysqli_stmt_execute($ins);
        $orderId = mysqli_insert_id($conn);

        foreach ($items as $it) {
            $oi = mysqli_prepare($conn, "INSERT INTO order_items (order_id, menu_item_id, item_name, quantity, price_each) VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($oi, 'iisid', $orderId, $it['menu_item_id'], $it['item_name'], $it['quantity'], $it['price']);
            mysqli_stmt_execute($oi);

            $stockUpd = mysqli_prepare($conn, "UPDATE menu_items SET stock = stock - ? WHERE id = ?");
            mysqli_stmt_bind_param($stockUpd, 'ii', $it['quantity'], $it['menu_item_id']);
            mysqli_stmt_execute($stockUpd);

            $delCart = mysqli_prepare($conn, "DELETE FROM cart WHERE id = ?");
            mysqli_stmt_bind_param($delCart, 'i', $it['id']);
            mysqli_stmt_execute($delCart);
        }

        // Deduct balance + ledger entry
        $newBal = $balance - $total;
        $updBal = mysqli_prepare($conn, "UPDATE users SET balance = ? WHERE id = ?");
        mysqli_stmt_bind_param($updBal, 'di', $newBal, $userId);
        mysqli_stmt_execute($updBal);

        $desc = 'Order #' . $orderId . ' finalized';
        $txn = mysqli_prepare($conn, "INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, 'deduction', ?)");
        mysqli_stmt_bind_param($txn, 'ids', $userId, $total, $desc);
        mysqli_stmt_execute($txn);
    }
}

// ---------------------------------------------------------------------
// Cart helpers
// ---------------------------------------------------------------------

function getUpcomingSchedule($conn) {
    // Returns the next schedule (today) whose order_close_time is still in the future,
    // or null if every schedule for today has already closed.
    $now = date('H:i:s');
    $stmt = mysqli_prepare($conn, "SELECT * FROM meal_schedules WHERE is_active = 1 AND order_close_time > ? ORDER BY order_close_time ASC LIMIT 1");
    mysqli_stmt_bind_param($stmt, 's', $now);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($res);
}

function getAllOpenSchedules($conn) {
    $now = date('H:i:s');
    $stmt = mysqli_prepare($conn, "SELECT * FROM meal_schedules WHERE is_active = 1 AND order_close_time > ? ORDER BY order_close_time ASC");
    mysqli_stmt_bind_param($stmt, 's', $now);
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_get_result($stmt);
}

function cartCount($conn, $userId) {
    $stmt = mysqli_prepare($conn, "SELECT COALESCE(SUM(quantity),0) FROM cart WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    return (int) mysqli_fetch_row($res)[0];
}

function timeLeftLabel($closeTime) {
    $now = new DateTime();
    $close = DateTime::createFromFormat('H:i:s', $closeTime);
    if (!$close) return '';
    $diff = $now->diff($close);
    if ($now > $close) return 'Closed';
    if ($diff->h > 0) return $diff->h . 'h ' . $diff->i . 'm left';
    return $diff->i . 'm left';
}

function placeholderImage() {
    return 'https://placehold.co/300x200/EAF2FF/1E5AA8?text=No+Image';
}
