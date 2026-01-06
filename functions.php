<?php
require_once 'db.php';

function checkRole($required_role) {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /login.php');
        exit;
    }
    
    if ($_SESSION['role'] !== $required_role) {
        die("
            <div class='flex items-center justify-center h-screen bg-gray-100'>
                <div class='text-center'>
                    <h1 class='text-4xl font-bold text-red-600 mb-4'>403 Forbidden</h1>
                    <p class='text-gray-600 mb-6'>You do not have permission to access this page.</p>
                    <a href='/dashboard.php' class='px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700'>Return to Dashboard</a>
                </div>
            </div>
        ");
    }
}

function formatCurrency($amount) {
    return 'BDT ' . number_format($amount, 2);
}

function formatDate($date_string) {
    return date('M d, Y', strtotime($date_string));
}

function formatDateTime($date_string) {
    return date('M d, Y h:i A', strtotime($date_string));
}

function getStatusBadgeClass($status) {
    $status = strtolower($status);
    switch ($status) {
        case 'completed':
        case 'paid':
            return 'bg-green-100 text-green-800';
        case 'scheduled':
        case 'pending':
            return 'bg-yellow-100 text-yellow-800';
        case 'cancelled':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function getCSRFToken() {
    return generateCSRFToken();
}

function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function requireCSRFToken() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!validateCSRFToken($token)) {
            http_response_code(403);
            die("
                <div class='flex items-center justify-center h-screen bg-gray-100'>
                    <div class='text-center'>
                        <h1 class='text-4xl font-bold text-red-600 mb-4'>403 Forbidden</h1>
                        <p class='text-gray-600 mb-6'>Invalid security token. Please refresh the page and try again.</p>
                        <a href='javascript:history.back()' class='px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700'>Go Back</a>
                    </div>
                </div>
            ");
        }
    }
}
?>
