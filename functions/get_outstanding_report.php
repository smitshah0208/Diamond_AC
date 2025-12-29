<?php
// functions/get_outstanding_report.php

// 1. Setup
error_reporting(E_ALL);
ini_set('display_errors', 0); // Hide errors from breaking JSON
include "../config/db.php";
header('Content-Type: application/json');

try {
    if (!$conn) throw new Exception("Database connection failed");

    // 2. Inputs
    $fromDate = $_GET['from_date'] ?? date('Y-m-01');
    $toDate = $_GET['to_date'] ?? date('Y-m-d');
    $viewMode = $_GET['view_mode'] ?? 'PARTY'; 

    $response = [
        'receivables' => [],
        'payables' => [],
        'total_receivable' => 0,
        'total_payable' => 0
    ];

    // 3. SQL Query
    // We fetch ALL invoices in the date range.
    // We do NOT filter out paid ones in the SQL, we handle that in the loop.
    $sql = "
    SELECT 
        i.txn_id, 
        i.invoice_num, 
        i.txn_date, 
        i.party_name, 
        i.broker_name,
        i.txn_type,         
        i.credit_days, 
        i.due_date,
        i.net_amount_local, 
        i.brokerage_amt,

        COALESCE(SUM(CASE WHEN c.party_or_broker = 'PARTY' THEN c.dr_local ELSE 0 END), 0) as party_paid_amt,
        COALESCE(SUM(CASE WHEN c.party_or_broker = 'PARTY' THEN c.cr_local ELSE 0 END), 0) as party_rcvd_amt,
        COALESCE(SUM(CASE WHEN c.party_or_broker = 'BROKER' THEN c.dr_local ELSE 0 END), 0) as broker_paid_amt

    FROM invoice_txn i
    LEFT JOIN cash_bank_entries c ON i.invoice_num = c.invoice_num
    
    GROUP BY i.txn_id
    HAVING i.due_date BETWEEN ? AND ?
    ORDER BY i.due_date ASC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $fromDate, $toDate);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        
        // --- LOGIC: PARTY VIEW ---
        if ($viewMode === 'PARTY') {
            
            // PURCHASE (Payable)
            if ($row['txn_type'] === 'PU') {
                $billAmount = floatval($row['net_amount_local']);
                $paidSoFar  = floatval($row['party_paid_amt']); 
                $balance    = $billAmount - $paidSoFar;

                // Prepare Data Row
                $row['display_name'] = $row['party_name'];
                $row['total_amt'] = $billAmount;
                $row['settled_amt'] = $paidSoFar;
                $row['balance'] = $balance;
                
                // Add to list (Even if balance is 0)
                $response['payables'][] = $row;
                
                // Only add to 'Total Pending' if actually unpaid
                if ($balance > 1.00) {
                    $response['total_payable'] += $balance;
                }
            }

            // SALES (Receivable)
            elseif ($row['txn_type'] === 'SA') {
                $billAmount = floatval($row['net_amount_local']);
                $rcvdSoFar  = floatval($row['party_rcvd_amt']);
                $balance    = $billAmount - $rcvdSoFar;

                $row['display_name'] = $row['party_name'];
                $row['total_amt'] = $billAmount;
                $row['settled_amt'] = $rcvdSoFar;
                $row['balance'] = $balance;
                
                $response['receivables'][] = $row;
                
                if ($balance > 1.00) {
                    $response['total_receivable'] += $balance;
                }
            }
        }

        // --- LOGIC: BROKER VIEW ---
        elseif ($viewMode === 'BROKER') {
            $brokerFee = floatval($row['brokerage_amt']);
            
            if ($brokerFee > 0) {
                $paidBroker = floatval($row['broker_paid_amt']);
                $balance    = $brokerFee - $paidBroker;

                $row['display_name'] = $row['broker_name'];
                $row['total_amt'] = $brokerFee;
                $row['settled_amt'] = $paidBroker;
                $row['balance'] = $balance;
                
                $response['payables'][] = $row;
                
                if ($balance > 1.00) {
                    $response['total_payable'] += $balance;
                }
            }
        }
    }

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => true, 'message' => $e->getMessage()]);
}
$conn->close();
?>