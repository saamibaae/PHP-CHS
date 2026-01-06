<?php
require_once __DIR__ . '/../db.php';
requireRole('PATIENT');

$user_id = $_SESSION['user_id'];
$test_id = $_GET['id'] ?? null;

if (!$test_id) {
    die("Invalid test ID.");
}

$stmt = $pdo->prepare("SELECT patient_id FROM core_patient WHERE user_id = ?");
$stmt->execute([$user_id]);
$patient_id = $stmt->fetchColumn();

if (!$patient_id) {
    die("Patient record not found.");
}

$bill_id_exists = checkColumnExists('core_labtest', 'bill_id');

if ($bill_id_exists) {
    $sql = "SELECT lt.*, p.full_name as patient_name, p.national_id, p.date_of_birth, p.gender, p.blood_type, p.phone, p.address,
            d.full_name as doctor_name, d.specialization, d.phone as doctor_phone,
            b.bill_id, b.total_amount, b.bill_date, b.status as bill_status, b.due_date,
            h.name as hospital_name, h.address as hospital_address, h.phone as hospital_phone
            FROM core_labtest lt
            INNER JOIN core_patient p ON lt.patient_id = p.patient_id
            INNER JOIN core_doctor d ON lt.ordered_by_id = d.doctor_id
            INNER JOIN core_hospital h ON d.hospital_id = h.hospital_id
            LEFT JOIN core_bill b ON lt.bill_id = b.bill_id
            WHERE lt.test_id = ? AND lt.patient_id = ? AND lt.status = 'Completed'";
} else {
    $sql = "SELECT lt.*, p.full_name as patient_name, p.national_id, p.date_of_birth, p.gender, p.blood_type, p.phone, p.address,
            d.full_name as doctor_name, d.specialization, d.phone as doctor_phone,
            NULL as bill_id, NULL as total_amount, NULL as bill_date, NULL as bill_status, NULL as due_date,
            h.name as hospital_name, h.address as hospital_address, h.phone as hospital_phone
            FROM core_labtest lt
            INNER JOIN core_patient p ON lt.patient_id = p.patient_id
            INNER JOIN core_doctor d ON lt.ordered_by_id = d.doctor_id
            INNER JOIN core_hospital h ON d.hospital_id = h.hospital_id
            WHERE lt.test_id = ? AND lt.patient_id = ? AND lt.status = 'Completed'";
}
$stmt = $pdo->prepare($sql);
$stmt->execute([$test_id, $patient_id]);
$test = $stmt->fetch();

if (!$test) {
    die("Lab test not found or not completed.");
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Test Report - <?= htmlspecialchars($test['patient_name']) ?></title>
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
            border-bottom: 3px solid #2563eb;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #2563eb;
            margin: 0;
        }
        .section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }
        .section-title {
            background-color: #2563eb;
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
            padding: 8px;
            text-align: left;
        }
        table th {
            background-color: #f3f4f6;
            font-weight: bold;
        }
        .result-box {
            background-color: #f0fdf4;
            border: 2px solid #10b981;
            padding: 15px;
            margin-top: 10px;
            white-space: pre-wrap;
            font-family: 'Courier New', monospace;
        }
        .bill-box {
            background-color: #fef3c7;
            border: 2px solid #f59e0b;
            padding: 15px;
            margin-top: 10px;
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
            background-color: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 5px;
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" class="btn">Print / Save as PDF</button>
        <a href="/patient/lab_test_detail.php?id=<?= $test_id ?>" class="btn" style="background-color: #6b7280;">Back</a>
    </div>

    <div class="header">
        <h1><?= htmlspecialchars($test['hospital_name']) ?></h1>
        <p><?= htmlspecialchars($test['hospital_address']) ?> | Phone: <?= htmlspecialchars($test['hospital_phone']) ?></p>
        <h2>LABORATORY TEST REPORT</h2>
    </div>

    <div class="section">
        <div class="section-title">Patient Information</div>
        <div class="info-row">
            <div class="info-label">Patient Name:</div>
            <div class="info-value"><?= htmlspecialchars($test['patient_name']) ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">National ID:</div>
            <div class="info-value"><?= htmlspecialchars($test['national_id']) ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Date of Birth:</div>
            <div class="info-value"><?= htmlspecialchars($test['date_of_birth']) ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Gender:</div>
            <div class="info-value"><?= htmlspecialchars($test['gender']) ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Blood Type:</div>
            <div class="info-value"><strong><?= htmlspecialchars($test['blood_type']) ?></strong></div>
        </div>
        <div class="info-row">
            <div class="info-label">Phone:</div>
            <div class="info-value"><?= htmlspecialchars($test['phone']) ?></div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Test Information</div>
        <div class="info-row">
            <div class="info-label">Test ID:</div>
            <div class="info-value"><strong>#<?= $test['test_id'] ?></strong></div>
        </div>
        <div class="info-row">
            <div class="info-label">Ordered Tests:</div>
            <div class="info-value"><?= nl2br(htmlspecialchars($test['test_type'])) ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Ordered By:</div>
            <div class="info-value">Dr. <?= htmlspecialchars($test['doctor_name']) ?> (<?= htmlspecialchars($test['specialization']) ?>)</div>
        </div>
        <div class="info-row">
            <div class="info-label">Order Date:</div>
            <div class="info-value"><?= date('F d, Y H:i A', strtotime($test['date_and_time'])) ?></div>
        </div>
        <?php if ($test['remarks']): ?>
        <div class="info-row">
            <div class="info-label">Remarks:</div>
            <div class="info-value"><?= nl2br(htmlspecialchars($test['remarks'])) ?></div>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($test['result']): ?>
    <div class="section">
        <div class="section-title">Test Results</div>
        <div class="result-box"><?= htmlspecialchars($test['result']) ?></div>
    </div>
    <?php endif; ?>

    <?php if ($test['bill_id']): ?>
    <div class="section">
        <div class="section-title">Bill Information</div>
        <div class="bill-box">
            <table>
                <tr>
                    <th>Bill ID</th>
                    <td>#<?= $test['bill_id'] ?></td>
                </tr>
                <tr>
                    <th>Bill Date</th>
                    <td><?= date('F d, Y', strtotime($test['bill_date'])) ?></td>
                </tr>
                <tr>
                    <th>Due Date</th>
                    <td><?= date('F d, Y', strtotime($test['due_date'])) ?></td>
                </tr>
                <tr>
                    <th>Test Cost</th>
                    <td><strong>৳<?= number_format($test['total_amount'], 2) ?></strong></td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td><strong><?= htmlspecialchars($test['bill_status']) ?></strong></td>
                </tr>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <div class="footer">
        <p>This is a computer-generated report. No signature required.</p>
        <p>Generated on: <?= date('F d, Y H:i A') ?></p>
        <p>For any queries, please contact: <?= htmlspecialchars($test['hospital_phone']) ?></p>
    </div>
</body>
</html>

