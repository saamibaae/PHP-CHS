<?php
require_once __DIR__ . '/../db.php';
requireRole('DOCTOR');

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM core_doctor WHERE user_id = ?");
$stmt->execute([$user_id]);
$doctor = $stmt->fetch();
$doctor_id = $doctor['doctor_id'];
$hospital_id = $doctor['hospital_id'];

// Get Labs
$stmt = $pdo->prepare("SELECT * FROM core_lab WHERE hospital_id = ?");
$stmt->execute([$hospital_id]);
$labs = $stmt->fetchAll();

// Get Patients (All patients, or search? For simplicity all)
$patients = $pdo->query("SELECT * FROM core_patient ORDER BY full_name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lab_id = $_POST['lab_id'];
    $patient_id = $_POST['patient_id'];
    $test_type = $_POST['test_type'];
    $test_cost = $_POST['test_cost'];
    $remarks = $_POST['remarks'];
    
    $sql = "INSERT INTO core_labtest (lab_id, patient_id, test_type, ordered_by_id, remarks, test_cost, status, date_and_time)
            VALUES (?, ?, ?, ?, ?, ?, 'Ordered', NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$lab_id, $patient_id, $test_type, $doctor_id, $remarks, $test_cost]);
    
    setFlash("Lab test ordered successfully.");
    header("Location: /doctor/dashboard.php");
    exit;
}

include __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <h2>Order Lab Test</h2>
    <a href="/doctor/dashboard.php" class="btn btn-secondary">Cancel</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="post">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Patient</label>
                    <select name="patient_id" class="form-control select2" required>
                        <option value="">-- Select Patient --</option>
                        <?php foreach ($patients as $p): ?>
                            <option value="<?= $p['patient_id'] ?>"><?= htmlspecialchars($p['full_name']) ?> (ID: <?= htmlspecialchars($p['national_id']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <label>Lab</label>
                    <select name="lab_id" class="form-control" required>
                        <option value="">-- Select Lab --</option>
                        <?php foreach ($labs as $lab): ?>
                            <option value="<?= $lab['lab_id'] ?>"><?= htmlspecialchars($lab['lab_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Test Type</label>
                    <input type="text" name="test_type" class="form-control" placeholder="e.g. Blood Count" required>
                </div>
                <div class="form-group col-md-6">
                    <label>Cost</label>
                    <input type="number" step="0.01" name="test_cost" class="form-control" required>
                </div>
            </div>
            
            <div class="form-group">
                <label>Remarks</label>
                <textarea name="remarks" class="form-control" rows="2"></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary">Order Test</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
