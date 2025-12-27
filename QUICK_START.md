# Quick Start Guide

Get the Healthcare Management System running in 5 minutes!

## 🚀 Quick Setup

### 1. Install Dependencies
```bash
pip install -r requirements_flask.txt
```

### 2. Configure Database
Edit `config.py`:
```python
DB_PASSWORD = 'your_mysql_password'  # Update this
```

### 3. Create Database
```sql
CREATE DATABASE healthcare_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 4. Load Initial Data
```bash
flask load-data
```

### 5. Run Application
```bash
python app.py
```

### 6. Access Application
Open: **http://localhost:5000**

---

## 📚 Full Documentation

- **[README.md](README.md)** - Complete project overview
- **[INITIALIZATION_TUTORIAL.md](INITIALIZATION_TUTORIAL.md)** - Detailed setup guide
- **[SYSTEM_FLOWS.md](SYSTEM_FLOWS.md)** - All system flows explained
- **[WORKFLOW_TESTING_GUIDE.md](WORKFLOW_TESTING_GUIDE.md)** - Testing instructions

---

## 🎯 Key Features

- ✅ **3 User Roles**: Admin, Doctor, Patient
- ✅ **Explicit MySQL Queries**: All database operations use raw SQL
- ✅ **Auto-Billing**: Lab tests automatically create bills
- ✅ **Stock Validation**: Prevents overselling
- ✅ **Prescription Expiry**: Blocks expired prescriptions

---

## 🔑 Default Test Users

Create test users using Flask shell (see [INITIALIZATION_TUTORIAL.md](INITIALIZATION_TUTORIAL.md)):

- **Admin**: `admin1` / `admin123`
- **Doctor**: `doctor1` / `doctor123`
- **Patient**: Register at `/register`

---

## 📊 System Flows

See [SYSTEM_FLOWS.md](SYSTEM_FLOWS.md) for complete flow documentation:

1. **Authentication**: Registration, Login, Logout
2. **Admin**: Dashboard, Departments, Labs, Doctors, Stock
3. **Doctor**: Appointments, Prescriptions, Lab Tests, Auto-Billing
4. **Patient**: Profile, Appointments, Prescriptions, Bills

---

## 🐛 Troubleshooting

**Can't connect to MySQL?**
- Check if MySQL is running
- Verify credentials in `config.py`

**Port 5000 in use?**
- Change port in `app.py`: `app.run(port=5001)`

**Module not found?**
- Run: `pip install -r requirements_flask.txt`

---

**For detailed instructions, see [INITIALIZATION_TUTORIAL.md](INITIALIZATION_TUTORIAL.md)**

