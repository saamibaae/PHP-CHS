<?php
require_once __DIR__ . '/../db.php';
requireRole('ADMIN');

$hospital_id = $_SESSION['hospital_id'];
$admission_id = $_GET['id'] ?? null;

if (!$admission_id) {
    setFlash("Invalid admission ID.", "error");
    header("Location: /admin/admissions.php");
    exit;
}

if (!createAdmissionTableIfNeeded()) {
    setFlash("Admission system is not set up. Please run setup_db.php or migrate_admission_table.php", "error");
    header("Location: /admin/admissions.php");
    exit;
}

$sql = "SELECT a.*, 
        p.full_name, p.national_id, p.patient_id, p.date_of_birth, p.gender, p.blood_type, p.phone, p.email, p.address,
        h.name as hospital_name, h.address as hospital_address, h.phone as hospital_phone,
        u1.username as admitted_by_username, u1.full_name as admitted_by_name,
        u2.username as discharged_by_username, u2.full_name as discharged_by_name
        FROM core_admission a
        INNER JOIN core_patient p ON a.patient_id = p.patient_id
        INNER JOIN core_hospital h ON a.hospital_id = h.hospital_id
        LEFT JOIN core_customuser u1 ON a.admitted_by_user_id = u1.id
        LEFT JOIN core_customuser u2 ON a.discharged_by_user_id = u2.id
        WHERE a.admission_id = ? AND a.hospital_id = ?";
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$admission_id, $hospital_id]);
    $admission = $stmt->fetch();
} catch (PDOException $e) {
    setFlash("Error accessing admission records: " . $e->getMessage(), "error");
    header("Location: /admin/admissions.php");
    exit;
}

if (!$admission) {
    setFlash("Admission not found or access denied.", "error");
    header("Location: /admin/admissions.php");
    exit;
}

$appointments_sql = "SELECT a.*, d.full_name as doctor_name, d.specialization
                     FROM core_appointment a
                     INNER JOIN core_doctor d ON a.doctor_id = d.doctor_id
                     WHERE a.patient_id = ? 
                     AND a.date_and_time >= ? 
                     AND (a.date_and_time <= ? OR ? IS NULL)
                     ORDER BY a.date_and_time DESC";
$appointments_stmt = $pdo->prepare($appointments_sql);
$appointments_stmt->execute([
    $admission['patient_id'], 
    $admission['admission_date'], 
    $admission['discharge_date'], 
    $admission['discharge_date']
]);
$appointments = $appointments_stmt->fetchAll();

$lab_tests_sql = "SELECT lt.*, d.full_name as doctor_name
                  FROM core_labtest lt
                  INNER JOIN core_doctor d ON lt.ordered_by_id = d.doctor_id
                  WHERE lt.patient_id = ?
                  AND lt.date_and_time >= ?
                  AND (lt.date_and_time <= ? OR ? IS NULL)
                  ORDER BY lt.date_and_time DESC";
$lab_tests_stmt = $pdo->prepare($lab_tests_sql);
$lab_tests_stmt->execute([
    $admission['patient_id'],
    $admission['admission_date'],
    $admission['discharge_date'],
    $admission['discharge_date']
]);
$lab_tests = $lab_tests_stmt->fetchAll();

include __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <h2><i class="fas fa-procedures mr-2"></i>Admission Details</h2>
    <div>
        <?php if ($admission['status'] == 'Admitted'): ?>
            <a href="/admin/discharge_patient.php?id=<?= $admission_id ?>" 
               class="btn btn-danger" 
               onclick="return confirm('Are you sure you want to discharge this patient?');">
                <i class="fas fa-sign-out-alt mr-2"></i>Discharge Patient
            </a>
        <?php endif; ?>
        <a href="/admin/admissions.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Back to Admissions
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h3><i class="fas fa-user mr-2"></i>Patient Information</h3>
            </div>
            <div class="card-body">
                <p><strong>Name:</strong> <?= htmlspecialchars($admission['full_name']) ?></p>
                <p><strong>National ID:</strong> <?= htmlspecialchars($admission['national_id']) ?></p>
                <p><strong>Date of Birth:</strong> <?= htmlspecialchars($admission['date_of_birth']) ?></p>
                <p><strong>Gender:</strong> <?= htmlspecialchars($admission['gender']) ?></p>
                <p><strong>Blood Type:</strong> <span class="badge badge-danger"><?= htmlspecialchars($admission['blood_type']) ?></span></p>
                <p><strong>Phone:</strong> <?= htmlspecialchars($admission['phone']) ?></p>
                <?php if ($admission['email']): ?>
                    <p><strong>Email:</strong> <?= htmlspecialchars($admission['email']) ?></p>
                <?php endif; ?>
                <?php if ($admission['address']): ?>
                    <p><strong>Address:</strong> <?= htmlspecialchars($admission['address']) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h3><i class="fas fa-hospital mr-2"></i>Admission Details</h3>
            </div>
            <div class="card-body">
                <p><strong>Admission ID:</strong> #<?= $admission['admission_id'] ?></p>
                <p><strong>Hospital:</strong> <?= htmlspecialchars($admission['hospital_name']) ?></p>
                <p><strong>Bed Number:</strong> <span class="badge badge-primary font-bold"><?= htmlspecialchars($admission['bed_number']) ?></span></p>
                <p><strong>Status:</strong> 
                    <?php if ($admission['status'] == 'Admitted'): ?>
                        <span class="badge badge-success">Admitted</span>
                    <?php else: ?>
                        <span class="badge badge-danger">Discharged</span>
                    <?php endif; ?>
                </p>
                <p><strong>Admission Date:</strong> <?= date('M d, Y H:i A', strtotime($admission['admission_date'])) ?></p>
                <?php if ($admission['discharge_date']): ?>
                    <p><strong>Discharge Date:</strong> <?= date('M d, Y H:i A', strtotime($admission['discharge_date'])) ?></p>
                    <?php
                    $admission_duration = (strtotime($admission['discharge_date']) - strtotime($admission['admission_date'])) / (60 * 60 * 24);
                    ?>
                    <p><strong>Duration:</strong> <?= round($admission_duration, 1) ?> days</p>
                <?php else: ?>
                    <?php
                    $admission_duration = (time() - strtotime($admission['admission_date'])) / (60 * 60 * 24);
                    ?>
                    <p><strong>Duration (Current):</strong> <?= round($admission_duration, 1) ?> days</p>
                <?php endif; ?>
                <?php if ($admission['reason']): ?>
                    <p><strong>Reason for Admission:</strong></p>
                    <div class="p-3 bg-gray-50 rounded">
                        <?= nl2br(htmlspecialchars($admission['reason'])) ?>
                    </div>
                <?php endif; ?>
                <hr>
                <p><strong>Admitted By:</strong> <?= htmlspecialchars($admission['admitted_by_name'] ?? $admission['admitted_by_username'] ?? 'N/A') ?></p>
                <?php if ($admission['discharged_by_name'] || $admission['discharged_by_username']): ?>
                    <p><strong>Discharged By:</strong> <?= htmlspecialchars($admission['discharged_by_name'] ?? $admission['discharged_by_username']) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($appointments)): ?>
<div class="card mb-4">
    <div class="card-header">
        <h3><i class="fas fa-calendar-check mr-2"></i>Appointments During Admission</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Doctor</th>
                        <th>Specialization</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $apt): ?>
                    <tr>
                        <td><?= date('M d, Y H:i', strtotime($apt['date_and_time'])) ?></td>
                        <td>Dr. <?= htmlspecialchars($apt['doctor_name']) ?></td>
                        <td><?= htmlspecialchars($apt['specialization']) ?></td>
                        <td>
                            <span class="badge badge-<?= strtolower($apt['status']) ?>"><?= htmlspecialchars($apt['status']) ?></span>
                        </td>
                        <td>
                            <a href="/admin/appointment_detail.php?id=<?= $apt['appointment_id'] ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($lab_tests)): ?>
<div class="card mb-4">
    <div class="card-header">
        <h3><i class="fas fa-vial mr-2"></i>Lab Tests During Admission</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Test Type</th>
                        <th>Ordered By</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lab_tests as $test): ?>
                    <tr>
                        <td><?= date('M d, Y H:i', strtotime($test['date_and_time'])) ?></td>
                        <td><?= htmlspecialchars(substr($test['test_type'], 0, 50)) ?><?= strlen($test['test_type']) > 50 ? '...' : '' ?></td>
                        <td>Dr. <?= htmlspecialchars($test['doctor_name']) ?></td>
                        <td>
                            <span class="badge badge-<?= strtolower($test['status']) ?>"><?= htmlspecialchars($test['status']) ?></span>
                        </td>
                        <td>
                            <a href="/admin/lab_test_process.php?id=<?= $test['test_id'] ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../templates/footer.php'; ?>

