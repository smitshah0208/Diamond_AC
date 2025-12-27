<?php include "config/db.php"; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cash/Bank Entry</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
    /* Inline CSS for the Wonderful UI */
    :root { --primary: #4f46e5; --primary-hover: #4338ca; --bg: #f3f4f6; --card: #ffffff; --text: #1f2937; --border: #e5e7eb; }
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
    body { background-color: var(--bg); display: flex; justify-content: center; padding: 40px 20px; }
    .container { background: var(--card); width: 100%; max-width: 900px; padding: 40px; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
    h2 { margin-bottom: 25px; color: var(--text); font-weight: 600; border-bottom: 2px solid var(--bg); padding-bottom: 15px; }
    
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .full-width { grid-column: span 2; }
    .field { margin-bottom: 5px; }
    label { display: block; font-size: 13px; font-weight: 500; color: #6b7280; margin-bottom: 6px; text-transform: uppercase; }
    
    input, select, textarea { width: 100%; padding: 12px 15px; border: 1px solid var(--border); border-radius: 8px; font-size: 15px; transition: 0.2s; }
    input:focus, select:focus, textarea:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }
    
    .currency-section { background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid var(--border); }
    .currency-header { font-weight: 600; color: var(--primary); margin-bottom: 15px; }
    
    .btn-group { margin-top: 30px; display: flex; gap: 15px; }
    button { flex: 1; padding: 14px; border: none; border-radius: 8px; font-size: 16px; font-weight: 500; cursor: pointer; }
    .btn-save { background: var(--primary); color: white; }
    .btn-save:hover { background: var(--primary-hover); }
    .btn-reset { background: white; border: 1px solid var(--border); color: #6b7280; }
    
    /* Loading/Status */
    button:disabled { opacity: 0.7; cursor: not-allowed; }
</style>
</head>
<body>

<div class="container">
    <h2>📝 Cash / Bank Entry</h2>

    <form id="txnForm">
        <div class="form-grid">
            <div class="field">
                <label>A/C Type</label>
                <select id="account_type">
                    <option value="Cash">Cash</option>
                    <option value="Bank">Bank</option>
                </select>
            </div>
            
            <div class="field">
                <label>Conversion Rate</label>
                <input type="number" step="0.01" id="convRate" placeholder="e.g. 84.50">
            </div>

            <div class="field full-width">
                <label>Description</label>
                <textarea id="description" rows="2" placeholder="Enter transaction details..."></textarea>
            </div>

            <!-- USD Section -->
            <div class="currency-section full-width">
                <div class="currency-header">🇺🇸 USD Amount ($)</div>
                <div class="form-grid">
                    <div class="field">
                        <label>Dr ($)</label>
                        <input type="number" step="0.01" id="drUsd" placeholder="0.00">
                    </div>
                    <div class="field">
                        <label>Cr ($)</label>
                        <input type="number" step="0.01" id="crUsd" placeholder="0.00">
                    </div>
                </div>
            </div>

            <!-- INR Section -->
            <div class="currency-section full-width">
                <div class="currency-header">🇮🇳 INR Amount (₹)</div>
                <div class="form-grid">
                    <div class="field">
                        <label>Dr (Rs)</label>
                        <input type="number" step="0.01" id="drInr" placeholder="0.00">
                    </div>
                    <div class="field">
                        <label>Cr (Rs)</label>
                        <input type="number" step="0.01" id="crInr" placeholder="0.00">
                    </div>
                </div>
            </div>
        </div>

        <div class="btn-group">
            <button type="button" class="btn-reset" onclick="resetForm()">Clear Form</button>
            <button type="submit" class="btn-save">💾 Save Transaction</button>
        </div>
    </form>
</div>

<script src="assets/js/cash_bank.js"></script>
</body>
</html>