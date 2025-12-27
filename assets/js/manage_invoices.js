let rows = [];
let currentInvoiceNum = null;
let originalInvoiceData = null;
let editingRowIndex = -1; // Track which row is being edited

/* ---------- Initialize ---------- */
document.addEventListener('DOMContentLoaded', function() {
    txnDate.valueAsDate = new Date();
    calcDueDate();
    
    // Add event listeners for recalculation
    [cal1, cal2, cal3, brokerPct, tax].forEach(input => {
        input.addEventListener('input', render);
    });
});

/* ---------- Search Invoice Numbers ---------- */
function setupInvoiceSearch() {
    let timeout = null;
    
    invoiceSearch.addEventListener('input', function() {
        const value = this.value.trim();
        invoiceSug.innerHTML = '';
        invoiceSug.classList.remove('active');
        
        if (value.length < 2) return;
        
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            fetch('functions/search_invoices.php?q=' + encodeURIComponent(value))
                .then(res => res.json())
                .then(data => {
                    if (data.length === 0) {
                        const div = document.createElement('div');
                        div.className = 'autocomplete-item';
                        div.style.color = '#999';
                        div.innerHTML = 'No invoices found';
                        invoiceSug.appendChild(div);
                        invoiceSug.classList.add('active');
                        return;
                    }
                    
                    data.forEach(item => {
                        const div = document.createElement('div');
                        div.className = 'autocomplete-item';
                        div.innerHTML = `<strong>${item.invoice_num}</strong> - ${item.party_name} - ₹${parseFloat(item.net_amount).toFixed(2)}`;
                        div.onclick = () => {
                            invoiceSearch.value = item.invoice_num;
                            invoiceSug.innerHTML = '';
                            invoiceSug.classList.remove('active');
                            loadInvoice(item.invoice_num);
                        };
                        invoiceSug.appendChild(div);
                    });
                    invoiceSug.classList.add('active');
                })
                .catch(err => console.error('Search error:', err));
        }, 300);
    });
}

setupInvoiceSearch();

/* ---------- Load Invoice ---------- */
function loadInvoice(invoiceNum) {
    currentInvoiceNum = invoiceNum;
    
    fetch('functions/get_invoice.php?invoice_num=' + encodeURIComponent(invoiceNum))
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                originalInvoiceData = data.invoice;
                
                // Fill form
                invType.value = data.invoice.txn_type;
                txnDate.value = data.invoice.txn_date;
                party.value = data.invoice.party_name;
                broker.value = data.invoice.broker_name || '';
                credit.value = data.invoice.credit_days || 0;
                due.value = data.invoice.due_date;
                notes.value = data.invoice.notes || '';
                
                // Load Percentages
                cal1.value = parseFloat(data.invoice.cal1) || 0;
                cal2.value = parseFloat(data.invoice.cal2) || 0;
                cal3.value = parseFloat(data.invoice.cal3) || 0;
                
                // Brokerage Percentage Calculation
                let calculatedPct = (parseFloat(data.invoice.brokerage_amt) / parseFloat(data.invoice.gross_amt) * 100) || 0;
                brokerPct.value = parseFloat(calculatedPct.toFixed(2));
                
                tax.value = parseFloat(data.invoice.tax) || 0;
                
                // Load items
                rows = data.items.map(item => ({
                    cur: item.currency,
                    qty: parseFloat(item.qty),
                    rateUsd: parseFloat(item.rate_usd),
                    rateInr: parseFloat(item.rate_inr),
                    convRate: parseFloat(item.conv_rate),
                    baseUsd: parseFloat(item.base_amount_usd),
                    baseInr: parseFloat(item.base_amount_inr)
                }));
                
                render();
                alert('✅ Invoice loaded successfully!');
            } else {
                alert('❌ Error loading invoice: ' + data.message);
            }
        })
        .catch(err => {
            console.error('Error:', err);
            alert('❌ Error loading invoice');
        });
}

/* ---------- Autocomplete Helper ---------- */
function setupAutocomplete(input, suggestionBox, searchFile) {
    let timeout = null;
    input.addEventListener('input', function() {
        const value = this.value.trim();
        suggestionBox.innerHTML = '';
        suggestionBox.classList.remove('active');
        
        if (value.length < 2) return;
        
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            fetch(searchFile + '?q=' + encodeURIComponent(value))
                .then(res => res.json())
                .then(data => {
                    if (data.length === 0) return;
                    data.forEach(item => {
                        const div = document.createElement('div');
                        div.className = 'autocomplete-item';
                        div.innerHTML = `<strong>${item.name}</strong>`;
                        div.onclick = () => {
                            input.value = item.name;
                            suggestionBox.innerHTML = '';
                            suggestionBox.classList.remove('active');
                        };
                        suggestionBox.appendChild(div);
                    });
                    suggestionBox.classList.add('active');
                });
        }, 300);
    });
    
    document.addEventListener('click', e => {
        if (e.target !== input) {
            suggestionBox.innerHTML = '';
            suggestionBox.classList.remove('active');
        }
    });
}

setupAutocomplete(party, partySug, 'functions/search_party.php');
setupAutocomplete(broker, brokerSug, 'functions/search_broker.php');

/* ---------- Due Date ---------- */
function calcDueDate() {
    let d = new Date(txnDate.value || new Date());
    d.setDate(d.getDate() + Number(credit.value || 0));
    due.value = d.toISOString().split('T')[0];
}
credit.addEventListener('input', calcDueDate);
txnDate.addEventListener('change', calcDueDate);

/* ---------- Modal Logic ---------- */
function openModal() {
    modal.style.display = 'block';
    // If we are NOT editing, reset the form
    if (editingRowIndex === -1) {
        resetModal();
    }
}

function closeModal() {
    modal.style.display = 'none';
    editingRowIndex = -1; // Clear editing state on close
    resetModal();         // Clean inputs
}

function resetModal() {
    [mqty, mrateUsd, mrateInr, conv, musd, minr].forEach(i => i.value = '');
    mcur.value = '';
    mrateUsd.disabled = true;
    mrateInr.disabled = true;
    conv.disabled = true;
    
    // Reset Button Text
    const btn = document.querySelector('.modal-buttons button:first-child');
    if(btn) btn.innerText = "✓ Add Item";
}

mcur.addEventListener('change', function() {
    let selectedValue = mcur.value;
    
    // Only clear values if we are NOT in the middle of loading an edit
    // (Simple check: if inputs are empty, it's fresh. If not, preserve them)
    if (mqty.value === '') {
        [mqty, mrateUsd, mrateInr, conv, musd, minr].forEach(i => i.value = '');
    }

    mrateUsd.disabled = true;
    mrateInr.disabled = true;
    conv.disabled = true;
    
    if (selectedValue === 'BOTH') {
        conv.disabled = false;
        mrateUsd.disabled = false;
    }
    if (selectedValue === 'INR') {
        mrateInr.disabled = false;
    }
});

[mqty, mrateUsd, mrateInr, conv].forEach(i => i.addEventListener('input', calcModal));

function calcModal() {
    let q = parseFloat(mqty.value) || 0;
    if (mcur.value === 'BOTH') {
        let rUsd = parseFloat(mrateUsd.value) || 0;
        let c = parseFloat(conv.value) || 0;
        let rInr = rUsd * c;
        mrateInr.value = rInr.toFixed(4);
        musd.value = (q * rUsd).toFixed(2);
        minr.value = (q * rInr).toFixed(2);
    }
    if (mcur.value === 'INR') {
        let rInr = parseFloat(mrateInr.value) || 0;
        musd.value = '';
        minr.value = (q * rInr).toFixed(2);
    }
}

/* ---------- Add / Update Row ---------- */
function addRow() {
    if (!mcur.value) { alert('Select currency'); return; }
    if (!mqty.value || parseFloat(mqty.value) <= 0) { alert('Invalid quantity'); return; }
    
    const newItem = {
        cur: mcur.value,
        qty: parseFloat(mqty.value),
        rateUsd: mcur.value === 'BOTH' ? parseFloat(mrateUsd.value) : 0,
        rateInr: parseFloat(mrateInr.value),
        convRate: mcur.value === 'BOTH' ? parseFloat(conv.value) : 0,
        baseUsd: parseFloat(musd.value) || 0,
        baseInr: parseFloat(minr.value)
    };
    
    if (editingRowIndex > -1) {
        // Update Existing Row
        rows[editingRowIndex] = newItem;
        editingRowIndex = -1; // Reset
    } else {
        // Add New Row
        rows.push(newItem);
    }
    
    closeModal();
    render();
}

/* ---------- Edit Row Function ---------- */
function editRow(index) {
    editingRowIndex = index;
    const item = rows[index];
    
    // Fill Modal with Data
    mcur.value = item.cur;
    
    // Trigger change event to set disabled/enabled states
    mcur.dispatchEvent(new Event('change'));
    
    mqty.value = item.qty;
    
    if (item.cur === 'BOTH') {
        mrateUsd.value = item.rateUsd;
        conv.value = item.convRate;
    } else {
        mrateInr.value = item.rateInr;
    }
    
    // Recalculate totals in modal
    calcModal();
    
    // Change Button Text to "Update"
    const btn = document.querySelector('.modal-buttons button:first-child');
    if(btn) btn.innerText = "✓ Update Item";
    
    // Open Modal
    modal.style.display = 'block';
}

/* ---------- Render Grid ---------- */
function render() {
    let tb = grid.querySelector('tbody');
    tb.innerHTML = '';
    
    const c1 = parseFloat(cal1.value) || 0;
    const c2 = parseFloat(cal2.value) || 0;
    const c3 = parseFloat(cal3.value) || 0;
    const brokerPercent = parseFloat(brokerPct.value) || 0;
    const taxPercent = parseFloat(tax.value) || 0;
    
    let totalAdjustedInr = 0;
    
    rows.forEach((r, i) => {
        let adjustedUsd = 0;
        let adjustedInr = 0;
        
        if (r.cur === 'BOTH') {
            adjustedUsd = r.baseUsd;
            if (c1 !== 0) adjustedUsd += (adjustedUsd * c1 / 100);
            if (c2 !== 0) adjustedUsd += (adjustedUsd * c2 / 100);
            if (c3 !== 0) adjustedUsd += (adjustedUsd * c3 / 100);
            adjustedInr = adjustedUsd * r.convRate;
        } else {
            adjustedInr = r.baseInr;
            if (c1 !== 0) adjustedInr += (adjustedInr * c1 / 100);
            if (c2 !== 0) adjustedInr += (adjustedInr * c2 / 100);
            if (c3 !== 0) adjustedInr += (adjustedInr * c3 / 100);
        }
        
        r.adjustedUsd = adjustedUsd;
        r.adjustedInr = adjustedInr;
        totalAdjustedInr += adjustedInr;
        
        tb.innerHTML += `
        <tr>
            <td>${r.cur}</td>
            <td>${r.qty}</td>
            <td>${r.rateUsd || '-'}</td>
            <td>${r.rateInr.toFixed(4)}</td>
            <td>${adjustedUsd > 0 ? adjustedUsd.toFixed(2) : '-'}</td>
            <td>₹ ${adjustedInr.toFixed(2)}</td>
            <td>
                <button onclick="editRow(${i})" style="margin-right:5px;cursor:pointer;">✏️ Edit</button>
                <button onclick="del(${i})" style="color:red;cursor:pointer;">🗑️ Delete</button>
            </td>
        </tr>`;
    });

    // --- Footer Calculations ---
    let baseTotal = rows.reduce((sum, r) => sum + r.baseInr, 0);
    document.getElementById('baseTotal').innerText = '₹ ' + baseTotal.toFixed(2);
    
    let displayAmount = baseTotal;
    if (c1 !== 0) displayAmount += (displayAmount * c1 / 100);
    document.getElementById('cal1Display').innerText = c1.toFixed(2);
    document.getElementById('afterCal1').innerText = '₹ ' + displayAmount.toFixed(2);
    
    if (c2 !== 0) displayAmount += (displayAmount * c2 / 100);
    document.getElementById('cal2Display').innerText = c2.toFixed(2);
    document.getElementById('afterCal2').innerText = '₹ ' + displayAmount.toFixed(2);
    
    if (c3 !== 0) displayAmount += (displayAmount * c3 / 100);
    document.getElementById('cal3Display').innerText = c3.toFixed(2);
    document.getElementById('afterCal3').innerText = '₹ ' + displayAmount.toFixed(2);
    
    const grossAmount = totalAdjustedInr;
    grossInr.innerText = '₹ ' + grossAmount.toFixed(2);
    
    const brokerageAmount = grossAmount * (brokerPercent / 100);
    document.getElementById('brokerPctDisplay').innerText = brokerPercent.toFixed(2);
    brokerAmt.innerText = '₹ ' + brokerageAmount.toFixed(2);
    
    const taxAmount = grossAmount * (taxPercent / 100);
    document.getElementById('taxDisplay').innerText = taxPercent.toFixed(2);
    document.getElementById('taxAmt').innerText = '₹ ' + taxAmount.toFixed(2);
    
    const netAmount = grossAmount + taxAmount;
    netInr.innerText = '₹ ' + netAmount.toFixed(2);
}

function del(i) {
    if (confirm('Delete this item?')) {
        rows.splice(i, 1);
        render();
    }
}

/* ---------- Update Invoice ---------- */
function updateInvoice() {
    if (!currentInvoiceNum) { alert('Select an invoice first'); return; }
    if (!party.value.trim()) { alert('Enter party name'); return; }
    if (rows.length === 0) { alert('Add at least one item'); return; }
    
    if (!confirm('Update invoice?')) return;
    
    const itemsToSave = rows.map(r => ({
        cur: r.cur,
        qty: r.qty,
        rateUsd: r.rateUsd,
        rateInr: r.rateInr,
        convRate: r.convRate || 0,
        baseUsd: r.baseUsd,
        baseInr: r.baseInr,
        adjustedUsd: r.adjustedUsd || 0,
        adjustedInr: r.adjustedInr
    }));
    
    const updateData = {
        invoice_num: currentInvoiceNum,
        txn_date: txnDate.value,
        party_name: party.value.trim(),
        broker_name: broker.value.trim(),
        notes: notes.value,
        credit_days: credit.value,
        due_date: due.value,
        cal1: parseFloat(cal1.value) || 0,
        cal2: parseFloat(cal2.value) || 0,
        cal3: parseFloat(cal3.value) || 0,
        brokerage_amt: parseFloat(brokerAmt.innerText.replace('₹ ', '').replace(',', '')),
        gross_amt: parseFloat(grossInr.innerText.replace('₹ ', '').replace(',', '')),
        tax: parseFloat(tax.value) || 0,
        net_amount: parseFloat(netInr.innerText.replace('₹ ', '').replace(',', '')),
        party_status: 1,
        broker_status: broker.value.trim() ? 1 : 0,
        items: itemsToSave
    };
    
    const updateBtn = event.target;
    updateBtn.innerText = '⏳ Updating...';
    updateBtn.disabled = true;
    
    fetch('functions/update_invoice.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(updateData)
    })
    .then(res => res.json())
    .then(data => {
        updateBtn.innerText = '💾 Update Invoice';
        updateBtn.disabled = false;
        
        if (data.success) {
            alert('✅ Updated successfully!');
            loadInvoice(currentInvoiceNum);
        } else {
            alert('❌ Error: ' + data.message);
        }
    })
    .catch(err => {
        updateBtn.innerText = '💾 Update Invoice';
        updateBtn.disabled = false;
        console.error(err);
        alert('❌ Network Error');
    });
}

function deleteInvoice() {
    if (!currentInvoiceNum) return;
    if (!confirm('Permanently delete invoice?')) return;
    
    fetch('functions/delete_invoice.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ invoice_num: currentInvoiceNum })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('✅ Deleted!');
            clearForm();
        } else {
            alert('❌ Error: ' + data.message);
        }
    });
}

function clearForm() {
    currentInvoiceNum = null;
    originalInvoiceData = null;
    rows = [];
    render();
    
    invoiceSearch.value = '';
    invType.value = '';
    party.value = '';
    broker.value = '';
    notes.value = '';
    credit.value = '0';
    cal1.value = '0';
    cal2.value = '0';
    cal3.value = '0';
    brokerPct.value = '0';
    tax.value = '0';
    txnDate.valueAsDate = new Date();
    calcDueDate();
}