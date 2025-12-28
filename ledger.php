<?php include "config/db.php"; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ledger Report</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        
        h2 { margin-top: 0; color: #1e293b; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; }
        
        /* Filters */
        .filters { display: flex; gap: 15px; background: #f8fafc; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; align-items: center; flex-wrap: wrap; }
        .field { flex: 1; min-width: 150px; }
        .field label { display: block; font-size: 13px; font-weight: 600; color: #64748b; margin-bottom: 5px; }
        .field input, .field select { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; }
        
        /* Checkbox Style */
        .chk-field { display: flex; align-items: center; background: #e2e8f0; padding: 10px; border-radius: 6px; height: 42px; margin-top: 22px; cursor: pointer; }
        .chk-field input { width: auto; margin-right: 8px; cursor: pointer; }
        .chk-field label { margin: 0; cursor: pointer; }

        .btn { padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: 500; color: white; height: 42px; margin-top: 22px; }
        .btn-go { background: #0f172a; }
        .btn-print { background: #dc2626; margin-left: auto; display: none; }

        /* Autocomplete */
        .autocomplete { position: relative; }
        .autocomplete-list { position: absolute; top: 100%; left: 0; right: 0; border: 1px solid #ddd; background: #fff; z-index: 10; max-height: 200px; overflow-y: auto; display: none; border-radius: 0 0 6px 6px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .autocomplete-list.active { display: block; }
        .autocomplete-item { padding: 10px; cursor: pointer; border-bottom: 1px solid #eee; }
        .autocomplete-item:hover { background: #f1f5f9; }

        /* Ledger Table */
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 14px; }
        th, td { border: 1px solid #e2e8f0; padding: 10px; text-align: left; vertical-align: top; }
        th { background: #f8fafc; color: #334155; font-weight: 600; }
        
        .text-right { text-align: right; }
        .fw-bold { font-weight: bold; }
        .desc-box { font-size: 13px; color: #555; line-height: 1.4; }
        .item-row { display: block; font-family: monospace; font-size: 12px; color: #475569; }

        /* PDF / Print Styles */
        @media print {
            body { background: white; padding: 0; }
            .container { box-shadow: none; max-width: 100%; padding: 0; }
            .filters, .btn-print, h2 { display: none; }
            #printHeader { display: block !important; margin-bottom: 20px; text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; }
            table { width: 100%; border: 1px solid #000; }
            th, td { border: 1px solid #000; padding: 8px; }
        }
        #printHeader { display: none; }
    </style>
</head>
<body>

<div class="container">
    <div style="display:flex; align-items:center;">
        <h2>📒 Ledger Report</h2>
        <button class="btn btn-print" id="printBtn" onclick="window.print()">🖨️ Print / Save PDF</button>
    </div>

    <!-- PRINT HEADER -->
    <div id="printHeader">
        <h1 style="margin:0;">LEDGER ACCOUNT</h1>
        <h3 id="printPartyName" style="margin:5px 0;"></h3>
        <p id="printDateRange" style="margin:0;"></p>
    </div>

    <div class="filters">
        <div class="field" style="max-width: 120px;">
            <label>Ledger Type</label>
            <select id="ledgerType">
                <option value="PARTY">Party</option>
                <option value="BROKER">Broker</option>
            </select>
        </div>
        <div class="field autocomplete" style="flex: 2; min-width: 250px;">
            <label>Search Name</label>
            <input id="searchName" placeholder="Type name..." autocomplete="off">
            <div id="sugBox" class="autocomplete-list"></div>
        </div>
        
        <!-- All Time Checkbox -->
        <div class="field chk-field" style="flex:0; min-width: 100px;">
            <input type="checkbox" id="allTime">
            <label for="allTime">All Time</label>
        </div>

        <div class="field">
            <label>From Date</label>
            <input type="date" id="fromDate">
        </div>
        <div class="field">
            <label>To Date</label>
            <input type="date" id="toDate">
        </div>
        
        <div class="field" style="flex:0;">
            <button class="btn btn-go" onclick="generateLedger()">Generate</button>
        </div>
    </div>

    <table id="ledgerTable">
        <thead>
            <tr>
                <th style="width: 50px;">#</th>
                <th style="width: 100px;">Date</th>
                <th style="width: 80px;">Type</th>
                <th>Description</th>
                <th style="width: 120px;" class="text-right">Debit</th>
                <th style="width: 120px;" class="text-right">Credit</th>
                <th style="width: 120px;" class="text-right">Balance</th>
            </tr>
        </thead>
        <tbody>
            <tr><td colspan="7" style="text-align:center; padding: 20px;">Select filters and click Generate.</td></tr>
        </tbody>
        <tfoot>
            <tr style="background:#f1f5f9; font-weight:bold;">
                <td colspan="4" class="text-right">Total:</td>
                <td class="text-right" id="ftTotalDr">0.00</td>
                <td class="text-right" id="ftTotalCr">0.00</td>
                <td class="text-right" id="ftBalance">0.00</td>
            </tr>
        </tfoot>
    </table>
</div>

<script src="assets/js/ledger.js"></script>
</body>
</html>