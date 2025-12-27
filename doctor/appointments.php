<?php
// doctor/appointments.php
require_once __DIR__ . '/../db.php';
requireRole('DOCTOR');

$user_id = $_SESSION['user_id'];

// Get Doctor ID
$stmt = $pdo->prepare("SELECT doctor_id FROM core_doctor WHERE user_id = ?");
$stmt->execute([$user_id]);
$doctor = $stmt->fetch();
$doctor_id = $doctor['doctor_id'];

$status = $_GET['status'] ?? null;
$sql = "SELECT a.*, p.full_name as patient_name
        FROM core_appointment a
        INNER JOIN core_patient p ON a.patient_id = p.patient_id
        WHERE a.doctor_id = ?";

$params = [$doctor_id];

if ($status) {
    $sql .= " AND a.status = ?";
    $params[] = $status;
}

$sql .= " ORDER BY a.date_and_time DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$appointments = $stmt->fetchAll();

include __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <h2>All Appointments</h2>
    <div class="btn-group">
        <a href="?status=" class="btn <?= !$status ? 'btn-primary' : 'btn-secondary' ?>">All</a>
        <a href="?status=Scheduled" class="btn <?= $status == 'Scheduled' ? 'btn-primary' : 'btn-secondary' ?>">Scheduled</a>
        <a href="?status=Completed" class="btn <?= $status == 'Completed' ? 'btn-primary' : 'btn-secondary' ?>">Completed</a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>Date & Time</th>
                    <th>Patient</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $apt): ?>
                <tr>
                    <td><?= date('M d, Y H:i', strtotime($apt['date_and_time'])) ?></td>
                    <td><?= htmlspecialchars($apt['patient_name']) ?></td>
                    <td><?= htmlspecialchars($apt['reason_for_visit']) ?></td>
                    <td>
                        <span class="badge badge-<?= strtolower($apt['status']) ?>">
                            <?= htmlspecialchars($apt['status']) ?>
                        </span>
                    </td>
                    <td>
                        <a href="/doctor/appointment_detail.php?id=<?= $apt['appointment_id'] ?>" class="btn-sm btn-info">View</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
