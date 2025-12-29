<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f8fafc;
            --card: #ffffff;
            --text: #1e293b;
            --border: #e2e8f0;
            --purple: #8b5cf6;
            --green: #10b981;
            --blue: #3b82f6;
            --orange: #f59e0b;
        }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); padding: 30px; margin: 0; }
        .container { max-width: 1200px; margin: 0 auto; }
        
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .header h1 { margin: 0; font-size: 26px; }
        .btn-set { background: #334155; color: white; border: none; padding: 10px 18px; border-radius: 8px; cursor: pointer; font-weight: 600; }
        
        .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 25px; }
        
        /* CARD DESIGN */
        .card { background: var(--card); border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid var(--border); }
        .card-head { padding: 15px 20px; font-weight: 700; font-size: 16px; display: flex; align-items: center; gap: 10px; color: white; }
        .card-body { padding: 0; }
        
        .bg-purple { background: var(--purple); }
        .bg-green { background: var(--green); }
        .bg-blue { background: var(--blue); }
        .bg-orange { background: var(--orange); }

        .data-row { display: flex; justify-content: space-between; padding: 12px 20px; border-bottom: 1px solid var(--border); font-size: 14px; }
        .data-row.opening { background: #fdfbf7; color: #64748b; font-size: 13px; font-weight: 500; }
        .data-row.closing { background: #f1f5f9; font-weight: 700; font-size: 16px; border-top: 2px solid var(--border); border-bottom: none; }
        
        .val-plus { color: var(--green); font-weight: 600; }
        .val-minus { color: #dc2626; font-weight: 600; }
        
        /* Modal */
        .modal { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.5); z-index: 1000; }
        .modal-content { background: white; width: 500px; margin: 5% auto; padding: 25px; border-radius: 12px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-size: 12px; font-weight: 700; text-transform: uppercase; color: #64748b; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; font-family: inherit; }
        .actions { text-align: right; margin-top: 20px; }
        
        .price-badge { display: inline-block; padding: 4px 8px; background: #e0e7ff; color: #4338ca; border-radius: 4px; font-size: 12px; font-weight: 600; margin-top: 4px; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <div>
            <h1>Financial Dashboard</h1>
            <div style="margin-top:5px; color:#64748b;">
                Viewing Data for FY: 
                <select id="fySelect" onchange="loadData()" style="padding:4px; border-radius:4px; border:1px solid #ccc; font-weight:600;">
                    <option value="2025-2026">2025-2026</option>
                    <option value="2026-2027">2026-2027</option>
                </select>
            </div>
        </div>
        <button class="btn-set" onclick="openModal()">⚙️ Set Opening Balance</button>
    </div>

    <div class="dashboard-grid">
        
        <!-- 1. STOCK CARD -->
        <div class="card">
            <div class="card-head bg-purple">📦 Stock Position</div>
            <div class="card-body">
                <div class="data-row opening">
                    <span>Opening Stock</span>
                    <div style="text-align:right">
                        <div>Qty: <span id="op_stk_qty">0</span></div>
                        <div>Val: <span id="op_stk_val">0.00</span></div>
                    </div>
                </div>
                <div class="data-row">
                    <span>Add: Purchase (PU)</span>
                    <div style="text-align:right" class="val-plus">
                        <div>+<span id="pur_qty">0</span></div>
                        <div>+<span id="pur_val">0.00</span></div>
                    </div>
                </div>
                <div class="data-row">
                    <span>Less: Sales (SA)</span>
                    <div style="text-align:right" class="val-minus">
                        <div>-<span id="sale_qty">0</span></div>
                        <div>-<span id="sale_val">0.00</span></div>
                    </div>
                </div>
                <div class="data-row closing">
                    <span>Current Stock</span>
                    <div style="text-align:right">
                        <div id="close_stk_qty">0</div>
                        <div id="close_stk_val">0.00</div>
                    </div>
                </div>
                <div style="padding:10px 20px; text-align:center; background:#f8fafc; border-top:1px dashed #e2e8f0;">
                    <span class="price-badge">Avg Price: <span id="avg_price">0.00</span> / qty</span>
                </div>
            </div>
        </div>

        <!-- 2. CASH LOCAL -->
        <div class="card">
            <div class="card-head bg-green">💵 Cash (Local)</div>
            <div class="card-body">
                <div class="data-row opening">
                    <span>Opening Balance</span>
                    <span id="op_cash">0.00</span>
                </div>
                <div class="data-row">
                    <span>Add: Total Receipts</span>
                    <span class="val-plus">+<span id="cash_in">0.00</span></span>
                </div>
                <div class="data-row">
                    <span>Less: Total Payments</span>
                    <span class="val-minus">-<span id="cash_out">0.00</span></span>
                </div>
                <div class="data-row closing" style="color:#059669;">
                    <span>Closing Cash</span>
                    <span id="close_cash">0.00</span>
                </div>
            </div>
        </div>

        <!-- 3. BANK LOCAL -->
        <div class="card">
            <div class="card-head bg-blue">🏦 Bank Balance</div>
            <div class="card-body">
                <div class="data-row opening">
                    <span>Opening Balance</span>
                    <span id="op_bank">0.00</span>
                </div>
                <div class="data-row">
                    <span>Add: Total Receipts</span>
                    <span class="val-plus">+<span id="bank_in">0.00</span></span>
                </div>
                <div class="data-row">
                    <span>Less: Total Payments</span>
                    <span class="val-minus">-<span id="bank_out">0.00</span></span>
                </div>
                <div class="data-row closing" style="color:#2563eb;">
                    <span>Closing Bank</span>
                    <span id="close_bank">0.00</span>
                </div>
            </div>
        </div>

        <!-- 4. DOLLAR HOLDINGS -->
        <div class="card">
            <div class="card-head bg-orange">💲 Dollar Cash ($)</div>
            <div class="card-body">
                <div class="data-row opening">
                    <span>Opening Balance</span>
                    <span>$<span id="op_usd">0.00</span></span>
                </div>
                <div class="data-row">
                    <span>Add: Total Receipts</span>
                    <span class="val-plus">+$<span id="usd_in">0.00</span></span>
                </div>
                <div class="data-row">
                    <span>Less: Total Payments</span>
                    <span class="val-minus">-$<span id="usd_out">0.00</span></span>
                </div>
                <div class="data-row closing" style="color:#d97706;">
                    <span>Closing Dollars</span>
                    <span>$<span id="close_usd">0.00</span></span>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- SETTINGS MODAL -->
<div id="opModal" class="modal">
    <div class="modal-content">
        <h2 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px;">Setup Opening Balances</h2>
        <input type="hidden" id="m_fy">
        
        <div class="form-grid">
            <div class="form-group">
                <label>Start Date</label>
                <input type="date" id="m_start" class="form-control">
            </div>
            <div class="form-group">
                <label>End Date</label>
                <input type="date" id="m_end" class="form-control">
            </div>
        </div>
        <hr style="border:0; border-top:1px dashed #ccc; margin:15px 0;">
        
        <div class="form-grid">
            <div class="form-group">
                <label style="color:#8b5cf6">Stock Qty</label>
                <input type="number" id="m_qty" class="form-control" step="0.01">
            </div>
            <div class="form-group">
                <label style="color:#8b5cf6">Stock Value (Local)</label>
                <input type="number" id="m_val" class="form-control" step="0.01">
            </div>
        </div>

        <div class="form-group" style="margin-top:10px;">
            <label style="color:#10b981">Cash Opening (Local)</label>
            <input type="number" id="m_cash" class="form-control" step="0.01">
        </div>
        <div class="form-group">
            <label style="color:#3b82f6">Bank Opening (Local)</label>
            <input type="number" id="m_bank" class="form-control" step="0.01">
        </div>
        <div class="form-group">
            <label style="color:#f59e0b">Dollar Opening ($)</label>
            <input type="number" id="m_usd" class="form-control" step="0.01">
        </div>

        <div class="actions">
            <button class="btn-set" style="background:#cbd5e1; color:#333;" onclick="closeModal()">Cancel</button>
            <button class="btn-set" style="background:#0f172a;" onclick="saveOpening()">Save Changes</button>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', loadData);

    function loadData() {
        const fy = document.getElementById('fySelect').value;

        // Fetch Data
        fetch(`functions/get_financial_summary.php?fy_label=${fy}`)
        .then(r => r.json())
        .then(data => {
            if(!data.success) { alert("Error: " + data.message); return; }

            const op = data.opening;
            const st = data.stock;
            const ca = data.cash;
            const ba = data.bank;
            const us = data.usd; // New Key

            // STOCK
            setVal('op_stk_qty', op.op_stock_qty);
            setVal('op_stk_val', op.op_stock_val);
            setVal('pur_qty', st.purchase_qty);
            setVal('pur_val', st.purchase_val);
            setVal('sale_qty', st.sales_qty);
            setVal('sale_val', st.sales_val);
            setVal('close_stk_qty', st.closing_qty);
            setVal('close_stk_val', st.closing_val);
            setVal('avg_price', st.avg_price);

            // CASH LOCAL
            setVal('op_cash', op.op_cash_local);
            setVal('cash_in', ca.in);
            setVal('cash_out', ca.out);
            setVal('close_cash', ca.closing);

            // BANK LOCAL
            setVal('op_bank', op.op_bank_local);
            setVal('bank_in', ba.in);
            setVal('bank_out', ba.out);
            setVal('close_bank', ba.closing);

            // DOLLARS
            setVal('op_usd', op.op_cash_usd);
            setVal('usd_in', us.in);
            setVal('usd_out', us.out);
            setVal('close_usd', us.closing);

            // FILL MODAL
            document.getElementById('m_fy').value = fy;
            document.getElementById('m_start').value = op.start_date || '';
            document.getElementById('m_end').value = op.end_date || '';
            document.getElementById('m_qty').value = op.op_stock_qty;
            document.getElementById('m_val').value = op.op_stock_val;
            document.getElementById('m_cash').value = op.op_cash_local;
            document.getElementById('m_bank').value = op.op_bank_local;
            document.getElementById('m_usd').value = op.op_cash_usd;
        });
    }

    function saveOpening() {
        const payload = {
            financial_year: document.getElementById('m_fy').value,
            start_date: document.getElementById('m_start').value,
            end_date: document.getElementById('m_end').value,
            qty: document.getElementById('m_qty').value,
            val: document.getElementById('m_val').value,
            cash: document.getElementById('m_cash').value,
            bank: document.getElementById('m_bank').value,
            usd: document.getElementById('m_usd').value
        };

        fetch('functions/save_opening.php', { method: 'POST', body: JSON.stringify(payload) })
        .then(r => r.json())
        .then(d => {
            if(d.success) { closeModal(); loadData(); }
            else { alert("Failed to save."); }
        });
    }

    function setVal(id, num) {
        document.getElementById(id).innerText = parseFloat(num).toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2});
    }

    function openModal() { document.getElementById('opModal').style.display = 'block'; }
    function closeModal() { document.getElementById('opModal').style.display = 'none'; }
</script>

</body>
</html>