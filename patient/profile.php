<?php
require_once __DIR__ . '/../db.php';
requireRole('PATIENT');

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM core_patient WHERE user_id = ?");
$stmt->execute([$user_id]);
$patient = $stmt->fetch();

if (!$patient) {
    die("Patient record not found. Please contact administrator.");
}

$patient_id = $patient['patient_id'];
$stmt = $pdo->prepare("SELECT * FROM core_patientemergencycontact WHERE patient_id = ?");
$stmt->execute([$patient_id]);
$contacts = $stmt->fetchAll();

include __DIR__ . '/../templates/header.php';
?>

<div class="px-4 sm:px-0 mb-6 flex justify-between items-center">
    <h1 class="text-3xl font-bold text-gray-900">My Profile</h1>
    <a href="/patient/edit_profile.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
        <i class="fas fa-edit mr-2"></i>Edit Profile
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <div class="bg-white shadow rounded-lg overflow-hidden border-t-4 border-blue-500">
            <div class="px-6 py-4 bg-white border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">
                    <i class="fas fa-user text-blue-500 mr-2"></i> Personal Information
                </h3>
            </div>
            <div class="px-6 py-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Full Name</p>
                            <p class="text-base text-gray-900"><?= htmlspecialchars($patient['full_name']) ?></p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">National ID</p>
                            <p class="text-base text-gray-900"><?= htmlspecialchars($patient['national_id']) ?></p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Date of Birth</p>
                            <p class="text-base text-gray-900"><?= htmlspecialchars($patient['date_of_birth']) ?></p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Gender</p>
                            <p class="text-base text-gray-900"><?= htmlspecialchars($patient['gender']) ?></p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Blood Type</p>
                            <p class="text-base font-bold text-red-600"><?= htmlspecialchars($patient['blood_type']) ?></p>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Phone</p>
                            <p class="text-base text-gray-900"><?= htmlspecialchars($patient['phone']) ?></p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Email</p>
                            <p class="text-base text-gray-900"><?= htmlspecialchars($patient['email']) ?></p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Address</p>
                            <p class="text-base text-gray-900"><?= nl2br(htmlspecialchars($patient['address'])) ?></p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Occupation</p>
                            <p class="text-base text-gray-900"><?= htmlspecialchars($patient['occupation'] ?? 'N/A') ?></p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Marital Status</p>
                            <p class="text-base text-gray-900"><?= htmlspecialchars($patient['marital_status']) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="lg:col-span-1">
        <div class="bg-white shadow rounded-lg overflow-hidden border-t-4 border-green-500">
            <div class="px-6 py-4 bg-white border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">
                    <i class="fas fa-phone-alt text-green-500 mr-2"></i> Emergency Contacts
                </h3>
            </div>
            <div class="px-6 py-4">
                <?php if (empty($contacts)): ?>
                    <p class="text-sm text-gray-500 text-center py-4">No emergency contacts on file.</p>
                <?php else: ?>
                    <ul class="divide-y divide-gray-200">
                        <?php foreach ($contacts as $c): ?>
                        <li class="py-4">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <p class="text-sm font-bold text-gray-900"><?= htmlspecialchars($c['contact_name']) ?></p>
                                    <p class="text-sm text-gray-600 mt-1"><?= htmlspecialchars($c['contact_phone']) ?></p>
                                    <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars($c['relationship']) ?></p>
                                </div>
                                <?php if ($c['is_primary']): ?>
                                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        Primary
                                    </span>
                                <?php endif; ?>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
