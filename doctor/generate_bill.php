<?php
require_once __DIR__ . '/../db.php';
requireRole('DOCTOR');

$user_id = $_SESSION['user_id'];
$appointment_id = $_GET['appointment_id'] ?? null;

if (!$appointment_id) {
    setFlash("Invalid appointment ID.", "error");
    header("Location: /doctor/appointments.php");
    exit;
}

$stmt = $pdo->prepare("SELECT doctor_id FROM core_doctor WHERE user_id = ?");
$stmt->execute([$user_id]);
$doctor_id = $stmt->fetchColumn();

if (!$doctor_id) {
    die("Doctor record not found.");
}

$sql = "SELECT a.*, p.patient_id, p.full_name as patient_name, p.national_id
        FROM core_appointment a
        INNER JOIN core_patient p ON a.patient_id = p.patient_id
        WHERE a.appointment_id = ? AND a.doctor_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$appointment_id, $doctor_id]);
$appointment = $stmt->fetch();

if (!$appointment) {
    setFlash("Appointment not found or access denied.", "error");
    header("Location: /doctor/appointments.php");
    exit;
}

if ($appointment['status'] != 'Completed') {
    setFlash("Can only generate bill for completed appointments.", "error");
    header("Location: /doctor/appointment_detail.php?id=" . $appointment_id);
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_type_id = intval($_POST['service_type_id'] ?? 0);
    $total_amount = floatval($_POST['total_amount'] ?? 0);
    $due_date = $_POST['due_date'] ?? date('Y-m-d', strtotime('+30 days'));
    
    if (!$service_type_id) {
        $error = "Please select a service type.";
    } elseif ($total_amount <= 0) {
        $error = "Total amount must be greater than 0.";
    } else {
        try {
            if (!$service_type_id) {
                $stmt = $pdo->query("SELECT service_type_id FROM core_servicetype WHERE name = 'Consultation' LIMIT 1");
                $service_type_id = $stmt->fetchColumn();
                
                if (!$service_type_id) {
                    $pdo->exec("INSERT INTO core_servicetype (name, description) VALUES ('Consultation', 'Doctor consultation fee')");
                    $service_type_id = $pdo->lastInsertId();
                }
            }
            
            $sql = "INSERT INTO core_bill (patient_id, service_type_id, total_amount, status, due_date)
                    VALUES (?, ?, ?, 'Pending', ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$appointment['patient_id'], $service_type_id, $total_amount, $due_date]);
            
            setFlash("Bill generated successfully.");
            header("Location: /doctor/appointment_detail.php?id=" . $appointment_id);
            exit;
        } catch (Exception $e) {
            $error = "Error generating bill: " . $e->getMessage();
        }
    }
}

$stmt = $pdo->query("SELECT * FROM core_servicetype ORDER BY name");
$service_types = $stmt->fetchAll();

include __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <h2><i class="fas fa-file-invoice-dollar mr-2"></i>Generate Bill</h2>
    <a href="/doctor/appointment_detail.php?id=<?= $appointment_id ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left mr-2"></i>Back to Appointment
    </a>
</div>

<?php if ($error): ?>
    <div class="alert alert-error mb-4">
        <i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3>Bill Information</h3>
    </div>
    <div class="card-body">
        <div class="mb-4 p-3 bg-gray-50 rounded">
            <p><strong>Patient:</strong> <?= htmlspecialchars($appointment['patient_name']) ?></p>
            <p><strong>Appointment Date:</strong> <?= date('M d, Y H:i', strtotime($appointment['date_and_time'])) ?></p>
            <p><strong>Diagnosis:</strong> <?= htmlspecialchars($appointment['diagnosis'] ?? 'N/A') ?></p>
        </div>
        
        <form method="post" action="">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Service Type <span class="text-danger">*</span></label>
                    <select name="service_type_id" class="form-control" required>
                        <option value="">-- Select Service Type --</option>
                        <?php foreach ($service_types as $st): ?>
                            <option value="<?= $st['service_type_id'] ?>">
                                <?= htmlspecialchars($st['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group col-md-6">
                    <label>Total Amount (৳) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="total_amount" class="form-control" 
                           min="0.01" required>
                </div>
            </div>
            
            <div class="form-group">
                <label>Due Date</label>
                <input type="date" name="due_date" class="form-control" 
                       value="<?= date('Y-m-d', strtotime('+30 days')) ?>">
            </div>
            
            <div class="form-group mt-4">
                <button type="submit" class="btn btn-success btn-lg">
                    <i class="fas fa-check mr-2"></i>Generate Bill
                </button>
                <a href="/doctor/appointment_detail.php?id=<?= $appointment_id ?>" class="btn btn-secondary btn-lg ml-2">
                    <i class="fas fa-times mr-2"></i>Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>

