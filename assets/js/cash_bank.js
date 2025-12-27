document.addEventListener('DOMContentLoaded', function () {
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
    const convRate = document.getElementById('convRate');
    const drUsd = document.getElementById('drUsd');
    const drInr = document.getElementById('drInr');
    const crUsd = document.getElementById('crUsd');
    const crInr = document.getElementById('crInr');
    const invSearch = document.getElementById('invSearch');
    const invBox = document.getElementById('invBox');

    txnDate.valueAsDate = new Date();

    // --- Modal Functions ---
    window.openModal = function () {
        resetForm();
        modal.style.display = 'block';
    };

    window.closeModal = function () {
        modal.style.display = 'none';
        resetForm();
    };

    // --- Toggle: General vs Invoice ---
    window.toggleTxnType = function () {
        const type = document.querySelector('input[name="txnCat"]:checked').value;
        if (type === 'INVOICE') {
            invBox.style.display = 'block';
        } else {
            invBox.style.display = 'none';
            invSearch.value = '';
        }
    };

    // --- Invoice Search (Autocomplete) ---
    const invSug = document.getElementById('invSug');
    let timeout = null;

    invSearch.addEventListener('input', function () {
        const val = this.value.trim();
        invSug.innerHTML = '';
        invSug.classList.remove('active');

        if (val.length < 2) return;

        clearTimeout(timeout);
        timeout = setTimeout(() => {
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
                            description.value = `Payment for ${item.invoice_num} (${item.party_name})`;
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
        if (e.target !== invSearch) {
            invSug.innerHTML = '';
            invSug.classList.remove('active');
        }
    });

    // --- Currency Logic ---
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

    // --- SUBMIT WITH STRICT VALIDATION ---
    txnForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        // 1. Basic Field Validation
        const txnCat = document.querySelector('input[name="txnCat"]:checked').value;
        if (currMode.value === 'BOTH' && !convRate.value) {
            alert('⚠️ Enter Conversion Rate.'); return;
        }
        if (!drInr.value && !crInr.value) {
            alert('⚠️ Enter Amount.'); return;
        }

        // 2. Strict Invoice Validation
        if (txnCat === 'INVOICE') {
            const invVal = invSearch.value.trim();

            if (!invVal) {
                alert('⚠️ Please select or type an Invoice Number.');
                invSearch.focus();
                return;
            }

            // Visual feedback
            const originalBtnText = saveBtn.innerText;
            saveBtn.innerText = '🔍 Checking Invoice...';
            saveBtn.disabled = true;

            try {
                // Call PHP to check DB
                const checkRes = await fetch('functions/check_invoice_exists.php?invoice_num=' + encodeURIComponent(invVal));

                // If the PHP file itself is not found or crashes
                if (!checkRes.ok) {
                    throw new Error("Network error checking invoice.");
                }

                const checkData = await checkRes.json();

                if (!checkData.exists) {
                    // STOP EVERYTHING
                    alert(`❌ Invoice "${invVal}" not foundII!\n\nPlease select a valid invoice from the suggestions.`);
                    saveBtn.innerText = originalBtnText;
                    saveBtn.disabled = false;
                    invSearch.focus();
                    return; // Exits the function here. Save will NOT proceed.
                }

            } catch (err) {
                console.error(err);
                alert("❌ Error verifying invoice. Please check console.");
                saveBtn.innerText = originalBtnText;
                saveBtn.disabled = false;
                return;
            }
        }

        // 3. Prepare Save Data (Only reached if validation passed)
        const formData = {
            id: editId.value,
            txn_date: txnDate.value,
            account_type: accountType.value,
            invoice_num: txnCat === 'INVOICE' ? invSearch.value : '',
            description: description.value,
            conversion_rate: convRate.value,
            dr_usd: drUsd.value,
            dr_inr: drInr.value,
            cr_usd: crUsd.value,
            cr_inr: crInr.value
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
                    const finalId = editId.value ? editId.value : data.id;
                    formData.id = finalId;
                    updateSessionTable(formData);
                    alert(editId.value ? "✅ Updated!" : "✅ Saved!");
                    modal.style.display = 'none';
                    resetForm();
                } else {
                    alert("❌ Error: " + data.message);
                }
            })
            .catch(err => {
                console.error(err);
                saveBtn.innerText = '💾 Save Transaction';
                saveBtn.disabled = false;
            });
    });

    // --- Update Table ---
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
        const ref = data.invoice_num ? `<span style="color:#2563eb; font-weight:bold;">${data.invoice_num}</span>` : '-';

        // COLORS SWAPPED BELOW:
        // Debit = red
        // Credit = green
        tr.innerHTML = `
            <td>${data.txn_date}</td>
            <td>${data.description}<br><small style="color:#666">${data.account_type}</small></td>
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
    window.editRow = function (id) {
        const tr = document.getElementById(`row-${id}`);
        if (!tr) return;

        const data = JSON.parse(tr.dataset.json);

        editId.value = data.id;
        txnDate.value = data.txn_date;
        accountType.value = data.account_type;
        description.value = data.description;

        if (data.invoice_num && data.invoice_num !== '') {
            document.querySelector('input[value="INVOICE"]').checked = true;
            invSearch.value = data.invoice_num;
            invBox.style.display = 'block';
        } else {
            document.querySelector('input[value="GENERAL"]').checked = true;
            invBox.style.display = 'none';
        }

        const rate = parseFloat(data.conversion_rate);
        if (rate > 0) {
            currMode.value = 'BOTH';
            convRate.disabled = false;
            convRate.value = rate;
            drUsd.disabled = false;
            drUsd.value = parseFloat(data.dr_usd) > 0 ? data.dr_usd : '';
            crUsd.disabled = false;
            crUsd.value = parseFloat(data.cr_usd) > 0 ? data.cr_usd : '';
        } else {
            currMode.value = 'INR';
            convRate.value = '';
            convRate.disabled = true;
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

    window.deleteRow = function (id) {
        if (!confirm("Are you sure you want to delete this entry?")) return;

        fetch('functions/delete_cash_entry.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const tr = document.getElementById(`row-${id}`);
                    if (tr) tr.remove();
                } else {
                    alert("Error: " + data.message);
                }
            });
    };

    function resetForm() {
        txnForm.reset();
        editId.value = "";
        modalTitle.innerText = "New Transaction";
        saveBtn.innerText = "💾 Save Transaction";
        document.querySelector('input[value="GENERAL"]').checked = true;
        invBox.style.display = 'none';
        currMode.value = 'INR';
        currMode.dispatchEvent(new Event('change'));
        txnDate.valueAsDate = new Date();
    }
});