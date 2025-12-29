<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
if (ob_get_level()) ob_clean();
header('Content-Type: application/json');

include "../config/db.php";

$data = json_decode(file_get_contents('php://input'), true);

$type = $data['type'] ?? 'PARTY'; 
$name = $data['name'] ?? '';
$isAllTime = $data['allTime'] ?? false;

if ($isAllTime) {
    $from = '1000-01-01';
    $to   = '9999-12-31';
} else {
    $from = $data['from'] ?? '2000-01-01';
    $to   = $data['to'] ?? date('Y-m-d');
}

if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Name is required']);
    exit;
}

try {
    
    $finalSql = "
        SELECT * FROM (
            -- 1. INVOICES
            SELECT 
                txn_date, 
                CAST(txn_type AS CHAR) as doc_type, 
                CAST(invoice_num AS CHAR) as invoice_num, 
                'INVOICE' as source, 
                CAST(notes AS CHAR) as description,
                net_amount_local, 
                brokerage_amt,
                cal1, cal2, cal3, credit_days, due_date,
                CAST(broker_name AS CHAR) as ref_name, 
                
                -- NEW TAX COLUMNS
                tax_local, 
                tax_usd,

                0.00 as dr_local, 
                0.00 as cr_local,
                '' as acc_mode 
            FROM invoice_txn
            WHERE txn_date BETWEEN ? AND ? 
            AND " . ($type === 'PARTY' ? "party_name" : "broker_name") . " = ?
            
            UNION ALL
            
            -- 2. CASH/BANK ENTRIES
            SELECT 
                txn_date, 
                CAST(transaction_type AS CHAR) as doc_type, 
                CAST(invoice_num AS CHAR) as invoice_num, 
                'CASH' as source, 
                CAST(description AS CHAR) as description,
                0.00 as net_amount_local, 
                0.00 as brokerage_amt,
                0 as cal1, 0 as cal2, 0 as cal3, 0 as credit_days, NULL as due_date,
                '' as ref_name, 
                
                -- DUMMY TAX COLUMNS FOR UNION ALIGNMENT
                0.00 as tax_local,
                0.00 as tax_usd,

                dr_local, 
                cr_local,
                CAST(account_type AS CHAR) as acc_mode 
            FROM cash_bank_entries
            WHERE txn_date BETWEEN ? AND ? 
            AND related_name = ? 
            AND party_or_broker = ?
        ) AS unified_ledger
        ORDER BY txn_date ASC, source DESC
    ";

    $stmt = $conn->prepare($finalSql);
    $cashTypeFilter = ($type === 'PARTY') ? 'PARTY' : 'BROKER';
    
    $stmt->bind_param("sssssss", $from, $to, $name, $from, $to, $name, $cashTypeFilter);
    
    if (!$stmt->execute()) throw new Exception($stmt->error);
    
    $result = $stmt->get_result();

    $ledger = [];
    $runningBalance = 0; 
    $totalDr = 0;
    $totalCr = 0;

    while ($row = $result->fetch_assoc()) {
        $date = $row['txn_date'];
        $debit = 0;
        $credit = 0;
        $descHtml = "";
        $rowType = $row['doc_type']; 

        // --- INVOICE ---
        if ($row['source'] === 'INVOICE') {
            $invNo = $row['invoice_num'];
            $descHtml .= "<strong>Inv: $invNo</strong>";
            
            $extras = [];
            if ($type === 'PARTY' && !empty($row['ref_name'])) $extras[] = "<strong>Broker:</strong> " . $row['ref_name'];
            if (floatval($row['cal1']) != 0) $extras[] = "C1: " . floatval($row['cal1']) . "%";
            if (floatval($row['cal2']) != 0) $extras[] = "C2: " . floatval($row['cal2']) . "%";
            if (floatval($row['cal3']) != 0) $extras[] = "C3: " . floatval($row['cal3']) . "%";
            if ($row['credit_days'] > 0) $extras[] = "Days: " . $row['credit_days'];
            if ($row['due_date']) $extras[] = "Due: " . $row['due_date'];
            
            // --- NEW: Add Tax to Description ---
            if (floatval($row['tax_local']) > 0) {
                $extras[] = "<strong style='color:#ea580c'>Tax:</strong> " . floatval($row['tax_local']);
                if (floatval($row['tax_usd']) > 0) {
                    $extras[] = "($" . floatval($row['tax_usd']) . ")";
                }
            }

            if (!empty($extras)) $descHtml .= "<br><span style='color:#444; font-size:11px;'>" . implode(" | ", $extras) . "</span>";

            $itemSql = "SELECT qty, rate_usd, rate_local FROM invoice_items WHERE invoice_id = ?";
            $itemStmt = $conn->prepare($itemSql);
            $itemStmt->bind_param("s", $invNo);
            $itemStmt->execute();
            $itemsRes = $itemStmt->get_result();
            while($item = $itemsRes->fetch_assoc()) {
                $rUsd = $item['rate_usd'] > 0 ? $item['rate_usd']."$" : "";
                $rLoc = floatval($item['rate_local']);
                $descHtml .= "<br><span class='item-row'>• " . floatval($item['qty']) . " @ " . $rUsd . " ($rLoc)</span>";
            }
            $itemStmt->close();

            $amt = ($type === 'PARTY') ? floatval($row['net_amount_local']) : floatval($row['brokerage_amt']);
            
            if ($type === 'BROKER') {
                $credit = $amt; 
            } else {
                if ($row['doc_type'] === 'PU') {
                    $credit = $amt; 
                } else {
                    $debit = $amt; 
                }
            }
        } 
        // --- CASH/BANK ---
        else {
            $descHtml = "<strong>" . $row['acc_mode'] . " Entry</strong>"; 
            if($row['invoice_num']) $descHtml .= " <span style='color:#2563eb'>(Ref: " . $row['invoice_num'] . ")</span>";
            if($row['description']) $descHtml .= "<br><span style='font-size:12px;color:#444'>" . $row['description'] . "</span>";

            $debit = floatval($row['dr_local']);
            $credit = floatval($row['cr_local']);
        }

        $runningBalance += ($credit - $debit);
        $totalDr += $debit;
        $totalCr += $credit;

        $ledger[] = [
            'date' => $date,
            'type' => $rowType,
            'desc' => $descHtml,
            'debit' => $debit,
            'credit' => $credit,
            'balance' => $runningBalance
        ];
    }

    echo json_encode([
        'success' => true, 
        'data' => $ledger, 
        'totalDr' => $totalDr, 
        'totalCr' => $totalCr, 
        'netBalance' => $runningBalance
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>