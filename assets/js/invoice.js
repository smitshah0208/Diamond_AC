let rows = [];

/* ---------- Initialize ---------- */
document.addEventListener('DOMContentLoaded', function() {
    txnDate.valueAsDate = new Date();
    calcDueDate();
    
    // Get invoice number immediately on page load
    getNextInvoiceNo();
    
    // Add event listeners for recalculation
    [cal1, cal2, cal3, brokerPct, tax].forEach(input => {
        input.addEventListener('input', render);
    });
});

/* ---------- Get Next Invoice No from Database ---------- */
function getNextInvoiceNo() {
    const type = invType.value;
    fetch('functions/get_invoice_no.php?type=' + type)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                invNo.value = data.invoice_num;
            } else {
                console.error('Error getting invoice no:', data.message);
                // Fallback
                invNo.value = type + '-1001';
            }
        })
        .catch(err => {
            console.error('Error:', err);
            invNo.value = type + '-1001';
        });
}

invType.addEventListener('change', getNextInvoiceNo);

/* ---------- Enhanced Autocomplete with Auto-Insert ---------- */
function setupAutocomplete(input, suggestionBox, searchFile, onSelect) {
    let selectedIndex = -1;
    let timeout = null;
    
    input.addEventListener('input', function() {
        const value = this.value.trim();
        suggestionBox.innerHTML = '';
        suggestionBox.classList.remove('active');
        selectedIndex = -1;
        
        // Require minimum 2 characters
        if (value.length < 2) return;
        
        // Debounce
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            fetch(searchFile + '?q=' + encodeURIComponent(value))
                .then(res => res.json())
                .then(data => {
                    if (data.length === 0) {
                        const div = document.createElement('div');
                        div.className = 'autocomplete-item';
                        div.style.color = '#999';
                        div.innerHTML = '✨ No match found. Will create new entry on save.';
                        suggestionBox.appendChild(div);
                        suggestionBox.classList.add('active');
                        return;
                    }
                    
                    data.forEach((item, index) => {
                        const div = document.createElement('div');
                        div.className = 'autocomplete-item';
                        div.innerHTML = `<strong>${item.name}</strong>`;
                        div.onclick = () => {
                            input.value = item.name;
                            input.dataset.id = item.id;
                            suggestionBox.innerHTML = '';
                            suggestionBox.classList.remove('active');
                            if (onSelect) onSelect(item);
                        };
                        suggestionBox.appendChild(div);
                    });
                    suggestionBox.classList.add('active');
                })
                .catch(err => console.error('Search error:', err));
        }, 300);
    });
    
    input.addEventListener('keydown', function(e) {
        const items = suggestionBox.getElementsByClassName('autocomplete-item');
        
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
            updateSelection(items);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            selectedIndex = Math.max(selectedIndex - 1, -1);
            updateSelection(items);
        } else if (e.key === 'Enter' && selectedIndex >= 0) {
            e.preventDefault();
            items[selectedIndex].click();
        } else if (e.key === 'Escape') {
            suggestionBox.innerHTML = '';
            suggestionBox.classList.remove('active');
        }
    });
    
    function updateSelection(items) {
        Array.from(items).forEach((item, index) => {
            if (index === selectedIndex) {
                item.classList.add('selected');
            } else {
                item.classList.remove('selected');
            }
        });
    }
    
    // Close on click outside
    document.addEventListener('click', function(e) {
        if (e.target !== input) {
            suggestionBox.innerHTML = '';
            suggestionBox.classList.remove('active');
        }
    });
}

// Setup autocomplete for Party
setupAutocomplete(party, partySug, 'functions/search_party.php', function(item) {
    console.log('Selected party:', item);
});

// Setup autocomplete for Broker
setupAutocomplete(broker, brokerSug, 'functions/search_broker.php', function(item) {
    console.log('Selected broker:', item);
});

/* ---------- Due Date Calculation ---------- */
function calcDueDate() {
    let d = new Date(txnDate.value || new Date());
    d.setDate(d.getDate() + Number(credit.value || 0));
    due.value = d.toISOString().split('T')[0];
}

credit.addEventListener('input', calcDueDate);
txnDate.addEventListener('change', calcDueDate);

/* ---------- Modal Functions ---------- */
function openModal() {
    modal.style.display = 'block';
    resetModal();
}

function closeModal() {
    modal.style.display = 'none';
}

function resetModal() {
    [mqty, mrateUsd, mrateInr, conv, musd, minr].forEach(i => i.value = '');
    mcur.value = '';
    mrateUsd.disabled = true;
    mrateInr.disabled = true;
    conv.disabled = true;
}

mcur.addEventListener('change', function() {
    let selectedValue = mcur.value;
    [mqty, mrateUsd, mrateInr, conv, musd, minr].forEach(i => i.value = '');
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

/* ---------- Add Row ---------- */
function addRow() {
    if (!mcur.value) {
        alert('Please select currency');
        return;
    }
    if (!mqty.value || parseFloat(mqty.value) <= 0) {
        alert('Please enter valid quantity');
        return;
    }
    
    rows.push({
        cur: mcur.value,
        qty: parseFloat(mqty.value),
        rateUsd: mcur.value === 'BOTH' ? parseFloat(mrateUsd.value) : 0,
        rateInr: parseFloat(mrateInr.value),
        convRate: mcur.value === 'BOTH' ? parseFloat(conv.value) : 0,
        baseUsd: parseFloat(musd.value) || 0,
        baseInr: parseFloat(minr.value)
    });
    
    closeModal();
    render();
}

/* ---------- Render Grid with Sequential Calculations ---------- */
function render() {
    let tb = grid.querySelector('tbody');
    tb.innerHTML = '';
    
    // Get percentage values
    const c1 = parseFloat(cal1.value) || 0;
    const c2 = parseFloat(cal2.value) || 0;
    const c3 = parseFloat(cal3.value) || 0;
    const brokerPercent = parseFloat(brokerPct.value) || 0;
    const taxPercent = parseFloat(tax.value) || 0;
    
    // Calculate adjusted amounts for each row
    let totalAdjustedInr = 0;
    
    rows.forEach((r, i) => {
        let adjustedUsd = 0;
        let adjustedInr = 0;
        
        if (r.cur === 'BOTH') {
            // Start with base USD amount
            adjustedUsd = r.baseUsd;
            
            // Apply cal1, cal2, cal3 sequentially to USD
            if (c1 !== 0) {
                adjustedUsd = adjustedUsd + (adjustedUsd * c1 / 100);
            }
            if (c2 !== 0) {
                adjustedUsd = adjustedUsd + (adjustedUsd * c2 / 100);
            }
            if (c3 !== 0) {
                adjustedUsd = adjustedUsd + (adjustedUsd * c3 / 100);
            }
            
            // Convert adjusted USD to INR using conversion rate
            adjustedInr = adjustedUsd * r.convRate;
            
        } else {
            // INR Only - apply percentages directly to INR
            adjustedInr = r.baseInr;
            
            if (c1 !== 0) {
                adjustedInr = adjustedInr + (adjustedInr * c1 / 100);
            }
            if (c2 !== 0) {
                adjustedInr = adjustedInr + (adjustedInr * c2 / 100);
            }
            if (c3 !== 0) {
                adjustedInr = adjustedInr + (adjustedInr * c3 / 100);
            }
        }
        
        // Store adjusted values back to row
        r.adjustedUsd = adjustedUsd;
        r.adjustedInr = adjustedInr;
        
        totalAdjustedInr += adjustedInr;
        
        // Display row with adjusted amounts
        tb.innerHTML += `
        <tr>
            <td>${r.cur}</td>
            <td>${r.qty}</td>
            <td>${r.rateUsd || '-'}</td>
            <td>${r.rateInr.toFixed(4)}</td>
            <td>${adjustedUsd > 0 ? adjustedUsd.toFixed(2) : '-'}</td>
            <td>₹ ${adjustedInr.toFixed(2)}</td>
            <td><button class="btn-delete" onclick="del(${i})">Delete</button></td>
        </tr>`;
    });

    // Base Total (sum of all base amounts before adjustments)
    let baseTotal = rows.reduce((sum, r) => sum + r.baseInr, 0);
    document.getElementById('baseTotal').innerText = '₹ ' + baseTotal.toFixed(2);
    
    // Calculate overall totals with percentages for display
    let displayAmount = baseTotal;
    
    if (c1 !== 0) {
        displayAmount = displayAmount + (displayAmount * c1 / 100);
    }
    document.getElementById('cal1Display').innerText = c1.toFixed(2);
    document.getElementById('afterCal1').innerText = '₹ ' + displayAmount.toFixed(2);
    
    if (c2 !== 0) {
        displayAmount = displayAmount + (displayAmount * c2 / 100);
    }
    document.getElementById('cal2Display').innerText = c2.toFixed(2);
    document.getElementById('afterCal2').innerText = '₹ ' + displayAmount.toFixed(2);
    
    if (c3 !== 0) {
        displayAmount = displayAmount + (displayAmount * c3 / 100);
    }
    document.getElementById('cal3Display').innerText = c3.toFixed(2);
    document.getElementById('afterCal3').innerText = '₹ ' + displayAmount.toFixed(2);
    
    // Gross Amount = Total of all adjusted amounts
    const grossAmount = totalAdjustedInr;
    grossInr.innerText = '₹ ' + grossAmount.toFixed(2);
    
    // Brokerage = calculated on Gross Amount but NOT added
    const brokerageAmount = grossAmount * (brokerPercent / 100);
    document.getElementById('brokerPctDisplay').innerText = brokerPercent.toFixed(2);
    brokerAmt.innerText = '₹ ' + brokerageAmount.toFixed(2);
    
    // Tax Amount = calculated on Gross Amount
    const taxAmount = grossAmount * (taxPercent / 100);
    document.getElementById('taxDisplay').innerText = taxPercent.toFixed(2);
    document.getElementById('taxAmt').innerText = '₹ ' + taxAmount.toFixed(2);
    
    // Net Amount = Gross + Tax (NO Brokerage)
    const netAmount = grossAmount + taxAmount;
    netInr.innerText = '₹ ' + netAmount.toFixed(2);
}

function del(i) {
    if (confirm('Delete this item?')) {
        rows.splice(i, 1);
        render();
    }
}

/* ---------- Save Invoice with Auto-Insert Party/Broker ---------- */
function saveInvoice() {
    if (!party.value.trim()) {
        alert('Please enter a party name');
        return;
    }
    if (rows.length === 0) {
        alert('Please add at least one item');
        return;
    }
    
    // Prepare items with adjusted amounts
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
    
    const invoiceData = {
        txn_type: invType.value,
        invoice_num: invNo.value,
        txn_date: txnDate.value,
        credit_days: credit.value,
        due_date: due.value,
        party_name: party.value.trim(),
        broker_name: broker.value.trim(),
        notes: notes.value,
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
    
    // Show loading state
    const saveBtn = event.target;
    const originalText = saveBtn.innerHTML;
    saveBtn.innerHTML = '⏳ Saving...';
    saveBtn.disabled = true;
    
    fetch('functions/save_invoice.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(invoiceData)
    })
    .then(response => {
        // Check if response is ok
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.text();
    })
    .then(text => {
        console.log('Raw response:', text); // Debug log
        
        // Try to parse JSON
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('JSON Parse Error:', e);
            console.error('Response text:', text);
            throw new Error('Invalid JSON response from server');
        }
        
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
        
        if (data.success) {
            alert('✅ Invoice saved successfully!\n\nInvoice No: ' + invoiceData.invoice_num + '\nNet Amount: ₹ ' + invoiceData.net_amount.toFixed(2));
            resetForm();
        } else {
            alert('❌ Error saving invoice: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
        alert('❌ Error saving invoice: ' + error.message + '\n\nCheck browser console for details.');
    });
}

function resetForm() {
    rows = [];
    render();
    party.value = '';
    party.dataset.id = '';
    broker.value = '';
    broker.dataset.id = '';
    notes.value = '';
    credit.value = '0';
    cal1.value = '0';
    cal2.value = '0';
    cal3.value = '0';
    brokerPct.value = '0';
    tax.value = '0';
    getNextInvoiceNo();
    calcDueDate();
}