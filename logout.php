<?php
// logout.php
require_once 'db.php';

session_unset();
session_destroy();

// We need to start session again to set the flash message for the login page
session_start();
$_SESSION['flash'] = ['message' => 'You have been logged out successfully.', 'type' => 'success'];

header('Location: login.php');
exit;
?>
