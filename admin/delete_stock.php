<?php
require_once __DIR__ . '/../db.php';
requireRole('ADMIN');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlash("Invalid request method.", "error");
    header("Location: /admin/pharmacy_stock.php");
    exit;
}

$hospital_id = $_SESSION['hospital_id'];
$stock_id = $_POST['id'] ?? null;
$pharmacy_id = $_POST['pharmacy'] ?? null;

if (!$stock_id) {
    setFlash("Invalid request.", "error");
    header("Location: /admin/pharmacy_stock.php");
    exit;
}

$sql = "SELECT pm.*, p.pharmacy_id, p.hospital_id
        FROM core_pharmacymedicine pm
        INNER JOIN core_pharmacy p ON pm.pharmacy_id = p.pharmacy_id
        WHERE pm.pharmacy_medicine_id = ? AND p.hospital_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$stock_id, $hospital_id]);
$stock_item = $stmt->fetch();

if (!$stock_item) {
    setFlash("Stock item not found or access denied.", "error");
    header("Location: /admin/pharmacy_stock.php");
    exit;
}

try {
    $delete_sql = "DELETE FROM core_pharmacymedicine WHERE pharmacy_medicine_id = ?";
    $delete_stmt = $pdo->prepare($delete_sql);
    $delete_stmt->execute([$stock_id]);
    
    setFlash("Stock item deleted successfully.");
} catch (Exception $e) {
    setFlash("Error deleting stock item: " . $e->getMessage(), "error");
}

$redirect_pharmacy = $pharmacy_id ?? $stock_item['pharmacy_id'];
header("Location: /admin/pharmacy_stock.php?pharmacy=" . $redirect_pharmacy);
exit;

