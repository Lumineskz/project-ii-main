<?php
require_once __DIR__ . '/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('register.php');
}

$role      = in_array($_POST['role'] ?? '', ['student', 'faculty']) ? $_POST['role'] : 'student';
$fullName  = trim($_POST['full_name'] ?? '');
$email     = trim($_POST['email'] ?? '');
$phone     = trim($_POST['phone'] ?? '');
$password  = $_POST['password'] ?? '';
$confirm   = $_POST['confirm_password'] ?? '';

$oldInput = $_POST;
unset($oldInput['password'], $oldInput['confirm_password']);
$_SESSION['old_input'] = $oldInput;

// --- Basic validation ---
if ($fullName === '' || $email === '' || $password === '') {
    setFlash('error', 'Please fill in all the required fields.');
    redirect('register.php');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    setFlash('error', 'Please enter a valid email address.');
    redirect('register.php');
}
if (strlen($password) < 6) {
    setFlash('error', 'Password must be at least 6 characters long.');
    redirect('register.php');
}
if ($password !== $confirm) {
    setFlash('error', 'Passwords do not match.');
    redirect('register.php');
}

// --- Role-specific validation ---
if ($role === 'student') {
    $studentId  = trim($_POST['student_id'] ?? '');
    $batch      = trim($_POST['batch'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $semester   = trim($_POST['semester'] ?? '');
    if ($studentId === '' || $batch === '' || $department === '') {
        setFlash('error', 'Please fill in your Student ID, batch and department.');
        redirect('register.php');
    }
} else {
    $employeeId = trim($_POST['employee_id'] ?? '');
    $subject    = trim($_POST['subject'] ?? '');
    $facDept    = trim($_POST['fac_department'] ?? '');
    $designation = trim($_POST['designation'] ?? '');
    if ($employeeId === '' || $subject === '' || $facDept === '') {
        setFlash('error', 'Please fill in your Employee ID, subject and department.');
        redirect('register.php');
    }
}

// --- Uniqueness check ---
$check = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
mysqli_stmt_bind_param($check, 's', $email);
mysqli_stmt_execute($check);
if (mysqli_num_rows(mysqli_stmt_get_result($check)) > 0) {
    setFlash('error', 'An account with that email already exists.');
    redirect('register.php');
}

if ($role === 'student') {
    $checkId = mysqli_prepare($conn, "SELECT user_id FROM student_details WHERE student_id = ?");
    mysqli_stmt_bind_param($checkId, 's', $studentId);
    mysqli_stmt_execute($checkId);
    if (mysqli_num_rows(mysqli_stmt_get_result($checkId)) > 0) {
        setFlash('error', 'That Student ID is already registered.');
        redirect('register.php');
    }
} else {
    $checkId = mysqli_prepare($conn, "SELECT user_id FROM faculty_details WHERE employee_id = ?");
    mysqli_stmt_bind_param($checkId, 's', $employeeId);
    mysqli_stmt_execute($checkId);
    if (mysqli_num_rows(mysqli_stmt_get_result($checkId)) > 0) {
        setFlash('error', 'That Employee ID is already registered.');
        redirect('register.php');
    }
}

// --- Create the account ---
$hash = password_hash($password, PASSWORD_DEFAULT);
$ins = mysqli_prepare($conn, "INSERT INTO users (role, full_name, email, phone, password, balance) VALUES (?, ?, ?, ?, ?, 0)");
mysqli_stmt_bind_param($ins, 'sssss', $role, $fullName, $email, $phone, $hash);
mysqli_stmt_execute($ins);
$userId = mysqli_insert_id($conn);

if ($role === 'student') {
    $insDet = mysqli_prepare($conn, "INSERT INTO student_details (user_id, student_id, batch, department, semester) VALUES (?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($insDet, 'issss', $userId, $studentId, $batch, $department, $semester);
    mysqli_stmt_execute($insDet);
} else {
    $insDet = mysqli_prepare($conn, "INSERT INTO faculty_details (user_id, employee_id, subject, department, designation) VALUES (?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($insDet, 'issss', $userId, $employeeId, $subject, $facDept, $designation);
    mysqli_stmt_execute($insDet);
}

unset($_SESSION['old_input']);

// --- Auto login ---
$_SESSION['user_id']   = $userId;
$_SESSION['role']      = $role;
$_SESSION['full_name'] = $fullName;
$_SESSION['email']     = $email;
$_SESSION['balance']   = 0;

setFlash('success', 'Account created! Recharge your balance to start reserving meals.');
redirect('../student/dashboard.php');
