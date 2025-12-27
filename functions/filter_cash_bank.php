<?php
// File: functions/filter_cash_bank.php
error_reporting(0);
ini_set('display_errors', 0);
if (ob_get_level()) ob_clean();
header('Content-Type: application/json');

include "../config/db.php";

$data = json_decode(file_get_contents('php://input'), true);

$type = $data['type'] ?? 'ALL';
$term = $data['term'] ?? '';
$from = $data['from'] ?? '';
$to = $data['to'] ?? '';

// Start Building Query
$sql = "SELECT * FROM cash_bank_entries WHERE 1=1";
$params = [];
$types = "";

// 1. Specific Search Filter
if ($type === 'INVOICE' && !empty($term)) {
    $sql .= " AND invoice_num LIKE ?";
    $params[] = "%$term%"; 
    $types .= "s";
} 
elseif ($type === 'PARTY' && !empty($term)) {
    $sql .= " AND party_or_broker = 'PARTY' AND related_name LIKE ?";
    $params[] = "%$term%";
    $types .= "s";
} 
elseif ($type === 'BROKER' && !empty($term)) {
    $sql .= " AND party_or_broker = 'BROKER' AND related_name LIKE ?";
    $params[] = "%$term%";
    $types .= "s";
}

// 2. Date Filter (Applies to all types if dates are provided)
if (!empty($from) && !empty($to)) {
    $sql .= " AND txn_date BETWEEN ? AND ?";
    $params[] = $from;
    $params[] = $to;
    $types .= "ss";
}

$sql .= " ORDER BY txn_date DESC, id DESC";

try {
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $rows = [];
    while ($r = $result->fetch_assoc()) {
        $rows[] = $r;
    }
    
    echo json_encode(['success' => true, 'data' => $rows]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>