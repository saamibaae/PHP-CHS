# System Flows Documentation

Complete documentation of all user flows and system processes in the Centralized Healthcare Management System.

## 📋 Table of Contents

1. [Authentication Flows](#authentication-flows)
2. [Hospital Admin Flows](#hospital-admin-flows)
3. [Doctor Flows](#doctor-flows)
4. [Patient Flows](#patient-flows)
5. [Business Logic Flows](#business-logic-flows)
6. [Data Flow Diagrams](#data-flow-diagrams)

---

## Authentication Flows

### Flow 1: Patient Registration

**Purpose**: Allow new patients to create an account and profile.

**Steps**:
1. User navigates to `/register`
2. Fills registration form:
   - Username, password, confirm password
   - Email, first name, last name
   - National ID, full name, date of birth
   - Gender, phone, address
   - Blood type, marital status, birth place
   - Father name, mother name
   - Emergency contact details
3. System validates form data
4. **SQL Query**: Check if username exists
   ```sql
   SELECT * FROM core_customuser WHERE username = %s
   ```
5. **SQL Query**: Insert new user
   ```sql
   INSERT INTO core_customuser (username, password, email, ...)
   VALUES (%s, %s, %s, ...)
   ```
6. **SQL Query**: Insert patient profile
   ```sql
   INSERT INTO core_patient (national_id, full_name, user_id, ...)
   VALUES (%s, %s, %s, ...)
   ```
7. **SQL Query**: Insert emergency contact
   ```sql
   INSERT INTO core_patientemergencycontact (patient_id, contact_name, ...)
   VALUES (%s, %s, ...)
   ```
8. User redirected to login page
9. User can now login with new credentials

**Database Tables Affected**:
- `core_customuser`
- `core_patient`
- `core_patientemergencycontact`

---

### Flow 2: User Login

**Purpose**: Authenticate users and redirect to role-specific dashboard.

**Steps**:
1. User navigates to `/login` or `/`
2. Enters username and password
3. System validates form
4. **SQL Query**: Fetch user by username
   ```sql
   SELECT * FROM core_customuser WHERE username = %s AND is_active = 1
   ```
5. System verifies password using Werkzeug
6. If valid:
   - Create Flask-Login session
   - Update last_login timestamp
   - **SQL Query**: Update last_login
     ```sql
     UPDATE core_customuser SET last_login = NOW() WHERE id = %s
     ```
   - Redirect based on role:
     - `ADMIN` → `/admin/dashboard`
     - `DOCTOR` → `/doctor/dashboard`
     - `PATIENT` → `/patient/dashboard`
7. If invalid: Show error message

**Database Tables Affected**:
- `core_customuser` (read, update)

---

### Flow 3: User Logout

**Purpose**: End user session and clear authentication.

**Steps**:
1. User clicks "Logout" button
2. Flask-Login clears session
3. User redirected to `/login`
4. Session cookie removed

**Database Tables Affected**: None

---

## Hospital Admin Flows

### Flow 4: Admin Dashboard View

**Purpose**: Display hospital statistics and analytics.

**Steps**:
1. Admin logs in → redirected to `/admin/dashboard`
2. System fetches admin's hospital_id
3. **SQL Queries**: Get statistics
   ```sql
   -- Departments count
   SELECT COUNT(*) FROM core_department WHERE hospital_id = %s
   
   -- Doctors count
   SELECT COUNT(*) FROM core_doctor WHERE hospital_id = %s
   
   -- Labs count
   SELECT COUNT(*) FROM core_lab WHERE hospital_id = %s
   
   -- Today's appointments
   SELECT COUNT(*) FROM core_appointment a
   INNER JOIN core_doctor d ON a.doctor_id = d.doctor_id
   WHERE d.hospital_id = %s AND DATE(a.date_and_time) = CURDATE()
   
   -- Recent appointments
   SELECT * FROM core_appointment a
   INNER JOIN core_doctor d ON a.doctor_id = d.doctor_id
   INNER JOIN core_patient p ON a.patient_id = p.patient_id
   WHERE d.hospital_id = %s
   ORDER BY a.date_and_time DESC LIMIT 10
   
   -- Appointments per day (for chart)
   SELECT DATE(date_and_time) as date, COUNT(*) as count
   FROM core_appointment a
   INNER JOIN core_doctor d ON a.doctor_id = d.doctor_id
   WHERE d.hospital_id = %s
   GROUP BY DATE(date_and_time)
   ORDER BY date DESC LIMIT 30
   ```
4. Render dashboard with statistics and chart data

**Database Tables Queried**:
- `core_department`
- `core_doctor`
- `core_lab`
- `core_appointment`
- `core_patient`

---

### Flow 5: Add Department

**Purpose**: Create a new department in the hospital.

**Steps**:
1. Admin navigates to `/admin/departments`
2. Clicks "Add Department"
3. Fills form:
   - Department name
   - Floor
   - Extension
   - Operating hours
4. System validates form
5. **SQL Query**: Check if department name already exists for this hospital
   ```sql
   SELECT * FROM core_department 
   WHERE dept_name = %s AND hospital_id = %s
   ```
6. **SQL Query**: Insert new department
   ```sql
   INSERT INTO core_department 
   (dept_name, floor, extension, operating_hours, hospital_id)
   VALUES (%s, %s, %s, %s, %s)
   ```
7. Redirect to departments list
8. New department appears in list

**Database Tables Affected**:
- `core_department`

---

### Flow 6: Add Doctor

**Purpose**: Create a new doctor account and profile.

**Steps**:
1. Admin navigates to `/admin/doctors`
2. Clicks "Add Doctor"
3. Fills form:
   - Username, password, confirm password
   - License number, full name, specialization
   - Phone, email, experience, gender
   - Shift timing, join date, department
4. System validates form
5. **SQL Query**: Check if username exists
   ```sql
   SELECT * FROM core_customuser WHERE username = %s
   ```
6. **SQL Query**: Insert user account
   ```sql
   INSERT INTO core_customuser 
   (username, password, email, first_name, last_name, role, hospital_id, ...)
   VALUES (%s, %s, %s, %s, %s, 'DOCTOR', %s, ...)
   ```
7. **SQL Query**: Insert doctor profile
   ```sql
   INSERT INTO core_doctor 
   (license_no, full_name, specialization, user_id, hospital_id, dept_id, ...)
   VALUES (%s, %s, %s, %s, %s, %s, ...)
   ```
8. Redirect to doctors list
9. New doctor can now login

**Database Tables Affected**:
- `core_customuser`
- `core_doctor`

---

### Flow 7: Update Pharmacy Stock

**Purpose**: Update medicine stock quantity, price, or expiry date.

**Steps**:
1. Admin navigates to `/admin/pharmacy/stock`
2. Selects a pharmacy
3. Views stock list for that pharmacy
4. Clicks "Update" on a stock item
5. Modifies:
   - Stock quantity
   - Unit price
   - Expiry date
6. **SQL Query**: Update stock
   ```sql
   UPDATE core_pharmacymedicine
   SET stock_quantity = %s, unit_price = %s, expiry_date = %s
   WHERE pharmacy_medicine_id = %s
   ```
7. Redirect to stock list
8. Updated values reflected

**Database Tables Affected**:
- `core_pharmacymedicine`

---

## Doctor Flows

### Flow 8: View Appointments

**Purpose**: Display all appointments for the doctor.

**Steps**:
1. Doctor logs in → redirected to `/doctor/dashboard`
2. Navigates to `/doctor/appointments`
3. **SQL Query**: Fetch doctor profile
   ```sql
   SELECT * FROM core_doctor WHERE user_id = %s
   ```
4. **SQL Query**: Fetch appointments
   ```sql
   SELECT * FROM core_appointment a
   INNER JOIN core_patient p ON a.patient_id = p.patient_id
   WHERE a.doctor_id = %s
   ORDER BY a.date_and_time DESC
   ```
5. Display appointments with patient info and status
6. Optional: Filter by status (Scheduled, Completed, Cancelled)

**Database Tables Queried**:
- `core_doctor`
- `core_appointment`
- `core_patient`

---

### Flow 9: Update Appointment with Diagnosis

**Purpose**: Doctor completes appointment and adds diagnosis.

**Steps**:
1. Doctor opens appointment detail page
2. Views patient information and appointment details
3. Updates form:
   - Status (Scheduled → Completed)
   - Diagnosis
   - Follow-up date (optional)
4. **SQL Query**: Update appointment
   ```sql
   UPDATE core_appointment
   SET status = %s, diagnosis = %s, follow_up_date = %s
   WHERE appointment_id = %s AND doctor_id = %s
   ```
5. Redirect to appointments list
6. Appointment status updated

**Database Tables Affected**:
- `core_appointment`

---

### Flow 10: Create Prescription

**Purpose**: Doctor creates a prescription for an appointment.

**Steps**:
1. Doctor opens appointment detail page
2. Clicks "Create Prescription"
3. Fills prescription form:
   - Valid until date
   - Refill count
   - Notes (optional)
4. **SQL Query**: Insert prescription
   ```sql
   INSERT INTO core_prescription 
   (appointment_id, valid_until, refill_count, notes)
   VALUES (%s, %s, %s, %s)
   ```
5. Redirect to add prescription items page
6. Doctor can now add medicines

**Database Tables Affected**:
- `core_prescription`

---

### Flow 11: Add Prescription Items

**Purpose**: Add medicines to a prescription.

**Steps**:
1. Doctor on prescription items page
2. Fills item form:
   - Medicine (select from dropdown)
   - Dosage, frequency, duration
   - Quantity
   - Before/after meal
   - Instructions (optional)
3. **SQL Query**: Fetch available medicines
   ```sql
   SELECT * FROM core_medicine ORDER BY name
   ```
4. **SQL Query**: Insert prescription item
   ```sql
   INSERT INTO core_prescriptionitem 
   (prescription_id, medicine_id, dosage, frequency, duration, 
    quantity, before_after_meal, instructions)
   VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
   ```
5. Item added to prescription
6. Doctor can add more items or finish

**Database Tables Affected**:
- `core_prescriptionitem`

**Database Tables Queried**:
- `core_medicine`

---

### Flow 12: Order Lab Test

**Purpose**: Doctor orders a lab test for a patient.

**Steps**:
1. Doctor navigates to `/doctor/lab-test/order`
2. Fills lab test form:
   - Lab (select from hospital's labs)
   - Patient (select from all patients)
   - Test type
   - Test cost
   - Remarks (optional)
3. **SQL Query**: Fetch labs for doctor's hospital
   ```sql
   SELECT * FROM core_lab WHERE hospital_id = %s
   ```
4. **SQL Query**: Fetch all patients
   ```sql
   SELECT * FROM core_patient ORDER BY full_name
   ```
5. **SQL Query**: Insert lab test
   ```sql
   INSERT INTO core_labtest 
   (lab_id, patient_id, test_type, ordered_by_id, test_cost, remarks, status)
   VALUES (%s, %s, %s, %s, %s, %s, 'Ordered')
   ```
6. Redirect to dashboard
7. Lab test created with status "Ordered"

**Database Tables Affected**:
- `core_labtest`

**Database Tables Queried**:
- `core_lab`
- `core_patient`

---

### Flow 13: Update Lab Test (Auto-Billing)

**Purpose**: Update lab test status and result. Triggers auto-billing when completed.

**Steps**:
1. Doctor navigates to lab test update page
2. Updates form:
   - Status (Ordered → In Progress → Completed)
   - Result (optional)
3. **SQL Query**: Fetch current lab test
   ```sql
   SELECT * FROM core_labtest WHERE test_id = %s AND ordered_by_id = %s
   ```
4. **SQL Query**: Update lab test
   ```sql
   UPDATE core_labtest
   SET status = %s, result = %s
   WHERE test_id = %s
   ```
5. **Auto-Billing Logic**: If status changed to "Completed"
   - **SQL Query**: Check if bill already exists
     ```sql
     SELECT COUNT(*) FROM core_bill b
     INNER JOIN core_servicetype st ON b.service_type_id = st.service_type_id
     WHERE b.patient_id = %s AND st.name = 'Laboratory' 
     AND b.transaction_id = %s
     ```
   - If no bill exists:
     - **SQL Query**: Get or create Laboratory service type
       ```sql
       SELECT * FROM core_servicetype WHERE name = 'Laboratory'
       -- If not exists, INSERT
       ```
     - **SQL Query**: Create bill
       ```sql
       INSERT INTO core_bill 
       (patient_id, service_type_id, total_amount, status, 
        due_date, transaction_id, bill_date)
       VALUES (%s, %s, %s, 'Pending', %s, %s, %s)
       ```
6. Redirect to dashboard
7. Bill automatically created for patient

**Database Tables Affected**:
- `core_labtest`
- `core_bill` (auto-created)
- `core_servicetype` (if needed)

---

## Patient Flows

### Flow 14: View Patient Profile

**Purpose**: Display patient's personal information and emergency contacts.

**Steps**:
1. Patient logs in → redirected to `/patient/dashboard`
2. Navigates to `/patient/profile`
3. **SQL Query**: Fetch patient profile
   ```sql
   SELECT * FROM core_patient WHERE user_id = %s
   ```
4. **SQL Query**: Fetch emergency contacts
   ```sql
   SELECT * FROM core_patientemergencycontact 
   WHERE patient_id = %s
   ORDER BY is_primary DESC
   ```
5. Display profile with all information
6. Patient can view but cannot edit (read-only)

**Database Tables Queried**:
- `core_patient`
- `core_patientemergencycontact`

---

### Flow 15: View Appointments

**Purpose**: Display patient's appointment history.

**Steps**:
1. Patient navigates to `/patient/appointments`
2. **SQL Query**: Fetch patient profile
   ```sql
   SELECT * FROM core_patient WHERE user_id = %s
   ```
3. **SQL Query**: Fetch appointments with doctor and hospital info
   ```sql
   SELECT a.*, d.full_name as doctor_name, d.specialization,
          h.name as hospital_name
   FROM core_appointment a
   INNER JOIN core_doctor d ON a.doctor_id = d.doctor_id
   INNER JOIN core_hospital h ON d.hospital_id = h.hospital_id
   WHERE a.patient_id = %s
   ORDER BY a.date_and_time DESC
   ```
4. Display appointments with doctor and hospital details
5. Patient can click to view appointment details

**Database Tables Queried**:
- `core_patient`
- `core_appointment`
- `core_doctor`
- `core_hospital`

---

### Flow 16: View Appointment Details with Prescriptions

**Purpose**: Display appointment details and associated prescriptions.

**Steps**:
1. Patient clicks on an appointment
2. **SQL Query**: Fetch appointment details
   ```sql
   SELECT a.*, d.full_name as doctor_name, d.specialization,
          h.name as hospital_name, h.address as hospital_address
   FROM core_appointment a
   INNER JOIN core_doctor d ON a.doctor_id = d.doctor_id
   INNER JOIN core_hospital h ON d.hospital_id = h.hospital_id
   WHERE a.appointment_id = %s AND a.patient_id = %s
   ```
3. **SQL Query**: Fetch prescriptions for this appointment
   ```sql
   SELECT * FROM core_prescription 
   WHERE appointment_id = %s
   ```
4. **SQL Query**: Fetch prescription items for each prescription
   ```sql
   SELECT pi.*, m.name as medicine_name, m.type as medicine_type
   FROM core_prescriptionitem pi
   INNER JOIN core_medicine m ON pi.medicine_id = m.medicine_id
   WHERE pi.prescription_id = %s
   ```
5. Display appointment with diagnosis, prescriptions, and medicines

**Database Tables Queried**:
- `core_appointment`
- `core_doctor`
- `core_hospital`
- `core_prescription`
- `core_prescriptionitem`
- `core_medicine`

---

### Flow 17: View Bills

**Purpose**: Display all bills (hospital bills and pharmacy bills).

**Steps**:
1. Patient navigates to `/patient/bills`
2. **SQL Query**: Fetch patient profile
   ```sql
   SELECT * FROM core_patient WHERE user_id = %s
   ```
3. **SQL Query**: Fetch all bills
   ```sql
   SELECT b.*, st.name as service_type_name
   FROM core_bill b
   INNER JOIN core_servicetype st ON b.service_type_id = st.service_type_id
   WHERE b.patient_id = %s
   ORDER BY b.bill_date DESC
   ```
4. **SQL Query**: Fetch pharmacy bills
   ```sql
   SELECT pb.*, p.name as pharmacy_name, pr.prescription_id
   FROM core_pharmacybill pb
   INNER JOIN core_pharmacy p ON pb.pharmacy_id = p.pharmacy_id
   LEFT JOIN core_prescription pr ON pb.prescription_id = pr.prescription_id
   INNER JOIN core_bill b ON pb.bill_id = b.bill_id
   WHERE b.patient_id = %s
   ```
5. Display all bills with status and amounts

**Database Tables Queried**:
- `core_patient`
- `core_bill`
- `core_servicetype`
- `core_pharmacybill`
- `core_pharmacy`
- `core_prescription`

---

## Business Logic Flows

### Flow 18: Prescription Expiry Validation

**Purpose**: Prevent using expired prescriptions for pharmacy bills.

**Steps**:
1. System receives prescription_id for pharmacy bill
2. **SQL Query**: Fetch prescription
   ```sql
   SELECT * FROM core_prescription WHERE prescription_id = %s
   ```
3. Check if `valid_until` date is in the past
   ```python
   if prescription.valid_until < date.today():
       raise ValidationError("Prescription has expired")
   ```
4. If expired: Show error, prevent bill creation
5. If valid: Proceed with bill creation

**Database Tables Queried**:
- `core_prescription`

---

### Flow 19: Stock Validation

**Purpose**: Ensure sufficient stock before creating pharmacy bill.

**Steps**:
1. System receives prescription_id and pharmacy_id
2. **SQL Query**: Fetch prescription items
   ```sql
   SELECT * FROM core_prescriptionitem 
   WHERE prescription_id = %s
   ```
3. For each item:
   - **SQL Query**: Check stock availability
     ```sql
     SELECT stock_quantity FROM core_pharmacymedicine
     WHERE pharmacy_id = %s AND medicine_id = %s
     ```
   - Compare requested quantity with stock_quantity
   - If insufficient: Show error, prevent bill creation
4. If all items available: Proceed with bill creation
5. **SQL Query**: Reduce stock after bill creation
   ```sql
   UPDATE core_pharmacymedicine
   SET stock_quantity = stock_quantity - %s
   WHERE pharmacy_id = %s AND medicine_id = %s
   ```

**Database Tables Queried**:
- `core_prescriptionitem`
- `core_pharmacymedicine`

**Database Tables Affected**:
- `core_pharmacymedicine` (stock reduced)

---

### Flow 20: Hospital Data Isolation

**Purpose**: Ensure admin only sees their hospital's data.

**Steps**:
1. Admin makes any request
2. System gets admin's hospital_id from current_user
3. All SQL queries include hospital_id filter:
   ```sql
   -- Example: Get departments
   SELECT * FROM core_department WHERE hospital_id = %s
   
   -- Example: Get doctors
   SELECT * FROM core_doctor WHERE hospital_id = %s
   
   -- Example: Get labs
   SELECT * FROM core_lab WHERE hospital_id = %s
   ```
4. Only data for admin's hospital is returned

**Database Tables Queried**: All hospital-related tables with hospital_id filter

---

## Data Flow Diagrams

### Complete Patient Journey

```
Patient Registration
    ↓
Login
    ↓
Patient Dashboard
    ↓
View Appointments
    ↓
View Appointment Details
    ↓
View Prescriptions
    ↓
View Bills
```

### Complete Doctor Workflow

```
Doctor Login
    ↓
View Appointments
    ↓
Update Appointment (Add Diagnosis)
    ↓
Create Prescription
    ↓
Add Prescription Items (Medicines)
    ↓
Order Lab Test
    ↓
Update Lab Test Status → Auto-Billing
```

### Complete Admin Workflow

```
Admin Login
    ↓
View Dashboard (Statistics)
    ↓
Manage Departments
    ↓
Manage Labs
    ↓
Add Doctors
    ↓
Manage Pharmacy Stock
```

---

## Summary

**Total Flows Documented**: 20

- **Authentication**: 3 flows
- **Admin**: 4 flows
- **Doctor**: 6 flows
- **Patient**: 4 flows
- **Business Logic**: 3 flows

**All flows use explicit raw MySQL queries** - no ORM usage.

For implementation details, see the `routes/` directory where all SQL queries are visible in the code.

