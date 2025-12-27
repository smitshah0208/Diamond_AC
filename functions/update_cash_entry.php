<?php
// File: functions/update_cash_entry.php
include "../config/db.php";
header('Content-Type: application/json');

$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);

if (empty($data['id'])) { 
    echo json_encode(['success'=>false, 'message'=>'Transaction ID missing']); 
    exit; 
}

$conn->begin_transaction();

try {
    // Updated Query to include 'related_name'
    $sql = "UPDATE cash_bank_entries SET 
            txn_date=?, 
            account_type=?, 
            invoice_num=?, 
            party_or_broker=?, 
            related_name=?, 
            description=?, 
            conversion_rate=?, 
            dr_usd=?, 
            cr_usd=?, 
            dr_inr=?, 
            cr_inr=?
            WHERE id=?";
            
    $stmt = $conn->prepare($sql);
    
    // Handle Nulls
    $invNum = !empty($data['invoice_num']) ? $data['invoice_num'] : NULL;
    $pob = !empty($data['party_or_broker']) ? $data['party_or_broker'] : NULL;
    $relName = !empty($data['related_name']) ? $data['related_name'] : NULL;
    $rate = floatval($data['conversion_rate'] ?? 0);
    
    // Bind: s s s s s s d d d d d i (12 params)
    $stmt->bind_param("ssssssdddddi", 
        $data['txn_date'], 
        $data['account_type'], 
        $invNum, 
        $pob, 
        $relName, 
        $data['description'],
        $rate, 
        $data['dr_usd'], 
        $data['cr_usd'], 
        $data['dr_inr'], 
        $data['cr_inr'],
        $data['id']
    );

    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }
    
    $stmt->close();
    $conn->commit();

    echo json_encode(['success'=>true, 'message'=>'Entry updated successfully']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success'=>false, 'message'=>$e->getMessage()]);
}
$conn->close();
?>