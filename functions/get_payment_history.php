<?php
// functions/get_payment_history.php
error_reporting(E_ALL);
ini_set('display_errors', 0);
include "../config/db.php";
header('Content-Type: application/json');

try {
    if (!$conn) throw new Exception("Database connection failed");

    if (empty($_GET['invoice_num'])) {
        throw new Exception("Invoice Number is missing");
    }

    $invNum = $_GET['invoice_num'];
    // Default to PARTY if not sent, but frontend will always send it
    $filterType = $_GET['type'] ?? 'PARTY'; 

    // --- SQL UPDATE ---
    // Added: AND party_or_broker = ? 
    // This ensures we only fetch the history relevant to the current view
    $sql = "SELECT 
                txn_date, 
                account_type, 
                party_or_broker, 
                description, 
                dr_local,   -- Debit (Payment)
                cr_local    -- Credit (Receipt)
            FROM cash_bank_entries 
            WHERE invoice_num = ? 
              AND party_or_broker = ?
            ORDER BY txn_date ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $invNum, $filterType);
    $stmt->execute();
    $result = $stmt->get_result();

    $history = [];
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $history, 'filter' => $filterType]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
$conn->close();
?>