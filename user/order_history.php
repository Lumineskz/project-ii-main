<?php
include '../config/db.php';
include '../includes/header.php';
// No need to session_start() here since it's already called in header.php


// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get all collected orders of the logged-in user
$orderQuery = "
SELECT o.order_id,
       o.order_time,
       o.total_amount,
       o.status,
       s.slot_name,
       s.start_time,
       s.end_time
FROM orders o
INNER JOIN order_slots s
ON o.slot_id = s.slot_id
WHERE o.user_id = '$user_id'
AND o.status='COLLECTED'
ORDER BY o.order_time DESC";

$orderResult = mysqli_query($conn, $orderQuery);
?>

<!DOCTYPE html>
<html>

<head>

    <title style:>Order History</title>

    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body>

<div class="container">

<h1>Order History</h1>

<?php

if(mysqli_num_rows($orderResult)==0)
{
    echo "<div class='empty'>
            <h3>No completed orders found.</h3>
          </div>";
}

while($order=mysqli_fetch_assoc($orderResult))
{

?>

<div class="order-card">

<div class="order-header">

<h2>Order #<?php echo $order['order_id']; ?></h2>

<span class="status">
<?php echo $order['status']; ?>
</span>

</div>

<div class="order-info">

<p>

<strong>Order Date :</strong>

<?php
echo date("d M Y h:i A",
strtotime($order['order_time']));
?>

</p>

<p>

<strong>Pickup Slot :</strong>

<?php
echo $order['slot_name'];
?>

(
<?php
echo date("h:i A",strtotime($order['start_time']));
?>

-

<?php
echo date("h:i A",strtotime($order['end_time']));
?>

)

</p>

</div>

<hr>

<?php

$order_id=$order['order_id'];

$itemQuery="SELECT
oi.quantity,
oi.price,

m.item_name,
m.image

FROM order_items oi

INNER JOIN menu_items m

ON oi.item_id=m.item_id

WHERE oi.order_id='$order_id'";

$itemResult=mysqli_query($conn,$itemQuery);

while($item=mysqli_fetch_assoc($itemResult))
{

?>

<div class="food-item">

<div class="food-image">

<img src="<?php echo $item['image']; ?>">

</div>

<div class="food-details">

<h3>

<?php
echo htmlspecialchars($item['item_name']);
?>

</h3>

<p>

Quantity :
<?php echo $item['quantity']; ?>

</p>

<p>

Price :
Rs. <?php echo number_format($item['price'],2); ?>

</p>

</div>

</div>

<?php
}
?>

<hr>

<div class="total">

Total Amount :

Rs.
<?php
echo number_format($order['total_amount'],2);
?>

</div>

</div>

<?php
}
?>

</div>

</body>

</html>