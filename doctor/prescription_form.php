<?php
require_once __DIR__ . '/../db.php';
requireRole('DOCTOR');

$appointment_id = $_GET['appointment_id'] ?? null;
if (!$appointment_id) die("Invalid request");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $valid_until = $_POST['valid_until'];
    $refill_count = $_POST['refill_count'];
    $notes = $_POST['notes'];
    
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
        <form method="post">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Valid Until</label>
                    <input type="date" name="valid_until" class="form-control" required value="<?= date('Y-m-d', strtotime('+30 days')) ?>">
                </div>
                <div class="form-group col-md-6">
                    <label>Refills</label>
                    <input type="number" name="refill_count" class="form-control" value="0">
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
