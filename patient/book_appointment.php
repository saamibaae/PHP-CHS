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
    
    $date_time = $date . ' ' . $time . ':00';
    
    if (strtotime($date_time) < time()) {
        setFlash("Cannot book appointments in the past.", "error");
        header("Location: /patient/book_appointment.php");
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("SELECT appointment_id FROM core_appointment WHERE doctor_id = ? AND date_and_time = ? AND status != 'Cancelled' FOR UPDATE");
        $stmt->execute([$doctor_id, $date_time]);
        if ($stmt->fetch()) {
            $pdo->rollBack();
            setFlash("This time slot is already booked. Please choose another time.", "error");
            header("Location: /patient/book_appointment.php");
            exit;
        }
        
        $stmt = $pdo->prepare("INSERT INTO core_appointment (patient_id, doctor_id, status, reason_for_visit, symptoms, visit_type, date_and_time) VALUES (?, ?, 'Scheduled', ?, ?, ?, ?)");
        $stmt->execute([$patient_id, $doctor_id, $reason, $symptoms, $visit_type, $date_time]);
        
        $pdo->commit();
        setFlash("Appointment booked successfully! It will appear in your appointments list.", "success");
        header("Location: /patient/appointments.php");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        setFlash("Error booking appointment: " . $e->getMessage(), "error");
        header("Location: /patient/book_appointment.php");
        exit;
    }
}

$hospitals = $pdo->query("SELECT hospital_id, name FROM core_hospital ORDER BY name")->fetchAll();

$selected_hospital_id = $_GET['hospital_id'] ?? null;
$selected_specialization = $_GET['specialization'] ?? null;
$selected_doctor_id = $_GET['doctor_id'] ?? null;

$specializations = [];
$doctors = [];

if ($selected_hospital_id) {
    $stmt = $pdo->prepare("SELECT DISTINCT d.specialization 
                          FROM core_doctor d 
                          WHERE d.hospital_id = ? 
                          ORDER BY d.specialization");
    $stmt->execute([$selected_hospital_id]);
    $specializations = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if ($selected_specialization) {
        $rating_table_exists = false;
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE 'core_doctorrating'");
            $rating_table_exists = $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            $rating_table_exists = false;
        }
        
        if ($rating_table_exists) {
            $stmt = $pdo->prepare("
                SELECT d.doctor_id, d.full_name, d.specialization, d.shift_timing, d.experience_yrs,
                       COUNT(DISTINCT a.patient_id) as patients_served,
                       COALESCE(AVG(dr.rating), 0) as avg_rating,
                       COUNT(dr.rating_id) as total_ratings
                FROM core_doctor d
                LEFT JOIN core_appointment a ON d.doctor_id = a.doctor_id AND a.status = 'Completed'
                LEFT JOIN core_doctorrating dr ON d.doctor_id = dr.doctor_id
                WHERE d.hospital_id = ? AND d.specialization = ?
                GROUP BY d.doctor_id, d.full_name, d.specialization, d.shift_timing, d.experience_yrs
                ORDER BY avg_rating DESC, patients_served DESC, d.full_name
            ");
        } else {
            $stmt = $pdo->prepare("
                SELECT d.doctor_id, d.full_name, d.specialization, d.shift_timing, d.experience_yrs,
                       COUNT(DISTINCT a.patient_id) as patients_served,
                       0 as avg_rating,
                       0 as total_ratings
                FROM core_doctor d
                LEFT JOIN core_appointment a ON d.doctor_id = a.doctor_id AND a.status = 'Completed'
                WHERE d.hospital_id = ? AND d.specialization = ?
                GROUP BY d.doctor_id, d.full_name, d.specialization, d.shift_timing, d.experience_yrs
                ORDER BY patients_served DESC, d.full_name
            ");
        }
        $stmt->execute([$selected_hospital_id, $selected_specialization]);
        $doctors = $stmt->fetchAll();
        
        foreach ($doctors as &$doctor) {
            $qual_sql = "SELECT q.degree_name, q.code, dq.year_obtained, dq.institution_name
                        FROM core_doctorqualification dq
                        INNER JOIN core_qualification q ON dq.qualification_id = q.qualification_id
                        WHERE dq.doctor_id = ?
                        ORDER BY dq.year_obtained DESC";
            $qual_stmt = $pdo->prepare($qual_sql);
            $qual_stmt->execute([$doctor['doctor_id']]);
            $doctor['qualifications'] = $qual_stmt->fetchAll();
        }
        unset($doctor);
    }
}

include __DIR__ . '/../templates/header.php';
?>

<div class="px-4 sm:px-0 mb-6">
    <h1 class="text-3xl font-bold text-gray-900">Book Appointment</h1>
    <p class="text-gray-600">Select a hospital, doctor, and available time slot</p>
</div>

<div class="bg-white shadow rounded-lg p-6">
    <form method="POST" id="appointmentForm" action="">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-user-md mr-2 text-purple-500"></i>Select Doctor
                </label>
                <select name="doctor_id" id="doctor_id" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" required onchange="loadDoctorSchedule(); showDoctorDetails();" <?= empty($doctors) ? 'disabled' : '' ?>>
                    <option value="">-- Select Doctor --</option>
                    <?php foreach ($doctors as $doc): 
                        $rating = round($doc['avg_rating'], 1);
                        $rating_display = $doc['total_ratings'] > 0 ? $rating . ' ⭐ (' . $doc['total_ratings'] . ')' : 'No ratings';
                    ?>
                    <option value="<?= $doc['doctor_id'] ?>" 
                            data-shift="<?= htmlspecialchars($doc['shift_timing']) ?>"
                            data-experience="<?= $doc['experience_yrs'] ?>"
                            data-patients="<?= $doc['patients_served'] ?>"
                            data-rating="<?= $rating ?>"
                            data-total-ratings="<?= $doc['total_ratings'] ?>"
                            data-qualifications="<?= htmlspecialchars(json_encode($doc['qualifications'])) ?>"
                            <?= $selected_doctor_id == $doc['doctor_id'] ? 'selected' : '' ?>>
                        Dr. <?= htmlspecialchars($doc['full_name']) ?> - <?= htmlspecialchars($doc['specialization']) ?> 
                        (<?= $rating_display ?>, <?= $doc['patients_served'] ?> patients)
                    </option>
                    <?php endforeach; ?>
                </select>
                <p id="shiftInfo" class="mt-2 text-sm text-gray-500"></p>
                
                <div id="doctorDetailsCard" class="mt-4 hidden bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                    <div class="flex justify-between items-start mb-3">
                        <h4 class="font-bold text-lg" id="doctorDetailsName"></h4>
                        <button type="button" onclick="document.getElementById('doctorDetailsCard').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mb-3">
                        <div>
                            <span class="text-sm text-gray-600">Experience:</span>
                            <span class="font-semibold" id="doctorDetailsExperience"></span>
                        </div>
                        <div>
                            <span class="text-sm text-gray-600">Patients Served:</span>
                            <span class="font-semibold" id="doctorDetailsPatients"></span>
                        </div>
                        <div>
                            <span class="text-sm text-gray-600">Rating:</span>
                            <span class="font-semibold text-yellow-600" id="doctorDetailsRating"></span>
                        </div>
                        <div>
                            <span class="text-sm text-gray-600">Shift:</span>
                            <span class="font-semibold" id="doctorDetailsShift"></span>
                        </div>
                    </div>
                    
                    <div id="doctorDetailsQualifications" class="mt-3">
                        <span class="text-sm font-semibold text-gray-700 block mb-2">Qualifications:</span>
                        <div id="qualificationsList" class="space-y-2"></div>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="far fa-calendar mr-2 text-red-500"></i>Select Date
                </label>
                <input type="date" name="date" id="appointment_date" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" required min="<?= date('Y-m-d') ?>" onchange="loadAvailableTimes()">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="far fa-clock mr-2 text-orange-500"></i>Select Time
                </label>
                <select name="time" id="appointment_time" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" required disabled>
                    <option value="">-- Select Time --</option>
                </select>
                <p id="timeInfo" class="mt-2 text-sm text-gray-500"></p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-file-medical mr-2 text-teal-500"></i>Reason for Visit
                </label>
                <input type="text" name="reason" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="e.g., Regular checkup, Follow-up" required>
            </div>

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
    
    window.location.href = '/patient/book_appointment.php?hospital_id=' + hospitalId;
}

function loadDoctors() {
    const hospitalId = document.getElementById('hospital_id').value;
    const specialization = document.getElementById('specialization').value;
    const doctorSelect = document.getElementById('doctor_id');
    
    if (!hospitalId || !specialization) {
        doctorSelect.innerHTML = '<option value="">-- Select Doctor --</option>';
        doctorSelect.disabled = true;
        return;
    }
    
    window.location.href = '/patient/book_appointment.php?hospital_id=' + hospitalId + '&specialization=' + encodeURIComponent(specialization);
}

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

function showDoctorDetails() {
    const doctorSelect = document.getElementById('doctor_id');
    const selectedOption = doctorSelect.options[doctorSelect.selectedIndex];
    const detailsCard = document.getElementById('doctorDetailsCard');
    
    if (!selectedOption.value) {
        detailsCard.classList.add('hidden');
        return;
    }
    
    const doctorName = selectedOption.textContent.split(' - ')[0].trim();
    const experience = selectedOption.getAttribute('data-experience') || '0';
    const patients = selectedOption.getAttribute('data-patients') || '0';
    const rating = parseFloat(selectedOption.getAttribute('data-rating')) || 0;
    const totalRatings = selectedOption.getAttribute('data-total-ratings') || '0';
    const shift = selectedOption.getAttribute('data-shift') || 'N/A';
    const qualificationsJson = selectedOption.getAttribute('data-qualifications');
    
    document.getElementById('doctorDetailsName').textContent = doctorName;
    document.getElementById('doctorDetailsExperience').textContent = experience + ' years';
    document.getElementById('doctorDetailsPatients').textContent = patients;
    
    let ratingHtml = '';
    if (rating > 0) {
        ratingHtml = rating.toFixed(1) + ' ⭐ (' + totalRatings + ' ratings)';
    } else {
        ratingHtml = 'No ratings yet';
    }
    document.getElementById('doctorDetailsRating').innerHTML = ratingHtml;
    document.getElementById('doctorDetailsShift').textContent = shift;
    
    const qualificationsList = document.getElementById('qualificationsList');
    qualificationsList.innerHTML = '';
    
    if (qualificationsJson && qualificationsJson !== 'null') {
        try {
            const qualifications = JSON.parse(qualificationsJson);
            if (qualifications && qualifications.length > 0) {
                qualifications.forEach(qual => {
                    const qualDiv = document.createElement('div');
                    qualDiv.className = 'text-sm bg-blue-50 p-2 rounded border-l-4 border-blue-500';
                    qualDiv.innerHTML = `
                        <strong>${qual.degree_name}</strong> (${qual.code})<br>
                        <span class="text-gray-600">${qual.institution_name} - ${qual.year_obtained}</span>
                    `;
                    qualificationsList.appendChild(qualDiv);
                });
            } else {
                qualificationsList.innerHTML = '<p class="text-gray-500 text-sm">No qualifications listed</p>';
            }
        } catch (e) {
            qualificationsList.innerHTML = '<p class="text-gray-500 text-sm">No qualifications listed</p>';
        }
    } else {
        qualificationsList.innerHTML = '<p class="text-gray-500 text-sm">No qualifications listed</p>';
    }
    
    detailsCard.classList.remove('hidden');
}

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
    
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 second timeout
    
    fetch(`/patient/check_availability.php?doctor_id=${doctorId}&date=${date}`, {
        signal: controller.signal
    })
        .then(response => {
            clearTimeout(timeoutId);
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }
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
            clearTimeout(timeoutId);
            console.error('Error:', error);
            timeSelect.disabled = false;
            if (error.name === 'AbortError') {
                timeInfo.textContent = 'Request timed out. Please try again.';
            } else {
                timeInfo.textContent = 'Error loading availability. Please try again.';
            }
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

document.addEventListener('DOMContentLoaded', function() {
    const doctorId = document.getElementById('doctor_id').value;
    const date = document.getElementById('appointment_date').value;
    
    if (doctorId && date) {
        loadAvailableTimes();
    }
    
    if (doctorId) {
        loadDoctorSchedule();
        showDoctorDetails();
    }
});
</script>

<?php include __DIR__ . '/../templates/footer.php'; ?>

