<?php
require_once __DIR__ . '/../db.php';
requireRole('DOCTOR');

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM core_doctor WHERE user_id = ?");
$stmt->execute([$user_id]);
$doctor = $stmt->fetch();

if (!$doctor) {
    die("Doctor record not found. Please contact administrator.");
}

$doctor_id = $doctor['doctor_id'];
$hospital_id = $doctor['hospital_id'];

$appointment_id = $_GET['appointment_id'] ?? null;
$patient_id_from_appt = null;
$appointment_info = null;

if ($appointment_id) {
    $stmt = $pdo->prepare("SELECT a.*, p.patient_id, p.full_name as patient_name 
                           FROM core_appointment a 
                           INNER JOIN core_patient p ON a.patient_id = p.patient_id 
                           WHERE a.appointment_id = ? AND a.doctor_id = ?");
    $stmt->execute([$appointment_id, $doctor_id]);
    $appointment_info = $stmt->fetch();
    if ($appointment_info) {
        $patient_id_from_appt = $appointment_info['patient_id'];
    }
}

$patients_sql = "SELECT DISTINCT p.patient_id, p.full_name, p.national_id, p.phone, p.email 
                 FROM core_patient p
                 INNER JOIN core_appointment a ON p.patient_id = a.patient_id
                 WHERE a.doctor_id = ?
                 ORDER BY p.full_name";
$stmt = $pdo->prepare($patients_sql);
$stmt->execute([$doctor_id]);
$patients = $stmt->fetchAll();

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = $_POST['patient_id'] ?? null;
    $test_type = trim($_POST['test_type'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');
    
    if (!$patient_id) {
        $error = "Please select a patient.";
    } elseif (empty($test_type)) {
        $error = "Please enter the lab tests required.";
    } else {
        try {
            $sql = "INSERT INTO core_labtest (patient_id, test_type, ordered_by_id, remarks, status, date_and_time, appointment_id)
                    VALUES (?, ?, ?, ?, 'Ordered', NOW(), ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$patient_id, $test_type, $doctor_id, $remarks, $appointment_id]);
            
            setFlash("Lab test order submitted successfully. Admin will process it and create a bill.");
            
            if ($appointment_id) {
                header("Location: /doctor/appointment_detail.php?id=" . $appointment_id);
            } else {
                header("Location: /doctor/lab_tests.php");
            }
            exit;
        } catch (Exception $e) {
            $error = "Error ordering lab test: " . $e->getMessage();
        }
    }
}

include __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <h2><i class="fas fa-vial mr-2"></i>Order Lab Test</h2>
    <div>
        <?php if ($appointment_id): ?>
            <a href="/doctor/appointment_detail.php?id=<?= $appointment_id ?>" class="btn btn-secondary mr-2">
                <i class="fas fa-arrow-left mr-2"></i>Back to Appointment
            </a>
        <?php endif; ?>
        <a href="/doctor/lab_tests.php" class="btn btn-info mr-2">
            <i class="fas fa-list mr-2"></i>View All Tests
        </a>
        <a href="/doctor/dashboard.php" class="btn btn-secondary">
            <i class="fas fa-home mr-2"></i>Dashboard
        </a>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-error mb-4">
        <i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<?php if ($appointment_info): ?>
    <div class="alert alert-info mb-4">
        <i class="fas fa-info-circle mr-2"></i>
        <strong>Ordering for:</strong> <?= htmlspecialchars($appointment_info['patient_name']) ?> 
        (Appointment: <?= date('M d, Y H:i', strtotime($appointment_info['date_and_time'])) ?>)
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3>Lab Test Order Form</h3>
    </div>
    <div class="card-body">
        <form method="post" id="labTestForm">
            <div class="form-group">
                <label>Patient <span class="text-danger">*</span></label>
                <?php if ($patient_id_from_appt): ?>
                    <input type="hidden" name="patient_id" value="<?= $patient_id_from_appt ?>">
                    <input type="text" class="form-control" 
                           value="<?= htmlspecialchars($appointment_info['patient_name']) ?>" 
                           readonly style="background-color: #f3f4f6;">
                    <small class="form-text text-muted">Patient from appointment</small>
                <?php else: ?>
                    <select name="patient_id" id="patient_select" class="form-control" required>
                        <option value="">-- Select Patient --</option>
                        <?php foreach ($patients as $p): ?>
                            <option value="<?= $p['patient_id'] ?>">
                                <?= htmlspecialchars($p['full_name']) ?> 
                                (ID: <?= htmlspecialchars($p['national_id']) ?>) 
                                - <?= htmlspecialchars($p['phone']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text text-muted">Select from patients who have appointments with you.</small>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label>Lab Tests Required <span class="text-danger">*</span></label>
                <textarea name="test_type" class="form-control" rows="6" 
                          placeholder="Enter all the lab tests required, one per line or separated by commas.&#10;&#10;Example:&#10;Complete Blood Count (CBC)&#10;Lipid Profile&#10;Blood Sugar (Fasting)&#10;Liver Function Test (LFT)&#10;Kidney Function Test (KFT)" 
                          required><?= htmlspecialchars($_POST['test_type'] ?? '') ?></textarea>
                <small class="form-text text-muted">List all lab tests needed. Admin will process this order and create a bill.</small>
            </div>
            
            <div class="form-group">
                <label>Remarks / Instructions</label>
                <textarea name="remarks" class="form-control" rows="3" 
                          placeholder="Any special instructions or notes..."><?= htmlspecialchars($_POST['remarks'] ?? '') ?></textarea>
            </div>
            
            <div class="form-group mt-4">
                <button type="submit" class="btn btn-success btn-lg">
                    <i class="fas fa-paper-plane mr-2"></i>Order Lab Test
                </button>
                <?php if ($appointment_id): ?>
                    <a href="/doctor/appointment_detail.php?id=<?= $appointment_id ?>" class="btn btn-secondary btn-lg ml-2">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </a>
                <?php else: ?>
                    <a href="/doctor/dashboard.php" class="btn btn-secondary btn-lg ml-2">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
