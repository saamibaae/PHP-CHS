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

$error = null;
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $occupation = trim($_POST['occupation'] ?? '');
    $marital_status = trim($_POST['marital_status'] ?? '');

    if (empty($full_name)) {
        $error = "Full name is required.";
    } elseif (empty($phone)) {
        $error = "Phone number is required.";
    } elseif (empty($email)) {
        $error = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (empty($address)) {
        $error = "Address is required.";
    } elseif (empty($marital_status)) {
        $error = "Marital status is required.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM core_customuser WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            $error = "Email is already registered to another account.";
        }
    }

    if (!$error) {
        try {
            $pdo->beginTransaction();

            $sql = "UPDATE core_patient SET 
                    full_name = ?, phone = ?, email = ?, address = ?, 
                    occupation = ?, marital_status = ?
                    WHERE user_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $full_name, $phone, $email, $address, 
                $occupation ?: null, $marital_status, $user_id
            ]);

            $name_parts = explode(' ', $full_name, 2);
            $first_name = $name_parts[0];
            $last_name = $name_parts[1] ?? '';
            
            $sql_user = "UPDATE core_customuser SET 
                        email = ?, first_name = ?, last_name = ?
                        WHERE id = ?";
            $stmt = $pdo->prepare($sql_user);
            $stmt->execute([$email, $first_name, $last_name, $user_id]);

            $pdo->commit();
            setFlash("Profile updated successfully!", "success");
            header('Location: /patient/profile.php');
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Update failed: " . $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error) {
    $patient = array_merge($patient, $_POST);
}

include __DIR__ . '/../templates/header.php';
?>

<div class="px-4 sm:px-0 mb-6">
    <h1 class="text-3xl font-bold text-gray-900">Edit Profile</h1>
    <p class="text-gray-600 mt-2">Update your personal information</p>
</div>

<div class="max-w-4xl">
    <div class="bg-white shadow rounded-lg overflow-hidden border-t-4 border-blue-500">
        <div class="px-6 py-4 bg-white border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">
                <i class="fas fa-user-edit text-blue-500 mr-2"></i> Personal Information
            </h3>
        </div>
        <div class="px-6 py-4">
            <?php if ($error): ?>
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline"><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="post" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">
                            Full Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="full_name" 
                               name="full_name" 
                               value="<?= htmlspecialchars($patient['full_name']) ?>" 
                               required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">
                            Phone <span class="text-red-500">*</span>
                        </label>
                        <input type="tel" 
                               id="phone" 
                               name="phone" 
                               value="<?= htmlspecialchars($patient['phone']) ?>" 
                               required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                            Email <span class="text-red-500">*</span>
                        </label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               value="<?= htmlspecialchars($patient['email']) ?>" 
                               required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label for="marital_status" class="block text-sm font-medium text-gray-700 mb-1">
                            Marital Status <span class="text-red-500">*</span>
                        </label>
                        <select id="marital_status" 
                                name="marital_status" 
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select...</option>
                            <option value="Single" <?= ($patient['marital_status'] === 'Single') ? 'selected' : '' ?>>Single</option>
                            <option value="Married" <?= ($patient['marital_status'] === 'Married') ? 'selected' : '' ?>>Married</option>
                            <option value="Divorced" <?= ($patient['marital_status'] === 'Divorced') ? 'selected' : '' ?>>Divorced</option>
                            <option value="Widowed" <?= ($patient['marital_status'] === 'Widowed') ? 'selected' : '' ?>>Widowed</option>
                        </select>
                    </div>

                    <div>
                        <label for="occupation" class="block text-sm font-medium text-gray-700 mb-1">
                            Occupation
                        </label>
                        <input type="text" 
                               id="occupation" 
                               name="occupation" 
                               value="<?= htmlspecialchars($patient['occupation'] ?? '') ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <div>
                    <label for="address" class="block text-sm font-medium text-gray-700 mb-1">
                        Address <span class="text-red-500">*</span>
                    </label>
                    <textarea id="address" 
                              name="address" 
                              rows="3" 
                              required
                              class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"><?= htmlspecialchars($patient['address']) ?></textarea>
                </div>

                <div class="bg-gray-50 p-4 rounded-md">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Non-editable Information</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500">National ID:</span>
                            <span class="text-gray-900 font-medium ml-2"><?= htmlspecialchars($patient['national_id']) ?></span>
                        </div>
                        <div>
                            <span class="text-gray-500">Date of Birth:</span>
                            <span class="text-gray-900 font-medium ml-2"><?= htmlspecialchars($patient['date_of_birth']) ?></span>
                        </div>
                        <div>
                            <span class="text-gray-500">Gender:</span>
                            <span class="text-gray-900 font-medium ml-2"><?= htmlspecialchars($patient['gender']) ?></span>
                        </div>
                        <div>
                            <span class="text-gray-500">Blood Type:</span>
                            <span class="text-gray-900 font-medium ml-2 text-red-600"><?= htmlspecialchars($patient['blood_type']) ?></span>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">These fields cannot be changed for security and medical record integrity.</p>
                </div>

                <div class="flex justify-end space-x-4 pt-4 border-t border-gray-200">
                    <a href="/patient/profile.php" 
                       class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-save mr-2"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>

