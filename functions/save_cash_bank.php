<?php
// File: functions/save_cash_bank.php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'cash_bank_log.txt');

if (ob_get_level()) ob_clean();
header('Content-Type: application/json; charset=utf-8');

include "../config/db.php";

try {
    // Read JSON Input
    $rawData = file_get_contents('php://input');
    $data = json_decode($rawData, true);

    if (!$data) {
        throw new Exception('Invalid Data');
    }

    $conn->begin_transaction();

    $sql = "INSERT INTO cash_bank_entries 
            (account_type, description, conversion_rate, dr_usd, cr_usd, dr_inr, cr_inr) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
            
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Database Prepare Error: " . $conn->error);
    }

    // Prepare variables (handle empty strings as 0 or null)
    $account_type = $data['account_type'];
    $desc = $data['description'];
    $rate = floatval($data['conversion_rate'] ?? 0);
    $dr_usd = floatval($data['dr_usd'] ?? 0);
    $cr_usd = floatval($data['cr_usd'] ?? 0);
    $dr_inr = floatval($data['dr_inr'] ?? 0);
    $cr_inr = floatval($data['cr_inr'] ?? 0);

    // Bind: s=string, s=string, d=double...
    $stmt->bind_param("ssddddd", 
        $account_type, 
        $desc, 
        $rate, 
        $dr_usd, 
        $cr_usd, 
        $dr_inr, 
        $cr_inr
    );

    if (!$stmt->execute()) {
        throw new Exception("Execution Error: " . $stmt->error);
    }

    $stmt->close();
    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Transaction saved successfully']);

} catch (Exception $e) {
    if (isset($conn)) $conn->rollback();
    error_log("Cash/Bank Save Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>