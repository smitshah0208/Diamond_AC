document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.getElementById('filterForm');
    const searchType = document.getElementById('searchType');
    const dynamicSearchBox = document.getElementById('dynamicSearchBox');
    const searchInput = document.getElementById('searchInput');
    const dynamicLabel = document.getElementById('dynamicLabel');
    const searchSug = document.getElementById('searchSug');
    
    const tableBody = document.querySelector('#resultsTable tbody');
    const totalDrEl = document.getElementById('totalDr');
    const totalCrEl = document.getElementById('totalCr');

    // 1. Toggle Inputs based on selection
    window.toggleSearchInput = function() {
        const val = searchType.value;
        searchInput.value = ''; // Clear previous search
        
        if (val === 'ALL') {
            dynamicSearchBox.style.display = 'none';
        } else {
            dynamicSearchBox.style.display = 'block';
            if(val === 'INVOICE') dynamicLabel.innerText = "Enter Invoice Number";
            if(val === 'PARTY') dynamicLabel.innerText = "Search Party Name";
            if(val === 'BROKER') dynamicLabel.innerText = "Search Broker Name";
            searchInput.focus();
        }
    };

    // 2. Autocomplete Logic (Reusing your existing search files)
    let timeout = null;
    searchInput.addEventListener('input', function() {
        const val = this.value.trim();
        const type = searchType.value;
        
        searchSug.innerHTML = '';
        searchSug.classList.remove('active');
        
        if (val.length < 2) return;
        
        // Determine which file to call
        let searchFile = '';
        if(type === 'INVOICE') searchFile = 'functions/search_invoices.php';
        else if(type === 'PARTY') searchFile = 'functions/search_party.php';
        else if(type === 'BROKER') searchFile = 'functions/search_broker.php';
        else return;

        clearTimeout(timeout);
        timeout = setTimeout(() => {
            fetch(searchFile + '?q=' + encodeURIComponent(val))
                .then(res => res.json())
                .then(data => {
                    if (data.length === 0) return;
                    data.forEach(item => {
                        const div = document.createElement('div');
                        div.className = 'autocomplete-item';
                        
                        // Handle different API responses
                        let displayText = '';
                        let fillValue = '';

                        if(type === 'INVOICE') {
                            displayText = `<strong>${item.invoice_num}</strong> - ${item.party_name}`;
                            fillValue = item.invoice_num;
                        } else {
                            displayText = `<strong>${item.name}</strong>`;
                            fillValue = item.name;
                        }

                        div.innerHTML = displayText;
                        div.onclick = () => {
                            searchInput.value = fillValue;
                            searchSug.innerHTML = '';
                            searchSug.classList.remove('active');
                        };
                        searchSug.appendChild(div);
                    });
                    searchSug.classList.add('active');
                });
        }, 300);
    });

    // Close autocomplete on click outside
    document.addEventListener('click', e => {
        if (e.target !== searchInput) {
            searchSug.innerHTML = '';
            searchSug.classList.remove('active');
        }
    });

    // 3. Search / Filter Submission
    filterForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const type = searchType.value;
        const term = searchInput.value;
        const from = document.getElementById('fromDate').value;
        const to = document.getElementById('toDate').value;

        // Validations
        if (type !== 'ALL' && term.length < 1) {
            alert("Please enter a search term.");
            return;
        }

        const btn = filterForm.querySelector('.search-btn');
        btn.innerText = "Searching...";
        btn.disabled = true;

        fetch('functions/filter_cash_bank.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ type, term, from, to })
        })
        .then(res => res.json())
        .then(res => {
            btn.innerText = "🔍 Search Records";
            btn.disabled = false;
            
            if(res.success) {
                renderTable(res.data);
            } else {
                alert("Error: " + res.message);
            }
        });
    });

    function renderTable(rows) {
        tableBody.innerHTML = '';
        let sumDr = 0;
        let sumCr = 0;

        if (rows.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="6" class="empty-row">No records found matching your criteria.</td></tr>';
            totalDrEl.innerText = '₹ 0.00';
            totalCrEl.innerText = '₹ 0.00';
            return;
        }

        rows.forEach(row => {
            const dr = parseFloat(row.dr_inr);
            const cr = parseFloat(row.cr_inr);
            sumDr += dr;
            sumCr += cr;

            const badgeClass = row.account_type === 'Bank' ? 'badge-bank' : 'badge-cash';
            
            // Format Particulars
            let particulars = row.description;
            if(row.related_name) {
                particulars = `<strong style="color:#1e293b">${row.related_name}</strong> <span style="font-size:11px;color:#64748b">(${row.party_or_broker})</span><br><span style="font-size:12px;color:#64748b">${row.description}</span>`;
            }

            // Format Invoice
            let invoice = row.invoice_num ? `<span style="font-weight:600;color:#2563eb">${row.invoice_num}</span>` : '-';

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${row.txn_date}</td>
                <td><span class="badge ${badgeClass}">${row.account_type}</span></td>
                <td>${particulars}</td>
                <td>${invoice}</td>
                <td style="text-align:right; color:${dr>0?'#ef4444':'#cbd5e1'}">${dr > 0 ? '₹'+dr.toFixed(2) : '-'}</td>
                <td style="text-align:right; color:${cr>0?'#10b981':'#cbd5e1'}">${cr > 0 ? '₹'+cr.toFixed(2) : '-'}</td>
            `;
            tableBody.appendChild(tr);
        });

        totalDrEl.innerText = '₹ ' + sumDr.toFixed(2);
        totalCrEl.innerText = '₹ ' + sumCr.toFixed(2);
    }
});