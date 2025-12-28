document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('entryModal');
    const txnForm = document.getElementById('txnForm');
    const editId = document.getElementById('editId');
    const saveBtn = document.getElementById('saveBtn');
    const modalTitle = document.getElementById('modalTitle');

    // Inputs
    const txnDate = document.getElementById('txnDate');
    const accountType = document.getElementById('account_type');
    const description = document.getElementById('description');
    const payCurrency = document.getElementById('payCurrency');

    // Sections & Search
    const generalSection = document.getElementById('generalSection');
    const invoiceSection = document.getElementById('invoiceSection');
    const genSearchBox = document.getElementById('genSearchBox');
    const genSearchInput = document.getElementById('genSearchInput');
    const genSearchLabel = document.getElementById('genSearchLabel');
    const genSug = document.getElementById('genSug');

    const invSearch = document.getElementById('invSearch');
    const invSug = document.getElementById('invSug');
    const invEntityDisplay = document.getElementById('invEntityDisplay');
    const invTaxInfo = document.getElementById('invTaxInfo');

    // Amounts
    const convRate = document.getElementById('convRate');
    const drUsd = document.getElementById('drUsd');
    const drLocal = document.getElementById('drLocal');
    const crUsd = document.getElementById('crUsd');
    const crLocal = document.getElementById('crLocal');

    // State Variables
    let selectedRelatedName = "";
    let selectedPartyOrBroker = "";
    
    // Temp Vars for Invoice
    let invPartyName = "";
    let invBrokerName = "";
    let invTaxUsd = 0;
    let invTaxLocal = 0;

    txnDate.valueAsDate = new Date();

    // ============================================
    // MODAL FUNCTIONS
    // ============================================
    window.openModal = function () { resetForm(); modal.style.display = 'block'; };
    window.closeModal = function () { modal.style.display = 'none'; resetForm(); };

    function resetForm() {
        txnForm.reset();
        editId.value = "";
        modalTitle.innerText = "New Transaction";
        saveBtn.innerText = "💾 Save Transaction";

        document.querySelector('input[name="txnCat"][value="GENERAL"]').checked = true;
        handleTxnCatChange();

        selectedRelatedName = "";
        selectedPartyOrBroker = "";
        
        // Reset Invoice Temp Data
        invPartyName = ""; invBrokerName = ""; invTaxUsd = 0; invTaxLocal = 0;
        invEntityDisplay.innerText = ""; invEntityDisplay.style.display = 'none';
        invTaxInfo.style.display = 'none';

        payCurrency.value = 'Local';
        updateLocks(); 
        txnDate.valueAsDate = new Date();
    }

    // ============================================
    // TOGGLE & DISPLAY LOGIC
    // ============================================
    window.handleTxnCatChange = function () {
        const cat = document.querySelector('input[name="txnCat"]:checked').value;
        if (cat === 'GENERAL') {
            generalSection.classList.add('active');
            invoiceSection.classList.remove('active');
            if (!editId.value) {
                document.querySelector('input[name="genLinkType"][value="NONE"]').checked = true;
                handleGenLinkChange();
            }
        } else {
            generalSection.classList.remove('active');
            invoiceSection.classList.add('active');
        }
    };

    window.handleGenLinkChange = function () {
        const linkType = document.querySelector('input[name="genLinkType"]:checked').value;
        if (document.activeElement.name === 'genLinkType') {
            genSearchInput.value = ''; selectedRelatedName = ''; selectedPartyOrBroker = '';
        }
        if (linkType === 'NONE') {
            genSearchBox.style.display = 'none';
            selectedPartyOrBroker = 'GENERAL';
            selectedRelatedName = 'General'; 
        } else {
            genSearchBox.style.display = 'block';
            selectedPartyOrBroker = linkType;
            genSearchLabel.innerText = linkType === 'PARTY' ? "Search / Enter Party Name" : "Search / Enter Broker Name";
        }
    };

    window.updateInvEntityDisplay = function () {
        const typeEl = document.querySelector('input[name="invLinkType"]:checked');
        if (!typeEl) return;
        const type = typeEl.value;
        let name = "";

        invTaxInfo.style.display = 'none';

        if (type === 'TAX') {
            selectedPartyOrBroker = 'TAX';
            selectedRelatedName = 'TAX';
            name = "Tax Payment";
            
            invTaxInfo.style.display = 'block';
            document.getElementById('txtTaxUsd').innerText = invTaxUsd > 0 ? `USD: $${invTaxUsd}` : "";
            document.getElementById('txtTaxLocal').innerText = invTaxLocal > 0 ? `Local: ${invTaxLocal}` : "";
        } else {
            selectedPartyOrBroker = type;
            if (editId.value && selectedRelatedName && selectedPartyOrBroker === type) {
                name = selectedRelatedName;
            } else {
                if (type === 'PARTY') name = invPartyName;
                if (type === 'BROKER') name = invBrokerName;
            }
            selectedRelatedName = name;
        }

        if (!name) name = "(No Name Found)";
        invEntityDisplay.innerText = "Selected: " + name;
        invEntityDisplay.style.display = 'block';
    };

    // ============================================
    // SEARCH LOGIC
    // ============================================
    let genTimeout = null;
    genSearchInput.addEventListener('input', function () {
        const val = this.value; 
        selectedRelatedName = val.trim();
        const type = document.querySelector('input[name="genLinkType"]:checked').value;
        selectedPartyOrBroker = type;

        genSug.innerHTML = ''; genSug.classList.remove('active');
        if (val.trim().length < 2) return;

        const searchFile = type === 'PARTY' ? 'functions/search_party.php' : 'functions/search_broker.php';
        clearTimeout(genTimeout);
        genTimeout = setTimeout(() => {
            fetch(searchFile + '?q=' + encodeURIComponent(val.trim())).then(r => r.json()).then(d => {
                if (d.length === 0) return;
                d.forEach(i => {
                    const div = document.createElement('div');
                    div.className = 'autocomplete-item';
                    div.innerHTML = `<strong>${i.name}</strong>`;
                    div.onclick = () => {
                        genSearchInput.value = i.name;
                        selectedRelatedName = i.name;
                        selectedPartyOrBroker = type;
                        genSug.innerHTML = ''; genSug.classList.remove('active');
                    };
                    genSug.appendChild(div);
                });
                genSug.classList.add('active');
            });
        }, 300);
    });

    let invTimeout = null;
    invSearch.addEventListener('input', function () {
        const val = this.value.trim();
        invSug.innerHTML = ''; invSug.classList.remove('active');
        invPartyName = ""; invBrokerName = ""; invTaxUsd=0; invTaxLocal=0;
        invEntityDisplay.style.display = 'none'; invTaxInfo.style.display = 'none';
        document.querySelectorAll('input[name="invLinkType"]').forEach(r => r.checked = false);

        if (val.length < 2) return;
        clearTimeout(invTimeout);
        invTimeout = setTimeout(() => {
            fetch('functions/search_invoices.php?q=' + encodeURIComponent(val)).then(r => r.json()).then(d => {
                if (d.length === 0) return;
                d.forEach(i => {
                    const div = document.createElement('div');
                    div.className = 'autocomplete-item';
                    div.innerHTML = `<strong>${i.invoice_num}</strong> - ${i.party_name}`;
                    div.onclick = () => {
                        invSearch.value = i.invoice_num;
                        invPartyName = i.party_name;
                        invBrokerName = i.broker_name;
                        invTaxUsd = parseFloat(i.tax_usd || 0);
                        invTaxLocal = parseFloat(i.tax_local || 0);

                        document.querySelector('input[name="invLinkType"][value="PARTY"]').checked = true;
                        updateInvEntityDisplay();
                        invSug.innerHTML = ''; invSug.classList.remove('active');
                    };
                    invSug.appendChild(div);
                });
                invSug.classList.add('active');
            });
        }, 300);
    });

    document.addEventListener('click', e => {
        if (e.target !== genSearchInput) { genSug.innerHTML = ''; genSug.classList.remove('active'); }
        if (e.target !== invSearch) { invSug.innerHTML = ''; invSug.classList.remove('active'); }
    });

    // ============================================
    // MUTUAL EXCLUSION & LOCKS
    // ============================================
    payCurrency.addEventListener('change', () => {
        convRate.value = ''; 
        drUsd.value = ''; drLocal.value = ''; 
        crUsd.value = ''; crLocal.value = '';
        updateLocks();
    });

    function handleDebitInput() {
        crUsd.value = ''; crLocal.value = '';
        calculate(); updateLocks();
    }
    function handleCreditInput() {
        drUsd.value = ''; drLocal.value = '';
        calculate(); updateLocks();
    }

    function updateLocks() {
        const isDollar = payCurrency.value === 'Dollar';
        const hasDebit = parseFloat(drUsd.value) || parseFloat(drLocal.value);
        const hasCredit = parseFloat(crUsd.value) || parseFloat(crLocal.value);

        convRate.disabled = !isDollar;

        if (hasCredit) { drLocal.disabled = true; drUsd.disabled = true; } 
        else { drLocal.disabled = isDollar; drUsd.disabled = !isDollar; }

        if (hasDebit) { crLocal.disabled = true; crUsd.disabled = true; } 
        else { crLocal.disabled = isDollar; crUsd.disabled = !isDollar; }
    }

    [drUsd, drLocal].forEach(el => el.addEventListener('input', handleDebitInput));
    [crUsd, crLocal].forEach(el => el.addEventListener('input', handleCreditInput));
    convRate.addEventListener('input', calculate);

    function calculate() {
        if (payCurrency.value !== 'Dollar') return;
        const rate = parseFloat(convRate.value) || 0;
        
        const dUsd = parseFloat(drUsd.value) || 0;
        if (dUsd && rate) drLocal.value = (dUsd * rate).toFixed(2);
        else if (document.activeElement === drUsd || document.activeElement === convRate) if(!dUsd) drLocal.value = ''; 

        const cUsd = parseFloat(crUsd.value) || 0;
        if (cUsd && rate) crLocal.value = (cUsd * rate).toFixed(2);
        else if (document.activeElement === crUsd || document.activeElement === convRate) if(!cUsd) crLocal.value = '';
    }

    // ============================================
    // SAVE LOGIC
    // ============================================
    txnForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        const cat = document.querySelector('input[name="txnCat"]:checked').value;

        if (cat === 'INVOICE') {
            if (!invSearch.value) { alert('Select Invoice'); return; }
            const linkType = document.querySelector('input[name="invLinkType"]:checked');
            if (!linkType) { alert('Select Payment For (Party/Broker/Tax)'); return; }
            
            // STRICT CHECK
            if ((!selectedRelatedName || selectedRelatedName === "(No Name Found)") && linkType.value !== 'TAX') {
                alert('⚠️ The selected Invoice does not have a valid Name for this type.');
                return;
            }
        } else {
            const genType = document.querySelector('input[name="genLinkType"]:checked').value;
            if (genType !== 'NONE' && (!selectedRelatedName || selectedRelatedName.trim() === '')) {
                alert('⚠️ Enter name in search box.'); return;
            }
        }

        if (payCurrency.value === 'Dollar' && !convRate.value) { alert('Enter Rate'); return; }
        if (!drLocal.value && !crLocal.value) { alert('Enter Amount'); return; }

        const finalPob = (cat === 'GENERAL' && selectedPartyOrBroker === 'GENERAL') ? 'GENERAL' : selectedPartyOrBroker;
        
        const formData = {
            id: editId.value,
            txn_date: txnDate.value,
            account_type: accountType.value,
            description: description.value,
            payment_currency: payCurrency.value,
            conversion_rate: convRate.value,
            dr_usd: drUsd.value,
            dr_local: drLocal.value,
            cr_usd: crUsd.value,
            cr_local: crLocal.value,
            invoice_num: cat === 'INVOICE' ? invSearch.value : '',
            party_or_broker: finalPob,
            related_name: selectedRelatedName ? selectedRelatedName.trim() : ''
        };

        const endpoint = editId.value ? 'functions/update_cash_entry.php' : 'functions/save_cash_bank.php';
        saveBtn.innerText = '⏳ Saving...'; saveBtn.disabled = true;

        fetch(endpoint, {
            method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(formData)
        })
        .then(res => res.json())
        .then(data => {
            saveBtn.innerText = '💾 Save Transaction'; saveBtn.disabled = false;
            if (data.success) {
                // If it was a new save, add ID and calculated type
                formData.id = editId.value ? editId.value : data.id;
                formData.transaction_type = data.txn_type; // Use backend calculated type
                updateSessionTable(formData);
                alert("✅ Saved!");
                closeModal();
            } else {
                alert("❌ Error: " + data.message);
            }
        });
    });

    // ============================================
    // EDIT & DELETE
    // ============================================
    window.editRow = function (id) {
        const tr = document.getElementById(`row-${id}`);
        if (!tr) return;
        const data = JSON.parse(tr.dataset.json);

        resetForm();

        editId.value = data.id;
        txnDate.value = data.txn_date;
        accountType.value = data.account_type;
        description.value = data.description;
        payCurrency.value = data.payment_currency || 'Local';
        
        if (data.invoice_num) {
            document.querySelector('input[name="txnCat"][value="INVOICE"]').checked = true;
            handleTxnCatChange();
            invSearch.value = data.invoice_num;
            
            if (data.party_or_broker === 'TAX') {
                document.querySelector('input[name="invLinkType"][value="TAX"]').checked = true;
                selectedPartyOrBroker = 'TAX';
            } else {
                const radio = document.querySelector(`input[name="invLinkType"][value="${data.party_or_broker}"]`);
                if (radio) radio.checked = true;
                selectedPartyOrBroker = data.party_or_broker;
            }
            selectedRelatedName = data.related_name;
            invEntityDisplay.innerText = "Selected: " + data.related_name;
            invEntityDisplay.style.display = 'block';
            
        } else {
            document.querySelector('input[name="txnCat"][value="GENERAL"]').checked = true;
            handleTxnCatChange();
            if (data.party_or_broker === 'GENERAL') {
                document.querySelector('input[name="genLinkType"][value="NONE"]').checked = true;
                selectedPartyOrBroker = 'GENERAL';
            } else {
                const radio = document.querySelector(`input[name="genLinkType"][value="${data.party_or_broker}"]`);
                if (radio) radio.checked = true;
                handleGenLinkChange();
                genSearchInput.value = data.related_name;
                selectedPartyOrBroker = data.party_or_broker;
                selectedRelatedName = data.related_name;
            }
        }

        // Fill Amounts & Locks
        convRate.value = parseFloat(data.conversion_rate) > 0 ? data.conversion_rate : '';
        drUsd.value = parseFloat(data.dr_usd) > 0 ? data.dr_usd : '';
        drLocal.value = parseFloat(data.dr_local) > 0 ? data.dr_local : '';
        crUsd.value = parseFloat(data.cr_usd) > 0 ? data.cr_usd : '';
        crLocal.value = parseFloat(data.cr_local) > 0 ? data.cr_local : '';
        updateLocks();

        modalTitle.innerText = "Edit Transaction";
        saveBtn.innerText = "💾 Update Entry";
        modal.style.display = 'block';
    };
    
    window.deleteRow = function (id) {
        if (!confirm("Delete?")) return;
        fetch('functions/delete_cash_entry.php', { method: 'POST', body: JSON.stringify({ id }) })
            .then(res => res.json()).then(d => { if (d.success) document.getElementById(`row-${id}`).remove(); });
    };

    function updateSessionTable(data) {
        const existingRow = document.getElementById(`row-${data.id}`);
        if (existingRow) existingRow.remove();

        const tbody = document.querySelector('#previewTable tbody');
        if (tbody.querySelector('.empty-row')) tbody.innerHTML = '';

        const tr = document.createElement('tr');
        tr.id = `row-${data.id}`;
        tr.dataset.json = JSON.stringify(data);

        const drVal = parseFloat(data.dr_local) > 0 ? data.dr_local : '-';
        const crVal = parseFloat(data.cr_local) > 0 ? data.cr_local : '-';
        
        const badgeColor = data.account_type === 'Bank' ? '#2563eb' : '#059669';
        const bgBadge = data.account_type === 'Bank' ? '#dbeafe' : '#d1fae5';
        const acBadge = `<span style="background:${bgBadge}; color:${badgeColor}; padding:3px 8px; border-radius:10px; font-size:11px; font-weight:bold;">${data.account_type}</span>`;

        let particulars = data.description;
        if(data.party_or_broker === 'TAX') particulars = `<strong style='color:#ea580c'>TAX PAYMENT</strong><br><small>${data.description}</small>`;
        else if (data.related_name && data.party_or_broker !== 'GENERAL') {
             particulars = `<strong>${data.related_name}</strong> <small>(${data.party_or_broker})</small><br><small>${data.description}</small>`;
        }

        tr.innerHTML = `
            <td>${data.txn_date}<br>${acBadge}</td>
            <td>${particulars}</td>
            <td>${data.invoice_num || '-'}</td>
            <td style="text-align:right; color:${drVal !== '-' ? 'red' : '#ccc'}">${drVal}</td>
            <td style="text-align:right; color:${crVal !== '-' ? 'green' : '#ccc'}">${crVal}</td>
            <td style="text-align:center;">
                <button class="btn-icon btn-edit" onclick="editRow(${data.id})">✎</button>
                <button class="btn-icon btn-del" onclick="deleteRow(${data.id})">🗑</button>
            </td>
        `;
        tbody.prepend(tr);
    }
});