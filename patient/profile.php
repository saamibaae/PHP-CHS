<?php
// patient/profile.php
require_once __DIR__ . '/../db.php';
requireRole('PATIENT');

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM core_patient WHERE user_id = ?");
$stmt->execute([$user_id]);
$patient = $stmt->fetch();

$patient_id = $patient['patient_id'];
$stmt = $pdo->prepare("SELECT * FROM core_patientemergencycontact WHERE patient_id = ?");
$stmt->execute([$patient_id]);
$contacts = $stmt->fetchAll();

include __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <h2>My Profile</h2>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">Personal Information</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Full Name:</strong> <?= htmlspecialchars($patient['full_name']) ?></p>
                        <p><strong>National ID:</strong> <?= htmlspecialchars($patient['national_id']) ?></p>
                        <p><strong>Date of Birth:</strong> <?= htmlspecialchars($patient['date_of_birth']) ?></p>
                        <p><strong>Gender:</strong> <?= htmlspecialchars($patient['gender']) ?></p>
                        <p><strong>Blood Type:</strong> <?= htmlspecialchars($patient['blood_type']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Phone:</strong> <?= htmlspecialchars($patient['phone']) ?></p>
                        <p><strong>Email:</strong> <?= htmlspecialchars($patient['email']) ?></p>
                        <p><strong>Address:</strong><br><?= nl2br(htmlspecialchars($patient['address'])) ?></p>
                        <p><strong>Occupation:</strong> <?= htmlspecialchars($patient['occupation']) ?></p>
                        <p><strong>Marital Status:</strong> <?= htmlspecialchars($patient['marital_status']) ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">Emergency Contacts</div>
            <div class="card-body">
                <ul class="list-group">
                    <?php foreach ($contacts as $c): ?>
                    <li class="list-group-item">
                        <strong><?= htmlspecialchars($c['contact_name']) ?></strong>
                        <?php if ($c['is_primary']): ?> <span class="badge badge-primary">Primary</span><?php endif; ?>
                        <br>
                        <?= htmlspecialchars($c['contact_phone']) ?>
                        <br>
                        <small class="text-muted"><?= htmlspecialchars($c['relationship']) ?></small>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
