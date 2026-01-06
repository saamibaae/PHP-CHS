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
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'core_doctorrating'");
    if ($stmt->rowCount() > 0) {
        echo "Table 'core_doctorrating' already exists. No migration needed.\n";
        exit;
    }
    
    echo "Creating core_doctorrating table...\n";
    
    $sql = "CREATE TABLE IF NOT EXISTS core_doctorrating (
        rating_id INT AUTO_INCREMENT PRIMARY KEY,
        doctor_id INT NOT NULL,
        patient_id INT NOT NULL,
        appointment_id INT,
        rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
        comment TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (doctor_id) REFERENCES core_doctor(doctor_id) ON DELETE CASCADE,
        FOREIGN KEY (patient_id) REFERENCES core_patient(patient_id) ON DELETE CASCADE,
        FOREIGN KEY (appointment_id) REFERENCES core_appointment(appointment_id) ON DELETE SET NULL,
        UNIQUE(patient_id, appointment_id)
    )";
    
    $pdo->exec($sql);
    
    echo "✓ Table 'core_doctorrating' created successfully!\n";
    echo "Migration completed.\n";
    
} catch (PDOException $e) {
    die("Migration Error: " . $e->getMessage() . "\n");
}
?>

