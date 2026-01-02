# Healthcare Management System - Project Explanation

This document provides a comprehensive explanation of the Healthcare Management System project, designed to help you understand the codebase and explain it to faculty members.

## Table of Contents

1. [Project Overview](#project-overview)
2. [Architecture](#architecture)
3. [File Structure](#file-structure)
4. [File-by-File Explanation](#file-by-file-explanation)
5. [Data Flow](#data-flow)
6. [Database Schema](#database-schema)
7. [User Roles and Permissions](#user-roles-and-permissions)
8. [Key Workflows](#key-workflows)
9. [Security Features](#security-features)

---

## Project Overview

The Healthcare Management System is a web-based application built with PHP and MySQL that manages hospital operations for three types of users:

- **Hospital Administrators**: Manage hospital resources (departments, labs, doctors, pharmacy stock)
- **Doctors**: View appointments, create prescriptions, order lab tests
- **Patients**: View their medical records, appointments, prescriptions, and bills

The system uses a role-based access control (RBAC) mechanism where each user type has specific permissions and can only access data relevant to their role.

---

## Architecture

### Technology Stack

- **Backend**: PHP 7.4+ (Server-side scripting)
- **Database**: MySQL/MariaDB (Relational database)
- **Frontend**: HTML, CSS (Tailwind CSS), JavaScript
- **Session Management**: PHP Sessions
- **Database Access**: PDO (PHP Data Objects) with prepared statements

### Design Pattern

The project follows a **modular MVC-like structure**:
- **Model**: Database tables and PDO queries
- **View**: PHP files with embedded HTML templates
- **Controller**: PHP files handling business logic and routing

### Key Principles

1. **Separation of Concerns**: Core files (`config.php`, `db.php`, `functions.php`) handle common functionality
2. **Role-Based Access**: Each module (admin, doctor, patient) is isolated
3. **Security First**: All database queries use prepared statements to prevent SQL injection
4. **Session-Based Authentication**: User authentication and authorization handled via PHP sessions

---

## File Structure

```
.
├── Core Files (Configuration & Utilities)
│   ├── config.php              # Database and application configuration
│   ├── db.php                  # Database connection and helper functions
│   ├── functions.php           # Shared utility functions
│   ├── index.php               # Entry point (redirects to login)
│   ├── login.php               # User login page
│   ├── register.php            # Patient registration
│   ├── dashboard.php           # Role-based dashboard router
│   ├── logout.php              # Logout handler
│   ├── setup_db.php            # Database initialization
│   └── seed_data.php           # Sample data seeding
│
├── Admin Module (admin/)
│   ├── dashboard.php           # Admin dashboard with statistics
│   ├── departments.php          # List all departments
│   ├── department_form.php      # Add/Edit department
│   ├── doctors.php              # List all doctors
│   ├── doctor_form.php          # Add new doctor
│   ├── labs.php                 # List all labs
│   ├── lab_form.php             # Add new lab
│   ├── pharmacy_stock.php       # View pharmacy stock
│   └── stock_form.php           # Update stock item
│
├── Doctor Module (doctor/)
│   ├── dashboard.php            # Doctor dashboard with today's schedule
│   ├── appointments.php         # List all appointments
│   ├── appointment_detail.php   # View/Update appointment details
│   ├── prescription_form.php    # Create new prescription
│   ├── add_prescription_items.php # Add medicines to prescription
│   └── lab_test_order.php       # Order lab test for patient
│
├── Patient Module (patient/)
│   ├── dashboard.php            # Patient dashboard
│   ├── profile.php              # View patient profile
│   ├── appointments.php         # View appointment history
│   ├── appointment_detail.php   # View appointment details
│   └── bills.php                # View bills
│
├── Templates (templates/)
│   ├── header.php               # Common header with navigation
│   └── footer.php               # Common footer
│
└── Static Assets (static/)
    └── css/
        └── style.css            # Custom CSS styles
```

---

## File-by-File Explanation

### Core Files

#### `config.php`
**Purpose**: Central configuration file for database and application settings.

**What it does**:
- Defines database connection constants (host, name, user, password)
- Sets application-wide constants (site name, base URL, debug mode)
- Configures timezone
- Starts PHP session

**Key Constants**:
- `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`: Database connection details
- `SITE_NAME`: Application name
- `BASE_URL`: Base URL for redirects

#### `db.php`
**Purpose**: Database connection and authentication helper functions.

**What it does**:
- Establishes PDO connection to MySQL database
- Provides helper functions for authentication and authorization
- Handles flash messages for user feedback

**Key Functions**:
- `isLoggedIn()`: Checks if user is logged in
- `requireLogin()`: Redirects to login if not authenticated
- `requireRole($role)`: Ensures user has specific role (ADMIN, DOCTOR, PATIENT)
- `setFlash($message, $type)`: Sets flash message for next page load
- `getFlash()`: Retrieves and clears flash message
- `verifyPassword($password, $hash)`: Verifies password (supports multiple hash formats)

#### `functions.php`
**Purpose**: Shared utility functions used across the application.

**What it does**:
- Provides role checking function
- Formats data for display (currency, dates)
- Generates CSS classes for status badges

**Key Functions**:
- `checkRole($required_role)`: Checks user role and shows error if unauthorized
- `formatCurrency($amount)`: Formats numbers as currency (BDT)
- `formatDate($date_string)`: Formats date as "Jan 01, 2024"
- `formatDateTime($date_string)`: Formats datetime as "Jan 01, 2024 10:30 AM"
- `getStatusBadgeClass($status)`: Returns Tailwind CSS classes for status badges

#### `index.php`
**Purpose**: Entry point of the application.

**What it does**:
- Redirects all requests to `login.php`

#### `login.php`
**Purpose**: User authentication page.

**What it does**:
- Displays login form (username and password)
- Validates user credentials against database
- Creates session with user information (user_id, username, role, hospital_id)
- Redirects to appropriate dashboard based on role

**Flow**:
1. User submits login form
2. Query database for user with matching username
3. Verify password using `verifyPassword()`
4. If valid, set session variables and redirect to `dashboard.php`
5. If invalid, show error message

#### `register.php`
**Purpose**: Patient registration page.

**What it does**:
- Displays registration form with patient information
- Validates input (password match, unique username, unique national ID)
- Creates user account and patient record in database
- Uses database transaction to ensure data consistency

**Key Features**:
- Password hashing using `password_hash()`
- Transaction handling (rollback on error)
- Validation for duplicate username and national ID

#### `dashboard.php`
**Purpose**: Role-based dashboard router.

**What it does**:
- Checks user role from session
- Redirects to appropriate dashboard:
  - ADMIN → `/admin/dashboard.php`
  - DOCTOR → `/doctor/dashboard.php`
  - PATIENT → `/patient/dashboard.php`

#### `logout.php`
**Purpose**: User logout handler.

**What it does**:
- Destroys current session
- Sets flash message for successful logout
- Redirects to login page

#### `setup_db.php`
**Purpose**: Database initialization script.

**What it does**:
- Creates database `healthcare_db` if it doesn't exist
- Creates all required tables (22+ tables)
- Seeds initial data (one district, one hospital, one admin user)

**Usage**: Run once via command line: `php setup_db.php`

#### `seed_data.php`
**Purpose**: Populates database with sample data.

**What it does**:
- Adds multiple districts, hospitals (public and private)
- Creates departments for hospitals
- Adds sample doctors and patients
- Sets default password `password123` for all users

**Usage**: Run after `setup_db.php`: `php seed_data.php`

---

### Admin Module Files

#### `admin/dashboard.php`
**Purpose**: Admin dashboard showing hospital statistics.

**What it displays**:
- Patient load vs hospital capacity
- Active departments with doctor counts
- Recent billing summaries

**Key Queries**:
- Total patients: Counts distinct patients from appointments
- Departments: Lists all departments with doctor counts
- Recent bills: Shows last 5 bills for hospital's patients

#### `admin/departments.php`
**Purpose**: Lists all departments in the hospital.

**What it does**:
- Fetches all departments for the logged-in admin's hospital
- Displays department name, floor, extension, operating hours
- Provides link to add/edit departments

#### `admin/department_form.php`
**Purpose**: Add or edit department.

**What it does**:
- If `id` parameter provided: Edit existing department
- If no `id`: Create new department
- Validates department belongs to admin's hospital
- Updates or inserts department record

#### `admin/doctors.php`
**Purpose**: Lists all doctors in the hospital.

**What it does**:
- Fetches all doctors with their department information
- Displays doctor name, specialization, department, phone, shift
- Provides link to add new doctor

#### `admin/doctor_form.php`
**Purpose**: Add new doctor to the system.

**What it does**:
- Creates both user account and doctor record
- Validates unique username and license number
- Uses database transaction to ensure both records created
- Links doctor to department and hospital

**Key Process**:
1. Create user account in `core_customuser` table
2. Create doctor record in `core_doctor` table
3. Link doctor to user via `user_id`

#### `admin/labs.php` and `admin/lab_form.php`
**Purpose**: Manage laboratory facilities.

**What it does**:
- Lists all labs in the hospital
- Allows adding new labs with name, location, and phone

#### `admin/pharmacy_stock.php`
**Purpose**: View and manage pharmacy stock.

**What it does**:
- Lists all pharmacies in the hospital
- Shows stock items for selected pharmacy
- Displays medicine name, quantity, unit price, expiry date
- Provides link to update stock

#### `admin/stock_form.php`
**Purpose**: Update pharmacy stock item.

**What it does**:
- Updates stock quantity, unit price, and expiry date
- Validates stock item belongs to admin's hospital

---

### Doctor Module Files

#### `doctor/dashboard.php`
**Purpose**: Doctor dashboard showing today's schedule.

**What it displays**:
- Today's appointments with patient names and symptoms
- Quick actions (view full schedule, order lab test)
- Doctor status information

**Key Query**:
- Fetches appointments for today where doctor_id matches logged-in doctor

#### `doctor/appointments.php`
**Purpose**: Lists all appointments for the doctor.

**What it does**:
- Shows all appointments (past and future)
- Allows filtering by status (Scheduled, Completed, etc.)
- Displays appointment date, patient name, reason, status

#### `doctor/appointment_detail.php`
**Purpose**: View and update appointment details.

**What it does**:
- Displays patient information (name, DOB, gender, blood type)
- Shows visit information (date, type, reason, symptoms)
- Allows updating appointment status and diagnosis
- Shows all prescriptions for the appointment

**Key Features**:
- Doctor can update status (Scheduled → Completed)
- Add diagnosis notes
- Set follow-up date
- View all prescriptions with medicine details

#### `doctor/prescription_form.php`
**Purpose**: Create new prescription for an appointment.

**What it does**:
- Creates prescription record linked to appointment
- Sets validity period (valid_until date)
- Sets refill count and notes
- Redirects to add prescription items page

#### `doctor/add_prescription_items.php`
**Purpose**: Add medicines to a prescription.

**What it does**:
- Displays form to add medicine with dosage, frequency, duration
- Shows existing items in the prescription
- Allows adding multiple medicines to one prescription

**Key Fields**:
- Medicine selection (dropdown from `core_medicine` table)
- Dosage (e.g., "500mg")
- Frequency (e.g., "1-0-1" meaning morning, noon, evening)
- Duration (e.g., "7 days")
- Quantity
- Before/After meal instructions

#### `doctor/lab_test_order.php`
**Purpose**: Order lab test for a patient.

**What it does**:
- Selects patient and lab
- Enters test type, cost, and remarks
- Creates lab test record with status "Ordered"
- Links test to ordering doctor

---

### Patient Module Files

#### `patient/dashboard.php`
**Purpose**: Patient dashboard with overview.

**What it displays**:
- Upcoming appointments (next 3)
- Recent billing history (last 5 bills)
- Recent prescriptions with medicine details

#### `patient/profile.php`
**Purpose**: View patient profile information.

**What it displays**:
- Personal information (name, DOB, gender, blood type)
- Contact information (phone, email, address)
- Emergency contacts
- Medical information (marital status, occupation, etc.)

#### `patient/appointments.php`
**Purpose**: View all appointments.

**What it displays**:
- All appointments with doctor name, specialization, hospital
- Appointment date, status, reason
- Link to view detailed appointment information

#### `patient/appointment_detail.php`
**Purpose**: View detailed appointment information.

**What it displays**:
- Doctor and hospital information
- Visit details (date, status, type, reason, symptoms)
- Diagnosis (if available)
- All prescriptions with medicine details

#### `patient/bills.php`
**Purpose**: View all bills.

**What it displays**:
- All bills with service type, date, amount, status
- Separates standard bills and pharmacy bills
- Shows payment status (Pending, Paid, etc.)

---

### Template Files

#### `templates/header.php`
**Purpose**: Common header for all pages.

**What it includes**:
- HTML head section (meta tags, CSS, JavaScript)
- Navigation bar with role-based menu items
- User information and logout button
- Flash message display

**Key Features**:
- Role-based navigation (different menu for ADMIN, DOCTOR, PATIENT)
- Shows logged-in user's name and role
- Displays flash messages (success/error)

#### `templates/footer.php`
**Purpose**: Common footer for all pages.

**What it includes**:
- Copyright information
- Closing HTML tags

---

## Data Flow

### User Login Flow

```
1. User visits index.php
   ↓
2. Redirected to login.php
   ↓
3. User enters credentials
   ↓
4. login.php queries core_customuser table
   ↓
5. Password verified using verifyPassword()
   ↓
6. Session created with user_id, username, role, hospital_id
   ↓
7. Redirected to dashboard.php
   ↓
8. dashboard.php checks role and redirects to:
   - ADMIN → /admin/dashboard.php
   - DOCTOR → /doctor/dashboard.php
   - PATIENT → /patient/dashboard.php
```

### Appointment Management Flow

```
1. Doctor views appointments (doctor/appointments.php)
   ↓
2. Clicks on appointment to view details
   ↓
3. doctor/appointment_detail.php displays:
   - Patient information
   - Visit details
   - Existing prescriptions
   ↓
4. Doctor can:
   - Update appointment status
   - Add diagnosis
   - Create prescription
   ↓
5. If creating prescription:
   - doctor/prescription_form.php creates prescription record
   - Redirects to doctor/add_prescription_items.php
   - Doctor adds medicines one by one
   ↓
6. Patient views appointment:
   - patient/appointment_detail.php shows all information
   - Displays prescriptions with medicine details
```

### Prescription Creation Flow

```
1. Doctor clicks "Create Prescription" on appointment detail page
   ↓
2. doctor/prescription_form.php:
   - Creates record in core_prescription table
   - Links to appointment_id
   - Sets validity period
   ↓
3. Redirects to doctor/add_prescription_items.php
   ↓
4. Doctor adds medicines:
   - Selects medicine from dropdown
   - Enters dosage, frequency, duration, quantity
   - Submits form
   ↓
5. Each submission creates record in core_prescriptionitem table
   ↓
6. Doctor can add multiple medicines
   ↓
7. When done, returns to appointment detail page
```

### Lab Test Ordering Flow

```
1. Doctor clicks "Order Lab Test" from dashboard
   ↓
2. doctor/lab_test_order.php displays form
   ↓
3. Doctor selects:
   - Patient
   - Lab (from hospital's labs)
   - Test type
   - Cost
   - Remarks
   ↓
4. Submits form
   ↓
5. Creates record in core_labtest table with status "Ordered"
   ↓
6. Test can later be updated to "Completed" (triggers billing)
```

---

## Database Schema

### Key Tables

#### User Management
- **core_customuser**: User accounts with role (ADMIN, DOCTOR, PATIENT)
- **core_patient**: Patient information linked to user account
- **core_doctor**: Doctor information linked to user account

#### Hospital Structure
- **core_hospital**: Base hospital information
- **core_publichospital**: Public hospital specific fields (government funding)
- **core_privatehospital**: Private hospital specific fields (owner, profit margin)
- **core_department**: Hospital departments
- **core_lab**: Laboratory facilities
- **core_pharmacy**: Pharmacy facilities

#### Clinical Data
- **core_appointment**: Patient appointments with doctors
- **core_prescription**: Prescriptions linked to appointments
- **core_prescriptionitem**: Individual medicines in prescriptions
- **core_labtest**: Lab test orders

#### Pharmacy
- **core_medicine**: Medicine catalog
- **core_manufacturer**: Medicine manufacturers
- **core_pharmacymedicine**: Stock items (medicine + pharmacy + quantity + price)
- **core_pharmacybill**: Pharmacy bills

#### Billing
- **core_bill**: General bills (lab tests, services)
- **core_servicetype**: Types of services (Lab Test, Consultation, etc.)

### Relationships

- **Hospital → Departments**: One-to-many
- **Hospital → Doctors**: One-to-many
- **Hospital → Labs**: One-to-many
- **Hospital → Pharmacies**: One-to-many
- **Doctor → Appointments**: One-to-many
- **Patient → Appointments**: One-to-many
- **Appointment → Prescriptions**: One-to-many
- **Prescription → PrescriptionItems**: One-to-many
- **Patient → Bills**: One-to-many

---

## User Roles and Permissions

### ADMIN (Hospital Administrator)

**Permissions**:
- ✅ View hospital dashboard with statistics
- ✅ Manage departments (add, edit)
- ✅ Manage doctors (add, view list)
- ✅ Manage labs (add, view list)
- ✅ Manage pharmacy stock (view, update)

**Restrictions**:
- ❌ Can only access data for their assigned hospital
- ❌ Cannot view patient medical records
- ❌ Cannot create prescriptions or appointments

**Session Data**:
- `user_id`: Admin user ID
- `role`: "ADMIN"
- `hospital_id`: Hospital they manage

### DOCTOR

**Permissions**:
- ✅ View today's schedule
- ✅ View all appointments
- ✅ Update appointment status and diagnosis
- ✅ Create prescriptions
- ✅ Add medicines to prescriptions
- ✅ Order lab tests

**Restrictions**:
- ❌ Can only see their own appointments
- ❌ Cannot access other doctors' appointments
- ❌ Cannot manage hospital resources
- ❌ Cannot view patient bills

**Session Data**:
- `user_id`: Doctor user ID
- `role`: "DOCTOR"
- `hospital_id`: Hospital they work at

### PATIENT

**Permissions**:
- ✅ View own profile
- ✅ View appointment history
- ✅ View appointment details
- ✅ View prescriptions
- ✅ View bills

**Restrictions**:
- ❌ Cannot create appointments
- ❌ Cannot view other patients' data
- ❌ Cannot access admin or doctor features
- ❌ Read-only access to own data

**Session Data**:
- `user_id`: Patient user ID
- `role`: "PATIENT"
- `hospital_id`: NULL (patients not tied to specific hospital)

---

## Key Workflows

### 1. Patient Registration Workflow

```
1. Patient visits register.php
   ↓
2. Fills registration form:
   - Account info (username, password, email)
   - Personal info (name, DOB, gender, blood type)
   - Contact info (phone, address)
   - Family info (father, mother names)
   ↓
3. System validates:
   - Username unique
   - National ID unique
   - Password match
   ↓
4. Database transaction:
   - Creates user in core_customuser (role: PATIENT)
   - Creates patient in core_patient
   - Links patient to user via user_id
   ↓
5. Redirects to login.php with success message
```

### 2. Doctor Consultation Workflow

```
1. Doctor views today's schedule
   ↓
2. Clicks on appointment
   ↓
3. Views patient information and symptoms
   ↓
4. Updates appointment:
   - Changes status to "Completed"
   - Adds diagnosis
   - Sets follow-up date if needed
   ↓
5. Creates prescription:
   - Sets validity period
   - Adds notes
   ↓
6. Adds medicines:
   - Selects medicine
   - Sets dosage, frequency, duration
   - Repeats for multiple medicines
   ↓
7. Patient can view prescription later
```

### 3. Lab Test Workflow

```
1. Doctor orders lab test:
   - Selects patient
   - Selects lab
   - Enters test type and cost
   ↓
2. Lab test created with status "Ordered"
   ↓
3. Lab completes test:
   - Updates status to "Completed"
   - Enters results
   ↓
4. System automatically:
   - Creates bill in core_bill table
   - Links to patient
   - Sets amount from test cost
   ↓
5. Patient can view bill in patient/bills.php
```

### 4. Pharmacy Stock Management Workflow

```
1. Admin views pharmacy stock (admin/pharmacy_stock.php)
   ↓
2. Selects pharmacy from dropdown
   ↓
3. Views all stock items:
   - Medicine name
   - Current quantity
   - Unit price
   - Expiry date
   ↓
4. Clicks "Update" on stock item
   ↓
5. Updates:
   - Stock quantity
   - Unit price
   - Expiry date
   ↓
6. Changes saved to core_pharmacymedicine table
```

---

## Security Features

### 1. SQL Injection Prevention

**Method**: Prepared Statements with PDO

**Example**:
```php
$stmt = $pdo->prepare("SELECT * FROM core_customuser WHERE username = ?");
$stmt->execute([$username]);
```

**Why it's secure**: User input is never directly concatenated into SQL queries. PDO handles escaping and parameter binding.

### 2. Session-Based Authentication

**Method**: PHP Sessions

**How it works**:
- User logs in → Session created with user_id, role, hospital_id
- Each page checks session for authentication
- Session destroyed on logout

**Protection**: Prevents unauthorized access to pages without valid session.

### 3. Role-Based Access Control

**Method**: `requireRole()` and `checkRole()` functions

**How it works**:
- Each protected page calls `requireRole('ADMIN')` or `checkRole('DOCTOR')`
- Function checks session for correct role
- Redirects or shows error if role doesn't match

**Example**:
```php
requireRole('ADMIN'); // Only ADMIN can access
```

### 4. Password Hashing

**Method**: PHP `password_hash()` with bcrypt

**How it works**:
- Passwords hashed using `password_hash($password, PASSWORD_DEFAULT)`
- Stored hash never reveals original password
- Verification using `password_verify()` or custom `verifyPassword()`

### 5. Hospital Data Isolation

**Method**: Hospital ID filtering

**How it works**:
- Admin and Doctor sessions include `hospital_id`
- All queries filter by `hospital_id`
- Prevents cross-hospital data access

**Example**:
```php
$stmt = $pdo->prepare("SELECT * FROM core_department WHERE hospital_id = ?");
$stmt->execute([$_SESSION['hospital_id']]);
```

### 6. Input Validation and Sanitization

**Method**: `htmlspecialchars()` for output

**How it works**:
- All user input displayed using `htmlspecialchars()`
- Prevents XSS (Cross-Site Scripting) attacks
- Validates required fields before database insertion

---

## Common Patterns in the Code

### 1. Page Structure Pattern

Most pages follow this structure:

```php
<?php
require_once '../db.php';  // or '../functions.php'
requireRole('ROLE');       // Check authentication

// Get data from database
$stmt = $pdo->prepare("SELECT ...");
$stmt->execute([...]);
$data = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process form data
    // Insert/Update database
    // Redirect with flash message
}

include '../templates/header.php';
?>
<!-- HTML content -->
<?php include '../templates/footer.php'; ?>
```

### 2. Flash Message Pattern

Used for user feedback:

```php
// Set message
setFlash("Operation successful!", "success");

// Display in template
if (isset($_SESSION['flash'])) {
    $msg = $_SESSION['flash'];
    unset($_SESSION['flash']);
    echo $msg['message'];
}
```

### 3. Form Handling Pattern

```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $field1 = $_POST['field1'];
    $field2 = $_POST['field2'];
    
    // Validate
    if (empty($field1)) {
        $error = "Field required";
    } else {
        // Insert/Update database
        $stmt = $pdo->prepare("INSERT INTO ...");
        $stmt->execute([...]);
        
        setFlash("Success!");
        header("Location: /page.php");
        exit;
    }
}
```

---

## Learning Points for Faculty Presentation

### 1. Database Design
- Multi-table inheritance (Hospital → PublicHospital/PrivateHospital)
- Foreign key relationships
- Normalization (separate tables for users, patients, doctors)

### 2. Security Best Practices
- Prepared statements prevent SQL injection
- Session management for authentication
- Role-based access control
- Input sanitization

### 3. Code Organization
- Modular structure (admin, doctor, patient modules)
- Shared utilities (functions.php, db.php)
- Template reuse (header.php, footer.php)

### 4. User Experience
- Role-based dashboards
- Flash messages for feedback
- Responsive design with Tailwind CSS

### 5. Business Logic
- Appointment management
- Prescription workflow
- Lab test ordering
- Stock management

---

## Conclusion

This Healthcare Management System demonstrates:

- **Backend Development**: PHP server-side scripting
- **Database Management**: MySQL with complex relationships
- **Security**: Authentication, authorization, SQL injection prevention
- **User Interface**: HTML, CSS, responsive design
- **Software Engineering**: Modular code structure, separation of concerns

The system is production-ready with proper error handling, security measures, and user-friendly interfaces for all three user roles.

