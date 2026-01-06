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

$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

$bill_id_exists = checkColumnExists('core_labtest', 'bill_id');

if ($bill_id_exists) {
    $sql = "SELECT lt.*, l.lab_name, l.location as lab_location, l.phone as lab_phone,
            d.full_name as doctor_name, d.specialization,
            b.status as bill_status, b.bill_id
            FROM core_labtest lt
            LEFT JOIN core_lab l ON lt.lab_id = l.lab_id
            INNER JOIN core_doctor d ON lt.ordered_by_id = d.doctor_id
            LEFT JOIN core_bill b ON lt.bill_id = b.bill_id
            WHERE lt.patient_id = ?";
} else {
    $sql = "SELECT lt.*, l.lab_name, l.location as lab_location, l.phone as lab_phone,
            d.full_name as doctor_name, d.specialization,
            NULL as bill_status, NULL as bill_id
            FROM core_labtest lt
            LEFT JOIN core_lab l ON lt.lab_id = l.lab_id
            INNER JOIN core_doctor d ON lt.ordered_by_id = d.doctor_id
            WHERE lt.patient_id = ?";
}
        
$params = [$patient_id];

if ($status_filter) {
    $sql .= " AND lt.status = ?";
    $params[] = $status_filter;
}

if ($search) {
    $sql .= " AND (lt.test_type LIKE ? OR l.lab_name LIKE ? OR d.full_name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$sql .= " ORDER BY lt.date_and_time DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$lab_tests = $stmt->fetchAll();

$stats_sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'Ordered' THEN 1 ELSE 0 END) as ordered,
                SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed
              FROM core_labtest
              WHERE patient_id = ?";
$stats_stmt = $pdo->prepare($stats_sql);
$stats_stmt->execute([$patient_id]);
$stats = $stats_stmt->fetch();

include __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <h2><i class="fas fa-vial mr-2"></i>My Lab Tests</h2>
</div>

<div class="stats-grid mb-4">
    <div class="stat-card">
        <h3><?= $stats['total'] ?? 0 ?></h3>
        <p><i class="fas fa-vial mr-2"></i>Total Tests</p>
    </div>
    <div class="stat-card warning">
        <h3><?= $stats['ordered'] ?? 0 ?></h3>
        <p><i class="fas fa-clock mr-2"></i>Pending</p>
    </div>
    <div class="stat-card success">
        <h3><?= $stats['completed'] ?? 0 ?></h3>
        <p><i class="fas fa-check-circle mr-2"></i>Completed</p>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="form-inline">
            <div class="form-group mr-3">
                <input type="text" name="search" class="form-control" 
                       placeholder="Search by test type, lab name, or doctor..." 
                       value="<?= htmlspecialchars($search) ?>" style="min-width: 300px;">
            </div>
            <div class="form-group mr-3">
                <select name="status" class="form-control" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="Ordered" <?= $status_filter == 'Ordered' ? 'selected' : '' ?>>Pending</option>
                    <option value="In Progress" <?= $status_filter == 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                    <option value="Completed" <?= $status_filter == 'Completed' ? 'selected' : '' ?>>Completed</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary mr-2">
                <i class="fas fa-search mr-2"></i>Search
            </button>
            <?php if ($search || $status_filter): ?>
                <a href="/patient/lab_tests.php" class="btn btn-secondary">
                    <i class="fas fa-times mr-2"></i>Clear
                </a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Lab Test History</h3>
    </div>
    <div class="card-body">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Test ID</th>
                        <th>Test Type</th>
                        <th>Lab</th>
                        <th>Ordered By</th>
                        <th>Ordered Date</th>
                        <th>Cost</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lab_tests as $test): ?>
                    <tr>
                        <td><strong>#<?= $test['test_id'] ?></strong></td>
                        <td><strong><?= htmlspecialchars($test['test_type']) ?></strong></td>
                        <td>
                            <?php if ($test['lab_name']): ?>
                                <?= htmlspecialchars($test['lab_name']) ?><br>
                                <small class="text-muted"><?= htmlspecialchars($test['lab_location']) ?></small><br>
                                <small class="text-muted"><i class="fas fa-phone mr-1"></i><?= htmlspecialchars($test['lab_phone']) ?></small>
                            <?php else: ?>
                                <span class="text-muted">To be assigned</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            Dr. <?= htmlspecialchars($test['doctor_name']) ?><br>
                            <small class="text-muted"><?= htmlspecialchars($test['specialization']) ?></small>
                        </td>
                        <td><?= date('M d, Y H:i', strtotime($test['date_and_time'])) ?></td>
                        <td><strong>৳<?= number_format($test['test_cost'], 2) ?></strong></td>
                        <td>
                            <?php
                            $status_class = [
                                'Ordered' => 'badge-warning',
                                'In Progress' => 'badge-info',
                                'Completed' => 'badge-success',
                                'Cancelled' => 'badge-danger'
                            ];
                            $badge_class = $status_class[$test['status']] ?? 'badge-primary';
                            ?>
                            <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($test['status']) ?></span>
                        </td>
                        <td>
                            <a href="/patient/lab_test_detail.php?id=<?= $test['test_id'] ?>" 
                               class="btn btn-sm btn-primary" title="View Details">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <?php if ($test['status'] == 'Completed'): ?>
                                <a href="/patient/lab_test_pdf.php?id=<?= $test['test_id'] ?>" 
                                   class="btn btn-sm btn-success mt-1" title="Download PDF" target="_blank">
                                    <i class="fas fa-file-pdf"></i> PDF
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($lab_tests)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <i class="fas fa-vial text-gray-400 text-4xl mb-2"></i>
                            <p class="text-gray-500">No lab tests found.</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>

