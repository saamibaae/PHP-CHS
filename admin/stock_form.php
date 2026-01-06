<?php
require_once __DIR__ . '/../db.php';
requireRole('ADMIN');

$hospital_id = $_SESSION['hospital_id'];
$stock_id = $_GET['id'] ?? null;

if (!$stock_id) {
    setFlash("Invalid request.", "error");
    header("Location: /admin/pharmacy_stock.php");
    exit;
}

$sql = "SELECT pm.*, m.name as medicine_name, m.type as medicine_type, p.pharmacy_id, p.name as pharmacy_name
        FROM core_pharmacymedicine pm
        INNER JOIN core_pharmacy p ON pm.pharmacy_id = p.pharmacy_id
        INNER JOIN core_medicine m ON pm.medicine_id = m.medicine_id
        WHERE pm.pharmacy_medicine_id = ? AND p.hospital_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$stock_id, $hospital_id]);
$stock_item = $stmt->fetch();

if (!$stock_item) {
    setFlash("Stock item not found or access denied.", "error");
    header("Location: /admin/pharmacy_stock.php");
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stock_quantity = intval($_POST['stock_quantity'] ?? 0);
    $unit_price = floatval($_POST['unit_price'] ?? 0);
    $expiry_date = $_POST['expiry_date'] ?? '';
    $batch_number = trim($_POST['batch_number'] ?? '');
    $last_restocked = $_POST['last_restocked'] ?? null;
    
    if ($stock_quantity <= 0) {
        $error = "Stock quantity must be greater than 0.";
    } elseif ($unit_price <= 0) {
        $error = "Unit price must be greater than 0.";
    } elseif (empty($expiry_date)) {
        $error = "Expiry date is required.";
    } elseif (empty($batch_number)) {
        $error = "Batch number is required.";
    } else {
        $check_sql = "SELECT pharmacy_medicine_id FROM core_pharmacymedicine 
                     WHERE pharmacy_id = ? AND medicine_id = ? AND batch_number = ? AND pharmacy_medicine_id != ?";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([$stock_item['pharmacy_id'], $stock_item['medicine_id'], $batch_number, $stock_id]);
        $conflict = $check_stmt->fetchColumn();
        
        if ($conflict) {
            $error = "A stock entry with this batch number already exists for this medicine. Please use a different batch number.";
        } else {
            try {
                $update_sql = "UPDATE core_pharmacymedicine 
                              SET stock_quantity = ?, unit_price = ?, expiry_date = ?, batch_number = ?";
                $params = [$stock_quantity, $unit_price, $expiry_date, $batch_number];
                
                if ($last_restocked) {
                    $update_sql .= ", last_restocked = ?";
                    $params[] = $last_restocked;
                }
                
                $update_sql .= " WHERE pharmacy_medicine_id = ?";
                $params[] = $stock_id;
                
                $stmt = $pdo->prepare($update_sql);
                $stmt->execute($params);
                
                setFlash("Stock updated successfully.");
                header("Location: /admin/pharmacy_stock.php?pharmacy=" . $stock_item['pharmacy_id']);
                exit;
            } catch (Exception $e) {
                $error = "Error updating stock: " . $e->getMessage();
            }
        }
    }
}

include __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <h2><i class="fas fa-edit mr-2"></i>Update Stock: <?= htmlspecialchars($stock_item['medicine_name']) ?></h2>
    <a href="/admin/pharmacy_stock.php?pharmacy=<?= $stock_item['pharmacy_id'] ?>" class="btn btn-secondary">
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
        <h3>Pharmacy: <?= htmlspecialchars($stock_item['pharmacy_name']) ?></h3>
    </div>
    <div class="card-body">
        <div class="mb-4 p-3 bg-gray-50 rounded">
            <strong>Medicine:</strong> <?= htmlspecialchars($stock_item['medicine_name']) ?> 
            <span class="text-muted">(<?= htmlspecialchars($stock_item['medicine_type']) ?>)</span>
        </div>
        
        <form method="post" action="">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Stock Quantity <span class="text-danger">*</span></label>
                    <input type="number" name="stock_quantity" class="form-control" 
                           min="1" required value="<?= htmlspecialchars($stock_item['stock_quantity']) ?>">
                </div>
                
                <div class="form-group col-md-6">
                    <label>Unit Price (৳) <span class="text-danger">*</span></label>
                    <input type="number" name="unit_price" class="form-control" 
                           step="0.01" min="0.01" required value="<?= htmlspecialchars($stock_item['unit_price']) ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Batch Number <span class="text-danger">*</span></label>
                    <input type="text" name="batch_number" class="form-control" 
                           required value="<?= htmlspecialchars($stock_item['batch_number']) ?>">
                </div>
                
                <div class="form-group col-md-6">
                    <label>Expiry Date <span class="text-danger">*</span></label>
                    <input type="date" name="expiry_date" class="form-control" 
                           min="<?= date('Y-m-d') ?>" required value="<?= htmlspecialchars($stock_item['expiry_date']) ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label>Last Restocked Date</label>
                <input type="date" name="last_restocked" class="form-control" 
                       value="<?= htmlspecialchars($stock_item['last_restocked'] ?? date('Y-m-d')) ?>">
            </div>
            
            <div class="form-group mt-4">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save mr-2"></i>Update Stock
                </button>
                <a href="/admin/pharmacy_stock.php?pharmacy=<?= $stock_item['pharmacy_id'] ?>" class="btn btn-secondary btn-lg ml-2">
                    <i class="fas fa-times mr-2"></i>Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>

