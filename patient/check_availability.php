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

// Get all booked times for this doctor on this date
$stmt = $pdo->prepare("SELECT TIME(date_and_time) as time FROM core_appointment WHERE doctor_id = ? AND DATE(date_and_time) = ? AND status != 'Cancelled'");
$stmt->execute([$doctor_id, $date]);
$booked_times = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Format times as HH:MM (without seconds)
$formatted_times = array_map(function($time) {
    return substr($time, 0, 5); // Get HH:MM from HH:MM:SS
}, $booked_times);

echo json_encode(['booked_times' => $formatted_times]);

