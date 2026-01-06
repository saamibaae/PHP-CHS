<?php
require_once 'config.php';

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}

function requireRole($role) {
    requireLogin();
    if ($_SESSION['role'] !== $role) {
        die("Access Denied: You do not have permission to view this page.");
    }
}

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

function verifyPassword($password, $hash) {
    if (strpos($hash, 'pbkdf2:sha256') === 0) {
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
    return password_verify($password, $hash);
}

function checkTableExists($tableName) {
    global $pdo;
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$tableName'");
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

function createAdmissionTableIfNeeded() {
    global $pdo;
    if (!checkTableExists('core_admission')) {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS core_admission (
                admission_id INT AUTO_INCREMENT PRIMARY KEY,
                patient_id INT NOT NULL,
                hospital_id INT NOT NULL,
                bed_number VARCHAR(50) NOT NULL,
                admission_date DATETIME DEFAULT CURRENT_TIMESTAMP,
                discharge_date DATETIME NULL,
                reason TEXT,
                status VARCHAR(20) DEFAULT 'Admitted',
                admitted_by_user_id INT,
                discharged_by_user_id INT NULL,
                FOREIGN KEY (patient_id) REFERENCES core_patient(patient_id) ON DELETE CASCADE,
                FOREIGN KEY (hospital_id) REFERENCES core_hospital(hospital_id) ON DELETE CASCADE,
                FOREIGN KEY (admitted_by_user_id) REFERENCES core_customuser(id),
                FOREIGN KEY (discharged_by_user_id) REFERENCES core_customuser(id),
                INDEX idx_hospital_bed_status (hospital_id, bed_number, status)
            )";
            $pdo->exec($sql);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    return true;
}

function checkColumnExists($tableName, $columnName) {
    global $pdo;
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM $tableName LIKE '$columnName'");
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}
?>
