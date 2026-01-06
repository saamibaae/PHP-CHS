<?php
require_once __DIR__ . '/../db.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? '';
$prescription_id = $_GET['id'] ?? null;

if (!$prescription_id) {
    die("Invalid prescription ID.");
}

$patient_check_sql = "";
$params = [$prescription_id];

if ($role === 'PATIENT') {
    $stmt = $pdo->prepare("SELECT patient_id FROM core_patient WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $patient_id = $stmt->fetchColumn();

    if (!$patient_id) {
        die("Patient record not found.");
    }
    
    $patient_check_sql = "AND a.patient_id = ?";
    $params[] = $patient_id;
} elseif ($role !== 'DOCTOR' && $role !== 'ADMIN') {
    die("Access Denied: You do not have permission to view this page.");
}

$sql = "SELECT p.*, a.date_and_time, a.diagnosis, a.appointment_id,
        pt.full_name as patient_name, pt.national_id, pt.date_of_birth, pt.gender, pt.blood_type, pt.phone, pt.address,
        d.full_name as doctor_name, d.specialization, d.phone as doctor_phone,
        h.name as hospital_name, h.address as hospital_address, h.phone as hospital_phone
        FROM core_prescription p
        INNER JOIN core_appointment a ON p.appointment_id = a.appointment_id
        INNER JOIN core_patient pt ON a.patient_id = pt.patient_id
        INNER JOIN core_doctor d ON a.doctor_id = d.doctor_id
        INNER JOIN core_hospital h ON d.hospital_id = h.hospital_id
        WHERE p.prescription_id = ? $patient_check_sql";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$prescription = $stmt->fetch();

if (!$prescription) {
    die("Prescription not found or access denied.");
}

$sql = "SELECT pi.*, m.name as medicine_name, m.type as medicine_type
        FROM core_prescriptionitem pi
        INNER JOIN core_medicine m ON pi.medicine_id = m.medicine_id
        WHERE pi.prescription_id = ?
        ORDER BY pi.item_id";
$stmt = $pdo->prepare($sql);
$stmt->execute([$prescription_id]);
$items = $stmt->fetchAll();

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescription - <?= htmlspecialchars($prescription['patient_name']) ?></title>
    <style>
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #10b981;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #10b981;
            margin: 0;
        }
        .section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }
        .section-title {
            background-color: #10b981;
            color: white;
            padding: 10px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .info-row {
            display: flex;
            margin-bottom: 8px;
        }
        .info-label {
            font-weight: bold;
            width: 150px;
        }
        .info-value {
            flex: 1;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        table th {
            background-color: #f3f4f6;
            font-weight: bold;
        }
        .medicine-box {
            background-color: #f0fdf4;
            border-left: 4px solid #10b981;
            padding: 15px;
            margin-bottom: 15px;
        }
        .medicine-name {
            font-size: 18px;
            font-weight: bold;
            color: #10b981;
            margin-bottom: 10px;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
        .no-print {
            text-align: center;
            margin: 20px 0;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #10b981;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 5px;
        }
        .validity-box {
            background-color: #fef3c7;
            border: 2px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" class="btn">Print / Save as PDF</button>
        <a href="/patient/appointment_detail.php?id=<?= $prescription['appointment_id'] ?>" class="btn" style="background-color: #6b7280;">Back</a>
    </div>

    <div class="header">
        <h1><?= htmlspecialchars($prescription['hospital_name']) ?></h1>
        <p><?= htmlspecialchars($prescription['hospital_address']) ?> | Phone: <?= htmlspecialchars($prescription['hospital_phone']) ?></p>
        <h2>PRESCRIPTION</h2>
    </div>

    <div class="section">
        <div class="section-title">Patient Information</div>
        <div class="info-row">
            <div class="info-label">Patient Name:</div>
            <div class="info-value"><?= htmlspecialchars($prescription['patient_name']) ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">National ID:</div>
            <div class="info-value"><?= htmlspecialchars($prescription['national_id']) ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Date of Birth:</div>
            <div class="info-value"><?= htmlspecialchars($prescription['date_of_birth']) ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Gender:</div>
            <div class="info-value"><?= htmlspecialchars($prescription['gender']) ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Blood Type:</div>
            <div class="info-value"><strong><?= htmlspecialchars($prescription['blood_type']) ?></strong></div>
        </div>
        <div class="info-row">
            <div class="info-label">Phone:</div>
            <div class="info-value"><?= htmlspecialchars($prescription['phone']) ?></div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Appointment Information</div>
        <div class="info-row">
            <div class="info-label">Appointment Date:</div>
            <div class="info-value"><?= date('F d, Y H:i A', strtotime($prescription['date_and_time'])) ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Prescribing Doctor:</div>
            <div class="info-value">Dr. <?= htmlspecialchars($prescription['doctor_name']) ?> (<?= htmlspecialchars($prescription['specialization']) ?>)</div>
        </div>
        <?php if ($prescription['diagnosis']): ?>
        <div class="info-row">
            <div class="info-label">Diagnosis:</div>
            <div class="info-value"><?= nl2br(htmlspecialchars($prescription['diagnosis'])) ?></div>
        </div>
        <?php endif; ?>
    </div>

    <div class="validity-box">
        <strong>Prescription Valid Until: <?= date('F d, Y', strtotime($prescription['valid_until'])) ?></strong>
        <?php if ($prescription['refill_count'] > 0): ?>
            <br>Refills Remaining: <?= $prescription['refill_count'] ?>
        <?php endif; ?>
    </div>

    <div class="section">
        <div class="section-title">Prescribed Medicines</div>
        <?php if (!empty($items)): ?>
            <?php foreach ($items as $item): ?>
            <div class="medicine-box">
                <div class="medicine-name"><?= htmlspecialchars($item['medicine_name']) ?></div>
                <table>
                    <tr>
                        <th width="30%">Dosage</th>
                        <td><?= htmlspecialchars($item['dosage']) ?></td>
                    </tr>
                    <tr>
                        <th>Frequency</th>
                        <td><?= htmlspecialchars($item['frequency']) ?></td>
                    </tr>
                    <tr>
                        <th>Duration</th>
                        <td><?= htmlspecialchars($item['duration']) ?></td>
                    </tr>
                    <tr>
                        <th>Quantity</th>
                        <td><?= htmlspecialchars($item['quantity']) ?></td>
                    </tr>
                    <tr>
                        <th>Timing</th>
                        <td><?= htmlspecialchars($item['before_after_meal']) ?></td>
                    </tr>
                    <?php if ($item['instructions']): ?>
                    <tr>
                        <th>Instructions</th>
                        <td><?= nl2br(htmlspecialchars($item['instructions'])) ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No medicines prescribed.</p>
        <?php endif; ?>
    </div>

    <?php if ($prescription['notes']): ?>
    <div class="section">
        <div class="section-title">Doctor's Notes</div>
        <p><?= nl2br(htmlspecialchars($prescription['notes'])) ?></p>
    </div>
    <?php endif; ?>

    <div class="footer">
        <p><strong>Dr. <?= htmlspecialchars($prescription['doctor_name']) ?></strong></p>
        <p><?= htmlspecialchars($prescription['specialization']) ?> | Phone: <?= htmlspecialchars($prescription['doctor_phone']) ?></p>
        <p><?= htmlspecialchars($prescription['hospital_name']) ?></p>
        <p>Generated on: <?= date('F d, Y H:i A') ?></p>
    </div>
</body>
</html>

