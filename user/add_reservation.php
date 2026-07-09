<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

require_once '../config/db.php';
require_once '../includes/reservation_functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: menu.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

if ($item_id <= 0) {
    $_SESSION['reservation_error'] = "Invalid menu item.";
    header("Location: menu.php");
    exit();
}

$result = addReservationItem(
    $conn,
    $user_id,
    $item_id,
    $quantity
);

if ($result === true) {

    $_SESSION['reservation_success'] =
        "Item successfully reserved.";

} else {

    $_SESSION['reservation_error'] = $result;

}

header("Location: menu.php");
exit();

?>