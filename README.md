# GRAND FInale (PHP)

This repository contains the PHP version of the project (web application). The original Flask code has been removed from the main tree — see the archive instructions below if you need the old code.

Quick start

- Requirements: PHP 7.4+ (or PHP 8), a webserver (Apache/Nginx) and MySQL/MariaDB.
- Database: run `setup_db.php` and `seed_data.php` to initialize and seed the database.

Reinitialize this folder as a fresh GitHub repository

Use the included `reinit_repo.ps1` script to remove any existing git history and create a fresh repository locally. Edit the script to set your new remote URL or run the commands manually (example below).

Manual commands (PowerShell):

```powershell
Remove-Item -Recurse -Force .git  # only if you want to delete existing history
git init
git add .
git commit -m "Initial commit — PHP conversion"
git branch -M main
git remote add origin https://github.com/<your-username>/<your-repo>.git
git push -u origin main
```

Files added/changed to make this GitHub-ready:

- `.gitignore` — ignores vendor, node_modules, env files, py caches, IDE settings.
- `.gitattributes` — normalizes line endings.
- `reinit_repo.ps1` — helper script to reinitialize git for a fresh repo.

If you want to keep a copy of the old Flask project, restore it from local backups before running `reinit_repo.ps1`.

Troubleshooting

- If you see Flask files or Python artifacts, run `git status` and remove them before committing.

# Centralized Healthcare Management System (CHS Bangladesh)

A comprehensive Flask-based healthcare management system with MySQL backend, implementing role-based access control for Hospital Admins, Doctors, and Patients. **All database operations use explicit raw MySQL queries.**

## 🚀 Features

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
- **Auto-billing**: Lab tests automatically generate bills when completed

### For Patients

- Register and create account
- View medical profile with blood type and emergency contacts
- View appointment history
- Access prescriptions
- View and track bills

### Business Logic

- **Stock Validation**: Pharmacy bills check stock availability before processing
- **Prescription Expiry**: Expired prescriptions cannot be used for pharmacy bills
- **Auto-Billing**: Lab tests automatically generate bills when marked as completed
- **Multi-Table Inheritance**: Hospital model with PublicHospital and PrivateHospital

## 🛠️ Technology Stack

- **Backend**: Flask 3.0+
- **Database**: MySQL/MariaDB (using PyMySQL)
- **ORM**: SQLAlchemy (for model definitions only)
- **Database Queries**: **Explicit raw MySQL queries** (no ORM usage)
- **Frontend**: Jinja2 Templates + HTML + CSS
- **Authentication**: Flask-Login with session management
- **Forms**: WTForms with CSRF protection
- **Password Hashing**: Werkzeug

## 📋 Prerequisites

- Python 3.10+
- MySQL/MariaDB server (8.0+)
- pip (Python package manager)

## 🚀 Quick Start

### 1. Clone the Repository

```bash
git clone <repository-url>
cd "GRAND FInale"
```

### 2. Install Dependencies

```bash
pip install -r requirements_flask.txt
```

### 3. Configure Database

Edit `config.py` and update the database configuration:

```python
DB_HOST = 'localhost'
DB_PORT = 3306
DB_USER = 'root'
DB_PASSWORD = 'your_mysql_password'  # Update this
DB_NAME = 'healthcare_db'
```

Or use environment variables:

```bash
export DB_HOST=localhost
export DB_PORT=3306
export DB_USER=root
export DB_PASSWORD=your_password
export DB_NAME=healthcare_db
```

### 4. Create MySQL Database

```sql
CREATE DATABASE healthcare_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 5. Load Initial Data

```bash
flask load-data
```

This will create:

- 5 Public Hospitals (Dhaka Medical College, BSMMU, etc.)
- 5 Private Hospitals (Square Hospital, United Hospital, etc.)
- Districts (Dhaka, Chittagong, Sylhet, etc.)
- Service Types, Qualifications, and Manufacturers

### 6. Run the Application

```bash
python app.py
```

Or using Flask CLI:

```bash
flask run
```

Access the application at: **http://localhost:5000**

## 📖 Detailed Setup Tutorial

See [INITIALIZATION_TUTORIAL.md](INITIALIZATION_TUTORIAL.md) for step-by-step instructions.

## 👥 User Roles & Access

### Hospital Admin

- **Role**: ADMIN
- **Capabilities**: Manage hospital resources (departments, labs, doctors, pharmacy stock)
- **Restrictions**: Can only access their assigned hospital's data

### Doctor

- **Role**: DOCTOR
- **Capabilities**: View appointments, create prescriptions, order lab tests
- **Restrictions**: Can only see their own appointments and patients

### Patient

- **Role**: PATIENT
- **Capabilities**: View profile, appointments, prescriptions, and bills
- **Restrictions**: Read-only access to their own medical data

## 📊 Database Schema

The system implements **22 entities** with **explicit raw MySQL queries**:

- **User Management**: User (CustomUser) with role-based access
- **Hospital**: Multi-table inheritance (Hospital → PublicHospital/PrivateHospital)
- **Medical Staff**: Doctor, DoctorQualification
- **Patients**: Patient, PatientEmergencyContact
- **Clinical**: Appointment, Prescription, PrescriptionItem, LabTest
- **Pharmacy**: Medicine, Pharmacy, PharmacyMedicine, PharmacyBill
- **Billing**: Bill (with auto-generation from lab tests)
- **Reference**: District, Qualification, Manufacturer, ServiceType

See [SCHEMA_VERIFICATION.md](SCHEMA_VERIFICATION.md) for complete schema details.

## 🔄 System Flows

See [SYSTEM_FLOWS.md](SYSTEM_FLOWS.md) for detailed flow documentation.

### Key Workflows

1. **Patient Registration → Login → Dashboard**
2. **Doctor: Appointment → Diagnosis → Prescription → Lab Test**
3. **Lab Test Completion → Auto-Billing**
4. **Pharmacy: Prescription → Stock Check → Bill Creation**

## 🗂️ Project Structure

```
.
├── app.py                    # Flask application factory
├── config.py                 # Configuration settings
├── models.py                 # SQLAlchemy models (23 models)
├── forms.py                  # WTForms (12 forms)
├── db_utils.py               # Raw SQL utilities
├── decorators.py             # Role-based decorators
├── utils.py                  # Business logic utilities
├── routes/
│   ├── auth.py              # Authentication routes
│   ├── admin.py             # Admin routes
│   ├── doctor.py            # Doctor routes
│   └── patient.py           # Patient routes
├── commands/
│   └── load_data.py         # Initial data loading
├── templates/               # Jinja2 templates
├── static/                  # CSS and JS files
├── requirements_flask.txt    # Flask dependencies
└── README.md                # This file
```

## 🔌 API Endpoints

### Authentication

- `GET/POST /` - Login page
- `GET/POST /login` - Login
- `POST /logout` - Logout
- `GET/POST /register` - Patient registration
- `GET /dashboard` - Role-based dashboard redirect

### Admin Routes

- `GET /admin/dashboard` - Admin dashboard with analytics
- `GET /admin/departments` - List departments
- `GET/POST /admin/departments/add` - Add department
- `GET/POST /admin/departments/<id>/edit` - Edit department
- `GET /admin/labs` - List labs
- `GET/POST /admin/labs/add` - Add lab
- `GET /admin/doctors` - List doctors
- `GET/POST /admin/doctors/add` - Add doctor
- `GET /admin/pharmacy/stock` - Manage pharmacy stock
- `GET/POST /admin/pharmacy/stock/<id>/update` - Update stock

### Doctor Routes

- `GET /doctor/dashboard` - Doctor dashboard
- `GET /doctor/appointments` - List appointments
- `GET /doctor/appointments/<id>` - Appointment details
- `GET/POST /doctor/appointments/<id>/update` - Update appointment
- `GET/POST /doctor/appointments/<id>/prescription/create` - Create prescription
- `GET/POST /doctor/prescriptions/<id>/items/add` - Add prescription items
- `GET/POST /doctor/lab-test/order` - Order lab test
- `GET/POST /doctor/lab-test/<id>/update` - Update lab test (triggers auto-billing)

### Patient Routes

- `GET /patient/dashboard` - Patient dashboard
- `GET /patient/profile` - View profile
- `GET /patient/appointments` - View appointments
- `GET /patient/appointments/<id>` - Appointment details
- `GET /patient/bills` - View bills

## 🧪 Testing

### Run Structure Tests

```bash
python test_application.py
```

### Manual Testing

See [WORKFLOW_TESTING_GUIDE.md](WORKFLOW_TESTING_GUIDE.md) for comprehensive testing instructions.

## 🔒 Security Features

- ✅ CSRF protection (Flask-WTF)
- ✅ Session-based authentication (Flask-Login)
- ✅ Password hashing (Werkzeug)
- ✅ Role-based access control
- ✅ Parameterized SQL queries (SQL injection prevention)
- ✅ Hospital data isolation

## 📝 Database Queries

**All database operations use explicit raw MySQL queries.** No ORM usage in routes.

Example queries:

```python
# Authentication
fetch_one("SELECT * FROM core_customuser WHERE username = %s", (username,))

# Admin Dashboard
fetch_count("SELECT COUNT(*) FROM core_department WHERE hospital_id = %s", (hospital_id,))

# Auto-Billing
execute_insert("""INSERT INTO core_bill
                 (patient_id, service_type_id, total_amount, status, due_date, transaction_id, bill_date)
                 VALUES (%s, %s, %s, %s, %s, %s, %s)""", (...))
```

See `routes/` directory for all SQL queries.

## 🐛 Troubleshooting

### MySQL Connection Error

- Ensure MySQL server is running
- Check database credentials in `config.py`
- Verify database exists: `SHOW DATABASES;`

### Import Errors

- Install all dependencies: `pip install -r requirements_flask.txt`
- Ensure you're using Python 3.10+

### Port Already in Use

- Change port in `app.py`: `app.run(debug=True, host='0.0.0.0', port=5001)`

### Database Tables Not Found

- Run `flask load-data` to create initial data
- Ensure database exists and is accessible

## 📚 Documentation

- [INITIALIZATION_TUTORIAL.md](INITIALIZATION_TUTORIAL.md) - Step-by-step setup guide
- [SYSTEM_FLOWS.md](SYSTEM_FLOWS.md) - Complete flow documentation
- [SCHEMA_VERIFICATION.md](SCHEMA_VERIFICATION.md) - Database schema verification
- [WORKFLOW_TESTING_GUIDE.md](WORKFLOW_TESTING_GUIDE.md) - Testing instructions
- [FINAL_VERIFICATION.md](FINAL_VERIFICATION.md) - Final verification report

## 🔮 Future Enhancements

- PDF prescription generation
- Email notifications for appointments
- SMS reminders
- Online appointment booking for patients
- Payment gateway integration
- Medical report uploads
- Doctor availability calendar
- Real-time chat with doctors

## 📄 License

Educational project for university coursework.

## 👨‍💻 Contributors

Developed as part of CSE330 Database Management Systems course project.

## 📞 Support

For issues or questions, refer to the documentation files or contact the development team.

---

**Note**: This project uses **explicit raw MySQL queries** throughout. All database operations are visible in the code for educational purposes.
#   h e a l t h c a r e - m a n a g e m e n t - s y s t e m 
 
 
#   P H P - C H S  
 