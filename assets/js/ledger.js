document.addEventListener('DOMContentLoaded', function() {
    const searchName = document.getElementById('searchName');
    const sugBox = document.getElementById('sugBox');
    const ledgerType = document.getElementById('ledgerType');
    const fromDate = document.getElementById('fromDate');
    const toDate = document.getElementById('toDate');
    const allTime = document.getElementById('allTime');
    const printBtn = document.getElementById('printBtn');
    
    const date = new Date();
    toDate.valueAsDate = date;
    const firstDay = new Date(date.getFullYear(), date.getMonth(), 1);
    fromDate.valueAsDate = firstDay;

    allTime.addEventListener('change', function() {
        if(this.checked) {
            fromDate.disabled = true; toDate.disabled = true;
            fromDate.style.background = "#e2e8f0"; toDate.style.background = "#e2e8f0";
        } else {
            fromDate.disabled = false; toDate.disabled = false;
            fromDate.style.background = "#fff"; toDate.style.background = "#fff";
        }
    });

    let timeout = null;
    searchName.addEventListener('input', function() {
        const val = this.value.trim();
        const type = ledgerType.value;
        sugBox.innerHTML = ''; sugBox.classList.remove('active');
        if (val.length < 2) return;
        
        const searchFile = (type === 'PARTY') ? 'functions/search_party.php' : 'functions/search_broker.php';
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            fetch(`${searchFile}?q=${encodeURIComponent(val)}`).then(r => r.json()).then(data => {
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

    document.addEventListener('click', e => { if(e.target !== searchName) { sugBox.innerHTML = ''; sugBox.classList.remove('active'); } });
    ledgerType.addEventListener('change', () => { searchName.value = ''; });

    window.generateLedger = function() {
        const name = searchName.value.trim();
        if(!name) { alert("Please select a Name."); return; }

        document.getElementById('printPartyName').innerText = `${ledgerType.value}: ${name}`;
        document.getElementById('printDateRange').innerText = allTime.checked ? "Period: ALL TIME" : `Period: ${fromDate.value} to ${toDate.value}`;

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
                allTime: allTime.checked
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
            alert("System Error: " + e.message);
        });
    };

    function renderTable(res) {
        const tb = document.querySelector('#ledgerTable tbody');
        tb.innerHTML = '';
        
        if(res.data.length === 0) {
            tb.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:20px;">No transactions found.</td></tr>';
            document.getElementById('ftTotalDr').innerText = '0.00';
            document.getElementById('ftTotalCr').innerText = '0.00';
            document.getElementById('ftBalance').innerText = '0.00';
            return;
        }

        let sr = 1;
        res.data.forEach(row => {
            const dr = parseFloat(row.debit);
            const cr = parseFloat(row.credit);
            
            // LOGIC: 
            // Formula used was: Balance = Credit - Debit
            // Positive = Credit Balance (Payable) -> 'Cr'
            // Negative = Debit Balance (Receivable) -> 'Dr'
            
            const balVal = row.balance;
            let balSuffix = '';
            let balColor = '#333';

            if (balVal > 0) {
                balSuffix = ' Cr'; // Credit Balance
                balColor = '#16a34a'; // Green (Usually Payables/Income)
            } else if (balVal < 0) {
                balSuffix = ' Dr'; // Debit Balance
                balColor = '#dc2626'; // Red (Receivables/Assets)
            }

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${sr++}</td>
                <td>${row.date}</td>
                <td><strong>${row.type}</strong></td>
                <td class="desc-box">${row.desc}</td>
                <td class="text-right" style="color:#dc2626;">${dr > 0 ? dr.toFixed(2) : '-'}</td>
                <td class="text-right" style="color:#16a34a;">${cr > 0 ? cr.toFixed(2) : '-'}</td>
                <td class="text-right fw-bold" style="color:${balColor}">
                    ${Math.abs(balVal).toFixed(2)}${balSuffix}
                </td>
            `;
            tb.appendChild(tr);
        });

        document.getElementById('ftTotalDr').innerText = res.totalDr.toFixed(2);
        document.getElementById('ftTotalCr').innerText = res.totalCr.toFixed(2);
        
        // Final Net Balance Logic
        const net = res.netBalance;
        const netTxt = Math.abs(net).toFixed(2) + (net > 0 ? ' Cr' : (net < 0 ? ' Dr' : ''));
        const netColor = net > 0 ? '#16a34a' : (net < 0 ? '#dc2626' : '#333');
        
        const ftBal = document.getElementById('ftBalance');
        ftBal.innerText = netTxt;
        ftBal.style.color = netColor;
    }
});