<?php
error_reporting(E_ALL);
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
    
    // Delete items first (Foreign Key safety)
    $stmt = $conn->prepare("DELETE FROM invoice_items WHERE invoice_id = ?");
    $stmt->bind_param("s", $invoiceNum);
    $stmt->execute();
    $stmt->close();
    
    // Delete header
    $stmt = $conn->prepare("DELETE FROM invoice_txn WHERE invoice_num = ?");
    $stmt->bind_param("s", $invoiceNum);
    if (!$stmt->execute()) throw new Exception('Could not delete invoice');
    
    if ($stmt->affected_rows === 0) throw new Exception('Invoice not found');
    
    $stmt->close();
    
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Deleted']);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>