<?php
require_once __DIR__ . '/../db.php';
requireRole('DOCTOR');

$appointment_id = $_GET['appointment_id'] ?? null;
if (!$appointment_id) die("Invalid request");

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT doctor_id FROM core_doctor WHERE user_id = ?");
$stmt->execute([$user_id]);
$doctor_id = $stmt->fetchColumn();

if (!$doctor_id) {
    die("Doctor profile not found.");
}

$stmt = $pdo->prepare("SELECT appointment_id FROM core_appointment WHERE appointment_id = ? AND doctor_id = ?");
$stmt->execute([$appointment_id, $doctor_id]);
if (!$stmt->fetch()) {
    die("Appointment not found or access denied.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $valid_until = $_POST['valid_until'] ?? '';
    $refill_count = intval($_POST['refill_count'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');
    
    if (empty($valid_until)) {
        die("Valid until date is required.");
    }
    if ($refill_count < 0 || $refill_count > 10) {
        die("Refill count must be between 0 and 10.");
    }
    
    $sql = "INSERT INTO core_prescription (appointment_id, valid_until, refill_count, notes) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$appointment_id, $valid_until, $refill_count, $notes]);
    $presc_id = $pdo->lastInsertId();
    
    setFlash("Prescription created. Now add medicines.");
    header("Location: /doctor/add_prescription_items.php?prescription_id=" . $presc_id);
    exit;
}

include __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <h2>Create Prescription</h2>
    <a href="/doctor/appointment_detail.php?id=<?= $appointment_id ?>" class="btn btn-secondary">Cancel</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="post" action="">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Valid Until</label>
                    <input type="date" name="valid_until" class="form-control" required value="<?= date('Y-m-d', strtotime('+30 days')) ?>">
                </div>
                <div class="form-group col-md-6">
                    <label>Refills</label>
                    <input type="number" name="refill_count" class="form-control" value="0" min="0" max="10">
                </div>
            </div>
            
            <div class="form-group">
                <label>Notes</label>
                <textarea name="notes" class="form-control" rows="2"></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary">Create & Add Items</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
