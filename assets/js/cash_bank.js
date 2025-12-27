document.addEventListener('DOMContentLoaded', function () {
    // --- Elements ---
    const modal = document.getElementById('entryModal');
    const txnForm = document.getElementById('txnForm');
    const currMode = document.getElementById('currMode');
    const editId = document.getElementById('editId');
    const saveBtn = document.getElementById('saveBtn');
    const modalTitle = document.getElementById('modalTitle');

    // Inputs
    const txnDate = document.getElementById('txnDate');
    const accountType = document.getElementById('account_type');
    const description = document.getElementById('description');

    // Sections
    const generalSection = document.getElementById('generalSection');
    const invoiceSection = document.getElementById('invoiceSection');
    const genSearchBox = document.getElementById('genSearchBox');
    const genSearchInput = document.getElementById('genSearchInput');
    const genSearchLabel = document.getElementById('genSearchLabel');
    const genSug = document.getElementById('genSug');

    const invSearch = document.getElementById('invSearch');
    const invSug = document.getElementById('invSug');
    const invEntityDisplay = document.getElementById('invEntityDisplay');

    // Currency
    const convRate = document.getElementById('convRate');
    const drUsd = document.getElementById('drUsd');
    const drInr = document.getElementById('drInr');
    const crUsd = document.getElementById('crUsd');
    const crInr = document.getElementById('crInr');

    // State Variables
    let selectedRelatedName = "";
    let selectedPartyOrBroker = "";

    // Invoice temp vars
    let invPartyName = "";
    let invBrokerName = "";

    txnDate.valueAsDate = new Date();

    // ============================================
    // MODAL FUNCTIONS
    // ============================================
    window.openModal = function () {
        resetForm();
        modal.style.display = 'block';
    };

    window.closeModal = function () {
        modal.style.display = 'none';
        resetForm();
    };

    function resetForm() {
        txnForm.reset();
        editId.value = "";
        modalTitle.innerText = "New Transaction";
        saveBtn.innerText = "💾 Save Transaction";

        // Reset Logic
        document.querySelector('input[name="txnCat"][value="GENERAL"]').checked = true;
        handleTxnCatChange();

        selectedRelatedName = "";
        selectedPartyOrBroker = "";
        invPartyName = "";
        invBrokerName = "";
        invEntityDisplay.innerText = "";
        invEntityDisplay.style.display = 'none';

        currMode.value = 'INR';
        currMode.dispatchEvent(new Event('change'));
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
            // Default to None if switching back
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

        // Only clear if user is actively changing it (not during edit load)
        if (document.activeElement.name === 'genLinkType') {
            genSearchInput.value = '';
            selectedRelatedName = '';
            selectedPartyOrBroker = '';
        }

        if (linkType === 'NONE') {
            genSearchBox.style.display = 'none';
        } else {
            genSearchBox.style.display = 'block';
            selectedPartyOrBroker = linkType;
            genSearchLabel.innerText = linkType === 'PARTY' ? "Search Party Name" : "Search Broker Name";
        }
    };

    window.updateInvEntityDisplay = function () {
        const typeEl = document.querySelector('input[name="invLinkType"]:checked');
        if (!typeEl) return;

        const type = typeEl.value;
        let name = "";

        // If we are just clicking radios, use temp vars. 
        // If editing, 'selectedRelatedName' holds the loaded value.
        if (editId.value && selectedRelatedName && selectedPartyOrBroker === type) {
            name = selectedRelatedName;
        } else {
            if (type === 'PARTY') name = invPartyName;
            if (type === 'BROKER') name = invBrokerName;
        }

        if (!name) name = "(No Name Found)";

        invEntityDisplay.innerText = "Selected: " + name;
        invEntityDisplay.style.display = 'block';

        selectedRelatedName = name;
        selectedPartyOrBroker = type;

        if (invSearch.value && name && !editId.value) {
            description.value = `Payment for ${invSearch.value} (${name})`;
        }
    };

    // ============================================
    // AUTOCOMPLETE LOGIC (General & Invoice)
    // ============================================
    // (General Search)
    let genTimeout = null;
    genSearchInput.addEventListener('input', function () {
        const val = this.value.trim();
        genSug.innerHTML = '';
        genSug.classList.remove('active');
        if (val.length < 2) return;

        const type = document.querySelector('input[name="genLinkType"]:checked').value;
        const searchFile = type === 'PARTY' ? 'functions/search_party.php' : 'functions/search_broker.php';

        clearTimeout(genTimeout);
        genTimeout = setTimeout(() => {
            fetch(searchFile + '?q=' + encodeURIComponent(val))
                .then(res => res.json())
                .then(data => {
                    if (data.length === 0) return;
                    data.forEach(item => {
                        const div = document.createElement('div');
                        div.className = 'autocomplete-item';
                        div.innerHTML = `<strong>${item.name}</strong>`;
                        div.onclick = () => {
                            genSearchInput.value = item.name;
                            selectedRelatedName = item.name;
                            selectedPartyOrBroker = type;
                            genSug.innerHTML = '';
                            genSug.classList.remove('active');
                        };
                        genSug.appendChild(div);
                    });
                    genSug.classList.add('active');
                });
        }, 300);
    });

    // (Invoice Search)
    let invTimeout = null;
    invSearch.addEventListener('input', function () {
        const val = this.value.trim();
        invSug.innerHTML = '';
        invSug.classList.remove('active');

        invPartyName = ""; invBrokerName = "";
        invEntityDisplay.style.display = 'none';
        document.querySelectorAll('input[name="invLinkType"]').forEach(r => r.checked = false);

        if (val.length < 2) return;

        clearTimeout(invTimeout);
        invTimeout = setTimeout(() => {
            fetch('functions/search_invoices.php?q=' + encodeURIComponent(val))
                .then(res => res.json())
                .then(data => {
                    if (data.length === 0) return;
                    data.forEach(item => {
                        const div = document.createElement('div');
                        div.className = 'autocomplete-item';
                        div.innerHTML = `<strong>${item.invoice_num}</strong> - ${item.party_name}`;
                        div.onclick = () => {
                            invSearch.value = item.invoice_num;
                            invPartyName = item.party_name;
                            invBrokerName = item.broker_name;

                            document.querySelector('input[name="invLinkType"][value="PARTY"]').checked = true;
                            updateInvEntityDisplay();

                            invSug.innerHTML = '';
                            invSug.classList.remove('active');
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
    // CURRENCY & LOCKS
    // ============================================
    currMode.addEventListener('change', () => {
        const isBoth = currMode.value === 'BOTH';
        convRate.disabled = !isBoth;
        drUsd.disabled = !isBoth;
        crUsd.disabled = !isBoth;
        if (!isBoth) { convRate.value = ''; drUsd.value = ''; crUsd.value = ''; }
        checkLocks();
    });

    function checkLocks() {
        const hasDebit = drUsd.value || drInr.value;
        const hasCredit = crUsd.value || crInr.value;
        if (hasDebit) { crUsd.disabled = true; crInr.disabled = true; }
        else { crInr.disabled = false; if (currMode.value === 'BOTH') crUsd.disabled = false; }
        if (hasCredit) { drUsd.disabled = true; drInr.disabled = true; }
        else { drInr.disabled = false; if (currMode.value === 'BOTH') drUsd.disabled = false; }
    }

    function calculate() {
        const rate = parseFloat(convRate.value) || 0;
        if (rate <= 0) return;
        if (document.activeElement === drUsd || document.activeElement === convRate) {
            const val = parseFloat(drUsd.value) || 0;
            if (val) drInr.value = (val * rate).toFixed(2);
        }
        if (document.activeElement === crUsd || document.activeElement === convRate) {
            const val = parseFloat(crUsd.value) || 0;
            if (val) crInr.value = (val * rate).toFixed(2);
        }
    }
    [drUsd, drInr, crUsd, crInr].forEach(i => i.addEventListener('input', checkLocks));
    [convRate, drUsd, crUsd].forEach(i => i.addEventListener('input', calculate));

    // ============================================
    // SAVE / UPDATE LOGIC
    // ============================================
    txnForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        const cat = document.querySelector('input[name="txnCat"]:checked').value;

        // VALIDATION
        if (cat === 'INVOICE') {
            if (!invSearch.value) { alert('⚠️ Select an Invoice.'); return; }
            if (!selectedRelatedName || selectedRelatedName === "(No Name Found)") {
                alert('⚠️ Valid Party/Broker name required.'); return;
            }
        } else {
            const genType = document.querySelector('input[name="genLinkType"]:checked').value;
            if (genType !== 'NONE' && !selectedRelatedName) {
                alert('⚠️ Enter name in search box.'); return;
            }
        }

        if (currMode.value === 'BOTH' && !convRate.value) { alert('⚠️ Enter Rate.'); return; }
        if (!drInr.value && !crInr.value) { alert('⚠️ Enter Amount.'); return; }

        const formData = {
            id: editId.value,
            txn_date: txnDate.value,
            account_type: accountType.value,
            description: description.value,
            conversion_rate: convRate.value,
            dr_usd: drUsd.value,
            dr_inr: drInr.value,
            cr_usd: crUsd.value,
            cr_inr: crInr.value,
            invoice_num: cat === 'INVOICE' ? invSearch.value : '',
            party_or_broker: selectedPartyOrBroker,
            related_name: selectedRelatedName
        };

        const endpoint = editId.value ? 'functions/update_cash_entry.php' : 'functions/save_cash_bank.php';
        saveBtn.innerText = '⏳ Saving...';
        saveBtn.disabled = true;

        fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        })
            .then(res => res.json())
            .then(data => {
                saveBtn.innerText = '💾 Save Transaction';
                saveBtn.disabled = false;
                if (data.success) {
                    formData.id = editId.value ? editId.value : data.id;
                    updateSessionTable(formData);
                    alert(editId.value ? "✅ Updated!" : "✅ Saved!");
                    closeModal();
                } else {
                    alert("❌ Error: " + data.message);
                }
            });
    });

    // ============================================
    // EDIT ROW (Crucial Update)
    // ============================================
    window.editRow = function (id) {
        const tr = document.getElementById(`row-${id}`);
        if (!tr) return;
        const data = JSON.parse(tr.dataset.json);

        // 1. Basic Fields
        editId.value = data.id;
        txnDate.value = data.txn_date;
        accountType.value = data.account_type;
        description.value = data.description;

        // 2. Set State Variables
        selectedRelatedName = data.related_name || "";
        selectedPartyOrBroker = data.party_or_broker || "";

        // 3. Determine Category (Invoice vs General)
        if (data.invoice_num && data.invoice_num !== '') {
            // ---> INVOICE MODE
            document.querySelector('input[name="txnCat"][value="INVOICE"]').checked = true;
            handleTxnCatChange();

            invSearch.value = data.invoice_num;

            // Set Link Type (Party vs Broker)
            if (data.party_or_broker) {
                const radio = document.querySelector(`input[name="invLinkType"][value="${data.party_or_broker}"]`);
                if (radio) radio.checked = true;
            }

            // Update Display
            invEntityDisplay.innerText = "Selected: " + (data.related_name || "(Unknown)");
            invEntityDisplay.style.display = 'block';

        } else {
            // ---> GENERAL MODE
            document.querySelector('input[name="txnCat"][value="GENERAL"]').checked = true;
            handleTxnCatChange();

            // Set Link Type (None, Party, Broker)
            if (data.party_or_broker) {
                const radio = document.querySelector(`input[name="genLinkType"][value="${data.party_or_broker}"]`);
                if (radio) {
                    radio.checked = true;
                    // Manually trigger visual update without clearing vars
                    genSearchBox.style.display = 'block';
                    genSearchLabel.innerText = data.party_or_broker === 'PARTY' ? "Search Party Name" : "Search Broker Name";
                    genSearchInput.value = data.related_name || "";
                }
            } else {
                document.querySelector('input[name="genLinkType"][value="NONE"]').checked = true;
                genSearchBox.style.display = 'none';
            }
        }

        // 4. Currency Fields
        const rate = parseFloat(data.conversion_rate);
        if (rate > 0) {
            currMode.value = 'BOTH';
            convRate.disabled = false; convRate.value = rate;
            drUsd.disabled = false; drUsd.value = parseFloat(data.dr_usd) > 0 ? data.dr_usd : '';
            crUsd.disabled = false; crUsd.value = parseFloat(data.cr_usd) > 0 ? data.cr_usd : '';
        } else {
            currMode.value = 'INR';
            convRate.value = ''; convRate.disabled = true;
            drUsd.value = ''; drUsd.disabled = true;
            crUsd.value = ''; crUsd.disabled = true;
        }

        drInr.value = parseFloat(data.dr_inr) > 0 ? data.dr_inr : '';
        crInr.value = parseFloat(data.cr_inr) > 0 ? data.cr_inr : '';

        modalTitle.innerText = "Edit Transaction";
        saveBtn.innerText = "💾 Update Entry";
        checkLocks();
        modal.style.display = 'block';
    };

    // --- Delete & Table Render ---
    window.deleteRow = function (id) {
        if (!confirm("Delete?")) return;
        fetch('functions/delete_cash_entry.php', { method: 'POST', body: JSON.stringify({ id }) })
            .then(res => res.json()).then(d => { if (d.success) document.getElementById(`row-${id}`).remove(); });
    };
    // --- Update Table (Session) ---
    function updateSessionTable(data) {
        const existingRow = document.getElementById(`row-${data.id}`);
        if (existingRow) existingRow.remove();

        const tbody = document.querySelector('#previewTable tbody');
        if (tbody.querySelector('.empty-row')) tbody.innerHTML = '';

        const tr = document.createElement('tr');
        tr.id = `row-${data.id}`;
        tr.dataset.json = JSON.stringify(data);

        const drVal = parseFloat(data.dr_inr) > 0 ? '₹' + parseFloat(data.dr_inr).toFixed(2) : '-';
        const crVal = parseFloat(data.cr_inr) > 0 ? '₹' + parseFloat(data.cr_inr).toFixed(2) : '-';

        // --- NEW: Account Type Badge ---
        const badgeColor = data.account_type === 'Bank' ? '#2563eb' : '#059669'; // Blue for Bank, Green for Cash
        const bgBadge = data.account_type === 'Bank' ? '#dbeafe' : '#d1fae5';
        const acBadge = `<span style="background:${bgBadge}; color:${badgeColor}; padding:3px 8px; border-radius:10px; font-size:11px; font-weight:bold; text-transform:uppercase;">${data.account_type}</span>`;

        let particulars = data.description;
        if (data.related_name) {
            const typeLabel = data.party_or_broker ? ` <small style='color:#666'>(${data.party_or_broker.charAt(0)})</small>` : '';
            particulars = `<strong style='color:#111'>${data.related_name}</strong>${typeLabel}<br><small>${data.description}</small>`;
        }

        let ref = data.invoice_num ? `<span style="color:#2563eb; font-weight:bold;">${data.invoice_num}</span>` : '-';

        tr.innerHTML = `
            <td>${data.txn_date}<br>${acBadge}</td> <!-- Added Badge Here -->
            <td>${particulars}</td>
            <td>${ref}</td>
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