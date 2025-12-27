<?php
// File: functions/update_cash_entry.php
include "../config/db.php";
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['id'])) { echo json_encode(['success'=>false, 'message'=>'ID missing']); exit; }

$conn->begin_transaction();

try {
    $sql = "UPDATE cash_bank_entries SET 
            txn_date=?, account_type=?, invoice_num=?, description=?, 
            conversion_rate=?, dr_usd=?, cr_usd=?, dr_inr=?, cr_inr=?
            WHERE id=?";
            
    $stmt = $conn->prepare($sql);
    
    $invNum = !empty($data['invoice_num']) ? $data['invoice_num'] : NULL;
    $rate = floatval($data['conversion_rate'] ?? 0);
    
    $stmt->bind_param("ssssdddddi", 
        $data['txn_date'], $data['account_type'], $invNum, $data['description'],
        $rate, $data['dr_usd'], $data['cr_usd'], $data['dr_inr'], $data['cr_inr'],
        $data['id']
    );

    if (!$stmt->execute()) throw new Exception($stmt->error);
    
    $stmt->close();
    $conn->commit();

    echo json_encode(['success'=>true, 'message'=>'Updated successfully']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success'=>false, 'message'=>$e->getMessage()]);
}
$conn->close();
?>