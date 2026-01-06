<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO("mysql:host=$host;charset=$charset", $user, $pass, $options);
    echo "Connected to MySQL server.\n";

    $pdo->exec("CREATE DATABASE IF NOT EXISTS healthcare_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database 'healthcare_db' created or already exists.\n";

    $pdo->exec("USE healthcare_db");

    $queries = [
        "CREATE TABLE IF NOT EXISTS core_district (
            district_id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            division VARCHAR(100) NOT NULL
        )",

        "CREATE TABLE IF NOT EXISTS core_hospital (
            hospital_id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(200) NOT NULL,
            address TEXT NOT NULL,
            phone VARCHAR(15) NOT NULL,
            capacity INT NOT NULL,
            registration_no VARCHAR(100) UNIQUE NOT NULL,
            email VARCHAR(254) UNIQUE NOT NULL,
            emergency_services BOOLEAN DEFAULT TRUE,
            established_date DATE NOT NULL,
            website VARCHAR(200),
            district_id INT NOT NULL,
            hospital_type VARCHAR(50),
            FOREIGN KEY (district_id) REFERENCES core_district(district_id)
        )",

        "CREATE TABLE IF NOT EXISTS core_publichospital (
            hospital_id INT PRIMARY KEY,
            govt_funding DECIMAL(15, 2) NOT NULL,
            accreditation_level VARCHAR(50) NOT NULL,
            subsidies DECIMAL(15, 2) NOT NULL,
            FOREIGN KEY (hospital_id) REFERENCES core_hospital(hospital_id) ON DELETE CASCADE
        )",

        "CREATE TABLE IF NOT EXISTS core_privatehospital (
            hospital_id INT PRIMARY KEY,
            owner_name VARCHAR(200) NOT NULL,
            profit_margin DECIMAL(5, 2) NOT NULL,
            FOREIGN KEY (hospital_id) REFERENCES core_hospital(hospital_id) ON DELETE CASCADE
        )",

        "CREATE TABLE IF NOT EXISTS core_customuser (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(150) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(254)UNIQUE NOT NULL,
            first_name VARCHAR(150),
            last_name VARCHAR(150),
            is_active BOOLEAN DEFAULT TRUE,
            is_staff BOOLEAN DEFAULT FALSE,
            is_superuser BOOLEAN DEFAULT FALSE,
            date_joined DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_login DATETIME,
            role VARCHAR(10) NOT NULL,
            hospital_id INT,
            FOREIGN KEY (hospital_id) REFERENCES core_hospital(hospital_id)
        )",

        "CREATE TABLE IF NOT EXISTS core_department (
            dept_id INT AUTO_INCREMENT PRIMARY KEY,
            dept_name VARCHAR(100) NOT NULL,
            floor VARCHAR(20) NOT NULL,
            head_doctor_id INT,
            extension VARCHAR(20),
            operating_hours VARCHAR(100) NOT NULL,
            hospital_id INT NOT NULL,
            UNIQUE(dept_name, hospital_id),
            FOREIGN KEY (hospital_id) REFERENCES core_hospital(hospital_id) ON DELETE CASCADE
        )",

        "CREATE TABLE IF NOT EXISTS core_doctor (
            doctor_id INT AUTO_INCREMENT PRIMARY KEY,
            license_no VARCHAR(100) UNIQUE NOT NULL,
            full_name VARCHAR(200) NOT NULL,
            specialization VARCHAR(200) NOT NULL,
            phone VARCHAR(15) NOT NULL,
            email VARCHAR(254) UNIQUE NOT NULL,
            experience_yrs INT NOT NULL,
            gender VARCHAR(1) NOT NULL,
            shift_timing VARCHAR(100) NOT NULL,
            join_date DATE NOT NULL,
            hospital_id INT NOT NULL,
            dept_id INT,
            user_id INT UNIQUE,
            FOREIGN KEY (hospital_id) REFERENCES core_hospital(hospital_id) ON DELETE CASCADE,
            FOREIGN KEY (dept_id) REFERENCES core_department(dept_id),
            FOREIGN KEY (user_id) REFERENCES core_customuser(id)
        )",

        "CREATE TABLE IF NOT EXISTS core_qualification (
            qualification_id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(20) UNIQUE NOT NULL,
            degree_name VARCHAR(200) NOT NULL
        )",

        "CREATE TABLE IF NOT EXISTS core_doctorqualification (
            doctor_qualification_id INT AUTO_INCREMENT PRIMARY KEY,
            doctor_id INT NOT NULL,
            qualification_id INT NOT NULL,
            year_obtained INT NOT NULL,
            institution_name VARCHAR(200) NOT NULL,
            UNIQUE(doctor_id, qualification_id),
            FOREIGN KEY (doctor_id) REFERENCES core_doctor(doctor_id) ON DELETE CASCADE,
            FOREIGN KEY (qualification_id) REFERENCES core_qualification(qualification_id) ON DELETE CASCADE
        )",

        "CREATE TABLE IF NOT EXISTS core_lab (
            lab_id INT AUTO_INCREMENT PRIMARY KEY,
            lab_name VARCHAR(200) NOT NULL,
            location VARCHAR(200) NOT NULL,
            phone VARCHAR(15) NOT NULL,
            hospital_id INT NOT NULL,
            FOREIGN KEY (hospital_id) REFERENCES core_hospital(hospital_id) ON DELETE CASCADE
        )",

        "CREATE TABLE IF NOT EXISTS core_pharmacy (
            pharmacy_id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(200) NOT NULL,
            location VARCHAR(200) NOT NULL,
            employee_count INT NOT NULL,
            hospital_id INT NOT NULL,
            FOREIGN KEY (hospital_id) REFERENCES core_hospital(hospital_id) ON DELETE CASCADE
        )",

        "CREATE TABLE IF NOT EXISTS core_manufacturer (
            manufacturer_id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(200) NOT NULL,
            phone VARCHAR(15) NOT NULL,
            address TEXT NOT NULL,
            license_no VARCHAR(100) UNIQUE NOT NULL
        )",

        "CREATE TABLE IF NOT EXISTS core_medicine (
            medicine_id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(200) NOT NULL,
            type VARCHAR(100) NOT NULL,
            dosage_info TEXT NOT NULL,
            side_effects TEXT,
            manufacturer_id INT NOT NULL,
            FOREIGN KEY (manufacturer_id) REFERENCES core_manufacturer(manufacturer_id) ON DELETE CASCADE
        )",

        "CREATE TABLE IF NOT EXISTS core_pharmacymedicine (
            pharmacy_medicine_id INT AUTO_INCREMENT PRIMARY KEY,
            pharmacy_id INT NOT NULL,
            medicine_id INT NOT NULL,
            stock_quantity INT NOT NULL,
            unit_price DECIMAL(10, 2) NOT NULL,
            expiry_date DATE NOT NULL,
            batch_number VARCHAR(100) NOT NULL,
            last_restocked DATE,
            UNIQUE(pharmacy_id, medicine_id, batch_number),
            FOREIGN KEY (pharmacy_id) REFERENCES core_pharmacy(pharmacy_id) ON DELETE CASCADE,
            FOREIGN KEY (medicine_id) REFERENCES core_medicine(medicine_id) ON DELETE CASCADE
        )",

        "CREATE TABLE IF NOT EXISTS core_patient (
            patient_id INT AUTO_INCREMENT PRIMARY KEY,
            national_id VARCHAR(50) UNIQUE NOT NULL,
            full_name VARCHAR(200) NOT NULL,
            date_of_birth DATE NOT NULL,
            gender VARCHAR(1) NOT NULL,
            phone VARCHAR(15) NOT NULL,
            email VARCHAR(254) UNIQUE NOT NULL,
            address TEXT NOT NULL,
            blood_type VARCHAR(3) NOT NULL,
            occupation VARCHAR(100),
            date_of_death DATE,
            marital_status VARCHAR(20) NOT NULL,
            birth_place VARCHAR(200) NOT NULL,
            father_name VARCHAR(200) NOT NULL,
            mother_name VARCHAR(200) NOT NULL,
            user_id INT UNIQUE,
            FOREIGN KEY (user_id) REFERENCES core_customuser(id)
        )",

        "CREATE TABLE IF NOT EXISTS core_patientemergencycontact (
            contact_id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            contact_name VARCHAR(200) NOT NULL,
            contact_phone VARCHAR(15) NOT NULL,
            relationship VARCHAR(100) NOT NULL,
            is_primary BOOLEAN DEFAULT FALSE,
            FOREIGN KEY (patient_id) REFERENCES core_patient(patient_id) ON DELETE CASCADE
        )",

        "CREATE TABLE IF NOT EXISTS core_servicetype (
            service_type_id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT
        )",

        "CREATE TABLE IF NOT EXISTS core_appointment (
            appointment_id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            doctor_id INT NOT NULL,
            status VARCHAR(20) DEFAULT 'Scheduled',
            reason_for_visit TEXT NOT NULL,
            diagnosis TEXT,
            follow_up_date DATE,
            symptoms TEXT NOT NULL,
            visit_type VARCHAR(20) NOT NULL,
            date_and_time DATETIME NOT NULL,
            FOREIGN KEY (patient_id) REFERENCES core_patient(patient_id) ON DELETE CASCADE,
            FOREIGN KEY (doctor_id) REFERENCES core_doctor(doctor_id) ON DELETE CASCADE
        )",

        "CREATE TABLE IF NOT EXISTS core_labtest (
            test_id INT AUTO_INCREMENT PRIMARY KEY,
            lab_id INT NULL,
            patient_id INT NOT NULL,
            test_type TEXT NOT NULL,
            result TEXT,
            ordered_by_id INT NOT NULL,
            remarks TEXT,
            test_cost DECIMAL(10, 2) DEFAULT 0.00,
            date_and_time DATETIME DEFAULT CURRENT_TIMESTAMP,
            status VARCHAR(20) DEFAULT 'Ordered',
            bill_id INT NULL,
            appointment_id INT NULL,
            FOREIGN KEY (lab_id) REFERENCES core_lab(lab_id) ON DELETE SET NULL,
            FOREIGN KEY (patient_id) REFERENCES core_patient(patient_id) ON DELETE CASCADE,
            FOREIGN KEY (ordered_by_id) REFERENCES core_doctor(doctor_id),
            FOREIGN KEY (bill_id) REFERENCES core_bill(bill_id) ON DELETE SET NULL,
            FOREIGN KEY (appointment_id) REFERENCES core_appointment(appointment_id) ON DELETE SET NULL
        )",

        "CREATE TABLE IF NOT EXISTS core_prescription (
            prescription_id INT AUTO_INCREMENT PRIMARY KEY,
            appointment_id INT NOT NULL,
            valid_until DATE NOT NULL,
            refill_count INT DEFAULT 0,
            notes TEXT,
            FOREIGN KEY (appointment_id) REFERENCES core_appointment(appointment_id) ON DELETE CASCADE
        )",

        "CREATE TABLE IF NOT EXISTS core_prescriptionitem (
            item_id INT AUTO_INCREMENT PRIMARY KEY,
            prescription_id INT NOT NULL,
            medicine_id INT NOT NULL,
            dosage VARCHAR(100) NOT NULL,
            frequency VARCHAR(100) NOT NULL,
            duration VARCHAR(100) NOT NULL,
            quantity INT NOT NULL,
            before_after_meal VARCHAR(20) NOT NULL,
            instructions TEXT,
            FOREIGN KEY (prescription_id) REFERENCES core_prescription(prescription_id) ON DELETE CASCADE,
            FOREIGN KEY (medicine_id) REFERENCES core_medicine(medicine_id)
        )",

        "CREATE TABLE IF NOT EXISTS core_bill (
            bill_id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            service_type_id INT NOT NULL,
            bill_date DATE DEFAULT (CURRENT_DATE),
            total_amount DECIMAL(10, 2) NOT NULL,
            status VARCHAR(20) DEFAULT 'Pending',
            insurance_covered DECIMAL(10, 2) DEFAULT 0.00,
            discount DECIMAL(10, 2) DEFAULT 0.00,
            tax DECIMAL(10, 2) DEFAULT 0.00,
            due_date DATE NOT NULL,
            transaction_id VARCHAR(100),
            FOREIGN KEY (patient_id) REFERENCES core_patient(patient_id) ON DELETE CASCADE,
            FOREIGN KEY (service_type_id) REFERENCES core_servicetype(service_type_id)
        )",

        "CREATE TABLE IF NOT EXISTS core_pharmacybill (
            pharmacy_bill_id INT AUTO_INCREMENT PRIMARY KEY,
            pharmacy_id INT NOT NULL,
            bill_id INT UNIQUE NOT NULL,
            purchase_date DATE DEFAULT (CURRENT_DATE),
            prescription_id INT,
            FOREIGN KEY (pharmacy_id) REFERENCES core_pharmacy(pharmacy_id),
            FOREIGN KEY (bill_id) REFERENCES core_bill(bill_id) ON DELETE CASCADE,
            FOREIGN KEY (prescription_id) REFERENCES core_prescription(prescription_id)
        )",

        "CREATE TABLE IF NOT EXISTS core_admission (
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
        )",

        "CREATE TABLE IF NOT EXISTS core_doctorrating (
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
        )"
    ];

    foreach ($queries as $sql) {
        $pdo->exec($sql);
    }
    echo "All tables created successfully.\n";

    echo "Running migrations...\n";
    
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM core_labtest LIKE 'bill_id'");
        if ($stmt->rowCount() == 0) {
            echo "Adding bill_id column to core_labtest table...\n";
            $pdo->exec("ALTER TABLE core_labtest ADD COLUMN bill_id INT NULL AFTER status");
            $fk_check = $pdo->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE 
                                     WHERE TABLE_SCHEMA = DATABASE() 
                                     AND TABLE_NAME = 'core_labtest' 
                                     AND COLUMN_NAME = 'bill_id' 
                                     AND REFERENCED_TABLE_NAME = 'core_bill'");
            if ($fk_check->rowCount() == 0) {
                try {
                    $pdo->exec("ALTER TABLE core_labtest ADD CONSTRAINT fk_labtest_bill FOREIGN KEY (bill_id) REFERENCES core_bill(bill_id) ON DELETE SET NULL");
                } catch (PDOException $e) {
                    echo "Note: " . $e->getMessage() . "\n";
                }
            }
        }
    } catch (PDOException $e) {
        echo "Note: " . $e->getMessage() . "\n";
    }
    
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM core_labtest LIKE 'appointment_id'");
        if ($stmt->rowCount() == 0) {
            echo "Adding appointment_id column to core_labtest table...\n";
            $pdo->exec("ALTER TABLE core_labtest ADD COLUMN appointment_id INT NULL AFTER bill_id");
            $fk_check = $pdo->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE 
                                     WHERE TABLE_SCHEMA = DATABASE() 
                                     AND TABLE_NAME = 'core_labtest' 
                                     AND COLUMN_NAME = 'appointment_id' 
                                     AND REFERENCED_TABLE_NAME = 'core_appointment'");
            if ($fk_check->rowCount() == 0) {
                try {
                    $pdo->exec("ALTER TABLE core_labtest ADD CONSTRAINT fk_labtest_appointment FOREIGN KEY (appointment_id) REFERENCES core_appointment(appointment_id) ON DELETE SET NULL");
                } catch (PDOException $e) {
                    echo "Note: " . $e->getMessage() . "\n";
                }
            }
        }
    } catch (PDOException $e) {
        echo "Note: " . $e->getMessage() . "\n";
    }
    
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'core_doctorrating'");
        if ($stmt->rowCount() == 0) {
            echo "Creating core_doctorrating table...\n";
            $pdo->exec("CREATE TABLE IF NOT EXISTS core_doctorrating (
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
            )");
        }
    } catch (PDOException $e) {
        echo "Note: " . $e->getMessage() . "\n";
    }
    
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'core_admission'");
        if ($stmt->rowCount() == 0) {
            echo "Creating core_admission table...\n";
            $pdo->exec("CREATE TABLE IF NOT EXISTS core_admission (
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
            )");
        }
    } catch (PDOException $e) {
        echo "Note: " . $e->getMessage() . "\n";
    }
    
    echo "Migrations completed.\n";

    $stmt = $pdo->query("SELECT COUNT(*) FROM core_district");
    if ($stmt->fetchColumn() == 0) {
        echo "Seeding initial data...\n";
        
        $pdo->exec("INSERT INTO core_district (name, division) VALUES ('Dhaka Central', 'Dhaka')");
        $district_id = $pdo->lastInsertId();

        $pdo->exec("INSERT INTO core_hospital (name, address, phone, capacity, registration_no, email, established_date, district_id, hospital_type) 
                    VALUES ('Dhaka Medical College', 'Secretariat Road, Dhaka', '01700000000', 2500, 'REG-12345', 'info@dmc.gov.bd', '1946-07-10', $district_id, 'public')");
        $hospital_id = $pdo->lastInsertId();

        $pdo->exec("INSERT INTO core_publichospital (hospital_id, govt_funding, accreditation_level, subsidies)
                    VALUES ($hospital_id, 10000000.00, 'A+', 500000.00)");

        $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO core_customuser (username, password, email, first_name, last_name, is_staff, is_superuser, role, hospital_id)
                    VALUES ('admin', '$password_hash', 'admin@dmc.gov.bd', 'Super', 'Admin', 1, 1, 'ADMIN', $hospital_id)");
        
        echo "Created Admin User: username='admin', password='admin123'\n";
    }

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage() . "\n");
}
?>
