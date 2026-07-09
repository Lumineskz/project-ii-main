<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

/*
|--------------------------------------------------------------------------
| Finalize Reserved Orders
|--------------------------------------------------------------------------
|
| This script converts RESERVED reservations into finalized orders.
| It should only be executed when the ordering window closes.
|
*/

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {

    $conn->begin_transaction();

    /*
    |--------------------------------------------------------------------------
    | Get all active reservations
    |--------------------------------------------------------------------------
    */

    // Slot passed from auto finalizer
$slotId = (int)($_GET['slot_id'] ?? 0);

if ($slotId <= 0) {
    throw new Exception("Invalid slot ID.");
}


$reservationQuery = $conn->prepare("
    SELECT *
    FROM reservations
    WHERE status='RESERVED'
    AND slot_id=?
    ORDER BY reservation_id ASC
");


$reservationQuery->bind_param(
    "i",
    $slotId
);


$reservationQuery->execute();

    $reservations = $reservationQuery->get_result();

    while ($reservation = $reservations->fetch_assoc()) {

        $reservationId = (int)$reservation['reservation_id'];
        $userId        = (int)$reservation['user_id'];
        $slotId        = (int)$reservation['slot_id'];

        /*
        |--------------------------------------------------------------------------
        | Get all reserved items
        |--------------------------------------------------------------------------
        */

        $itemsQuery = $conn->prepare("
            SELECT
                ri.*,
                mi.item_name
            FROM reservation_items ri
            INNER JOIN menu_items mi
                ON ri.item_id = mi.item_id
            WHERE reservation_id=?
        ");

        $itemsQuery->bind_param("i", $reservationId);
        $itemsQuery->execute();

        $items = $itemsQuery->get_result();

        if ($items->num_rows == 0) {

            $cancel = $conn->prepare("
                UPDATE reservations
                SET status='FAILED'
                WHERE reservation_id=?
            ");

            $cancel->bind_param("i", $reservationId);
            $cancel->execute();

            continue;
        }

        /*
        |--------------------------------------------------------------------------
        | Calculate reservation total
        |--------------------------------------------------------------------------
        */

        $reservationItems = [];
        $grandTotal = 0;

        while ($item = $items->fetch_assoc()) {

            $reservationItems[] = $item;
            $grandTotal += $item['subtotal'];

        }

        /*
        |--------------------------------------------------------------------------
        | Check user balance
        |--------------------------------------------------------------------------
        */

        $balanceQuery = $conn->prepare("
            SELECT balance
            FROM users
            WHERE user_id=?
            LIMIT 1
        ");

        $balanceQuery->bind_param("i", $userId);
        $balanceQuery->execute();

        $user = $balanceQuery->get_result()->fetch_assoc();

        if (!$user) {

            throw new Exception("User not found.");

        }

        $balance = (float)$user['balance'];

        /*
        |--------------------------------------------------------------------------
        | Insufficient balance
        |--------------------------------------------------------------------------
        */

        if ($balance < $grandTotal) {

            /*
            | Restore reserved stock
            */

            foreach ($reservationItems as $item) {

                $restore = $conn->prepare("
                    UPDATE menu_items
                    SET available_stock = available_stock + ?
                    WHERE item_id=?
                ");

                $restore->bind_param(
                    "ii",
                    $item['quantity'],
                    $item['item_id']
                );

                $restore->execute();

            }

            /*
            | Mark failed
            */

            $failed = $conn->prepare("
                UPDATE reservations
                SET status='FAILED'
                WHERE reservation_id=?
            ");

            $failed->bind_param("i", $reservationId);
            $failed->execute();

            continue;

        }

        /*
        |--------------------------------------------------------------------------
        | Reservation passed balance check.
        | Order creation begins here.
        |--------------------------------------------------------------------------
        */

/*
|--------------------------------------------------------------------------
| Create Order
|--------------------------------------------------------------------------
*/

$orderInsert = $conn->prepare("
    INSERT INTO orders
    (
        user_id,
        slot_id,
        status,
        total_amount
    )
    VALUES
    (
        ?,
        ?,
        'PENDING',
        ?
    )
");

$slotId = (int)$reservation['slot_id'];

$orderInsert->bind_param(
    "iid",
    $userId,
    $slotId,
    $grandTotal
);

$orderInsert->execute();
$slotId = (int)$reservation['slot_id'];


$orderInsert->execute();

$orderId = $conn->insert_id;


/*
|--------------------------------------------------------------------------
| Insert Order Items
|--------------------------------------------------------------------------
*/

foreach ($reservationItems as $item) {

    $insertItem = $conn->prepare("
        INSERT INTO order_items
        (
            order_id,
            item_id,
            quantity,
            price
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?
        )
    ");

    $insertItem->bind_param(
        "iiid",
        $orderId,
        $item['item_id'],
        $item['quantity'],
        $item['price']
    );

    $insertItem->execute();

}


/*
|--------------------------------------------------------------------------
| Deduct Balance
|--------------------------------------------------------------------------
*/

$newBalance = $balance - $grandTotal;

$updateBalance = $conn->prepare("
    UPDATE users
    SET balance=?
    WHERE user_id=?
");

$updateBalance->bind_param(
    "di",
    $newBalance,
    $userId
);

$updateBalance->execute();


/*
|--------------------------------------------------------------------------
| Transaction History
|--------------------------------------------------------------------------
*/

$description =
    "Finalized Reservation #".$reservationId;

$transaction = $conn->prepare("
    INSERT INTO transactions
    (
        user_id,
        type,
        amount,
        description
    )
    VALUES
    (
        ?,
        'DEBIT',
        ?,
        ?
    )
");

$transaction->bind_param(
    "ids",
    $userId,
    $grandTotal,
    $description
);

$transaction->execute();


/*
|--------------------------------------------------------------------------
| Update Reservation
|--------------------------------------------------------------------------
*/

$finish = $conn->prepare("
    UPDATE reservations
    SET
        status='FINALIZED',
        total_amount=?
    WHERE reservation_id=?
");

$finish->bind_param(
    "di",
    $grandTotal,
    $reservationId
);

$finish->execute();

    }

    $conn->commit();

}
catch(Exception $e){

    $conn->rollback();

    die($e->getMessage());

}

?>