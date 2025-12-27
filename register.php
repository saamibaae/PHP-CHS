<?php
// register.php
require_once 'db.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect data
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $email = $_POST['email'];
    $full_name = $_POST['full_name'];
    $national_id = $_POST['national_id'];
    $dob = $_POST['date_of_birth'];
    $gender = $_POST['gender'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $blood_type = $_POST['blood_type'];
    $marital_status = $_POST['marital_status'];
    $birth_place = $_POST['birth_place'];
    $father_name = $_POST['father_name'];
    $mother_name = $_POST['mother_name'];
    $occupation = $_POST['occupation'] ?? null;

    // Validation
    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check duplicates
        $stmt = $pdo->prepare("SELECT id FROM core_customuser WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = "Username already exists.";
        } else {
            $stmt = $pdo->prepare("SELECT patient_id FROM core_patient WHERE national_id = ?");
            $stmt->execute([$national_id]);
            if ($stmt->fetch()) {
                $error = "National ID already registered.";
            }
        }
    }

    if (!$error) {
        try {
            $pdo->beginTransaction();

            // Insert User
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $name_parts = explode(' ', $full_name, 2);
            $first_name = $name_parts[0];
            $last_name = $name_parts[1] ?? '';
            $now = date('Y-m-d H:i:s');

            $sql_user = "INSERT INTO core_customuser 
                         (username, password, email, first_name, last_name, is_active, 
                          is_staff, is_superuser, date_joined, role, hospital_id)
                         VALUES (?, ?, ?, ?, ?, 1, 0, 0, ?, 'PATIENT', NULL)";
            
            $stmt = $pdo->prepare($sql_user);
            $stmt->execute([$username, $hashed_password, $email, $first_name, $last_name, $now]);
            $user_id = $pdo->lastInsertId();

            // Insert Patient
            $sql_patient = "INSERT INTO core_patient 
                            (national_id, full_name, date_of_birth, gender, phone, email, address,
                             blood_type, occupation, marital_status, birth_place, father_name, mother_name, user_id)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql_patient);
            $stmt->execute([
                $national_id, $full_name, $dob, $gender, $phone, $email, $address,
                $blood_type, $occupation, $marital_status, $birth_place, $father_name, $mother_name, $user_id
            ]);

            $pdo->commit();
            setFlash("Registration successful! Please log in.");
            header('Location: login.php');
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Registration failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Registration - Healthcare Management System</title>
    <link rel="stylesheet" href="/static/css/style.css">
</head>
<body>
    <div class="container">
        <div class="login-container" style="max_width: 800px;"> <!-- Wider for registration -->
            <div class="login-card">
                <h2>🏥 CHS Bangladesh</h2>
                <h3 class="text-center mb-4">Patient Registration</h3>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="post">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Username</label>
                            <input type="text" name="username" class="form-control" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Confirm Password</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                    </div>

                    <hr>
                    <h4>Personal Information</h4>

                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="full_name" class="form-control" required value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>National ID</label>
                            <input type="text" name="national_id" class="form-control" required value="<?= htmlspecialchars($_POST['national_id'] ?? '') ?>">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Date of Birth</label>
                            <input type="date" name="date_of_birth" class="form-control" required value="<?= htmlspecialchars($_POST['date_of_birth'] ?? '') ?>">
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
                            <label>Blood Type</label>
                            <select name="blood_type" class="form-control" required>
                                <option value="A+">A+</option>
                                <option value="A-">A-</option>
                                <option value="B+">B+</option>
                                <option value="B-">B-</option>
                                <option value="AB+">AB+</option>
                                <option value="AB-">AB-</option>
                                <option value="O+">O+</option>
                                <option value="O-">O-</option>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Marital Status</label>
                            <select name="marital_status" class="form-control" required>
                                <option value="Single">Single</option>
                                <option value="Married">Married</option>
                                <option value="Divorced">Divorced</option>
                                <option value="Widowed">Widowed</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Phone</label>
                            <input type="text" name="phone" class="form-control" required value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                        </div>
                         <div class="form-group col-md-6">
                            <label>Occupation</label>
                            <input type="text" name="occupation" class="form-control" value="<?= htmlspecialchars($_POST['occupation'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="address" class="form-control" rows="3" required><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Birth Place</label>
                            <input type="text" name="birth_place" class="form-control" required value="<?= htmlspecialchars($_POST['birth_place'] ?? '') ?>">
                        </div>
                        <div class="form-group col-md-4">
                            <label>Father's Name</label>
                            <input type="text" name="father_name" class="form-control" required value="<?= htmlspecialchars($_POST['father_name'] ?? '') ?>">
                        </div>
                        <div class="form-group col-md-4">
                            <label>Mother's Name</label>
                            <input type="text" name="mother_name" class="form-control" required value="<?= htmlspecialchars($_POST['mother_name'] ?? '') ?>">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%;">Register</button>
                </form>
                
                <p class="text-center mt-3">
                    <a href="/login.php">Already have an account? Login</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
