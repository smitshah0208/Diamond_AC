<?php
include "../config/db.php";
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
if(!$data) { echo json_encode(['success'=>false]); exit; }

$fy = $data['financial_year'];

// Check if entry exists for this year
$check = $conn->query("SELECT id FROM financial_openings WHERE financial_year = '$fy'");

if ($check->num_rows > 0) {
    $sql = "UPDATE financial_openings SET 
            start_date=?, end_date=?, op_stock_qty=?, op_stock_val=?, 
            op_cash_local=?, op_bank_local=?, op_cash_usd=?
            WHERE financial_year=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssddddds", 
        $data['start_date'], $data['end_date'],
        $data['qty'], $data['val'], $data['cash'], $data['bank'], $data['usd'], $fy
    );
} else {
    $sql = "INSERT INTO financial_openings 
            (start_date, end_date, op_stock_qty, op_stock_val, op_cash_local, op_bank_local, op_cash_usd, financial_year)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssddddds", 
        $data['start_date'], $data['end_date'],
        $data['qty'], $data['val'], $data['cash'], $data['bank'], $data['usd'], $fy
    );
}

if($stmt->execute()) {
    echo json_encode(['success'=>true]);
} else {
    echo json_encode(['success'=>false, 'error'=>$conn->error]);
}
?>