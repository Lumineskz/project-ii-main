<?php
require_once __DIR__ . '/slot_schedule.php';

/*
|--------------------------------------------------------------------------
| Reservation Functions
|--------------------------------------------------------------------------
| Shared reservation logic for Click2Eat
|--------------------------------------------------------------------------
*/

function getOrCreateReservation($conn, $user_id)
{
    $schedule = getOrderScheduleState($conn);


    /*
    |--------------------------------------------------------------------------
    | Cannot order during closure
    |--------------------------------------------------------------------------
    */

    if ($schedule['is_closed']) {
        throw new Exception("Ordering is currently closed.");
    }


    /*
    |--------------------------------------------------------------------------
    | Get next available slot
    |--------------------------------------------------------------------------
    */

    if (!$schedule['next_slot']) {
        throw new Exception("No upcoming slot available.");
    }


    $slotId = (int)$schedule['next_slot']['slot_id'];


    $stmt = $conn->prepare("
        SELECT reservation_id
        FROM reservations
        WHERE user_id = ?
        AND slot_id = ?
        AND status = 'RESERVED'
        LIMIT 1
    ");


    $stmt->bind_param(
        "ii",
        $user_id,
        $slotId
    );


    $stmt->execute();


    $result = $stmt->get_result();


    if ($row = $result->fetch_assoc()) {

        $stmt->close();

        return $row['reservation_id'];

    }


    $stmt->close();



    /*
    |--------------------------------------------------------------------------
    | Create new reservation
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        INSERT INTO reservations
        (
            user_id,
            slot_id,
            total_amount
        )
        VALUES
        (
            ?,
            ?,
            0
        )
    ");


    $stmt->bind_param(
        "ii",
        $user_id,
        $slotId
    );


    $stmt->execute();


    $reservationId = $stmt->insert_id;


    $stmt->close();


    return $reservationId;
}
if (!function_exists('getAvailableStock')) {

    function getAvailableStock($conn,$item_id)
    {

        $stmt = $conn->prepare("
            SELECT available_stock
            FROM menu_items
            WHERE item_id = ?
            LIMIT 1
        ");

        $stmt->bind_param("i",$item_id);
        $stmt->execute();

        $result = $stmt->get_result();
        $menu = $result->fetch_assoc();

        $stock = intval($menu['available_stock']);

        $stmt->close();

        $stmt = $conn->prepare("
            SELECT IFNULL(SUM(quantity),0) AS reserved
            FROM reservation_items
            INNER JOIN reservations
                ON reservations.reservation_id = reservation_items.reservation_id
            WHERE item_id = ?
            AND reservations.status='RESERVED'
        ");

        $stmt->bind_param("i",$item_id);
        $stmt->execute();

        $reserved = $stmt->get_result()->fetch_assoc()['reserved'];

        $stmt->close();

        return max(0,$stock-$reserved);

    }

}

if (!function_exists('calculateReservationTotal')) {

    function calculateReservationTotal($conn,$reservation_id)
    {

        $stmt = $conn->prepare("
            SELECT IFNULL(SUM(subtotal),0) total
            FROM reservation_items
            WHERE reservation_id=?
        ");

        $stmt->bind_param("i",$reservation_id);
        $stmt->execute();

        $total = $stmt->get_result()->fetch_assoc()['total'];

        $stmt->close();

        $stmt = $conn->prepare("
            UPDATE reservations
            SET total_amount=?
            WHERE reservation_id=?
        ");

        $stmt->bind_param("di",$total,$reservation_id);
        $stmt->execute();

        $stmt->close();

        return $total;

    }

}

if (!function_exists('addReservationItem')) {

    function addReservationItem($conn,$user_id,$item_id,$quantity)
    {

        if($quantity<=0)
            return "Invalid quantity.";

        $available = getAvailableStock($conn,$item_id);

        if($quantity>$available)
            return "Only ".$available." item(s) available.";

        $reservation_id = getOrCreateReservation($conn,$user_id);

        $stmt = $conn->prepare("
            SELECT reservation_item_id,quantity
            FROM reservation_items
            WHERE reservation_id=?
            AND item_id=?
        ");

        $stmt->bind_param("ii",$reservation_id,$item_id);
        $stmt->execute();

        $existing = $stmt->get_result()->fetch_assoc();

        $stmt->close();

        $stmt = $conn->prepare("
            SELECT price
            FROM menu_items
            WHERE item_id=?
        ");

        $stmt->bind_param("i",$item_id);
        $stmt->execute();

        $price = $stmt->get_result()->fetch_assoc()['price'];

        $stmt->close();

        if($existing){

            $newQty = $existing['quantity'] + $quantity;

            if($newQty > ($available + $existing['quantity']))
                return "Not enough stock.";

            $subtotal = $price * $newQty;

            $stmt = $conn->prepare("
                UPDATE reservation_items
                SET quantity=?,
                    price=?,
                    subtotal=?
                WHERE reservation_item_id=?
            ");

            $stmt->bind_param(
                "iddi",
                $newQty,
                $price,
                $subtotal,
                $existing['reservation_item_id']
            );

            $stmt->execute();
            $stmt->close();

        }else{

            $subtotal = $price * $quantity;

            $stmt = $conn->prepare("
                INSERT INTO reservation_items
                (
                    reservation_id,
                    item_id,
                    quantity,
                    price,
                    subtotal
                )
                VALUES(?,?,?,?,?)
            ");

            $stmt->bind_param(
                "iiidd",
                $reservation_id,
                $item_id,
                $quantity,
                $price,
                $subtotal
            );

            $stmt->execute();

            $stmt->close();

        }

        calculateReservationTotal($conn,$reservation_id);

        return true;

    }

}

if (!function_exists('removeReservationItem')) {

    function removeReservationItem($conn,$reservation_item_id,$user_id)
    {

        $stmt = $conn->prepare("
            SELECT reservation_id
            FROM reservations
            WHERE reservation_id=
            (
                SELECT reservation_id
                FROM reservation_items
                WHERE reservation_item_id=?
            )
            AND user_id=?
        ");

        $stmt->bind_param(
            "ii",
            $reservation_item_id,
            $user_id
        );

        $stmt->execute();

        $reservation = $stmt->get_result()->fetch_assoc();

        $stmt->close();

        if(!$reservation)
            return false;

        $reservation_id = $reservation['reservation_id'];

        $stmt = $conn->prepare("
            DELETE
            FROM reservation_items
            WHERE reservation_item_id=?
        ");

        $stmt->bind_param("i",$reservation_item_id);
        $stmt->execute();
        $stmt->close();

        $total = calculateReservationTotal(
            $conn,
            $reservation_id
        );

        if($total<=0){

            $stmt = $conn->prepare("
                DELETE
                FROM reservations
                WHERE reservation_id=?
            ");

            $stmt->bind_param(
                "i",
                $reservation_id
            );

            $stmt->execute();
            $stmt->close();

        }

        return true;

    }

}

if (!function_exists('getReservationItems')) {

    function getReservationItems($conn,$user_id)
    {

        $stmt = $conn->prepare("
            SELECT
                reservation_items.*,
                menu_items.item_name,
                menu_items.image
            FROM reservation_items

            INNER JOIN reservations
            ON reservations.reservation_id=
            reservation_items.reservation_id

            INNER JOIN menu_items
            ON menu_items.item_id=
            reservation_items.item_id

            WHERE reservations.user_id=?
            AND reservations.status='RESERVED'

            ORDER BY reservation_item_id ASC
        ");

        $stmt->bind_param("i",$user_id);

        $stmt->execute();

        $items = $stmt
            ->get_result()
            ->fetch_all(MYSQLI_ASSOC);

        $stmt->close();

        return $items;

    }

}

?>