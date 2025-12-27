<?php
// File: functions/check_invoice_exists.php
error_reporting(0);
ini_set('display_errors', 0);
if (ob_get_level()) ob_clean(); // Crucial to prevent JSON errors
header('Content-Type: application/json; charset=utf-8');

include "../config/db.php";

$inv = isset($_GET['invoice_num']) ? trim($_GET['invoice_num']) : '';

if ($inv === '') {
    echo json_encode(['exists' => false]);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT txn_id FROM invoice_txn WHERE invoice_num = ? LIMIT 1");
    $stmt->bind_param("s", $inv);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        echo json_encode(['exists' => true]);
    } else {
        echo json_encode(['exists' => false]);
    }
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['exists' => false]);
}
$conn->close();
?>