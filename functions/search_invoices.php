<?php
// File: functions/search_invoices.php
error_reporting(0);
ini_set('display_errors', 0);
if (ob_get_level()) ob_clean(); // Prevent stray spaces
header('Content-Type: application/json; charset=utf-8');

include "../config/db.php";

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

try {
    // Make sure we select broker_name
    $stmt = $conn->prepare("SELECT invoice_num, party_name, broker_name, net_amount 
                            FROM invoice_txn 
                            WHERE invoice_num LIKE ? 
                            ORDER BY txn_id DESC LIMIT 20");
    $searchTerm = $query . '%';
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $invoices = [];
    while ($row = $result->fetch_assoc()) {
        $invoices[] = [
            'invoice_num' => $row['invoice_num'],
            'party_name' => $row['party_name'],
            // Ensure broker_name is not null
            'broker_name' => $row['broker_name'] ? $row['broker_name'] : '', 
            'net_amount' => $row['net_amount']
        ];
    }
    
    echo json_encode($invoices);
    $stmt->close();
    
} catch (Exception $e) {
    echo json_encode([]);
}
$conn->close();
?>