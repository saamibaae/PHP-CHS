<?php
require_once __DIR__ . '/../db.php';
requireRole('ADMIN');

$hospital_id = $_SESSION['hospital_id'];
$lab_id = $_GET['id'] ?? null;
$lab = null;
$title = "Add Lab";

if ($lab_id) {
    $stmt = $pdo->prepare("SELECT * FROM core_lab WHERE lab_id = ? AND hospital_id = ?");
    $stmt->execute([$lab_id, $hospital_id]);
    $lab = $stmt->fetch();
    if (!$lab) {
        die("Lab not found.");
    }
    $title = "Edit Lab";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lab_name = $_POST['lab_name'];
    $location = $_POST['location'];
    $phone = $_POST['phone'];
    
    if ($lab_id) {
        $sql = "UPDATE core_lab SET lab_name = ?, location = ?, phone = ? WHERE lab_id = ? AND hospital_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$lab_name, $location, $phone, $lab_id, $hospital_id]);
        setFlash("Lab updated successfully.");
    } else {
        $sql = "INSERT INTO core_lab (lab_name, hospital_id, location, phone) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$lab_name, $hospital_id, $location, $phone]);
        setFlash("Lab added successfully.");
    }
    
    header('Location: /admin/labs.php');
    exit;
}

include __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <h2><?= $title ?></h2>
    <a href="/admin/labs.php" class="btn btn-secondary">Back</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="post">
            <div class="form-group">
                <label>Lab Name</label>
                <input type="text" name="lab_name" class="form-control" required value="<?= htmlspecialchars($lab['lab_name'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label>Location</label>
                <input type="text" name="location" class="form-control" required value="<?= htmlspecialchars($lab['location'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone" class="form-control" required value="<?= htmlspecialchars($lab['phone'] ?? '') ?>">
            </div>
            
            <button type="submit" class="btn btn-primary"><?= $lab_id ? 'Update Lab' : 'Add Lab' ?></button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
