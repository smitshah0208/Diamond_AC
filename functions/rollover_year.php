<?php
// functions/rollover_year.php
error_reporting(E_ALL);
ini_set('display_errors', 0);
include "../config/db.php";
header('Content-Type: application/json');

try {
    if (!$conn) throw new Exception("Database connection failed");

    $data = json_decode(file_get_contents('php://input'), true);
    $targetYear = $data['target_year']; // e.g., "2026-2027"

    // 1. Calculate Previous Year Label
    $parts = explode('-', $targetYear);
    $startYear = intval($parts[0]);
    $prevYearLabel = ($startYear - 1) . '-' . $startYear;

    // 2. Check if Target Year already has data
    $check = $conn->query("SELECT id FROM financial_openings WHERE financial_year = '$targetYear'");
    if ($check->num_rows > 0) {
        throw new Exception("Opening balances for $targetYear already exist! Please delete/edit manually.");
    }

    // 3. Get Previous Year's Data
    $sqlOp = "SELECT * FROM financial_openings WHERE financial_year = '$prevYearLabel'";
    $resOp = $conn->query($sqlOp);
    $prevOp = $resOp->fetch_assoc();

    if (!$prevOp) {
        throw new Exception("Previous year ($prevYearLabel) data not found.");
    }

    $fyStart = $prevOp['start_date'];
    $fyEnd   = $prevOp['end_date'];

    // ---------------------------------------------------------
    // A. STOCK CALCULATION (Weighted Average Cost Method)
    // ---------------------------------------------------------
    
    // 1. Get Purchase & Sales Data
    // We calculate Values from Invoice Header (txn) and Qty from Items
    
    // Get Values
    $sqlVal = "SELECT txn_type, SUM(net_amount_local) as val FROM invoice_txn 
               WHERE txn_date BETWEEN '$fyStart' AND '$fyEnd' GROUP BY txn_type";
    $resVal = $conn->query($sqlVal);
    $purVal=0; $saleVal=0;
    while($row = $resVal->fetch_assoc()){
        if($row['txn_type']=='PU') $purVal=floatval($row['val']);
        // We don't need Sale Value for Stock Cost calculation, only for P&L
    }

    // Get Quantities
    $sqlQty = "SELECT t.txn_type, SUM(i.qty) as qty 
               FROM invoice_items i JOIN invoice_txn t ON t.invoice_num = i.invoice_id
               WHERE t.txn_date BETWEEN '$fyStart' AND '$fyEnd' GROUP BY t.txn_type";
    $resQty = $conn->query($sqlQty);
    $purQty=0; $saleQty=0;
    while($row = $resQty->fetch_assoc()){
        if($row['txn_type']=='PU') $purQty=floatval($row['qty']);
        if($row['txn_type']=='SA') $saleQty=floatval($row['qty']);
    }

    // --- CRITICAL FIX: CALCULATE WEIGHTED AVERAGE COST ---
    $totalInputQty = floatval($prevOp['op_stock_qty']) + $purQty;
    $totalInputVal = floatval($prevOp['op_stock_val']) + $purVal;
    
    // Calculate Average Cost per unit
    $avgCost = ($totalInputQty > 0) ? ($totalInputVal / $totalInputQty) : 0;

    // Calculate Closing Qty
    $closingQty = $totalInputQty - $saleQty;

    // Calculate Closing Value (At Cost)
    $closingValAtCost = $closingQty * $avgCost;

    // ---------------------------------------------------------
    // B. CASH / BANK / USD CALCULATION
    // ---------------------------------------------------------
    $sqlCash = "SELECT account_type, payment_currency, transaction_type, 
                SUM(dr_local) as dr, SUM(cr_local) as cr, SUM(dr_usd) as dru, SUM(cr_usd) as cru
                FROM cash_bank_entries WHERE txn_date BETWEEN '$fyStart' AND '$fyEnd'
                GROUP BY account_type, payment_currency, transaction_type";
    $resCash = $conn->query($sqlCash);

    $cashNet=0; $bankNet=0; $usdNet=0;
    while($row = $resCash->fetch_assoc()){
        $acc = $row['account_type']; $cur = $row['payment_currency']; $type = $row['transaction_type'];
        
        if($acc=='Cash' && $cur=='Local') $cashNet += ($type=='Receipt' ? $row['cr'] : -$row['dr']);
        if($acc=='Bank') $bankNet += ($type=='Receipt' ? $row['cr'] : -$row['dr']);
        if($acc=='Cash' && $cur=='Dollar') $usdNet += ($type=='Receipt' ? $row['cru'] : -$row['dru']);
    }

    $newOpCash = floatval($prevOp['op_cash_local']) + $cashNet;
    $newOpBank = floatval($prevOp['op_bank_local']) + $bankNet;
    $newOpUsd  = floatval($prevOp['op_cash_usd']) + $usdNet;

    // ---------------------------------------------------------
    // 4. INSERT INTO DATABASE
    // ---------------------------------------------------------
    $newStart = ($startYear) . '-04-01';
    $newEnd   = ($startYear + 1) . '-03-31';

    $ins = $conn->prepare("INSERT INTO financial_openings 
        (financial_year, start_date, end_date, op_stock_qty, op_stock_val, op_cash_local, op_bank_local, op_cash_usd) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    $ins->bind_param("ssdddddd", 
        $targetYear, $newStart, $newEnd, 
        $closingQty, $closingValAtCost, $newOpCash, $newOpBank, $newOpUsd
    );

    if($ins->execute()){
        echo json_encode(['success'=>true, 'message'=>"Successfully imported closing balance from $prevYearLabel"]);
    } else {
        throw new Exception($conn->error);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>