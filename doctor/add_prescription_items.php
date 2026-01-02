<?php
require_once __DIR__ . '/../db.php';
requireRole('DOCTOR');

$presc_id = $_GET['prescription_id'] ?? null;
if (!$presc_id) die("Invalid request");

$stmt = $pdo->prepare("SELECT p.*, a.appointment_id FROM core_prescription p JOIN core_appointment a ON p.appointment_id = a.appointment_id WHERE p.prescription_id = ?");
$stmt->execute([$presc_id]);
$presc = $stmt->fetch();

// Fetch Medicines for Dropdown
$medicines = $pdo->query("SELECT * FROM core_medicine ORDER BY name")->fetchAll();

// Handle Add Item
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $medicine_id = $_POST['medicine_id'];
    $dosage = $_POST['dosage'];
    $frequency = $_POST['frequency'];
    $duration = $_POST['duration'];
    $quantity = $_POST['quantity'];
    $before_after = $_POST['before_after_meal'];
    $instructions = $_POST['instructions'];
    
    $sql = "INSERT INTO core_prescriptionitem 
            (prescription_id, medicine_id, dosage, frequency, duration, quantity, before_after_meal, instructions)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$presc_id, $medicine_id, $dosage, $frequency, $duration, $quantity, $before_after, $instructions]);
    
    setFlash("Medicine added.");
    // Refresh page to show added item
    header("Location: /doctor/add_prescription_items.php?prescription_id=" . $presc_id);
    exit;
}

// Get Existing Items
$sql = "SELECT pi.*, m.name FROM core_prescriptionitem pi JOIN core_medicine m ON pi.medicine_id = m.medicine_id WHERE pi.prescription_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$presc_id]);
$existing_items = $stmt->fetchAll();

include __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <h2>Add Medicines to Prescription #<?= $presc_id ?></h2>
    <a href="/doctor/appointment_detail.php?id=<?= $presc['appointment_id'] ?>" class="btn btn-success">Done / Finish</a>
</div>

<div class="row">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header">Add Item</div>
            <div class="card-body">
                <form method="post">
                    <div class="form-group">
                        <label>Medicine</label>
                        <select name="medicine_id" class="form-control select2" required>
                            <?php foreach ($medicines as $m): ?>
                                <option value="<?= $m['medicine_id'] ?>"><?= htmlspecialchars($m['name']) ?> (<?= htmlspecialchars($m['type']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Dosage</label>
                            <input type="text" name="dosage" class="form-control" placeholder="e.g. 500mg" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Frequency</label>
                            <input type="text" name="frequency" class="form-control" placeholder="e.g. 1-0-1" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Duration</label>
                            <input type="text" name="duration" class="form-control" placeholder="e.g. 7 days" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Quantity</label>
                            <input type="number" name="quantity" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Instructions</label>
                        <select name="before_after_meal" class="form-control">
                            <option value="After">After Meal</option>
                            <option value="Before">Before Meal</option>
                            <option value="With">With Meal</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Additional Notes</label>
                        <input type="text" name="instructions" class="form-control">
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">Add Medicine</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-7">
        <div class="card">
            <div class="card-header">Items in this Prescription</div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Medicine</th>
                            <th>Dosage</th>
                            <th>Freq</th>
                            <th>Qty</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($existing_items as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['name']) ?></td>
                            <td><?= htmlspecialchars($item['dosage']) ?></td>
                            <td><?= htmlspecialchars($item['frequency']) ?></td>
                            <td><?= htmlspecialchars($item['quantity']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($existing_items)): ?>
                        <tr><td colspan="4" class="text-center">No items added yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
