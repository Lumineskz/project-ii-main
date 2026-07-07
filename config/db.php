<?php
    $host = 'localhost';
    $db = 'canteen_system';
    $user = 'root';
    $pass = '';

    $configuredTimezone = getenv('APP_TIMEZONE');
    if ($configuredTimezone === false || $configuredTimezone === '') {
        $configuredTimezone = 'Asia/Manila';
    }
    date_default_timezone_set($configuredTimezone);

    $conn = new mysqli($host, $user, $pass, $db);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }else{
        // echo "Connected successfully";
    }

?>
