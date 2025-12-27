<?php
// File: functions/update_invoice.php
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable echo errors to prevent JSON breakage
ini_set('log_errors', 1);
ini_set('error_log', 'debug_update_log.txt'); // Log errors to file

if (ob_get_level()) ob_clean();
header('Content-Type: application/json; charset=utf-8');

include "../config/db.php";

try {
    $rawData = file_get_contents('php://input');
    $data = json_decode($rawData, true);

    if (!$data) {
        throw new Exception('Invalid JSON data received');
    }

    $conn->begin_transaction();
    
    $invoiceNum = $data['invoice_num'];
    
    // 1. Check if invoice exists
    $stmt = $conn->prepare("SELECT txn_id FROM invoice_txn WHERE invoice_num = ? LIMIT 1");
    $stmt->bind_param("s", $invoiceNum);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        throw new Exception('Invoice not found');
    }
    $stmt->close();
    
    // 2. Handle Party Name (Auto-Insert)
    $partyName = trim($data['party_name']);
    if (!empty($partyName)) {
        $stmt = $conn->prepare("SELECT id FROM parties WHERE name = ? LIMIT 1");
        $stmt->bind_param("s", $partyName);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            $stmt->close();
            $ins = $conn->prepare("INSERT INTO parties (name) VALUES (?)");
            $ins->bind_param("s", $partyName);
            $ins->execute();
            $ins->close();
        } else {
            $stmt->close();
        }
    }
    
    // 3. Handle Broker Name (Auto-Insert)
    $brokerName = trim($data['broker_name']);
    if (!empty($brokerName)) {
        $stmt = $conn->prepare("SELECT id FROM brokers WHERE name = ? LIMIT 1");
        $stmt->bind_param("s", $brokerName);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            $stmt->close();
            $ins = $conn->prepare("INSERT INTO brokers (name) VALUES (?)");
            $ins->bind_param("s", $brokerName);
            $ins->execute();
            $ins->close();
        } else {
            $stmt->close();
        }
    }
    
    // 4. Update Invoice Header
    // We have 16 variables to bind below
    $stmt = $conn->prepare("UPDATE invoice_txn SET 
        txn_date = ?, 
        party_name = ?, 
        broker_name = ?, 
        notes = ?, 
        cal1 = ?, 
        cal2 = ?, 
        cal3 = ?, 
        brokerage_amt = ?, 
        gross_amt = ?, 
        tax = ?, 
        net_amount = ?, 
        party_status = ?, 
        broker_status = ?, 
        credit_days = ?, 
        due_date = ?
        WHERE invoice_num = ?");
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    // Prepare variables
    $cal1 = floatval($data['cal1'] ?? 0);
    $cal2 = floatval($data['cal2'] ?? 0);
    $cal3 = floatval($data['cal3'] ?? 0);
    $brokerage = floatval($data['brokerage_amt'] ?? 0);
    $gross = floatval($data['gross_amt'] ?? 0);
    $tax = floatval($data['tax'] ?? 0);
    $net = floatval($data['net_amount'] ?? 0);
    $p_status = intval($data['party_status'] ?? 0);
    $b_status = intval($data['broker_status'] ?? 0);
    $credit = intval($data['credit_days'] ?? 0);

    // FIX: The type string must have 16 characters for 16 variables
    // s(4) + d(7) + i(3) + s(2) = 16
    // Previous error was missing one 'd'
    $stmt->bind_param("ssssdddddddiiiss",
        $data['txn_date'],  // s
        $partyName,         // s
        $brokerName,        // s
        $data['notes'],     // s
        $cal1,              // d
        $cal2,              // d
        $cal3,              // d
        $brokerage,         // d
        $gross,             // d
        $tax,               // d
        $net,               // d
        $p_status,          // i
        $b_status,          // i
        $credit,            // i
        $data['due_date'],  // s
        $invoiceNum         // s (WHERE)
    );
    
    if (!$stmt->execute()) {
        throw new Exception('Error updating invoice header: ' . $stmt->error);
    }
    $stmt->close();
    
    // 5. Update Items (Delete old, Insert new)
    $stmt = $conn->prepare("DELETE FROM invoice_items WHERE invoice_id = ?");
    $stmt->bind_param("s", $invoiceNum);
    $stmt->execute();
    $stmt->close();
    
    if (!empty($data['items']) && is_array($data['items'])) {
        $stmt = $conn->prepare("INSERT INTO invoice_items 
            (invoice_id, currency, qty, rate_usd, rate_inr, conv_rate, 
             base_amount_usd, base_amount_inr, adjusted_amount_usd, adjusted_amount_inr) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($data['items'] as $item) {
            $conv = floatval($item['convRate'] ?? 0);
            $baseUsd = floatval($item['baseUsd'] ?? 0);
            $adjUsd = floatval($item['adjustedUsd'] ?? 0);
            
            $stmt->bind_param("ssdddddddd",
                $invoiceNum,
                $item['cur'],
                $item['qty'],
                $item['rateUsd'],
                $item['rateInr'],
                $conv,
                $baseUsd,
                $item['baseInr'],
                $adjUsd,
                $item['adjustedInr']
            );
            
            if (!$stmt->execute()) {
                throw new Exception('Error saving items');
            }
        }
        $stmt->close();
    }
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Invoice updated successfully'
    ]);
    
} catch (Exception $e) {
    if (isset($conn)) $conn->rollback();
    error_log("Update Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

if (isset($conn)) $conn->close();
exit;
?>