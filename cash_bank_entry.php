<?php include "config/db.php"; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cash/Bank Entry</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/cash_bank.css">
    <style>
        /* Extra styles for this specific logic */
        .sub-section { background: #f0f9ff; padding: 15px; border-radius: 8px; border: 1px solid #bae6fd; margin-bottom: 15px; display: none; }
        .sub-section.active { display: block; }
        .entity-display { font-weight: bold; color: #1e40af; background: #dbeafe; padding: 8px; border-radius: 4px; margin-top: 5px; display: none; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h2>Cash & Bank Book</h2>
        <button class="btn-add" onclick="openModal()">+ Add New Entry</button>
    </div>

    <h3>Session Entries</h3>
    <table id="previewTable">
        <thead>
            <tr>
                <th style="width: 120px;">Date</th>
                <th>Particulars</th>
                <th>Ref Invoice</th>
                <th style="text-align:right;">Debit (₹)</th>
                <th style="text-align:right;">Credit (₹)</th>
                <th style="text-align:center; width: 100px;">Action</th>
            </tr>
        </thead>
        <tbody>
            <tr><td colspan="6" class="empty-row">No session entries.</td></tr>
        </tbody>
    </table>
</div>

<!-- ================= MODAL ================= -->
<div class="modal" id="entryModal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal()">&times;</span>
        <h2 id="modalTitle" style="margin-bottom: 20px;">New Transaction</h2>

        <form id="txnForm">
            <input type="hidden" id="editId" value="">

            <div class="form-grid">
                <div><label>Date</label><input type="date" id="txnDate" required></div>
                <div>
                    <label>A/C Type</label>
                    <select id="account_type">
                        <option value="Cash">Cash</option>
                        <option value="Bank">Bank</option>
                    </select>
                </div>
            </div>

            <!-- 1. CATEGORY SELECTION -->
            <div class="radio-group">
                <label class="radio-label">
                    <input type="radio" name="txnCat" value="GENERAL" checked onchange="handleTxnCatChange()"> 
                    General / Other
                </label>
                <label class="radio-label">
                    <input type="radio" name="txnCat" value="INVOICE" onchange="handleTxnCatChange()"> 
                    Against Invoice
                </label>
            </div>

            <!-- 2. GENERAL MODE SECTION -->
            <div id="generalSection" class="sub-section active">
                <label style="color:#1e40af; margin-bottom:10px;">Link this transaction to (Optional):</label>
                <div class="radio-group" style="background:white; border:1px solid #e5e7eb;">
                    <label class="radio-label"><input type="radio" name="genLinkType" value="NONE" checked onchange="handleGenLinkChange()"> None</label>
                    <label class="radio-label"><input type="radio" name="genLinkType" value="PARTY" onchange="handleGenLinkChange()"> Party</label>
                    <label class="radio-label"><input type="radio" name="genLinkType" value="BROKER" onchange="handleGenLinkChange()"> Broker</label>
                </div>
                
                <div id="genSearchBox" class="autocomplete" style="display:none;">
                    <label id="genSearchLabel">Search Name</label>
                    <input id="genSearchInput" placeholder="Type 2 letters to search..." autocomplete="off">
                    <div id="genSug" class="autocomplete-list"></div>
                </div>
            </div>

            <!-- 3. INVOICE MODE SECTION -->
            <div id="invoiceSection" class="sub-section">
                <div class="form-grid">
                    <div class="full-width autocomplete">
                        <label>Select Invoice *</label>
                        <input id="invSearch" placeholder="Type Invoice No..." autocomplete="off">
                        <div id="invSug" class="autocomplete-list"></div>
                    </div>
                </div>
                
                <div style="border-top:1px solid #bfdbfe; padding-top:10px;">
                    <label style="margin-bottom:8px;">Payment For / Received From:</label>
                    <div style="display:flex; gap:20px; margin-bottom:5px;">
                        <label class="radio-label"><input type="radio" name="invLinkType" value="PARTY" onchange="updateInvEntityDisplay()"> Party</label>
                        <label class="radio-label"><input type="radio" name="invLinkType" value="BROKER" onchange="updateInvEntityDisplay()"> Broker</label>
                    </div>
                    <div id="invEntityDisplay" class="entity-display"></div>
                </div>
            </div>

            <!-- Description -->
            <div class="form-grid">
                <div class="full-width">
                    <label>Description</label>
                    <textarea id="description" rows="2" placeholder="Enter details..."></textarea>
                </div>
            </div>

            <!-- Currency & Amounts -->
            <div class="form-grid">
                <div>
                    <label>Currency Mode</label>
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

            <button type="submit" class="btn-add" id="saveBtn" style="width:100%; margin-top:15px;">💾 Save Transaction</button>
        </form>
    </div>
</div>

<script src="assets/js/cash_bank.js"></script>
</body>
</html>