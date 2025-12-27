<?php
// File: functions/save_invoice.php

// 1. Setup Error Handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'debug_log.txt');

// 2. Prepare JSON Response
if (ob_get_level()) ob_clean();
header('Content-Type: application/json; charset=utf-8');

try {
    // 3. Include Database
    if (!file_exists("../config/db.php")) {
        throw new Exception("Database config file not found at ../config/db.php");
    }
    include "../config/db.php";

    // 4. Get Data
    $rawData = file_get_contents('php://input');
    $data = json_decode($rawData, true);

    if (!$data) {
        throw new Exception("No JSON data received or invalid JSON");
    }

    // 5. Start Transaction
    $conn->begin_transaction();

    // ---------------------------------------------------------
    // A. Handle Party
    // ---------------------------------------------------------
    $partyName = trim($data['party_name'] ?? '');
    if (empty($partyName)) throw new Exception("Party name is required");

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

    // ---------------------------------------------------------
    // B. Handle Broker
    // ---------------------------------------------------------
    $brokerName = trim($data['broker_name'] ?? '');
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

    // ---------------------------------------------------------
    // C. Insert Invoice Header
    // ---------------------------------------------------------
    
    // Check if invoice number already exists
    $check = $conn->prepare("SELECT txn_id FROM invoice_txn WHERE invoice_num = ?");
    $check->bind_param("s", $data['invoice_num']);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        throw new Exception("Invoice Number " . $data['invoice_num'] . " already exists.");
    }
    $check->close();

    // Extract txn_number (e.g., 1001 from PU-1001)
    $invoice_num = $data['invoice_num'];
    $parts = explode('-', $invoice_num);
    if (count($parts) > 1) {
        $txn_number = intval($parts[1]); 
    } else {
        $txn_number = intval(preg_replace('/[^0-9]/', '', $invoice_num));
    }
    
    $sql = "INSERT INTO invoice_txn 
            (txn_type, invoice_num, txn_number, txn_date, party_name, broker_name, notes, 
             credit_days, due_date, cal1, cal2, cal3, 
             brokerage_amt, gross_amt, tax, net_amount, party_status, broker_status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);

    // Prepare variables for binding
    $txn_type = $data['txn_type'];
    $txn_date = $data['txn_date'];
    $notes = $data['notes'] ?? '';
    $credit_days = intval($data['credit_days'] ?? 0);
    $due_date = $data['due_date'];
    
    $cal1 = floatval($data['cal1'] ?? 0);
    $cal2 = floatval($data['cal2'] ?? 0);
    $cal3 = floatval($data['cal3'] ?? 0);
    $brokerage_amt = floatval($data['brokerage_amt'] ?? 0);
    $gross_amt = floatval($data['gross_amt'] ?? 0);
    $tax = floatval($data['tax'] ?? 0);
    $net_amount = floatval($data['net_amount'] ?? 0);
    
    $party_status = intval($data['party_status'] ?? 0);
    $broker_status = intval($data['broker_status'] ?? 0);

    // Bind parameters
    $stmt->bind_param("ssissssisdddddddii",
        $txn_type,
        $invoice_num,
        $txn_number,
        $txn_date,
        $partyName,
        $brokerName,
        $notes,
        $credit_days,
        $due_date,
        $cal1,
        $cal2,
        $cal3,
        $brokerage_amt,
        $gross_amt,
        $tax,
        $net_amount,
        $party_status,
        $broker_status
    );

    if (!$stmt->execute()) throw new Exception("Execute failed: " . $stmt->error);
    $stmt->close();

    // ---------------------------------------------------------
    // D. Insert Invoice Items (FIXED BINDING)
    // ---------------------------------------------------------
    if (!empty($data['items']) && is_array($data['items'])) {
        $sqlItems = "INSERT INTO invoice_items 
            (invoice_id, currency, qty, rate_usd, rate_inr, conv_rate, 
             base_amount_usd, base_amount_inr, adjusted_amount_usd, adjusted_amount_inr) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
        $stmt = $conn->prepare($sqlItems);
        
        // --- KEY FIX: Define variables first, Bind ONCE ---
        $b_inv = $invoice_num;
        $b_cur = "";
        $b_qty = 0.0;
        $b_rusd = 0.0;
        $b_rinr = 0.0;
        $b_conv = 0.0;
        $b_busd = 0.0;
        $b_binr = 0.0;
        $b_ausd = 0.0;
        $b_ainr = 0.0;
        
        $stmt->bind_param("ssdddddddd", 
            $b_inv, $b_cur, $b_qty, $b_rusd, $b_rinr, 
            $b_conv, $b_busd, $b_binr, $b_ausd, $b_ainr
        );
        
        foreach ($data['items'] as $item) {
            // Update variable values (Passed by reference automatically)
            $b_cur = $item['cur'];
            $b_qty = floatval($item['qty']);
            $b_rusd = floatval($item['rateUsd']);
            $b_rinr = floatval($item['rateInr']);
            $b_conv = floatval($item['convRate'] ?? 0);
            $b_busd = floatval($item['baseUsd'] ?? 0);
            $b_binr = floatval($item['baseInr']);
            $b_ausd = floatval($item['adjustedUsd'] ?? 0);
            $b_ainr = floatval($item['adjustedInr']);
            
            if (!$stmt->execute()) throw new Exception("Item Insert Failed: " . $stmt->error);
        }
        $stmt->close();
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Saved successfully']);

} catch (Exception $e) {
    if (isset($conn)) $conn->rollback();
    error_log("Save Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

if (isset($conn)) $conn->close();
?>