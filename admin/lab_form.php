<?php
// admin/lab_form.php
require_once __DIR__ . '/../db.php';
requireRole('ADMIN');

$hospital_id = $_SESSION['hospital_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lab_name = $_POST['lab_name'];
    $location = $_POST['location'];
    $phone = $_POST['phone'];
    
    // Insert
    $sql = "INSERT INTO core_lab (lab_name, hospital_id, location, phone) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$lab_name, $hospital_id, $location, $phone]);
    
    setFlash("Lab added successfully.");
    header('Location: /admin/labs.php');
    exit;
}

include __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <h2>Add Lab</h2>
    <a href="/admin/labs.php" class="btn btn-secondary">Back</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="post">
            <div class="form-group">
                <label>Lab Name</label>
                <input type="text" name="lab_name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>Location</label>
                <input type="text" name="location" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone" class="form-control" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Add Lab</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
