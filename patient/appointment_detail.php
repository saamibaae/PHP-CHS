<?php
require_once __DIR__ . '/../db.php';
requireRole('PATIENT');

$user_id = $_SESSION['user_id'];
$appointment_id = $_GET['id'] ?? null;

if (!$appointment_id) die("Invalid request");

$stmt = $pdo->prepare("SELECT patient_id FROM core_patient WHERE user_id = ?");
$stmt->execute([$user_id]);
$patient_id = $stmt->fetchColumn();

// Get Appointment
$sql = "SELECT a.*, d.full_name as doctor_name, d.specialization, 
               dept.dept_name, h.name as hospital_name
        FROM core_appointment a
        INNER JOIN core_doctor d ON a.doctor_id = d.doctor_id
        LEFT JOIN core_department dept ON d.dept_id = dept.dept_id
        INNER JOIN core_hospital h ON d.hospital_id = h.hospital_id
        WHERE a.appointment_id = ? AND a.patient_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$appointment_id, $patient_id]);
$appointment = $stmt->fetch();

if (!$appointment) die("Appointment not found.");

// Get Prescriptions
$sql = "SELECT * FROM core_prescription WHERE appointment_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$appointment_id]);
$prescriptions = $stmt->fetchAll();

// Get Items for Prescriptions
foreach ($prescriptions as &$presc) {
    $sql = "SELECT pi.*, m.name as medicine_name 
            FROM core_prescriptionitem pi
            INNER JOIN core_medicine m ON pi.medicine_id = m.medicine_id
            WHERE pi.prescription_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$presc['prescription_id']]);
    $presc['items'] = $stmt->fetchAll();
}

include __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <h2>Appointment Details</h2>
    <a href="/patient/appointments.php" class="btn btn-secondary">Back</a>
</div>

<div class="row">
    <div class="col-md-5">
        <div class="card mb-4">
            <div class="card-header">
                <h3>Doctor & Hospital</h3>
            </div>
            <div class="card-body">
                <h4>Dr. <?= htmlspecialchars($appointment['doctor_name']) ?></h4>
                <p class="text-muted"><?= htmlspecialchars($appointment['specialization']) ?></p>
                <hr>
                <p><strong>Hospital:</strong> <?= htmlspecialchars($appointment['hospital_name']) ?></p>
                <p><strong>Department:</strong> <?= htmlspecialchars($appointment['dept_name'] ?? 'General') ?></p>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Visit Details</h3>
            </div>
            <div class="card-body">
                <p><strong>Date:</strong> <?= date('M d, Y h:i A', strtotime($appointment['date_and_time'])) ?></p>
                <p><strong>Status:</strong> <span class="badge badge-<?= strtolower($appointment['status']) ?>"><?= $appointment['status'] ?></span></p>
                <p><strong>Type:</strong> <?= htmlspecialchars($appointment['visit_type']) ?></p>
                <hr>
                <p><strong>Reason:</strong><br><?= nl2br(htmlspecialchars($appointment['reason_for_visit'])) ?></p>
                
                <?php if ($appointment['diagnosis']): ?>
                    <div class="alert alert-info mt-3">
                        <strong>Diagnosis:</strong><br>
                        <?= nl2br(htmlspecialchars($appointment['diagnosis'])) ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($appointment['follow_up_date']): ?>
                    <p class="mt-2"><strong>Follow-up Required:</strong> <?= $appointment['follow_up_date'] ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card">
            <div class="card-header">
                <h3>Prescriptions</h3>
            </div>
            <div class="card-body">
                <?php foreach ($prescriptions as $presc): ?>
                    <div class="prescription-block mb-3 border p-3">
                        <div class="d-flex justify-content-between">
                            <strong>Rx #<?= $presc['prescription_id'] ?></strong>
                            <span>Valid Until: <?= $presc['valid_until'] ?></span>
                        </div>
                        
                        <table class="table table-sm mt-2">
                            <thead>
                                <tr>
                                    <th>Medicine</th>
                                    <th>Dosage</th>
                                    <th>Freq</th>
                                    <th>Instr</th>
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
                        <?php if ($presc['notes']): ?>
                            <p class="text-muted small">Note: <?= htmlspecialchars($presc['notes']) ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($prescriptions)): ?>
                    <p class="text-center text-muted">No prescriptions found for this appointment.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
