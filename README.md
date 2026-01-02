# Healthcare Management System

A comprehensive PHP-based healthcare management system with MySQL backend, implementing role-based access control for Hospital Admins, Doctors, and Patients.

## Features

### For Hospital Admins
- Manage departments, labs, and doctors
- Update pharmacy stock
- View hospital statistics and appointment analytics
- Add new doctors with login credentials

### For Doctors
- View and manage appointments
- Create prescriptions with multiple medicines
- Order lab tests for patients
- Update diagnosis and appointment status

### For Patients
- Register and create account
- View medical profile with blood type and emergency contacts
- View appointment history
- Access prescriptions
- View and track bills

## Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL/MariaDB
- **Frontend**: HTML, CSS (Tailwind), JavaScript
- **Authentication**: Session-based with role-based access control
- **Password Hashing**: PHP password_hash (with Werkzeug PBKDF2 support)

## Prerequisites

- PHP 7.4+ (or PHP 8)
- MySQL/MariaDB server (8.0+)
- Web server (Apache/Nginx) or PHP built-in server

## Installation

### 1. Clone the Repository

```bash
git clone <repository-url>
cd "GRAND FInale - Copy"
```

### 2. Configure Database

Edit `config.php` and update the database configuration:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'healthcare_db');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### 3. Setup Database

Run the database setup script:

```bash
php setup_db.php
```

This will create the database and all necessary tables.

### 4. Seed Initial Data (Optional)

To populate the database with sample data:

```bash
php seed_data.php
```

This creates sample hospitals, doctors, and patients. Default password for all users: `password123`

### 5. Run the Application

Using PHP built-in server:

```bash
php -S localhost:8000
```

Or configure your web server (Apache/Nginx) to point to the project directory.

Access the application at: **http://localhost:8000**

## Default Login Credentials

After running `seed_data.php`, you can login with:

- **Admin**: username=`admin`, password=`admin123`
- **Doctor**: username=`dr_rahman`, password=`password123`
- **Patient**: username=`patient1`, password=`password123`

## User Roles

### Hospital Admin (ADMIN)
- Manage hospital resources (departments, labs, doctors, pharmacy stock)
- Can only access their assigned hospital's data

### Doctor (DOCTOR)
- View appointments, create prescriptions, order lab tests
- Can only see their own appointments and patients

### Patient (PATIENT)
- View profile, appointments, prescriptions, and bills
- Read-only access to their own medical data

## Project Structure

```
.
├── config.php              # Database and app configuration
├── db.php                  # Database connection and helper functions
├── functions.php           # Shared utility functions
├── index.php               # Entry point (redirects to login)
├── login.php               # Login page
├── register.php            # Patient registration
├── dashboard.php           # Role-based dashboard router
├── logout.php              # Logout handler
├── setup_db.php            # Database initialization script
├── seed_data.php           # Sample data seeding script
├── admin/                  # Admin module
│   ├── dashboard.php
│   ├── departments.php
│   ├── department_form.php
│   ├── doctors.php
│   ├── doctor_form.php
│   ├── labs.php
│   ├── lab_form.php
│   ├── pharmacy_stock.php
│   └── stock_form.php
├── doctor/                 # Doctor module
│   ├── dashboard.php
│   ├── appointments.php
│   ├── appointment_detail.php
│   ├── prescription_form.php
│   ├── add_prescription_items.php
│   └── lab_test_order.php
├── patient/                # Patient module
│   ├── dashboard.php
│   ├── profile.php
│   ├── appointments.php
│   ├── appointment_detail.php
│   └── bills.php
├── templates/              # Shared templates
│   ├── header.php
│   └── footer.php
└── static/                 # Static assets
    └── css/
        └── style.css
```

## Database Schema

The system implements 22+ entities including:

- **User Management**: CustomUser with role-based access
- **Hospital**: Multi-table inheritance (Hospital → PublicHospital/PrivateHospital)
- **Medical Staff**: Doctor, DoctorQualification
- **Patients**: Patient, PatientEmergencyContact
- **Clinical**: Appointment, Prescription, PrescriptionItem, LabTest
- **Pharmacy**: Medicine, Pharmacy, PharmacyMedicine, PharmacyBill
- **Billing**: Bill
- **Reference**: District, Qualification, Manufacturer, ServiceType

## Security Features

- Session-based authentication
- Role-based access control
- Parameterized SQL queries (SQL injection prevention)
- Password hashing
- Hospital data isolation

## Troubleshooting

### MySQL Connection Error
- Ensure MySQL server is running
- Check database credentials in `config.php`
- Verify database exists: `SHOW DATABASES;`

### Port Already in Use
- Change port: `php -S localhost:8001`

### Database Tables Not Found
- Run `php setup_db.php` to create tables
- Ensure database exists and is accessible

## Documentation

For detailed project explanation and learning guide, see [PROJECT_EXPLANATION.md](PROJECT_EXPLANATION.md)

## License

Educational project for university coursework.
