<?php
require_once '../functions.php';
checkRole('PATIENT');

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT patient_id, full_name, blood_type FROM core_patient WHERE user_id = ?");
$stmt->execute([$user_id]);
$patient = $stmt->fetch();

if (!$patient) {
    die("Patient record not found. Please contact administrator.");
}

$patient_id = $patient['patient_id'];

$stmt = $pdo->prepare("
    SELECT a.date_and_time, d.full_name as doctor_name, d.specialization
    FROM core_appointment a
    JOIN core_doctor d ON a.doctor_id = d.doctor_id
    WHERE a.patient_id = ? AND a.date_and_time >= NOW() AND a.status != 'Completed'
    ORDER BY a.date_and_time ASC LIMIT 3
");
$stmt->execute([$patient_id]);
$upcoming_appts = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT bill_date, total_amount, status, service_type_id
    FROM core_bill
    WHERE patient_id = ?
    ORDER BY bill_date DESC LIMIT 5
");
$stmt->execute([$patient_id]);
$bills = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT p.prescription_id, p.valid_until, m.name as medicine_name, pi.dosage, pi.instructions
    FROM core_prescription p
    JOIN core_prescriptionitem pi ON p.prescription_id = pi.prescription_id
    JOIN core_medicine m ON pi.medicine_id = m.medicine_id
    JOIN core_appointment a ON p.appointment_id = a.appointment_id
    WHERE a.patient_id = ?
    ORDER BY p.prescription_id DESC LIMIT 5
");
$stmt->execute([$patient_id]);
$prescriptions = $stmt->fetchAll();

include '../templates/header.php';
?>

<div class="px-4 sm:px-0 mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Hello, <?= htmlspecialchars($patient['full_name']) ?></h1>
        <p class="text-gray-600">Blood Type: <span class="font-bold text-red-600"><?= $patient['blood_type'] ?></span></p>
    </div>
    <a href="/patient/book_appointment.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
        <i class="fas fa-calendar-plus mr-2"></i>Book Appointment
    </a>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="bg-white shadow rounded-lg overflow-hidden border-t-4 border-blue-500">
        <div class="px-6 py-4 bg-white border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-medium text-gray-900">
                <i class="far fa-calendar-alt text-blue-500 mr-2"></i> Upcoming Visits
            </h3>
            <a href="/patient/appointments.php" class="text-sm text-blue-600 hover:text-blue-800">View All</a>
        </div>
        <ul class="divide-y divide-gray-200">
            <?php foreach ($upcoming_appts as $appt): ?>
            <li class="px-6 py-4 hover:bg-gray-50 transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-bold text-gray-900">Dr. <?= htmlspecialchars($appt['doctor_name']) ?></p>
                        <p class="text-xs text-gray-500"><?= htmlspecialchars($appt['specialization']) ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-blue-600 font-medium"><?= date('M d', strtotime($appt['date_and_time'])) ?></p>
                        <p class="text-xs text-gray-400"><?= date('h:i A', strtotime($appt['date_and_time'])) ?></p>
                    </div>
                </div>
            </li>
            <?php endforeach; ?>
            <?php if (empty($upcoming_appts)): ?>
            <li class="px-6 py-8 text-center text-gray-500">No upcoming appointments.</li>
            <?php endif; ?>
        </ul>
    </div>

    <div class="bg-white shadow rounded-lg overflow-hidden border-t-4 border-green-500">
        <div class="px-6 py-4 bg-white border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-medium text-gray-900">
                <i class="fas fa-file-invoice-dollar text-green-500 mr-2"></i> Billing History
            </h3>
            <a href="/patient/bills.php" class="text-sm text-green-600 hover:text-green-800">View All</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($bills as $bill): ?>
                    <tr>
                        <td class="px-6 py-3 text-sm text-gray-500"><?= formatDate($bill['bill_date']) ?></td>
                        <td class="px-6 py-3 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= getStatusBadgeClass($bill['status']) ?>">
                                <?= htmlspecialchars($bill['status']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-3 text-sm text-gray-900 text-right font-medium">
                            <?= formatCurrency($bill['total_amount']) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($bills)): ?>
                    <tr><td colspan="3" class="px-6 py-4 text-center text-gray-500">No bills found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white shadow rounded-lg overflow-hidden md:col-span-2 border-t-4 border-purple-500">
        <div class="px-6 py-4 bg-white border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">
                <i class="fas fa-pills text-purple-500 mr-2"></i> Recent Prescriptions
            </h3>
        </div>
        <div class="px-6 py-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($prescriptions as $rx): ?>
                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition bg-gray-50">
                    <div class="flex justify-between items-start mb-2">
                        <h4 class="text-md font-bold text-gray-900"><?= htmlspecialchars($rx['medicine_name']) ?></h4>
                        <span class="text-xs bg-gray-200 text-gray-600 px-2 py-0.5 rounded">Rx #<?= $rx['prescription_id'] ?></span>
                    </div>
                    <p class="text-sm text-gray-600 mb-1"><strong>Dosage:</strong> <?= htmlspecialchars($rx['dosage']) ?></p>
                    <p class="text-xs text-gray-500 mb-3 italic"><?= htmlspecialchars($rx['instructions']) ?></p>
                    <div class="mt-auto">
                        <span class="text-xs text-red-500">Valid: <?= formatDate($rx['valid_until']) ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($prescriptions)): ?>
                <p class="col-span-full text-center text-gray-500">No active prescriptions.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<?php include '../templates/footer.php'; ?>