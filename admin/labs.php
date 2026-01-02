<?php
require_once __DIR__ . '/../db.php';
requireRole('ADMIN');

$hospital_id = $_SESSION['hospital_id'];

$stmt = $pdo->prepare("SELECT * FROM core_lab WHERE hospital_id = ? ORDER BY lab_name");
$stmt->execute([$hospital_id]);
$labs = $stmt->fetchAll();

include __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <h2>Labs</h2>
    <a href="/admin/lab_form.php" class="btn btn-primary">Add Lab</a>
</div>

<div class="card">
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>Lab Name</th>
                    <th>Location</th>
                    <th>Phone</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($labs as $lab): ?>
                <tr>
                    <td><?= htmlspecialchars($lab['lab_name']) ?></td>
                    <td><?= htmlspecialchars($lab['location']) ?></td>
                    <td><?= htmlspecialchars($lab['phone']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($labs)): ?>
                <tr>
                    <td colspan="3" class="text-center">No labs found.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
