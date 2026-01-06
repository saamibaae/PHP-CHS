# Healthcare Management System

A robust and efficient web-based solution designed to streamline hospital operations, built with **PHP** and **MySQL**. This system bridges the gap between administrators, doctors, and patients, offering a seamless experience for managing medical workflows.

## 🚀 Overview

This application serves as a comprehensive digital backbone for hospitals, enabling:

- **Administrators** to manage resources, staff, and pharmacy stocks.
- **Doctors** to handle appointments, prescriptions, and lab tests digitally.
- **Patients** to book appointments, access medical records, and view bills.

## ✨ Key Features

### 🏥 For Hospital Administrators

- **Resource Management**: Complete control over departments, labs, and pharmacy inventories.
- **Staff Administration**: Onboard and manage doctors with specialized roles.
- **Operational Oversight**: Track hospital admissions, discharges, and bed occupancy.
- **Pharmacy Control**: Monitor stock levels and manage medicine inventory.

### 👨‍⚕️ For Doctors

- **Digital Workbench**: View daily schedules and manage patient appointments.
- **e-Prescriptions**: Generate professional prescriptions with multiple medicines and instructions.
- **Lab Integration**: Order lab tests and view results directly within the patient's file.
- **Clinical Notes**: Maintain diagnosis records and treatment history.

### 👤 For Patients

- **Self-Service Portal**: Register and book appointments online.
- **Medical History**: Access past prescriptions, lab reports, and doctor notes.
- **Billing Transparency**: View bill status and transaction history.
- **Profile Management**: Update personal and emergency contact information.

## 🛠️ Technology Stack

- **Backend**: PHP 7.4+ (Native)
- **Database**: MySQL / MariaDB
- **Frontend**: HTML5, Tailwind CSS, JavaScript
- **Security**:
  - Role-Based Access Control (RBAC)
  - PDO Prepared Statements (SQL Injection Protection)
  - Secure Password Hashing (Bcrypt/PBKDF2)
  - Session Management

## ⚙️ Installation & Setup

### Prerequisites

- PHP 7.4 or higher
- MySQL 8.0 or higher
- A web server (Apache, Nginx, or PHP built-in server)

### Quick Start (Windows)

1.  **Clone the Repository**

    ```bash
    git clone <repository-url>
    cd healthcare-management-system
    ```

2.  **Configure Database**
    Open `config.php` and verify your database credentials:

    ```php
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'healthcare_db');
    define('DB_USER', 'root'); // Your MySQL username
    define('DB_PASS', '');     // Your MySQL password
    ```

3.  **Initialize Database**
    Run the setup script to create the schema and tables:

    ```bash
    php setup_db.php
    ```

4.  **Seed Data (Optional)**
    Populate the system with dummy data for testing:

    ```bash
    php seed_data.php
    ```

    _This will create default users for Admin, Doctor, and Patient roles._

5.  **Run Application**
    Start the PHP development server:
    ```bash
    php -S localhost:8000
    ```
    Visit **[http://localhost:8000](http://localhost:8000)** in your browser.

## 🔐 Default Credentials

If you ran `seed_data.php`, use these credentials to log in:

| Role        | Username    | Password      |
| ----------- | ----------- | ------------- |
| **Admin**   | `admin`     | `admin123`    |
| **Doctor**  | `dr_rahman` | `password123` |
| **Patient** | `patient1`  | `password123` |

## 📂 Project Structure

- **`admin/`**: Administrative tools (Staff, Stock, Admissions).
- **`doctor/`**: Clinical tools (Appointments, Prescriptions).
- **`patient/`**: Patient portal (Booking, Profile, History).
- **`templates/`**: Shared UI components (Header, Footer).
- **`static/`**: CSS and assets.
- **`db.php`**: Database connection handler.
- **`functions.php`**: Core utility functions.
