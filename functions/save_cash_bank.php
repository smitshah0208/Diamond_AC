<?php
include "../config/db.php";
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) { echo json_encode(['success'=>false, 'message'=>'Invalid data']); exit; }

$conn->begin_transaction();

try {
    $invNum = !empty($data['invoice_num']) ? $data['invoice_num'] : NULL;
    $pob = !empty($data['party_or_broker']) ? $data['party_or_broker'] : 'GENERAL';
    $relName = !empty($data['related_name']) ? trim($data['related_name']) : NULL;
    $payCur = !empty($data['payment_currency']) ? $data['payment_currency'] : 'Local';
    $rate = floatval($data['conversion_rate'] ?? 0);

    // --- LOGIC CORRECTION ---
    // User Rule: Debit = Payment, Credit = Receipt
    $drLocal = floatval($data['dr_local'] ?? 0);
    $txnType = ($drLocal > 0) ? 'Payment' : 'Receipt';

    // --- STRICT LOGIC: AUTO-CREATE PARTY/BROKER (General Mode Only) ---
    if (empty($invNum) && $relName && ($pob === 'PARTY' || $pob === 'BROKER')) {
        $table = ($pob === 'PARTY') ? 'parties' : 'brokers';
        
        $stmtCheck = $conn->prepare("SELECT id FROM $table WHERE name = ?");
        $stmtCheck->bind_param("s", $relName);
        $stmtCheck->execute();
        if ($stmtCheck->get_result()->num_rows === 0) {
            $stmtCheck->close();
            $stmtIns = $conn->prepare("INSERT INTO $table (name) VALUES (?)");
            $stmtIns->bind_param("s", $relName);
            $stmtIns->execute();
            $stmtIns->close();
        } else {
            $stmtCheck->close();
        }
    }

    $sql = "INSERT INTO cash_bank_entries 
            (txn_date, account_type, transaction_type, invoice_num, party_or_broker, related_name, description, 
             payment_currency, conversion_rate, dr_usd, cr_usd, dr_local, cr_local) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
    $stmt = $conn->prepare($sql);
    
    // Bind 13 variables
    $stmt->bind_param("ssssssssddddd", 
        $data['txn_date'], 
        $data['account_type'],
        $txnType, // Now Corrected (Dr=Payment, Cr=Receipt)
        $invNum, 
        $pob, 
        $relName,
        $data['description'],
        $payCur,
        $rate, 
        $data['dr_usd'], 
        $data['cr_usd'], 
        $data['dr_local'], 
        $data['cr_local']
    );

    if (!$stmt->execute()) throw new Exception($stmt->error);
    
    $newId = $stmt->insert_id;
    $stmt->close();
    $conn->commit();

    echo json_encode(['success'=>true, 'id'=>$newId, 'txn_type'=>$txnType, 'message'=>'Saved successfully']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success'=>false, 'message'=>$e->getMessage()]);
}
$conn->close();
?>