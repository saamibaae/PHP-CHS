<?php
require_once __DIR__ . '/../db.php';
requireRole('ADMIN');

$hospital_id = $_SESSION['hospital_id'];

$stmt = $pdo->prepare("SELECT * FROM core_pharmacy WHERE hospital_id = ?");
$stmt->execute([$hospital_id]);
$pharmacies = $stmt->fetchAll();

$selected_pharmacy = null;
$pharmacy_id = $_GET['pharmacy'] ?? null;

if ($pharmacy_id) {
    $stmt = $pdo->prepare("SELECT * FROM core_pharmacy WHERE pharmacy_id = ? AND hospital_id = ?");
    $stmt->execute([$pharmacy_id, $hospital_id]);
    $selected_pharmacy = $stmt->fetch();
} elseif (!empty($pharmacies)) {
    $selected_pharmacy = $pharmacies[0];
    $pharmacy_id = $selected_pharmacy['pharmacy_id'];
}

$stock_items = [];
if ($selected_pharmacy) {
    $sql = "SELECT pm.*, m.name as medicine_name, m.type as medicine_type
            FROM core_pharmacymedicine pm
            INNER JOIN core_medicine m ON pm.medicine_id = m.medicine_id
            WHERE pm.pharmacy_id = ?
            ORDER BY m.name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$selected_pharmacy['pharmacy_id']]);
    $stock_items = $stmt->fetchAll();
}

include __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <h2>Pharmacy Stock</h2>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="form-inline">
            <label class="mr-2">Select Pharmacy:</label>
            <select name="pharmacy" class="form-control mr-2" onchange="this.form.submit()">
                <?php foreach ($pharmacies as $pharm): ?>
                    <option value="<?= $pharm['pharmacy_id'] ?>" <?= ($selected_pharmacy && $pharm['pharmacy_id'] == $selected_pharmacy['pharmacy_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($pharm['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
</div>

<?php if ($selected_pharmacy): ?>
<div class="card">
    <div class="card-header">
        <h3>Stock at <?= htmlspecialchars($selected_pharmacy['name']) ?></h3>
    </div>
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>Medicine</th>
                    <th>Type</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Expiry Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stock_items as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['medicine_name']) ?></td>
                    <td><?= htmlspecialchars($item['medicine_type']) ?></td>
                    <td><?= htmlspecialchars($item['stock_quantity']) ?></td>
                    <td><?= number_format($item['unit_price'], 2) ?></td>
                    <td><?= htmlspecialchars($item['expiry_date']) ?></td>
                    <td>
                        <a href="/admin/stock_form.php?id=<?= $item['pharmacy_medicine_id'] ?>" class="btn-sm btn-secondary">Update</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($stock_items)): ?>
                <tr>
                    <td colspan="6" class="text-center">No stock items found.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php else: ?>
    <div class="alert alert-info">No pharmacies found for this hospital.</div>
<?php endif; ?>

<?php include __DIR__ . '/../templates/footer.php'; ?>
