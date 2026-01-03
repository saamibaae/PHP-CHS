<?php
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
    $stmt = $pdo->query("SELECT district_id FROM core_district LIMIT 1");
    $default_district_id = $stmt->fetchColumn();

    $hospitals = [
        // Top 5 Government Hospitals
        [
            'type' => 'public',
            'name' => 'Dhaka Medical College',
            'address' => 'Secretariat Road, Dhaka',
            'phone' => '01700000000',
            'capacity' => 2500,
            'reg' => 'GOV-001',
            'email' => 'info@dmc.gov.bd',
            'est' => '1946-07-10',
            'district' => $district_ids['Dhaka South'] ?? $default_district_id,
            'funding' => 10000000.00,
            'accreditation' => 'A+',
            'subsidies' => 500000.00,
            'admin_username' => 'DMC'
        ],
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
            'subsidies' => 2000000.00,
            'admin_username' => 'BSMMU'
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
            'subsidies' => 1500000.00,
            'admin_username' => 'SSMCH'
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
            'subsidies' => 1800000.00,
            'admin_username' => 'CMCH'
        ],
        [
            'type' => 'public',
            'name' => 'Sir Salimullah Medical College Hospital',
            'address' => 'Mitford, Dhaka',
            'phone' => '02-7123456',
            'capacity' => 1200,
            'reg' => 'GOV-005',
            'email' => 'info@ssmc.gov.bd',
            'est' => '1875-01-01',
            'district' => $district_ids['Dhaka South'] ?? $default_district_id,
            'funding' => 25000000.00,
            'accreditation' => 'A',
            'subsidies' => 1200000.00,
            'admin_username' => 'SSMC'
        ],
        // Top 5 Private Hospitals
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
            'profit' => 15.5,
            'admin_username' => 'square'
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
            'profit' => 18.2,
            'admin_username' => 'evercare'
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
            'profit' => 16.0,
            'admin_username' => 'united'
        ],
        [
            'type' => 'private',
            'name' => 'Apollo Hospitals Dhaka',
            'address' => 'Plot 81, Block E, Bashundhara R/A, Dhaka',
            'phone' => '10678',
            'capacity' => 350,
            'reg' => 'PVT-004',
            'email' => 'info@apollodhaka.com',
            'est' => '2005-01-01',
            'district' => $district_ids['Dhaka North'] ?? $default_district_id,
            'owner' => 'Apollo Hospitals Group',
            'profit' => 17.5,
            'admin_username' => 'apollo'
        ],
        [
            'type' => 'private',
            'name' => 'Popular Medical College Hospital',
            'address' => 'Dhanmondi, Dhaka',
            'phone' => '02-9123456',
            'capacity' => 300,
            'reg' => 'PVT-005',
            'email' => 'info@popularhospital.com',
            'est' => '2010-01-01',
            'district' => $district_ids['Dhaka South'] ?? $default_district_id,
            'owner' => 'Popular Group',
            'profit' => 14.0,
            'admin_username' => 'popular'
        ]
    ];

    $hospital_ids = [];
    $hospital_codes = []; // Map hospital_id => hospital_code (admin_username)
    $pass_hash = password_hash('password123', PASSWORD_DEFAULT);

    foreach ($hospitals as $h) {
        // Check if hospital exists by registration number
        $stmt = $pdo->prepare("SELECT hospital_id FROM core_hospital WHERE registration_no = ?");
        $stmt->execute([$h['reg']]);
        $hid = $stmt->fetchColumn();
        
        // If not found by reg number, check by name (for DMC which might have different reg from setup_db.php)
        if (!$hid && isset($h['admin_username']) && $h['admin_username'] == 'DMC') {
            $stmt = $pdo->prepare("SELECT hospital_id FROM core_hospital WHERE name = ?");
            $stmt->execute([$h['name']]);
            $hid = $stmt->fetchColumn();
        }

        if (!$hid) {
            $sql = "INSERT INTO core_hospital (name, address, phone, capacity, registration_no, email, emergency_services, established_date, district_id, hospital_type)
                    VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$h['name'], $h['address'], $h['phone'], $h['capacity'], $h['reg'], $h['email'], $h['est'], $h['district'], $h['type']]);
            $hid = $pdo->lastInsertId();

            if ($h['type'] == 'public') {
                // Check if public hospital record already exists
                $stmt = $pdo->prepare("SELECT hospital_id FROM core_publichospital WHERE hospital_id = ?");
                $stmt->execute([$hid]);
                if (!$stmt->fetch()) {
                    $pdo->prepare("INSERT INTO core_publichospital (hospital_id, govt_funding, accreditation_level, subsidies) VALUES (?, ?, ?, ?)")
                        ->execute([$hid, $h['funding'], $h['accreditation'], $h['subsidies']]);
                }
            } else {
                // Check if private hospital record already exists
                $stmt = $pdo->prepare("SELECT hospital_id FROM core_privatehospital WHERE hospital_id = ?");
                $stmt->execute([$hid]);
                if (!$stmt->fetch()) {
                    $pdo->prepare("INSERT INTO core_privatehospital (hospital_id, owner_name, profit_margin) VALUES (?, ?, ?)")
                        ->execute([$hid, $h['owner'], $h['profit']]);
                }
            }
            echo "Added Hospital: {$h['name']}\n";
        } else {
            echo "Hospital already exists: {$h['name']}\n";
        }
        
        // Create admin account for this hospital if it doesn't exist
        if (isset($h['admin_username']) && $hid) {
            $stmt = $pdo->prepare("SELECT id FROM core_customuser WHERE username = ? AND hospital_id = ?");
            $stmt->execute([$h['admin_username'], $hid]);
            if (!$stmt->fetch()) {
                $pdo->prepare("INSERT INTO core_customuser (username, password, email, first_name, last_name, is_staff, is_superuser, role, hospital_id) VALUES (?, ?, ?, ?, ?, 1, 1, 'ADMIN', ?)")
                    ->execute([$h['admin_username'], $pass_hash, "admin@{$h['reg']}.com", 'Admin', $h['name'], $hid]);
                echo "  Created Admin: username='{$h['admin_username']}', password='password123'\n";
            } else {
                echo "  Admin account already exists: {$h['admin_username']}\n";
            }
        }
        
        // Store hospital code for doctor username generation
        if (isset($h['admin_username']) && $hid) {
            $hospital_codes[$hid] = strtoupper($h['admin_username']);
        }
        
        $hospital_ids[] = $hid;
    }

    $depts = ['Cardiology', 'Neurology', 'Orthopedics', 'Pediatrics', 'Internal Medicine', 'Surgery', 'Dermatology', 'Ophthalmology', 'ENT', 'Gynecology', 'Urology', 'Psychiatry', 'Radiology', 'Emergency Medicine'];
    foreach ($hospital_ids as $hid) {
        foreach ($depts as $dname) {
            $stmt = $pdo->prepare("SELECT dept_id FROM core_department WHERE hospital_id = ? AND dept_name = ?");
            $stmt->execute([$hid, $dname]);
            if (!$stmt->fetch()) {
                $pdo->prepare("INSERT INTO core_department (dept_name, floor, operating_hours, hospital_id) VALUES (?, ?, ?, ?)")
                    ->execute([$dname, '2nd Floor', '24/7', $hid]);
            }
        }
    }
    echo "Departments assigned to hospitals.\n";

    // Create one doctor for each specialization in each hospital
    $specializations = ['Cardiology', 'Neurology', 'Orthopedics', 'Pediatrics', 'Internal Medicine', 'Surgery', 'Dermatology', 'Ophthalmology', 'ENT', 'Gynecology', 'Urology', 'Psychiatry', 'Radiology', 'Emergency Medicine'];
    $doctor_names = [
        'Cardiology' => ['Rahman', 'Hasan', 'Ali', 'Khan', 'Ahmed', 'Iqbal', 'Chowdhury', 'Hossain', 'Uddin', 'Karim'],
        'Neurology' => ['Sarah Khan', 'Fatima Begum', 'Nusrat Jahan', 'Ayesha Rahman', 'Tasnim Ahmed', 'Rashida Islam', 'Sharmin Akter', 'Nazma Begum', 'Rokeya Khatun', 'Shirin Sultana'],
        'Orthopedics' => ['Zafar Iqbal', 'Kamal Hossain', 'Rashidul Islam', 'Shahidul Alam', 'Mizanur Rahman', 'Abdul Karim', 'Mohammad Ali', 'Shah Alam', 'Nurul Islam', 'Abul Kalam'],
        'Pediatrics' => ['Ahmed', 'Karim', 'Rashid', 'Hasan', 'Mahmud', 'Rahman', 'Ali', 'Khan', 'Chowdhury', 'Hossain'],
        'Internal Medicine' => ['Nusrat Jahan', 'Farida Begum', 'Shahana Akter', 'Rokeya Khatun', 'Nasima Begum', 'Shirin Sultana', 'Taslima Begum', 'Rashida Islam', 'Ayesha Rahman', 'Fatima Khan'],
        'Surgery' => ['Maruf Hossain', 'Shahidul Islam', 'Kamrul Hasan', 'Rashidul Alam', 'Mizanur Rahman', 'Abdul Kader', 'Mohammad Ali', 'Shah Alam', 'Nurul Islam', 'Abul Kalam'],
        'Dermatology' => ['Rashida Begum', 'Sharmin Akter', 'Nazma Khatun', 'Farida Islam', 'Taslima Rahman', 'Ayesha Khan', 'Rokeya Begum', 'Shirin Akter', 'Nasima Khatun', 'Fatima Islam'],
        'Ophthalmology' => ['Kamal Hossain', 'Rashidul Islam', 'Shahidul Alam', 'Mizanur Rahman', 'Abdul Karim', 'Mohammad Ali', 'Shah Alam', 'Nurul Islam', 'Abul Kalam', 'Rahman Uddin'],
        'ENT' => ['Karim Hossain', 'Rashidul Alam', 'Shahidul Islam', 'Mizanur Rahman', 'Abdul Kader', 'Mohammad Ali', 'Shah Alam', 'Nurul Islam', 'Abul Kalam', 'Kamal Uddin'],
        'Gynecology' => ['Fatima Begum', 'Ayesha Rahman', 'Tasnim Ahmed', 'Rashida Islam', 'Sharmin Akter', 'Nazma Begum', 'Rokeya Khatun', 'Shirin Sultana', 'Nasima Begum', 'Farida Khan'],
        'Urology' => ['Kamal Hossain', 'Rashidul Islam', 'Shahidul Alam', 'Mizanur Rahman', 'Abdul Karim', 'Mohammad Ali', 'Shah Alam', 'Nurul Islam', 'Abul Kalam', 'Rahman Uddin'],
        'Psychiatry' => ['Sarah Khan', 'Fatima Begum', 'Nusrat Jahan', 'Ayesha Rahman', 'Tasnim Ahmed', 'Rashida Islam', 'Sharmin Akter', 'Nazma Begum', 'Rokeya Khatun', 'Shirin Sultana'],
        'Radiology' => ['Kamal Hossain', 'Rashidul Islam', 'Shahidul Alam', 'Mizanur Rahman', 'Abdul Karim', 'Mohammad Ali', 'Shah Alam', 'Nurul Islam', 'Abul Kalam', 'Rahman Uddin'],
        'Emergency Medicine' => ['Maruf Hossain', 'Shahidul Islam', 'Kamrul Hasan', 'Rashidul Alam', 'Mizanur Rahman', 'Abdul Kader', 'Mohammad Ali', 'Shah Alam', 'Nurul Islam', 'Abul Kalam']
    ];
    
    foreach ($hospital_ids as $hid) {
        // Get hospital name and code
        $stmt = $pdo->prepare("SELECT name FROM core_hospital WHERE hospital_id = ?");
        $stmt->execute([$hid]);
        $hospital_name = $stmt->fetchColumn();
        $hospital_code = $hospital_codes[$hid] ?? strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $hospital_name), 0, 4));
        
        foreach ($specializations as $spec) {
            // Get department ID for this specialization
            $stmt = $pdo->prepare("SELECT dept_id FROM core_department WHERE hospital_id = ? AND dept_name = ?");
            $stmt->execute([$hid, $spec]);
            $did = $stmt->fetchColumn();
            
            if (!$did) continue;
            
            // Check if doctor already exists for this hospital and specialization
            $stmt = $pdo->prepare("SELECT doctor_id FROM core_doctor WHERE hospital_id = ? AND specialization = ?");
            $stmt->execute([$hid, $spec]);
            if ($stmt->fetch()) {
                continue; // Doctor already exists for this specialization in this hospital
            }
            
            // Generate username in format: {lowercase_hospital_code}_{UPPERCASE_SPECIALIZATION}
            // Convert specialization to uppercase and replace spaces with underscores
            $spec_code = strtoupper(str_replace(' ', '_', $spec));
            $username = strtolower($hospital_code) . '_' . $spec_code;
            
            // Check if username already exists (in case script is run multiple times)
            $stmt = $pdo->prepare("SELECT id FROM core_customuser WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                echo "  Doctor username already exists: {$username}, skipping...\n";
                continue;
            }
            
            // Get a name from the pool for this specialization
            $name_pool = $doctor_names[$spec];
            $first_name = $name_pool[array_rand($name_pool)];
            $full_name = 'Dr. ' . $first_name;
            
            // Create user account
            $pdo->prepare("INSERT INTO core_customuser (username, password, email, first_name, role, hospital_id) VALUES (?, ?, ?, ?, 'DOCTOR', ?)")
                ->execute([$username, $pass_hash, "{$username}@example.com", $full_name, $hid]);
            $uid = $pdo->lastInsertId();
            
            // Create doctor record
            $lic = 'BMDC-' . str_pad(rand(10000, 99999), 5, '0', STR_PAD_LEFT);
            $phone = '017' . str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
            $genders = ['M', 'F'];
            $gender = $genders[array_rand($genders)];
            $shift_timings = ['9AM-5PM', '10AM-6PM', '8AM-4PM', '11AM-7PM'];
            $shift = $shift_timings[array_rand($shift_timings)];
            
            $pdo->prepare("INSERT INTO core_doctor (license_no, full_name, specialization, phone, email, experience_yrs, gender, shift_timing, join_date, hospital_id, dept_id, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute([$lic, $full_name, $spec, $phone, "{$username}@example.com", rand(5, 25), $gender, $shift, date('Y-m-d', strtotime('-' . rand(0, 365) . ' days')), $hid, $did, $uid]);
            
            echo "Added Doctor: {$full_name} ({$spec}) at {$hospital_name} - Username: {$username}\n";
        }
    }

    $patients = [
        ['user' => 'patient1', 'name' => 'Rahim Uddin', 'nid' => '1234567890'],
        ['user' => 'patient2', 'name' => 'Karim Hasan', 'nid' => '0987654321'],
        ['user' => 'patient3', 'name' => 'Fatima Begum', 'nid' => '1122334455']
    ];

    foreach ($patients as $p) {
        $stmt = $pdo->prepare("SELECT id FROM core_customuser WHERE username = ?");
        $stmt->execute([$p['user']]);
        if (!$stmt->fetch()) {
            $pdo->prepare("INSERT INTO core_customuser (username, password, email, first_name, role) VALUES (?, ?, ?, ?, 'PATIENT')")
                ->execute([$p['user'], $pass_hash, "{$p['user']}@example.com", $p['name']]);
            $uid = $pdo->lastInsertId();

            $pdo->prepare("INSERT INTO core_patient (national_id, full_name, date_of_birth, gender, phone, email, address, blood_type, marital_status, birth_place, father_name, mother_name, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute([$p['nid'], $p['name'], '1990-01-01', 'M', '01900000000', "{$p['user']}@example.com", 'Dhaka', 'O+', 'Single', 'Dhaka', 'Father', 'Mother', $uid]);
            
            echo "Added Patient: {$p['name']}\n";
        }
    }

    // Create sample appointments for patients
    $stmt = $pdo->query("SELECT patient_id FROM core_patient LIMIT 3");
    $patient_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $stmt = $pdo->query("SELECT doctor_id FROM core_doctor LIMIT 5");
    $doctor_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($patient_ids) && !empty($doctor_ids)) {
        $appointment_count = 0;
        foreach ($patient_ids as $pid) {
            // Create 1-2 appointments per patient
            $num_appts = rand(1, 2);
            for ($i = 0; $i < $num_appts; $i++) {
                $did = $doctor_ids[array_rand($doctor_ids)];
                $date = date('Y-m-d H:i:s', strtotime('+' . rand(1, 30) . ' days ' . rand(9, 16) . ':00:00'));
                $statuses = ['Scheduled', 'Completed', 'Cancelled'];
                $status = $statuses[array_rand($statuses)];
                
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM core_appointment WHERE patient_id = ? AND doctor_id = ? AND date_and_time = ?");
                $stmt->execute([$pid, $did, $date]);
                if ($stmt->fetchColumn() == 0) {
                    $pdo->prepare("INSERT INTO core_appointment (patient_id, doctor_id, status, reason_for_visit, symptoms, visit_type, date_and_time) VALUES (?, ?, ?, ?, ?, ?, ?)")
                        ->execute([$pid, $did, $status, 'Regular checkup', 'General consultation', 'In-person', $date]);
                    $appointment_count++;
                }
            }
        }
        if ($appointment_count > 0) {
            echo "Created $appointment_count sample appointments.\n";
        }
    }

    echo "\nSeed Complete!\n";
    echo "Default Password for all new users: password123\n";

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

