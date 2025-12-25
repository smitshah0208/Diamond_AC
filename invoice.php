<?php include "config/db.php"; ?>
<!DOCTYPE html>
<html>
<head>
<title>Invoice Entry</title>
<link rel="stylesheet" href="assets/css/invoice.css">
</head>
<body>

<!-- ================= HEADER ================= -->
<div class="box">
<h2>Invoice Entry</h2>

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

<div class="row">
<div class="field autocomplete">
<label>Party Name *</label>
<input id="party" placeholder="Search or enter party name..." autocomplete="off">
<div id="partySug" class="autocomplete-list"></div>
</div>

<div class="field autocomplete">
<label>Broker Name</label>
<input id="broker" placeholder="Search or enter broker name..." autocomplete="off">
<div id="brokerSug" class="autocomplete-list"></div>
</div>
</div>

<div class="row">
<div class="field">
<label>Credit Days</label>
<input type="number" id="credit" value="0" min="0">
</div>

<div class="field">
<label>Due Date</label>
<input type="date" id="due" readonly>
</div>
</div>

<div class="row">
<div class="field">
<label>Cal1 % (Optional)</label>
<input type="number" id="cal1" value="0" step="0.01">
</div>
<div class="field">
<label>Cal2 % (Optional)</label>
<input type="number" id="cal2" value="0" step="0.01">
</div>
<div class="field">
<label>Cal3 % (Optional)</label>
<input type="number" id="cal3" value="0" step="0.01">
</div>
<div class="field">
<label>Brokerage % *</label>
<input type="number" id="brokerPct" value="0" step="0.01">
</div>
<div class="field">
<label>Tax %</label>
<input type="number" id="tax" value="0" step="0.01">
</div>
</div>

<div class="row">
<div class="field" style="flex:1">
<label>Description</label>
<textarea id="description" rows="2" placeholder="Enter invoice description..."></textarea>
</div>
</div>

<button onclick="openModal()">+ Add Grid Items</button>
</div>

<!-- ================= GRID ================= -->
<div class="box">
<h3>Invoice Items</h3>
<table id="grid">
<thead>
<tr>
<th>Currency</th><th>Qty</th><th>Rate $</th><th>Rate ₹</th>
<th>Amount $</th><th>Amount ₹</th><th>Action</th>
</tr>
</thead>
<tbody></tbody>
</table>

<div class="calculation-section">
<div class="calc-row">
<span class="calc-label">Base Total:</span>
<span class="calc-value" id="baseTotal">₹ 0.00</span>
</div>
<div class="calc-row">
<span class="calc-label">After Cal1 (<span id="cal1Display">0</span>%):</span>
<span class="calc-value" id="afterCal1">₹ 0.00</span>
</div>
<div class="calc-row">
<span class="calc-label">After Cal2 (<span id="cal2Display">0</span>%):</span>
<span class="calc-value" id="afterCal2">₹ 0.00</span>
</div>
<div class="calc-row">
<span class="calc-label">After Cal3 (<span id="cal3Display">0</span>%):</span>
<span class="calc-value" id="afterCal3">₹ 0.00</span>
</div>
<div class="calc-row gross-row">
<span class="calc-label"><strong>Gross Amount:</strong></span>
<span class="calc-value"><strong id="grossInr">₹ 0.00</strong></span>
</div>
<div class="calc-row info-row">
<span class="calc-label">Brokerage (<span id="brokerPctDisplay">0</span>%):</span>
<span class="calc-value" id="brokerAmt">₹ 0.00</span>
</div>
<div class="calc-row">
<span class="calc-label">Tax (<span id="taxDisplay">0</span>%):</span>
<span class="calc-value" id="taxAmt">₹ 0.00</span>
</div>
<div class="calc-row net-row">
<span class="calc-label"><strong>Net Amount:</strong></span>
<span class="calc-value"><strong id="netInr">₹ 0.00</strong></span>
</div>
</div>

<div style="margin-top:20px">
<button onclick="saveInvoice()" style="background:#28a745;font-size:16px;padding:10px 30px">💾 Save Invoice</button>
<button onclick="resetForm()" style="background:#dc3545;font-size:16px;padding:10px 30px;margin-left:10px">🔄 Reset Form</button>
</div>
</div>

<!-- ================= MODAL ================= -->
<div class="modal" id="modal">
<div class="modal-content">
<h3>Add Invoice Item</h3>

<div class="row">
<div class="field">
<label>Currency *</label>
<select id="mcur">
<option value="">-- Select --</option>
<option value="BOTH">₹ & $</option>
<option value="INR">₹ Only</option>
</select>
</div>

<div class="field">
<label>Conversion Rate</label>
<input type="number" id="conv" disabled step="0.0001">
</div>
</div>

<div class="row">
<div class="field">
<label>Quantity *</label>
<input type="number" id="mqty" step="0.01">
</div>
<div class="field">
<label>Rate $ *</label>
<input type="number" id="mrateUsd" disabled step="0.01">
</div>
<div class="field">
<label>Rate ₹ *</label>
<input type="number" id="mrateInr" disabled step="0.01">
</div>
</div>

<div class="row">
<div class="field">
<label>Amount $</label>
<input id="musd" readonly>
</div>
<div class="field">
<label>Amount ₹</label>
<input id="minr" readonly>
</div>
</div>

<div class="modal-buttons">
<button onclick="addRow()">✓ Add Item</button>
<button onclick="closeModal()" style="background:#666">✕ Cancel</button>
</div>

</div>
</div>

<script src="assets/js/invoice.js"></script>
</body>
</html>