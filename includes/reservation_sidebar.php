<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/reservation_functions.php';

if (!isset($_SESSION['user_id'])) {
    exit('<div class="reservation-empty">Please login.</div>');
}

$user_id = (int)$_SESSION['user_id'];

$items = getReservationItems($conn, $user_id);

$total = 0;
?>

<div class="reservation-wrapper">

<?php if (empty($items)): ?>

    <div class="reservation-empty">

        <i class="fas fa-shopping-basket"></i>

        <h3>No Reserved Items</h3>

        <p>
            Reserve food from the menu before the ordering window closes.
        </p>

    </div>

<?php else: ?>

    <?php foreach ($items as $item): ?>

        <?php $total += $item['subtotal']; ?>

        <div class="reservation-card">

            <img
                src="<?= htmlspecialchars($item['image']) ?>"
                alt="<?= htmlspecialchars($item['item_name']) ?>">

            <div class="reservation-info">

                <h4>
                    <?= htmlspecialchars($item['item_name']) ?>
                </h4>

                <p>
                    Qty:
                    <strong><?= $item['quantity']; ?></strong>
                </p>

                <p>
                    Rs.
                    <?= number_format($item['price'],2); ?>
                    each
                </p>

                <p>
                    Subtotal:
                    <strong>
                        Rs.
                        <?= number_format($item['subtotal'],2); ?>
                    </strong>
                </p>

            </div>

            <form action="../user/remove_reservation.php" method="POST">

                <input
                    type="hidden"
                    name="reservation_item_id"
                    value="<?= $item['reservation_item_id']; ?>">

                <button
                    type="submit"
                    class="remove-btn">

                    <i class="fas fa-trash"></i>

                </button>

            </form>

        </div>

    <?php endforeach; ?>

    <div class="reservation-summary">

        <div class="summary-row">

            <span>Total</span>

            <strong>

                Rs.
                <?= number_format($total,2); ?>

            </strong>

        </div>

        <hr>

        <small>

            Your balance will only be deducted automatically
            when the ordering window closes.

        </small>

    </div>

<?php endif; ?>

</div>