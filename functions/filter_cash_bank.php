<?php
// File: functions/filter_cash_bank.php

// 1. Start output buffering immediately to catch any stray text/errors
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 0); // Hide errors from output, we will catch them

header('Content-Type: application/json; charset=utf-8');

try {
    // Check if db config exists
    if (!file_exists("../config/db.php")) {
        throw new Exception("Database config file not found.");
    }
    include "../config/db.php";

    // Check DB connection
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception("Database connection failed: " . ($conn->connect_error ?? 'Unknown error'));
    }

    // Get JSON input
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);

    // Default values
    $type = $data['type'] ?? 'ALL';
    $term = isset($data['term']) ? trim($data['term']) : '';
    $from = $data['from'] ?? '';
    $to = $data['to'] ?? '';

    // Build Query
    $sql = "SELECT * FROM cash_bank_entries WHERE 1=1";
    $params = [];
    $types = "";

    // 1. Search Logic
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

    // 2. Date Logic
    if (!empty($from) && !empty($to)) {
        $sql .= " AND txn_date BETWEEN ? AND ?";
        $params[] = $from;
        $params[] = $to;
        $types .= "ss";
    }

    $sql .= " ORDER BY txn_date DESC, id DESC";

    // Prepare & Execute
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("SQL Prepare failed: " . $conn->error);
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        throw new Exception("SQL Execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $rows = [];
    while ($r = $result->fetch_assoc()) {
        $rows[] = $r;
    }

    // Clean buffer before outputting JSON
    ob_end_clean();
    echo json_encode(['success' => true, 'data' => $rows]);

} catch (Exception $e) {
    // Clean buffer and output JSON error
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

if(isset($conn)) $conn->close();
?>