<?php
require_once __DIR__ . '/../db.php';
requireRole('PATIENT');

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT patient_id FROM core_patient WHERE user_id = ?");
$stmt->execute([$user_id]);
$patient_id = $stmt->fetchColumn();

if (!$patient_id) {
    die("Patient record not found. Please contact administrator.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctor_id = $_POST['doctor_id'] ?? null;
    $date = $_POST['date'] ?? null;
    $time = $_POST['time'] ?? null;
    $reason = $_POST['reason'] ?? '';
    $symptoms = $_POST['symptoms'] ?? '';
    $visit_type = 'In-person'; // Default visit type
    
    if (!$doctor_id || !$date || !$time) {
        setFlash("Please fill all required fields.", "error");
        header("Location: /patient/book_appointment.php");
        exit;
    }
    
    // Combine date and time
    $date_time = $date . ' ' . $time . ':00';
    
    // Check if doctor is available at this time
    $stmt = $pdo->prepare("SELECT appointment_id FROM core_appointment WHERE doctor_id = ? AND date_and_time = ? AND status != 'Cancelled'");
    $stmt->execute([$doctor_id, $date_time]);
    if ($stmt->fetch()) {
        setFlash("This time slot is already booked. Please choose another time.", "error");
        header("Location: /patient/book_appointment.php");
        exit;
    }
    
    // Check if date is in the past
    if (strtotime($date_time) < time()) {
        setFlash("Cannot book appointments in the past.", "error");
        header("Location: /patient/book_appointment.php");
        exit;
    }
    
    // Create appointment
    $stmt = $pdo->prepare("INSERT INTO core_appointment (patient_id, doctor_id, status, reason_for_visit, symptoms, visit_type, date_and_time) VALUES (?, ?, 'Scheduled', ?, ?, ?, ?)");
    $stmt->execute([$patient_id, $doctor_id, $reason, $symptoms, $visit_type, $date_time]);
    
    setFlash("Appointment booked successfully! It will appear in your appointments list.", "success");
    header("Location: /patient/appointments.php");
    exit;
}

// Get all hospitals
$hospitals = $pdo->query("SELECT hospital_id, name FROM core_hospital ORDER BY name")->fetchAll();

// Get selected hospital and specialization (if any)
$selected_hospital_id = $_GET['hospital_id'] ?? null;
$selected_specialization = $_GET['specialization'] ?? null;
$selected_doctor_id = $_GET['doctor_id'] ?? null;

$specializations = [];
$doctors = [];

if ($selected_hospital_id) {
    // Get specializations for selected hospital
    $stmt = $pdo->prepare("SELECT DISTINCT d.specialization FROM core_doctor d WHERE d.hospital_id = ? ORDER BY d.specialization");
    $stmt->execute([$selected_hospital_id]);
    $specializations = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if ($selected_specialization) {
        // Get doctors for selected hospital and specialization
        $stmt = $pdo->prepare("SELECT d.doctor_id, d.full_name, d.specialization, d.shift_timing FROM core_doctor d WHERE d.hospital_id = ? AND d.specialization = ? ORDER BY d.full_name");
        $stmt->execute([$selected_hospital_id, $selected_specialization]);
        $doctors = $stmt->fetchAll();
    }
}

include __DIR__ . '/../templates/header.php';
?>

<div class="px-4 sm:px-0 mb-6">
    <h1 class="text-3xl font-bold text-gray-900">Book Appointment</h1>
    <p class="text-gray-600">Select a hospital, doctor, and available time slot</p>
</div>

<div class="bg-white shadow rounded-lg p-6">
    <form method="POST" id="appointmentForm">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Hospital Selection -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-hospital mr-2 text-blue-500"></i>Select Hospital
                </label>
                <select name="hospital_id" id="hospital_id" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" required onchange="loadSpecializations()">
                    <option value="">-- Select Hospital --</option>
                    <?php foreach ($hospitals as $h): ?>
                    <option value="<?= $h['hospital_id'] ?>" <?= $selected_hospital_id == $h['hospital_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($h['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Specialization Selection -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-stethoscope mr-2 text-green-500"></i>Specialization
                </label>
                <select name="specialization" id="specialization" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" required onchange="loadDoctors()" <?= empty($specializations) ? 'disabled' : '' ?>>
                    <option value="">-- Select Specialization --</option>
                    <?php foreach ($specializations as $spec): ?>
                    <option value="<?= htmlspecialchars($spec) ?>" <?= $selected_specialization == $spec ? 'selected' : '' ?>>
                        <?= htmlspecialchars($spec) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Doctor Selection -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-user-md mr-2 text-purple-500"></i>Select Doctor
                </label>
                <select name="doctor_id" id="doctor_id" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" required onchange="loadDoctorSchedule()" <?= empty($doctors) ? 'disabled' : '' ?>>
                    <option value="">-- Select Doctor --</option>
                    <?php foreach ($doctors as $doc): ?>
                    <option value="<?= $doc['doctor_id'] ?>" data-shift="<?= htmlspecialchars($doc['shift_timing']) ?>" <?= $selected_doctor_id == $doc['doctor_id'] ? 'selected' : '' ?>>
                        Dr. <?= htmlspecialchars($doc['full_name']) ?> - <?= htmlspecialchars($doc['specialization']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <p id="shiftInfo" class="mt-2 text-sm text-gray-500"></p>
            </div>

            <!-- Date Selection -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="far fa-calendar mr-2 text-red-500"></i>Select Date
                </label>
                <input type="date" name="date" id="appointment_date" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" required min="<?= date('Y-m-d') ?>" onchange="loadAvailableTimes()">
            </div>

            <!-- Time Selection -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="far fa-clock mr-2 text-orange-500"></i>Select Time
                </label>
                <select name="time" id="appointment_time" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" required disabled>
                    <option value="">-- Select Time --</option>
                </select>
                <p id="timeInfo" class="mt-2 text-sm text-gray-500"></p>
            </div>

            <!-- Reason for Visit -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-file-medical mr-2 text-teal-500"></i>Reason for Visit
                </label>
                <input type="text" name="reason" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="e.g., Regular checkup, Follow-up" required>
            </div>

            <!-- Symptoms -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-notes-medical mr-2 text-pink-500"></i>Symptoms / Notes
                </label>
                <textarea name="symptoms" rows="3" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Describe your symptoms or any additional notes..."></textarea>
            </div>
        </div>

        <div class="mt-6 flex justify-end space-x-3">
            <a href="/patient/appointments.php" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                Cancel
            </a>
            <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                <i class="fas fa-calendar-check mr-2"></i>Book Appointment
            </button>
        </div>
    </form>
</div>

<script>
// Generate time slots (9 AM to 5 PM, 30-minute intervals)
function generateTimeSlots() {
    const slots = [];
    for (let hour = 9; hour <= 17; hour++) {
        for (let minute = 0; minute < 60; minute += 30) {
            const timeStr = String(hour).padStart(2, '0') + ':' + String(minute).padStart(2, '0');
            slots.push(timeStr);
        }
    }
    return slots;
}

// Load specializations when hospital is selected
function loadSpecializations() {
    const hospitalId = document.getElementById('hospital_id').value;
    const specializationSelect = document.getElementById('specialization');
    const doctorSelect = document.getElementById('doctor_id');
    
    if (!hospitalId) {
        specializationSelect.innerHTML = '<option value="">-- Select Specialization --</option>';
        specializationSelect.disabled = true;
        doctorSelect.innerHTML = '<option value="">-- Select Doctor --</option>';
        doctorSelect.disabled = true;
        return;
    }
    
    // Redirect to reload with hospital_id
    window.location.href = '/patient/book_appointment.php?hospital_id=' + hospitalId;
}

// Load doctors when specialization is selected
function loadDoctors() {
    const hospitalId = document.getElementById('hospital_id').value;
    const specialization = document.getElementById('specialization').value;
    const doctorSelect = document.getElementById('doctor_id');
    
    if (!hospitalId || !specialization) {
        doctorSelect.innerHTML = '<option value="">-- Select Doctor --</option>';
        doctorSelect.disabled = true;
        return;
    }
    
    // Redirect to reload with hospital_id and specialization
    window.location.href = '/patient/book_appointment.php?hospital_id=' + hospitalId + '&specialization=' + encodeURIComponent(specialization);
}

// Load doctor schedule info
function loadDoctorSchedule() {
    const doctorSelect = document.getElementById('doctor_id');
    const selectedOption = doctorSelect.options[doctorSelect.selectedIndex];
    const shiftInfo = document.getElementById('shiftInfo');
    
    if (selectedOption.value) {
        const shift = selectedOption.getAttribute('data-shift');
        shiftInfo.textContent = 'Shift: ' + shift;
    } else {
        shiftInfo.textContent = '';
    }
    
    loadAvailableTimes();
}

// Load available times for selected doctor and date
function loadAvailableTimes() {
    const doctorId = document.getElementById('doctor_id').value;
    const date = document.getElementById('appointment_date').value;
    const timeSelect = document.getElementById('appointment_time');
    const timeInfo = document.getElementById('timeInfo');
    
    if (!doctorId || !date) {
        timeSelect.innerHTML = '<option value="">-- Select Time --</option>';
        timeSelect.disabled = true;
        return;
    }
    
    timeSelect.disabled = true;
    timeInfo.textContent = 'Loading available times...';
    
    // Fetch booked times for this doctor and date
    fetch(`/patient/check_availability.php?doctor_id=${doctorId}&date=${date}`)
        .then(response => response.json())
        .then(data => {
            const bookedTimes = data.booked_times || [];
            const allSlots = generateTimeSlots();
            
            timeSelect.innerHTML = '<option value="">-- Select Time --</option>';
            
            allSlots.forEach(slot => {
                if (!bookedTimes.includes(slot)) {
                    const option = document.createElement('option');
                    option.value = slot;
                    option.textContent = formatTime(slot);
                    timeSelect.appendChild(option);
                }
            });
            
            timeSelect.disabled = false;
            
            if (timeSelect.options.length === 1) {
                timeInfo.textContent = 'No available time slots for this date. Please select another date.';
                timeInfo.className = 'mt-2 text-sm text-red-500';
            } else {
                timeInfo.textContent = timeSelect.options.length - 1 + ' time slots available';
                timeInfo.className = 'mt-2 text-sm text-green-500';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            timeInfo.textContent = 'Error loading availability. Please try again.';
            timeInfo.className = 'mt-2 text-sm text-red-500';
        });
}

function formatTime(timeStr) {
    const [hours, minutes] = timeStr.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour > 12 ? hour - 12 : (hour === 0 ? 12 : hour);
    return `${displayHour}:${minutes} ${ampm}`;
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    const doctorId = document.getElementById('doctor_id').value;
    const date = document.getElementById('appointment_date').value;
    
    if (doctorId && date) {
        loadAvailableTimes();
    }
    
    if (doctorId) {
        loadDoctorSchedule();
    }
});
</script>

<?php include __DIR__ . '/../templates/footer.php'; ?>

