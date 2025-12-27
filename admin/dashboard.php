<?php
// admin/dashboard.php
require_once '../functions.php';
checkRole('ADMIN');

$hospital_id = $_SESSION['hospital_id'];

// 1. Capacity vs Load
$stmt = $pdo->prepare("SELECT capacity, name FROM core_hospital WHERE hospital_id = ?");
$stmt->execute([$hospital_id]);
$hospital_info = $stmt->fetch();

$stmt = $pdo->prepare("SELECT COUNT(DISTINCT patient_id) FROM core_appointment WHERE doctor_id IN (SELECT doctor_id FROM core_doctor WHERE hospital_id = ?)");
$stmt->execute([$hospital_id]);
$total_patients = $stmt->fetchColumn();

// 2. Active Departments
$stmt = $pdo->prepare("
    SELECT d.dept_name, d.floor, 
    (SELECT COUNT(*) FROM core_doctor WHERE dept_id = d.dept_id) as doctor_count 
    FROM core_department d 
    WHERE d.hospital_id = ?
");
$stmt->execute([$hospital_id]);
$departments = $stmt->fetchAll();

// 3. Recent Billing Summaries
$stmt = $pdo->prepare("
    SELECT b.bill_id, b.total_amount, b.status, b.bill_date, p.full_name as patient_name
    FROM core_bill b
    JOIN core_patient p ON b.patient_id = p.patient_id
    WHERE b.patient_id IN (
        SELECT patient_id FROM core_appointment a 
        JOIN core_doctor doc ON a.doctor_id = doc.doctor_id 
        WHERE doc.hospital_id = ?
    )
    ORDER BY b.bill_date DESC LIMIT 5
");
$stmt->execute([$hospital_id]);
$recent_bills = $stmt->fetchAll();

include '../templates/header.php';
?>

<div class="px-4 sm:px-0 mb-6">
    <h1 class="text-3xl font-bold text-gray-900"><?= htmlspecialchars($hospital_info['name']) ?></h1>
    <p class="mt-1 text-sm text-gray-600">Hospital Administration Dashboard</p>
</div>

<!-- Grid Layout -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
    <!-- Capacity Card -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                    <i class="fas fa-users text-white text-2xl"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Patient Load</dt>
                        <dd>
                            <div class="text-lg font-medium text-gray-900"><?= $total_patients ?> / <?= $hospital_info['capacity'] ?></div>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 px-5 py-3">
            <div class="text-sm">
                <div class="w-full bg-gray-200 rounded-full h-2.5">
                    <div class="bg-blue-600 h-2.5 rounded-full" style="width: <?= min(100, ($total_patients / $hospital_info['capacity']) * 100) ?>%"></div>
                </div>
                <span class="text-gray-500 mt-1 block"><?= round(($total_patients / $hospital_info['capacity']) * 100) ?>% Capacity Used</span>
            </div>
        </div>
    </div>

    <!-- Active Depts Card -->
    <div class="bg-white overflow-hidden shadow rounded-lg col-span-1 md:col-span-2">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Active Departments</h3>
        </div>
        <div class="bg-gray-50 px-4 py-4 sm:px-6">
            <ul class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <?php foreach ($departments as $dept): ?>
                <li class="col-span-1 bg-white rounded-lg shadow divide-y divide-gray-200">
                    <div class="w-full flex items-center justify-between p-6 space-x-6">
                        <div class="flex-1 truncate">
                            <div class="flex items-center space-x-3">
                                <h3 class="text-gray-900 text-sm font-medium truncate"><?= htmlspecialchars($dept['dept_name']) ?></h3>
                                <span class="flex-shrink-0 inline-block px-2 py-0.5 text-green-800 text-xs font-medium bg-green-100 rounded-full">Active</span>
                            </div>
                            <p class="mt-1 text-gray-500 text-sm truncate"><?= htmlspecialchars($dept['floor']) ?></p>
                        </div>
                        <div class="text-right">
                            <span class="text-2xl font-bold text-gray-900"><?= $dept['doctor_count'] ?></span>
                            <span class="text-gray-500 text-xs block">Doctors</span>
                        </div>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<!-- Recent Billing -->
<div class="bg-white shadow overflow-hidden sm:rounded-lg">
    <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
        <div>
            <h3 class="text-lg leading-6 font-medium text-gray-900">Recent Billing Summaries</h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">Financial overview of recent transactions.</p>
        </div>
        <button class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded text-sm">
            Generate Report
        </button>
    </div>
    <div class="border-t border-gray-200">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bill ID</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($recent_bills as $bill): ?>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#<?= $bill['bill_id'] ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($bill['patient_name']) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= formatDate($bill['bill_date']) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-semibold"><?= formatCurrency($bill['total_amount']) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= getStatusBadgeClass($bill['status']) ?>">
                            <?= htmlspecialchars($bill['status']) ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../templates/footer.php'; ?>