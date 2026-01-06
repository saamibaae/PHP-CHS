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

$sql = "SELECT a.*, p.full_name, p.national_id, p.patient_id,
        h.name as hospital_name
        FROM core_admission a
        INNER JOIN core_patient p ON a.patient_id = p.patient_id
        INNER JOIN core_hospital h ON a.hospital_id = h.hospital_id
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

if ($admission['status'] == 'Discharged') {
    setFlash("Patient is already discharged.", "error");
    header("Location: /admin/admissions.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $sql = "UPDATE core_admission 
                SET status = 'Discharged', 
                    discharge_date = NOW(),
                    discharged_by_user_id = ?
                WHERE admission_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$_SESSION['user_id'], $admission_id]);
        
        setFlash("Patient discharged successfully. Bed {$admission['bed_number']} is now available.");
        header("Location: /admin/admissions.php");
        exit;
    } catch (Exception $e) {
        $error = "Error discharging patient: " . $e->getMessage();
    }
}

include __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <h2><i class="fas fa-sign-out-alt mr-2"></i>Discharge Patient</h2>
    <a href="/admin/admissions.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left mr-2"></i>Back to Admissions
    </a>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-error mb-4">
        <i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3>Discharge Confirmation</h3>
    </div>
    <div class="card-body">
        <div class="alert alert-warning mb-4">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            <strong>Confirm Discharge:</strong> Are you sure you want to discharge this patient?
        </div>
        
        <div class="row mb-4">
            <div class="col-md-6">
                <h4>Patient Information</h4>
                <p><strong>Name:</strong> <?= htmlspecialchars($admission['full_name']) ?></p>
                <p><strong>National ID:</strong> <?= htmlspecialchars($admission['national_id']) ?></p>
            </div>
            <div class="col-md-6">
                <h4>Admission Details</h4>
                <p><strong>Hospital:</strong> <?= htmlspecialchars($admission['hospital_name']) ?></p>
                <p><strong>Bed Number:</strong> <span class="badge badge-primary"><?= htmlspecialchars($admission['bed_number']) ?></span></p>
                <p><strong>Admission Date:</strong> <?= date('M d, Y H:i', strtotime($admission['admission_date'])) ?></p>
                <?php if ($admission['reason']): ?>
                    <p><strong>Reason:</strong> <?= htmlspecialchars($admission['reason']) ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <form method="post" action="">
            <div class="form-group mt-4">
                <button type="submit" class="btn btn-danger btn-lg">
                    <i class="fas fa-check mr-2"></i>Confirm Discharge
                </button>
                <a href="/admin/admissions.php" class="btn btn-secondary btn-lg ml-2">
                    <i class="fas fa-times mr-2"></i>Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>

