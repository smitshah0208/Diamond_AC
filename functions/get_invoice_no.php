<?php
// Disable all error output
error_reporting(0);
ini_set('display_errors', 0);

// Set JSON header first
header('Content-Type: application/json');

// Clean output buffer
if (ob_get_level()) ob_clean();

try {
    include "../config/db.php";
    
    $type = isset($_GET['type']) ? mysqli_real_escape_string($conn, $_GET['type']) : 'PU';
    
    // Get the last txn_number for this specific type from database
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
        // No previous invoice of this type found, start with 1001
        $nextNum = 1001;
    }
    
    // Generate invoice_num: PU-1001, SA-1001, etc.
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