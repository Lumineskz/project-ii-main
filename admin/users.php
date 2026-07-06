<?php
include '../config/db.php';
include '../includes/header.php';
// No need to session_start() here since it's already called in header.php

$query = "SELECT * FROM users WHERE role='USER'";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Users</title>

    <style>

    table{
        width:100%;
        border-collapse:collapse;
    }

    th,td{
        border:1px solid #ddd;
        padding:10px;
        text-align:center;
    }

    th{
        background:#009879;
        color:white;
    }

    .btn{
        background:#0d6efd;
        color:white;
        padding:6px 12px;
        text-decoration:none;
        border-radius:5px;
    }

    </style>

</head>

<body>

<h2>Manage Users</h2>

<table>

<tr>
    <th>User ID</th>
    <th>Name</th>
    <th>Email</th>
    <th>Balance</th>
    <th>Action</th>
</tr>

<?php while($row=mysqli_fetch_assoc($result)){ ?>

<tr>

<td><?= $row['user_id']; ?></td>

<td><?= $row['name']; ?></td>

<td><?= $row['email']; ?></td>

<td>Rs. <?= $row['balance']; ?></td>

<td>

<a class="btn"
href="edit_user.php?id=<?= $row['user_id']; ?>">
Edit
</a>

</td>

</tr>

<?php } ?>

</table>

</body>
</html>
?>