<?php
require_once __DIR__ . '/../db.php';
requireRole('PATIENT');

$user_id = $_SESSION['user_id'];
$appointment_id = $_GET['appointment_id'] ?? null;

if (!$appointment_id) {
    setFlash("Invalid appointment ID.", "error");
    header("Location: /patient/appointments.php");
    exit;
}

$stmt = $pdo->prepare("SELECT patient_id FROM core_patient WHERE user_id = ?");
$stmt->execute([$user_id]);
$patient_id = $stmt->fetchColumn();

if (!$patient_id) {
    die("Patient record not found.");
}

$sql = "SELECT a.*, d.doctor_id, d.full_name as doctor_name, d.specialization
        FROM core_appointment a
        INNER JOIN core_doctor d ON a.doctor_id = d.doctor_id
        WHERE a.appointment_id = ? AND a.patient_id = ? AND a.status = 'Completed'";
$stmt = $pdo->prepare($sql);
$stmt->execute([$appointment_id, $patient_id]);
$appointment = $stmt->fetch();

if (!$appointment) {
    setFlash("Appointment not found or not completed.", "error");
    header("Location: /patient/appointments.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM core_doctorrating WHERE appointment_id = ? AND patient_id = ?");
$stmt->execute([$appointment_id, $patient_id]);
$existing_rating = $stmt->fetch();

$error = null;
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = intval($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    
    if ($rating < 1 || $rating > 5) {
        $error = "Please select a valid rating (1-5 stars).";
    } else {
        try {
            if ($existing_rating) {
                $sql = "UPDATE core_doctorrating SET rating = ?, comment = ? WHERE rating_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$rating, $comment, $existing_rating['rating_id']]);
                $success_message = "Rating updated successfully!";
            } else {
                $sql = "INSERT INTO core_doctorrating (doctor_id, patient_id, appointment_id, rating, comment)
                        VALUES (?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$appointment['doctor_id'], $patient_id, $appointment_id, $rating, $comment]);
                $success_message = "Thank you for your rating!";
            }
            
            setFlash($success_message);
            header("Location: /patient/appointment_detail.php?id=" . $appointment_id);
            exit;
        } catch (Exception $e) {
            $error = "Error submitting rating: " . $e->getMessage();
        }
    }
}

include __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <h2><i class="fas fa-star mr-2"></i>Rate Doctor</h2>
    <a href="/patient/appointment_detail.php?id=<?= $appointment_id ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left mr-2"></i>Back
    </a>
</div>

<?php if ($error): ?>
    <div class="alert alert-error mb-4">
        <i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3>Rate Your Experience</h3>
    </div>
    <div class="card-body">
        <div class="mb-4 p-3 bg-gray-50 rounded">
            <p><strong>Doctor:</strong> Dr. <?= htmlspecialchars($appointment['doctor_name']) ?></p>
            <p><strong>Specialization:</strong> <?= htmlspecialchars($appointment['specialization']) ?></p>
            <p><strong>Appointment Date:</strong> <?= date('M d, Y H:i', strtotime($appointment['date_and_time'])) ?></p>
        </div>
        
        <form method="post" action="">
            <div class="form-group mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Rating <span class="text-danger">*</span></label>
                <div class="rating-input flex items-center space-x-2">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <input type="radio" name="rating" id="rating<?= $i ?>" value="<?= $i ?>" 
                               class="hidden" <?= ($existing_rating && $existing_rating['rating'] == $i) ? 'checked' : '' ?> required>
                        <label for="rating<?= $i ?>" class="cursor-pointer text-4xl text-gray-300 hover:text-yellow-400 transition-colors rating-star">
                            <i class="fas fa-star"></i>
                        </label>
                    <?php endfor; ?>
                </div>
                <p class="text-sm text-gray-500 mt-2">Click on a star to rate (1 = Poor, 5 = Excellent)</p>
            </div>
            
            <div class="form-group">
                <label class="block text-sm font-medium text-gray-700 mb-2">Comment (Optional)</label>
                <textarea name="comment" rows="4" class="form-control" 
                          placeholder="Share your experience... (This will be anonymous)"><?= htmlspecialchars($existing_rating['comment'] ?? '') ?></textarea>
                <small class="form-text text-muted">Your comment will be anonymous and help other patients make informed decisions.</small>
            </div>
            
            <div class="form-group mt-4">
                <button type="submit" class="btn btn-success btn-lg">
                    <i class="fas fa-check mr-2"></i><?= $existing_rating ? 'Update Rating' : 'Submit Rating' ?>
                </button>
                <a href="/patient/appointment_detail.php?id=<?= $appointment_id ?>" class="btn btn-secondary btn-lg ml-2">
                    <i class="fas fa-times mr-2"></i>Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function updateStarDisplay(rating) {
        document.querySelectorAll('.rating-star').forEach((star, index) => {
            const starRating = 5 - index;
            if (starRating <= rating) {
                star.classList.remove('text-gray-300');
                star.classList.add('text-yellow-400');
            } else {
                star.classList.remove('text-yellow-400');
                star.classList.add('text-gray-300');
            }
        });
    }
    
    const ratingStars = document.querySelectorAll('.rating-star');
    const ratingInput = document.querySelector('.rating-input');
    
    if (ratingStars.length > 0 && ratingInput) {
        ratingStars.forEach((star, index) => {
            star.addEventListener('click', function() {
                const rating = 5 - index;
                const radioInput = document.getElementById('rating' + rating);
                if (radioInput) {
                    radioInput.checked = true;
                    updateStarDisplay(rating);
                }
            });
            
            star.addEventListener('mouseenter', function() {
                const rating = 5 - index;
                updateStarDisplay(rating);
            });
        });
        
        ratingInput.addEventListener('mouseleave', function() {
            const checked = document.querySelector('input[name="rating"]:checked');
            if (checked) {
                updateStarDisplay(parseInt(checked.value));
            } else {
                updateStarDisplay(0);
            }
        });
        
        const checked = document.querySelector('input[name="rating"]:checked');
        if (checked) {
            updateStarDisplay(parseInt(checked.value));
        } else {
            updateStarDisplay(0);
        }
    }
});
</script>

<?php include __DIR__ . '/../templates/footer.php'; ?>

