<?php
// admin/departments.php
require_once __DIR__ . '/../db.php';
requireRole('ADMIN');

$hospital_id = $_SESSION['hospital_id'];

// Get Departments
$stmt = $pdo->prepare("SELECT * FROM core_department WHERE hospital_id = ? ORDER BY dept_name");
$stmt->execute([$hospital_id]);
$departments = $stmt->fetchAll();

include __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <h2>Departments</h2>
    <a href="/admin/department_form.php" class="btn btn-primary">Add Department</a>
</div>

<div class="card">
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>Department Name</th>
                    <th>Floor</th>
                    <th>Extension</th>
                    <th>Operating Hours</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($departments as $dept): ?>
                <tr>
                    <td><?= htmlspecialchars($dept['dept_name']) ?></td>
                    <td><?= htmlspecialchars($dept['floor']) ?></td>
                    <td><?= htmlspecialchars($dept['extension'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($dept['operating_hours']) ?></td>
                    <td>
                        <a href="/admin/department_form.php?id=<?= $dept['dept_id'] ?>" class="btn-sm btn-secondary">Edit</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($departments)): ?>
                <tr>
                    <td colspan="5" class="text-center">No departments found.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
