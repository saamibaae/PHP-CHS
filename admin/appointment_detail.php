<?php
require_once __DIR__ . '/../db.php';
requireRole('ADMIN');

$appointment_id = $_GET['id'] ?? null;

if (!$appointment_id) {
    setFlash("Invalid appointment ID.", "error");
    header("Location: /admin/dashboard.php");
    exit;
}

$sql = "SELECT a.*, 
        p.full_name as patient_name, p.date_of_birth, p.gender, p.blood_type, p.national_id,
        d.full_name as doctor_name, d.specialization
        FROM core_appointment a
        INNER JOIN core_patient p ON a.patient_id = p.patient_id
        INNER JOIN core_doctor d ON a.doctor_id = d.doctor_id
        WHERE a.appointment_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$appointment_id]);
$appointment = $stmt->fetch();

if (!$appointment) {
    setFlash("Appointment not found.", "error");
    header("Location: /admin/dashboard.php");
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
        WHERE lt.appointment_id = ?
        ORDER BY lt.date_and_time DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$appointment_id]);
$lab_tests = $stmt->fetchAll();

include __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <h2><i class="fas fa-calendar-check mr-2"></i>Appointment Details</h2>
    <a href="/admin/admissions.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left mr-2"></i>Back
    </a>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h3>Patient Info</h3>
            </div>
            <div class="card-body">
                <p><strong>Name:</strong> <?= htmlspecialchars($appointment['patient_name']) ?></p>
                <p><strong>National ID:</strong> <?= htmlspecialchars($appointment['national_id']) ?></p>
                <p><strong>DOB:</strong> <?= htmlspecialchars($appointment['date_of_birth']) ?></p>
                <p><strong>Gender:</strong> <?= htmlspecialchars($appointment['gender']) ?></p>
                <p><strong>Blood Type:</strong> <?= htmlspecialchars($appointment['blood_type']) ?></p>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h3>Doctor Info</h3>
            </div>
            <div class="card-body">
                <p><strong>Name:</strong> Dr. <?= htmlspecialchars($appointment['doctor_name']) ?></p>
                <p><strong>Specialization:</strong> <?= htmlspecialchars($appointment['specialization']) ?></p>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Visit Info</h3>
            </div>
            <div class="card-body">
                <p><strong>Date:</strong> <?= date('M d, Y H:i', strtotime($appointment['date_and_time'])) ?></p>
                <p><strong>Status:</strong> <span class="badge badge-secondary"><?= htmlspecialchars($appointment['status']) ?></span></p>
                <p><strong>Type:</strong> <?= htmlspecialchars($appointment['visit_type']) ?></p>
                <hr>
                <p><strong>Reason:</strong><br><?= nl2br(htmlspecialchars($appointment['reason_for_visit'])) ?></p>
                <?php if ($appointment['symptoms']): ?>
                    <p><strong>Symptoms:</strong><br><?= nl2br(htmlspecialchars($appointment['symptoms'])) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h3>Diagnosis & Notes</h3>
            </div>
            <div class="card-body">
                <?php if ($appointment['diagnosis']): ?>
                    <div class="alert alert-info">
                        <strong>Diagnosis:</strong><br>
                        <?= nl2br(htmlspecialchars($appointment['diagnosis'])) ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No diagnosis recorded yet.</p>
                <?php endif; ?>

                <?php if ($appointment['follow_up_date']): ?>
                    <p><strong>Follow-up Required:</strong> <?= $appointment['follow_up_date'] ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h3>Prescriptions</h3>
            </div>
            <div class="card-body">
                <?php foreach ($prescriptions as $presc): ?>
                    <div class="prescription-block mb-3 border p-3">
                        <div class="d-flex justify-content-between">
                            <strong>Rx #<?= $presc['prescription_id'] ?> (Valid Until: <?= $presc['valid_until'] ?>)</strong>
                            <a href="/patient/prescription_pdf.php?id=<?= $presc['prescription_id'] ?>" class="btn btn-sm btn-success" target="_blank">
                                <i class="fas fa-file-pdf mr-1"></i>View PDF
                            </a>
                        </div>
                        <?php if ($presc['notes']): ?>
                            <p class="mb-2 mt-1"><em>Notes: <?= htmlspecialchars($presc['notes']) ?></em></p>
                        <?php endif; ?>
                        
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
                    <p class="text-muted text-center">No prescriptions found.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-vial mr-2"></i>Lab Tests</h3>
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
                                        <?= htmlspecialchars($lt['lab_name']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('M d, Y H:i', strtotime($lt['date_and_time'])) ?></td>
                                <td>
                                    <span class="badge badge-info"><?= htmlspecialchars($lt['status']) ?></span>
                                </td>
                                <td>
                                    <a href="/admin/lab_test_process.php?id=<?= $lt['test_id'] ?>" 
                                       class="btn btn-sm btn-primary" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-muted text-center">No lab tests ordered.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
