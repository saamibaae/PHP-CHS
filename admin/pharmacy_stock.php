<?php
require_once __DIR__ . '/../db.php';
requireRole('ADMIN');

$hospital_id = $_SESSION['hospital_id'];

$stmt = $pdo->prepare("SELECT * FROM core_pharmacy WHERE hospital_id = ?");
$stmt->execute([$hospital_id]);
$pharmacies = $stmt->fetchAll();

$selected_pharmacy = null;
$pharmacy_id = $_GET['pharmacy'] ?? null;
$search = $_GET['search'] ?? '';
$filter_low_stock = isset($_GET['low_stock']) ? true : false;
$filter_expiring = isset($_GET['expiring']) ? true : false;

if ($pharmacy_id) {
    $stmt = $pdo->prepare("SELECT * FROM core_pharmacy WHERE pharmacy_id = ? AND hospital_id = ?");
    $stmt->execute([$pharmacy_id, $hospital_id]);
    $selected_pharmacy = $stmt->fetch();
} elseif (!empty($pharmacies)) {
    $selected_pharmacy = $pharmacies[0];
    $pharmacy_id = $selected_pharmacy['pharmacy_id'];
}

$stock_items = [];
$stats = [
    'total_items' => 0,
    'low_stock' => 0,
    'expiring_soon' => 0,
    'total_value' => 0
];

if ($selected_pharmacy) {
    $sql = "SELECT pm.*, m.name as medicine_name, m.type as medicine_type, m.medicine_id,
            DATEDIFF(pm.expiry_date, CURDATE()) as days_until_expiry
            FROM core_pharmacymedicine pm
            INNER JOIN core_medicine m ON pm.medicine_id = m.medicine_id
            WHERE pm.pharmacy_id = ?";
    
    $params = [$selected_pharmacy['pharmacy_id']];
    
    if ($search) {
        $sql .= " AND (m.name LIKE ? OR m.type LIKE ? OR pm.batch_number LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    if ($filter_low_stock) {
        $sql .= " AND pm.stock_quantity < 50";
    }
    
    if ($filter_expiring) {
        $sql .= " AND pm.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
    }
    
    $sql .= " ORDER BY m.name";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $stock_items = $stmt->fetchAll();
    
    $stats_sql = "SELECT 
                    COUNT(*) as total_items,
                    SUM(CASE WHEN stock_quantity < 50 THEN 1 ELSE 0 END) as low_stock,
                    SUM(CASE WHEN expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as expiring_soon,
                    SUM(stock_quantity * unit_price) as total_value
                  FROM core_pharmacymedicine
                  WHERE pharmacy_id = ?";
    $stats_stmt = $pdo->prepare($stats_sql);
    $stats_stmt->execute([$selected_pharmacy['pharmacy_id']]);
    $stats = $stats_stmt->fetch();
}

include __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <h2><i class="fas fa-pills mr-2"></i>Pharmacy Stock Management</h2>
    <?php if ($selected_pharmacy): ?>
        <a href="/admin/add_stock.php?pharmacy=<?= $pharmacy_id ?>" class="btn btn-success">
            <i class="fas fa-plus mr-2"></i>Add Medicine to Stock
        </a>
    <?php endif; ?>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="form-inline">
            <input type="hidden" name="pharmacy" value="<?= $pharmacy_id ?>">
            <div class="form-group mr-3">
                <label class="mr-2">Select Pharmacy:</label>
                <select name="pharmacy" class="form-control" onchange="this.form.submit()" style="min-width: 200px;">
                    <?php foreach ($pharmacies as $pharm): ?>
                        <option value="<?= $pharm['pharmacy_id'] ?>" <?= ($selected_pharmacy && $pharm['pharmacy_id'] == $selected_pharmacy['pharmacy_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($pharm['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mr-3">
                <input type="text" name="search" class="form-control" placeholder="Search medicines..." value="<?= htmlspecialchars($search) ?>" style="min-width: 250px;">
            </div>
            <div class="form-group mr-3">
                <label class="mr-2">
                    <input type="checkbox" name="low_stock" value="1" <?= $filter_low_stock ? 'checked' : '' ?> onchange="this.form.submit()">
                    Low Stock (< 50)
                </label>
            </div>
            <div class="form-group mr-3">
                <label class="mr-2">
                    <input type="checkbox" name="expiring" value="1" <?= $filter_expiring ? 'checked' : '' ?> onchange="this.form.submit()">
                    Expiring Soon
                </label>
            </div>
            <button type="submit" class="btn btn-primary mr-2">
                <i class="fas fa-search mr-2"></i>Search
            </button>
            <?php if ($search || $filter_low_stock || $filter_expiring): ?>
                <a href="/admin/pharmacy_stock.php?pharmacy=<?= $pharmacy_id ?>" class="btn btn-secondary">
                    <i class="fas fa-times mr-2"></i>Clear
                </a>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php if ($selected_pharmacy): ?>
    <div class="stats-grid mb-4">
        <div class="stat-card">
            <h3><?= $stats['total_items'] ?? 0 ?></h3>
            <p><i class="fas fa-boxes mr-2"></i>Total Stock Items</p>
        </div>
        <div class="stat-card warning">
            <h3><?= $stats['low_stock'] ?? 0 ?></h3>
            <p><i class="fas fa-exclamation-triangle mr-2"></i>Low Stock Items</p>
        </div>
        <div class="stat-card danger">
            <h3><?= $stats['expiring_soon'] ?? 0 ?></h3>
            <p><i class="fas fa-calendar-times mr-2"></i>Expiring Soon (30 days)</p>
        </div>
        <div class="stat-card success">
            <h3>৳<?= number_format($stats['total_value'] ?? 0, 2) ?></h3>
            <p><i class="fas fa-dollar-sign mr-2"></i>Total Stock Value</p>
        </div>
    </div>

    <?php if ($stats['low_stock'] > 0): ?>
        <div class="alert alert-warning mb-4">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            <strong>Warning:</strong> <?= $stats['low_stock'] ?> medicine(s) have low stock (less than 50 units).
        </div>
    <?php endif; ?>
    
    <?php if ($stats['expiring_soon'] > 0): ?>
        <div class="alert alert-error mb-4">
            <i class="fas fa-calendar-times mr-2"></i>
            <strong>Alert:</strong> <?= $stats['expiring_soon'] ?> medicine(s) are expiring within the next 30 days.
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-warehouse mr-2"></i>Stock at <?= htmlspecialchars($selected_pharmacy['name']) ?></h3>
        </div>
        <div class="card-body">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Medicine</th>
                            <th>Type</th>
                            <th>Batch Number</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Total Value</th>
                            <th>Expiry Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stock_items as $item): 
                            $is_low_stock = $item['stock_quantity'] < 50;
                            $is_expiring_soon = isset($item['days_until_expiry']) && $item['days_until_expiry'] <= 30 && $item['days_until_expiry'] >= 0;
                            $is_expired = isset($item['days_until_expiry']) && $item['days_until_expiry'] < 0;
                            $total_value = $item['stock_quantity'] * $item['unit_price'];
                        ?>
                        <tr class="<?= $is_expired ? 'bg-red-50' : ($is_expiring_soon ? 'bg-yellow-50' : ($is_low_stock ? 'bg-orange-50' : '')) ?>">
                            <td><strong><?= htmlspecialchars($item['medicine_name']) ?></strong></td>
                            <td><?= htmlspecialchars($item['medicine_type']) ?></td>
                            <td><code class="text-sm"><?= htmlspecialchars($item['batch_number']) ?></code></td>
                            <td>
                                <span class="<?= $is_low_stock ? 'text-danger font-bold' : '' ?>">
                                    <?= htmlspecialchars($item['stock_quantity']) ?>
                                </span>
                            </td>
                            <td>৳<?= number_format($item['unit_price'], 2) ?></td>
                            <td><strong>৳<?= number_format($total_value, 2) ?></strong></td>
                            <td>
                                <?php if ($is_expired): ?>
                                    <span class="text-danger font-bold">
                                        <?= htmlspecialchars($item['expiry_date']) ?>
                                        <i class="fas fa-exclamation-circle ml-1" title="Expired"></i>
                                    </span>
                                <?php elseif ($is_expiring_soon): ?>
                                    <span class="text-yellow-600 font-bold">
                                        <?= htmlspecialchars($item['expiry_date']) ?>
                                        <i class="fas fa-clock ml-1" title="Expiring in <?= $item['days_until_expiry'] ?> days"></i>
                                    </span>
                                <?php else: ?>
                                    <?= htmlspecialchars($item['expiry_date']) ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($is_expired): ?>
                                    <span class="badge badge-danger">Expired</span>
                                <?php elseif ($is_expiring_soon): ?>
                                    <span class="badge badge-warning">Expiring Soon</span>
                                <?php elseif ($is_low_stock): ?>
                                    <span class="badge badge-warning">Low Stock</span>
                                <?php else: ?>
                                    <span class="badge badge-success">In Stock</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="/admin/stock_form.php?id=<?= $item['pharmacy_medicine_id'] ?>" class="btn btn-sm btn-primary" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="/admin/delete_stock.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this stock item? This action cannot be undone.');">
                                        <input type="hidden" name="id" value="<?= $item['pharmacy_medicine_id'] ?>">
                                        <input type="hidden" name="pharmacy" value="<?= $pharmacy_id ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($stock_items)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <i class="fas fa-inbox text-gray-400 text-4xl mb-2"></i>
                                <p class="text-gray-500">No stock items found.</p>
                                <a href="/admin/add_stock.php?pharmacy=<?= $pharmacy_id ?>" class="btn btn-primary mt-2">
                                    <i class="fas fa-plus mr-2"></i>Add First Medicine
                                </a>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle mr-2"></i>
        No pharmacies found for this hospital. Please create a pharmacy first.
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../templates/footer.php'; ?>
