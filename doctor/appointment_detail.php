<?php
require_once __DIR__ . '/../db.php';
requireRole('DOCTOR');

$user_id = $_SESSION['user_id'];
$appointment_id = $_GET['id'] ?? null;

if (!$appointment_id) die("Invalid ID");

$stmt = $pdo->prepare("SELECT doctor_id FROM core_doctor WHERE user_id = ?");
$stmt->execute([$user_id]);
$doctor_id = $stmt->fetchColumn();

if (!$doctor_id) {
    die("Doctor record not found. Please contact administrator.");
}

$sql = "SELECT a.*, p.full_name as patient_name, p.date_of_birth, p.gender, p.blood_type
        FROM core_appointment a
        INNER JOIN core_patient p ON a.patient_id = p.patient_id
        WHERE a.appointment_id = ? AND a.doctor_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$appointment_id, $doctor_id]);
$appointment = $stmt->fetch();

if (!$appointment) die("Appointment not found.");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'];
    $diagnosis = $_POST['diagnosis'];
    $follow_up_date = $_POST['follow_up_date'] ?: null;
    
    $sql = "UPDATE core_appointment SET status = ?, diagnosis = ?, follow_up_date = ? WHERE appointment_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$status, $diagnosis, $follow_up_date, $appointment_id]);
    
    setFlash("Appointment updated.");
    header("Location: /doctor/appointment_detail.php?id=" . $appointment_id);
    exit;
}

$sql = "SELECT * FROM core_prescription WHERE appointment_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$appointment_id]);
$prescriptions = $stmt->fetchAll();

foreach ($prescriptions as &$presc) {
    $sql = "SELECT pi.*, m.name as medicine_name 
            FROM core_prescriptionitem pi
            INNER JOIN core_medicine m ON pi.medicine_id = m.medicine_id
            WHERE pi.prescription_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$presc['prescription_id']]);
    $presc['items'] = $stmt->fetchAll();
}

$sql = "SELECT lt.*, l.lab_name, l.location as lab_location
        FROM core_labtest lt
        LEFT JOIN core_lab l ON lt.lab_id = l.lab_id
        WHERE lt.patient_id = ? AND lt.ordered_by_id = ? 
        AND (lt.appointment_id = ? OR lt.appointment_id IS NULL)
        ORDER BY lt.date_and_time DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$appointment['patient_id'], $doctor_id, $appointment_id]);
$lab_tests = $stmt->fetchAll();

include __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <h2>Appointment Details</h2>
    <a href="/doctor/dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h3>Patient Info</h3>
            </div>
            <div class="card-body">
                <p><strong>Name:</strong> <?= htmlspecialchars($appointment['patient_name']) ?></p>
                <p><strong>DOB:</strong> <?= htmlspecialchars($appointment['date_of_birth']) ?></p>
                <p><strong>Gender:</strong> <?= htmlspecialchars($appointment['gender']) ?></p>
                <p><strong>Blood Type:</strong> <?= htmlspecialchars($appointment['blood_type']) ?></p>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3>Visit Info</h3>
            </div>
            <div class="card-body">
                <p><strong>Date:</strong> <?= date('M d, Y H:i', strtotime($appointment['date_and_time'])) ?></p>
                <p><strong>Type:</strong> <?= htmlspecialchars($appointment['visit_type']) ?></p>
                <p><strong>Reason:</strong><br><?= nl2br(htmlspecialchars($appointment['reason_for_visit'])) ?></p>
                <p><strong>Symptoms:</strong><br><?= nl2br(htmlspecialchars($appointment['symptoms'])) ?></p>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h3>Diagnosis & Status</h3>
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <?php foreach (['Scheduled', 'Completed', 'Cancelled', 'No-Show'] as $s): ?>
                                    <option value="<?= $s ?>" <?= $appointment['status'] == $s ? 'selected' : '' ?>><?= $s ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Follow-up Date</label>
                            <input type="date" name="follow_up_date" class="form-control" value="<?= htmlspecialchars($appointment['follow_up_date'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Diagnosis</label>
                        <textarea name="diagnosis" class="form-control" rows="3"><?= htmlspecialchars($appointment['diagnosis'] ?? '') ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Update Appointment</button>
                    <?php if ($appointment['status'] == 'Completed'): ?>
                        <a href="/doctor/generate_bill.php?appointment_id=<?= $appointment_id ?>" class="btn btn-success ml-2">
                            <i class="fas fa-file-invoice-dollar mr-2"></i>Generate Bill
                        </a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3>Prescriptions</h3>
                <div>
                    <a href="/doctor/lab_test_order.php?appointment_id=<?= $appointment_id ?>" class="btn btn-info btn-sm mr-2">
                        <i class="fas fa-vial mr-1"></i>Order Lab Test
                    </a>
                    <a href="/doctor/prescription_form.php?appointment_id=<?= $appointment_id ?>" class="btn btn-success btn-sm">Add Prescription</a>
                </div>
            </div>
            <div class="card-body">
                <?php foreach ($prescriptions as $presc): ?>
                    <div class="prescription-block mb-3 border p-3">
                        <div class="d-flex justify-content-between">
                            <strong>Valid Until: <?= $presc['valid_until'] ?></strong>
                            <div>
                                <a href="/patient/prescription_pdf.php?id=<?= $presc['prescription_id'] ?>" class="btn btn-sm btn-success mr-2" target="_blank">
                                    <i class="fas fa-file-pdf mr-1"></i>PDF
                                </a>
                                <a href="/doctor/add_prescription_items.php?prescription_id=<?= $presc['prescription_id'] ?>" class="btn btn-sm btn-outline-primary">Add Items</a>
                            </div>
                        </div>
                        <p class="mb-2"><em>Notes: <?= htmlspecialchars($presc['notes']) ?></em></p>
                        
                        <table class="table table-sm mt-2">
                            <thead>
                                <tr>
                                    <th>Medicine</th>
                                    <th>Dosage</th>
                                    <th>Freq</th>
                                    <th>Instruction</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($presc['items'] as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['medicine_name']) ?></td>
                                    <td><?= htmlspecialchars($item['dosage']) ?></td>
                                    <td><?= htmlspecialchars($item['frequency']) ?></td>
                                    <td><?= htmlspecialchars($item['before_after_meal']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($prescriptions)): ?>
                    <p class="text-muted text-center">No prescriptions yet.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3><i class="fas fa-vial mr-2"></i>Lab Tests</h3>
                <a href="/doctor/lab_test_order.php?appointment_id=<?= $appointment_id ?>" class="btn btn-success btn-sm">
                    <i class="fas fa-plus mr-2"></i>Order Lab Test
                </a>
            </div>
            <div class="card-body">
                <?php if (!empty($lab_tests)): ?>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Test Type</th>
                                <th>Lab</th>
                                <th>Ordered Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lab_tests as $lt): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($lt['test_type']) ?></strong></td>
                                <td>
                                    <?php if ($lt['lab_name']): ?>
                                        <?= htmlspecialchars($lt['lab_name']) ?><br>
                                        <small class="text-muted"><?= htmlspecialchars($lt['lab_location']) ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">To be assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('M d, Y H:i', strtotime($lt['date_and_time'])) ?></td>
                                <td>
                                    <?php
                                    $status_class = [
                                        'Ordered' => 'badge-warning',
                                        'In Progress' => 'badge-info',
                                        'Completed' => 'badge-success',
                                        'Cancelled' => 'badge-danger'
                                    ];
                                    $badge_class = $status_class[$lt['status']] ?? 'badge-primary';
                                    ?>
                                    <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($lt['status']) ?></span>
                                </td>
                                <td>
                                    <a href="/doctor/lab_test_detail.php?id=<?= $lt['test_id'] ?>" 
                                       class="btn btn-sm btn-primary" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-muted text-center">
                        <i class="fas fa-vial text-gray-400 mb-2"></i><br>
                        No lab tests ordered for this patient yet.
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
