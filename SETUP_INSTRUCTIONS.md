# Quick Setup & Testing Guide

## Prerequisites
Before starting, ensure you have:
- Python 3.10+ installed
- MySQL/MariaDB server installed and running
- MySQL root password (or appropriate user credentials)

## Step-by-Step Setup

### 1. Install Dependencies
```bash
pip install -r requirements.txt
```

Expected output: All packages installed successfully

### 2. Configure Database Connection

**Option A: Using MySQL Command Line**
```sql
mysql -u root -p
CREATE DATABASE healthcare_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON healthcare_db.* TO 'root'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

**Option B: Using MySQL Workbench or phpMyAdmin**
- Create new database named: `healthcare_db`
- Character set: `utf8mb4`
- Collation: `utf8mb4_unicode_ci`

### 3. Update Database Password

Edit `healthcare_system/settings.py` line 78-85:
```python
DATABASES = {
    'default': {
        'ENGINE': 'django.db.backends.mysql',
        'NAME': 'healthcare_db',
        'USER': 'root',
        'PASSWORD': 'YOUR_MYSQL_PASSWORD_HERE',  # ← Update this line
        'HOST': 'localhost',
        'PORT': '3306',
    }
}
```

### 4. Run Migrations
```bash
python manage.py migrate
```

Expected output: Should show ~30-40 migrations being applied successfully

### 5. Load Initial Hospital Data
```bash
python manage.py load_initial_data
```

This creates:
- ✅ 5 Public Hospitals (Dhaka Medical College, BSMMU, Kurmitola General, Chittagong Medical College, Sylhet MAG Osmani)
- ✅ 5 Private Hospitals (Square, United, Evercare, Labaid, Ibn Sina)
- ✅ 5 Districts (Dhaka, Chittagong, Sylhet, Rajshahi, Khulna)
- ✅ Service Types (Consultation, Laboratory, Pharmacy, Emergency, Surgery)
- ✅ Qualifications (MBBS, MD, MS, FCPS, FRCS)
- ✅ 3 Pharmaceutical Manufacturers

### 6. Create Superuser (Admin Access)
```bash
python manage.py createsuperuser
```

Follow the prompts:
- Username: admin
- Email: admin@example.com
- Password: (your choice, at least 8 characters)

### 7. Start Development Server
```bash
python manage.py runserver
```

Expected output:
```
Starting development server at http://127.0.0.1:8000/
```

### 8. Access the Application

Open browser and go to:
- **Main Application**: http://127.0.0.1:8000/
- **Django Admin Panel**: http://127.0.0.1:8000/admin/

## Creating Test Users

### Method 1: Via Django Admin (Recommended)

1. Login to admin panel: http://127.0.0.1:8000/admin/
2. Use superuser credentials created in Step 6

#### Create Hospital Admin User:
1. Go to **Custom Users** → Add Custom User
   - Username: `admin_dhaka`
   - Password: `admin123`
   - Role: **Hospital Admin**
   - Hospital: Select "Dhaka Medical College Hospital"
   - Check: **Staff status** ✓
2. Save

#### Create Doctor User:
1. Go to **Doctors** → Add Doctor
   - First add user:
     * Username: `doctor_john`
     * Password: `doctor123`
     * Role: **Doctor**
     * Hospital: "Dhaka Medical College Hospital"
   - Then fill doctor details:
     * License No: DOC-001
     * Full Name: John Doe
     * Specialization: Cardiology
     * Phone: 01712345678
     * Email: john@example.com
     * Experience Years: 5
     * Gender: Male
     * Shift Timing: 9 AM - 5 PM
     * Join Date: 2020-01-01
     * Hospital: Dhaka Medical College Hospital
     * Department: (select one)
2. Save

#### Create Patient User:
**Option A: Via Registration Form** (Easier)
1. Go to http://127.0.0.1:8000/register/
2. Fill in the form with patient details
3. Submit

**Option B: Via Admin Panel**
1. Create CustomUser first (username, password, role=PATIENT)
2. Go to **Patients** → Add Patient
3. Fill in details and link to user

### Method 2: Via Django Shell (Advanced)

```bash
python manage.py shell
```

```python
from core.models import *
from django.contrib.auth.hashers import make_password

# Get a hospital
hospital = Hospital.objects.first()

# Create Admin User
admin_user = CustomUser.objects.create(
    username='admin_test',
    password=make_password('admin123'),
    role='ADMIN',
    hospital=hospital,
    is_staff=True
)

# Create Doctor
doctor_user = CustomUser.objects.create(
    username='doctor_test',
    password=make_password('doctor123'),
    role='DOCTOR',
    hospital=hospital
)

dept = Department.objects.filter(hospital=hospital).first()
if dept:
    doctor = Doctor.objects.create(
        user=doctor_user,
        license_no='DOC-TEST-001',
        full_name='Dr. Test Doctor',
        specialization='General Medicine',
        phone='01712345678',
        email='doctor@test.com',
        experience_yrs=5,
        gender='M',
        shift_timing='9 AM - 5 PM',
        join_date='2020-01-01',
        hospital=hospital,
        dept=dept
    )

# Create Patient
patient_user = CustomUser.objects.create(
    username='patient_test',
    password=make_password('patient123'),
    role='PATIENT',
    email='patient@test.com'
)

patient = Patient.objects.create(
    user=patient_user,
    national_id='1234567890',
    full_name='Test Patient',
    date_of_birth='1990-01-01',
    gender='M',
    phone='01712345678',
    email='patient@test.com',
    address='Dhaka, Bangladesh',
    blood_type='O+',
    occupation='Engineer',
    marital_status='Single',
    birth_place='Dhaka',
    father_name='Father Name',
    mother_name='Mother Name'
)

print("Test users created successfully!")
exit()
```

## Testing Workflows

### Test 1: Hospital Admin Login
1. Login with admin credentials
2. Verify dashboard shows hospital stats
3. Test: Add new department
4. Test: Add new lab
5. Test: Add new doctor
6. Test: View and update pharmacy stock

### Test 2: Doctor Login
1. Login with doctor credentials
2. View dashboard (should show appointments)
3. Create sample appointment via admin panel
4. Test: View appointment details
5. Test: Update appointment status
6. Test: Create prescription
7. Test: Add medicines to prescription
8. Test: Order lab test

### Test 3: Patient Login
1. Login with patient credentials
2. View dashboard
3. Test: View profile and emergency contacts
4. Test: View appointments
5. Test: View prescription details
6. Test: View bills

### Test 4: Business Logic

#### Test Prescription Expiry:
1. Create prescription with past expiry date
2. Try to create pharmacy bill
3. Should show error: "Prescription expired"

#### Test Stock Validation:
1. Set medicine stock to 5 units
2. Try to purchase 10 units
3. Should show error: "Insufficient stock"

#### Test Auto-Billing:
1. Doctor orders lab test
2. Lab technician marks test as "Completed"
3. Bill should auto-generate in Bills table
4. Patient should see bill in dashboard

## Troubleshooting

### Error: "Can't connect to MySQL server"
**Solution**: 
- Ensure MySQL service is running
- Check username/password in settings.py
- Verify database 'healthcare_db' exists

### Error: "Table doesn't exist"
**Solution**:
```bash
python manage.py migrate --run-syncdb
```

### Error: "No module named 'pymysql'"
**Solution**:
```bash
pip install PyMySQL
```

### Error: Static files not loading
**Solution**:
```bash
python manage.py collectstatic --noinput
```

### Error: "Template does not exist"
**Solution**:
- Verify templates directory: `core/templates/`
- Check TEMPLATES setting in settings.py

## Quick Test Commands

```bash
# Check if all apps are properly configured
python manage.py check

# View current migrations status
python manage.py showmigrations

# Run Django development server
python manage.py runserver

# Access Django shell for testing
python manage.py shell

# Create sample data
python manage.py load_initial_data
```

## Sample Test Data

After setup, you should have:
- **10 Hospitals** (5 public + 5 private)
- **5 Districts**
- **5 Service Types**
- **5 Qualifications**
- **3 Manufacturers**

You need to manually create:
- Departments (via admin panel)
- Doctors (via admin panel)
- Patients (via registration or admin)
- Appointments (via admin panel)
- Medicines (via admin panel)
- Pharmacy stock (via admin panel)

## Demo Credentials (After Creating Test Users)

| Role | Username | Password | Description |
|------|----------|----------|-------------|
| Superuser | admin | (set during createsuperuser) | Full admin access |
| Hospital Admin | admin_dhaka | admin123 | Manages Dhaka Medical College |
| Doctor | doctor_john | doctor123 | Cardiology specialist |
| Patient | patient_test | patient123 | Regular patient |

## System Requirements Check

```bash
# Check Python version (should be 3.10+)
python --version

# Check Django version
python -c "import django; print(django.get_version())"

# Check MySQL connection
python -c "import pymysql; print('PyMySQL installed')"

# List installed packages
pip list
```

## Next Steps After Setup

1. ✅ Create departments for hospitals
2. ✅ Add medicines to the system
3. ✅ Create pharmacy stock entries
4. ✅ Create appointments for testing
5. ✅ Test prescription creation
6. ✅ Test lab test ordering
7. ✅ Test billing workflow

## Support

If you encounter issues:
1. Check this guide thoroughly
2. Review README.md for detailed information
3. Check Django error messages in terminal
4. Verify MySQL is running and accessible
5. Ensure all migrations are applied

Good luck with your Healthcare Management System! 🏥

