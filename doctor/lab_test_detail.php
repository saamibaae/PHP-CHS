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
$test_id = $_GET['id'] ?? null;
$edit_mode = isset($_GET['edit']);

if (!$test_id) {
    setFlash("Invalid test ID.", "error");
    header("Location: /doctor/lab_tests.php");
    exit;
}

$sql = "SELECT lt.*, p.full_name as patient_name, p.national_id, p.date_of_birth, p.gender, p.blood_type,
        l.lab_name, l.location as lab_location, l.phone as lab_phone,
        d.full_name as doctor_name, d.specialization
        FROM core_labtest lt
        INNER JOIN core_patient p ON lt.patient_id = p.patient_id
        INNER JOIN core_lab l ON lt.lab_id = l.lab_id
        INNER JOIN core_doctor d ON lt.ordered_by_id = d.doctor_id
        WHERE lt.test_id = ? AND lt.ordered_by_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$test_id, $doctor_id]);
$test = $stmt->fetch();

if (!$test) {
    setFlash("Lab test not found or access denied.", "error");
    header("Location: /doctor/lab_tests.php");
    exit;
}

$error = null;
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'] ?? '';
    $result = trim($_POST['result'] ?? '');
    $remarks = trim($_POST['remarks'] ?? $test['remarks']);
    
    if (empty($status)) {
        $error = "Status is required.";
    } elseif ($status == 'Completed' && empty($result)) {
        $error = "Test result is required when marking as Completed.";
    } else {
        try {
            $update_sql = "UPDATE core_labtest SET status = ?, result = ?, remarks = ? WHERE test_id = ?";
            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->execute([$status, $result, $remarks, $test_id]);
            
            setFlash("Lab test updated successfully.");
            header("Location: /doctor/lab_test_detail.php?id=" . $test_id);
            exit;
        } catch (Exception $e) {
            $error = "Error updating lab test: " . $e->getMessage();
        }
    }
}

include __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <h2><i class="fas fa-vial mr-2"></i>Lab Test Details</h2>
    <div>
        <?php if ($edit_mode): ?>
            <a href="/doctor/lab_test_detail.php?id=<?= $test_id ?>" class="btn btn-secondary mr-2">
                <i class="fas fa-times mr-2"></i>Cancel Edit
            </a>
        <?php else: ?>
            <?php if ($test['status'] == 'Ordered' || $test['status'] == 'In Progress'): ?>
                <a href="/doctor/lab_test_detail.php?id=<?= $test_id ?>&edit=1" class="btn btn-success mr-2">
                    <i class="fas fa-edit mr-2"></i>Update Result
                </a>
            <?php endif; ?>
        <?php endif; ?>
        <a href="/doctor/lab_tests.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Back to Tests
        </a>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-error mb-4">
        <i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h3><i class="fas fa-user mr-2"></i>Patient Information</h3>
            </div>
            <div class="card-body">
                <p><strong>Name:</strong> <?= htmlspecialchars($test['patient_name']) ?></p>
                <p><strong>National ID:</strong> <?= htmlspecialchars($test['national_id']) ?></p>
                <p><strong>Date of Birth:</strong> <?= htmlspecialchars($test['date_of_birth']) ?></p>
                <p><strong>Gender:</strong> <?= htmlspecialchars($test['gender']) ?></p>
                <p><strong>Blood Type:</strong> <span class="text-danger font-bold"><?= htmlspecialchars($test['blood_type']) ?></span></p>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-flask mr-2"></i>Lab Information</h3>
            </div>
            <div class="card-body">
                <p><strong>Lab Name:</strong> <?= htmlspecialchars($test['lab_name']) ?></p>
                <p><strong>Location:</strong> <?= htmlspecialchars($test['lab_location']) ?></p>
                <p><strong>Phone:</strong> <?= htmlspecialchars($test['lab_phone']) ?></p>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3><i class="fas fa-file-medical mr-2"></i>Test Information</h3>
                <span class="badge <?php
                    $status_class = [
                        'Ordered' => 'badge-warning',
                        'In Progress' => 'badge-info',
                        'Completed' => 'badge-success',
                        'Cancelled' => 'badge-danger'
                    ];
                    echo $status_class[$test['status']] ?? 'badge-primary';
                ?>"><?= htmlspecialchars($test['status']) ?></span>
            </div>
            <div class="card-body">
                <?php if ($edit_mode && ($test['status'] == 'Ordered' || $test['status'] == 'In Progress')): ?>
                    <form method="post">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Test Type</label>
                                <input type="text" class="form-control" 
                                       value="<?= htmlspecialchars($test['test_type']) ?>" readonly>
                            </div>
                            <div class="form-group col-md-6">
                                <label>Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-control" required>
                                    <option value="Ordered" <?= $test['status'] == 'Ordered' ? 'selected' : '' ?>>Ordered</option>
                                    <option value="In Progress" <?= $test['status'] == 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                    <option value="Completed" <?= $test['status'] == 'Completed' ? 'selected' : '' ?>>Completed</option>
                                    <option value="Cancelled" <?= $test['status'] == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Test Result <span class="text-danger">*</span> (Required if Completed)</label>
                            <textarea name="result" class="form-control" rows="8" 
                                      placeholder="Enter test results, values, observations, etc..."><?= htmlspecialchars($test['result'] ?? '') ?></textarea>
                            <small class="form-text text-muted">Enter detailed test results, measurements, and observations.</small>
                        </div>
                        
                        <div class="form-group">
                            <label>Remarks / Notes</label>
                            <textarea name="remarks" class="form-control" rows="3"><?= htmlspecialchars($test['remarks'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Test Cost</label>
                            <input type="text" class="form-control" 
                                   value="৳<?= number_format($test['test_cost'], 2) ?>" readonly>
                        </div>
                        
                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-save mr-2"></i>Update Test Result
                            </button>
                            <a href="/doctor/lab_test_detail.php?id=<?= $test_id ?>" class="btn btn-secondary btn-lg ml-2">
                                <i class="fas fa-times mr-2"></i>Cancel
                            </a>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="form-row mb-3">
                        <div class="col-md-6">
                            <strong>Test Type:</strong><br>
                            <span class="text-lg"><?= htmlspecialchars($test['test_type']) ?></span>
                        </div>
                        <div class="col-md-6">
                            <strong>Test Cost:</strong><br>
                            <span class="text-lg font-bold text-primary">৳<?= number_format($test['test_cost'], 2) ?></span>
                        </div>
                    </div>
                    
                    <div class="form-row mb-3">
                        <div class="col-md-6">
                            <strong>Ordered Date:</strong><br>
                            <?= date('M d, Y H:i', strtotime($test['date_and_time'])) ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Ordered By:</strong><br>
                            Dr. <?= htmlspecialchars($test['doctor_name']) ?> (<?= htmlspecialchars($test['specialization']) ?>)
                        </div>
                    </div>
                    
                    <?php if ($test['remarks']): ?>
                        <div class="mb-3">
                            <strong>Remarks:</strong><br>
                            <div class="p-3 bg-gray-50 rounded"><?= nl2br(htmlspecialchars($test['remarks'])) ?></div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($test['result']): ?>
                        <div class="mb-3">
                            <strong>Test Result:</strong><br>
                            <div class="p-4 bg-green-50 border border-green-200 rounded">
                                <pre class="whitespace-pre-wrap font-sans"><?= htmlspecialchars($test['result']) ?></pre>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-clock mr-2"></i>
                            Test result is pending. Update the result when available.
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>

