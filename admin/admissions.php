<?php
require_once __DIR__ . '/../db.php';
requireRole('ADMIN');

$hospital_id = $_SESSION['hospital_id'];

$stmt = $pdo->prepare("SELECT capacity, name FROM core_hospital WHERE hospital_id = ?");
$stmt->execute([$hospital_id]);
$hospital = $stmt->fetch();

if (!$hospital) {
    die("Hospital not found.");
}

if (!createAdmissionTableIfNeeded()) {
    $current_admissions = 0;
} else {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM core_admission WHERE hospital_id = ? AND status = 'Admitted'");
        $stmt->execute([$hospital_id]);
        $current_admissions = $stmt->fetchColumn();
    } catch (PDOException $e) {
        $current_admissions = 0;
    }
}
$available_beds = $hospital['capacity'] - $current_admissions;

$status_filter = $_GET['status'] ?? 'Admitted';
$search = $_GET['search'] ?? '';

$admissions = [];
if (checkTableExists('core_admission')) {
    $sql = "SELECT a.*, p.full_name, p.national_id, p.blood_type, p.phone,
            u1.username as admitted_by_username, u2.username as discharged_by_username
            FROM core_admission a
            INNER JOIN core_patient p ON a.patient_id = p.patient_id
            LEFT JOIN core_customuser u1 ON a.admitted_by_user_id = u1.id
            LEFT JOIN core_customuser u2 ON a.discharged_by_user_id = u2.id
            WHERE a.hospital_id = ?";
            
    $params = [$hospital_id];

    if ($status_filter) {
        $sql .= " AND a.status = ?";
        $params[] = $status_filter;
    }

    if ($search) {
        $sql .= " AND (p.full_name LIKE ? OR p.national_id LIKE ? OR a.bed_number LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }

    $sql .= " ORDER BY a.admission_date DESC";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $admissions = $stmt->fetchAll();
    } catch (PDOException $e) {
        $admissions = [];
    }
}

include __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <h2><i class="fas fa-procedures mr-2"></i>Patient Admissions</h2>
    <a href="/admin/admit_patient.php" class="btn btn-success">
        <i class="fas fa-plus mr-2"></i>Admit Patient
    </a>
</div>

<div class="stats-grid mb-4">
    <div class="stat-card">
        <h3><?= $current_admissions ?> / <?= $hospital['capacity'] ?></h3>
        <p><i class="fas fa-bed mr-2"></i>Current Occupancy</p>
    </div>
    <div class="stat-card success">
        <h3><?= $available_beds ?></h3>
        <p><i class="fas fa-bed-empty mr-2"></i>Available Beds</p>
    </div>
    <div class="stat-card warning">
        <h3><?= $hospital['capacity'] ?></h3>
        <p><i class="fas fa-hospital mr-2"></i>Total Capacity</p>
    </div>
    <div class="stat-card <?= ($current_admissions / $hospital['capacity'] * 100) >= 90 ? 'danger' : 'info' ?>">
        <h3><?= round($current_admissions / $hospital['capacity'] * 100, 1) ?>%</h3>
        <p><i class="fas fa-chart-line mr-2"></i>Capacity Used</p>
    </div>
</div>

<?php if (($current_admissions / $hospital['capacity'] * 100) >= 90): ?>
    <div class="alert alert-error mb-4">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        <strong>Warning:</strong> Hospital capacity is at <?= round($current_admissions / $hospital['capacity'] * 100, 1) ?>%. 
        Only <?= $available_beds ?> bed(s) remaining.
    </div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="form-inline">
            <div class="form-group mr-3">
                <input type="text" name="search" class="form-control" 
                       placeholder="Search by patient name, ID, or bed number..." 
                       value="<?= htmlspecialchars($search) ?>" style="min-width: 300px;">
            </div>
            <div class="form-group mr-3">
                <select name="status" class="form-control" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="Admitted" <?= $status_filter == 'Admitted' ? 'selected' : '' ?>>Admitted</option>
                    <option value="Discharged" <?= $status_filter == 'Discharged' ? 'selected' : '' ?>>Discharged</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary mr-2">
                <i class="fas fa-search mr-2"></i>Search
            </button>
            <?php if ($search || $status_filter): ?>
                <a href="/admin/admissions.php" class="btn btn-secondary">
                    <i class="fas fa-times mr-2"></i>Clear
                </a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Admission Records</h3>
    </div>
    <div class="card-body">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Admission ID</th>
                        <th>Patient</th>
                        <th>Bed Number</th>
                        <th>Admission Date</th>
                        <th>Discharge Date</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($admissions as $adm): ?>
                    <tr>
                        <td><strong>#<?= $adm['admission_id'] ?></strong></td>
                        <td>
                            <strong><?= htmlspecialchars($adm['full_name']) ?></strong><br>
                            <small class="text-muted">ID: <?= htmlspecialchars($adm['national_id']) ?></small><br>
                            <small class="text-muted">Blood: <span class="text-danger"><?= htmlspecialchars($adm['blood_type']) ?></span></small>
                        </td>
                        <td>
                            <span class="badge badge-primary font-bold"><?= htmlspecialchars($adm['bed_number']) ?></span>
                        </td>
                        <td><?= date('M d, Y H:i', strtotime($adm['admission_date'])) ?></td>
                        <td>
                            <?php if ($adm['discharge_date']): ?>
                                <?= date('M d, Y H:i', strtotime($adm['discharge_date'])) ?>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($adm['reason']): ?>
                                <span class="text-sm"><?= htmlspecialchars(substr($adm['reason'], 0, 50)) ?><?= strlen($adm['reason']) > 50 ? '...' : '' ?></span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($adm['status'] == 'Admitted'): ?>
                                <span class="badge badge-success">Admitted</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Discharged</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group">
                                <?php if ($adm['status'] == 'Admitted'): ?>
                                    <a href="/admin/discharge_patient.php?id=<?= $adm['admission_id'] ?>" 
                                       class="btn btn-sm btn-danger" 
                                       title="Discharge Patient"
                                       onclick="return confirm('Are you sure you want to discharge this patient?');">
                                        <i class="fas fa-sign-out-alt"></i> Discharge
                                    </a>
                                <?php endif; ?>
                                <a href="/admin/admission_detail.php?id=<?= $adm['admission_id'] ?>" 
                                   class="btn btn-sm btn-primary" title="View Details">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($admissions)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <i class="fas fa-procedures text-gray-400 text-4xl mb-2"></i>
                            <p class="text-gray-500">No admissions found.</p>
                            <a href="/admin/admit_patient.php" class="btn btn-primary mt-2">
                                <i class="fas fa-plus mr-2"></i>Admit First Patient
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

