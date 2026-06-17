<?php
session_start();
require_once 'includes/functions.php';

// Redirect to dashboard if logged in, otherwise to login
if (isLoggedIn()) {
    redirectToDashboard();
} else {
    header('Location: login.php');
    exit();
}
?>