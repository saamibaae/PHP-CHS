# Healthcare Management System

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
    git clone https://github.com/saamibaae/PHP-CHS
    cd PHP-CHS
    ```

2.  **Configure Database**

    ```php
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'healthcare_db');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    ```

3.  **Initialize Database**

    ```bash
    php setup_db.php
    ```

4.  **Seed Data (Optional)**

    ```bash
    php seed_data.php
    ```

5.  **Run Application**
    ```bash
    php -S localhost:8000
    ```
    Visit **[http://localhost:8000](http://localhost:8000)** in your browser.

## 🔐 Default Credentials

If you ran `seed_data.php`, use these credentials to log in:

| Role        | Username         | Password      |
| ----------- | ---------------- | ------------- |
| **Admin**   | `dmc`            | `password123` |
| **Doctor**  | `dmc_cardiology` | `password123` |
| **Patient** | `sami1`          | `123456`      |
