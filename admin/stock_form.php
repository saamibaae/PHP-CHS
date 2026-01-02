<?php
require_once __DIR__ . '/../db.php';
requireRole('ADMIN');

$hospital_id = $_SESSION['hospital_id'];
$stock_id = $_GET['id'] ?? null;

if (!$stock_id) {
    die("Invalid request.");
}

$sql = "SELECT pm.*, m.name as medicine_name, p.pharmacy_id
        FROM core_pharmacymedicine pm
        INNER JOIN core_pharmacy p ON pm.pharmacy_id = p.pharmacy_id
        INNER JOIN core_medicine m ON pm.medicine_id = m.medicine_id
        WHERE pm.pharmacy_medicine_id = ? AND p.hospital_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$stock_id, $hospital_id]);
$stock_item = $stmt->fetch();

if (!$stock_item) {
    die("Stock item not found or access denied.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stock_quantity = $_POST['stock_quantity'];
    $unit_price = $_POST['unit_price'];
    $expiry_date = $_POST['expiry_date'];
    
    $sql = "UPDATE core_pharmacymedicine 
            SET stock_quantity = ?, unit_price = ?, expiry_date = ?
            WHERE pharmacy_medicine_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$stock_quantity, $unit_price, $expiry_date, $stock_id]);
    
    setFlash("Stock updated successfully.");
    header("Location: /admin/pharmacy_stock.php?pharmacy=" . $stock_item['pharmacy_id']);
    exit;
}

include __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <h2>Update Stock: <?= htmlspecialchars($stock_item['medicine_name']) ?></h2>
    <a href="/admin/pharmacy_stock.php?pharmacy=<?= $stock_item['pharmacy_id'] ?>" class="btn btn-secondary">Back</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="post">
            <div class="form-group">
                <label>Stock Quantity</label>
                <input type="number" name="stock_quantity" class="form-control" required value="<?= htmlspecialchars($stock_item['stock_quantity']) ?>">
            </div>
            
            <div class="form-group">
                <label>Unit Price</label>
                <input type="number" step="0.01" name="unit_price" class="form-control" required value="<?= htmlspecialchars($stock_item['unit_price']) ?>">
            </div>
            
            <div class="form-group">
                <label>Expiry Date</label>
                <input type="date" name="expiry_date" class="form-control" required value="<?= htmlspecialchars($stock_item['expiry_date']) ?>">
            </div>
            
            <button type="submit" class="btn btn-primary">Update Stock</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
