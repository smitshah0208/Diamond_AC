<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Outstanding Report</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f4f9; padding: 20px; }
        
        .controls { background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); display: flex; gap: 15px; align-items: center; margin-bottom: 20px; flex-wrap: wrap;}
        .controls label { font-weight: 600; font-size: 13px; color: #555; }
        .controls input, .controls select { padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        .btn-go { background: #2563eb; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-go:hover { background: #1d4ed8; }

        .report-section { display: none; margin-bottom: 30px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .report-section.active { display: block; }

        h2 { border-left: 5px solid #333; padding-left: 10px; margin-top: 0; color: #444; }
        .h-receivable { border-color: #059669; color: #059669; } 
        .h-payable { border-color: #dc2626; color: #dc2626; } 

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px 10px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; }
        th { background: #f8fafc; font-weight: 600; color: #555; }
        tr:hover { background: #f1f5f9; }
        
        .amount-col { text-align: right; font-family: 'Consolas', monospace; }
        
        .status-paid { color: #059669; font-weight: bold; background: #ecfdf5; }
        .status-pending { color: #dc2626; }
        
        .total-box { text-align: right; padding: 15px; background: #eee; font-weight: bold; font-size: 16px; border-radius: 0 0 8px 8px; }

        .inv-link { color: #2563eb; text-decoration: none; font-weight: bold; cursor: pointer; border-bottom: 1px dotted #2563eb; }
        .inv-link:hover { color: #1d4ed8; border-bottom: 1px solid #1d4ed8; }

        /* MODAL STYLES */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: #fff; margin: 10% auto; padding: 20px; border-radius: 8px; width: 60%; max-width: 700px; box-shadow: 0 4px 10px rgba(0,0,0,0.2); position: relative; }
        .close-btn { position: absolute; right: 20px; top: 15px; font-size: 24px; cursor: pointer; color: #aaa; }
        .close-btn:hover { color: #000; }
        .modal-header { font-size: 18px; font-weight: bold; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #eee; }
        
        .mini-table { width: 100%; font-size: 13px; }
        .mini-table th { background: #eee; padding: 8px; }
        .mini-table td { padding: 8px; border-bottom: 1px solid #f0f0f0; }
    </style>
</head>
<body>

    <div class="controls">
        <div>
            <label>View For:</label>
            <select id="viewMode" onchange="toggleReportTitle()">
                <option value="PARTY">Party Outstanding</option>
                <option value="BROKER">Broker Outstanding</option>
            </select>
        </div>
        <div>
            <label>Due Date From:</label>
            <input type="date" id="dateFrom">
        </div>
        <div>
            <label>Due Date To:</label>
            <input type="date" id="dateTo">
        </div>
        <button class="btn-go" onclick="fetchReport()" id="btnGen">Generate Report</button>
    </div>

    <!-- RECEIVABLES -->
    <div id="secReceivable" class="report-section">
        <h2 class="h-receivable">Sales Report</h2>
        <table>
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Party Name</th>
                    <th>Inv Date</th>
                    <th>Due Date</th>
                    <th class="amount-col">Bill Amount</th>
                    <th class="amount-col">Received</th>
                    <th class="amount-col">Balance</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="tbReceivable"></tbody>
        </table>
        <div class="total-box">Outstanding To Receive: <span id="sumReceivable">0.00</span></div>
    </div>

    <!-- PAYABLES -->
    <div id="secPayable" class="report-section">
        <h2 id="titlePayable" class="h-payable">Payables Report</h2>
        <table>
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th id="colNameHeader">Party Name</th>
                    <th>Inv Date</th>
                    <th>Due Date</th>
                    <th class="amount-col" id="colAmtHeader">Bill Amount</th>
                    <th class="amount-col">Paid</th>
                    <th class="amount-col">Balance</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="tbPayable"></tbody>
        </table>
        <div class="total-box">Outstanding To Pay: <span id="sumPayable">0.00</span></div>
    </div>

    <!-- HISTORY MODAL -->
    <div id="historyModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal()">&times;</span>
            <div class="modal-header">
                Payment History: <span id="modalInvNum" style="color:#2563eb;"></span> 
                <span id="modalTypeBadge" style="font-size:12px; background:#eee; padding:2px 6px; border-radius:4px; margin-left:10px;"></span>
            </div>
            <div id="modalBody">
                <table class="mini-table">
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
                <div id="noHistoryMsg" style="text-align:center; padding:15px; color:#777; display:none;">No payment records found for this type.</div>
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
            toggleReportTitle();
        });

        function toggleReportTitle() {
            const mode = document.getElementById('viewMode').value;
            if (mode === 'BROKER') {
                document.getElementById('titlePayable').innerText = "Brokerage Report (Commission)";
                document.getElementById('colNameHeader').innerText = "Broker Name";
                document.getElementById('colAmtHeader').innerText = "Brokerage Amt";
            } else {
                document.getElementById('titlePayable').innerText = "Purchase Report";
                document.getElementById('colNameHeader').innerText = "Party Name";
                document.getElementById('colAmtHeader').innerText = "Bill Amount";
            }
            document.getElementById('tbReceivable').innerHTML = '';
            document.getElementById('tbPayable').innerHTML = '';
            document.getElementById('secReceivable').classList.remove('active');
            document.getElementById('secPayable').classList.remove('active');
        }

        function fetchReport() {
            const dFrom = document.getElementById('dateFrom').value;
            const dTo = document.getElementById('dateTo').value;
            const mode = document.getElementById('viewMode').value;
            const btn = document.getElementById('btnGen');

            if(!dFrom || !dTo) { alert("Please select both dates."); return; }

            btn.innerText = "Loading..."; btn.disabled = true;

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

            if (mode === 'PARTY') {
                secRec.classList.add('active');
                secPay.classList.add('active');
                fillTable('tbReceivable', data.receivables);
                document.getElementById('sumReceivable').innerText = formatMoney(data.total_receivable);
            } else {
                secRec.classList.remove('active');
                secPay.classList.add('active');
            }

            fillTable('tbPayable', data.payables);
            document.getElementById('sumPayable').innerText = formatMoney(data.total_payable);
        }

        function fillTable(tbodyId, rows) {
            const tbody = document.getElementById(tbodyId);
            tbody.innerHTML = '';

            if(!rows || rows.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align:center; padding:20px; color:#888;">No records found.</td></tr>';
                return;
            }

            const today = new Date();
            today.setHours(0,0,0,0); 

            rows.forEach(row => {
                const tr = document.createElement('tr');
                const dueDateObj = new Date(row.due_date);
                const isOverdue = dueDateObj < today && row.balance > 1; 
                const dueText = isOverdue ? `${row.due_date} (Overdue)` : row.due_date;
                const isPaid = row.balance <= 1;
                const statusBadge = isPaid ? '✅ COMPLETED' : '⚠️ PENDING';

                tr.className = isPaid ? 'status-paid' : '';

                tr.innerHTML = `
                    <td>
                        <span class="inv-link" onclick="viewHistory('${row.invoice_num}')">
                            ${row.invoice_num}
                        </span>
                    </td>
                    <td>${row.display_name}</td>
                    <td>${row.txn_date}</td>
                    <td style="${isOverdue ? 'color:red;font-weight:bold;' : ''}">${dueText}</td>
                    <td class="amount-col">${formatMoney(row.total_amt)}</td>
                    <td class="amount-col" style="color:#555;">${formatMoney(row.settled_amt)}</td>
                    <td class="amount-col" style="font-weight:bold;">${formatMoney(row.balance)}</td>
                    <td style="font-size:12px;">${statusBadge}</td>
                `;
                tbody.appendChild(tr);
            });
        }

        function formatMoney(amount) {
            return parseFloat(amount).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        // --- UPDATED HISTORY LOGIC ---

        function viewHistory(invoiceNum) {
            // 1. Get the current active mode (PARTY or BROKER)
            const currentMode = document.getElementById('viewMode').value;

            document.getElementById('modalInvNum').innerText = invoiceNum;
            document.getElementById('modalTypeBadge').innerText = currentMode; // Show which list we are looking at

            const tbody = document.getElementById('historyTableBody');
            const msg = document.getElementById('noHistoryMsg');
            tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;">Loading...</td></tr>';
            msg.style.display = 'none';
            document.getElementById('historyModal').style.display = 'block';

            // 2. Send the 'type' to the backend
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
                        <td style="text-align:right; color:${dr > 0 ? 'red' : '#ccc'}">${dr > 0 ? formatMoney(dr) : '-'}</td>
                        <td style="text-align:right; color:${cr > 0 ? 'green' : '#ccc'}">${cr > 0 ? formatMoney(cr) : '-'}</td>
                    `;
                    tbody.appendChild(row);
                });
            })
            .catch(err => {
                tbody.innerHTML = '';
                msg.style.display = 'block';
                msg.innerText = "Error loading history.";
                console.error(err);
            });
        }

        function closeModal() {
            document.getElementById('historyModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('historyModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>