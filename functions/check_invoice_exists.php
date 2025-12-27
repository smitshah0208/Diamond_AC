<?php
// File: functions/check_invoice_exists.php -- cashbank 

// Disable error display to screen (prevents breaking JSON)
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Clear any previous output (newlines/spaces from includes)
if (ob_get_level()) ob_clean();

header('Content-Type: application/json; charset=utf-8');

include "../config/db.php";

$inv = isset($_GET['invoice_num']) ? trim($_GET['invoice_num']) : '';

if ($inv === '') {
    echo json_encode(['exists' => false]);
    exit;
}

try {
    // Check if invoice exists in invoice_txn table
    $stmt = $conn->prepare("SELECT txn_id FROM invoice_txn WHERE invoice_num = ? LIMIT 1");
    if (!$stmt) {
        throw new Exception($conn->error);
    }
    
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
    // If error, assume false but log it (optional)
    echo json_encode(['exists' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>