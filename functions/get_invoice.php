<?php
// File: functions/get_invoice.php
error_reporting(E_ALL);
ini_set('display_errors', 0);

if (ob_get_level()) ob_clean();
header('Content-Type: application/json; charset=utf-8');

include "../config/db.php";

$invoiceNum = isset($_GET['invoice_num']) ? trim($_GET['invoice_num']) : '';

if (empty($invoiceNum)) {
    echo json_encode(['success' => false, 'message' => 'Invoice number required']);
    exit;
}

try {
    // 1. Get Invoice Header
    $stmt = $conn->prepare("SELECT * FROM invoice_txn WHERE invoice_num = ? LIMIT 1");
    $stmt->bind_param("s", $invoiceNum);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Invoice not found']);
        exit;
    }
    
    $invoice = $result->fetch_assoc();
    $stmt->close();
    
    // 2. Get Invoice Items
    $stmt = $conn->prepare("SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY id ASC");
    $stmt->bind_param("s", $invoiceNum);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'invoice' => $invoice,
        'items' => $items
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

if(isset($conn)) $conn->close();
exit;
?>