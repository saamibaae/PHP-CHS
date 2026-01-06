<?php
require_once __DIR__ . '/../db.php';
requireRole('ADMIN');

$hospital_id = $_SESSION['hospital_id'];
$pharmacy_id = $_GET['pharmacy'] ?? null;

if (!$pharmacy_id) {
    setFlash("Please select a pharmacy first.", "error");
    header("Location: /admin/pharmacy_stock.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM core_pharmacy WHERE pharmacy_id = ? AND hospital_id = ?");
$stmt->execute([$pharmacy_id, $hospital_id]);
$pharmacy = $stmt->fetch();

if (!$pharmacy) {
    setFlash("Pharmacy not found or access denied.", "error");
    header("Location: /admin/pharmacy_stock.php");
    exit;
}

$error = null;
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $medicine_id = $_POST['medicine_id'] ?? null;
    $medicine_name = trim($_POST['medicine_name'] ?? '');
    $medicine_type = trim($_POST['medicine_type'] ?? 'General');
    $stock_quantity = intval($_POST['stock_quantity'] ?? 0);
    $unit_price = floatval($_POST['unit_price'] ?? 0);
    $expiry_date = $_POST['expiry_date'] ?? '';
    $batch_number = trim($_POST['batch_number'] ?? '');
    $last_restocked = $_POST['last_restocked'] ?? date('Y-m-d');
    
    if (empty($medicine_id) && empty($medicine_name)) {
        $error = "Please select an existing medicine or enter a new medicine name.";
    } elseif ($stock_quantity <= 0) {
        $error = "Stock quantity must be greater than 0.";
    } elseif ($unit_price <= 0) {
        $error = "Unit price must be greater than 0.";
    } elseif (empty($expiry_date)) {
        $error = "Expiry date is required.";
    } elseif (empty($batch_number)) {
        $error = "Batch number is required.";
    } else {
        try {
            $pdo->beginTransaction();
            
            if (empty($medicine_id)) {
                $stmt = $pdo->query("SELECT manufacturer_id FROM core_manufacturer LIMIT 1");
                $manufacturer_id = $stmt->fetchColumn();
                
                if (!$manufacturer_id) {
                    $stmt = $pdo->prepare("INSERT INTO core_manufacturer (name, phone, address, license_no) 
                                        VALUES (?, ?, ?, ?)");
                    $stmt->execute(['Generic Manufacturer', '01700000000', 'Dhaka, Bangladesh', 'MANUF-' . time()]);
                    $manufacturer_id = $pdo->lastInsertId();
                }
                
                $stmt = $pdo->prepare("SELECT medicine_id FROM core_medicine WHERE LOWER(name) = LOWER(?)");
                $stmt->execute([$medicine_name]);
                $existing_medicine = $stmt->fetchColumn();
                
                if ($existing_medicine) {
                    $medicine_id = $existing_medicine;
                } else {
                    $stmt = $pdo->prepare("INSERT INTO core_medicine (name, type, dosage_info, manufacturer_id) 
                                        VALUES (?, ?, ?, ?)");
                    $stmt->execute([$medicine_name, $medicine_type, 'As prescribed', $manufacturer_id]);
                    $medicine_id = $pdo->lastInsertId();
                }
            }
            
            $stmt = $pdo->prepare("SELECT pharmacy_medicine_id FROM core_pharmacymedicine 
                                 WHERE pharmacy_id = ? AND medicine_id = ? AND batch_number = ?");
            $stmt->execute([$pharmacy_id, $medicine_id, $batch_number]);
            $existing_batch = $stmt->fetchColumn();
            
            if ($existing_batch) {
                $error = "A stock entry with this batch number already exists for this medicine. Please use a different batch number or update the existing entry.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO core_pharmacymedicine 
                                     (pharmacy_id, medicine_id, stock_quantity, unit_price, expiry_date, batch_number, last_restocked)
                                     VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $pharmacy_id, 
                    $medicine_id, 
                    $stock_quantity, 
                    $unit_price, 
                    $expiry_date, 
                    $batch_number,
                    $last_restocked
                ]);
                
                $pdo->commit();
                setFlash("Medicine added to stock successfully!");
                header("Location: /admin/pharmacy_stock.php?pharmacy=" . $pharmacy_id);
                exit;
            }
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error adding stock: " . $e->getMessage();
        }
    }
}

$stmt = $pdo->query("SELECT medicine_id, name, type FROM core_medicine ORDER BY name");
$medicines = $stmt->fetchAll();

include __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <h2><i class="fas fa-plus-circle mr-2"></i>Add Medicine to Stock</h2>
    <a href="/admin/pharmacy_stock.php?pharmacy=<?= $pharmacy_id ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left mr-2"></i>Back to Stock
    </a>
</div>

<?php if ($error): ?>
    <div class="alert alert-error mb-4">
        <i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3>Pharmacy: <?= htmlspecialchars($pharmacy['name']) ?></h3>
    </div>
    <div class="card-body">
        <form method="post" id="addStockForm" action="">
            <div class="form-group">
                <label>Select Existing Medicine <span class="text-muted">(or create new below)</span></label>
                <select name="medicine_id" id="medicine_select" class="form-control">
                    <option value="">-- Select Medicine --</option>
                    <?php foreach ($medicines as $med): ?>
                        <option value="<?= $med['medicine_id'] ?>" data-name="<?= htmlspecialchars($med['name']) ?>" data-type="<?= htmlspecialchars($med['type']) ?>">
                            <?= htmlspecialchars($med['name']) ?> (<?= htmlspecialchars($med['type']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="alert alert-info mb-4">
                <i class="fas fa-info-circle mr-2"></i>
                <strong>Or</strong> create a new medicine by filling in the fields below and leaving "Select Existing Medicine" as "-- Select Medicine --"
            </div>
            
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Medicine Name <span class="text-danger">*</span></label>
                    <input type="text" name="medicine_name" id="medicine_name" class="form-control" 
                           placeholder="e.g., Paracetamol, Amoxicillin" required>
                    <small class="form-text text-muted">Required if creating new medicine</small>
                </div>
                
                <div class="form-group col-md-6">
                    <label>Medicine Type</label>
                    <input type="text" name="medicine_type" id="medicine_type" class="form-control" 
                           placeholder="e.g., Tablet, Capsule, Syrup" value="General">
                </div>
            </div>
            
            <hr>
            
            <h4 class="mb-3">Stock Information</h4>
            
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>Stock Quantity <span class="text-danger">*</span></label>
                    <input type="number" name="stock_quantity" class="form-control" 
                           min="1" required value="<?= $_POST['stock_quantity'] ?? '' ?>">
                </div>
                
                <div class="form-group col-md-4">
                    <label>Unit Price (৳) <span class="text-danger">*</span></label>
                    <input type="number" name="unit_price" class="form-control" 
                           step="0.01" min="0.01" required value="<?= $_POST['unit_price'] ?? '' ?>">
                </div>
                
                <div class="form-group col-md-4">
                    <label>Batch Number <span class="text-danger">*</span></label>
                    <input type="text" name="batch_number" class="form-control" 
                           placeholder="e.g., BATCH-2024-001" required value="<?= htmlspecialchars($_POST['batch_number'] ?? '') ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Expiry Date <span class="text-danger">*</span></label>
                    <input type="date" name="expiry_date" class="form-control" 
                           min="<?= date('Y-m-d') ?>" required value="<?= $_POST['expiry_date'] ?? '' ?>">
                </div>
                
                <div class="form-group col-md-6">
                    <label>Last Restocked Date</label>
                    <input type="date" name="last_restocked" class="form-control" 
                           value="<?= $_POST['last_restocked'] ?? date('Y-m-d') ?>">
                </div>
            </div>
            
            <div class="form-group mt-4">
                <button type="submit" class="btn btn-success btn-lg">
                    <i class="fas fa-save mr-2"></i>Add to Stock
                </button>
                <a href="/admin/pharmacy_stock.php?pharmacy=<?= $pharmacy_id ?>" class="btn btn-secondary btn-lg ml-2">
                    <i class="fas fa-times mr-2"></i>Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function handleMedicineSelect() {
        const select = document.getElementById('medicine_select');
        const nameInput = document.getElementById('medicine_name');
        const typeInput = document.getElementById('medicine_type');
        
        if (select && nameInput && typeInput) {
            if (select.value) {
                const option = select.options[select.selectedIndex];
                nameInput.value = option.getAttribute('data-name');
                typeInput.value = option.getAttribute('data-type');
                nameInput.readOnly = true;
                typeInput.readOnly = true;
            } else {
                nameInput.readOnly = false;
                typeInput.readOnly = false;
            }
        }
    }
    
    const medicineSelect = document.getElementById('medicine_select');
    if (medicineSelect) {
        medicineSelect.addEventListener('change', handleMedicineSelect);
    }
    
    const addStockForm = document.getElementById('addStockForm');
    if (addStockForm) {
        addStockForm.addEventListener('submit', function(e) {
            const medicineSelect = document.getElementById('medicine_select').value;
            const medicineName = document.getElementById('medicine_name').value.trim();
            
            if (!medicineSelect && !medicineName) {
                e.preventDefault();
                alert('Please either select an existing medicine or enter a new medicine name.');
                return false;
            }
        });
    }
});
</script>

<?php include __DIR__ . '/../templates/footer.php'; ?>

