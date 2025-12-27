<?php
error_reporting(0);
ini_set('display_errors', 0);

if (ob_get_level()) ob_clean();
header('Content-Type: application/json; charset=utf-8');

include "../config/db.php";

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

try {
    // Search invoices by invoice_num
    $stmt = $conn->prepare("SELECT invoice_num, party_name, net_amount, txn_date 
                            FROM invoice_txn 
                            WHERE invoice_num LIKE ? 
                            ORDER BY txn_id DESC 
                            LIMIT 20");
    $searchTerm = $query . '%';
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $invoices = [];
    while ($row = $result->fetch_assoc()) {
        $invoices[] = [
            'invoice_num' => $row['invoice_num'],
            'party_name' => $row['party_name'],
            'net_amount' => $row['net_amount'],
            'txn_date' => $row['txn_date']
        ];
    }
    
    echo json_encode($invoices);
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    echo json_encode([]);
}
exit;
?>