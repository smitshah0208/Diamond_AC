let rows = [];

document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Invoice page loaded successfully');
    
    // --- 1. Initialize Default Values ---
    const txnDate = document.getElementById('txnDate');
    if(txnDate) txnDate.valueAsDate = new Date();
    calcDueDate();
    getNextInvoiceNo();

    // --- 2. Main Page Event Listeners ---
    ['cal1', 'cal2', 'cal3', 'brokerPct', 'taxPct'].forEach(id => {
        const el = document.getElementById(id);
        if(el) el.addEventListener('input', render);
    });

    // --- 3. Autocomplete Setup ---
    setupAutocomplete('party', 'partySug', 'functions/search_party.php');
    setupAutocomplete('broker', 'brokerSug', 'functions/search_broker.php');

    // --- 4. MODAL LOGIC ---
    const mcur = document.getElementById('mcur');
    if(mcur) {
        mcur.addEventListener('change', function() {
            const val = this.value;
            const mrateUsd = document.getElementById('mrateUsd');
            const mrateLocal = document.getElementById('mrateLocal');
            const conv = document.getElementById('conv');
            
            // Clear values
            mrateUsd.value = ''; mrateLocal.value = ''; conv.value = '';
            document.getElementById('musd').value = '';
            document.getElementById('mlocal').value = '';

            // Toggle Fields
            if(val === 'BOTH') {
                unlockField('mrateUsd'); unlockField('conv'); lockField('mrateLocal');
            } else if (val === 'LOCAL') {
                lockField('mrateUsd'); lockField('conv'); unlockField('mrateLocal');
            } else {
                lockField('mrateUsd'); lockField('conv'); lockField('mrateLocal');
            }
        });
    }

    // Modal Calculation Listeners
    ['mqty', 'mrateUsd', 'mrateLocal', 'conv'].forEach(id => {
        const el = document.getElementById(id);
        if(el) el.addEventListener('input', calculateModalValues);
    });
});

function unlockField(id) {
    const el = document.getElementById(id);
    if(el) { el.readOnly = false; el.disabled = false; el.style.backgroundColor = '#ffffff'; el.style.border = '1px solid #ccc'; }
}

function lockField(id) {
    const el = document.getElementById(id);
    if(el) { el.readOnly = true; el.style.backgroundColor = '#f0f0f0'; }
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

function openModal() {
    const modal = document.getElementById('modal');
    if(modal) {
        modal.style.display = 'block';
        document.getElementById('mcur').value = '';
        document.getElementById('mqty').value = '';
        document.getElementById('musd').value = '';
        document.getElementById('mlocal').value = '';
        document.getElementById('mrateUsd').value = ''; lockField('mrateUsd');
        document.getElementById('mrateLocal').value = ''; lockField('mrateLocal');
        document.getElementById('conv').value = ''; lockField('conv');
    }
}

function closeModal() {
    const modal = document.getElementById('modal');
    if(modal) modal.style.display = 'none';
}

function addRow() {
    const mcur = document.getElementById('mcur');
    const mqty = document.getElementById('mqty');
    
    if(!mcur || !mqty) return;
    
    const currency = mcur.value;
    const qty = parseFloat(mqty.value) || 0;
    
    if(!currency) { alert('❌ Please select a Currency!'); mcur.focus(); return; }
    if(qty <= 0) { alert('❌ Please enter a valid Quantity!'); mqty.focus(); return; }
    
    const rUsd = parseFloat(document.getElementById('mrateUsd').value) || 0;
    const rLoc = parseFloat(document.getElementById('mrateLocal').value) || 0;
    const conv = parseFloat(document.getElementById('conv').value) || 0;
    const amtUsd = parseFloat(document.getElementById('musd').value) || 0;
    const amtLoc = parseFloat(document.getElementById('mlocal').value) || 0;
    
    if(currency === 'BOTH') {
        if(rUsd <= 0) { alert('❌ Enter Rate $'); document.getElementById('mrateUsd').focus(); return; }
        if(conv <= 0) { alert('❌ Enter Conversion Rate'); document.getElementById('conv').focus(); return; }
    } else if(currency === 'LOCAL') {
        if(rLoc <= 0) { alert('❌ Enter Local Rate'); document.getElementById('mrateLocal').focus(); return; }
    }

    const item = {
        cur: currency,
        qty: qty,
        rateUsd: rUsd,
        rateLocal: rLoc,
        convRate: conv,
        baseUsd: amtUsd,
        baseLocal: amtLoc
    };
    
    rows.push(item);
    closeModal();
    render();
}

function render() {
    const tb = document.querySelector('#grid tbody');
    if(!tb) return;
    
    tb.innerHTML = '';

    const getVal = (id) => parseFloat(document.getElementById(id)?.value || 0);
    const c1 = getVal('cal1');
    const c2 = getVal('cal2');
    const c3 = getVal('cal3');
    const bPct = getVal('brokerPct');
    const tPct = getVal('taxPct');

    let totBaseUsd = 0;
    let totBaseLoc = 0;
    let grandAdjUsd = 0;
    let grandAdjLoc = 0;

    rows.forEach((r, i) => {
        totBaseUsd += r.baseUsd;
        totBaseLoc += r.baseLocal;
        
        // --- Calculate Adjusted Amount Per Row for Display ---
        let adjUsd = r.baseUsd;
        let adjLoc = r.baseLocal;
        
        // Apply Cal1, Cal2, Cal3 sequentially/cumulatively
        [c1, c2, c3].forEach(pct => {
            if(pct) {
                adjUsd += adjUsd * (pct / 100);
                adjLoc += adjLoc * (pct / 100);
            }
        });
        
        grandAdjUsd += adjUsd;
        grandAdjLoc += adjLoc;
        
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><strong>${r.cur}</strong></td>
            <td>${r.qty.toFixed(2)}</td>
            <td>${r.rateUsd > 0 ? r.rateUsd.toFixed(2) : '-'}</td>
            <td>${r.rateLocal.toFixed(4)}</td>
            <!-- Display Adjusted Amounts -->
            <td><strong>${adjUsd.toFixed(2)}</strong></td>
            <td><strong>${adjLoc.toFixed(2)}</strong></td>
            <td><button class="btn-delete" onclick="deleteRow(${i})">Delete</button></td>
        `;
        tb.appendChild(tr);
    });

    // Update Bottom Calculations
    document.getElementById('baseTotalUsd').innerText = totBaseUsd.toFixed(2);
    document.getElementById('baseTotalLocal').innerText = totBaseLoc.toFixed(2);

    // Gross Amount is the sum of adjusted amounts
    const gUsd = grandAdjUsd;
    const gLoc = grandAdjLoc;

    document.getElementById('grossUsd').innerText = gUsd.toFixed(2);
    document.getElementById('grossLocal').innerText = gLoc.toFixed(2);

    const bUsd = gUsd * bPct / 100;
    const bLoc = gLoc * bPct / 100;
    document.getElementById('brokerAmtUsd').innerText = bUsd.toFixed(2);
    document.getElementById('brokerAmtLocal').innerText = bLoc.toFixed(2);

    const tUsd = gUsd * tPct / 100;
    const tLoc = gLoc * tPct / 100;
    document.getElementById('taxAmtUsd').innerText = tUsd.toFixed(2);
    document.getElementById('taxAmtLocal').innerText = tLoc.toFixed(2);

    document.getElementById('netUsd').innerText = (gUsd + tUsd).toFixed(2);
    document.getElementById('netLocal').innerText = (gLoc + tLoc).toFixed(2);
}

function deleteRow(i) {
    if(confirm('Delete this item?')) {
        rows.splice(i, 1);
        render();
    }
}

function saveInvoice() {
    const party = document.getElementById('party').value.trim();
    const invNo = document.getElementById('invNo').value;
    
    if(!party) { alert('❌ Please enter Party Name!'); document.getElementById('party').focus(); return; }
    if(rows.length === 0) { alert('❌ Please add at least one invoice item!'); return; }

    const getVal = (id) => parseFloat(document.getElementById(id)?.value || 0);
    const c1 = getVal('cal1');
    const c2 = getVal('cal2');
    const c3 = getVal('cal3');

    // --- Prepare Items with Adjusted Values for DB ---
    const itemsToSend = rows.map(r => {
        let aUsd = r.baseUsd;
        let aLoc = r.baseLocal;
        
        // Re-calculate adjustment for saving
        [c1, c2, c3].forEach(pct => {
            if(pct) {
                aUsd += aUsd * (pct / 100);
                aLoc += aLoc * (pct / 100);
            }
        });

        return {
            ...r,
            adjUsd: aUsd.toFixed(2),
            adjLocal: aLoc.toFixed(2)
        };
    });

    const data = {
        txn_type: document.getElementById('invType').value,
        invoice_num: invNo,
        txn_date: document.getElementById('txnDate').value,
        party_name: party,
        broker_name: document.getElementById('broker').value,
        notes: document.getElementById('notes').value,
        credit_days: document.getElementById('credit').value,
        due_date: document.getElementById('due').value,
        cal1: document.getElementById('cal1').value,
        cal2: document.getElementById('cal2').value,
        cal3: document.getElementById('cal3').value,
        brokerage_pct: document.getElementById('brokerPct').value,
        tax_pct: document.getElementById('taxPct').value,
        
        gross_amt_local: document.getElementById('grossLocal').innerText,
        gross_amt_usd: document.getElementById('grossUsd').innerText,
        brokerage_amt: document.getElementById('brokerAmtLocal').innerText,
        brokerage_amt_usd: document.getElementById('brokerAmtUsd').innerText,
        tax_local: document.getElementById('taxAmtLocal').innerText,
        tax_usd: document.getElementById('taxAmtUsd').innerText,
        net_amount_local: document.getElementById('netLocal').innerText,
        net_amount_usd: document.getElementById('netUsd').innerText,
        
        party_status: 1,
        broker_status: document.getElementById('broker').value ? 1 : 0,
        items: itemsToSend // Send the items with calculated 'adjUsd/adjLocal'
    };

    const btn = event.target;
    const originalText = btn.innerText;
    btn.innerText = 'Saving...';
    btn.disabled = true;

    fetch('functions/save_invoice.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(result => {
        btn.innerText = originalText;
        btn.disabled = false;
        if(result.success) {
            // Success message with Invoice Number
            alert(`✅ Invoice ${invNo} has been saved successfully!`);
            resetForm();
        } else {
            alert('❌ Error: ' + (result.message || 'Unknown error'));
        }
    })
    .catch(error => {
        btn.innerText = originalText;
        btn.disabled = false;
        alert('❌ Error: ' + error.message);
    });
}

function resetForm() {
    rows = [];
    render();
    document.getElementById('party').value = '';
    document.getElementById('broker').value = '';
    document.getElementById('notes').value = '';
    ['cal1', 'cal2', 'cal3', 'brokerPct', 'taxPct'].forEach(id => document.getElementById(id).value = 0);
    document.getElementById('credit').value = 0;
    calcDueDate();
    getNextInvoiceNo();
}

function getNextInvoiceNo() {
    const type = document.getElementById('invType').value;
    fetch('functions/get_invoice_no.php?type=' + type)
        .then(r => r.json())
        .then(d => { if(d.success) document.getElementById('invNo').value = d.invoice_num; });
}

document.getElementById('invType').addEventListener('change', getNextInvoiceNo);

function calcDueDate() {
    const txnDate = new Date(document.getElementById('txnDate').value);
    const credit = Number(document.getElementById('credit').value) || 0;
    txnDate.setDate(txnDate.getDate() + credit);
    document.getElementById('due').value = txnDate.toISOString().split('T')[0];
}

document.getElementById('credit').addEventListener('input', calcDueDate);
document.getElementById('txnDate').addEventListener('change', calcDueDate);

function setupAutocomplete(inputId, sugBoxId, phpFile) {
    const inp = document.getElementById(inputId);
    const box = document.getElementById(sugBoxId);
    let timeout;
    inp.addEventListener('input', function() {
        const val = inp.value.trim();
        box.innerHTML = '';
        box.classList.remove('active');
        if(val.length < 2) return;
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