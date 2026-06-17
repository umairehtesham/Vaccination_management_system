<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


function isLoggedIn() {
    return isset($_SESSION['user_id']);
}


function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../login.php');
        exit();
    }
}


function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}


function redirectToDashboard() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
    
    switch($_SESSION['role']) {
        case 'admin':
            header('Location: admin/dashboard.php');
            break;
        case 'staff':
            header('Location: staff/dashboard.php');
            break;
        case 'citizen':
            header('Location: citizen/dashboard.php');
            break;
        default:
            header('Location: login.php');
    }
    exit();
}


function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}


function calculateAge($dob) {
    $birthDate = new DateTime($dob);
    $today = new DateTime('today');
    $age = $birthDate->diff($today)->y;
    return $age;
}


function calculateNextDoseDate($currentDate, $intervalDays) {
    if ($intervalDays <= 0) {
        return null;
    }
    $date = new DateTime($currentDate);
    $date->add(new DateInterval('P' . $intervalDays . 'D'));
    return $date->format('Y-m-d');
}


function formatDate($date) {
    if (empty($date) || $date == '0000-00-00') {
        return 'N/A';
    }
    return date('M d, Y', strtotime($date));
}


function showAlert($message, $type = 'info') {
    $alertClass = '';
    switch($type) {
        case 'success':
            $alertClass = 'alert-success';
            break;
        case 'error':
            $alertClass = 'alert-danger';
            break;
        case 'warning':
            $alertClass = 'alert-warning';
            break;
        default:
            $alertClass = 'alert-info';
    }
    
    return "<div class='alert $alertClass alert-dismissible fade show' role='alert'>
                $message
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
            </div>";
}


function generateBatchNumber($prefix = 'BATCH') {
    return $prefix . date('Ymd') . rand(1000, 9999);
}
?>