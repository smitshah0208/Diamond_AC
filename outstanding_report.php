<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Outstanding Report</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --bg-color: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --success-bg: #dcfce7;
            --success-text: #166534;
            --danger-bg: #fee2e2;
            --danger-text: #991b1b;
        }

        body { 
            font-family: 'Inter', sans-serif; 
            background-color: var(--bg-color); 
            color: var(--text-main); 
            margin: 0; 
            padding: 30px; 
        }

        .container { max-width: 1200px; margin: 0 auto; }

        .page-header { margin-bottom: 25px; }
        .page-header h1 { font-size: 26px; font-weight: 700; margin: 0; color: #1e293b; }
        .page-header p { color: var(--text-muted); margin: 5px 0 0 0; font-size: 14px; }

        /* Controls */
        .controls-card {
            background: var(--card-bg);
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            display: flex;
            gap: 20px;
            align-items: flex-end;
            margin-bottom: 30px;
            flex-wrap: wrap;
            border: 1px solid var(--border);
        }

        .input-group { display: flex; flex-direction: column; gap: 8px; }
        .input-group label {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .form-control {
            padding: 10px 14px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            background-color: #fff;
            min-width: 180px;
            height: 42px; /* Fixed height for alignment */
            box-sizing: border-box;
        }
        .form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }

        .btn-primary {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 0 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            height: 42px;
            transition: all 0.2s;
        }
        .btn-primary:hover { background-color: var(--primary-hover); transform: translateY(-1px); }
        .btn-primary:active { transform: translateY(0); }

        /* Report Sections */
        .report-section { display: none; animation: fadeIn 0.3s ease-in-out; }
        .report-section.active { display: block; }
        
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .table-card {
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .card-header {
            padding: 16px 24px;
            background: #fff;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .card-header h2 { font-size: 18px; font-weight: 600; margin: 0; display: flex; align-items: center; gap: 10px;}
        
        .h-receivable { color: var(--success-text); }
        .h-payable { color: var(--danger-text); }

        .table-responsive { width: 100%; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th { background: #f8fafc; color: var(--text-muted); font-weight: 600; text-align: left; padding: 14px 24px; border-bottom: 1px solid var(--border); white-space: nowrap; }
        td { padding: 14px 24px; border-bottom: 1px solid var(--border); color: var(--text-main); }
        tr:hover { background-color: #f8fafc; }

        .amount-col { text-align: right; font-family: 'Consolas', monospace; font-weight: 500; }
        .inv-link { color: var(--primary); font-weight: 600; text-decoration: none; cursor: pointer; border-bottom: 1px dotted transparent; }
        .inv-link:hover { border-bottom-color: var(--primary); }

        .badge { display: inline-flex; padding: 4px 10px; border-radius: 99px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .badge-paid { background: var(--success-bg); color: var(--success-text); }
        .badge-pending { background: var(--danger-bg); color: var(--danger-text); }
        .text-overdue { color: #dc2626; font-weight: 600; }

        .card-footer { background: #f8fafc; padding: 16px 24px; border-top: 1px solid var(--border); text-align: right; font-size: 16px; font-weight: 600; }
        .total-amount { font-family: monospace; font-size: 18px; margin-left: 10px; font-weight: 700; }

        /* Modal */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(2px); animation: fadeIn 0.2s; }
        .modal-content { background: #fff; margin: 5% auto; border-radius: 16px; width: 90%; max-width: 700px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); overflow: hidden; animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .modal-header { padding: 20px 24px; background: #f8fafc; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
        .modal-title { font-size: 18px; font-weight: 700; }
        .close-btn { font-size: 24px; color: var(--text-muted); cursor: pointer; }
        .close-btn:hover { color: var(--danger-text); }
        .modal-body { padding: 0; max-height: 60vh; overflow-y: auto; }
        .history-table th { background: #fff; border-bottom: 2px solid var(--border); position: sticky; top: 0; }
        .history-table td { padding: 12px 24px; }

    </style>
</head>
<body>

<div class="container">
    
    <div class="page-header">
        <h1>Outstanding Report</h1>
        <p>Track your receivables, payables, and commission history.</p>
    </div>

    <!-- Controls -->
    <div class="controls-card">
        <div class="input-group">
            <label>View For</label>
            <select id="viewMode" class="form-control" onchange="toggleControls()">
                <option value="PARTY">Party Outstanding</option>
                <option value="BROKER">Broker Outstanding</option>
            </select>
        </div>

        <!-- NEW: Sub-Filter for Party (Hidden for Broker) -->
        <div class="input-group" id="partyFilterGroup">
            <label>Report Category</label>
            <select id="partySubFilter" class="form-control">
                <option value="ALL">Show Both (Sales & Purchase)</option>
                <option value="SALES">Sales Receivables Only</option>
                <option value="PURCHASE">Purchase Payables Only</option>
            </select>
        </div>

        <div class="input-group">
            <label>From Date</label>
            <input type="date" id="dateFrom" class="form-control">
        </div>
        <div class="input-group">
            <label>To Date</label>
            <input type="date" id="dateTo" class="form-control">
        </div>
        <button class="btn-primary" onclick="fetchReport()" id="btnGen">Generate Report</button>
    </div>

    <!-- SECTION: RECEIVABLES (SALES) -->
    <div id="secReceivable" class="report-section">
        <div class="table-card">
            <div class="card-header">
                <h2 class="h-receivable">
                    <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="margin-right:8px"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12" /></svg>
                    Sales Receivables
                </h2>
                <span class="badge badge-paid" style="background:#dcfce7; color:#166534;">Inflow</span>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Party Name</th>
                            <th>Inv Date</th>
                            <th>Due Date</th>
                            <th class="amount-col">Bill Amt</th>
                            <th class="amount-col">Received</th>
                            <th class="amount-col">Balance</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="tbReceivable"></tbody>
                </table>
            </div>
            <div class="card-footer">
                Total Receivable: <span id="sumReceivable" class="total-amount" style="color:#166534">0.00</span>
            </div>
        </div>
    </div>

    <!-- SECTION: PAYABLES (PURCHASE / BROKERAGE) -->
    <div id="secPayable" class="report-section">
        <div class="table-card">
            <div class="card-header">
                <h2 id="titlePayable" class="h-payable">
                    <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="margin-right:8px"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6" /></svg>
                    Payables
                </h2>
                <span class="badge badge-pending" style="background:#fee2e2; color:#991b1b;">Outflow</span>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th id="colNameHeader">Party Name</th>
                            <th>Inv Date</th>
                            <th>Due Date</th>
                            <th class="amount-col" id="colAmtHeader">Bill Amt</th>
                            <th class="amount-col">Paid</th>
                            <th class="amount-col">Balance</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="tbPayable"></tbody>
                </table>
            </div>
            <div class="card-footer">
                Total Payable: <span id="sumPayable" class="total-amount" style="color:#991b1b">0.00</span>
            </div>
        </div>
    </div>

</div> <!-- End Container -->

<!-- MODAL: HISTORY -->
<div id="historyModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-title">Payment History: <span id="modalInvNum" style="color:var(--primary);"></span></div>
            <span class="close-btn" onclick="closeModal()">&times;</span>
        </div>
        <div class="modal-body">
            <table class="history-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Description</th>
                        <th style="text-align:right">Paid (Dr)</th>
                        <th style="text-align:right">Rcvd (Cr)</th>
                    </tr>
                </thead>
                <tbody id="historyTableBody"></tbody>
            </table>
            <div id="noHistoryMsg" style="padding:30px; text-align:center; color:#64748b; font-style:italic; display:none;">No records found.</div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const today = new Date();
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0); 
        
        document.getElementById('dateFrom').valueAsDate = firstDay;
        document.getElementById('dateTo').valueAsDate = lastDay;
        toggleControls(); // Set initial state
    });

    // Toggle dropdowns based on mode
    function toggleControls() {
        const mode = document.getElementById('viewMode').value;
        const partyFilter = document.getElementById('partyFilterGroup');

        if (mode === 'BROKER') {
            partyFilter.style.display = 'none'; // Hide Sales/Purchase select for Brokers
            document.getElementById('titlePayable').innerHTML = 
                `<svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="margin-right:8px"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg> Brokerage Commission`;
            document.getElementById('colNameHeader').innerText = "Broker Name";
            document.getElementById('colAmtHeader').innerText = "Commission Amt";
        } else {
            partyFilter.style.display = 'flex'; // Show for Party
            document.getElementById('titlePayable').innerHTML = 
                `<svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="margin-right:8px"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6" /></svg> Purchase Payables`;
            document.getElementById('colNameHeader').innerText = "Party Name";
            document.getElementById('colAmtHeader').innerText = "Bill Amount";
        }

        // Hide tables when switching modes to avoid confusion before clicking Generate
        document.getElementById('secReceivable').classList.remove('active');
        document.getElementById('secPayable').classList.remove('active');
    }

    function fetchReport() {
        const dFrom = document.getElementById('dateFrom').value;
        const dTo = document.getElementById('dateTo').value;
        const mode = document.getElementById('viewMode').value;
        const btn = document.getElementById('btnGen');

        if(!dFrom || !dTo) { alert("Please select both dates."); return; }

        btn.innerText = "Processing..."; btn.disabled = true;

        fetch(`functions/get_outstanding_report.php?from_date=${dFrom}&to_date=${dTo}&view_mode=${mode}`)
        .then(res => res.text())
        .then(text => {
            try { return JSON.parse(text); } 
            catch (e) { throw new Error("Server Error: " + text); }
        })
        .then(data => {
            btn.innerText = "Generate Report"; btn.disabled = false;
            if(data.error) { alert(data.message); return; }
            renderTables(data, mode);
        })
        .catch(err => {
            console.error(err);
            btn.innerText = "Generate Report"; btn.disabled = false;
            alert(err.message);
        });
    }

    function renderTables(data, mode) {
        const secRec = document.getElementById('secReceivable');
        const secPay = document.getElementById('secPayable');
        const partySubFilter = document.getElementById('partySubFilter').value;

        // Reset visibility
        secRec.classList.remove('active');
        secPay.classList.remove('active');

        if (mode === 'PARTY') {
            // Logic for Party: Check Sub Filter
            const showSales = (partySubFilter === 'ALL' || partySubFilter === 'SALES');
            const showPurchase = (partySubFilter === 'ALL' || partySubFilter === 'PURCHASE');

            if (showSales) {
                fillTable('tbReceivable', data.receivables);
                document.getElementById('sumReceivable').innerText = formatMoney(data.total_receivable);
                secRec.classList.add('active');
            }
            if (showPurchase) {
                fillTable('tbPayable', data.payables);
                document.getElementById('sumPayable').innerText = formatMoney(data.total_payable);
                secPay.classList.add('active');
            }

        } else {
            // Logic for Broker: Always show Payable (Commission)
            fillTable('tbPayable', data.payables);
            document.getElementById('sumPayable').innerText = formatMoney(data.total_payable);
            secPay.classList.add('active');
        }
    }

    function fillTable(tbodyId, rows) {
        const tbody = document.getElementById(tbodyId);
        tbody.innerHTML = '';

        if(!rows || rows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" style="text-align:center; padding:30px; color:#64748b;">No outstanding records found.</td></tr>';
            return;
        }

        const today = new Date();
        today.setHours(0,0,0,0); 

        rows.forEach(row => {
            const tr = document.createElement('tr');
            const dueDateObj = new Date(row.due_date);
            const isOverdue = dueDateObj < today && row.balance > 1; 
            const dueText = isOverdue ? `<span class="text-overdue">${row.due_date}<br><small>Overdue</small></span>` : row.due_date;
            
            const isPaid = row.balance <= 1;
            const badgeClass = isPaid ? 'badge-paid' : 'badge-pending';
            const badgeText = isPaid ? 'PAID' : 'PENDING';

            tr.innerHTML = `
                <td><span class="inv-link" onclick="viewHistory('${row.invoice_num}')">${row.invoice_num}</span></td>
                <td><strong>${row.display_name}</strong></td>
                <td>${row.txn_date}</td>
                <td>${dueText}</td>
                <td class="amount-col">${formatMoney(row.total_amt)}</td>
                <td class="amount-col" style="color:#64748b;">${formatMoney(row.settled_amt)}</td>
                <td class="amount-col" style="font-weight:700;">${formatMoney(row.balance)}</td>
                <td><span class="badge ${badgeClass}">${badgeText}</span></td>
            `;
            tbody.appendChild(tr);
        });
    }

    function formatMoney(amount) {
        return parseFloat(amount).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // --- HISTORY MODAL ---
    function viewHistory(invoiceNum) {
        const currentMode = document.getElementById('viewMode').value;
        document.getElementById('modalInvNum').innerText = invoiceNum;
        
        const tbody = document.getElementById('historyTableBody');
        const msg = document.getElementById('noHistoryMsg');
        tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding:20px;">Loading data...</td></tr>';
        msg.style.display = 'none';
        
        document.getElementById('historyModal').style.display = 'block';

        fetch(`functions/get_payment_history.php?invoice_num=${encodeURIComponent(invoiceNum)}&type=${currentMode}`)
        .then(res => res.json())
        .then(data => {
            tbody.innerHTML = '';
            if (!data.success || data.data.length === 0) {
                msg.style.display = 'block';
                return;
            }
            data.data.forEach(item => {
                const dr = parseFloat(item.dr_local);
                const cr = parseFloat(item.cr_local);
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${item.txn_date}</td>
                    <td>${item.description || '-'}</td>
                    <td class="amount-col" style="color:${dr > 0 ? '#991b1b' : '#cbd5e1'}">${dr > 0 ? formatMoney(dr) : '-'}</td>
                    <td class="amount-col" style="color:${cr > 0 ? '#166534' : '#cbd5e1'}">${cr > 0 ? formatMoney(cr) : '-'}</td>
                `;
                tbody.appendChild(row);
            });
        })
        .catch(err => {
            tbody.innerHTML = '';
            msg.style.display = 'block';
            console.error(err);
        });
    }

    function closeModal() { document.getElementById('historyModal').style.display = 'none'; }
    window.onclick = function(e) { if (e.target == document.getElementById('historyModal')) closeModal(); }
</script>

</body>
</html>