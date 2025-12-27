<?php
// templates/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Healthcare Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
</head>
<body class="bg-gray-50 text-gray-800 font-sans antialiased">
    <?php if (isset($_SESSION['user_id'])): ?>
    <nav class="bg-white shadow-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <a href="/dashboard.php" class="text-2xl font-bold text-blue-600 flex items-center">
                            <i class="fas fa-hospital-alt mr-2"></i> CHS BD
                        </a>
                    </div>
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <?php if ($_SESSION['role'] == 'ADMIN'): ?>
                            <a href="/admin/dashboard.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">Dashboard</a>
                            <a href="/admin/doctors.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">Doctors</a>
                        <?php elseif ($_SESSION['role'] == 'DOCTOR'): ?>
                            <a href="/doctor/dashboard.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">Dashboard</a>
                            <a href="/doctor/appointments.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">Schedule</a>
                        <?php elseif ($_SESSION['role'] == 'PATIENT'): ?>
                            <a href="/patient/dashboard.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">Dashboard</a>
                            <a href="/patient/appointments.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">My Care</a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <span class="text-sm font-medium text-gray-700 mr-4">
                            <?= htmlspecialchars($_SESSION['username']) ?> 
                            <span class="text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full ml-1"><?= $_SESSION['role'] ?></span>
                        </span>
                        <a href="/logout.php" class="relative inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            <i class="fas fa-sign-out-alt mr-2"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    <?php endif; ?>

    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8 min-h-[calc(100vh-4rem)]">
        <?php 
        if (isset($_SESSION['flash'])): 
            $msg = $_SESSION['flash'];
            unset($_SESSION['flash']);
            $bgClass = $msg['type'] == 'error' ? 'bg-red-100 text-red-700 border-red-400' : 'bg-green-100 text-green-700 border-green-400';
        ?>
            <div class="<?= $bgClass ?> border px-4 py-3 rounded relative mb-6 mx-4 sm:mx-0" role="alert">
                <span class="block sm:inline"><?= htmlspecialchars($msg['message']) ?></span>
            </div>
        <?php endif; ?>