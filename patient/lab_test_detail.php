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

$test_id = $_GET['id'] ?? null;

if (!$test_id) {
    setFlash("Invalid test ID.", "error");
    header("Location: /patient/lab_tests.php");
    exit;
}

$bill_id_exists = checkColumnExists('core_labtest', 'bill_id');

if ($bill_id_exists) {
    $sql = "SELECT lt.*, l.lab_name, l.location as lab_location, l.phone as lab_phone,
            d.full_name as doctor_name, d.specialization, d.phone as doctor_phone,
            b.status as bill_status, b.bill_id
            FROM core_labtest lt
            LEFT JOIN core_lab l ON lt.lab_id = l.lab_id
            INNER JOIN core_doctor d ON lt.ordered_by_id = d.doctor_id
            LEFT JOIN core_bill b ON lt.bill_id = b.bill_id
            WHERE lt.test_id = ? AND lt.patient_id = ?";
} else {
    $sql = "SELECT lt.*, l.lab_name, l.location as lab_location, l.phone as lab_phone,
            d.full_name as doctor_name, d.specialization, d.phone as doctor_phone,
            NULL as bill_status, NULL as bill_id
            FROM core_labtest lt
            LEFT JOIN core_lab l ON lt.lab_id = l.lab_id
            INNER JOIN core_doctor d ON lt.ordered_by_id = d.doctor_id
            WHERE lt.test_id = ? AND lt.patient_id = ?";
}
$stmt = $pdo->prepare($sql);
$stmt->execute([$test_id, $patient_id]);
$test = $stmt->fetch();

if (!$test) {
    setFlash("Lab test not found or access denied.", "error");
    header("Location: /patient/lab_tests.php");
    exit;
}

$can_access_results = ($test['status'] == 'Completed');

include __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <h2><i class="fas fa-vial mr-2"></i>Lab Test Details</h2>
    <a href="/patient/lab_tests.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left mr-2"></i>Back to Tests
    </a>
</div>

<div class="row">
    <div class="col-md-4">
        <?php if ($test['lab_name']): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h3><i class="fas fa-flask mr-2"></i>Lab Information</h3>
            </div>
            <div class="card-body">
                <p><strong>Lab Name:</strong> <?= htmlspecialchars($test['lab_name']) ?></p>
                <p><strong>Location:</strong> <?= htmlspecialchars($test['lab_location']) ?></p>
                <p><strong>Phone:</strong> <?= htmlspecialchars($test['lab_phone']) ?></p>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-user-md mr-2"></i>Ordered By</h3>
            </div>
            <div class="card-body">
                <p><strong>Doctor:</strong> Dr. <?= htmlspecialchars($test['doctor_name']) ?></p>
                <p><strong>Specialization:</strong> <?= htmlspecialchars($test['specialization']) ?></p>
                <p><strong>Phone:</strong> <?= htmlspecialchars($test['doctor_phone']) ?></p>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3><i class="fas fa-file-medical mr-2"></i>Test Information</h3>
                <span class="badge <?php
                    $status_class = [
                        'Ordered' => 'badge-warning',
                        'In Progress' => 'badge-info',
                        'Completed' => 'badge-success',
                        'Cancelled' => 'badge-danger'
                    ];
                    echo $status_class[$test['status']] ?? 'badge-primary';
                ?>"><?= htmlspecialchars($test['status']) ?></span>
            </div>
            <div class="card-body">
                <div class="form-row mb-3">
                    <div class="col-md-6">
                        <strong>Test Type:</strong><br>
                        <span class="text-lg"><?= htmlspecialchars($test['test_type']) ?></span>
                    </div>
                    <div class="col-md-6">
                        <strong>Test Cost:</strong><br>
                        <span class="text-lg font-bold text-primary">৳<?= number_format($test['test_cost'], 2) ?></span>
                    </div>
                </div>
                
                <div class="form-row mb-3">
                    <div class="col-md-6">
                        <strong>Ordered Date:</strong><br>
                        <?= date('M d, Y H:i', strtotime($test['date_and_time'])) ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Status:</strong><br>
                        <span class="badge <?php
                            echo $status_class[$test['status']] ?? 'badge-primary';
                        ?>"><?= htmlspecialchars($test['status']) ?></span>
                    </div>
                </div>
                
                <?php if ($test['remarks']): ?>
                    <div class="mb-3">
                        <strong>Remarks:</strong><br>
                        <div class="p-3 bg-gray-50 rounded"><?= nl2br(htmlspecialchars($test['remarks'])) ?></div>
                    </div>
                <?php endif; ?>
                
                <?php if ($test['status'] == 'Ordered'): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-hourglass-half mr-2"></i>
                        <strong>Status:</strong> Lab test order is being processed by admin. You will be notified when it's completed.
                    </div>
                <?php elseif ($test['status'] == 'Completed' && $test['result']): ?>
                    <div class="mb-3">
                        <strong>Test Result:</strong><br>
                        <div class="p-4 bg-green-50 border border-green-200 rounded">
                            <pre class="whitespace-pre-wrap font-sans"><?= htmlspecialchars($test['result']) ?></pre>
                        </div>
                    </div>
                    <?php if ($test['bill_id']): ?>
                        <div class="mt-3">
                            <a href="/patient/lab_test_pdf.php?id=<?= $test_id ?>" class="btn btn-success" target="_blank">
                                <i class="fas fa-file-pdf mr-2"></i>Download PDF Report with Bill
                            </a>
                        </div>
                    <?php endif; ?>
                <?php elseif ($test['status'] == 'Completed' && !$test['result']): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-clock mr-2"></i>
                        Test is marked as completed but results are not yet available. Please contact the lab.
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-clock mr-2"></i>
                        Test result is pending. Please check back later or contact the lab.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>

