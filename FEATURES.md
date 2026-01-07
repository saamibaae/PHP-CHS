# Healthcare Management System - Features

## Flow explanation

### Admin Dashboard Flow

- Admit patients by searching patient and assigning a bed (`admin/admit_patient.php`)
- Discharge patients when treatment is completed (`admin/discharge_patient.php`)
- View and manage patient admissions (`admin/admissions.php`, `admin/admission_detail.php`)
- Add and manage hospital labs (`admin/labs.php`, `admin/lab_form.php`)
- Receive lab test requests from doctors (`admin/lab_test_orders.php`)
- Complete lab tests by entering results and cost (`admin/lab_test_process.php`)
- Automatically generate bills after lab test completion (`admin/lab_test_process.php`)
- Add and manage doctors (`admin/doctors.php`, `admin/doctor_form.php`)
- Create and manage hospital departments (`admin/departments.php`, `admin/department_form.php`)
- Manage pharmacy stock (`admin/pharmacy_stock.php`, `admin/add_stock.php`, `admin/stock_form.php`)

### Doctor Dashboard Flow

- View today's and upcoming appointments (`doctor/dashboard.php`, `doctor/appointments.php`)
- Open appointment details to view patient information (`doctor/appointment_detail.php`)
- Update diagnosis and appointment status (`doctor/appointment_detail.php`)
- Create prescriptions (`doctor/prescription_form.php`)
- Add medicines to prescriptions (`doctor/add_prescription_items.php`)
- Order lab tests for patients (`doctor/lab_test_order.php`)
- View ordered lab tests (`doctor/lab_tests.php`, `doctor/lab_test_detail.php`)
- Generate bills after completing appointments (`doctor/generate_bill.php`)

### Patient Dashboard Flow

- View dashboard summary (`patient/dashboard.php`)
- Book appointments by selecting doctor, date, and time (`patient/book_appointment.php`, `patient/check_availability.php`)
- View all appointments (`patient/appointments.php`)
- View appointment details (`patient/appointment_detail.php`)
- View and download prescription PDFs (`patient/prescription_pdf.php`)
- Track lab test status (`patient/lab_tests.php`, `patient/lab_test_detail.php`)
- Download lab test reports (`patient/lab_test_pdf.php`)
- View bills and payment status (`patient/bills.php`)
- View and edit profile information (`patient/profile.php`, `patient/edit_profile.php`)
- Rate doctors after completed appointments (`patient/rate_doctor.php`)

### Authentication Flow

- Login to the system (`login.php`)
- Register as a new patient (`register.php`)
- Logout from the system (`logout.php`)

---

## Admin Panel Features

### Dashboard Overview

**Page:** `admin/dashboard.php`

- View hospital occupancy statistics showing how many beds are currently in use versus total capacity
- See a list of all active departments along with how many doctors work in each one
- Review recent billing summaries with patient names, dates, amounts, and payment status
- Monitor capacity usage with a visual progress bar
- Quick access to manage patient admissions

### Doctor Management

**Pages:** `admin/doctors.php`, `admin/doctor_form.php`

- View a complete list of all doctors in the hospital with their details
- See each doctor's name, specialization, department assignment, phone number, and shift timing
- Add new doctors to the system by creating their account and profile
- Edit existing doctor information when needed
- The system validates that usernames and license numbers are unique before creating accounts

### Department Management

**Pages:** `admin/departments.php`, `admin/department_form.php`

- View all hospital departments in one place
- See department details like name, floor location, extension number, and operating hours
- Create new departments as the hospital expands
- Update department information when changes occur

### Lab Management

**Pages:** `admin/labs.php`, `admin/lab_form.php`

- View all labs available in the hospital
- See lab contact information including name, location, and phone number
- Add new labs to the system
- Edit lab details when information changes

### Pharmacy Stock Management

**Pages:** `admin/pharmacy_stock.php`, `admin/add_stock.php`, `admin/stock_form.php`, `admin/delete_stock.php`

- View all medicine stock items in the pharmacy
- See important statistics like total items, low stock warnings, expiring medicines, and total inventory value
- Search for specific medicines by name, type, or batch number
- Filter to see only low stock items or medicines expiring soon
- Add new medicines to stock with details like quantity, price, batch number, and expiry date
- Update stock quantities and prices when needed
- Remove stock items that are no longer needed
- The system highlights expired or expiring items with color coding

### Lab Test Processing

**Pages:** `admin/lab_test_orders.php`, `admin/lab_test_process.php`

- View all lab test orders submitted by doctors
- See test statistics including total orders, pending tests, and completed tests
- Search for tests by patient name, national ID, or test type
- Process lab tests by entering detailed results
- Set the cost for each test
- Assign tests to specific labs
- The system automatically creates bills when tests are completed
- Mark bills as paid when patients settle their accounts
- Once completed, both patients and doctors can access the test results

### Patient Admissions

**Pages:** `admin/admissions.php`, `admin/admit_patient.php`, `admin/admission_detail.php`, `admin/discharge_patient.php`

- View all patient admissions with current status
- Monitor hospital capacity with real-time statistics
- Get warnings when the hospital reaches 90% capacity
- Search for patients by name, national ID, or bed number
- Admit new patients by searching for their username and assigning a bed
- See complete admission details including duration and related appointments
- View appointments and lab tests that occurred during a patient's stay
- Discharge patients when treatment is complete
- The system automatically updates bed availability when patients are discharged

### Appointment Details

**Page:** `admin/appointment_detail.php`

- View comprehensive information about any appointment
- See patient details, doctor information, and visit notes
- Review all prescriptions issued during the appointment
- Check lab tests ordered during the visit
- Access prescription PDFs and process lab tests directly from this page

---

## Doctor Panel Features

### Dashboard

**Page:** `doctor/dashboard.php`

- See your name and specialization displayed prominently
- View today's schedule with all appointments listed by time
- See patient names and symptoms for each appointment
- Quick access buttons to consult with patients or create prescriptions
- Order lab tests directly from the dashboard
- View your full schedule for all dates

### Appointment Management

**Pages:** `doctor/appointments.php`, `doctor/appointment_detail.php`

- View all your appointments in one place
- Filter appointments by status (Scheduled, Completed, etc.)
- Open any appointment to see complete patient information
- Update appointment status as you work through your day
- Record diagnosis and treatment notes
- Set follow-up dates when needed
- Generate bills for completed appointments
- View and manage all prescriptions for each patient
- See all lab tests ordered for each patient
- Order new lab tests directly from the appointment page

### Prescription Management

**Pages:** `doctor/prescription_form.php`, `doctor/add_prescription_items.php`

- Create new prescriptions for patients during appointments
- Set prescription validity dates and refill counts
- Add multiple medicines to each prescription
- Specify dosage, frequency, duration, and quantity for each medicine
- Include instructions about when to take medicines (before/after meals)
- Add special notes or instructions for patients
- The system automatically creates medicine records if they don't exist yet

### Lab Test Ordering

**Pages:** `doctor/lab_test_order.php`, `doctor/lab_tests.php`, `doctor/lab_test_detail.php`

- Order lab tests for your patients during or after appointments
- Select from patients who have appointments with you
- Specify which tests are needed in detail
- Add remarks or special instructions for the lab
- View all lab tests you've ordered with their current status
- Search and filter your lab test orders
- See test results once they're completed by admin
- Update test results if needed before admin processes them
- Track costs and bill status for each test

### Bill Generation

**Page:** `doctor/generate_bill.php`

- Create bills for completed appointments
- Select the type of service provided
- Set the total amount for the consultation
- Set due dates for payment
- The system links bills to patients automatically

---

## Patient Panel Features

### Dashboard

**Page:** `patient/dashboard.php`

- See your name and blood type displayed at the top
- View your next three upcoming appointments at a glance
- Check recent billing history with payment status
- Monitor lab test updates and see when results are ready
- Review your recent prescriptions with medicine details
- Quick access to book new appointments

### Appointment Booking

**Pages:** `patient/book_appointment.php`, `patient/check_availability.php`

- Book appointments by selecting your preferred hospital
- Choose from available specializations
- See doctor profiles with ratings, experience, and qualifications
- Select a date that works for you
- Choose from available time slots that update in real-time
- Enter your reason for the visit
- Add any symptoms or notes you want the doctor to know
- The system checks availability automatically and prevents double-booking

### Appointment Management

**Pages:** `patient/appointments.php`, `patient/appointment_detail.php`

- View all your appointments in chronological order
- See appointment status (Scheduled, Completed, etc.)
- Open any appointment to see full details
- View doctor information and hospital details
- See diagnosis and treatment notes from your doctor
- Check follow-up dates if scheduled
- View all prescriptions from the appointment
- Download prescription PDFs for your records
- Rate your doctor after completed appointments

### Lab Test Tracking

**Pages:** `patient/lab_tests.php`, `patient/lab_test_detail.php`, `patient/lab_test_pdf.php`

- View all lab tests ordered for you
- See test status (Pending, In Progress, Completed)
- Search for specific tests by type or doctor
- View detailed test information including which lab is processing it
- See test results once they're completed
- Check bill status and costs
- Download PDF reports of completed tests for your records

### Prescription Access

**Page:** `patient/prescription_pdf.php`

- Download prescription PDFs from your appointment details
- Get a printable prescription with all medicine details
- See dosage instructions, frequency, and special notes
- Prescriptions include doctor information and validity dates

### Bills and Payments

**Page:** `patient/bills.php`

- View all your bills in one place
- See hospital service bills separately from pharmacy bills
- Check payment status for each bill
- View bill dates, amounts, and service types
- Bills are organized by date with most recent first

### Profile Management

**Pages:** `patient/profile.php`, `patient/edit_profile.php`

- View your complete profile information
- See personal details like name, ID, date of birth, and blood type
- Check your contact information and address
- View emergency contacts on file
- Edit your profile to update contact details
- Change your address, phone, email, or occupation
- Some information like national ID and blood type cannot be changed for security

### Doctor Rating

**Page:** `patient/rate_doctor.php`

- Rate doctors after completed appointments
- Give a star rating from 1 to 5
- Add optional comments about your experience
- Update your rating if you change your mind
- Your ratings help other patients choose doctors
- Ratings are anonymous and help improve service quality

---

## Authentication Features

### Login

**Page:** `login.php`

- Sign in with your username and password
- The system remembers your role and redirects you to the right dashboard
- Get error messages if your credentials are incorrect
- Link to registration page if you're a new patient

### Patient Registration

**Page:** `register.php`

- Create a new patient account
- Provide account details like username, password, and email
- Enter personal information including national ID and date of birth
- Add emergency contact information
- The system validates all information before creating your account
- Password must be strong (at least 8 characters with letters and numbers)
- After registration, you'll be redirected to login

### Logout

**Page:** `logout.php`

- Safely log out of your account
- Your session ends and you're redirected to the login page

---
