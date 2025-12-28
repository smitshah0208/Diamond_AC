<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
if (ob_get_level()) ob_clean();
header('Content-Type: application/json; charset=utf-8');

try {
    include "../config/db.php";
    
    $rawData = file_get_contents('php://input');
    $data = json_decode($rawData, true);
    
    if (!$data) {
        throw new Exception("Invalid JSON data received");
    }

    $conn->begin_transaction();

    // 1. Party
    $partyName = trim($data['party_name']);
    $res = $conn->query("SELECT id FROM parties WHERE name = '" . $conn->real_escape_string($partyName) . "'");
    if ($res->num_rows == 0) {
        $conn->query("INSERT INTO parties (name) VALUES ('" . $conn->real_escape_string($partyName) . "')");
    }

    // 2. Broker
    $brokerName = trim($data['broker_name']);
    if (!empty($brokerName)) {
        $res = $conn->query("SELECT id FROM brokers WHERE name = '" . $conn->real_escape_string($brokerName) . "'");
        if ($res->num_rows == 0) {
            $conn->query("INSERT INTO brokers (name) VALUES ('" . $conn->real_escape_string($brokerName) . "')");
        }
    }

    // 3. Invoice Number Logic
    $invNum = $data['invoice_num'];
    $parts = explode('-', $invNum);
    $txnNum = (count($parts) > 1) ? intval($parts[1]) : intval(preg_replace('/[^0-9]/', '', $invNum));

    // 4. Header Insert
    $stmt = $conn->prepare("INSERT INTO invoice_txn 
        (txn_type, invoice_num, txn_number, txn_date, party_name, broker_name, notes, 
         brokerage_pct, credit_days, due_date, cal1, cal2, cal3, 
         brokerage_amt, brokerage_amt_usd, gross_amt_local, gross_amt_usd, 
         tax_local, tax_usd, net_amount_local, net_amount_usd, 
         party_status, broker_status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    if(!$stmt) throw new Exception("Prepare failed: " . $conn->error);

    $stmt->bind_param("ssissssdisdddddddddddii",
        $data['txn_type'], $invNum, $txnNum, $data['txn_date'], $partyName, $brokerName, $data['notes'],
        $data['brokerage_pct'], $data['credit_days'], $data['due_date'], 
        $data['cal1'], $data['cal2'], $data['cal3'],
        $data['brokerage_amt'], $data['brokerage_amt_usd'], 
        $data['gross_amt_local'], $data['gross_amt_usd'], 
        $data['tax_local'], $data['tax_usd'], 
        $data['net_amount_local'], $data['net_amount_usd'],
        $data['party_status'], $data['broker_status']
    );
    
    if(!$stmt->execute()) throw new Exception("Header execute failed: " . $stmt->error);
    $stmt->close();

    // 5. Items Insert
    $stmt = $conn->prepare("INSERT INTO invoice_items 
        (invoice_id, currency, qty, rate_usd, rate_local, conv_rate, 
         base_amount_usd, base_amount_local, adjusted_amount_usd, adjusted_amount_local) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $p_id = $invNum;
    $p_cur = ""; $p_qty = 0; $p_rusd = 0; $p_rloc = 0; $p_conv = 0;
    $p_busd = 0; $p_bloc = 0; $p_ausd = 0; $p_aloc = 0;

    $stmt->bind_param("ssdddddddd", $p_id, $p_cur, $p_qty, $p_rusd, $p_rloc, $p_conv, $p_busd, $p_bloc, $p_ausd, $p_aloc);

    foreach ($data['items'] as $item) {
        $p_cur = $item['cur'];
        $p_qty = floatval($item['qty']);
        $p_rusd = floatval($item['rateUsd']);
        $p_rloc = floatval($item['rateLocal']);
        $p_conv = floatval($item['convRate']);
        $p_busd = floatval($item['baseUsd']);
        $p_bloc = floatval($item['baseLocal']);
        // Here we take the calculated adjusted amounts from JS
        $p_ausd = floatval($item['adjUsd']); 
        $p_aloc = floatval($item['adjLocal']);
        
        if(!$stmt->execute()) throw new Exception("Item execute failed: " . $stmt->error);
    }
    $stmt->close();

    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if (isset($conn)) $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>