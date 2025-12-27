<?php
// admin/doctors.php
require_once __DIR__ . '/../db.php';
requireRole('ADMIN');

$hospital_id = $_SESSION['hospital_id'];

// Get Doctors
$sql = "SELECT d.*, dept.dept_name 
        FROM core_doctor d
        LEFT JOIN core_department dept ON d.dept_id = dept.dept_id
        WHERE d.hospital_id = ?
        ORDER BY d.full_name";
$stmt = $pdo->prepare($sql);
$stmt->execute([$hospital_id]);
$doctors = $stmt->fetchAll();

include __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <h2>Doctors</h2>
    <a href="/admin/doctor_form.php" class="btn btn-primary">Add Doctor</a>
</div>

<div class="card">
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Specialization</th>
                    <th>Department</th>
                    <th>Phone</th>
                    <th>Shift</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($doctors as $doc): ?>
                <tr>
                    <td>Dr. <?= htmlspecialchars($doc['full_name']) ?></td>
                    <td><?= htmlspecialchars($doc['specialization']) ?></td>
                    <td><?= htmlspecialchars($doc['dept_name'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($doc['phone']) ?></td>
                    <td><?= htmlspecialchars($doc['shift_timing']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($doctors)): ?>
                <tr>
                    <td colspan="5" class="text-center">No doctors found.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
