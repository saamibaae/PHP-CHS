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

$error = null;
$patient = null;
$search_username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_patient'])) {
    $search_username = trim($_POST['username'] ?? '');
    
    if (empty($search_username)) {
        $error = "Please enter a username.";
    } else {
        $sql = "SELECT p.*, u.username 
                FROM core_patient p
                INNER JOIN core_customuser u ON p.user_id = u.id
                WHERE u.username = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$search_username]);
        $patient = $stmt->fetch();
        
        if (!$patient) {
            $error = "Patient with username '$search_username' not found.";
        } else {
            if (checkTableExists('core_admission')) {
                try {
                    $stmt = $pdo->prepare("SELECT * FROM core_admission WHERE patient_id = ? AND hospital_id = ? AND status = 'Admitted'");
                    $stmt->execute([$patient['patient_id'], $hospital_id]);
                    if ($stmt->fetch()) {
                        $error = "This patient is already admitted to this hospital.";
                        $patient = null;
                    }
                } catch (PDOException $e) {
                }
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admit_patient'])) {
    $patient_id = intval($_POST['patient_id'] ?? 0);
    $bed_number = trim($_POST['bed_number'] ?? '');
    $reason = trim($_POST['reason'] ?? '');
    
    if (!$patient_id) {
        $error = "Invalid patient ID.";
    } elseif (empty($bed_number)) {
        $error = "Bed number is required.";
    } elseif ($available_beds <= 0) {
        $error = "No available beds. Hospital is at full capacity.";
        } else {
            if (!createAdmissionTableIfNeeded()) {
                $error = "Admission system is not set up. Please run setup_db.php or migrate_admission_table.php";
            } else {
                try {
                    $pdo->beginTransaction();
                    
                    $stmt = $pdo->prepare("SELECT * FROM core_admission WHERE hospital_id = ? AND bed_number = ? AND status = 'Admitted' FOR UPDATE");
                    $stmt->execute([$hospital_id, $bed_number]);
                    if ($stmt->fetch()) {
                        $pdo->rollBack();
                        $error = "Bed number $bed_number is already occupied.";
                    } else {
                        $sql = "INSERT INTO core_admission (patient_id, hospital_id, bed_number, reason, status, admitted_by_user_id)
                                VALUES (?, ?, ?, ?, 'Admitted', ?)";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$patient_id, $hospital_id, $bed_number, $reason, $_SESSION['user_id']]);
                        
                        $pdo->commit();
                        setFlash("Patient admitted successfully to bed $bed_number.");
                        header("Location: /admin/admissions.php");
                        exit;
                    }
                } catch (Exception $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $error = "Error admitting patient: " . $e->getMessage();
                }
            }
        }
    
    if ($patient_id) {
        $sql = "SELECT p.*, u.username 
                FROM core_patient p
                INNER JOIN core_customuser u ON p.user_id = u.id
                WHERE p.patient_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$patient_id]);
        $patient = $stmt->fetch();
    }
}

include __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <h2><i class="fas fa-procedures mr-2"></i>Admit Patient</h2>
    <a href="/admin/admissions.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left mr-2"></i>Back to Admissions
    </a>
</div>

<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3 class="mb-1"><?= htmlspecialchars($hospital['name']) ?></h3>
                <p class="text-muted mb-0">Hospital Capacity: <?= $hospital['capacity'] ?> beds</p>
            </div>
            <div class="text-right">
                <div class="text-3xl font-bold <?= $available_beds <= 10 ? 'text-danger' : ($available_beds <= 50 ? 'text-warning' : 'text-success') ?>">
                    <?= $current_admissions ?> / <?= $hospital['capacity'] ?>
                </div>
                <p class="text-muted mb-0">
                    <?= $available_beds ?> beds available
                </p>
            </div>
        </div>
        <?php if ($available_beds <= 0): ?>
            <div class="alert alert-error mt-3">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <strong>Warning:</strong> Hospital is at full capacity. Cannot admit more patients.
            </div>
        <?php elseif ($available_beds <= 10): ?>
            <div class="alert alert-warning mt-3">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <strong>Low Capacity:</strong> Only <?= $available_beds ?> bed(s) remaining.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-error mb-4">
        <i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-search mr-2"></i>Search Patient</h3>
            </div>
            <div class="card-body">
                <form method="post" action="">
                    <input type="hidden" name="search_patient" value="1">
                    <div class="form-group">
                        <label>Patient Username <span class="text-danger">*</span></label>
                        <div class="d-flex">
                            <input type="text" name="username" class="form-control" 
                                   placeholder="Enter patient username..." 
                                   value="<?= htmlspecialchars($search_username) ?>" required autofocus>
                            <button type="submit" class="btn btn-primary ml-2">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                        <small class="form-text text-muted">Enter the username of the patient to admit</small>
                    </div>
                </form>
                
                <?php if ($patient): ?>
                    <div class="mt-4 p-3 bg-green-50 border border-green-200 rounded">
                        <h4 class="text-success mb-3">
                            <i class="fas fa-check-circle mr-2"></i>Patient Found
                        </h4>
                        <p><strong>Name:</strong> <?= htmlspecialchars($patient['full_name']) ?></p>
                        <p><strong>National ID:</strong> <?= htmlspecialchars($patient['national_id']) ?></p>
                        <p><strong>Date of Birth:</strong> <?= htmlspecialchars($patient['date_of_birth']) ?></p>
                        <p><strong>Gender:</strong> <?= htmlspecialchars($patient['gender']) ?></p>
                        <p><strong>Blood Type:</strong> <span class="text-danger font-bold"><?= htmlspecialchars($patient['blood_type']) ?></span></p>
                        <p><strong>Phone:</strong> <?= htmlspecialchars($patient['phone']) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-7">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-bed mr-2"></i>Admission Details</h3>
            </div>
            <div class="card-body">
                <?php if ($patient && $available_beds > 0): ?>
                    <form method="post" action="">
                        <input type="hidden" name="admit_patient" value="1">
                        <input type="hidden" name="patient_id" value="<?= $patient['patient_id'] ?>">
                        
                        <div class="form-group">
                            <label>Patient</label>
                            <input type="text" class="form-control" 
                                   value="<?= htmlspecialchars($patient['full_name']) ?> (<?= htmlspecialchars($patient['username']) ?>)" 
                                   readonly style="background-color: #f3f4f6;">
                        </div>
                        
                        <div class="form-group">
                            <label>Bed Number <span class="text-danger">*</span></label>
                            <input type="text" name="bed_number" class="form-control" 
                                   placeholder="e.g., A-101, B-205, ICU-01" 
                                   required value="<?= htmlspecialchars($_POST['bed_number'] ?? '') ?>">
                            <small class="form-text text-muted">Enter the bed number for this patient</small>
                        </div>
                        
                        <div class="form-group">
                            <label>Reason for Admission</label>
                            <textarea name="reason" class="form-control" rows="4" 
                                      placeholder="Enter the reason for admission..."><?= htmlspecialchars($_POST['reason'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-check mr-2"></i>Admit Patient
                            </button>
                            <a href="/admin/admissions.php" class="btn btn-secondary btn-lg ml-2">
                                <i class="fas fa-times mr-2"></i>Cancel
                            </a>
                        </div>
                    </form>
                <?php elseif (!$patient): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-user-injured text-4xl mb-3"></i>
                        <p>Please search for a patient first.</p>
                    </div>
                <?php else: ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        Cannot admit patient. Hospital is at full capacity.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>

