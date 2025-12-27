<?php
// db.php
require_once 'config.php';

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    // Global PDO instance
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// Helper function to check login
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Helper to require login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}

// Helper to check role
function requireRole($role) {
    requireLogin();
    if ($_SESSION['role'] !== $role) {
        die("Access Denied: You do not have permission to view this page.");
    }
}

// Helper for flash messages
function setFlash($message, $type = 'success') {
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Password Verification (Supports Werkzeug PBKDF2)
function verifyPassword($password, $hash) {
    if (strpos($hash, 'pbkdf2:sha256') === 0) {
        // Handle Werkzeug hash: pbkdf2:sha256:iterations$salt$hash
        $parts = explode('$', $hash);
        if (count($parts) === 3) {
            $params = explode(':', $parts[0]);
            if (count($params) === 3) {
                $iterations = (int)$params[2];
                $salt = $parts[1];
                $stored_hash = $parts[2];
                
                $calculated_hash = hash_pbkdf2('sha256', $password, $salt, $iterations, 32, true);
                return hash_equals($stored_hash, bin2hex($calculated_hash));
            }
        }
    }
    // Fallback to standard PHP password_verify (Bcrypt/Argon2)
    return password_verify($password, $hash);
}
?>
