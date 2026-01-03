<?php
require_once __DIR__ . '/../db.php';
requireRole('DOCTOR');

$presc_id = $_GET['prescription_id'] ?? null;
if (!$presc_id) die("Invalid request");

$stmt = $pdo->prepare("SELECT p.*, a.appointment_id FROM core_prescription p JOIN core_appointment a ON p.appointment_id = a.appointment_id WHERE p.prescription_id = ?");
$stmt->execute([$presc_id]);
$presc = $stmt->fetch();

if (!$presc) {
    die("Prescription not found.");
}

// Handle Add Item
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $medicine_name = trim($_POST['medicine_name']);
    $dosage = $_POST['dosage'];
    $frequency = $_POST['frequency'];
    $duration = $_POST['duration'];
    $quantity = $_POST['quantity'];
    $before_after = $_POST['before_after_meal'];
    $instructions = $_POST['instructions'];
    
    if (empty($medicine_name)) {
        setFlash("Medicine name is required.", "error");
        header("Location: /doctor/add_prescription_items.php?prescription_id=" . $presc_id);
        exit;
    }
    
    // Get or create a default manufacturer
    $stmt = $pdo->query("SELECT manufacturer_id FROM core_manufacturer LIMIT 1");
    $manufacturer_id = $stmt->fetchColumn();
    
    if (!$manufacturer_id) {
        // Create a default manufacturer if none exists
        $pdo->exec("INSERT INTO core_manufacturer (name, phone, address, license_no) 
                    VALUES ('Generic Manufacturer', '01700000000', 'Dhaka, Bangladesh', 'MANUF-001')");
        $manufacturer_id = $pdo->lastInsertId();
    }
    
    // Check if medicine exists, if not create it
    $stmt = $pdo->prepare("SELECT medicine_id FROM core_medicine WHERE LOWER(name) = LOWER(?)");
    $stmt->execute([$medicine_name]);
    $medicine_id = $stmt->fetchColumn();
    
    if (!$medicine_id) {
        // Create medicine automatically
        $stmt = $pdo->prepare("INSERT INTO core_medicine (name, type, dosage_info, manufacturer_id) 
                              VALUES (?, 'General', 'As prescribed', ?)");
        $stmt->execute([$medicine_name, $manufacturer_id]);
        $medicine_id = $pdo->lastInsertId();
    }
    
    // Add prescription item
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
                        <label>Medicine Name</label>
                        <input type="text" name="medicine_name" class="form-control" placeholder="e.g. Paracetamol, Amoxicillin, etc." required autofocus>
                        <small class="form-text text-muted">Type the medicine name - it will be added automatically if it doesn't exist.</small>
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
                        <label>When to Take</label>
                        <select name="before_after_meal" class="form-control">
                            <option value="After">After Meal</option>
                            <option value="Before">Before Meal</option>
                            <option value="With">With Meal</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Additional Instructions</label>
                        <input type="text" name="instructions" class="form-control" placeholder="e.g. Take with plenty of water">
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
