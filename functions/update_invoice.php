<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
if (ob_get_level()) ob_clean();
header('Content-Type: application/json; charset=utf-8');

include "../config/db.php";

try {
    $rawData = file_get_contents('php://input');
    $data = json_decode($rawData, true);

    if (!$data) throw new Exception('Invalid JSON data received');

    $conn->begin_transaction();
    
    $invoiceNum = $data['invoice_num'];
    
    // 1. Check if invoice exists
    $stmt = $conn->prepare("SELECT txn_id FROM invoice_txn WHERE invoice_num = ?");
    $stmt->bind_param("s", $invoiceNum);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) throw new Exception('Invoice not found');
    $stmt->close();
    
    // 2. Handle Party & Broker Names (Auto-create if new)
    $partyName = trim($data['party_name']);
    $conn->query("INSERT IGNORE INTO parties (name) VALUES ('" . $conn->real_escape_string($partyName) . "')");
    
    $brokerName = trim($data['broker_name']);
    if($brokerName) {
        $conn->query("INSERT IGNORE INTO brokers (name) VALUES ('" . $conn->real_escape_string($brokerName) . "')");
    }

    // 3. Update Invoice Header (Including Tax USD)
    // We are updating 20 fields, plus the WHERE clause (invoice_num)
    $stmt = $conn->prepare("UPDATE invoice_txn SET 
        txn_date = ?, party_name = ?, broker_name = ?, notes = ?, 
        credit_days = ?, due_date = ?,
        cal1 = ?, cal2 = ?, cal3 = ?, brokerage_pct = ?, 
        brokerage_amt = ?, brokerage_amt_usd = ?,
        gross_amt_local = ?, gross_amt_usd = ?,
        tax_local = ?, tax_usd = ?, 
        net_amount_local = ?, net_amount_usd = ?,
        party_status = ?, broker_status = ?
        WHERE invoice_num = ?");

    if(!$stmt) throw new Exception("Prepare failed: " . $conn->error);

    // Correct Type String: ssssisddddddddddddiis
    // s (date), s (party), s (broker), s (notes), i (days), s (due_date)
    // d (cal1), d (cal2), d (cal3), d (bpct), d (bamt), d (busd)
    // d (gloc), d (gusd), d (tloc), d (tusd), d (nloc), d (nusd)
    // i (pstat), i (bstat)
    // s (invNum - WHERE clause)
    
    $stmt->bind_param("ssssisddddddddddddiis",
        $data['txn_date'],
        $partyName,
        $brokerName,
        $data['notes'],
        $data['credit_days'],
        $data['due_date'],    // Fixed: Treated as 's' (string)
        $data['cal1'],
        $data['cal2'],
        $data['cal3'],
        $data['brokerage_pct'],
        $data['brokerage_amt'],
        $data['brokerage_amt_usd'],
        $data['gross_amt_local'],
        $data['gross_amt_usd'],
        $data['tax_local'],
        $data['tax_usd'],
        $data['net_amount_local'],
        $data['net_amount_usd'],
        $data['party_status'],
        $data['broker_status'],
        $invoiceNum
    );

    if (!$stmt->execute()) throw new Exception('Update header failed: ' . $stmt->error);
    $stmt->close();
    
    // 4. Update Items (Delete old & Insert new)
    $conn->query("DELETE FROM invoice_items WHERE invoice_id = '" . $conn->real_escape_string($invoiceNum) . "'");
    
    $stmt = $conn->prepare("INSERT INTO invoice_items 
        (invoice_id, currency, qty, rate_usd, rate_local, conv_rate, 
         base_amount_usd, base_amount_local, adjusted_amount_usd, adjusted_amount_local) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
    foreach ($data['items'] as $item) {
        $p_id = $invoiceNum;
        $p_cur = $item['cur'];
        $p_qty = floatval($item['qty']);
        $p_rusd = floatval($item['rateUsd']);
        $p_rloc = floatval($item['rateLocal']);
        $p_conv = floatval($item['convRate']);
        $p_busd = floatval($item['baseUsd']);
        $p_bloc = floatval($item['baseLocal']);
        $p_ausd = floatval($item['adjUsd']);
        $p_aloc = floatval($item['adjLocal']);

        $stmt->bind_param("ssdddddddd", $p_id, $p_cur, $p_qty, $p_rusd, $p_rloc, $p_conv, $p_busd, $p_bloc, $p_ausd, $p_aloc);
        if (!$stmt->execute()) throw new Exception('Update items failed: ' . $stmt->error);
    }
    
    $conn->commit();
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    if (isset($conn)) $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>