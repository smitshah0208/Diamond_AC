<?php
// File: functions/save_cash_bank.php
include "../config/db.php";
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) { echo json_encode(['success'=>false, 'message'=>'Invalid data']); exit; }

$conn->begin_transaction();

try {
    // Added related_name
    $sql = "INSERT INTO cash_bank_entries 
            (txn_date, account_type, invoice_num, party_or_broker, related_name, description, conversion_rate, dr_usd, cr_usd, dr_inr, cr_inr) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
    $stmt = $conn->prepare($sql);
    
    $invNum = !empty($data['invoice_num']) ? $data['invoice_num'] : NULL;
    $pob = !empty($data['party_or_broker']) ? $data['party_or_broker'] : NULL;
    $relName = !empty($data['related_name']) ? $data['related_name'] : NULL; // New
    $rate = floatval($data['conversion_rate'] ?? 0);
    
    // Bind: s s s s s s d d d d d (11 params)
    $stmt->bind_param("ssssssddddd", 
        $data['txn_date'], 
        $data['account_type'], 
        $invNum, 
        $pob, 
        $relName, // New
        $data['description'],
        $rate, $data['dr_usd'], $data['cr_usd'], $data['dr_inr'], $data['cr_inr']
    );

    if (!$stmt->execute()) throw new Exception($stmt->error);
    
    $newId = $stmt->insert_id;
    $stmt->close();
    $conn->commit();

    echo json_encode(['success'=>true, 'id'=>$newId, 'message'=>'Saved successfully']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success'=>false, 'message'=>$e->getMessage()]);
}
$conn->close();
?>