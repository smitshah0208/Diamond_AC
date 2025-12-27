<?php include "config/db.php"; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cash/Bank Entry</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/cash_bank.css">
<!-- <style>
    /* ... Previous CSS ... */
    :root { --primary: #4f46e5; --bg: #f3f4f6; --text: #1f2937; --border: #e5e7eb; }
    * { box-sizing: border-box; font-family: 'Inter', sans-serif; }
    body { background: var(--bg); padding: 20px; }
    .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 12px; }
    .header { display: flex; justify-content: space-between; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px; }
    .btn-add { background: var(--primary); color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; }
    
    /* Table Actions */
    .btn-icon { background: none; border: none; cursor: pointer; font-size: 16px; margin: 0 5px; }
    .btn-edit { color: #2563eb; }
    .btn-del { color: #dc2626; }

    /* Modal Styles */
    .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; }
    .modal-content { background: white; width: 90%; max-width: 800px; margin: 50px auto; padding: 30px; border-radius: 12px; }
    .close-btn { float: right; cursor: pointer; font-size: 24px; color: #999; }
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px; }
    .full-width { grid-column: span 2; }
    label { display: block; font-size: 12px; font-weight: 600; color: #6b7280; margin-bottom: 5px; text-transform: uppercase; }
    input, select, textarea { width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 6px; }
    
    /* Radio Button Group */
    .radio-group { display: flex; gap: 20px; margin-bottom: 15px; padding: 10px; background: #f9fafb; border-radius: 6px; }
    .radio-label { display: flex; align-items: center; gap: 5px; cursor: pointer; font-weight: 500; font-size: 14px; }
    
    /* Autocomplete */
    .autocomplete { position: relative; }
    .autocomplete-list { position: absolute; top: 100%; left: 0; right: 0; border: 1px solid #ddd; background: #fff; z-index: 99; max-height: 150px; overflow-y: auto; display: none; }
    .autocomplete-list.active { display: block; }
    .autocomplete-item { padding: 10px; cursor: pointer; border-bottom: 1px solid #eee; }
    .autocomplete-item:hover { background-color: #f3f4f6; }
</style> -->
</head>
<body>

<div class="container">
    <div class="header">
        <h2>Cash & Bank Book</h2>
        <button class="btn-add" onclick="openModal()">+ Add New Entry</button>
    </div>

    <h3>Session Entries</h3>
    <table id="previewTable" style="width:100%; border-collapse:collapse; margin-top:10px;">
        <thead>
            <tr style="background:#f9fafb; text-align:left;">
                <th style="padding:10px;">Date</th>
                <th style="padding:10px;">Particulars</th>
                <th style="padding:10px;">Ref Invoice</th>
                <th style="padding:10px; text-align:right;">Debit (₹)</th>
                <th style="padding:10px; text-align:right;">Credit (₹)</th>
                <th style="padding:10px; text-align:center;">Action</th>
            </tr>
        </thead>
        <tbody>
            <tr><td colspan="6" class="empty-row" style="padding:20px; text-align:center; color:#999;">No session entries.</td></tr>
        </tbody>
    </table>
</div>

<!-- ================= MODAL ================= -->
<div class="modal" id="entryModal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal()">&times;</span>
        <h2 id="modalTitle" style="margin-bottom: 20px;">New Transaction</h2>
        
        <form id="txnForm">
            <input type="hidden" id="editId" value=""> <!-- Stores ID if editing -->

            <div class="form-grid">
                <div>
                    <label>Date</label>
                    <input type="date" id="txnDate" required>
                </div>
                <div>
                    <label>A/C Type</label>
                    <select id="account_type">
                        <option value="Cash">Cash</option>
                        <option value="Bank">Bank</option>
                    </select>
                </div>
            </div>

            <!-- Transaction Category Toggle -->
            <div class="radio-group">
                <label class="radio-label">
                    <input type="radio" name="txnCat" value="GENERAL" checked onchange="toggleTxnType()"> 
                    General / Other
                </label>
                <label class="radio-label">
                    <input type="radio" name="txnCat" value="INVOICE" onchange="toggleTxnType()"> 
                    Against Invoice
                </label>
            </div>

            <!-- Invoice Search (Hidden by default) -->
            <div class="form-grid" id="invBox" style="display:none;">
                <div class="full-width autocomplete">
                    <label>Select Invoice *</label>
                    <input id="invSearch" placeholder="Type Invoice No..." autocomplete="off">
                    <div id="invSug" class="autocomplete-list"></div>
                </div>
            </div>

            <div class="form-grid">
                <div class="full-width">
                    <label>Description</label>
                    <textarea id="description" rows="2" placeholder="Enter details..."></textarea>
                </div>
            </div>

            <!-- Currency & Amounts -->
            <div class="form-grid">
                <div>
                    <label>Mode</label>
                    <select id="currMode"><option value="INR">INR Only</option><option value="BOTH">Multi-Currency</option></select>
                </div>
                <div><label>Conv. Rate</label><input type="number" step="0.01" id="convRate" disabled></div>
            </div>

            <div class="form-grid">
                <div><label>Debit ($)</label><input type="number" step="0.01" id="drUsd" disabled></div>
                <div><label>Debit (₹)</label><input type="number" step="0.01" id="drInr"></div>
            </div>
            <div class="form-grid">
                <div><label>Credit ($)</label><input type="number" step="0.01" id="crUsd" disabled></div>
                <div><label>Credit (₹)</label><input type="number" step="0.01" id="crInr"></div>
            </div>

            <button type="submit" class="btn-add" id="saveBtn" style="width:100%; margin-top:20px;">💾 Save Transaction</button>
        </form>
    </div>
</div>

<script src="assets/js/cash_bank.js"></script>
</body>
</html>