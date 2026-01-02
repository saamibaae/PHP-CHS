<?php
require_once __DIR__ . '/../db.php';
requireRole('ADMIN');

$hospital_id = $_SESSION['hospital_id'];
$dept_id = $_GET['id'] ?? null;
$dept = null;
$title = "Add Department";

if ($dept_id) {
    $stmt = $pdo->prepare("SELECT * FROM core_department WHERE dept_id = ? AND hospital_id = ?");
    $stmt->execute([$dept_id, $hospital_id]);
    $dept = $stmt->fetch();
    if (!$dept) {
        die("Department not found.");
    }
    $title = "Edit Department";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dept_name = $_POST['dept_name'];
    $floor = $_POST['floor'];
    $extension = $_POST['extension'] ?: null;
    $operating_hours = $_POST['operating_hours'];
    
    if ($dept_id) {
        $sql = "UPDATE core_department 
                SET dept_name = ?, floor = ?, extension = ?, operating_hours = ?
                WHERE dept_id = ? AND hospital_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$dept_name, $floor, $extension, $operating_hours, $dept_id, $hospital_id]);
        setFlash("Department updated successfully.");
    } else {
        $sql = "INSERT INTO core_department (dept_name, floor, extension, operating_hours, hospital_id)
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$dept_name, $floor, $extension, $operating_hours, $hospital_id]);
        setFlash("Department added successfully.");
    }
    
    header('Location: /admin/departments.php');
    exit;
}

include __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <h2><?= $title ?></h2>
    <a href="/admin/departments.php" class="btn btn-secondary">Back</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="post">
            <div class="form-group">
                <label>Department Name</label>
                <input type="text" name="dept_name" class="form-control" required value="<?= htmlspecialchars($dept['dept_name'] ?? '') ?>">
            </div>
            
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Floor</label>
                    <input type="text" name="floor" class="form-control" required value="<?= htmlspecialchars($dept['floor'] ?? '') ?>">
                </div>
                <div class="form-group col-md-6">
                    <label>Extension (Optional)</label>
                    <input type="text" name="extension" class="form-control" value="<?= htmlspecialchars($dept['extension'] ?? '') ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label>Operating Hours</label>
                <input type="text" name="operating_hours" class="form-control" required value="<?= htmlspecialchars($dept['operating_hours'] ?? '') ?>" placeholder="e.g. 9:00 AM - 5:00 PM">
            </div>
            
            <button type="submit" class="btn btn-primary">Save Department</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
