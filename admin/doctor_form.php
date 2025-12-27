<?php
// admin/doctor_form.php
require_once __DIR__ . '/../db.php';
requireRole('ADMIN');

$hospital_id = $_SESSION['hospital_id'];

// Get Departments for dropdown
$stmt = $pdo->prepare("SELECT dept_id, dept_name FROM core_department WHERE hospital_id = ? ORDER BY dept_name");
$stmt->execute([$hospital_id]);
$departments = $stmt->fetchAll();

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // User fields
    $username = $_POST['username'];
    $password = $_POST['password'];
    $email = $_POST['email'];
    
    // Doctor fields
    $license_no = $_POST['license_no'];
    $full_name = $_POST['full_name'];
    $specialization = $_POST['specialization'];
    $phone = $_POST['phone'];
    $experience_yrs = $_POST['experience_yrs'];
    $gender = $_POST['gender'];
    $shift_timing = $_POST['shift_timing'];
    $join_date = $_POST['join_date'];
    $dept_id = $_POST['dept_id'] ?: null;

    // Check duplicates
    $stmt = $pdo->prepare("SELECT id FROM core_customuser WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        $error = "Username already exists.";
    } else {
        $stmt = $pdo->prepare("SELECT doctor_id FROM core_doctor WHERE license_no = ?");
        $stmt->execute([$license_no]);
        if ($stmt->fetch()) {
            $error = "License number already exists.";
        }
    }

    if (!$error) {
        try {
            $pdo->beginTransaction();

            // Create User
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $name_parts = explode(' ', $full_name, 2);
            $first_name = $name_parts[0];
            $last_name = $name_parts[1] ?? '';
            $now = date('Y-m-d H:i:s');

            $sql_user = "INSERT INTO core_customuser 
                         (username, password, email, first_name, last_name, is_active, 
                          is_staff, is_superuser, date_joined, role, hospital_id)
                         VALUES (?, ?, ?, ?, ?, 1, 0, 0, ?, 'DOCTOR', ?)";
            
            $stmt = $pdo->prepare($sql_user);
            $stmt->execute([$username, $hashed_password, $email, $first_name, $last_name, $now, $hospital_id]);
            $user_id = $pdo->lastInsertId();

            // Create Doctor
            $sql_doctor = "INSERT INTO core_doctor 
                           (license_no, full_name, specialization, phone, email, experience_yrs, 
                            gender, shift_timing, join_date, hospital_id, dept_id, user_id)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql_doctor);
            $stmt->execute([
                $license_no, $full_name, $specialization, $phone, $email, $experience_yrs,
                $gender, $shift_timing, $join_date, $hospital_id, $dept_id, $user_id
            ]);

            $pdo->commit();
            setFlash("Doctor added successfully.");
            header('Location: /admin/doctors.php');
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error adding doctor: " . $e->getMessage();
        }
    }
}

include __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <h2>Add New Doctor</h2>
    <a href="/admin/doctors.php" class="btn btn-secondary">Back</a>
</div>

<div class="card">
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <h4>Account Information</h4>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>
                <div class="form-group col-md-6">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
            </div>

            <hr>
            <h4>Doctor Profile</h4>
            
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" class="form-control" required value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>License No</label>
                    <input type="text" name="license_no" class="form-control" required value="<?= htmlspecialchars($_POST['license_no'] ?? '') ?>">
                </div>
                <div class="form-group col-md-6">
                    <label>Specialization</label>
                    <input type="text" name="specialization" class="form-control" required value="<?= htmlspecialchars($_POST['specialization'] ?? '') ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <div class="form-group col-md-6">
                    <label>Phone</label>
                    <input type="text" name="phone" class="form-control" required value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>Gender</label>
                    <select name="gender" class="form-control" required>
                        <option value="M">Male</option>
                        <option value="F">Female</option>
                        <option value="O">Other</option>
                    </select>
                </div>
                <div class="form-group col-md-4">
                    <label>Experience (Years)</label>
                    <input type="number" name="experience_yrs" class="form-control" required value="<?= htmlspecialchars($_POST['experience_yrs'] ?? '') ?>">
                </div>
                <div class="form-group col-md-4">
                    <label>Shift Timing</label>
                    <input type="text" name="shift_timing" class="form-control" required value="<?= htmlspecialchars($_POST['shift_timing'] ?? '') ?>" placeholder="e.g. 9AM-5PM">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Join Date</label>
                    <input type="date" name="join_date" class="form-control" required value="<?= htmlspecialchars($_POST['join_date'] ?? '') ?>">
                </div>
                <div class="form-group col-md-6">
                    <label>Department</label>
                    <select name="dept_id" class="form-control">
                        <option value="">-- Select Department --</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= $dept['dept_id'] ?>"><?= htmlspecialchars($dept['dept_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn-primary mt-3">Create Doctor Account</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
