<?php
// dashboard.php
require_once 'db.php';
requireLogin();

switch ($_SESSION['role']) {
    case 'ADMIN':
        header('Location: /admin/dashboard.php');
        exit;
    case 'DOCTOR':
        header('Location: /doctor/dashboard.php');
        exit;
    case 'PATIENT':
        header('Location: /patient/dashboard.php');
        exit;
    default:
        setFlash("Invalid user role.", "error");
        header('Location: login.php');
        exit;
}
?>
