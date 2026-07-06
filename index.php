<?php
include("includes/header.php");
include("config/db.php");

/* check if user is logged in, if they are, redirect to their respective dashboard */
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'ADMIN') {
        header("Location: admin/dashboard.php");
        exit();
    } else {
        header("Location: user/dashboard.php");
        exit();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-TuH4dFfXx7wP8vYwB/7U7UsZMl7lH/afTn6bZ1B4vK6+5S+sZZw9f5fy8z4VR7b7Yt+P+q7gFh4z9nYH1xN6Qw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
        <link rel="stylesheet" href="./css/style.css">
        <title>Click2Eat - Home</title>
    </head>
    <body>
        <div class="home-header">
            <h1>Welcome to Click2Eat!</h1>
            <p>Your one-stop solution for delicious meals.</p>
            <a href="./login.php" class="go-to">Go to Login</a>
        </div>
        <div class="student">
            <h2>Student</h2>
            <p>Enjoy a wide variety of meals at student-friendly prices.</p>
        </div>
        <div class="faculty">
            <h2>Faculty</h2>
            <p>Experience our premium menu designed for busy professionals.</p>
        </div>
        <div class="contact-admin">
            <h2>Contact Admin</h2>
            <p>Have questions or need assistance? Contact our admin team for support.</p>
            <p ><i class="fas fa-envelope"></i> admin@gmail.com</p>
        </div>
    </body>
</html>