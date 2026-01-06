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
    
    $stmt = $pdo->query("SHOW COLUMNS FROM core_labtest LIKE 'bill_id'");
    if ($stmt->rowCount() > 0) {
        echo "Column 'bill_id' already exists in 'core_labtest' table.\n";
    } else {
        echo "Adding bill_id column to core_labtest table...\n";
        
        $pdo->exec("ALTER TABLE core_labtest ADD COLUMN bill_id INT NULL AFTER status");
        
        echo "Adding foreign key constraint...\n";
        try {
            $pdo->exec("ALTER TABLE core_labtest ADD CONSTRAINT fk_labtest_bill FOREIGN KEY (bill_id) REFERENCES core_bill(bill_id) ON DELETE SET NULL");
        } catch (PDOException $e) {
            echo "Note: " . $e->getMessage() . "\n";
        }
    }
    
    $stmt = $pdo->query("SHOW COLUMNS FROM core_labtest LIKE 'appointment_id'");
    if ($stmt->rowCount() == 0) {
        echo "Adding appointment_id column...\n";
        $pdo->exec("ALTER TABLE core_labtest ADD COLUMN appointment_id INT NULL AFTER bill_id");
        try {
            $pdo->exec("ALTER TABLE core_labtest ADD CONSTRAINT fk_labtest_appointment FOREIGN KEY (appointment_id) REFERENCES core_appointment(appointment_id) ON DELETE SET NULL");
        } catch (PDOException $e) {
            echo "Note: " . $e->getMessage() . "\n";
        }
    }
    
    echo "✓ Migration completed successfully!\n";
    
} catch (PDOException $e) {
    die("Migration Error: " . $e->getMessage() . "\n");
}
?>

