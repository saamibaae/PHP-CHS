<?php
require_once __DIR__ . '/../db.php';
requireRole('ADMIN');

$hospital_id = $_SESSION['hospital_id'];

$stmt = $pdo->query("SELECT service_type_id FROM core_servicetype WHERE name LIKE '%Lab%' OR name LIKE '%Test%' LIMIT 1");
$lab_service_type = $stmt->fetchColumn();

if (!$lab_service_type) {
    $pdo->exec("INSERT INTO core_servicetype (name, description) VALUES ('Lab Test', 'Laboratory test services')");
    $lab_service_type = $pdo->lastInsertId();
}

$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

$bill_id_exists = checkColumnExists('core_labtest', 'bill_id');

if ($bill_id_exists) {
    $sql = "SELECT lt.*, p.full_name as patient_name, p.national_id, p.phone as patient_phone,
            d.full_name as doctor_name, d.specialization,
            b.bill_id, b.status as bill_status, b.total_amount as bill_amount
            FROM core_labtest lt
            INNER JOIN core_patient p ON lt.patient_id = p.patient_id
            INNER JOIN core_doctor d ON lt.ordered_by_id = d.doctor_id
            LEFT JOIN core_bill b ON lt.bill_id = b.bill_id
            WHERE d.hospital_id = ?";
} else {
    $sql = "SELECT lt.*, p.full_name as patient_name, p.national_id, p.phone as patient_phone,
            d.full_name as doctor_name, d.specialization,
            NULL as bill_id, NULL as bill_status, NULL as bill_amount
            FROM core_labtest lt
            INNER JOIN core_patient p ON lt.patient_id = p.patient_id
            INNER JOIN core_doctor d ON lt.ordered_by_id = d.doctor_id
            WHERE d.hospital_id = ?";
}
        
$params = [$hospital_id];

if ($status_filter) {
    $sql .= " AND lt.status = ?";
    $params[] = $status_filter;
}

if ($search) {
    $sql .= " AND (p.full_name LIKE ? OR p.national_id LIKE ? OR lt.test_type LIKE ?)";
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
                SUM(CASE WHEN lt.status = 'Ordered' THEN 1 ELSE 0 END) as ordered,
                SUM(CASE WHEN lt.status = 'Completed' THEN 1 ELSE 0 END) as completed
              FROM core_labtest lt
              INNER JOIN core_doctor d ON lt.ordered_by_id = d.doctor_id
              WHERE d.hospital_id = ?";
$stats_stmt = $pdo->prepare($stats_sql);
$stats_stmt->execute([$hospital_id]);
$stats = $stats_stmt->fetch();

include __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <h2><i class="fas fa-vial mr-2"></i>Lab Test Orders</h2>
</div>

<div class="stats-grid mb-4">
    <div class="stat-card">
        <h3><?= $stats['total'] ?? 0 ?></h3>
        <p><i class="fas fa-vial mr-2"></i>Total Orders</p>
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
                       placeholder="Search by patient name, ID, or test type..." 
                       value="<?= htmlspecialchars($search) ?>" style="min-width: 300px;">
            </div>
            <div class="form-group mr-3">
                <select name="status" class="form-control" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="Ordered" <?= $status_filter == 'Ordered' ? 'selected' : '' ?>>Ordered</option>
                    <option value="Completed" <?= $status_filter == 'Completed' ? 'selected' : '' ?>>Completed</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary mr-2">
                <i class="fas fa-search mr-2"></i>Search
            </button>
            <?php if ($search || $status_filter): ?>
                <a href="/admin/lab_test_orders.php" class="btn btn-secondary">
                    <i class="fas fa-times mr-2"></i>Clear
                </a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Lab Test Orders</h3>
    </div>
    <div class="card-body">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Test ID</th>
                        <th>Patient</th>
                        <th>Lab Tests</th>
                        <th>Ordered By</th>
                        <th>Order Date</th>
                        <th>Status</th>
                        <th>Bill</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lab_tests as $test): ?>
                    <tr>
                        <td><strong>#<?= $test['test_id'] ?></strong></td>
                        <td>
                            <strong><?= htmlspecialchars($test['patient_name']) ?></strong><br>
                            <small class="text-muted">ID: <?= htmlspecialchars($test['national_id']) ?></small><br>
                            <small class="text-muted"><?= htmlspecialchars($test['patient_phone']) ?></small>
                        </td>
                        <td>
                            <div class="text-sm" style="max-width: 300px;">
                                <?= nl2br(htmlspecialchars($test['test_type'])) ?>
                            </div>
                        </td>
                        <td>
                            Dr. <?= htmlspecialchars($test['doctor_name']) ?><br>
                            <small class="text-muted"><?= htmlspecialchars($test['specialization']) ?></small>
                        </td>
                        <td><?= date('M d, Y H:i', strtotime($test['date_and_time'])) ?></td>
                        <td>
                            <?php
                            $status_class = [
                                'Ordered' => 'badge-warning',
                                'Completed' => 'badge-success'
                            ];
                            $badge_class = $status_class[$test['status']] ?? 'badge-primary';
                            ?>
                            <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($test['status']) ?></span>
                        </td>
                        <td>
                            <?php if ($test['bill_id']): ?>
                                <span class="badge <?= $test['bill_status'] == 'Paid' ? 'badge-success' : 'badge-warning' ?>">
                                    <?= htmlspecialchars($test['bill_status']) ?>
                                </span><br>
                                <small class="text-muted">৳<?= number_format($test['bill_amount'], 2) ?></small>
                            <?php else: ?>
                                <span class="text-muted">No bill</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="/admin/lab_test_process.php?id=<?= $test['test_id'] ?>" 
                                   class="btn btn-sm btn-primary" title="Process">
                                    <i class="fas fa-cog"></i> Process
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($lab_tests)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <i class="fas fa-vial text-gray-400 text-4xl mb-2"></i>
                            <p class="text-gray-500">No lab test orders found.</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>

