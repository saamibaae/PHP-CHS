<?php
require_once __DIR__ . '/../db.php';
requireRole('PATIENT');

header('Content-Type: application/json');

$doctor_id = $_GET['doctor_id'] ?? null;
$date = $_GET['date'] ?? null;

if (!$doctor_id || !$date) {
    echo json_encode(['error' => 'Missing parameters', 'booked_times' => []]);
    exit;
}

$doctor_id = filter_var($doctor_id, FILTER_VALIDATE_INT);
if ($doctor_id === false || $doctor_id <= 0) {
    echo json_encode(['error' => 'Invalid doctor ID', 'booked_times' => []]);
    exit;
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode(['error' => 'Invalid date format', 'booked_times' => []]);
    exit;
}

$date_timestamp = strtotime($date);
if ($date_timestamp === false || $date_timestamp < strtotime('today')) {
    echo json_encode(['error' => 'Date cannot be in the past', 'booked_times' => []]);
    exit;
}

$stmt = $pdo->prepare("SELECT TIME(date_and_time) as time FROM core_appointment WHERE doctor_id = ? AND DATE(date_and_time) = ? AND status != 'Cancelled'");
$stmt->execute([$doctor_id, $date]);
$booked_times = $stmt->fetchAll(PDO::FETCH_COLUMN);

$formatted_times = array_map(function($time) {
    return substr($time, 0, 5); // Get HH:MM from HH:MM:SS
}, $booked_times);

echo json_encode(['booked_times' => $formatted_times]);

