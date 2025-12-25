<?php
// Disable error display, only log errors
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set JSON header
header('Content-Type: application/json');

// Include database connection
include "../config/db.php";

// Get JSON data
$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);

if (!$data) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid JSON data received'
    ]);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();
    
    // ========== Auto-Insert Party if not exists ==========
    $partyName = trim($data['party_name']);
    if (!empty($partyName)) {
        // Check if party exists
        $stmt = $conn->prepare("SELECT id FROM parties WHERE name = ? LIMIT 1");
        $stmt->bind_param("s", $partyName);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            // Party doesn't exist, insert it
            $insertStmt = $conn->prepare("INSERT INTO parties (name) VALUES (?)");
            $insertStmt->bind_param("s", $partyName);
            if (!$insertStmt->execute()) {
                throw new Exception("Error inserting party: " . $insertStmt->error);
            }
            $insertStmt->close();
        }
        $stmt->close();
    }
    
    // ========== Auto-Insert Broker if not exists ==========
    $brokerName = trim($data['broker_name']);
    if (!empty($brokerName)) {
        // Check if broker exists
        $stmt = $conn->prepare("SELECT id FROM brokers WHERE name = ? LIMIT 1");
        $stmt->bind_param("s", $brokerName);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            // Broker doesn't exist, insert it
            $insertStmt = $conn->prepare("INSERT INTO brokers (name, rate) VALUES (?, 1.00)");
            $insertStmt->bind_param("s", $brokerName);
            if (!$insertStmt->execute()) {
                throw new Exception("Error inserting broker: " . $insertStmt->error);
            }
            $insertStmt->close();
        }
        $stmt->close();
    }
    
    // ========== Insert Invoice ==========
    $stmt = $conn->prepare("INSERT INTO invoice_txn 
        (txn_type, invoice_no, txn_date, party_name, broker_name, description, 
         brokerage_amt, gross_amt, tax, net_amount, party_status, broker_status, credit_days, due_date) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("ssssssdddiiiis",
        $data['txn_type'],
        $data['invoice_no'],
        $data['txn_date'],
        $partyName,
        $brokerName,
        $data['description'],
        $data['brokerage_amt'],
        $data['gross_amt'],
        $data['tax'],
        $data['net_amount'],
        $data['party_status'],
        $data['broker_status'],
        $data['credit_days'],
        $data['due_date']
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Error saving invoice: " . $stmt->error);
    }
    
    $invoice_no = $data['invoice_no'];
    $stmt->close();
    
    // ========== Save Invoice Items ==========
    if (!empty($data['items']) && is_array($data['items'])) {
        $stmt = $conn->prepare("INSERT INTO invoice_items 
            (invoice_id, currency, qty, rate_usd, rate_inr, conv_rate, base_amount_usd, base_amount_inr, adjusted_amount_usd, adjusted_amount_inr) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        if (!$stmt) {
            throw new Exception("Prepare items failed: " . $conn->error);
        }
        
        foreach ($data['items'] as $item) {
            $convRate = isset($item['convRate']) ? floatval($item['convRate']) : 0;
            $baseUsd = isset($item['baseUsd']) ? floatval($item['baseUsd']) : 0;
            $adjustedUsd = isset($item['adjustedUsd']) ? floatval($item['adjustedUsd']) : 0;
            
            $stmt->bind_param("ssdddddddd",
                $invoice_no,
                $item['cur'],
                $item['qty'],
                $item['rateUsd'],
                $item['rateInr'],
                $convRate,
                $baseUsd,
                $item['baseInr'],
                $adjustedUsd,
                $item['adjustedInr']
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Error saving invoice items: " . $stmt->error);
            }
        }
        $stmt->close();
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Invoice saved successfully',
        'invoice_no' => $invoice_no
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    if (isset($conn)) {
        $conn->rollback();
    }
    
    // Log error
    error_log("Save invoice error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

if (isset($conn)) {
    $conn->close();
}
?>