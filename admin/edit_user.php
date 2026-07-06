<?php

include("../includes/db.php");

$id = $_GET['id'];

$query = mysqli_query($conn,"SELECT * FROM users WHERE user_id='$id'");
$user = mysqli_fetch_assoc($query);

if(isset($_POST['update']))
{

$name=$_POST['name'];

$password=$_POST['password'];

if(!empty($password))
{

$hashed=password_hash($password,PASSWORD_DEFAULT);

$sql="UPDATE users
SET
name='$name',
password='$hashed'
WHERE user_id='$id'";

}
else
{

$sql="UPDATE users
SET
name='$name'
WHERE user_id='$id'";

}

mysqli_query($conn,$sql);

header("Location: users.php");
exit();

}

?>

<!DOCTYPE html>

<html>

<head>

<title>Edit User</title>

<style>

body{

font-family:Arial;
background:#f4f4f4;

}

.container{

width:400px;
margin:40px auto;
background:white;
padding:20px;

}

input{

width:100%;
padding:10px;
margin-bottom:15px;

}

button{

padding:10px 20px;
background:green;
color:white;
border:none;

}

</style>

</head>

<body>

<div class="container">

<h2>Edit User</h2>

<form method="POST">

<label>Name</label>

<input
type="text"
name="name"
value="<?= htmlspecialchars($user['name']); ?>"
required>

<label>Email</label>

<input
type="email"
value="<?= htmlspecialchars($user['email']); ?>"
readonly>

<label>Reset Password</label>

<input
type="password"
name="password"
placeholder="Leave blank if no change">

<button
type="submit"
name="update">

Update User

</button>

</form>

</div>

</body>

</html>