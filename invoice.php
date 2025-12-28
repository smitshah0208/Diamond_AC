<?php include "config/db.php"; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Entry</title>
    <link rel="stylesheet" href="assets/css/invoice.css">
    <style>
        .calc-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #eee; }
        .calc-label { font-weight: 600; color: #555; width: 40%; }
        .calc-val-box { width: 30%; text-align: right; font-family: monospace; font-size: 14px; }
        .calc-usd { color: #27ae60; } 
        .calc-local { color: #2980b9; } 
        .net-row { background: #e8f4fd; padding: 10px 5px; font-size: 16px; border-top: 2px solid #3399ff; margin-top: 5px; font-weight:bold; }
        input[readonly], input[disabled] { background-color: #f0f0f0; cursor: not-allowed; color: #666; }
    </style>
</head>
<body>

<div class="box">
    <h2>Invoice Entry</h2>

    <!-- Header -->
    <div class="row">
        <div class="field">
            <label>Invoice Type *</label>
            <select id="invType">
                <option value="PU">Purchase</option>
                <option value="SA">Sales</option>
            </select>
        </div>
        <div class="field">
            <label>Invoice No</label>
            <input id="invNo" readonly>
        </div>
        <div class="field">
            <label>Transaction Date *</label>
            <input type="date" id="txnDate" required>
        </div>
    </div>

    <!-- Parties -->
    <div class="row">
        <div class="field autocomplete">
            <label>Party Name *</label>
            <input id="party" placeholder="Search party..." autocomplete="off">
            <div id="partySug" class="autocomplete-list"></div>
        </div>
        <div class="field autocomplete">
            <label>Broker Name</label>
            <input id="broker" placeholder="Search broker..." autocomplete="off">
            <div id="brokerSug" class="autocomplete-list"></div>
        </div>
    </div>

    <!-- Dates & Credit -->
    <div class="row">
        <div class="field"><label>Credit Days</label><input type="number" id="credit" value="0" min="0"></div>
        <div class="field"><label>Due Date</label><input type="date" id="due" readonly></div>
    </div>

    <!-- Percentages -->
    <div class="row">
        <div class="field"><label>Cal1 %</label><input type="number" id="cal1" value="0" step="0.01"></div>
        <div class="field"><label>Cal2 %</label><input type="number" id="cal2" value="0" step="0.01"></div>
        <div class="field"><label>Cal3 %</label><input type="number" id="cal3" value="0" step="0.01"></div>
        <div class="field"><label>Brokerage %</label><input type="number" id="brokerPct" value="0" step="0.01"></div>
        <div class="field"><label>Tax %</label><input type="number" id="taxPct" value="0" step="0.01"></div>
    </div>

    <div class="row">
        <div class="field" style="flex:1"><label>Notes</label><textarea id="notes" rows="2"></textarea></div>
    </div>

    <button onclick="openModal()" style="margin-top:10px;">+ Add Grid Items</button>
</div>

<!-- Grid -->
<div class="box">
    <h3>Invoice Items</h3>
    <table id="grid">
        <thead>
            <tr>
                <th>Currency</th><th>Qty</th><th>Rate $</th><th>Rate (Local)</th>
                <th>Amount $</th><th>Amount (Local)</th><th>Action</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>

    <!-- Calculations -->
    <div class="calculation-section">
        <div class="calc-row">
            <span class="calc-label"></span>
            <span class="calc-val-box calc-usd"><strong>USD ($)</strong></span>
            <span class="calc-val-box calc-local"><strong>LOCAL</strong></span>
        </div>
        <div class="calc-row">
            <span class="calc-label">Base Total:</span>
            <span class="calc-val-box" id="baseTotalUsd">0.00</span>
            <span class="calc-val-box" id="baseTotalLocal">0.00</span>
        </div>
        <div class="calc-row">
            <span class="calc-label">Gross Amount:</span>
            <span class="calc-val-box calc-usd" id="grossUsd">0.00</span>
            <span class="calc-val-box calc-local" id="grossLocal">0.00</span>
        </div>
        <div class="calc-row">
            <span class="calc-label">Brokerage Amt:</span>
            <span class="calc-val-box" id="brokerAmtUsd">0.00</span>
            <span class="calc-val-box" id="brokerAmtLocal">0.00</span>
        </div>
        <div class="calc-row">
            <span class="calc-label">Tax Amt:</span>
            <span class="calc-val-box" id="taxAmtUsd">0.00</span>
            <span class="calc-val-box" id="taxAmtLocal">0.00</span>
        </div>
        <div class="calc-row net-row">
            <span class="calc-label">Net Amount:</span>
            <span class="calc-val-box calc-usd" id="netUsd">0.00</span>
            <span class="calc-val-box calc-local" id="netLocal">0.00</span>
        </div>
    </div>

    <div style="margin-top:20px; text-align:right;">
        <button onclick="resetForm()" style="background:#6c757d; margin-right:10px;">🔄 Reset</button>
        <button onclick="saveInvoice()" style="background:#28a745;">💾 Save Invoice</button>
    </div>
</div>

<!-- Modal -->
<div class="modal" id="modal">
    <div class="modal-content">
        <h3>Add Invoice Item</h3>
        <div class="row">
            <div class="field">
                <label>Currency *</label>
                <!-- JS hooks into this ID -->
                <select id="mcur">
                    <option value="">-- Select --</option>
                    <option value="BOTH">USD ($) & Local</option>
                    <option value="LOCAL">Local Only</option>
                </select>
            </div>
            <div class="field">
                <label>Conv. Rate</label>
                <input type="number" id="conv" disabled step="0.0001" placeholder="Ex: 84.50">
            </div>
        </div>
        <div class="row">
            <div class="field"><label>Quantity *</label><input type="number" id="mqty" step="0.01"></div>
            <div class="field"><label>Rate $</label><input type="number" id="mrateUsd" disabled step="0.01"></div>
            <div class="field"><label>Rate (Local)</label><input type="number" id="mrateLocal" disabled step="0.01"></div>
        </div>
        <div class="row">
            <div class="field"><label>Amount $</label><input id="musd" readonly></div>
            <div class="field"><label>Amount (Local)</label><input id="mlocal" readonly></div>
        </div>
        
        <div style="margin-top:15px; padding:10px; background:#fff3cd; border-left:4px solid #ffc107; font-size:13px;">
            <strong>Note:</strong> Select "USD & Local" to enter Dollar Rate + Conversion. Select "Local Only" for direct Local Rate.
        </div>

        <div class="modal-buttons">
            <button onclick="addRow()">✓ Add Item</button>
            <button onclick="closeModal()" style="background:#6c757d">✕ Cancel</button>
        </div>
    </div>
</div>

<script src="assets/js/invoice.js"></script>
</body>
</html>