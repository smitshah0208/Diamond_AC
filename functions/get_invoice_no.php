<?php
// File: functions/get_invoice_no.php
error_reporting(E_ALL);
ini_set('display_errors', 0);

if (ob_get_level()) ob_clean();
header('Content-Type: application/json; charset=utf-8');

try {
    include "../config/db.php";
    
    $type = isset($_GET['type']) ? $_GET['type'] : 'PU';
    
    // Ensure type is safe for SQL if not using prepared statement for it, 
    // but here we use prepared statement so it's fine.
    
    // Get the last txn_number for this specific type
    $stmt = $conn->prepare("SELECT txn_number FROM invoice_txn WHERE txn_type = ? ORDER BY txn_number DESC LIMIT 1");
    
    if (!$stmt) {
        throw new Exception("Database prepare failed");
    }
    
    $stmt->bind_param("s", $type);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Increment the last number
        $nextNum = intval($row['txn_number']) + 1;
    } else {
        // No previous invoice found, start with 1001
        $nextNum = 1001;
    }
    
    // Generate invoice_num: PU-1001, SA-1001
    $invoiceNum = $type . '-' . $nextNum;
    
    echo json_encode([
        'success' => true,
        'txn_number' => $nextNum,
        'invoice_num' => $invoiceNum
    ]);
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error generating invoice number',
        'error' => $e->getMessage()
    ]);
}
exit;
?>