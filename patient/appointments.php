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

$sql = "SELECT a.*, d.full_name as doctor_name, d.specialization, h.name as hospital_name
        FROM core_appointment a
        INNER JOIN core_doctor d ON a.doctor_id = d.doctor_id
        INNER JOIN core_hospital h ON d.hospital_id = h.hospital_id
        WHERE a.patient_id = ?
        ORDER BY a.date_and_time DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$patient_id]);
$appointments = $stmt->fetchAll();

include __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <h2>My Appointments</h2>
    <a href="/patient/book_appointment.php" class="btn btn-primary">
        <i class="fas fa-calendar-plus mr-2"></i>Book New Appointment
    </a>
</div>

<div class="card">
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>Date & Time</th>
                    <th>Doctor</th>
                    <th>Hospital</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($appointments)): ?>
                <tr>
                    <td colspan="5" class="text-center py-8 text-gray-500">
                        No appointments found. Your appointment history will appear here once you have appointments.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($appointments as $apt): ?>
                <tr>
                    <td><?= date('M d, Y H:i', strtotime($apt['date_and_time'])) ?></td>
                    <td>
                        Dr. <?= htmlspecialchars($apt['doctor_name']) ?><br>
                        <small class="text-muted"><?= htmlspecialchars($apt['specialization']) ?></small>
                    </td>
                    <td><?= htmlspecialchars($apt['hospital_name']) ?></td>
                    <td>
                        <span class="badge badge-<?= strtolower($apt['status']) ?>">
                            <?= htmlspecialchars($apt['status']) ?>
                        </span>
                    </td>
                    <td>
                        <a href="/patient/appointment_detail.php?id=<?= $apt['appointment_id'] ?>" class="btn-sm btn-info">View</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
