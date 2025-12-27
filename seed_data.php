<?php
// seed_data.php
// Adds Bangladeshi Hospitals and Random Users

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'healthcare_db';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "Connected to database.\n";

    // 1. Districts
    $districts = [
        ['name' => 'Dhaka North', 'division' => 'Dhaka'],
        ['name' => 'Dhaka South', 'division' => 'Dhaka'],
        ['name' => 'Chittagong', 'division' => 'Chittagong'],
        ['name' => 'Sylhet', 'division' => 'Sylhet']
    ];

    $district_ids = [];
    foreach ($districts as $d) {
        $stmt = $pdo->prepare("SELECT district_id FROM core_district WHERE name = ?");
        $stmt->execute([$d['name']]);
        $id = $stmt->fetchColumn();
        
        if (!$id) {
            $stmt = $pdo->prepare("INSERT INTO core_district (name, division) VALUES (?, ?)");
            $stmt->execute([$d['name'], $d['division']]);
            $id = $pdo->lastInsertId();
            echo "Added District: {$d['name']}\n";
        }
        $district_ids[$d['name']] = $id;
    }
    // Fallback for existing 'Dhaka Central' if created by setup
    $stmt = $pdo->query("SELECT district_id FROM core_district LIMIT 1");
    $default_district_id = $stmt->fetchColumn();


    // 2. Hospitals
    $hospitals = [
        // Public
        [
            'type' => 'public',
            'name' => 'Bangabandhu Sheikh Mujib Medical University (BSMMU)',
            'address' => 'Shahbag, Dhaka',
            'phone' => '02-55165760',
            'capacity' => 1900,
            'reg' => 'GOV-002',
            'email' => 'info@bsmmu.edu.bd',
            'est' => '1998-04-30',
            'district' => $district_ids['Dhaka South'] ?? $default_district_id,
            'funding' => 50000000.00,
            'accreditation' => 'A+',
            'subsidies' => 2000000.00
        ],
        [
            'type' => 'public',
            'name' => 'Shaheed Suhrawardy Medical College Hospital',
            'address' => 'Sher-e-Bangla Nagar, Dhaka',
            'phone' => '02-9130800',
            'capacity' => 1350,
            'reg' => 'GOV-003',
            'email' => 'director@shsmc.gov.bd',
            'est' => '1963-01-01',
            'district' => $district_ids['Dhaka North'] ?? $default_district_id,
            'funding' => 30000000.00,
            'accreditation' => 'A',
            'subsidies' => 1500000.00
        ],
        [
            'type' => 'public',
            'name' => 'Chittagong Medical College Hospital',
            'address' => 'Panchlaish, Chittagong',
            'phone' => '031-619400',
            'capacity' => 2200,
            'reg' => 'GOV-004',
            'email' => 'cmch@ac.bd',
            'est' => '1957-01-01',
            'district' => $district_ids['Chittagong'] ?? $default_district_id,
            'funding' => 40000000.00,
            'accreditation' => 'A',
            'subsidies' => 1800000.00
        ],
        // Private
        [
            'type' => 'private',
            'name' => 'Square Hospitals Ltd.',
            'address' => '18/F, Bir Uttam Qazi Nuruzzaman Sarak, West Panthapath, Dhaka',
            'phone' => '10616',
            'capacity' => 400,
            'reg' => 'PVT-001',
            'email' => 'info@squarehospital.com',
            'est' => '2006-12-16',
            'district' => $district_ids['Dhaka South'] ?? $default_district_id,
            'owner' => 'Square Group',
            'profit' => 15.5
        ],
        [
            'type' => 'private',
            'name' => 'Evercare Hospital Dhaka',
            'address' => 'Plot 81, Block E, Bashundhara R/A, Dhaka',
            'phone' => '10678',
            'capacity' => 425,
            'reg' => 'PVT-002',
            'email' => 'feedback@evercarebd.com',
            'est' => '2005-04-01',
            'district' => $district_ids['Dhaka North'] ?? $default_district_id,
            'owner' => 'Evercare Group',
            'profit' => 18.2
        ],
        [
            'type' => 'private',
            'name' => 'United Hospital Limited',
            'address' => 'Plot 15, Road 71, Gulshan, Dhaka',
            'phone' => '10666',
            'capacity' => 500,
            'reg' => 'PVT-003',
            'email' => 'info@uhlbd.com',
            'est' => '2006-08-24',
            'district' => $district_ids['Dhaka North'] ?? $default_district_id,
            'owner' => 'United Group',
            'profit' => 16.0
        ]
    ];

    $hospital_ids = [];

    foreach ($hospitals as $h) {
        // Check existence
        $stmt = $pdo->prepare("SELECT hospital_id FROM core_hospital WHERE registration_no = ?");
        $stmt->execute([$h['reg']]);
        $hid = $stmt->fetchColumn();

        if (!$hid) {
            // Insert Base
            $sql = "INSERT INTO core_hospital (name, address, phone, capacity, registration_no, email, emergency_services, established_date, district_id, hospital_type)
                    VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$h['name'], $h['address'], $h['phone'], $h['capacity'], $h['reg'], $h['email'], $h['est'], $h['district'], $h['type']]);
            $hid = $pdo->lastInsertId();

            // Insert Child
            if ($h['type'] == 'public') {
                $pdo->prepare("INSERT INTO core_publichospital (hospital_id, govt_funding, accreditation_level, subsidies) VALUES (?, ?, ?, ?)")
                    ->execute([$hid, $h['funding'], $h['accreditation'], $h['subsidies']]);
            } else {
                $pdo->prepare("INSERT INTO core_privatehospital (hospital_id, owner_name, profit_margin) VALUES (?, ?, ?)")
                    ->execute([$hid, $h['owner'], $h['profit']]);
            }
            echo "Added Hospital: {$h['name']}\n";
        }
        $hospital_ids[] = $hid;
    }

    // 3. Departments (Common ones for new hospitals)
    $depts = ['Cardiology', 'Neurology', 'Orthopedics', 'Pediatrics', 'Internal Medicine', 'Surgery'];
    foreach ($hospital_ids as $hid) {
        foreach ($depts as $dname) {
            // Check duplicates
            $stmt = $pdo->prepare("SELECT dept_id FROM core_department WHERE hospital_id = ? AND dept_name = ?");
            $stmt->execute([$dname, $hid]);
            if (!$stmt->fetch()) {
                $pdo->prepare("INSERT INTO core_department (dept_name, floor, operating_hours, hospital_id) VALUES (?, ?, ?, ?)")
                    ->execute([$dname, '2nd Floor', '24/7', $hid]);
            }
        }
    }
    echo "Departments assigned to hospitals.\n";

    // 4. Doctors
    $doctors = [
        ['name' => 'Dr. Rahman', 'spec' => 'Cardiology', 'user' => 'dr_rahman'],
        ['name' => 'Dr. Sarah Khan', 'spec' => 'Neurology', 'user' => 'dr_sarah'],
        ['name' => 'Dr. Ahmed', 'spec' => 'Pediatrics', 'user' => 'dr_ahmed'],
        ['name' => 'Dr. Zafar Iqbal', 'spec' => 'Orthopedics', 'user' => 'dr_zafar'],
        ['name' => 'Dr. Nusrat Jahan', 'spec' => 'Internal Medicine', 'user' => 'dr_nusrat']
    ];

    $pass_hash = password_hash('password123', PASSWORD_DEFAULT);

    foreach ($doctors as $i => $doc) {
        // Pick a random hospital
        $hid = $hospital_ids[array_rand($hospital_ids)];
        // Get Dept ID
        $stmt = $pdo->prepare("SELECT dept_id FROM core_department WHERE hospital_id = ? AND dept_name = ?");
        $stmt->execute([$hid, $doc['spec']]);
        $did = $stmt->fetchColumn();

        if (!$did) continue; // Skip if dept mismatch (unlikely based on logic above)

        // Check if user exists
        $stmt = $pdo->prepare("SELECT id FROM core_customuser WHERE username = ?");
        $stmt->execute([$doc['user']]);
        if (!$stmt->fetch()) {
            // Create User
            $pdo->prepare("INSERT INTO core_customuser (username, password, email, first_name, role, hospital_id) VALUES (?, ?, ?, ?, 'DOCTOR', ?)")
                ->execute([$doc['user'], $pass_hash, "{$doc['user']}@example.com", $doc['name'], $hid]);
            $uid = $pdo->lastInsertId();

            // Create Doctor
            $lic = 'BMDC-' . rand(10000, 99999);
            $pdo->prepare("INSERT INTO core_doctor (license_no, full_name, specialization, phone, email, experience_yrs, gender, shift_timing, join_date, hospital_id, dept_id, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute([$lic, $doc['name'], $doc['spec'], '0171100000' . $i, "{$doc['user']}@example.com", rand(5, 20), 'M', '9AM-5PM', date('Y-m-d'), $hid, $did, $uid]);
            
            echo "Added Doctor: {$doc['name']} at Hospital ID $hid\n";
        }
    }

    // 5. Patients
    $patients = [
        ['user' => 'patient1', 'name' => 'Rahim Uddin', 'nid' => '1234567890'],
        ['user' => 'patient2', 'name' => 'Karim Hasan', 'nid' => '0987654321'],
        ['user' => 'patient3', 'name' => 'Fatima Begum', 'nid' => '1122334455']
    ];

    foreach ($patients as $p) {
        $stmt = $pdo->prepare("SELECT id FROM core_customuser WHERE username = ?");
        $stmt->execute([$p['user']]);
        if (!$stmt->fetch()) {
            // Create User
            $pdo->prepare("INSERT INTO core_customuser (username, password, email, first_name, role) VALUES (?, ?, ?, ?, 'PATIENT')")
                ->execute([$p['user'], $pass_hash, "{$p['user']}@example.com", $p['name']]);
            $uid = $pdo->lastInsertId();

            // Create Patient
            $pdo->prepare("INSERT INTO core_patient (national_id, full_name, date_of_birth, gender, phone, email, address, blood_type, marital_status, birth_place, father_name, mother_name, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute([$p['nid'], $p['name'], '1990-01-01', 'M', '01900000000', "{$p['user']}@example.com", 'Dhaka', 'O+', 'Single', 'Dhaka', 'Father', 'Mother', $uid]);
            
            echo "Added Patient: {$p['name']}\n";
        }
    }

    echo "\nSeed Complete!\n";
    echo "Default Password for all new users: password123\n";

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
