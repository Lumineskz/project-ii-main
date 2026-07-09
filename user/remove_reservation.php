<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/db.php';
require_once '../includes/reservation_functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: menu.php");
    exit;
}

if (!isset($_POST['reservation_item_id'])) {
    header("Location: menu.php");
    exit;
}

$reservationItemId = (int)$_POST['reservation_item_id'];
$userId = (int)$_SESSION['user_id'];

$conn->begin_transaction();

try {

    /*
    |----------------------------------------------------------
    | Find reservation item
    |----------------------------------------------------------
    */

    $stmt = $conn->prepare("
        SELECT
            ri.quantity,
            ri.item_id,
            ri.reservation_id
        FROM reservation_items ri
        JOIN reservations r
            ON ri.reservation_id = r.reservation_id
        WHERE
            ri.reservation_item_id = ?
            AND r.user_id = ?
            AND r.status='RESERVED'
        LIMIT 1
    ");

    $stmt->bind_param("ii", $reservationItemId, $userId);
    $stmt->execute();

    $result = $stmt->get_result();

    if (!$row = $result->fetch_assoc()) {
        throw new Exception("Reservation not found.");
    }

    $itemId = (int)$row['item_id'];
    $qty = (int)$row['quantity'];
    $reservationId = (int)$row['reservation_id'];

    /*
    |----------------------------------------------------------
    | Restore stock
    |----------------------------------------------------------
    */

    $stmt = $conn->prepare("
        UPDATE menu_items
        SET available_stock = available_stock + ?
        WHERE item_id=?
    ");

    $stmt->bind_param("ii", $qty, $itemId);
    $stmt->execute();

    /*
    |----------------------------------------------------------
    | Delete reservation item
    |----------------------------------------------------------
    */

    $stmt = $conn->prepare("
        DELETE FROM reservation_items
        WHERE reservation_item_id=?
    ");

    $stmt->bind_param("i", $reservationItemId);
    $stmt->execute();

    /*
    |----------------------------------------------------------
    | Delete reservation if empty
    |----------------------------------------------------------
    */

    $stmt = $conn->prepare("
        SELECT COUNT(*) total
        FROM reservation_items
        WHERE reservation_id=?
    ");

    $stmt->bind_param("i", $reservationId);
    $stmt->execute();

    $count = $stmt->get_result()->fetch_assoc()['total'];

    if ($count == 0) {

        $stmt = $conn->prepare("
            DELETE FROM reservations
            WHERE reservation_id=?
        ");

        $stmt->bind_param("i", $reservationId);
        $stmt->execute();

    }

    $conn->commit();

}
catch(Exception $e){

    $conn->rollback();

}

header("Location: menu.php");
exit;