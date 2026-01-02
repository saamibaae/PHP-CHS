<?php
require_once '../functions.php';
checkRole('DOCTOR');

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT doctor_id, full_name, specialization FROM core_doctor WHERE user_id = ?");
$stmt->execute([$user_id]);
$doctor = $stmt->fetch();

if (!$doctor) {
    die("Doctor record not found. Please contact administrator.");
}

$doctor_id = $doctor['doctor_id'];

$today = date('Y-m-d');
$stmt = $pdo->prepare("
    SELECT a.appointment_id, a.date_and_time, a.symptoms, a.status, p.full_name, p.patient_id
    FROM core_appointment a
    JOIN core_patient p ON a.patient_id = p.patient_id
    WHERE a.doctor_id = ? AND DATE(a.date_and_time) = ?
    ORDER BY a.date_and_time ASC
");
$stmt->execute([$doctor_id, $today]);
$schedule = $stmt->fetchAll();

include '../templates/header.php';
?>

<div class="flex justify-between items-center mb-6 px-4 sm:px-0">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Dr. <?= htmlspecialchars($doctor['full_name']) ?></h1>
        <p class="text-sm text-gray-600"><?= htmlspecialchars($doctor['specialization']) ?> Specialist</p>
    </div>
    <div class="flex space-x-3">
        <a href="/doctor/lab_test_order.php" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 font-bold py-2 px-4 rounded inline-flex items-center">
            <i class="fas fa-vial mr-2"></i> Order Lab Test
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-blue-50">
            <h2 class="text-lg font-medium text-blue-900">Today's Schedule (<?= date('M d') ?>)</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Symptoms</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($schedule as $appt): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <?= date('h:i A', strtotime($appt['date_and_time'])) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            <?= htmlspecialchars($appt['full_name']) ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                            <?= htmlspecialchars($appt['symptoms']) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="/doctor/appointment_detail.php?id=<?= $appt['appointment_id'] ?>" class="text-blue-600 hover:text-blue-900 mr-3">Consult</a>
                            <a href="/doctor/prescription_form.php?appointment_id=<?= $appt['appointment_id'] ?>" class="text-green-600 hover:text-green-900">Rx</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($schedule)): ?>
                    <tr>
                        <td colspan="4" class="px-6 py-10 text-center text-gray-500 italic">No appointments scheduled for today.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="space-y-6">
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Issue</h3>
            <p class="text-sm text-gray-500 mb-4">Start a new prescription or lab order directly.</p>
            <div class="space-y-3">
                 <a href="/doctor/appointments.php" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none">
                    View Full Schedule
                </a>
            </div>
        </div>
        
        <div class="bg-green-50 shadow rounded-lg p-6 border border-green-200">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle text-green-400 text-3xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-medium text-green-800">Status</h3>
                    <p class="text-sm text-green-600">You are currently active.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?>