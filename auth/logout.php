<?php
require_once __DIR__ . '/../config/config.php';
$_SESSION = [];
session_destroy();
session_start();
setFlash('info', 'You have been logged out.');
redirect('../index.php');
