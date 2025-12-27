<?php
// File: functions/delete_cash_entry.php
include "../config/db.php";
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['id'])) { 
    echo json_encode(['success'=>false, 'message'=>'ID missing']); 
    exit; 
}

$stmt = $conn->prepare("DELETE FROM cash_bank_entries WHERE id = ?");
$stmt->bind_param("i", $data['id']);

if ($stmt->execute()) {
    echo json_encode(['success'=>true, 'message'=>'Deleted successfully']);
} else {
    echo json_encode(['success'=>false, 'message'=>'Error deleting entry']);
}
$stmt->close();
$conn->close();
?>