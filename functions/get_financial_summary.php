<?php
// functions/get_financial_summary.php
error_reporting(E_ALL);
ini_set('display_errors', 0);
include "../config/db.php";
header('Content-Type: application/json');

try {
    if (!$conn)
        throw new Exception("Database connection failed");

    // 1. Get Financial Year
    $fyLabel = $_GET['fy_label'] ?? '2025-2026';

    // ---------------------------------------------------------
    // A. FETCH OPENING BALANCES
    // ---------------------------------------------------------
    $sqlOpen = "SELECT * FROM financial_openings WHERE financial_year = ?";
    $stmt = $conn->prepare($sqlOpen);
    $stmt->bind_param("s", $fyLabel);
    $stmt->execute();
    $opening = $stmt->get_result()->fetch_assoc();

    // Default dates if not found
    if (!$opening) {
        $opening = [
            'start_date' => date('Y-04-01'),
            'end_date' => date('Y-03-31', strtotime('+1 year')),
            'op_stock_qty' => 0,
            'op_stock_val' => 0,
            'op_cash_local' => 0,
            'op_bank_local' => 0,
            'op_cash_usd' => 0
        ];
    }

    $fyStart = $opening['start_date'];
    $fyEnd = $opening['end_date'];

    // ---------------------------------------------------------
    // B. STOCK CALCULATION (Unchanged)
    // ---------------------------------------------------------
    // ---------------------------------------------------------
    // B. CALCULATE STOCK (CORRECTED LOGIC)
    // ---------------------------------------------------------

    // 1. Get Values (From Header Table Only - To avoid duplicates)
    $sqlVal = "SELECT txn_type, SUM(net_amount_local) as total_val 
               FROM invoice_txn 
               WHERE txn_date BETWEEN ? AND ? 
               GROUP BY txn_type";

    $stmtVal = $conn->prepare($sqlVal);
    $stmtVal->bind_param("ss", $fyStart, $fyEnd);
    $stmtVal->execute();
    $resVal = $stmtVal->get_result();

    $purVal = 0;
    $saleVal = 0;
    while ($row = $resVal->fetch_assoc()) {
        if ($row['txn_type'] === 'PU')
            $purVal = floatval($row['total_val']);
        elseif ($row['txn_type'] === 'SA')
            $saleVal = floatval($row['total_val']);
    }

    // 2. Get Quantities (From Items Table)
    $sqlQty = "SELECT t.txn_type, SUM(i.qty) as total_qty 
               FROM invoice_items i
               JOIN invoice_txn t ON i.invoice_id = t.invoice_num
               WHERE t.txn_date BETWEEN ? AND ?
               GROUP BY t.txn_type";

    $stmtQty = $conn->prepare($sqlQty);
    $stmtQty->bind_param("ss", $fyStart, $fyEnd);
    $stmtQty->execute();
    $resQty = $stmtQty->get_result();

    $purQty = 0;
    $saleQty = 0;
    while ($row = $resQty->fetch_assoc()) {
        if ($row['txn_type'] === 'PU')
            $purQty = floatval($row['total_qty']);
        elseif ($row['txn_type'] === 'SA')
            $saleQty = floatval($row['total_qty']);
    }

    // 3. Final Calculation
    $closingQty = floatval($opening['op_stock_qty']) + $purQty - $saleQty;
    $closingVal = floatval($opening['op_stock_val']) + $purVal - $saleVal;
    $avgPrice = ($closingQty > 0) ? ($closingVal / $closingQty) : 0;
    // ---------------------------------------------------------
    // C. CASH & BANK SPLIT (Receipts vs Payments)
    // ---------------------------------------------------------
    $sqlCash = "
    SELECT 
        account_type, 
        payment_currency, 
        transaction_type, 
        SUM(dr_local) as sum_dr_local, 
        SUM(cr_local) as sum_cr_local,
        SUM(dr_usd) as sum_dr_usd, 
        SUM(cr_usd) as sum_cr_usd
    FROM cash_bank_entries
    WHERE txn_date BETWEEN ? AND ?
    GROUP BY account_type, payment_currency, transaction_type
    ";

    $stmtCash = $conn->prepare($sqlCash);
    $stmtCash->bind_param("ss", $fyStart, $fyEnd);
    $stmtCash->execute();
    $resCash = $stmtCash->get_result();

    // Init Split Variables
    $cashLocRec = 0;
    $cashLocPay = 0;
    $bankLocRec = 0;
    $bankLocPay = 0;
    $usdRec = 0;
    $usdPay = 0;

    while ($row = $resCash->fetch_assoc()) {
        $acc = $row['account_type'];
        $cur = $row['payment_currency'];
        $type = $row['transaction_type'];

        // 1. CASH LOCAL
        if ($acc === 'Cash' && $cur === 'Local') {
            if ($type === 'Receipt')
                $cashLocRec += floatval($row['sum_cr_local']);
            elseif ($type === 'Payment')
                $cashLocPay += floatval($row['sum_dr_local']);
        }

        // 2. BANK LOCAL (Bank always uses Local columns)
        if ($acc === 'Bank') {
            if ($type === 'Receipt')
                $bankLocRec += floatval($row['sum_cr_local']);
            elseif ($type === 'Payment')
                $bankLocPay += floatval($row['sum_dr_local']);
        }

        // 3. DOLLARS (Uses USD columns)
        if ($acc === 'Cash' && $cur === 'Dollar') {
            if ($type === 'Receipt')
                $usdRec += floatval($row['sum_cr_usd']);
            elseif ($type === 'Payment')
                $usdPay += floatval($row['sum_dr_usd']);
        }
    }

    // Calculate Closings
    $finalCashLoc = floatval($opening['op_cash_local']) + $cashLocRec - $cashLocPay;
    $finalBankLoc = floatval($opening['op_bank_local']) + $bankLocRec - $bankLocPay;
    $finalCashUsd = floatval($opening['op_cash_usd']) + $usdRec - $usdPay;

    // ---------------------------------------------------------
    // RETURN DATA
    // ---------------------------------------------------------
    echo json_encode([
        'success' => true,
        'opening' => $opening,
        'stock' => [
            'purchase_qty' => $purQty,
            'purchase_val' => $purVal,
            'sales_qty' => $saleQty,
            'sales_val' => $saleVal,
            'closing_qty' => $closingQty,
            'closing_val' => $closingVal,
            'avg_price' => $avgPrice
        ],
        'cash' => [
            'in' => $cashLocRec,
            'out' => $cashLocPay,
            'closing' => $finalCashLoc
        ],
        'bank' => [
            'in' => $bankLocRec,
            'out' => $bankLocPay,
            'closing' => $finalBankLoc
        ],
        'usd' => [
            'in' => $usdRec,
            'out' => $usdPay,
            'closing' => $finalCashUsd
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>