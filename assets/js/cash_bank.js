// File: assets/js/cash_bank.js

document.addEventListener('DOMContentLoaded', function() {
    
    // --- Select Elements ---
    const txnForm = document.getElementById('txnForm');
    const convRate = document.getElementById('convRate');
    const drUsd = document.getElementById('drUsd');
    const crUsd = document.getElementById('crUsd');
    const drInr = document.getElementById('drInr');
    const crInr = document.getElementById('crInr');
    const accountType = document.getElementById('account_type');
    const description = document.getElementById('description');

    // --- Auto Calculation Logic ---
    function calculate() {
        const rate = parseFloat(convRate.value) || 0;
        
        if (rate > 0) {
            // If editing Dr($), update Dr(Rs)
            if (document.activeElement === drUsd || document.activeElement === convRate) {
                const val = parseFloat(drUsd.value) || 0;
                drInr.value = (val * rate).toFixed(2);
            }
            // If editing Cr($), update Cr(Rs)
            if (document.activeElement === crUsd || document.activeElement === convRate) {
                const val = parseFloat(crUsd.value) || 0;
                crInr.value = (val * rate).toFixed(2);
            }
        }
    }

    [convRate, drUsd, crUsd].forEach(input => {
        input.addEventListener('input', calculate);
    });

    // --- Save Logic (AJAX) ---
    txnForm.addEventListener('submit', function(e) {
        e.preventDefault();

        // Basic Validation
        if (!description.value.trim()) {
            alert('Please enter a description');
            return;
        }

        const btn = txnForm.querySelector('.btn-save');
        const originalText = btn.innerText;
        btn.innerText = '⏳ Saving...';
        btn.disabled = true;

        const formData = {
            account_type: accountType.value,
            description: description.value.trim(),
            conversion_rate: convRate.value,
            dr_usd: drUsd.value,
            cr_usd: crUsd.value,
            dr_inr: drInr.value,
            cr_inr: crInr.value
        };

        fetch('functions/save_cash_bank.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        })
        .then(res => res.json())
        .then(data => {
            btn.innerText = originalText;
            btn.disabled = false;

            if (data.success) {
                alert('✅ ' + data.message);
                resetForm();
            } else {
                alert('❌ Error: ' + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            btn.innerText = originalText;
            btn.disabled = false;
            alert('❌ Network Error. Check console.');
        });
    });

    // --- Reset Logic ---
    window.resetForm = function() {
        txnForm.reset();
        accountType.value = "Cash"; // Default
    };
});