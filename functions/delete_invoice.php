<?php
error_reporting(0);
ini_set('display_errors', 0);

if (ob_get_level()) ob_clean();
header('Content-Type: application/json; charset=utf-8');

include "../config/db.php";

$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);

if (!$data || empty($data['invoice_num'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

try {
    $conn->begin_transaction();
    
    $invoiceNum = $data['invoice_num'];
    
    // Check if invoice exists
    $stmt = $conn->prepare("SELECT txn_id FROM invoice_txn WHERE invoice_num = ? LIMIT 1");
    $stmt->bind_param("s", $invoiceNum);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Invoice not found');
    }
    $stmt->close();
    
    // Delete invoice items first
    $stmt = $conn->prepare("DELETE FROM invoice_items WHERE invoice_id = ?");
    $stmt->bind_param("s", $invoiceNum);
    if (!$stmt->execute()) {
        throw new Exception('Error deleting invoice items');
    }
    $stmt->close();
    
    // Delete invoice
    $stmt = $conn->prepare("DELETE FROM invoice_txn WHERE invoice_num = ?");
    $stmt->bind_param("s", $invoiceNum);
    if (!$stmt->execute()) {
        throw new Exception('Error deleting invoice');
    }
    $stmt->close();
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Invoice deleted successfully'
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
exit;
?>