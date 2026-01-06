<?php
require_once __DIR__ . '/../db.php';
requireRole('PATIENT');

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT patient_id FROM core_patient WHERE user_id = ?");
$stmt->execute([$user_id]);
$patient_id = $stmt->fetchColumn();

if (!$patient_id) {
    die("Patient record not found. Please contact administrator.");
}

$sql = "SELECT b.*, st.name as service_type_name
        FROM core_bill b
        INNER JOIN core_servicetype st ON b.service_type_id = st.service_type_id
        WHERE b.patient_id = ?
        ORDER BY b.bill_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$patient_id]);
$bills = $stmt->fetchAll();

$sql = "SELECT pb.*, b.*, p.name as pharmacy_name
        FROM core_pharmacybill pb
        INNER JOIN core_bill b ON pb.bill_id = b.bill_id
        INNER JOIN core_pharmacy p ON pb.pharmacy_id = p.pharmacy_id
        WHERE b.patient_id = ?
        ORDER BY pb.purchase_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$patient_id]);
$pharmacy_bills = $stmt->fetchAll();

include __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <h2>My Bills</h2>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Hospital Service Bills</div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Service</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bills as $bill): ?>
                        <tr>
                            <td><?= $bill['bill_date'] ?></td>
                            <td><?= htmlspecialchars($bill['service_type_name']) ?></td>
                            <td><?= number_format($bill['total_amount'], 2) ?></td>
                            <td>
                                <span class="badge badge-<?= strtolower($bill['status']) == 'paid' ? 'completed' : 'pending' ?>">
                                    <?= htmlspecialchars($bill['status']) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($bills)): ?>
                        <tr><td colspan="4" class="text-center">No bills found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Pharmacy Bills</div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Pharmacy</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pharmacy_bills as $bill): ?>
                        <tr>
                            <td><?= $bill['purchase_date'] ?></td>
                            <td><?= htmlspecialchars($bill['pharmacy_name']) ?></td>
                            <td><?= number_format($bill['total_amount'], 2) ?></td>
                            <td>
                                <span class="badge badge-<?= strtolower($bill['status']) == 'paid' ? 'completed' : 'pending' ?>">
                                    <?= htmlspecialchars($bill['status']) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($pharmacy_bills)): ?>
                        <tr><td colspan="4" class="text-center">No pharmacy bills found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
