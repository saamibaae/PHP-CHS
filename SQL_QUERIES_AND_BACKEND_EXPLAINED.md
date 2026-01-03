# SQL Queries and Backend Functions - Complete Guide
## Explained Like You're Learning for the First Time! 📚

---

## Table of Contents

1. [What is SQL? (The Basics)](#what-is-sql-the-basics)
2. [How Our Database is Organized](#how-our-database-is-organized)
3. [Core Backend Functions](#core-backend-functions)
4. [Authentication & Login System](#authentication--login-system)
5. [Patient Module - All Queries Explained](#patient-module---all-queries-explained)
6. [Doctor Module - All Queries Explained](#doctor-module---all-queries-explained)
7. [Admin Module - All Queries Explained](#admin-module---all-queries-explained)
8. [How Data Flows Through the System](#how-data-flows-through-the-system)

---

## What is SQL? (The Basics)

**SQL** stands for **Structured Query Language**. Think of it as a way to talk to a database (like a filing cabinet full of information).

### Basic SQL Commands:

1. **SELECT** = "Show me" or "Get me"
   - Example: `SELECT * FROM users` = "Show me everything from the users table"

2. **INSERT** = "Add new"
   - Example: `INSERT INTO users (name) VALUES ('John')` = "Add a new user named John"

3. **UPDATE** = "Change"
   - Example: `UPDATE users SET name='Jane' WHERE id=1` = "Change the name to Jane for user with id 1"

4. **DELETE** = "Remove"
   - Example: `DELETE FROM users WHERE id=1` = "Remove the user with id 1"

5. **WHERE** = "Only if"
   - Example: `SELECT * FROM users WHERE age > 18` = "Show me users only if their age is more than 18"

### What is a Prepared Statement?

A **prepared statement** is like filling out a form template. Instead of writing the actual values directly, we use placeholders (`?`) and fill them in later. This is **MUCH SAFER** because it prevents hackers from injecting bad code.

**Bad way (DANGEROUS):**
```php
$query = "SELECT * FROM users WHERE username = '$username'";
// If username is "admin' OR '1'='1", this breaks our security!
```

**Good way (SAFE - Prepared Statement):**
```php
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);
// The ? is replaced safely, no matter what username is!
```

---

## How Our Database is Organized

Our database is like a big filing cabinet with different drawers (tables):

### Main Tables:

1. **core_customuser** - All user accounts (admins, doctors, patients)
2. **core_hospital** - Hospital information
3. **core_doctor** - Doctor details
4. **core_patient** - Patient details
5. **core_appointment** - Appointments between patients and doctors
6. **core_prescription** - Prescriptions created by doctors
7. **core_prescriptionitem** - Individual medicines in a prescription
8. **core_department** - Hospital departments
9. **core_medicine** - Medicine catalog
10. **core_bill** - Patient bills

### How Tables Connect (Relationships):

- A **patient** has many **appointments**
- An **appointment** has one **prescription**
- A **prescription** has many **prescription items** (medicines)
- A **doctor** belongs to one **hospital**
- A **hospital** has many **departments**

---

## Core Backend Functions

### File: `db.php`

This file connects to the database and provides helper functions.

#### 1. Database Connection
```php
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
$pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
```

**What it does:**
- Creates a connection to the MySQL database
- `$pdo` is our "talking device" to the database
- Like picking up a phone to call the database

#### 2. `isLoggedIn()`
```php
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}
```

**What it does:**
- Checks if someone is logged in
- Returns `true` if `user_id` exists in the session (like checking if someone has a ticket)
- Returns `false` if not logged in

**Real-world example:**
- Like checking if someone has a membership card before entering a club

#### 3. `requireLogin()`
```php
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}
```

**What it does:**
- If user is NOT logged in, redirect them to login page
- Stops the current page from loading
- Like a bouncer at a club checking your ID

#### 4. `requireRole($role)`
```php
function requireRole($role) {
    requireLogin();
    if ($_SESSION['role'] !== $role) {
        die("Access Denied: You do not have permission to view this page.");
    }
}
```

**What it does:**
- First checks if logged in
- Then checks if user's role matches the required role
- If not, shows "Access Denied" and stops

**Example:**
- Only doctors can access doctor pages
- Only admins can access admin pages
- Like different keys for different rooms

#### 5. `setFlash($message, $type)`
```php
function setFlash($message, $type = 'success') {
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}
```

**What it does:**
- Saves a message to show on the next page
- Like leaving a sticky note for yourself
- Used for "Success!" or "Error!" messages

**Example:**
- After saving, show "Appointment booked successfully!"
- After error, show "Invalid username or password"

---

## Authentication & Login System

### File: `login.php`

#### Query 1: Check Username and Password
```php
$stmt = $pdo->prepare("SELECT * FROM core_customuser WHERE LOWER(username) = LOWER(?)");
$stmt->execute([$username]);
$user = $stmt->fetch();
```

**What it does:**
- Looks for a user with matching username (case-insensitive)
- `LOWER()` makes both lowercase so "Admin" and "admin" are the same
- `?` is replaced with the actual username safely

**Step by step:**
1. User types username: "apollo_ENT"
2. Query becomes: `SELECT * FROM core_customuser WHERE LOWER(username) = LOWER('apollo_ENT')`
3. Database searches for user
4. Returns user data if found, or nothing if not found

#### Query 2: Verify Password
```php
if ($user && verifyPassword($password, $user['password'])) {
    // Login successful
}
```

**What it does:**
- Checks if user exists AND password matches
- `verifyPassword()` compares the typed password with the stored hash
- If both match, user is logged in

**How password hashing works:**
- We NEVER store passwords as plain text (like "password123")
- Instead, we store a "hash" (like "a3f5b2c1d4e6...")
- When user logs in, we hash their input and compare
- Like a fingerprint - you can't reverse it, but you can compare

#### Query 3: Set Session (After Successful Login)
```php
$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role'] = $user['role'];
$_SESSION['hospital_id'] = $user['hospital_id'];
```

**What it does:**
- Saves user information in the session
- Session is like a temporary memory that lasts until logout
- Other pages can check `$_SESSION['role']` to know who is logged in

---

## Patient Module - All Queries Explained

### File: `patient/dashboard.php`

#### Query 1: Get Patient Information
```php
$stmt = $pdo->prepare("SELECT patient_id, full_name, blood_type FROM core_patient WHERE user_id = ?");
$stmt->execute([$user_id]);
$patient = $stmt->fetch();
```

**What it does:**
- Gets patient's basic info using their user ID
- `user_id` connects the login account to the patient record
- Returns: patient_id, name, and blood type

**Why we need this:**
- To display "Hello, [Name]" on dashboard
- To show blood type
- To get patient_id for other queries

#### Query 2: Get Upcoming Appointments
```php
$stmt = $pdo->prepare("
    SELECT a.date_and_time, d.full_name as doctor_name, d.specialization
    FROM core_appointment a
    JOIN core_doctor d ON a.doctor_id = d.doctor_id
    WHERE a.patient_id = ? AND a.date_and_time >= NOW() AND a.status != 'Completed'
    ORDER BY a.date_and_time ASC LIMIT 3
");
$stmt->execute([$patient_id]);
$upcoming_appts = $stmt->fetchAll();
```

**What it does:**
- Gets the next 3 upcoming appointments
- `JOIN` connects appointments with doctor information
- `date_and_time >= NOW()` means future appointments only
- `status != 'Completed'` excludes finished appointments
- `ORDER BY ... ASC` sorts from earliest to latest
- `LIMIT 3` shows only 3 results

**Step by step:**
1. Start with appointments table (`a`)
2. Connect to doctors table (`d`) using `doctor_id`
3. Filter: only this patient's appointments
4. Filter: only future dates
5. Filter: exclude completed ones
6. Sort by date (earliest first)
7. Take only first 3

#### Query 3: Get Recent Bills
```php
$stmt = $pdo->prepare("
    SELECT bill_date, total_amount, status, service_type_id
    FROM core_bill
    WHERE patient_id = ?
    ORDER BY bill_date DESC LIMIT 5
");
$stmt->execute([$patient_id]);
$bills = $stmt->fetchAll();
```

**What it does:**
- Gets last 5 bills for this patient
- `ORDER BY bill_date DESC` = newest first (DESC = descending)
- Shows date, amount, status, and service type

#### Query 4: Get Recent Prescriptions
```php
$stmt = $pdo->prepare("
    SELECT p.prescription_id, p.valid_until, m.name as medicine_name, pi.dosage, pi.instructions
    FROM core_prescription p
    JOIN core_prescriptionitem pi ON p.prescription_id = pi.prescription_id
    JOIN core_medicine m ON pi.medicine_id = m.medicine_id
    JOIN core_appointment a ON p.appointment_id = a.appointment_id
    WHERE a.patient_id = ?
    ORDER BY p.prescription_id DESC LIMIT 5
");
$stmt->execute([$patient_id]);
$prescriptions = $stmt->fetchAll();
```

**What it does:**
- Gets recent prescriptions with medicine details
- **Multiple JOINs** connect 4 tables:
  1. `core_prescription` (p) - the prescription
  2. `core_prescriptionitem` (pi) - individual medicines
  3. `core_medicine` (m) - medicine names
  4. `core_appointment` (a) - to filter by patient

**Why so many JOINs?**
- Prescription has items
- Items have medicine IDs
- We need medicine names
- We filter by patient through appointments

### File: `patient/appointments.php`

#### Query: Get All Appointments
```php
$sql = "SELECT a.*, d.full_name as doctor_name, d.specialization, h.name as hospital_name
        FROM core_appointment a
        INNER JOIN core_doctor d ON a.doctor_id = d.doctor_id
        INNER JOIN core_hospital h ON d.hospital_id = h.hospital_id
        WHERE a.patient_id = ?
        ORDER BY a.date_and_time DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$patient_id]);
$appointments = $stmt->fetchAll();
```

**What it does:**
- Gets ALL appointments (past and future) for this patient
- `a.*` = all columns from appointments table
- Joins with doctors to get doctor name and specialization
- Joins with hospitals to get hospital name
- `ORDER BY ... DESC` = newest first

**INNER JOIN explained:**
- Only shows appointments that have matching doctors
- If doctor is deleted, appointment won't show (safety feature)

### File: `patient/book_appointment.php`

#### Query 1: Get All Hospitals
```php
$hospitals = $pdo->query("SELECT hospital_id, name FROM core_hospital ORDER BY name")->fetchAll();
```

**What it does:**
- Simple query to get all hospitals
- No WHERE clause = gets everything
- Sorted alphabetically by name

#### Query 2: Get Specializations for Hospital
```php
$stmt = $pdo->prepare("SELECT DISTINCT d.specialization FROM core_doctor d WHERE d.hospital_id = ? ORDER BY d.specialization");
$stmt->execute([$selected_hospital_id]);
$specializations = $stmt->fetchAll(PDO::FETCH_COLUMN);
```

**What it does:**
- Gets unique specializations available at selected hospital
- `DISTINCT` = removes duplicates (if 5 cardiologists, shows "Cardiology" only once)
- `FETCH_COLUMN` = returns just the specialization names, not full rows

#### Query 3: Get Doctors for Hospital and Specialization
```php
$stmt = $pdo->prepare("SELECT d.doctor_id, d.full_name, d.specialization, d.shift_timing 
                       FROM core_doctor d 
                       WHERE d.hospital_id = ? AND d.specialization = ? 
                       ORDER BY d.full_name");
$stmt->execute([$selected_hospital_id, $selected_specialization]);
$doctors = $stmt->fetchAll();
```

**What it does:**
- Gets doctors matching both hospital AND specialization
- Shows doctor ID, name, specialization, and shift timing
- Sorted by name alphabetically

#### Query 4: Check Doctor Availability
```php
$stmt = $pdo->prepare("SELECT TIME(date_and_time) as time 
                       FROM core_appointment 
                       WHERE doctor_id = ? AND DATE(date_and_time) = ? AND status != 'Cancelled'");
$stmt->execute([$doctor_id, $date]);
$booked_times = $stmt->fetchAll(PDO::FETCH_COLUMN);
```

**What it does:**
- Gets all booked time slots for a doctor on a specific date
- `TIME(date_and_time)` = extracts just the time (like "14:30:00")
- `DATE(date_and_time) = ?` = matches the date part only
- Excludes cancelled appointments
- Used to show which times are already taken

#### Query 5: Create New Appointment
```php
$stmt = $pdo->prepare("INSERT INTO core_appointment 
                       (patient_id, doctor_id, status, reason_for_visit, symptoms, visit_type, date_and_time) 
                       VALUES (?, ?, 'Scheduled', ?, ?, ?, ?)");
$stmt->execute([$patient_id, $doctor_id, $reason, $symptoms, $visit_type, $date_time]);
```

**What it does:**
- Creates a new appointment record
- Sets status to 'Scheduled' automatically
- Saves all appointment details
- Returns the new appointment ID

### File: `patient/check_availability.php`

#### Query: Get Booked Times (AJAX Endpoint)
```php
$stmt = $pdo->prepare("SELECT TIME(date_and_time) as time 
                       FROM core_appointment 
                       WHERE doctor_id = ? AND DATE(date_and_time) = ? AND status != 'Cancelled'");
$stmt->execute([$doctor_id, $date]);
$booked_times = $stmt->fetchAll(PDO::FETCH_COLUMN);
```

**What it does:**
- Same as Query 4 above, but returns JSON
- Used by JavaScript to update available time slots in real-time
- When user selects a date, this runs automatically via AJAX

### File: `patient/appointment_detail.php`

#### Query 1: Get Appointment with Doctor and Hospital Info
```php
$sql = "SELECT a.*, d.full_name as doctor_name, d.specialization, 
               dept.dept_name, h.name as hospital_name
        FROM core_appointment a
        INNER JOIN core_doctor d ON a.doctor_id = d.doctor_id
        LEFT JOIN core_department dept ON d.dept_id = dept.dept_id
        INNER JOIN core_hospital h ON d.hospital_id = h.hospital_id
        WHERE a.appointment_id = ? AND a.patient_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$appointment_id, $patient_id]);
$appointment = $stmt->fetch();
```

**What it does:**
- Gets full appointment details with related information
- `LEFT JOIN` for department = shows appointment even if doctor has no department
- Verifies appointment belongs to logged-in patient (security!)

#### Query 2: Get Prescriptions for Appointment
```php
$sql = "SELECT * FROM core_prescription WHERE appointment_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$appointment_id]);
$prescriptions = $stmt->fetchAll();
```

**What it does:**
- Gets all prescriptions linked to this appointment
- One appointment can have multiple prescriptions

#### Query 3: Get Prescription Items (Medicines)
```php
$sql = "SELECT pi.*, m.name as medicine_name 
        FROM core_prescriptionitem pi
        INNER JOIN core_medicine m ON pi.medicine_id = m.medicine_id
        WHERE pi.prescription_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$presc['prescription_id']]);
$presc['items'] = $stmt->fetchAll();
```

**What it does:**
- Gets all medicines in a prescription
- Joins with medicine table to get medicine names
- Returns dosage, frequency, instructions for each medicine

### File: `patient/profile.php`

#### Query: Get Patient Profile
```php
$stmt = $pdo->prepare("SELECT * FROM core_patient WHERE user_id = ?");
$stmt->execute([$user_id]);
$patient = $stmt->fetch();
```

**What it does:**
- Gets complete patient information
- Shows: name, DOB, gender, blood type, address, etc.

### File: `patient/bills.php`

#### Query: Get All Bills
```php
$sql = "SELECT b.*, st.name as service_type_name
        FROM core_bill b
        LEFT JOIN core_servicetype st ON b.service_type_id = st.service_type_id
        WHERE b.patient_id = ?
        ORDER BY b.bill_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$patient_id]);
$bills = $stmt->fetchAll();
```

**What it does:**
- Gets all bills for this patient
- Joins with service type to show what the bill is for
- Sorted by date (newest first)

---

## Doctor Module - All Queries Explained

### File: `doctor/dashboard.php`

#### Query 1: Get Doctor Information
```php
$stmt = $pdo->prepare("SELECT doctor_id, full_name, specialization FROM core_doctor WHERE user_id = ?");
$stmt->execute([$user_id]);
$doctor = $stmt->fetch();
```

**What it does:**
- Gets doctor's basic info from their user account
- Returns doctor_id (needed for other queries), name, and specialization

#### Query 2: Get Today's Schedule
```php
$stmt = $pdo->prepare("
    SELECT a.appointment_id, a.date_and_time, a.symptoms, a.status, p.full_name, p.patient_id
    FROM core_appointment a
    JOIN core_patient p ON a.patient_id = p.patient_id
    WHERE a.doctor_id = ? AND DATE(a.date_and_time) = ?
    ORDER BY a.date_and_time ASC
");
$stmt->execute([$doctor_id, $today]);
$schedule = $stmt->fetchAll();
```

**What it does:**
- Gets all appointments for TODAY only
- `DATE(a.date_and_time) = ?` = matches date part only (ignores time)
- Joins with patients to show patient names
- Sorted by time (earliest first)

**Why DATE() function?**
- `date_and_time` stores both date and time (like "2024-01-15 14:30:00")
- `DATE()` extracts just the date part ("2024-01-15")
- So we can match today's date regardless of time

### File: `doctor/appointments.php`

#### Query: Get All Appointments (Filtered by Status)
```php
$sql = "SELECT a.*, p.full_name as patient_name
        FROM core_appointment a
        INNER JOIN core_patient p ON a.patient_id = p.patient_id
        WHERE a.doctor_id = ?";

if ($status) {
    $sql .= " AND a.status = ?";
    $params[] = $status;
}

$sql .= " ORDER BY a.date_and_time DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$appointments = $stmt->fetchAll();
```

**What it does:**
- Gets all appointments for this doctor
- If status filter is provided (like "Scheduled"), adds it to WHERE clause
- `$params` array builds dynamically based on filters
- Shows all appointments sorted by date (newest first)

**Dynamic Query Building:**
- Starts with base query
- Adds conditions only if needed
- This is safe because we use prepared statements

### File: `doctor/appointment_detail.php`

#### Query 1: Get Appointment with Patient Info
```php
$sql = "SELECT a.*, p.full_name as patient_name, p.date_of_birth, p.gender, p.blood_type
        FROM core_appointment a
        INNER JOIN core_patient p ON a.patient_id = p.patient_id
        WHERE a.appointment_id = ? AND a.doctor_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$appointment_id, $doctor_id]);
$appointment = $stmt->fetch();
```

**What it does:**
- Gets appointment with patient's medical info
- Verifies appointment belongs to this doctor (security!)
- Shows patient's DOB, gender, blood type for medical context

#### Query 2: Update Appointment Status
```php
$sql = "UPDATE core_appointment SET status = ?, diagnosis = ?, follow_up_date = ? WHERE appointment_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$status, $diagnosis, $follow_up_date, $appointment_id]);
```

**What it does:**
- Updates appointment when doctor adds diagnosis
- Can change status to "Completed", "Cancelled", etc.
- Saves diagnosis text and follow-up date if needed

**UPDATE explained:**
- Changes existing record (doesn't create new one)
- `SET` = what to change
- `WHERE` = which record to change

#### Query 3: Get Prescriptions for Appointment
```php
$sql = "SELECT * FROM core_prescription WHERE appointment_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$appointment_id]);
$prescriptions = $stmt->fetchAll();
```

**What it does:**
- Gets all prescriptions for this appointment
- Doctor can see what prescriptions were already created

#### Query 4: Get Prescription Items
```php
$sql = "SELECT pi.*, m.name as medicine_name 
        FROM core_prescriptionitem pi
        INNER JOIN core_medicine m ON pi.medicine_id = m.medicine_id
        WHERE pi.prescription_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$presc['prescription_id']]);
$presc['items'] = $stmt->fetchAll();
```

**What it does:**
- Gets all medicines in a prescription
- Shows medicine names, dosages, frequencies

### File: `doctor/prescription_form.php`

#### Query: Create New Prescription
```php
$sql = "INSERT INTO core_prescription (appointment_id, valid_until, refill_count, notes) VALUES (?, ?, ?, ?)";
$stmt = $pdo->prepare($sql);
$stmt->execute([$appointment_id, $valid_until, $refill_count, $notes]);
$presc_id = $pdo->lastInsertId();
```

**What it does:**
- Creates a new prescription record
- Links it to the appointment
- Sets validity period and refill count
- `lastInsertId()` gets the ID of the newly created prescription
- This ID is used to add medicines next

**Why we need lastInsertId():**
- After creating prescription, we need its ID
- We use this ID to add medicines to it
- Like getting a receipt number after making a purchase

### File: `doctor/add_prescription_items.php`

#### Query 1: Get Prescription Info
```php
$stmt = $pdo->prepare("SELECT p.*, a.appointment_id FROM core_prescription p JOIN core_appointment a ON p.appointment_id = a.appointment_id WHERE p.prescription_id = ?");
$stmt->execute([$presc_id]);
$presc = $stmt->fetch();
```

**What it does:**
- Gets prescription details and linked appointment ID
- Verifies prescription exists

#### Query 2: Check if Medicine Exists
```php
$stmt = $pdo->prepare("SELECT medicine_id FROM core_medicine WHERE LOWER(name) = LOWER(?)");
$stmt->execute([$medicine_name]);
$medicine_id = $stmt->fetchColumn();
```

**What it does:**
- Checks if typed medicine name already exists
- Case-insensitive search (Paracetamol = paracetamol)
- Returns medicine_id if found, or nothing if not found

#### Query 3: Create Medicine if Not Exists
```php
if (!$medicine_id) {
    $stmt = $pdo->prepare("INSERT INTO core_medicine (name, type, dosage_info, manufacturer_id) 
                          VALUES (?, 'General', 'As prescribed', ?)");
    $stmt->execute([$medicine_name, $manufacturer_id]);
    $medicine_id = $pdo->lastInsertId();
}
```

**What it does:**
- If medicine doesn't exist, creates it automatically
- Sets default type as "General"
- Links to a default manufacturer
- Gets the new medicine_id

**Auto-creation explained:**
- Doctor types "NewMedicine123"
- System checks: doesn't exist
- System creates it automatically
- Now it can be used in prescription

#### Query 4: Add Medicine to Prescription
```php
$sql = "INSERT INTO core_prescriptionitem 
        (prescription_id, medicine_id, dosage, frequency, duration, quantity, before_after_meal, instructions)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $pdo->prepare($sql);
$stmt->execute([$presc_id, $medicine_id, $dosage, $frequency, $duration, $quantity, $before_after, $instructions]);
```

**What it does:**
- Adds one medicine to the prescription
- Saves all details: how much, how often, when to take, etc.
- Can be called multiple times to add multiple medicines

#### Query 5: Get Existing Prescription Items
```php
$sql = "SELECT pi.*, m.name FROM core_prescriptionitem pi JOIN core_medicine m ON pi.medicine_id = m.medicine_id WHERE pi.prescription_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$presc_id]);
$existing_items = $stmt->fetchAll();
```

**What it does:**
- Gets all medicines already added to this prescription
- Shows them in a list so doctor can see what's already added

### File: `doctor/lab_test_order.php`

#### Query 1: Get Doctor's Hospital Labs
```php
$stmt = $pdo->prepare("SELECT lab_id, lab_name FROM core_lab WHERE hospital_id = ?");
$stmt->execute([$hospital_id]);
$labs = $stmt->fetchAll();
```

**What it does:**
- Gets all labs in doctor's hospital
- Doctor can only order tests from their own hospital's labs

#### Query 2: Get Doctor's Patients
```php
$stmt = $pdo->prepare("
    SELECT DISTINCT p.patient_id, p.full_name
    FROM core_patient p
    INNER JOIN core_appointment a ON p.patient_id = a.patient_id
    WHERE a.doctor_id = ?
    ORDER BY p.full_name
");
$stmt->execute([$doctor_id]);
$patients = $stmt->fetchAll();
```

**What it does:**
- Gets all patients who have had appointments with this doctor
- `DISTINCT` = removes duplicates (if patient had multiple appointments)
- Only shows patients this doctor has seen

#### Query 3: Create Lab Test Order
```php
$sql = "INSERT INTO core_labtest 
        (lab_id, patient_id, test_type, test_cost, ordered_by_id, remarks, status) 
        VALUES (?, ?, ?, ?, ?, ?, 'Ordered')";
$stmt = $pdo->prepare($sql);
$stmt->execute([$lab_id, $patient_id, $test_type, $test_cost, $doctor_id, $remarks]);
```

**What it does:**
- Creates a new lab test order
- Sets status to "Ordered" automatically
- Links to lab, patient, and ordering doctor
- Saves test type, cost, and any remarks

---

## Admin Module - All Queries Explained

### File: `admin/dashboard.php`

#### Query 1: Get Hospital Statistics
```php
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT a.patient_id) as total_patients,
           (SELECT capacity FROM core_hospital WHERE hospital_id = ?) as capacity
    FROM core_appointment a
    WHERE EXISTS (
        SELECT 1 FROM core_doctor d 
        WHERE d.doctor_id = a.doctor_id AND d.hospital_id = ?
    )
");
$stmt->execute([$hospital_id, $hospital_id]);
$stats = $stmt->fetch();
```

**What it does:**
- Counts unique patients who have appointments with doctors from this hospital
- Gets hospital capacity
- `EXISTS` = checks if appointment's doctor belongs to this hospital
- Subquery gets capacity from hospital table

**Why EXISTS?**
- More efficient than JOIN when we just need to check existence
- Like asking "Does this appointment have a doctor from my hospital?"

#### Query 2: Get Departments with Doctor Counts
```php
$sql = "SELECT d.dept_id, d.dept_name, d.floor, COUNT(doct.doctor_id) as doctor_count
        FROM core_department d
        LEFT JOIN core_doctor doct ON d.dept_id = doct.dept_id
        WHERE d.hospital_id = ?
        GROUP BY d.dept_id, d.dept_name, d.floor
        ORDER BY d.dept_name";
$stmt = $pdo->prepare($sql);
$stmt->execute([$hospital_id]);
$departments = $stmt->fetchAll();
```

**What it does:**
- Gets all departments with number of doctors in each
- `LEFT JOIN` = shows departments even if they have no doctors
- `GROUP BY` = groups doctors by department and counts them
- `COUNT()` = counts how many doctors in each group

**GROUP BY explained:**
- Groups rows by department
- Then counts doctors in each group
- Like counting students in each classroom

### File: `admin/departments.php`

#### Query: Get All Departments
```php
$sql = "SELECT d.*, doct.full_name as head_doctor_name
        FROM core_department d
        LEFT JOIN core_doctor doct ON d.head_doctor_id = doct.doctor_id
        WHERE d.hospital_id = ?
        ORDER BY d.dept_name";
$stmt = $pdo->prepare($sql);
$stmt->execute([$hospital_id]);
$departments = $stmt->fetchAll();
```

**What it does:**
- Gets all departments for this hospital
- Shows head doctor name if department has one
- `LEFT JOIN` = shows department even without head doctor

### File: `admin/department_form.php`

#### Query 1: Get Department for Editing
```php
if ($dept_id) {
    $stmt = $pdo->prepare("SELECT * FROM core_department WHERE dept_id = ? AND hospital_id = ?");
    $stmt->execute([$dept_id, $hospital_id]);
    $dept = $stmt->fetch();
}
```

**What it does:**
- If editing, gets existing department data
- Verifies department belongs to admin's hospital (security!)

#### Query 2: Get Available Doctors for Head Doctor
```php
$stmt = $pdo->prepare("SELECT doctor_id, full_name FROM core_doctor WHERE hospital_id = ? ORDER BY full_name");
$stmt->execute([$hospital_id]);
$doctors = $stmt->fetchAll();
```

**What it does:**
- Gets all doctors in hospital
- Used to select a head doctor for the department

#### Query 3: Create or Update Department
```php
if ($dept_id) {
    // UPDATE
    $sql = "UPDATE core_department SET dept_name = ?, floor = ?, head_doctor_id = ?, extension = ?, operating_hours = ? WHERE dept_id = ? AND hospital_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$dept_name, $floor, $head_doctor_id, $extension, $operating_hours, $dept_id, $hospital_id]);
} else {
    // INSERT
    $sql = "INSERT INTO core_department (dept_name, floor, head_doctor_id, extension, operating_hours, hospital_id) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$dept_name, $floor, $head_doctor_id, $extension, $operating_hours, $hospital_id]);
}
```

**What it does:**
- If `dept_id` exists: UPDATE (change existing)
- If no `dept_id`: INSERT (create new)
- Always verifies hospital_id matches (security!)

### File: `admin/doctors.php`

#### Query: Get All Doctors
```php
$sql = "SELECT d.*, dept.dept_name 
        FROM core_doctor d
        LEFT JOIN core_department dept ON d.dept_id = dept.dept_id
        WHERE d.hospital_id = ?
        ORDER BY d.full_name";
$stmt = $pdo->prepare($sql);
$stmt->execute([$hospital_id]);
$doctors = $stmt->fetchAll();
```

**What it does:**
- Gets all doctors in hospital
- Shows their department name
- `LEFT JOIN` = shows doctor even if not assigned to department

### File: `admin/doctor_form.php`

#### Query 1: Check Username Exists
```php
$stmt = $pdo->prepare("SELECT id FROM core_customuser WHERE username = ?");
$stmt->execute([$username]);
if ($stmt->fetch()) {
    $error = "Username already exists.";
}
```

**What it does:**
- Checks if username is already taken
- Prevents duplicate usernames

#### Query 2: Check License Number Exists
```php
$stmt = $pdo->prepare("SELECT doctor_id FROM core_doctor WHERE license_no = ?");
$stmt->execute([$license_no]);
if ($stmt->fetch()) {
    $error = "License number already exists.";
}
```

**What it does:**
- Checks if license number is already used
- Each doctor must have unique license number

#### Query 3: Create User Account
```php
$password_hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT INTO core_customuser (username, password, email, first_name, role, hospital_id) VALUES (?, ?, ?, ?, 'DOCTOR', ?)");
$stmt->execute([$username, $password_hash, $email, $first_name, $hospital_id]);
$user_id = $pdo->lastInsertId();
```

**What it does:**
- Creates login account for new doctor
- Hashes password before storing (security!)
- Sets role as 'DOCTOR'
- Gets new user_id to link with doctor record

#### Query 4: Create Doctor Record
```php
$stmt = $pdo->prepare("INSERT INTO core_doctor (license_no, full_name, specialization, phone, email, experience_yrs, gender, shift_timing, join_date, hospital_id, dept_id, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->execute([$license_no, $full_name, $specialization, $phone, $email, $experience_yrs, $gender, $shift_timing, $join_date, $hospital_id, $dept_id, $user_id]);
```

**What it does:**
- Creates doctor record with all details
- Links to user account via `user_id`
- Links to department via `dept_id`
- Links to hospital via `hospital_id`

**Transaction explained:**
- Both queries (user + doctor) must succeed
- If one fails, both are rolled back
- Like buying two items - either both or neither

### File: `admin/labs.php`

#### Query: Get All Labs
```php
$stmt = $pdo->prepare("SELECT * FROM core_lab WHERE hospital_id = ? ORDER BY lab_name");
$stmt->execute([$hospital_id]);
$labs = $stmt->fetchAll();
```

**What it does:**
- Gets all labs in hospital
- Sorted alphabetically

### File: `admin/lab_form.php`

#### Query: Create or Update Lab
```php
if ($lab_id) {
    $stmt = $pdo->prepare("UPDATE core_lab SET lab_name = ?, location = ?, phone = ? WHERE lab_id = ? AND hospital_id = ?");
    $stmt->execute([$lab_name, $location, $phone, $lab_id, $hospital_id]);
} else {
    $stmt = $pdo->prepare("INSERT INTO core_lab (lab_name, location, phone, hospital_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([$lab_name, $location, $phone, $hospital_id]);
}
```

**What it does:**
- Creates new lab or updates existing one
- Always verifies hospital_id (security!)

### File: `admin/pharmacy_stock.php`

#### Query 1: Get Pharmacies
```php
$stmt = $pdo->prepare("SELECT * FROM core_pharmacy WHERE hospital_id = ?");
$stmt->execute([$hospital_id]);
$pharmacies = $stmt->fetchAll();
```

**What it does:**
- Gets all pharmacies in hospital

#### Query 2: Get Stock Items for Pharmacy
```php
$sql = "SELECT pm.*, m.name as medicine_name, m.type as medicine_type
        FROM core_pharmacymedicine pm
        INNER JOIN core_medicine m ON pm.medicine_id = m.medicine_id
        WHERE pm.pharmacy_id = ?
        ORDER BY m.name";
$stmt = $pdo->prepare($sql);
$stmt->execute([$pharmacy_id]);
$stock_items = $stmt->fetchAll();
```

**What it does:**
- Gets all medicines in selected pharmacy
- Shows medicine names and types
- Shows stock quantity, price, expiry date

### File: `admin/stock_form.php`

#### Query: Update Stock Item
```php
$stmt = $pdo->prepare("UPDATE core_pharmacymedicine SET stock_quantity = ?, unit_price = ?, expiry_date = ? WHERE pharmacy_medicine_id = ?");
$stmt->execute([$quantity, $price, $expiry_date, $stock_id]);
```

**What it does:**
- Updates stock quantity, price, or expiry date
- Used when restocking or price changes

---

## How Data Flows Through the System

### Example 1: Patient Books Appointment

1. **Patient clicks "Book Appointment"**
   - Goes to `patient/book_appointment.php`

2. **Selects Hospital**
   - Query runs: `SELECT hospital_id, name FROM core_hospital`
   - Shows list of hospitals

3. **Selects Specialization**
   - Query runs: `SELECT DISTINCT specialization FROM core_doctor WHERE hospital_id = ?`
   - Shows available specializations

4. **Selects Doctor**
   - Query runs: `SELECT doctor_id, full_name FROM core_doctor WHERE hospital_id = ? AND specialization = ?`
   - Shows available doctors

5. **Selects Date**
   - AJAX call to `check_availability.php`
   - Query: `SELECT TIME(date_and_time) FROM core_appointment WHERE doctor_id = ? AND DATE(date_and_time) = ?`
   - Returns booked times, JavaScript shows available slots

6. **Submits Form**
   - Query: `INSERT INTO core_appointment (...) VALUES (...)`
   - Creates appointment record
   - Status set to 'Scheduled'

7. **Appointment Appears**
   - In patient's dashboard: `SELECT ... FROM core_appointment WHERE patient_id = ? AND status != 'Completed'`
   - In doctor's dashboard: `SELECT ... FROM core_appointment WHERE doctor_id = ? AND DATE(date_and_time) = TODAY`

### Example 2: Doctor Creates Prescription

1. **Doctor views appointment**
   - Query: `SELECT a.*, p.* FROM core_appointment a JOIN core_patient p ...`
   - Shows patient info and appointment details

2. **Doctor clicks "Create Prescription"**
   - Goes to `doctor/prescription_form.php`
   - Fills validity date, refills, notes

3. **Submits Prescription Form**
   - Query: `INSERT INTO core_prescription (...) VALUES (...)`
   - Creates prescription record
   - Gets prescription_id

4. **Add Medicines**
   - Goes to `doctor/add_prescription_items.php`
   - Doctor types medicine name

5. **System Checks Medicine**
   - Query: `SELECT medicine_id FROM core_medicine WHERE name = ?`
   - If not found, creates it: `INSERT INTO core_medicine (...)`

6. **Adds Medicine to Prescription**
   - Query: `INSERT INTO core_prescriptionitem (...) VALUES (...)`
   - Links medicine to prescription with dosage, frequency, etc.

7. **Patient Sees Prescription**
   - Query: `SELECT p.*, pi.*, m.name FROM core_prescription p JOIN core_prescriptionitem pi JOIN core_medicine m ...`
   - Shows all medicines with details

### Example 3: Login Process

1. **User enters username/password**
   - Form submits to `login.php`

2. **Check Username**
   - Query: `SELECT * FROM core_customuser WHERE username = ?`
   - Gets user record if exists

3. **Verify Password**
   - Function: `verifyPassword($password, $user['password'])`
   - Compares hashed passwords

4. **Set Session**
   - `$_SESSION['user_id'] = $user['id']`
   - `$_SESSION['role'] = $user['role']`
   - `$_SESSION['hospital_id'] = $user['hospital_id']`

5. **Redirect to Dashboard**
   - `dashboard.php` checks role
   - Redirects to appropriate module

---

## Key Concepts Explained Simply

### JOINs Explained

**INNER JOIN:**
- Shows only rows that match in BOTH tables
- Like: "Show me students who have grades"
- If student has no grades, they don't appear

**LEFT JOIN:**
- Shows all rows from left table, even if no match in right
- Like: "Show me all students, and their grades if they have any"
- If student has no grades, they still appear (with NULL grades)

**Example:**
```sql
-- INNER JOIN: Only appointments with doctors
SELECT * FROM appointment a
INNER JOIN doctor d ON a.doctor_id = d.doctor_id
-- If appointment has no doctor, it won't show

-- LEFT JOIN: All appointments, even without doctors
SELECT * FROM appointment a
LEFT JOIN doctor d ON a.doctor_id = d.doctor_id
-- If appointment has no doctor, it still shows (doctor fields are NULL)
```

### GROUP BY Explained

Groups rows together and can count/sum them.

**Example:**
```sql
SELECT department, COUNT(*) as doctor_count
FROM doctor
GROUP BY department
```

**What happens:**
- Groups all doctors by department
- Counts how many in each group
- Result: "Cardiology: 5 doctors", "Neurology: 3 doctors"

### Prepared Statements (Why They're Safe)

**Bad (DANGEROUS):**
```php
$query = "SELECT * FROM users WHERE username = '$username'";
// If $username = "admin' OR '1'='1"
// Query becomes: SELECT * FROM users WHERE username = 'admin' OR '1'='1'
// This returns ALL users! (SQL Injection attack)
```

**Good (SAFE):**
```php
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);
// The ? is replaced safely, no matter what $username contains
// Even if hacker tries to inject code, it's treated as plain text
```

### Password Hashing

**Why hash passwords?**
- We NEVER store passwords as plain text
- Instead, we store a "hash" (like a fingerprint)

**How it works:**
1. User registers with password "mypass123"
2. System hashes it: `password_hash("mypass123")` → "a3f5b2c1d4e6..."
3. Stores hash in database
4. When user logs in with "mypass123"
5. System hashes input: `password_hash("mypass123")` → "a3f5b2c1d4e6..."
6. Compares: stored hash == input hash? ✅ Login!

**Why this is safe:**
- Even if hacker gets database, they see hashes, not passwords
- Can't reverse hash to get original password
- Like a fingerprint - you can't recreate the person from fingerprint

---

## Summary: Most Common Query Patterns

### Pattern 1: Get One Record
```php
$stmt = $pdo->prepare("SELECT * FROM table WHERE id = ?");
$stmt->execute([$id]);
$result = $stmt->fetch(); // Gets one row
```

### Pattern 2: Get Multiple Records
```php
$stmt = $pdo->prepare("SELECT * FROM table WHERE condition = ?");
$stmt->execute([$value]);
$results = $stmt->fetchAll(); // Gets all matching rows
```

### Pattern 3: Create New Record
```php
$stmt = $pdo->prepare("INSERT INTO table (col1, col2) VALUES (?, ?)");
$stmt->execute([$value1, $value2]);
$new_id = $pdo->lastInsertId(); // Gets ID of new record
```

### Pattern 4: Update Record
```php
$stmt = $pdo->prepare("UPDATE table SET col1 = ?, col2 = ? WHERE id = ?");
$stmt->execute([$value1, $value2, $id]);
```

### Pattern 5: Delete Record
```php
$stmt = $pdo->prepare("DELETE FROM table WHERE id = ?");
$stmt->execute([$id]);
```

### Pattern 6: Join Tables
```php
$stmt = $pdo->prepare("
    SELECT a.*, b.name 
    FROM table_a a
    JOIN table_b b ON a.b_id = b.id
    WHERE a.condition = ?
");
$stmt->execute([$value]);
$results = $stmt->fetchAll();
```

---

## Tips for Understanding Queries

1. **Read FROM first** - Start with which table(s) you're getting data from
2. **Then WHERE** - What conditions filter the data
3. **Then SELECT** - What columns you want to see
4. **Then JOIN** - How tables connect
5. **Then ORDER BY** - How results are sorted

**Example:**
```sql
SELECT name, age           -- 3. What to show
FROM users                 -- 1. Where to get from
WHERE age > 18             -- 2. Filter condition
ORDER BY name              -- 4. How to sort
```

---

## Common Mistakes to Avoid

1. **Forgetting WHERE clause in UPDATE/DELETE**
   - Bad: `UPDATE users SET name='John'` (updates ALL users!)
   - Good: `UPDATE users SET name='John' WHERE id=1`

2. **Not using prepared statements**
   - Always use `?` placeholders, never string concatenation

3. **Not checking if record exists**
   - Always check before updating/deleting

4. **Forgetting to verify ownership**
   - Always check if record belongs to logged-in user/hospital

---

**End of Documentation**

This guide covers all major SQL queries and backend functions in the system. Study each section, and you'll understand how everything works! 🎓

