<?php
// config.php

// Database Settings
define('DB_HOST', 'localhost');
define('DB_NAME', 'healthcare_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// App Settings
define('SITE_NAME', 'Healthcare Management System');
define('BASE_URL', 'http://localhost:8000'); // Adjust as needed
define('DEBUG', true);

// Timezone
date_default_timezone_set('Asia/Dhaka');

// Start Session
session_start();
?>
