document.addEventListener('DOMContentLoaded', function() {
    const searchName = document.getElementById('searchName');
    const sugBox = document.getElementById('sugBox');
    const ledgerType = document.getElementById('ledgerType');
    const fromDate = document.getElementById('fromDate');
    const toDate = document.getElementById('toDate');
    const allTime = document.getElementById('allTime'); // Checkbox
    const printBtn = document.getElementById('printBtn');
    
    // Set Default Dates (Current Month)
    const date = new Date();
    toDate.valueAsDate = date;
    const firstDay = new Date(date.getFullYear(), date.getMonth(), 1);
    fromDate.valueAsDate = firstDay;

    // --- Toggle Date Fields ---
    allTime.addEventListener('change', function() {
        if(this.checked) {
            fromDate.disabled = true;
            toDate.disabled = true;
            fromDate.style.background = "#e2e8f0";
            toDate.style.background = "#e2e8f0";
        } else {
            fromDate.disabled = false;
            toDate.disabled = false;
            fromDate.style.background = "#fff";
            toDate.style.background = "#fff";
        }
    });

    // --- Autocomplete ---
    let timeout = null;
    searchName.addEventListener('input', function() {
        const val = this.value.trim();
        const type = ledgerType.value;
        sugBox.innerHTML = ''; sugBox.classList.remove('active');
        
        if (val.length < 2) return;
        
        const searchFile = (type === 'PARTY') ? 'functions/search_party.php' : 'functions/search_broker.php';
        
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            fetch(`${searchFile}?q=${encodeURIComponent(val)}`)
                .then(r => r.json())
                .then(data => {
                    if (data.length === 0) return;
                    sugBox.classList.add('active');
                    data.forEach(item => {
                        const div = document.createElement('div');
                        div.className = 'autocomplete-item';
                        div.innerText = item.name;
                        div.onclick = () => {
                            searchName.value = item.name;
                            sugBox.innerHTML = ''; sugBox.classList.remove('active');
                        };
                        sugBox.appendChild(div);
                    });
                });
        }, 300);
    });

    document.addEventListener('click', e => {
        if(e.target !== searchName) { sugBox.innerHTML = ''; sugBox.classList.remove('active'); }
    });

    ledgerType.addEventListener('change', () => { searchName.value = ''; });

    // --- Generate Ledger ---
    window.generateLedger = function() {
        const name = searchName.value.trim();
        if(!name) { alert("Please select a Name first."); return; }

        // Update Print Header
        document.getElementById('printPartyName').innerText = `${ledgerType.value}: ${name}`;
        
        if (allTime.checked) {
            document.getElementById('printDateRange').innerText = `Period: ALL TIME`;
        } else {
            document.getElementById('printDateRange').innerText = `Period: ${fromDate.value} to ${toDate.value}`;
        }

        const btn = document.querySelector('.btn-go');
        btn.innerText = "Processing..."; btn.disabled = true;

        fetch('functions/get_ledger_data.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                type: ledgerType.value,
                name: name,
                from: fromDate.value,
                to: toDate.value,
                allTime: allTime.checked // Send checkbox status
            })
        })
        .then(r => r.json())
        .then(res => {
            btn.innerText = "Generate"; btn.disabled = false;
            if(res.success) {
                renderTable(res);
                printBtn.style.display = 'inline-block';
            } else {
                alert("Error: " + res.message);
            }
        })
        .catch(e => {
            btn.innerText = "Generate"; btn.disabled = false;
            console.error(e);
            alert("System Error");
        });
    };

    function renderTable(res) {
        const tb = document.querySelector('#ledgerTable tbody');
        tb.innerHTML = '';
        
        if(res.data.length === 0) {
            tb.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:20px;">No transactions found.</td></tr>';
            return;
        }

        let sr = 1;
        res.data.forEach(row => {
            const dr = parseFloat(row.debit);
            const cr = parseFloat(row.credit);
            
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${sr++}</td>
                <td>${row.date}</td>
                <td><strong>${row.type}</strong></td>
                <td class="desc-box">${row.desc}</td>
                <td class="text-right" style="color:${dr>0?'#dc2626':''}">${dr > 0 ? dr.toFixed(2) : '-'}</td>
                <td class="text-right" style="color:${cr>0?'#16a34a':''}">${cr > 0 ? cr.toFixed(2) : '-'}</td>
                <td class="text-right fw-bold">${row.balance.toFixed(2)}</td>
            `;
            tb.appendChild(tr);
        });

        document.getElementById('ftTotalDr').innerText = res.totalDr.toFixed(2);
        document.getElementById('ftTotalCr').innerText = res.totalCr.toFixed(2);
        document.getElementById('ftBalance').innerText = res.netBalance.toFixed(2);
    }
});