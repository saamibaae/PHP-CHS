# Healthcare Management System (PHP Version)

This project has been migrated from Flask to Native PHP to ensure full transparency of MySQL queries and lightweight performance.

## Requirements
- PHP 7.4 or higher
- MySQL Database
- PDO Extension for PHP

## Setup
1. **Database**: Ensure your MySQL database `healthcare_db` is running and the credentials in `config.php` are correct.
   - Default: User `root`, Password ``, Host `localhost`.
2. **Web Server**: You can use the built-in PHP server for testing.
   ```bash
   php -S localhost:8000
   ```
3. **Access**: Open [http://localhost:8000](http://localhost:8000) in your browser.

## Structure
- **config.php**: Database credentials and site settings.
- **db.php**: Database connection (PDO) and helper functions.
- **index.php**: Redirects to login.
- **login.php / register.php**: Authentication.
- **admin/**: Hospital Administrator portal (Departments, Doctors, Labs, Stock).
- **doctor/**: Doctor portal (Appointments, Prescriptions, Lab Tests).
- **patient/**: Patient portal (Appointments, Bills, Profile).
- **templates/**: Header and Footer layout files.
- **flask_backup/**: The original Python/Flask source code.

## Key Features
- **Raw SQL Queries**: All database interactions use explicit SQL queries via PDO, located directly in the controller files for maximum visibility and control.
- **Role-Based Access**: Admin, Doctor, and Patient roles are strictly enforced.
- **Secure Auth**: Uses standard PHP `password_hash` and session management.
