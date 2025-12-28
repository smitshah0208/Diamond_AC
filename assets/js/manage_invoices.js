let rows = [];
let currentInvoiceNum = null;
let editingRowIndex = -1;

document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Manage Invoices loaded');
    
    // 1. Initialize Dates
    const txnDate = document.getElementById('txnDate');
    if(txnDate) txnDate.valueAsDate = new Date();

    // 2. Add Calculation Listeners
    ['cal1', 'cal2', 'cal3', 'brokerPct', 'tax'].forEach(id => {
        const el = document.getElementById(id);
        if(el) el.addEventListener('input', render);
    });

    // 3. Date Listeners
    const crEl = document.getElementById('credit');
    const txEl = document.getElementById('txnDate');
    if(crEl) crEl.addEventListener('input', calcDueDate);
    if(txEl) txEl.addEventListener('change', calcDueDate);

    // 4. Initialize Modal Logic
    initModalListeners();

    // 5. Setup Search
    setupInvoiceSearch();

    // 6. Setup Party & Broker Search
    setupAutocomplete('party', 'partySug', 'functions/search_party.php');
    setupAutocomplete('broker', 'brokerSug', 'functions/search_broker.php');
});

// --- Helper: Clear/Reset Form (Manual Reset) ---
function resetForm() {
    currentInvoiceNum = null;
    rows = [];
    editingRowIndex = -1;
    
    // Clear Header Fields
    document.getElementById('invoiceSearch').value = '';
    document.getElementById('invType').value = '';
    document.getElementById('txnDate').valueAsDate = new Date();
    document.getElementById('party').value = '';
    document.getElementById('broker').value = '';
    document.getElementById('notes').value = '';
    document.getElementById('credit').value = '';
    document.getElementById('due').value = '';
    
    // Clear Percentages
    ['cal1', 'cal2', 'cal3', 'brokerPct', 'tax'].forEach(id => document.getElementById(id).value = '');

    // Clear Grid & Totals
    render(); 
    
    console.log('🔄 Form cleared');
}

// --- Autocomplete ---
function setupAutocomplete(inputId, sugBoxId, phpFile) {
    const inp = document.getElementById(inputId);
    const box = document.getElementById(sugBoxId);
    if(!inp || !box) return;
    
    let timeout;
    inp.addEventListener('input', function() {
        const val = inp.value.trim();
        box.innerHTML = '';
        box.classList.remove('active');
        if(val.length < 1) return;
        
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            fetch(`${phpFile}?q=${encodeURIComponent(val)}`)
                .then(r => r.json())
                .then(data => {
                    if(!data || data.length === 0) return;
                    box.classList.add('active');
                    data.forEach(item => {
                        const div = document.createElement('div');
                        div.className = 'autocomplete-item';
                        div.innerText = item.name;
                        div.onclick = function() {
                            inp.value = item.name;
                            box.innerHTML = '';
                            box.classList.remove('active');
                        };
                        box.appendChild(div);
                    });
                });
        }, 300);
    });
    document.addEventListener('click', function(e) { if(e.target !== inp) { box.innerHTML = ''; box.classList.remove('active'); } });
}

// --- Search Invoice ---
function setupInvoiceSearch() {
    const searchInp = document.getElementById('invoiceSearch');
    const sugBox = document.getElementById('invoiceSug');
    let timeout;
    if(!searchInp) return;

    searchInp.addEventListener('input', function() {
        const val = this.value.trim();
        sugBox.innerHTML = '';
        sugBox.classList.remove('active');
        if (val.length < 2) return;
        
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            fetch('functions/search_invoices.php?q=' + encodeURIComponent(val))
                .then(res => res.json())
                .then(data => {
                    if (!data || data.length === 0) return;
                    sugBox.classList.add('active');
                    data.forEach(item => {
                        const div = document.createElement('div');
                        div.className = 'autocomplete-item';
                        div.innerHTML = `<strong>${item.invoice_num}</strong> <small>(${item.party_name})</small>`;
                        div.onclick = () => {
                            searchInp.value = item.invoice_num;
                            sugBox.innerHTML = '';
                            sugBox.classList.remove('active');
                            loadInvoice(item.invoice_num);
                        };
                        sugBox.appendChild(div);
                    });
                });
        }, 300);
    });
}

// --- Load Invoice ---
function loadInvoice(invNum) {
    console.log('📥 Loading invoice:', invNum);
    currentInvoiceNum = invNum;

    fetch('functions/get_invoice.php?invoice_num=' + encodeURIComponent(invNum))
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const inv = data.invoice;
                
                document.getElementById('invType').value = inv.txn_type;
                document.getElementById('txnDate').value = inv.txn_date;
                document.getElementById('party').value = inv.party_name;
                document.getElementById('broker').value = inv.broker_name || '';
                document.getElementById('notes').value = inv.notes || '';
                document.getElementById('credit').value = inv.credit_days;
                document.getElementById('due').value = inv.due_date;
                
                document.getElementById('cal1').value = parseFloat(inv.cal1);
                document.getElementById('cal2').value = parseFloat(inv.cal2);
                document.getElementById('cal3').value = parseFloat(inv.cal3);
                document.getElementById('brokerPct').value = parseFloat(inv.brokerage_pct);
                
                let tPct = 0;
                if(inv.gross_amt_local > 0 && inv.tax_local > 0) {
                    tPct = (parseFloat(inv.tax_local) / parseFloat(inv.gross_amt_local)) * 100;
                }
                document.getElementById('tax').value = tPct.toFixed(2);

                rows = data.items.map(item => ({
                    cur: item.currency,
                    qty: parseFloat(item.qty),
                    rateUsd: parseFloat(item.rate_usd),
                    rateLocal: parseFloat(item.rate_local),
                    convRate: parseFloat(item.conv_rate),
                    baseUsd: parseFloat(item.base_amount_usd),
                    baseLocal: parseFloat(item.base_amount_local)
                }));

                render();
                
                // ✅ Notification added here
                alert(`✅ Invoice ${invNum} loaded successfully!`);
                
            } else {
                alert('❌ Error: ' + data.message);
            }
        })
        .catch(err => console.error(err));
}

// --- Modal ---
function initModalListeners() {
    const mcur = document.getElementById('mcur');
    if(mcur) {
        mcur.addEventListener('change', function() {
            const val = this.value;
            ['mrateUsd', 'mrateLocal', 'conv', 'musd', 'mlocal'].forEach(id => document.getElementById(id).value = '');

            if(val === 'BOTH') {
                unlockField('mrateUsd'); unlockField('conv'); lockField('mrateLocal');
            } else if (val === 'LOCAL') {
                lockField('mrateUsd'); lockField('conv'); unlockField('mrateLocal');
            } else {
                lockField('mrateUsd'); lockField('conv'); lockField('mrateLocal');
            }
        });
    }

    ['mqty', 'mrateUsd', 'mrateLocal', 'conv'].forEach(id => {
        document.getElementById(id).addEventListener('input', calculateModalValues);
    });
}

function calculateModalValues() {
    const cur = document.getElementById('mcur').value;
    const qty = parseFloat(document.getElementById('mqty').value) || 0;
    
    if(cur === 'BOTH') {
        const rUsd = parseFloat(document.getElementById('mrateUsd').value) || 0;
        const c = parseFloat(document.getElementById('conv').value) || 0;
        const amtUsd = parseFloat((qty * rUsd).toFixed(2));
        document.getElementById('musd').value = amtUsd;
        document.getElementById('mlocal').value = parseFloat((amtUsd * c).toFixed(2));
        document.getElementById('mrateLocal').value = c > 0 ? (rUsd * c).toFixed(4) : 0;
    } else if (cur === 'LOCAL') {
        const rLoc = parseFloat(document.getElementById('mrateLocal').value) || 0;
        document.getElementById('mlocal').value = parseFloat((qty * rLoc).toFixed(2));
        document.getElementById('musd').value = 0;
    }
}

function unlockField(id) {
    const el = document.getElementById(id);
    if(el) { el.readOnly = false; el.disabled = false; el.style.backgroundColor = '#ffffff'; }
}
function lockField(id) {
    const el = document.getElementById(id);
    if(el) { el.readOnly = true; el.style.backgroundColor = '#f0f0f0'; }
}

function openModal() {
    const modal = document.getElementById('modal');
    if(modal) {
        modal.style.display = 'block';
        if(editingRowIndex === -1) {
            document.getElementById('mcur').value = '';
            document.getElementById('mqty').value = '';
            document.getElementById('musd').value = '';
            document.getElementById('mlocal').value = '';
            lockField('mrateUsd'); lockField('mrateLocal'); lockField('conv');
            document.querySelector('.modal-buttons button:first-child').innerText = "✓ Add Item";
        }
    }
}

function closeModal() {
    document.getElementById('modal').style.display = 'none';
    editingRowIndex = -1;
}

function addRow() {
    const mcur = document.getElementById('mcur').value;
    const mqty = parseFloat(document.getElementById('mqty').value) || 0;
    
    if(!mcur) { alert('Select Currency'); return; }
    if(mqty <= 0) { alert('Invalid Qty'); return; }

    const item = {
        cur: mcur,
        qty: mqty,
        rateUsd: parseFloat(document.getElementById('mrateUsd').value) || 0,
        rateLocal: parseFloat(document.getElementById('mrateLocal').value) || 0,
        convRate: parseFloat(document.getElementById('conv').value) || 0,
        baseUsd: parseFloat(document.getElementById('musd').value) || 0,
        baseLocal: parseFloat(document.getElementById('mlocal').value) || 0
    };

    if(editingRowIndex > -1) rows[editingRowIndex] = item;
    else rows.push(item);
    
    closeModal();
    render();
}

function editRow(i) {
    editingRowIndex = i;
    const item = rows[i];
    const mcur = document.getElementById('mcur');
    mcur.value = item.cur;
    mcur.dispatchEvent(new Event('change'));
    
    document.getElementById('mqty').value = item.qty;
    document.getElementById('mrateUsd').value = item.rateUsd > 0 ? item.rateUsd : '';
    document.getElementById('mrateLocal').value = item.rateLocal;
    document.getElementById('conv').value = item.convRate > 0 ? item.convRate : '';
    calculateModalValues();
    
    document.querySelector('.modal-buttons button:first-child').innerText = "✓ Update Item";
    document.getElementById('modal').style.display = 'block';
}

function render() {
    const tb = document.querySelector('#grid tbody');
    tb.innerHTML = '';

    const getVal = (id) => parseFloat(document.getElementById(id)?.value || 0);
    const c1 = getVal('cal1'); const c2 = getVal('cal2'); const c3 = getVal('cal3');
    const bPct = getVal('brokerPct'); const tPct = getVal('tax');

    let totBaseUsd = 0; let totBaseLoc = 0;
    let grandAdjUsd = 0; let grandAdjLoc = 0;

    rows.forEach((r, i) => {
        totBaseUsd += r.baseUsd; totBaseLoc += r.baseLocal;
        let adjUsd = r.baseUsd; let adjLoc = r.baseLocal;
        [c1, c2, c3].forEach(pct => {
            if(pct) { adjUsd += adjUsd * (pct/100); adjLoc += adjLoc * (pct/100); }
        });
        grandAdjUsd += adjUsd; grandAdjLoc += adjLoc;
        
        tb.innerHTML += `
        <tr>
            <td>${r.cur}</td>
            <td>${r.qty.toFixed(2)}</td>
            <td>${r.rateUsd > 0 ? r.rateUsd.toFixed(2) : '-'}</td>
            <td>${r.rateLocal.toFixed(4)}</td>
            <td>${adjUsd.toFixed(2)}</td>
            <td>${adjLoc.toFixed(2)}</td>
            <td><button onclick="editRow(${i})">✏️</button> <button onclick="del(${i})" style="color:red; margin-left:5px;">🗑️</button></td>
        </tr>`;
    });

    document.getElementById('baseTotal').innerText = totBaseLoc.toFixed(2);
    document.getElementById('baseTotalUsd').innerText = totBaseUsd.toFixed(2);
    
    const gUsd = grandAdjUsd; const gLoc = grandAdjLoc;
    document.getElementById('grossUsd').innerText = gUsd.toFixed(2);
    document.getElementById('grossLocal').innerText = gLoc.toFixed(2);
    
    document.getElementById('brokerAmtUsd').innerText = (gUsd * bPct / 100).toFixed(2);
    document.getElementById('brokerAmt').innerText = (gLoc * bPct / 100).toFixed(2);
    
    const tUsd = gUsd * tPct / 100;
    const tLoc = gLoc * tPct / 100;
    document.getElementById('taxAmtUsd').innerText = tUsd.toFixed(2);
    document.getElementById('taxAmt').innerText = tLoc.toFixed(2);
    
    document.getElementById('netUsd').innerText = (gUsd + tUsd).toFixed(2);
    document.getElementById('netLocal').innerText = (gLoc + tLoc).toFixed(2);
}

function del(i) {
    if(confirm('Delete row?')) { rows.splice(i, 1); render(); }
}

function calcDueDate() {
    const txn = new Date(document.getElementById('txnDate').value);
    const cr = parseFloat(document.getElementById('credit').value) || 0;
    txn.setDate(txn.getDate() + cr);
    document.getElementById('due').value = txn.toISOString().split('T')[0];
}

function updateInvoice() {
    if(!currentInvoiceNum) { alert('Load invoice first'); return; }
    if(rows.length === 0) { alert('No items'); return; }
    
    const getVal = (id) => parseFloat(document.getElementById(id)?.value || 0);
    const getTxt = (id) => parseFloat(document.getElementById(id)?.innerText || 0);
    
    const itemsToSend = rows.map(r => {
        let aUsd = r.baseUsd; let aLoc = r.baseLocal;
        [getVal('cal1'), getVal('cal2'), getVal('cal3')].forEach(pct => {
            if(pct) { aUsd += aUsd * (pct/100); aLoc += aLoc * (pct/100); }
        });
        return { ...r, adjUsd: aUsd.toFixed(2), adjLocal: aLoc.toFixed(2) };
    });

    const data = {
        invoice_num: currentInvoiceNum,
        txn_date: document.getElementById('txnDate').value,
        party_name: document.getElementById('party').value,
        broker_name: document.getElementById('broker').value,
        notes: document.getElementById('notes').value,
        credit_days: document.getElementById('credit').value,
        due_date: document.getElementById('due').value,
        cal1: getVal('cal1'), cal2: getVal('cal2'), cal3: getVal('cal3'),
        brokerage_pct: getVal('brokerPct'),
        tax_pct: getVal('tax'),
        
        gross_amt_local: getTxt('grossLocal'), gross_amt_usd: getTxt('grossUsd'),
        brokerage_amt: getTxt('brokerAmt'), brokerage_amt_usd: getTxt('brokerAmtUsd'),
        tax_local: getTxt('taxAmt'), tax_usd: getTxt('taxAmtUsd'),
        net_amount_local: getTxt('netLocal'), net_amount_usd: getTxt('netUsd'),
        
        party_status: 1, broker_status: document.getElementById('broker').value ? 1 : 0,
        items: itemsToSend
    };

    const btn = event.target;
    const oldTxt = btn.innerText;
    btn.innerText = 'Updating...'; btn.disabled = true;

    fetch('functions/update_invoice.php', {
        method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(d => {
        btn.innerText = oldTxt; btn.disabled = false;
        if(d.success) alert('✅ Invoice Updated Successfully!');
        else alert('❌ Error: ' + d.message);
    })
    .catch(e => {
        btn.innerText = oldTxt; btn.disabled = false;
        alert('❌ Error: ' + e.message);
    });
}

function deleteInvoice() {
    if(!currentInvoiceNum) return;
    if(!confirm('Delete this invoice?')) return;
    
    fetch('functions/delete_invoice.php', {
        method: 'POST', headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({invoice_num: currentInvoiceNum})
    })
    .then(r => r.json())
    .then(d => {
        if(d.success) { 
            alert('✅ Invoice Deleted Successfully'); 
            resetForm(); // ✅ Clears form immediately instead of reloading
        }
        else alert('❌ Error: ' + d.message);
    });
}