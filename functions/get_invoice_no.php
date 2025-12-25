<?php
header('Content-Type: application/json');
include "../config/db.php";

$type = isset($_GET['type']) ? mysqli_real_escape_string($conn, $_GET['type']) : 'PU';

try {
    // Get the last invoice number for this specific type from database
    $stmt = $conn->prepare("SELECT invoice_no FROM invoice_txn WHERE txn_type = ? ORDER BY txn_id DESC LIMIT 1");
    $stmt->bind_param("s", $type);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Extract number from invoice_no (e.g., "PU-0004" -> 4)
        $lastNo = $row['invoice_no'];
        preg_match('/\d+$/', $lastNo, $matches);
        $nextNum = isset($matches[0]) ? intval($matches[0]) + 1 : 1;
    } else {
        // No previous invoice of this type found
        $nextNum = 1;
    }
    
    // Format: PU-0001, SA-0001, etc.
    $invoiceNo = $type . '-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
    
    echo json_encode([
        'success' => true,
        'invoice_no' => $invoiceNo
    ]);
    
    $stmt->close();
    
} catch (Exception $e) {
    error_log("Get invoice no error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error generating invoice number: ' . $e->getMessage()
    ]);
}

$conn->close();
?>