<?php
require_once 'db.php';

session_unset();
session_destroy();
session_start();
$_SESSION['flash'] = ['message' => 'You have been logged out successfully.', 'type' => 'success'];

header('Location: login.php');
exit;
?>
