<?php
// functions/get_financial_summary.php
error_reporting(E_ALL);
ini_set('display_errors', 0);
include "../config/db.php";
header('Content-Type: application/json');

try {
    if (!$conn) throw new Exception("Database connection failed");

    // Get the selected Year from Frontend (e.g., "2026-2027")
    $fyLabel = $_GET['fy_label'] ?? '2025-2026';
    
    // ---------------------------------------------------------
    // A. FETCH OPENING (OR CALCULATE DATES DYNAMICALLY)
    // ---------------------------------------------------------
    $sqlOpen = "SELECT * FROM financial_openings WHERE financial_year = ?";
    $stmt = $conn->prepare($sqlOpen);
    $stmt->bind_param("s", $fyLabel);
    $stmt->execute();
    $opening = $stmt->get_result()->fetch_assoc();

    if (!$opening) {
        // --- STRICT DATE LOGIC ---
        // We parse "2026-2027" to get Start: 2026 and End: 2027
        $years = explode('-', $fyLabel);
        
        if (count($years) == 2) {
            $y1 = intval($years[0]); // e.g. 2026
            $y2 = intval($years[1]); // e.g. 2027
        } else {
            // Fallback if label is weird, though it shouldn't be
            $y1 = date('Y');
            $y2 = date('Y') + 1;
        }

        $opening = [
            'start_date' => "$y1-04-01",  // April 1st of First Year
            'end_date'   => "$y2-03-31",  // March 31st of Second Year (STRICT)
            'op_stock_qty' => 0, 'op_stock_val' => 0,
            'op_cash_local' => 0, 'op_bank_local' => 0, 'op_cash_usd' => 0
        ];
    }
    
    $fyStart = $opening['start_date'];
    $fyEnd   = $opening['end_date'];

    // ---------------------------------------------------------
    // B. STOCK & BROKERAGE CALCULATION
    // ---------------------------------------------------------
    
    // 1. Get Values & Brokerage
    $sqlVal = "SELECT txn_type, SUM(net_amount_local) as total_val, SUM(brokerage_amt) as total_brok 
               FROM invoice_txn 
               WHERE txn_date BETWEEN ? AND ? 
               GROUP BY txn_type";
    
    $stmtVal = $conn->prepare($sqlVal);
    $stmtVal->bind_param("ss", $fyStart, $fyEnd);
    $stmtVal->execute();
    $resVal = $stmtVal->get_result();
    
    $purVal = 0; $saleVal = 0;
    $totalBrokerage = 0;

    while($row = $resVal->fetch_assoc()) {
        $totalBrokerage += floatval($row['total_brok']);
        if($row['txn_type'] === 'PU') $purVal = floatval($row['total_val']);
        elseif($row['txn_type'] === 'SA') $saleVal = floatval($row['total_val']);
    }

    // 2. Get Quantities
    $sqlQty = "SELECT t.txn_type, SUM(i.qty) as total_qty 
               FROM invoice_items i
               JOIN invoice_txn t ON i.invoice_id = t.invoice_num
               WHERE t.txn_date BETWEEN ? AND ?
               GROUP BY t.txn_type";
               
    $stmtQty = $conn->prepare($sqlQty);
    $stmtQty->bind_param("ss", $fyStart, $fyEnd);
    $stmtQty->execute();
    $resQty = $stmtQty->get_result();
    
    $purQty = 0; $saleQty = 0;
    while($row = $resQty->fetch_assoc()) {
        if($row['txn_type'] === 'PU') $purQty = floatval($row['total_qty']);
        elseif($row['txn_type'] === 'SA') $saleQty = floatval($row['total_qty']);
    }

    // ---------------------------------------------------------
    // C. PROFIT & LOSS LOGIC
    // ---------------------------------------------------------
    
    $totalInputQty = floatval($opening['op_stock_qty']) + $purQty;
    $totalInputVal = floatval($opening['op_stock_val']) + $purVal;

    $avgCost = ($totalInputQty > 0) ? ($totalInputVal / $totalInputQty) : 0;

    $cogs = $saleQty * $avgCost;
    $grossProfit = $saleVal - $cogs;
    $netProfit = $grossProfit - $totalBrokerage;

    // Closing Stock
    $closingQty = $totalInputQty - $saleQty;
    $closingStockValAtCost = $closingQty * $avgCost;


    // ---------------------------------------------------------
    // D. CASH & BANK
    // ---------------------------------------------------------
    $sqlCash = "SELECT account_type, payment_currency, transaction_type, 
                SUM(dr_local) as sum_dr, SUM(cr_local) as sum_cr, 
                SUM(dr_usd) as sum_dr_usd, SUM(cr_usd) as sum_cr_usd
                FROM cash_bank_entries WHERE txn_date BETWEEN ? AND ?
                GROUP BY account_type, payment_currency, transaction_type";
    
    $stmtCash = $conn->prepare($sqlCash);
    $stmtCash->bind_param("ss", $fyStart, $fyEnd);
    $stmtCash->execute();
    $resCash = $stmtCash->get_result();

    $cashLocRec=0; $cashLocPay=0; $bankLocRec=0; $bankLocPay=0; $usdRec=0; $usdPay=0;

    while($row = $resCash->fetch_assoc()) {
        $acc = $row['account_type']; $cur = $row['payment_currency']; $type = $row['transaction_type'];
        
        if ($acc === 'Cash' && $cur === 'Local') {
            if ($type === 'Receipt') $cashLocRec += floatval($row['sum_cr']); else $cashLocPay += floatval($row['sum_dr']);
        }
        if ($acc === 'Bank') {
            if ($type === 'Receipt') $bankLocRec += floatval($row['sum_cr']); else $bankLocPay += floatval($row['sum_dr']);
        }
        if ($acc === 'Cash' && $cur === 'Dollar') {
            if ($type === 'Receipt') $usdRec += floatval($row['sum_cr_usd']); else $usdPay += floatval($row['sum_dr_usd']);
        }
    }

    $finalCashLoc = floatval($opening['op_cash_local']) + $cashLocRec - $cashLocPay;
    $finalBankLoc = floatval($opening['op_bank_local']) + $bankLocRec - $bankLocPay;
    $finalCashUsd = floatval($opening['op_cash_usd']) + $usdRec - $usdPay;

    echo json_encode([
        'success' => true,
        'opening' => $opening,
        'stock' => [
            'purchase_qty' => $purQty, 'purchase_val' => $purVal,
            'sales_qty' => $saleQty, 'sales_val' => $saleVal,
            'closing_qty' => $closingQty, 'closing_val' => $closingStockValAtCost, 
            'avg_price' => $avgCost
        ],
        'pnl' => [
            'sales_revenue' => $saleVal,
            'cogs' => $cogs,
            'gross_profit' => $grossProfit,
            'brokerage' => $totalBrokerage,
            'net_profit' => $netProfit
        ],
        'cash' => [ 'in'=>$cashLocRec, 'out'=>$cashLocPay, 'closing'=>$finalCashLoc ],
        'bank' => [ 'in'=>$bankLocRec, 'out'=>$bankLocPay, 'closing'=>$finalBankLoc ],
        'usd'  => [ 'in'=>$usdRec, 'out'=>$usdPay, 'closing'=>$finalCashUsd ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>