# Initialization Tutorial - Healthcare Management System

This tutorial will guide you through setting up the Centralized Healthcare Management System from scratch.

## 📋 Table of Contents

1. [Prerequisites](#prerequisites)
2. [Step 1: Clone/Download Project](#step-1-clonedownload-project)
3. [Step 2: Install Python Dependencies](#step-2-install-python-dependencies)
4. [Step 3: Setup MySQL Database](#step-3-setup-mysql-database)
5. [Step 4: Configure Application](#step-4-configure-application)
6. [Step 5: Load Initial Data](#step-5-load-initial-data)
7. [Step 6: Create Test Users](#step-6-create-test-users)
8. [Step 7: Run the Application](#step-7-run-the-application)
9. [Step 8: Verify Installation](#step-8-verify-installation)
10. [Troubleshooting](#troubleshooting)

---

## Prerequisites

Before starting, ensure you have:

- ✅ **Python 3.10 or higher** installed
- ✅ **MySQL/MariaDB 8.0+** installed and running
- ✅ **pip** (Python package manager)
- ✅ **Git** (if cloning from repository)

### Verify Prerequisites

**Check Python version:**
```bash
python --version
# Should show Python 3.10.x or higher
```

**Check MySQL:**
```bash
mysql --version
# Should show MySQL 8.0.x or higher
```

**Check if MySQL is running:**
```bash
# Windows
net start MySQL80

# Linux/Mac
sudo systemctl status mysql
# or
sudo service mysql status
```

---

## Step 1: Clone/Download Project

### Option A: Clone from Git

```bash
git clone <repository-url>
cd "GRAND FInale"
```

### Option B: Download ZIP

1. Download the project ZIP file
2. Extract to your desired location
3. Navigate to the project directory:

```bash
cd "path/to/GRAND FInale"
```

---

## Step 2: Install Python Dependencies

### Create Virtual Environment (Recommended)

**Windows:**
```bash
python -m venv venv
venv\Scripts\activate
```

**Linux/Mac:**
```bash
python3 -m venv venv
source venv/bin/activate
```

### Install Dependencies

```bash
pip install -r requirements_flask.txt
```

**Expected output:**
```
Collecting Flask>=3.0.0
Collecting Flask-Login>=0.6.3
Collecting Flask-WTF>=1.2.1
...
Successfully installed Flask Flask-Login Flask-WTF ...
```

**Verify installation:**
```bash
pip list | grep Flask
# Should show Flask, Flask-Login, Flask-WTF, Flask-SQLAlchemy
```

---

## Step 3: Setup MySQL Database

### 3.1. Login to MySQL

```bash
mysql -u root -p
```

Enter your MySQL root password when prompted.

### 3.2. Create Database

```sql
CREATE DATABASE healthcare_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 3.3. Verify Database Creation

```sql
SHOW DATABASES;
-- Should see 'healthcare_db' in the list

USE healthcare_db;
SHOW TABLES;
-- Tables will be empty initially, that's OK
```

### 3.4. Exit MySQL

```sql
EXIT;
```

---

## Step 4: Configure Application

### 4.1. Edit Configuration File

Open `config.py` in your text editor:

```python
# config.py
class Config:
    # Database configuration
    DB_HOST = 'localhost'          # Change if MySQL is on different host
    DB_PORT = 3306                 # Change if MySQL uses different port
    DB_USER = 'root'               # Your MySQL username
    DB_PASSWORD = ''                # Your MySQL password (UPDATE THIS!)
    DB_NAME = 'healthcare_db'      # Database name
```

**⚠️ Important:** Update `DB_PASSWORD` with your MySQL root password.

### 4.2. Alternative: Use Environment Variables

Instead of editing `config.py`, you can set environment variables:

**Windows (PowerShell):**
```powershell
$env:DB_HOST="localhost"
$env:DB_PORT="3306"
$env:DB_USER="root"
$env:DB_PASSWORD="your_password"
$env:DB_NAME="healthcare_db"
```

**Linux/Mac:**
```bash
export DB_HOST=localhost
export DB_PORT=3306
export DB_USER=root
export DB_PASSWORD=your_password
export DB_NAME=healthcare_db
```

### 4.3. Test Database Connection

Create a test script `test_db_connection.py`:

```python
from config import Config
import pymysql

try:
    conn = pymysql.connect(
        host=Config.DB_HOST,
        port=Config.DB_PORT,
        user=Config.DB_USER,
        password=Config.DB_PASSWORD,
        database=Config.DB_NAME,
        charset='utf8mb4'
    )
    print("✅ Database connection successful!")
    conn.close()
except Exception as e:
    print(f"❌ Database connection failed: {e}")
```

Run it:
```bash
python test_db_connection.py
```

If successful, you'll see: `✅ Database connection successful!`

---

## Step 5: Load Initial Data

The application includes a Flask CLI command to load initial data (hospitals, districts, etc.).

### 5.1. Run Data Loading Command

```bash
flask load-data
```

**Expected output:**
```
Loading initial data...
Creating districts...
Creating hospitals...
✅ Initial data loaded successfully!
```

### 5.2. Verify Data Loaded

Login to MySQL and check:

```sql
USE healthcare_db;

-- Check hospitals
SELECT COUNT(*) FROM core_hospital;
-- Should show 10 (5 public + 5 private)

-- Check districts
SELECT COUNT(*) FROM core_district;
-- Should show 5

-- Check service types
SELECT * FROM core_servicetype;
-- Should show service types
```

### 5.3. If Command Fails

If `flask load-data` doesn't work, you can run it directly:

```bash
python -c "from app import create_app; from config import Config; app = create_app(Config); app.app_context().push(); from commands.load_data import load_data; load_data()"
```

---

## Step 6: Create Test Users

You need to create at least one user for each role (Admin, Doctor, Patient) to test the system.

### Option A: Using Flask Shell (Recommended)

```bash
flask shell
```

Then run:

```python
from app import create_app
from config import Config
from models import User, db
from werkzeug.security import generate_password_hash

app = create_app(Config)
with app.app_context():
    # Create Admin User
    admin = User()
    admin.username = 'admin1'
    admin.password = generate_password_hash('admin123')
    admin.email = 'admin@hospital.com'
    admin.first_name = 'Admin'
    admin.last_name = 'User'
    admin.role = 'ADMIN'
    admin.hospital_id = 1  # First hospital from initial data
    admin.is_active = True
    admin.is_staff = True
    db.session.add(admin)
    db.session.commit()
    print("✅ Admin user created: admin1 / admin123")
    
    # Create Doctor User
    doctor = User()
    doctor.username = 'doctor1'
    doctor.password = generate_password_hash('doctor123')
    doctor.email = 'doctor@hospital.com'
    doctor.first_name = 'Doctor'
    doctor.last_name = 'User'
    doctor.role = 'DOCTOR'
    doctor.hospital_id = 1
    doctor.is_active = True
    db.session.add(doctor)
    db.session.commit()
    print("✅ Doctor user created: doctor1 / doctor123")
    
    # Note: Doctor profile needs to be created separately via admin interface
    # Patient can register via /register page
```

### Option B: Using SQL Directly

```sql
USE healthcare_db;

-- Create Admin User (password: admin123)
INSERT INTO core_customuser 
(username, password, email, first_name, last_name, is_active, is_staff, 
 is_superuser, date_joined, role, hospital_id)
VALUES 
('admin1', 'pbkdf2:sha256:...', 'admin@hospital.com', 'Admin', 'User',
 1, 1, 0, NOW(), 'ADMIN', 1);

-- Note: You need to hash the password first using Werkzeug
-- Use Flask shell method above for proper password hashing
```

### Option C: Register Patient via Web Interface

1. Start the application (Step 7)
2. Navigate to `http://localhost:5000/register`
3. Fill in patient registration form
4. Submit to create patient account

---

## Step 7: Run the Application

### 7.1. Start the Server

```bash
python app.py
```

**Expected output:**
```
 * Running on http://0.0.0.0:5000
 * Debug mode: on
```

### 7.2. Access the Application

Open your web browser and navigate to:

```
http://localhost:5000
```

You should see the login page.

### 7.3. Alternative: Using Flask CLI

```bash
flask run
```

Or with specific host/port:

```bash
flask run --host=0.0.0.0 --port=5000
```

---

## Step 8: Verify Installation

### 8.1. Test Login

1. Navigate to `http://localhost:5000`
2. Try logging in with test credentials:
   - **Admin**: `admin1` / `admin123`
   - **Doctor**: `doctor1` / `doctor123`
   - **Patient**: Register new account at `/register`

### 8.2. Verify Routes

**Admin Routes:**
- `http://localhost:5000/admin/dashboard` - Should show admin dashboard
- `http://localhost:5000/admin/departments` - Should show departments list

**Doctor Routes:**
- `http://localhost:5000/doctor/dashboard` - Should show doctor dashboard
- `http://localhost:5000/doctor/appointments` - Should show appointments

**Patient Routes:**
- `http://localhost:5000/patient/dashboard` - Should show patient dashboard
- `http://localhost:5000/patient/profile` - Should show profile

### 8.3. Run Structure Tests

```bash
python test_application.py
```

**Expected output:**
```
TEST SUMMARY
Total: 7/7 tests passed
[SUCCESS] All tests passed! Application structure is correct.
```

---

## Troubleshooting

### Issue: "ModuleNotFoundError: No module named 'flask'"

**Solution:**
```bash
pip install -r requirements_flask.txt
```

### Issue: "Can't connect to MySQL server"

**Solutions:**
1. Check if MySQL is running:
   ```bash
   # Windows
   net start MySQL80
   
   # Linux/Mac
   sudo systemctl start mysql
   ```

2. Verify credentials in `config.py`
3. Test connection manually:
   ```bash
   mysql -u root -p
   ```

### Issue: "Access denied for user 'root'@'localhost'"

**Solution:**
1. Reset MySQL root password or use correct password
2. Update `DB_PASSWORD` in `config.py`

### Issue: "Unknown database 'healthcare_db'"

**Solution:**
```sql
CREATE DATABASE healthcare_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Issue: "flask: command not found"

**Solution:**
```bash
# Use python -m flask instead
python -m flask load-data
```

### Issue: "Port 5000 already in use"

**Solution:**
1. Change port in `app.py`:
   ```python
   app.run(debug=True, host='0.0.0.0', port=5001)
   ```

2. Or kill the process using port 5000:
   ```bash
   # Windows
   netstat -ano | findstr :5000
   taskkill /PID <PID> /F
   
   # Linux/Mac
   lsof -ti:5000 | xargs kill
   ```

### Issue: "No tables found after load-data"

**Solution:**
1. Check if database exists
2. Verify database connection
3. Check for errors in `flask load-data` output
4. Manually verify tables:
   ```sql
   USE healthcare_db;
   SHOW TABLES;
   ```

---

## Next Steps

After successful installation:

1. ✅ Read [SYSTEM_FLOWS.md](SYSTEM_FLOWS.md) to understand system flows
2. ✅ Read [WORKFLOW_TESTING_GUIDE.md](WORKFLOW_TESTING_GUIDE.md) for testing
3. ✅ Create additional test users for comprehensive testing
4. ✅ Explore all features for each user role

---

## Summary

✅ **Project cloned/downloaded**  
✅ **Dependencies installed**  
✅ **Database created**  
✅ **Application configured**  
✅ **Initial data loaded**  
✅ **Test users created**  
✅ **Application running**  
✅ **Installation verified**

**Your Healthcare Management System is now ready to use!** 🎉

For detailed flow documentation, see [SYSTEM_FLOWS.md](SYSTEM_FLOWS.md).

