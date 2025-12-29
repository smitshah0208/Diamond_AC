// Event Listener for Page Load
document.addEventListener('DOMContentLoaded', function() {
    // Set default dates to current month
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0); 
    
    // Check if elements exist before setting values
    const dFrom = document.getElementById('dateFrom');
    const dTo = document.getElementById('dateTo');

    if(dFrom) dFrom.valueAsDate = firstDay;
    if(dTo) dTo.valueAsDate = lastDay;
});

// GLOBAL FUNCTION - Must be outside the block above
function fetchReport() {
    const dFrom = document.getElementById('dateFrom').value;
    const dTo = document.getElementById('dateTo').value;
    const type = document.getElementById('reportType').value;
    const btn = document.getElementById('btnGen');

    if(!dFrom || !dTo) { alert("Please select both dates."); return; }

    btn.innerText = "Loading..."; 
    btn.disabled = true;

    // Fetch call to the backend
    fetch(`functions/get_outstanding_report.php?from_date=${dFrom}&to_date=${dTo}&type=${type}`)
    .then(response => response.text()) // Get text first to debug errors
    .then(text => {
        try {
            return JSON.parse(text); // Parse JSON manually
        } catch (e) {
            console.error("Server Error Response:", text);
            throw new Error("Server returned invalid data. Check console (F12) for details.");
        }
    })
    .then(data => {
        btn.innerText = "Generate Report"; 
        btn.disabled = false;

        // Check if PHP sent an error flag
        if(data.error) {
            alert("System Error: " + data.message);
            return;
        }

        renderTables(data, type);
    })
    .catch(err => {
        console.error(err);
        btn.innerText = "Generate Report"; 
        btn.disabled = false;
        alert(err.message);
    });
}

function renderTables(data, type) {
    const secRec = document.getElementById('secReceivable');
    const secPay = document.getElementById('secPayable');

    // 1. Manage Visibility
    if(type === 'ALL') { 
        secRec.classList.add('active'); 
        secPay.classList.add('active'); 
    } else if(type === 'RECEIVABLE') { 
        secRec.classList.add('active'); 
        secPay.classList.remove('active'); 
    } else { 
        secRec.classList.remove('active'); 
        secPay.classList.add('active'); 
    }

    // 2. Render Receivables
    fillTable('tbReceivable', data.receivables);
    document.getElementById('sumReceivable').innerText = formatMoney(data.total_receivable);

    // 3. Render Payables
    fillTable('tbPayable', data.payables);
    document.getElementById('sumPayable').innerText = formatMoney(data.total_payable);
}

function fillTable(tbodyId, rows) {
    const tbody = document.getElementById(tbodyId);
    tbody.innerHTML = '';

    if(!rows || rows.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center; padding:20px; color:#888;">No outstanding invoices found for this period.</td></tr>';
        return;
    }

    const today = new Date();
    today.setHours(0,0,0,0); // Normalize today to midnight for comparison

    rows.forEach(row => {
        const tr = document.createElement('tr');
        
        // Check if Due Date is passed
        const dueDateObj = new Date(row.due_date);
        const isOverdue = dueDateObj < today;
        const dueStyle = isOverdue ? 'class="due-overdue"' : '';
        const dueText = isOverdue ? `${row.due_date} (Overdue)` : row.due_date;

        tr.innerHTML = `
            <td><strong>${row.invoice_num}</strong></td>
            <td>${row.party_name}</td>
            <td>${row.invoice_date}</td>
            <td>${row.credit_days}</td>
            <td ${dueStyle}>${dueText}</td>
            <td class="amount-col">${formatMoney(row.total_amount)}</td>
            <td class="amount-col" style="color:#777;">${formatMoney(row.paid_so_far)}</td>
            <td class="amount-col" style="font-weight:bold; font-size:1.05em;">${formatMoney(row.balance)}</td>
        `;
        tbody.appendChild(tr);
    });
}

function formatMoney(amount) {
    return parseFloat(amount).toLocaleString('en-IN', { 
        minimumFractionDigits: 2, 
        maximumFractionDigits: 2 
    });
}