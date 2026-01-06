<?php
require_once __DIR__ . '/../db.php';
requireRole('ADMIN');

$hospital_id = $_SESSION['hospital_id'];
$test_id = $_GET['id'] ?? null;

if (!$test_id) {
    setFlash("Invalid test ID.", "error");
    header("Location: /admin/lab_test_orders.php");
    exit;
}

$bill_id_exists = checkColumnExists('core_labtest', 'bill_id');

if ($bill_id_exists) {
    $sql = "SELECT lt.*, p.full_name as patient_name, p.national_id, p.patient_id,
            d.full_name as doctor_name, d.specialization,
            b.bill_id, b.status as bill_status
            FROM core_labtest lt
            INNER JOIN core_patient p ON lt.patient_id = p.patient_id
            INNER JOIN core_doctor d ON lt.ordered_by_id = d.doctor_id
            LEFT JOIN core_bill b ON lt.bill_id = b.bill_id
            WHERE lt.test_id = ? AND d.hospital_id = ?";
} else {
    $sql = "SELECT lt.*, p.full_name as patient_name, p.national_id, p.patient_id,
            d.full_name as doctor_name, d.specialization,
            NULL as bill_id, NULL as bill_status
            FROM core_labtest lt
            INNER JOIN core_patient p ON lt.patient_id = p.patient_id
            INNER JOIN core_doctor d ON lt.ordered_by_id = d.doctor_id
            WHERE lt.test_id = ? AND d.hospital_id = ?";
}
$stmt = $pdo->prepare($sql);
$stmt->execute([$test_id, $hospital_id]);
$test = $stmt->fetch();

if (!$test) {
    setFlash("Lab test not found or access denied.", "error");
    header("Location: /admin/lab_test_orders.php");
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'complete') {
        $result = trim($_POST['result'] ?? '');
        $test_cost = floatval($_POST['test_cost'] ?? 0);
        
        if (empty($result)) {
            $error = "Test result is required.";
        } elseif ($test_cost <= 0) {
            $error = "Test cost must be greater than 0.";
        } else {
            try {
                $pdo->beginTransaction();
                
                $lab_id = !empty($_POST['lab_id']) ? intval($_POST['lab_id']) : null;
                
                if ($lab_id) {
                    $update_sql = "UPDATE core_labtest SET status = 'Completed', result = ?, test_cost = ?, lab_id = ? WHERE test_id = ?";
                    $update_stmt = $pdo->prepare($update_sql);
                    $update_stmt->execute([$result, $test_cost, $lab_id, $test_id]);
                } else {
                    $update_sql = "UPDATE core_labtest SET status = 'Completed', result = ?, test_cost = ? WHERE test_id = ?";
                    $update_stmt = $pdo->prepare($update_sql);
                    $update_stmt->execute([$result, $test_cost, $test_id]);
                }
                
                $stmt = $pdo->query("SELECT service_type_id FROM core_servicetype WHERE name LIKE '%Lab%' OR name LIKE '%Test%' LIMIT 1");
                $service_type_id = $stmt->fetchColumn();
                
                if (!$service_type_id) {
                    $pdo->exec("INSERT INTO core_servicetype (name, description) VALUES ('Lab Test', 'Laboratory test services')");
                    $service_type_id = $pdo->lastInsertId();
                }
                
                if (!$test['bill_id']) {
                    $due_date = date('Y-m-d', strtotime('+30 days'));
                    $bill_sql = "INSERT INTO core_bill (patient_id, service_type_id, total_amount, status, due_date)
                                VALUES (?, ?, ?, 'Pending', ?)";
                    $bill_stmt = $pdo->prepare($bill_sql);
                    $bill_stmt->execute([$test['patient_id'], $service_type_id, $test_cost, $due_date]);
                    $bill_id = $pdo->lastInsertId();
                    
                    if (!$bill_id) {
                        throw new Exception("Failed to create bill");
                    }
                    
                    if ($bill_id_exists) {
                        $link_sql = "UPDATE core_labtest SET bill_id = ? WHERE test_id = ?";
                        $link_stmt = $pdo->prepare($link_sql);
                        $link_stmt->execute([$bill_id, $test_id]);
                    }
                }
                
                $pdo->commit();
                setFlash("Lab test marked as completed and bill created successfully.");
                header("Location: /admin/lab_test_orders.php");
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Error processing lab test: " . $e->getMessage();
            }
        }
    } elseif ($action == 'mark_paid') {
        if (!$test['bill_id']) {
            $error = "No bill found for this test.";
        } else {
            try {
                $pdo->beginTransaction();
                
                $update_sql = "UPDATE core_bill SET status = 'Paid' WHERE bill_id = ?";
                $update_stmt = $pdo->prepare($update_sql);
                $update_stmt->execute([$test['bill_id']]);
                
                if ($update_stmt->rowCount() === 0) {
                    throw new Exception("Bill not found or already updated");
                }
                
                $pdo->commit();
                setFlash("Bill marked as paid. Patient can now access lab test results.");
                header("Location: /admin/lab_test_process.php?id=" . $test_id);
                exit;
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = "Error updating bill: " . $e->getMessage();
            }
        }
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$test_id, $hospital_id]);
    $test = $stmt->fetch();
}

include __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <h2><i class="fas fa-cog mr-2"></i>Process Lab Test Order</h2>
    <a href="/admin/lab_test_orders.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left mr-2"></i>Back to Orders
    </a>
</div>

<?php if ($error): ?>
    <div class="alert alert-error mb-4">
        <i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h3><i class="fas fa-user mr-2"></i>Patient Information</h3>
            </div>
            <div class="card-body">
                <p><strong>Name:</strong> <?= htmlspecialchars($test['patient_name']) ?></p>
                <p><strong>National ID:</strong> <?= htmlspecialchars($test['national_id']) ?></p>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-user-md mr-2"></i>Ordered By</h3>
            </div>
            <div class="card-body">
                <p><strong>Doctor:</strong> Dr. <?= htmlspecialchars($test['doctor_name']) ?></p>
                <p><strong>Specialization:</strong> <?= htmlspecialchars($test['specialization']) ?></p>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-vial mr-2"></i>Lab Test Details</h3>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>Ordered Tests:</strong>
                    <div class="p-3 bg-gray-50 rounded mt-2">
                        <?= nl2br(htmlspecialchars($test['test_type'])) ?>
                    </div>
                </div>
                
                <?php if ($test['remarks']): ?>
                    <div class="mb-3">
                        <strong>Remarks:</strong>
                        <div class="p-3 bg-gray-50 rounded mt-2">
                            <?= nl2br(htmlspecialchars($test['remarks'])) ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="mb-3">
                    <strong>Order Date:</strong> <?= date('M d, Y H:i', strtotime($test['date_and_time'])) ?>
                </div>
                
                <div class="mb-3">
                    <strong>Status:</strong>
                    <span class="badge <?= $test['status'] == 'Completed' ? 'badge-success' : 'badge-warning' ?>">
                        <?= htmlspecialchars($test['status']) ?>
                    </span>
                </div>
                
                <?php if ($test['bill_id']): ?>
                    <div class="mb-3">
                        <strong>Bill Status:</strong>
                        <span class="badge <?= $test['bill_status'] == 'Paid' ? 'badge-success' : 'badge-warning' ?>">
                            <?= htmlspecialchars($test['bill_status']) ?>
                        </span>
                    </div>
                <?php endif; ?>
                
                <hr class="my-4">
                
                <?php if (strtolower(trim($test['status'] ?? '')) != 'completed'): ?>
                    <div class="card mt-4">
                        <div class="card-header">
                            <h4>
                                <i class="fas fa-flask mr-2"></i>Complete Lab Test
                            </h4>
                            <p class="text-muted mb-0">Enter test results and create bill. Once completed, both patient and doctor will be able to view the results.</p>
                        </div>
                        <div class="card-body">
                            <form method="post" action="">
                                <input type="hidden" name="action" value="complete">
                                
                                <?php
                                $labs_stmt = $pdo->prepare("SELECT lab_id, lab_name FROM core_lab WHERE hospital_id = ? ORDER BY lab_name");
                                $labs_stmt->execute([$hospital_id]);
                                $labs = $labs_stmt->fetchAll();
                                ?>
                                <?php if (!empty($labs)): ?>
                                <div class="form-group">
                                    <label for="lab_id">
                                        <i class="fas fa-building mr-2"></i>Assign to Lab (Optional)
                                    </label>
                                    <select name="lab_id" id="lab_id" class="form-control">
                                        <option value="">-- Select Lab --</option>
                                        <?php foreach ($labs as $lab): ?>
                                            <option value="<?= $lab['lab_id'] ?>" <?= $test['lab_id'] == $lab['lab_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($lab['lab_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <?php endif; ?>
                                
                                <hr class="my-4">
                                
                                <div class="form-group">
                                    <label for="result" class="font-weight-bold">
                                        <i class="fas fa-file-medical mr-2"></i>Lab Test Result <span class="text-danger">*</span>
                                    </label>
                                    <p class="text-muted small mb-2">Enter the detailed test results, measurements, values, observations, and any other relevant information. This will be visible to both the patient and the ordering doctor once completed.</p>
                                    <textarea name="result" id="result" rows="12" 
                                              class="form-control"
                                              placeholder="Example format:&#10;Blood Test Results:&#10;Hemoglobin: 14.5 g/dL (Normal: 12-16)&#10;White Blood Cells: 7,200 /μL (Normal: 4,000-11,000)&#10;Platelets: 250,000 /μL (Normal: 150,000-450,000)&#10;&#10;Observations: All values within normal range. No abnormalities detected." 
                                              required><?= htmlspecialchars($test['result'] ?? '') ?></textarea>
                                    <small class="form-text text-muted">This result will be shared with the patient and doctor after completion.</small>
                                </div>
                                
                                <hr class="my-4">
                                
                                <div class="form-group">
                                    <label for="test_cost" class="font-weight-bold">
                                        <i class="fas fa-money-bill-wave mr-2"></i>Test Cost / Bill Amount (৳) <span class="text-danger">*</span>
                                    </label>
                                    <p class="text-muted small mb-2">Enter the cost of this lab test. A bill will be automatically created for the patient.</p>
                                    <div class="input-group" style="max-width: 300px;">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">৳</span>
                                        </div>
                                        <input type="number" step="0.01" name="test_cost" id="test_cost" 
                                               class="form-control"
                                               min="0.01" required value="<?= htmlspecialchars($test['test_cost'] ?? '') ?>"
                                               placeholder="0.00">
                                    </div>
                                    <small class="form-text text-muted">A bill will be created automatically and linked to this test.</small>
                                </div>
                                
                                <div class="form-group mt-4">
                                    <button type="submit" class="btn btn-success btn-lg">
                                        <i class="fas fa-check mr-2"></i>Complete Test & Create Bill
                                    </button>
                                    <a href="/admin/lab_test_orders.php" class="btn btn-secondary btn-lg ml-2">
                                        <i class="fas fa-times mr-2"></i>Cancel
                                    </a>
                                </div>
                                
                                <div class="alert alert-info mt-3">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    <strong>Note:</strong> After completing this test, the patient and the ordering doctor (Dr. <?= htmlspecialchars($test['doctor_name']) ?>) will be able to view the test results in their respective dashboards.
                                </div>
                            </form>
                        </div>
                    </div>
                <?php elseif (strtolower(trim($test['status'])) == 'completed'): ?>
                    <div class="card mt-4">
                        <div class="card-header">
                            <h4>
                                <i class="fas fa-check-circle mr-2 text-success"></i>Test Results
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-success mb-4">
                                <pre style="white-space: pre-wrap; font-family: inherit; margin: 0;"><?= htmlspecialchars($test['result']) ?></pre>
                            </div>
                            
                            <div class="mb-4">
                                <strong>Test Cost:</strong> 
                                <span class="text-lg font-semibold text-success">৳<?= number_format($test['test_cost'], 2) ?></span>
                            </div>
                            
                            <?php if ($test['bill_id']): ?>
                                <div class="alert alert-info mt-4">
                                    <div>
                                        <strong>Bill Status:</strong> 
                                        <span class="badge <?= $test['bill_status'] == 'Paid' ? 'badge-success' : 'badge-warning' ?>">
                                            <?= htmlspecialchars($test['bill_status']) ?>
                                        </span>
                                        <p class="mt-2 mb-0">
                                            Patient can access lab test results once the test is completed.
                                        </p>
                                        <?php if ($test['bill_status'] != 'Paid'): ?>
                                            <form method="post" action="" class="mt-3">
                                                <input type="hidden" name="action" value="mark_paid">
                                                <button type="submit" class="btn btn-success">
                                                    <i class="fas fa-money-bill-wave mr-2"></i>Mark Bill as Paid
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning mt-4">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        Current status: <strong><?= htmlspecialchars($test['status']) ?></strong>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>

