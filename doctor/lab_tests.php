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

$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

$sql = "SELECT lt.*, p.full_name as patient_name, p.national_id, l.lab_name, l.location as lab_location,
        d.full_name as doctor_name
        FROM core_labtest lt
        INNER JOIN core_patient p ON lt.patient_id = p.patient_id
        INNER JOIN core_lab l ON lt.lab_id = l.lab_id
        INNER JOIN core_doctor d ON lt.ordered_by_id = d.doctor_id
        WHERE lt.ordered_by_id = ?";
        
$params = [$doctor_id];

if ($status_filter) {
    $sql .= " AND lt.status = ?";
    $params[] = $status_filter;
}

if ($search) {
    $sql .= " AND (p.full_name LIKE ? OR lt.test_type LIKE ? OR p.national_id LIKE ?)";
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
                SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled
              FROM core_labtest
              WHERE ordered_by_id = ?";
$stats_stmt = $pdo->prepare($stats_sql);
$stats_stmt->execute([$doctor_id]);
$stats = $stats_stmt->fetch();

include __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <h2><i class="fas fa-vial mr-2"></i>Lab Tests</h2>
    <a href="/doctor/lab_test_order.php" class="btn btn-success">
        <i class="fas fa-plus mr-2"></i>Order New Test
    </a>
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
    <div class="stat-card danger">
        <h3><?= $stats['cancelled'] ?? 0 ?></h3>
        <p><i class="fas fa-times-circle mr-2"></i>Cancelled</p>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="form-inline">
            <div class="form-group mr-3">
                <input type="text" name="search" class="form-control" 
                       placeholder="Search by patient name, test type, or ID..." 
                       value="<?= htmlspecialchars($search) ?>" style="min-width: 300px;">
            </div>
            <div class="form-group mr-3">
                <select name="status" class="form-control" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="Ordered" <?= $status_filter == 'Ordered' ? 'selected' : '' ?>>Ordered</option>
                    <option value="In Progress" <?= $status_filter == 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                    <option value="Completed" <?= $status_filter == 'Completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="Cancelled" <?= $status_filter == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary mr-2">
                <i class="fas fa-search mr-2"></i>Search
            </button>
            <?php if ($search || $status_filter): ?>
                <a href="/doctor/lab_tests.php" class="btn btn-secondary">
                    <i class="fas fa-times mr-2"></i>Clear
                </a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Ordered Lab Tests</h3>
    </div>
    <div class="card-body">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Test ID</th>
                        <th>Patient</th>
                        <th>Test Type</th>
                        <th>Lab</th>
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
                        <td>
                            <strong><?= htmlspecialchars($test['patient_name']) ?></strong><br>
                            <small class="text-muted">ID: <?= htmlspecialchars($test['national_id']) ?></small>
                        </td>
                        <td><?= htmlspecialchars($test['test_type']) ?></td>
                        <td>
                            <?= htmlspecialchars($test['lab_name']) ?><br>
                            <small class="text-muted"><?= htmlspecialchars($test['lab_location']) ?></small>
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
                            <div class="btn-group">
                                <a href="/doctor/lab_test_detail.php?id=<?= $test['test_id'] ?>" 
                                   class="btn btn-sm btn-primary" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if ($test['status'] == 'Ordered' || $test['status'] == 'In Progress'): ?>
                                    <a href="/doctor/lab_test_detail.php?id=<?= $test['test_id'] ?>&edit=1" 
                                       class="btn btn-sm btn-success" title="Update Result">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($lab_tests)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <i class="fas fa-vial text-gray-400 text-4xl mb-2"></i>
                            <p class="text-gray-500">No lab tests found.</p>
                            <a href="/doctor/lab_test_order.php" class="btn btn-primary mt-2">
                                <i class="fas fa-plus mr-2"></i>Order First Test
                            </a>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>

