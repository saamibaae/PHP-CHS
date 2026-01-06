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
    
    echo "Connected to database.\n";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'core_admission'");
    if ($stmt->rowCount() > 0) {
        echo "Table 'core_admission' already exists. No migration needed.\n";
        exit;
    }
    
    echo "Creating core_admission table...\n";
    
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
    
    echo "✓ Table 'core_admission' created successfully!\n";
    echo "Migration completed.\n";
    
} catch (PDOException $e) {
    die("Migration Error: " . $e->getMessage() . "\n");
}
?>

